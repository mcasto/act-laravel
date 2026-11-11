<?php

namespace App\Http\Controllers;

use App\Helpers\LocalTime;
use App\Helpers\PatronMail;
use App\Helpers\RefId;
use App\Helpers\TheaterSeason;
use App\Mail\PaymentReminderMailer;
use App\Mail\PurchaseConfirmationMailer;
use App\Mail\TicketSaleMailer;
use App\Models\Angel;
use App\Models\Patron;
use App\Models\PatronFlexPackage;
use App\Models\PaymentMethod;
use App\Models\Performance;
use App\Support\EmailTemplates;
use Illuminate\Validation\ValidationException;
use App\Models\CompTicket;
use App\Models\StandardButton;
use App\Models\Ticket;
use App\Models\TicketSale;
use App\Services\ChangeLogger;
use Carbon\Carbon;
use Exception;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Blade;
use Illuminate\Support\Facades\DB;
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
                'performance.show.paymentRates',
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

                // angel_level lets the ticket sale form tell "Angel with no
                // concession benefits" apart from "not recognized as an
                // Angel" (e.g. Angel record under a spouse's email).
                $angel = $angelByPatron->get($sale->patron_id);
                $arr['patron']['angel_level'] = $angel?->angelLevel?->label;
                $arr['patron']['angel_concession_benefits'] = $angel?->concessionBenefits() ?? [];

                return $arr;
            });

        // Comp tickets no longer need merging in here — every redeemed
        // comp gets its own real TicketSale row (payment_method: comp),
        // created by CompTixController::redeemComp(), so the query above
        // already includes them.
        return $ticketSales
            ->sortByDesc(fn($item) => $item['performance']['date'] ?? '')
            ->values();
    }

    /**
     * Public self-service purchase (PayPal/Transfer/Flex forms on the
     * ticket purchase page). Unauthenticated, so it only accepts the fields
     * those forms actually send — admin-only fields like confirmed,
     * send_mail, front_row and tickets are dropped before handing off to
     * store(), otherwise anyone could post confirmed: true to skip payment,
     * send_mail: false to suppress the patron/admin emails, or type: comp
     * to mint free tickets. Admin entry goes through the auth-gated
     * /admin/ticket-sales route straight to store() instead.
     */
    public function publicStore(Request $request)
    {
        $validated = $request->validate([
            'type'     => 'required|in:paypal,transfer,flex',
            'email'    => 'required|email',
            'quantity' => 'required|integer|min:1',
        ]);

        // Patrons type a ticket count for these methods, so it's capped at
        // the seats left (the purchase page warns them first).
        $performance = Performance::find($request->input('performance_id'));
        if ($performance) {
            $left = $performance->seatsRemaining();
            if ((int) $validated['quantity'] > $left) {
                throw ValidationException::withMessages([
                    'quantity' => $left > 0
                        ? "Sorry, only {$left} " . ($left === 1 ? 'seat is' : 'seats are') . ' left for that performance.'
                        : 'Sorry, that performance has no seats left.',
                ]);
            }
        }

        $request->replace($request->only([
            'type',
            'performance_id',
            'first_name',
            'last_name',
            'email',
            'phone',
            'quantity',
            'transfer_date',
            'special_request',
        ]));

        // Flex balance is enforced server-side, and the booking is optionally
        // held for email confirmation — see FlexRedemptionController.
        if ($validated['type'] === 'flex') {
            return app(FlexRedemptionController::class)->request($request);
        }

        return $this->store($request);
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
            'extra_comp' => 'sometimes|boolean',
            'confirmed' => 'sometimes|boolean',
            'front_row' => 'sometimes|integer|min:0|max:20',
            'allow_angel_benefits' => 'sometimes|boolean',
            'special_seating' => 'sometimes|nullable|string|max:1000',
            'door_last' => 'sometimes|nullable|string|max:255',
            'door_first' => 'sometimes|nullable|string|max:255',
            'comments' => 'sometimes|nullable|string',
            'tickets' => 'sometimes|array',
            'tickets.*' => 'nullable|string|max:255',
        ]);

        $patron = Patron::firstOrCreateByEmail(
            $validated['email'],
            [
                'first_name' => $validated['first_name'],
                'last_name'  => $validated['last_name'],
                'phone'      => $validated['phone'],
            ]
        );

        if ($validated['type'] === 'comp') {
            $performance = Performance::with('show')->find($validated['performance_id']);
            $purchaserName = trim("{$patron->first_name} {$patron->last_name}");

            // Comp tickets are transferable, same as flex — the purchaser
            // (the "-er") stays on the record as CompTicket::name, but
            // whoever each Tickets-section row names (the "-ee") is who
            // actually shows up at the door. A blank row falls back to the
            // purchaser's own name.
            $doorNameFor = function (int $i) use ($validated, $purchaserName): string {
                $submitted = trim($validated['tickets'][$i] ?? '');

                return $submitted !== '' ? $submitted : $purchaserName;
            };

            $quantity = (int) $validated['quantity'];
            $extraComp = (bool) ($validated['extra_comp'] ?? false);

            // Each person gets one comp per show. More than one in a single
            // sale is only allowed as an explicit override (e.g. a board
            // member comping a group) — the admin ticks "Issue additional
            // comps". Without it, quantity > 1 is refused rather than
            // silently issuing just one and dropping the rest.
            if ($quantity > 1 && ! $extraComp) {
                return response()->json([
                    'status'  => 'error',
                    'message' => 'Each person gets one comp per show. To issue '
                        . $quantity . ' comps, check "Issue additional comps" and save again.',
                ]);
            }

            // An unredeemed one (e.g. assigned on the Comp Tickets page but
            // not yet used) is redeemed here rather than issuing a new one.
            // If theirs is already used, a new one is only created when the
            // admin explicitly ticks "Issue additional comps" — otherwise
            // it's refused, since that's how duplicate comps were getting
            // created by mistake.
            // Comps are keyed by email, so match any of the patron's addresses.
            $existingComps = CompTicket::with('performance')
                ->whereIn(DB::raw('LOWER(email)'), $patron->allEmails())
                ->where('show_id', $performance?->show_id)
                ->get();

            $unredeemed = $existingComps->firstWhere('redeemed_at', null);
            $usedComp = $existingComps->whereNotNull('redeemed_at')->sortBy('redeemed_at')->first();

            if (! $unredeemed && $usedComp && ! $extraComp) {
                $number = $usedComp->number !== null
                    ? ' (#' . str_pad((string) $usedComp->number, 3, '0', STR_PAD_LEFT) . ')'
                    : '';
                $when = $usedComp->performance
                    ? " for the {$usedComp->performance->formatted_date} performance"
                    : '';

                return response()->json([
                    'status'  => 'error',
                    'message' => "{$purchaserName} already used their comp ticket{$number}{$when}. "
                        . 'To move it, edit that sale instead. To give them another, check "Issue additional comps" and save again.',
                ]);
            }

            // One CompTicket per seat — each gets its own ticket number,
            // mirrored 1-ticket TicketSale, and confirmation email (see
            // CompTixController::redeemComp()).
            for ($i = 0; $i < $quantity; $i++) {
                $comp = $i === 0 ? $unredeemed : null;

                if (! $comp) {
                    $comp = CompTicket::create([
                        'name'    => $purchaserName,
                        'email'   => $patron->email,
                        'show_id' => $performance?->show_id,
                    ]);
                    $comp->uid = RefId::ref_id($comp->id);
                    $comp->number = $performance?->show?->reserveTicketNumbers(1)[0] ?? null;
                    $comp->save();
                }

                // The form's Door Name belongs to the first seat (it's what
                // Ticket 1's name defaults to); the other seats keep the
                // purchaser as their door name, as before.
                $formDoorName = $i === 0
                    ? array_filter([
                        'door_first' => trim($validated['door_first'] ?? '') ?: null,
                        'door_last'  => trim($validated['door_last'] ?? '') ?: null,
                    ])
                    : [];

                app(CompTixController::class)->redeemComp(
                    $comp->uid,
                    $validated['performance_id'],
                    $doorNameFor($i),
                    $validated['send_mail'],
                    $formDoorName
                );
            }

            return response()->json(['status' => 'success']);
        }

        $paymentMethod = PaymentMethod::where('value', $validated['type'])
            ->first();

        $rec = [
            'patron_id'         => $patron->id,
            'transfer_date'     => $validated['transfer_date'] ?? null,
            'performance_id'    => $validated['performance_id'],
            // Local wall-clock time, same as Fixr sales (sold_at is an
            // uncast DATETIME shown as-is in the admin).
            'sold_at'           => LocalTime::now(),
            'quantity'          => $validated['quantity'],
            'payment_method_id' => $paymentMethod->id,
            'confirmed'         => $validated['confirmed'] ?? false,
            'front_row'         => $validated['front_row'] ?? 0,
            'allow_angel_benefits' => $validated['allow_angel_benefits'] ?? true,
            'special_seating'   => $validated['special_seating'] ?? null,
            'door_last'         => $validated['door_last'] ?? $patron->last_name,
            'door_first'        => $validated['door_first'] ?? $patron->first_name,
            // The admin form always sends comments (pre-filled from the
            // patron's notes); empty means cleared, stored as '' — see
            // update(). Public purchases don't send it, so they get the
            // patron's notes.
            'comments'          => array_key_exists('comments', $validated)
                ? ($validated['comments'] ?? '')
                : $patron->comments,
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

                // The payment method's details section (Admin → Email
                // Templates), e.g. PayPal or Bank Transfer instructions.
                $standardButton = StandardButton::where('key', $validated['type'])->first();
                $sectionView = $standardButton ? "standard-buttons.{$standardButton->key}-confirmation" : null;

                $rawConfirmationBody = $sectionView && EmailTemplates::exists($sectionView)
                    ? EmailTemplates::source($sectionView)
                    : null;
                if ($rawConfirmationBody !== null) {
                    $confirmationData['section_view'] = $sectionView;
                }
                $confirmationData['confirmation_body'] = filled($rawConfirmationBody)
                    ? Blade::render($rawConfirmationBody, $confirmationData, true)
                    : null;
            }

            if ($validated['send_mail']) {
                Mail::to(config('mail.admin_to.address'))->send(new TicketSaleMailer($ticketData));
                PatronMail::to($patron->email)->send(new PurchaseConfirmationMailer($confirmationData));
            }
        } catch (Exception $e) {
            logger()->error('Failed to send ticket sale email', [
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString(),
            ]);
        }

        return response()->json(['transaction_id' => $ticketSale->transaction_id]);
    }

    /**
     * The table's No Show count — how many of the party didn't show,
     * 0 up to the seats the sale still holds (quantity - cancelled).
     */
    public function updateNoShow(Request $request, string $id)
    {
        $rec = TicketSale::findOrFail($id);

        $validated = $request->validate([
            'no_show' => 'required|integer|min:0|max:' . $rec->active_quantity,
        ]);

        $rec->no_show = $validated['no_show'];
        $rec->save();

        return response()->json(['status' => 'success', 'no_show' => $rec->no_show]);
    }

    /**
     * The table's Cancelled count — how many of the sale's tickets were
     * cancelled, 0 up to its quantity. The record is kept either way;
     * deleting a sale is only for a mistaken or test entry. No Show is
     * capped to the seats left.
     *
     * Flex and comp sales: `return` says whether the tickets newly
     * cancelled by this change go back to the patron (the admin is asked).
     * Returned Flex tickets go back to the Flex balance (see
     * TicketSale::used_quantity); a returned comp goes back to being an
     * unused comp on the Comp Tickets list. Lowering the count un-returns
     * from the most recent first.
     */
    public function updateCancelled(Request $request, string $id)
    {
        $rec = TicketSale::with('paymentMethod')->findOrFail($id);

        $validated = $request->validate([
            'cancelled' => 'required|integer|min:0|max:' . (int) $rec->quantity,
            'return'    => 'sometimes|boolean',
        ]);

        $method = $rec->paymentMethod?->value;
        $returnable = in_array($method, ['flex', 'comp'], true);

        $old = (int) $rec->cancelled;
        $new = (int) $validated['cancelled'];
        $returned = (int) $rec->cancelled_returned;

        if ($returnable && $new > $old && ($validated['return'] ?? false)) {
            $returned += $new - $old;
        }
        $returned = min($returned, $new);

        // A comp sale is one ticket, mirrored from a CompTicket whose uid is
        // the sale's transaction_id (CompTixController::redeemComp()).
        // Returning it frees the comp the same way deleting the sale does
        // (destroy()), but the sale itself is kept for the record.
        if ($method === 'comp') {
            $comp = CompTicket::where('uid', $rec->transaction_id)->first();
            $wasReturned = (int) $rec->cancelled_returned > 0;

            if ($comp && $returned > 0 && ! $wasReturned) {
                $comp->update([
                    'ticket_sale_id' => null,
                    'redeemed_at'    => null,
                    'performance_id' => null,
                    'pickup_name'    => null,
                    'number'         => null,
                ]);
            } elseif ($comp && $returned === 0 && $wasReturned) {
                // Un-cancelling a returned comp re-links it — unless the
                // patron has already used it again for another performance.
                if ($comp->ticket_sale_id && $comp->ticket_sale_id !== $rec->id) {
                    return response()->json([
                        'status'  => 'error',
                        'message' => 'This comp was returned and has since been used for another performance, so this sale can\'t be un-cancelled. Issue a new comp instead.',
                    ]);
                }

                $ticket = $rec->tickets()->orderBy('number')->first();
                $comp->update([
                    'ticket_sale_id' => $rec->id,
                    'redeemed_at'    => now(),
                    'performance_id' => $rec->performance_id,
                    'pickup_name'    => $ticket?->name ?? $comp->pickup_name,
                    'number'         => $ticket?->number,
                ]);
            }
        }

        $rec->cancelled = $new;
        $rec->cancelled_returned = $returned;
        $rec->resold = min((int) $rec->resold, $new);
        $rec->no_show = min((int) $rec->no_show, $rec->active_quantity);
        $rec->save();

        return response()->json([
            'status'             => 'success',
            'cancelled'          => $rec->cancelled,
            'cancelled_returned' => $rec->cancelled_returned,
            'resold'             => $rec->resold,
            'no_show'            => $rec->no_show,
        ]);
    }

    /**
     * The table's Resold count — how many of the sale's cancelled tickets
     * were put back on sale. Only resold seats come off the performance's
     * seats taken; never more than were cancelled.
     */
    public function updateResold(Request $request, string $id)
    {
        $rec = TicketSale::findOrFail($id);

        $validated = $request->validate([
            'resold' => 'required|integer|min:0|max:' . (int) $rec->cancelled,
        ]);

        $rec->update(['resold' => $validated['resold']]);

        return response()->json(['status' => 'success', 'resold' => $rec->resold]);
    }

    /**
     * Moves a purchase to another performance of the same show while
     * keeping the original for the record:
     *  - a copy goes onto the new performance with every detail of the
     *    original (payment, paid, seating, door name, comments, …) and
     *    reason_changed "Rescheduled from <old performance>";
     *  - its numbered tickets move with it, so ticket numbers, names and
     *    redemptions carry over (numbers are per show, hence same show only);
     *  - a comp's CompTicket follows the new sale;
     *  - the original drops to quantity 0 (no seats, no reminder, no Flex
     *    use) with reason_changed "Rescheduled to <new performance>".
     * An optional note is added to both reasons.
     */
    public function reschedule(Request $request, string $id)
    {
        $sale = TicketSale::with(['performance', 'paymentMethod'])->findOrFail($id);

        $validated = $request->validate([
            'performance_id' => 'required|integer|exists:performances,id',
            'note'           => 'sometimes|nullable|string|max:500',
        ]);

        $from = $sale->performance;
        $to = Performance::findOrFail($validated['performance_id']);

        if ((int) $sale->quantity === 0) {
            return response()->json(['status' => 'error', 'message' => 'This purchase was already rescheduled — reschedule the new one instead.']);
        }
        if ($to->id === $from?->id) {
            return response()->json(['status' => 'error', 'message' => 'Pick a different performance.']);
        }
        if ($to->show_id !== $from?->show_id) {
            return response()->json(['status' => 'error', 'message' => 'A purchase can only be moved to another performance of the same show.']);
        }

        $label = fn (Performance $p) => "{$p->formatted_date} {$p->formatted_time}";
        $note = trim($validated['note'] ?? '');
        $withNote = fn (string $reason) => $note !== '' ? "{$reason} — {$note}" : $reason;

        $newSale = DB::transaction(function () use ($sale, $from, $to, $label, $withNote) {
            $copy = $sale->replicate(['active_quantity', 'used_quantity', 'held_quantity']);
            $copy->performance_id = $to->id;
            $copy->no_show = 0;
            $copy->reason_changed = $withNote('Rescheduled from ' . $label($from));
            $copy->save();

            $sale->tickets()->update(['ticket_sale_id' => $copy->id]);

            CompTicket::where('ticket_sale_id', $sale->id)->update([
                'ticket_sale_id' => $copy->id,
                'performance_id' => $to->id,
            ]);

            $sale->update([
                'quantity'           => 0,
                'cancelled'          => 0,
                'cancelled_returned' => 0,
                'resold'             => 0,
                'no_show'            => 0,
                'reason_changed'     => $withNote('Rescheduled to ' . $label($to)),
            ]);

            return $copy;
        });

        return response()->json([
            'status'   => 'success',
            'new_id'   => $newSale->id,
            'sales'    => $this->allSales(),
        ]);
    }

    /**
     * The table's Paid toggle — marks a sale paid/unpaid without opening
     * the full edit form.
     */
    public function updatePaid(Request $request, string $id)
    {
        $validated = $request->validate([
            'confirmed' => 'required|boolean',
        ]);

        $rec = TicketSale::findOrFail($id);
        $rec->confirmed = $validated['confirmed'];
        $rec->save();

        return response()->json(['status' => 'success', 'confirmed' => $rec->confirmed]);
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
            'id'            => 'required|integer',
            'type'          => 'required|string',
            'performance_id' => 'required|integer',
            'first_name'    => 'required|string',
            'last_name'     => 'required|string',
            'email'         => 'required|email',
            'phone'         => 'nullable|string',
            'quantity'      => 'required|integer|min:0', // 0 = rescheduled away (see reschedule())
            'transfer_date' => 'sometimes|nullable|date',
            'no_show'       => 'sometimes|integer|min:0',
            'confirmed'     => 'sometimes|boolean',
            'reason_changed' => 'nullable|string',
            'front_row'       => 'sometimes|integer|min:0|max:20',
            'allow_angel_benefits' => 'sometimes|boolean',
            'special_seating' => 'sometimes|nullable|string|max:1000',
            'door_last'       => 'sometimes|nullable|string|max:255',
            'door_first'      => 'sometimes|nullable|string|max:255',
            'comments'        => 'sometimes|nullable|string',
            'tickets'         => 'sometimes|array',
            'tickets.*.id'    => 'required_with:tickets|integer|exists:tickets,id',
            'tickets.*.name'  => 'required_with:tickets|string|max:255',
        ]);

        $patron = Patron::firstOrCreateByEmail(
            $validated['email'],
            [
                'first_name' => $validated['first_name'],
                'last_name'  => $validated['last_name'],
                'phone'      => $validated['phone'],
            ]
        );

        // Comp tickets live in a separate table (see store()'s comp
        // branch) — `id` here is a comp_tickets.id, not a ticket_sales.id,
        // so this used to 422 with "The selected id is invalid" (or worse,
        // could have silently hit an unrelated ticket_sales row if the ids
        // happened to collide) whenever a comp row was edited and saved.
        // Looked up by id rather than trusting the submitted `type`, so a
        // stale/incorrect payment_method value can't misroute this to the
        // wrong table.
        $existingComp = CompTicket::find($validated['id']);

        if ($existingComp) {
            if ($validated['type'] !== 'comp') {
                return response()->json([
                    'status' => 'error',
                    'message' => "Changing a Comp ticket to a different payment method isn't supported — delete and re-add it instead.",
                ]);
            }

            // Only email/name/performance/pickup name actually exist on
            // CompTicket — quantity, confirmed, front_row, special_seating,
            // no_show, reason_changed, door_last/door_first, and comments
            // have no equivalent here and are silently ignored, same as
            // store()'s comp branch.
            $purchaserName = trim("{$patron->first_name} {$patron->last_name}");

            // The Tickets section's one row is the door attendee (the
            // "-ee"), which can differ from the purchaser — same as
            // store()'s comp branch. Previously this was silently ignored
            // here too, so an edited door name never actually saved.
            $submittedDoorName = trim($validated['tickets'][0]['name'] ?? '');
            $doorName = $submittedDoorName !== '' ? $submittedDoorName : ($existingComp->pickup_name ?: $purchaserName);

            $existingComp->update([
                'email'          => $patron->email,
                'name'           => $purchaserName,
                'performance_id' => $validated['performance_id'],
                'pickup_name'    => $doorName,
            ]);

            // Keep the mirrored TicketSale/Ticket in sync too, or they
            // drift back out of sync with the comp they belong to.
            $existingComp->ticketSale?->update([
                'patron_id'      => $patron->id,
                'performance_id' => $validated['performance_id'],
            ]);
            $existingComp->ticketSale?->tickets()->update(['name' => $doorName]);

            return response()->json($this->allSales());
        }

        $paymentMethod = PaymentMethod::where('value', $validated['type'])->first();

        $ticketSale = TicketSale::findOrFail($validated['id']);
        $ticketSale->update([
            'patron_id'         => $patron->id,
            'performance_id'    => $validated['performance_id'],
            'quantity'          => $validated['quantity'],
            'payment_method_id' => $paymentMethod->id,
            // Neither is on the edit form — no_show is set from the table's
            // No Show count (updateNoShow()), transfer_date by the patron's
            // own bank-transfer purchase — so keep what's stored unless sent.
            // A party can't have more no-shows than people, so a lowered
            // quantity caps it.
            'transfer_date'     => $validated['transfer_date'] ?? $ticketSale->transfer_date,
            // Cancelled isn't on the edit form either (it's the table's
            // Cancelled count). Both counts are capped to the new quantity.
            'cancelled'         => min((int) $ticketSale->cancelled, (int) $validated['quantity']),
            'cancelled_returned' => min((int) $ticketSale->cancelled_returned, (int) $ticketSale->cancelled, (int) $validated['quantity']),
            'resold'            => min((int) $ticketSale->resold, (int) $ticketSale->cancelled, (int) $validated['quantity']),
            'no_show'           => min(
                (int) ($validated['no_show'] ?? $ticketSale->no_show),
                max(0, (int) $validated['quantity'] - min((int) $ticketSale->cancelled, (int) $validated['quantity'])),
            ),
            'confirmed'         => $validated['confirmed'] ?? false,
            'reason_changed'    => $validated['reason_changed'] ?? null,
            'front_row'         => $validated['front_row'] ?? 0,
            'allow_angel_benefits' => $validated['allow_angel_benefits'] ?? $ticketSale->allow_angel_benefits,
            'special_seating'   => $validated['special_seating'] ?? null,
            'door_last'         => $validated['door_last'] ?? $ticketSale->door_last ?? $patron->last_name,
            'door_first'        => $validated['door_first'] ?? $ticketSale->door_first ?? $patron->first_name,
            // Sent = what the admin left in the box. Empty means cleared, so
            // store '' (not null): null would make the form re-show the
            // patron's default notes on the next open.
            'comments'          => array_key_exists('comments', $validated)
                ? ($validated['comments'] ?? '')
                : ($ticketSale->comments ?? $patron->comments),
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
        // The admin list is all ticket_sales rows — a redeemed comp is its
        // own TicketSale (see CompTixController::redeemComp()) — so the id
        // is always a TicketSale id, never a CompTicket id. Deleting a comp
        // sale hands the comp back to the person: their CompTicket stays on
        // the show's Comp Tickets list as unredeemed, with its ticket number
        // released (a fresh one is reserved if it's redeemed again — see
        // CompTixController::redeemComp()).
        $sale = TicketSale::findOrFail($request->input('id'));

        DB::transaction(function () use ($sale) {
            CompTicket::where('ticket_sale_id', $sale->id)->update([
                'ticket_sale_id' => null,
                'redeemed_at'    => null,
                'performance_id' => null,
                'pickup_name'    => null,
                'number'         => null,
            ]);
            $sale->delete();
        });

        return response()->json($this->allSales());
    }

    /**
     * Payment reminders for unconfirmed PayPal / Bank Transfer reservations,
     * from the Unconfirmed Payments dialog in AdminTicketSales.vue. One
     * email per patron (a patron with several pending sales gets one email
     * listing them all), sent individually through the default mailer
     * rather than as a BCC blast, which gets rejected or spam-foldered once
     * the list is long. Sales are re-checked here, so one that was
     * confirmed after the dialog loaded is skipped rather than nagged.
     */
    public function sendPaymentReminders(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'ticket_sale_ids'   => 'required|array|min:1',
            'ticket_sale_ids.*' => 'integer',
            'subject'           => 'required|string|max:255',
            'body'              => 'required|string|max:5000',
        ]);

        $sales = TicketSale::with([
                'performance.show',
                'patron',
                'paymentMethod',
                'tickets' => fn ($query) => $query->orderBy('number'),
            ])
            ->whereIn('id', $validated['ticket_sale_ids'])
            ->where('confirmed', false)
            ->notFullyCancelled()
            ->whereHas('paymentMethod', fn ($query) => $query->whereIn('value', ['paypal', 'transfer']))
            ->get()
            ->filter(fn ($sale) => $sale->patron?->email);

        $sent = [];
        $failed = [];

        foreach ($sales->groupBy('patron_id') as $patronSales) {
            $patron = $patronSales->first()->patron;

            $reservations = $patronSales
                ->sortBy(fn ($sale) => $sale->performance?->date . ' ' . $sale->performance?->start_time)
                ->map(fn ($sale) => [
                    'show_name'        => $sale->performance?->show?->name,
                    'performance_date' => $sale->performance ? Carbon::parse($sale->performance->date)->format('F j, Y') : null,
                    'performance_time' => $sale->performance ? Carbon::parse($sale->performance->start_time)->format('g:i A') : null,
                    'num_tickets'      => $sale->active_quantity,
                    'payment_method'   => $sale->paymentMethod?->label,
                    'reference_number' => $sale->tickets->pluck('formatted_number')->implode(', '),
                ])
                ->values()
                ->all();

            try {
                PatronMail::to($patron->email)->send(new PaymentReminderMailer([
                    'subject'      => $validated['subject'],
                    'body'         => $validated['body'],
                    'name'         => trim("{$patron->first_name} {$patron->last_name}"),
                    'reservations' => $reservations,
                ]));
                $sent[] = $patron->email;
            } catch (Exception $e) {
                logger()->error('Failed to send payment reminder email', [
                    'error'     => $e->getMessage(),
                    'patron_id' => $patron->id,
                ]);
                $failed[] = $patron->email;
            }
        }

        ChangeLogger::record('Sent payment reminders for unconfirmed ticket sales', [
            'subject' => $validated['subject'],
            'sent'    => $sent,
            'failed'  => $failed,
        ]);

        return response()->json([
            'status' => 'success',
            'sent'   => count($sent),
            'failed' => $failed,
        ]);
    }

    /**
     * Seating summary for a performance — shown on the New/Edit Ticket
     * Sale form once a performance is picked, so the admin can see how
     * many front-row seats are already committed and how full the show
     * is before adding another sale. front_row is cumulative across two
     * sources: each patron's own standing accessibility need
     * (Patron::front_row, counted once per distinct patron even if they
     * have multiple sales for this performance) and each sale's own
     * reserved-party count (TicketSale::front_row, summed per sale since
     * each represents an independent reservation).
     */
    public function seatingSummary(int $performanceId): JsonResponse
    {
        $summary = Performance::findOrFail($performanceId)->salesSummary();

        return response()->json([
            'front_row_total' => $summary['front_row_total'],
            'reservations_total' => $summary['reserved'],
            'seats_taken' => $summary['held'],
            'sold_out_target' => $summary['sold_out_target'],
        ]);
    }
}
