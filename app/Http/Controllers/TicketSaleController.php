<?php

namespace App\Http\Controllers;

use App\Helpers\RefId;
use App\Helpers\TheaterSeason;
use App\Mail\PurchaseConfirmationMailer;
use App\Mail\TicketSaleMailer;
use App\Models\Angel;
use App\Models\Patron;
use App\Models\PatronFlexPackage;
use App\Models\PaymentMethod;
use App\Models\Performance;
use App\Models\CompTicket;
use App\Models\StandardButton;
use App\Models\Ticket;
use App\Models\TicketSale;
use Carbon\Carbon;
use Exception;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Blade;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Facades\Storage;

class TicketSaleController extends Controller
{
    public function index()
    {
        return response()->json($this->allSales());
    }

    private function allSales()
    {
        $compPaymentMethod = PaymentMethod::where('value', 'comp')->first();

        // Keyed by patron_id -> that patron's Angel record for the current
        // (calendar) season, so each ticket sale can list which of their
        // level's benefits are concession-related (for the door/box-office
        // print sheet — see AdminTicketSalesPrint.vue). Deliberately the
        // real calendar-current season, not ActiveSeason::get()'s
        // early-flip override — that override is only for tagging brand
        // new donations ahead of time, and a show happening now is always
        // for the real current season regardless of that promo flip.
        $angelByPatron = Angel::whereNotNull('patron_id')
            ->where('season', TheaterSeason::currentString())
            ->with('angelLevel')
            ->get()
            ->keyBy('patron_id');

        $ticketSales = TicketSale::with([
                'performance.show',
                'patron',
                'paymentMethod',
                'tickets' => fn ($query) => $query->orderBy('number'),
            ])
            ->join('performances', 'ticket_sales.performance_id', '=', 'performances.id')
            ->orderBy('performances.date', 'desc')
            ->orderBy('performances.start_time', 'asc')
            ->select('ticket_sales.*')
            ->get()
            ->map(function ($sale) use ($angelByPatron) {
                $arr = $sale->toArray();

                $angel = $angelByPatron->get($sale->patron_id);
                $arr['patron']['angel_concession_benefits'] = $angel
                    ? collect($angel->angelLevel?->benefits ?? [])
                        ->filter(fn ($benefit) => $benefit->concession ?? false)
                        ->pluck('text')
                        ->values()
                        ->all()
                    : [];

                return $arr;
            });

        $compTickets = CompTicket::with('performance.show')
            ->join('performances', 'comp_tickets.performance_id', '=', 'performances.id')
            ->select('comp_tickets.*')
            ->get()
            ->map(fn($comp) => [
                'id'             => $comp->id,
                'quantity'       => 1,
                'number'         => $comp->number,
                'sold_at'        => $comp->redeemed_at ?? $comp->sent_at,
                'transaction_id' => $comp->uid,
                'patron'         => [
                    'first_name'   => $comp->name,
                    'last_name'    => '',
                    'email'        => $comp->email,
                    'phone'        => null,
                    'pickup_name'  => $comp->pickup_name,
                ],
                'payment_method' => $compPaymentMethod,
                'performance'    => $comp->performance,
            ]);

        return $ticketSales->concat($compTickets)
            ->sortByDesc(fn($item) => $item['performance']['date'] ?? '')
            ->values();
    }

    public function store(Request $request)
    {
        $request->mergeIfMissing(['send_mail' => true]);

        $validated = $request->validate([
            'type' => 'required|string',
            'performance_id' => 'required|integer',
            'first_name' => 'required|string',
            'last_name'  => 'required|string',
            'email'      => 'required|email',
            'phone'      => 'nullable|string',
            'quantity' => 'required|integer|min:1',
            'transfer_date' => 'sometimes|nullable|date',
            'special_request' => 'sometimes|nullable|string',
            'send_mail' => 'sometimes|boolean',
            'confirmed' => 'sometimes|boolean',
            'special_seating' => 'sometimes|integer|min:0|max:20',
            'tickets' => 'sometimes|array',
            'tickets.*' => 'nullable|string|max:255',
        ]);

        $patron = Patron::firstOrCreate(
            ['email' => $validated['email']],
            [
                'first_name' => $validated['first_name'],
                'last_name'  => $validated['last_name'],
                'phone'      => $validated['phone'],
            ]
        );

        if ($validated['type'] === 'comp') {
            $performance = Performance::with('show')->find($validated['performance_id']);
            $pickupName  = $patron->first_name . ' ' . $patron->last_name;

            $comp = CompTicket::where('email', $patron->email)
                ->where('show_id', $performance?->show_id)
                ->whereNull('redeemed_at')
                ->first();

            if (! $comp) {
                $comp = CompTicket::create([
                    'name'    => $pickupName,
                    'email'   => $patron->email,
                    'show_id' => $performance?->show_id,
                ]);
                $comp->uid = RefId::ref_id($comp->id);
                $comp->number = $performance?->show?->reserveTicketNumbers(1)[0] ?? null;
                $comp->save();
            }

            app(CompTixController::class)->redeemComp(
                $comp->uid,
                $validated['performance_id'],
                $pickupName,
                $validated['send_mail']
            );

            return response()->json(['status' => 'success']);
        }

        $paymentMethod = PaymentMethod::where('value', $validated['type'])
            ->first();

        $rec = [
            'patron_id'         => $patron->id,
            'transfer_date'     => $validated['transfer_date'] ?? null,
            'performance_id'    => $validated['performance_id'],
            'sold_at'           => now(),
            'quantity'          => $validated['quantity'],
            'payment_method_id' => $paymentMethod->id,
            'confirmed'         => $validated['confirmed'] ?? false,
            'special_seating'   => $validated['special_seating'] ?? 0,
        ];

        $ticketSale = TicketSale::create($rec);
        $ticketSale->transaction_id = RefId::ref_id($ticketSale->id);
        $ticketSale->save();
        $ticketSale->issueTickets($patron->first_name . ' ' . $patron->last_name, $validated['tickets'] ?? []);

        try {
            $performance = Performance::with('show')->find($validated['performance_id']);
            $ticketNumbers = $ticketSale->tickets()->orderBy('number')->get()->pluck('formatted_number')->all();

            $ticketData = [
                'show'           => $performance?->show?->name,
                'performance'    => $performance ? $performance->date . ' ' . $performance->start_time : null,
                'first_name'     => $patron->first_name,
                'last_name'      => $patron->last_name,
                'email'          => $patron->email,
                'mobile_number'  => $patron->phone,
                'payment_method' => $paymentMethod?->label,
                'quantity'       => $validated['quantity'],
                'sold_at'        => $rec['sold_at'],
                'special_request' => $validated['special_request'] ?? null,
                'ticket_numbers' => $ticketNumbers,
            ];

            $confirmationData = [
                'name'             => $patron->first_name . ' ' . $patron->last_name,
                'show_name'        => $performance?->show?->name,
                'num_tickets'      => $validated['quantity'],
                'performance_date' => $performance ? Carbon::parse($performance->date)->format('F j, Y') : null,
                'performance_time' => $performance ? Carbon::parse($performance->start_time)->format('g:i A') : null,
                'reference_number' => implode(', ', $ticketNumbers),
            ];

            if ($validated['type'] === 'flex') {
                $season = TheaterSeason::currentString();
                $flexPackage = PatronFlexPackage::where('patron_id', $patron->id)
                    ->where('season', $season)
                    ->first();

                $flexConfig = json_decode(Storage::disk('local')->get('flex-purchase-config.json'), true);

                $confirmationData['view']           = 'flex-confirmation';
                $confirmationData['remaining_flex'] = $flexPackage?->ticketsRemaining() ?? 0;
                $confirmationData['season']         = $season;

                $rawConfirmationBody = $flexConfig['confirmation_body'] ?? null;
                $confirmationData['confirmation_body'] = $rawConfirmationBody
                    ? Blade::render($rawConfirmationBody, $confirmationData, true)
                    : null;
            } else {
                $confirmationData['view'] = 'purchase-confirmation';

                $standardButton = StandardButton::where('key', $validated['type'])->first();
                $confirmationPath = $standardButton
                    ? resource_path("views/standard-buttons/{$standardButton->key}-confirmation.blade.php")
                    : null;

                $rawConfirmationBody = ($confirmationPath && file_exists($confirmationPath))
                    ? file_get_contents($confirmationPath)
                    : null;
                $confirmationData['confirmation_body'] = $rawConfirmationBody
                    ? Blade::render($rawConfirmationBody, $confirmationData, true)
                    : null;
            }

            if ($validated['send_mail']) {
                Mail::to(config('mail.admin_to.address'))->send(new TicketSaleMailer($ticketData));
                Mail::to($patron->email)->send(new PurchaseConfirmationMailer($confirmationData));
            }
        } catch (Exception $e) {
            logger()->error('Failed to send ticket sale email', [
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString(),
            ]);
        }

        return response()->json(['transaction_id' => $ticketSale->transaction_id]);
    }

    public function updateNoShow(Request $request, string $id)
    {
        $rec = TicketSale::findOrFail($id);
        $rec->no_show = $request->input('no_show');
        $rec->save();

        return response()->json(['rec' => $rec, 'id' => $id]);
    }

    /**
     * Marks individual tickets on this sale as redeemed/un-redeemed — a
     * separate, focused action from the main update() form, since it's
     * driven by the table's "Tickets" cell dialog rather than the edit
     * screen. Tickets don't all redeem together (a group of 3 can show up
     * across two separate arrivals), so each is toggled independently.
     */
    public function redeemTickets(Request $request, string $id)
    {
        $ticketSale = TicketSale::findOrFail($id);

        $validated = $request->validate([
            'tickets' => 'required|array',
            'tickets.*.id' => 'required|integer|exists:tickets,id',
            'tickets.*.redeemed' => 'required|boolean',
        ]);

        // The dialog submits every ticket's current state on each save, not
        // just the ones that changed — only touch redeemed_at when the
        // redeemed flag actually flips, so an already-redeemed ticket keeps
        // its original timestamp instead of getting bumped to now() again.
        foreach ($validated['tickets'] as $ticket) {
            $ticketModel = Ticket::where('id', $ticket['id'])
                ->where('ticket_sale_id', $ticketSale->id)
                ->first();

            if (! $ticketModel) {
                continue;
            }

            if ($ticket['redeemed'] && ! $ticketModel->redeemed_at) {
                $ticketModel->update(['redeemed_at' => now()]);
            } elseif (! $ticket['redeemed'] && $ticketModel->redeemed_at) {
                $ticketModel->update(['redeemed_at' => null]);
            }
        }

        return response()->json([
            'status' => 'success',
            'tickets' => $ticketSale->tickets()->orderBy('number')->get(),
        ]);
    }

    public function update(Request $request)
    {
        $validated = $request->validate([
            'id'            => 'required|integer|exists:ticket_sales,id',
            'type'          => 'required|string',
            'performance_id' => 'required|integer',
            'first_name'    => 'required|string',
            'last_name'     => 'required|string',
            'email'         => 'required|email',
            'phone'         => 'nullable|string',
            'quantity'      => 'required|integer|min:1',
            'transfer_date' => 'sometimes|nullable|date',
            'no_show'       => 'sometimes|boolean',
            'confirmed'     => 'sometimes|boolean',
            'reason_changed' => 'nullable|string',
            'special_seating' => 'sometimes|integer|min:0|max:20',
            'tickets'         => 'sometimes|array',
            'tickets.*.id'    => 'required_with:tickets|integer|exists:tickets,id',
            'tickets.*.name'  => 'required_with:tickets|string|max:255',
        ]);

        $patron = Patron::firstOrCreate(
            ['email' => $validated['email']],
            [
                'first_name' => $validated['first_name'],
                'last_name'  => $validated['last_name'],
                'phone'      => $validated['phone'],
            ]
        );

        $paymentMethod = PaymentMethod::where('value', $validated['type'])->first();

        $ticketSale = TicketSale::findOrFail($validated['id']);
        $ticketSale->update([
            'patron_id'         => $patron->id,
            'performance_id'    => $validated['performance_id'],
            'quantity'          => $validated['quantity'],
            'payment_method_id' => $paymentMethod->id,
            'transfer_date'     => $validated['transfer_date'] ?? null,
            'no_show'           => $validated['no_show'] ?? false,
            'confirmed'         => $validated['confirmed'] ?? false,
            'reason_changed'    => $validated['reason_changed'] ?? null,
            'special_seating'   => $validated['special_seating'] ?? 0,
        ]);

        foreach ($validated['tickets'] ?? [] as $ticket) {
            Ticket::where('id', $ticket['id'])
                ->where('ticket_sale_id', $ticketSale->id)
                ->update(['name' => $ticket['name']]);
        }

        $ticketSale->reconcileTicketCount($validated['quantity']);

        return response()->json($this->allSales());
    }

    public function destroy(Request $request)
    {
        $id = $request->input('id');

        if ($request->input('payment_method.value') === 'comp') {
            CompTicket::findOrFail($id)->delete();
        } else {
            TicketSale::findOrFail($id)->delete();
        }

        return response()->json($this->allSales());
    }
}
