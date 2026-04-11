<?php

namespace App\Http\Controllers;

use App\Mail\TicketSaleMailer;
use App\Models\Patron;
use App\Models\PaymentMethod;
use App\Models\Performance;
use App\Models\TicketSale;
use Exception;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Str;

class TicketSaleController extends Controller
{
    public function index()
    {
        return response()->json(TicketSale::with('performance.show')
            ->join('performances', 'ticket_sales.performance_id', '=', 'performances.id')
            ->orderBy('performances.date', 'desc')
            ->orderBy('performances.start_time', 'asc')
            ->select('ticket_sales.*')
            ->get());
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
            'quantity' => 'required|integer',
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

        $paymentMethod = PaymentMethod::where('value', $validated['type'])
            ->first();

        $rec = [
            'patron_id'         => $patron->id,
            'transaction_id'    => Str::uuid(),
            'transfer_date'     => $validated['transfer_date'] ?? null,
            'performance_id'    => $validated['performance_id'],
            'sold_at'           => now(),
            'quantity'          => $validated['quantity'],
            'payment_method_id' => $paymentMethod->id,
        ];

        TicketSale::create($rec);

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

            Mail::to(config('mail.to.address'))->send(new TicketSaleMailer($ticketData));
        } catch (Exception $e) {
            logger()->error('Failed to send ticket sale email', [
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString(),
            ]);
        }

        return response()->json(['transaction_id' => $rec['transaction_id']]);
    }
}
