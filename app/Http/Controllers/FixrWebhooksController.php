<?php

namespace App\Http\Controllers;

use App\Models\FixrWebhookResponse;
use App\Models\Patron;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;

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
    public function create(Request $request): JsonResponse
    {
        $expectedToken = env('FIXR_AUTH_TOKEN');
        $token         = $request->header('Authorization');
        if ($token != $expectedToken) {
            return response()->json(['error' => 'Invalid Authorization', 'message' => 'Auth token not recognized']);
        }

        $data = $request->all();

        $payload = json_encode($data['payload']);

        $rec = [
            'patron_id'  => $this->findPatronId($data),
            'event'      => $data['event'],
            'payload'    => $payload,
            'message_id' => $data['message_id'],
        ];

        FixrWebhookResponse::create($rec);

        // mc-todo: send e-mail to SiteConfig->ticketEmail if event is ticket sale

        return response()->json(['message' => 'Webhook received'], 201);
    }
}
