<?php

namespace App\Http\Controllers;

use App\Helpers\ActiveSeason;
use App\Mail\AngelDonationMailer;
use App\Models\Angel;
use App\Models\AngelLevel;
use App\Models\FixrWebhookResponse;
use App\Models\Patron;
use App\Models\PaymentMethod;
use App\Models\Performance;
use App\Models\TicketSale;
use Carbon\Carbon;
use Exception;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Facades\Storage;
use Illuminate\Validation\ValidationException;

class FixrWebhooksController extends Controller
{
    /**
     * Receive a Fixr "ticket_sold" webhook, log the raw payload, and record
     * it as a TicketSale against the matching Performance — or, if the event
     * id instead matches an Angel level's Fixr link, as an Angel donation.
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
                'payload.price.amount' => 'nullable|numeric',
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
        $holder = $validated['payload']['ticket_holders'][0];

        // Fixr is just the payment processor for credit card purchases, not a
        // distinct payment method — recorded as "credit_card" so the label
        // stays accurate if the processor ever changes.
        $creditCardMethod = PaymentMethod::where('value', 'credit_card')->first();

        $performance = $this->findByFixrLink(Performance::whereNotNull('fixr_link')->get(), $eventId);

        if ($performance) {
            $patron = Patron::firstOrCreate(
                ['email' => $holder['email']],
                [
                    'first_name' => $holder['first_name'],
                    'last_name' => $holder['last_name'],
                    'phone' => $holder['mobile_number'] ?? null,
                ]
            );

            $ticketSale = TicketSale::create([
                'patron_id' => $patron->id,
                'performance_id' => $performance->id,
                'payment_method_id' => $creditCardMethod?->id,
                'sold_at' => Carbon::parse($validated['payload']['sold_at'])->setTimezone('America/Guayaquil'),
                'quantity' => $validated['payload']['quantity'],
                'transaction_id' => $validated['payload']['order_reference'],
                'confirmed' => true,
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

        $angelLevel = $this->findByFixrLink(AngelLevel::whereNotNull('fixr_link')->get(), $eventId);

        if ($angelLevel) {
            $angel = Angel::create([
                'angel_level_id' => $angelLevel->id,
                'first_name' => $holder['first_name'],
                'last_name' => $holder['last_name'],
                'email' => $holder['email'],
                'recognition_name' => trim($holder['first_name'] . ' ' . $holder['last_name']),
                // Not payload.price.amount — Fixr reports that net of their
                // processing fee, but Angel donations are always fixed at
                // the level's pledged amount (see AngelPage.vue), so that's
                // the figure that belongs on record.
                'donation_amount' => $angelLevel->min_amount,
                'payment_method_id' => $creditCardMethod?->id,
                'benefit' => implode("\n", $angelLevel->benefits ?? []),
                'season' => ActiveSeason::get(),
                // Founding-angel status is permanent for a given donor —
                // only inherited from a past record under this name, never
                // set fresh here.
                'founding_angel' => Angel::wasFoundingAngel($holder['first_name'], $holder['last_name']),
            ]);

            try {
                Mail::to(config('mail.admin_to.address'))
                    ->send(new AngelDonationMailer($angel));
            } catch (Exception $e) {
                logger()->error('Failed to send Angel donation notification email', [
                    'error' => $e->getMessage(),
                    'angel_id' => $angel->id,
                ]);
            }

            FixrWebhookResponse::create([
                'patron_id' => null,
                'event' => $validated['event'],
                'payload' => json_encode($request->all()),
                'message_id' => $validated['message_id'],
            ]);

            return response()->json([
                'status' => 'success',
                'angel_id' => $angel->id,
            ], 200);
        }

        logger()->error('Fixr webhook: no performance or angel level found matching event_id', ['event_id' => $eventId]);

        return response()->json([
            'status' => 'error',
            'message' => 'No matching performance or angel level found',
        ], 422);
    }

    /**
     * Match the same way the frontend does when opening the Fixr checkout
     * (frontend/src/stores/actions/open-fixr.js): trailing digits of the
     * link's path, not an exact URL match, since fixr_link may be either
     * the public or the organizer link for the same event.
     *
     * @param iterable<object{fixr_link: ?string}> $records
     */
    private function findByFixrLink(iterable $records, string $eventId): mixed
    {
        foreach ($records as $record) {
            $path = parse_url($record->fixr_link, PHP_URL_PATH);

            if (! $path || ! preg_match('/[0-9]+$/', $path, $matches)) {
                continue;
            }

            if ($matches[0] === $eventId) {
                return $record;
            }
        }

        return null;
    }
}
