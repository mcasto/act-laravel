<?php

namespace App\Http\Controllers;

use App\Models\FixrWebhookResponse;
use App\Models\Patron;
use App\Models\PaymentMethod;
use App\Models\Performance;
use App\Models\TicketSale;
use Carbon\Carbon;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;
use Illuminate\Validation\ValidationException;

class FixrWebhooksController extends Controller
{
    /**
     * Receive a Fixr "ticket_sold" webhook, log the raw payload, and record
     * the sale as a TicketSale against the matching Performance.
     */
    public function create(Request $request)
    {
        $filename = "logs/fixr_webhook_" . date('Y_m_d_H_i_s') . ".log";
        Storage::put($filename, print_r($request->all(), true));

        try {
            $validated = $request->validate([
                'event' => 'required|string',
                'message_id' => 'required|string',
                'payload' => 'required|array',
                'payload.event_id' => 'required',
                'payload.order_reference' => 'required|string',
                'payload.sold_at' => 'required|date',
                'payload.quantity' => 'required|integer',
                'payload.ticket_holders' => 'required|array|min:1',
                'payload.ticket_holders.*.first_name' => 'required|string',
                'payload.ticket_holders.*.last_name' => 'required|string',
                'payload.ticket_holders.*.email' => 'required|email',
                'payload.ticket_holders.*.mobile_number' => 'nullable|string',
            ]);
        } catch (ValidationException $e) {
            logger()->error('Fixr webhook validation failed', ['errors' => $e->errors()]);

            return response()->json([
                'status' => 'error',
                'message' => 'Validation failed',
                'errors' => $e->errors(),
            ], 422);
        }

        if (FixrWebhookResponse::where('message_id', $validated['message_id'])->exists()) {
            return response()->json(['status' => 'duplicate'], 200);
        }

        $eventId = (string) $validated['payload']['event_id'];

        // Match the same way the frontend does when opening the Fixr checkout
        // (frontend/src/stores/actions/open-fixr.js): trailing digits of the
        // link's path, not an exact URL match, since fixr_link may be either
        // the public or the organizer link for the same event.
        $performance = Performance::whereNotNull('fixr_link')
            ->get()
            ->first(function (Performance $performance) use ($eventId) {
                $path = parse_url($performance->fixr_link, PHP_URL_PATH);

                if (! $path || ! preg_match('/[0-9]+$/', $path, $matches)) {
                    return false;
                }

                return $matches[0] === $eventId;
            });

        if (! $performance) {
            logger()->error('Fixr webhook: no performance found matching event_id', ['event_id' => $eventId]);

            return response()->json([
                'status' => 'error',
                'message' => 'No matching performance found',
            ], 422);
        }

        $holder = $validated['payload']['ticket_holders'][0];

        $patron = Patron::firstOrCreate(
            ['email' => $holder['email']],
            [
                'first_name' => $holder['first_name'],
                'last_name' => $holder['last_name'],
                'phone' => $holder['mobile_number'] ?? null,
            ]
        );

        $fixrPaymentMethod = PaymentMethod::where('value', 'fixr')->first();

        $ticketSale = TicketSale::create([
            'patron_id' => $patron->id,
            'performance_id' => $performance->id,
            'payment_method_id' => $fixrPaymentMethod?->id,
            'sold_at' => Carbon::parse($validated['payload']['sold_at'])->setTimezone('America/Guayaquil'),
            'quantity' => $validated['payload']['quantity'],
            'transaction_id' => $validated['payload']['order_reference'],
        ]);

        FixrWebhookResponse::create([
            'patron_id' => $patron->id,
            'event' => $validated['event'],
            'payload' => json_encode($request->all()),
            'message_id' => $validated['message_id'],
        ]);

        return response()->json([
            'status' => 'success',
            'ticket_sale_id' => $ticketSale->id,
        ], 200);
    }
}
