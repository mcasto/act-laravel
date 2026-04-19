<?php

namespace App\Http\Controllers;

use App\Helpers\RefId;
use App\Helpers\TheaterSeason;
use App\Mail\PurchaseConfirmationMailer;
use App\Mail\TicketSaleMailer;
use App\Models\Patron;
use App\Models\PatronFlexPackage;
use App\Models\PaymentMethod;
use App\Models\Performance;
use App\Models\CompTicket;
use App\Models\TicketSale;
use Carbon\Carbon;
use Exception;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Str;

class TicketSaleController extends Controller
{
    public function index()
    {
        return response()->json($this->allSales());
    }

    private function allSales()
    {
        $compPaymentMethod = PaymentMethod::where('value', 'comp')->first();

        $ticketSales = TicketSale::with('performance.show', 'patron', 'paymentMethod')
            ->join('performances', 'ticket_sales.performance_id', '=', 'performances.id')
            ->orderBy('performances.date', 'desc')
            ->orderBy('performances.start_time', 'asc')
            ->select('ticket_sales.*')
            ->get()
            ->map(fn($sale) => $sale->toArray());

        $compTickets = CompTicket::with('performance.show')
            ->join('performances', 'comp_tickets.performance_id', '=', 'performances.id')
            ->select('comp_tickets.*')
            ->get()
            ->map(fn($comp) => [
                'id'             => $comp->id,
                'quantity'       => 1,
                'sold_at'        => $comp->redeemed_at ?? $comp->sent_at,
                'transaction_id' => $comp->uid,
                'patron'         => [
                    'first_name' => $comp->name,
                    'last_name'  => '',
                    'email'      => $comp->email,
                    'phone'      => null,
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
        $validated = $request->validate([
            'type' => 'required|string',
            'performance_id' => 'required|integer',
            'first_name' => 'required|string',
            'last_name'  => 'required|string',
            'email'      => 'required|email',
            'phone'      => 'required|string',
            'quantity' => 'required|integer|min:1',
            'transfer_date' => 'sometimes|nullable|date'
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
                $comp->save();
            }

            app(CompTixController::class)->redeemComp(
                $comp->uid,
                $validated['performance_id'],
                $pickupName
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
        ];

        $ticketSale = TicketSale::create($rec);
        $ticketSale->transaction_id = RefId::ref_id($ticketSale->id);
        $ticketSale->save();

        try {
            $performance = Performance::with('show')->find($validated['performance_id']);

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
            ];

            $confirmationData = [
                'name'             => $patron->first_name . ' ' . $patron->last_name,
                'show_name'        => $performance?->show?->name,
                'num_tickets'      => $validated['quantity'],
                'performance_date' => $performance ? Carbon::parse($performance->date)->format('F j, Y') : null,
                'performance_time' => $performance ? Carbon::parse($performance->start_time)->format('g:i A') : null,
            ];

            if ($validated['type'] === 'flex') {
                $season = TheaterSeason::currentString();
                $flexPackage = PatronFlexPackage::where('patron_id', $patron->id)
                    ->where('season', $season)
                    ->first();

                $confirmationData['view']           = 'flex-confirmation';
                $confirmationData['remaining_flex'] = $flexPackage?->ticketsRemaining() ?? 0;
                $confirmationData['season']         = $season;
            } else {
                $confirmationData['view'] = 'purchase-confirmation';
            }

            Mail::to(config('mail.admin_to.address'))->send(new TicketSaleMailer($ticketData));

            Mail::to($patron->email)->send(new PurchaseConfirmationMailer($confirmationData));
        } catch (Exception $e) {
            logger()->error('Failed to send ticket sale email', [
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString(),
            ]);
        }

        return response()->json(['transaction_id' => $ticketSale->transaction_id]);
    }

    public function update(Request $request)
    {
        $validated = $request->validate([
            'id'            => 'required|integer|exists:ticket_sales,id',
            'type'          => 'required|string',
            'performance_id'=> 'required|integer',
            'first_name'    => 'required|string',
            'last_name'     => 'required|string',
            'email'         => 'required|email',
            'phone'         => 'required|string',
            'quantity'      => 'required|integer|min:1',
            'transfer_date' => 'sometimes|nullable|date',
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
        ]);

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
