<?php

namespace App\Http\Controllers;

use App\Models\Patron;
use App\Models\TicketSale;
use Carbon\Carbon;
use Exception;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Mail;
use App\Mail\TicketSaleMailer;

class FixrWebhooksController extends Controller
{
    /**
     * Find patron ID if necessary and it exists
     *
     * For ticket_sold events, attempts to find the patron by email
     * from the ticket holder information. Returns null for other event types
     * or if patron is not found.
     *
     * @param array $data The webhook data containing event type and payload
     * @return int|null The patron ID or null
     *
     * @source Database Model: Patron (reads)
     */
    private function findPatronId(array $data): ?int
    {
        if ($data['event'] !== 'ticket_sold') {
            // patron_id is unneeded
            return null;
        }

        $patron = Patron::where('email', $data['payload']['ticket_holders'][0]['email'])->first();

        return $patron?->id; // Uses null-safe operator (PHP 8+)
    }

    /**
     * Receive and log Fixr webhook data
     *
     * Authenticates the webhook using a token, extracts event data,
     * associates it with a patron if applicable, and stores the webhook
     * response in the database for auditing purposes.
     *
     * @param Request $request The webhook request from Fixr containing event and payload data
     * @return JsonResponse Success message or authorization error
     *
     * @source Database Models:
     *   - Patron (reads via findPatronId)
     *   - FixrWebhookResponse (creates)
     *
     * @todo Send email to SiteConfig->ticketEmail if the event is a ticket sale
     */
    public function create(Request $request)
    {
        $validated = $request->validate([
            'payload' => 'required|array',
            'payload.event_name' => 'required|string',
            'payload.event_url' => 'required|url',
            'payload.sold_at' => 'required|date',
            'payload.ticket_holders' => 'required|array|min:1',
            'payload.ticket_holders.*.first_name' => 'required|string',
            'payload.ticket_holders.*.last_name' => 'required|string',
            'payload.ticket_holders.*.email' => 'required|email',
            'payload.ticket_holders.*.mobile_number' => 'required|string',
            'payload.ticket_holders.*.contact_preferences_user_response' => 'required|string',
        ]);

        // Fetch and extract event date from Fixr page
        $response = Http::withHeaders([
            'User-Agent' => 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36'
        ])->get($validated['payload']['event_url']);

        $html = $response->body();
        $performanceDateTime = null;

        if (preg_match('/<script id="__NEXT_DATA__" type="application\/json">(.*?)<\/script>/', $html, $matches)) {
            $jsonData = json_decode($matches[1], true);
            $eventData = $jsonData['props']['pageProps']['meta'] ?? null;

            if ($eventData && isset($eventData['openTimeVenueLocalised'])) {
                $performanceDateTime = $eventData['openTimeVenueLocalised']; // Keep raw format
            }
        }

        // Build records for each ticket holder
        $records = [];
        foreach ($validated['payload']['ticket_holders'] as $holder) {
            $rec = [
                'show' => $validated['payload']['event_name'],
                'sold_at' => Carbon::parse($validated['payload']['sold_at'])->format('Y-m-d H:i:s'),
                'first_name' => $holder['first_name'],
                'last_name' => $holder['last_name'],
                'email' => $holder['email'],
                'mobile_number' => $holder['mobile_number'],
                'contact_preferences_user_response' => $holder['contact_preferences_user_response'],
                'performance' => $performanceDateTime ? Carbon::parse($performanceDateTime)->format('Y-m-d H:i:s') : null,
            ];

            $records[] = $rec;
        }

        // Now insert into database
        foreach ($records as $rec) {
            TicketSale::create($rec);

            // Send notification email
            try {
                Mail::to(config('mail.to.address'))
                    ->send(new TicketSaleMailer($rec));
            } catch (Exception $e) {
                logger()->error('Failed to send ticket sale email', ['error' => $e->getMessage(), 'rec' => $rec]);
            }
        }

        return ['records' => $records];
    }
}
