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
use App\Models\Show;

class FixrWebhooksController extends Controller
{
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
        try {
            $validated = $request->validate([
                'payload' => 'required|array',
                'payload.event_name' => 'required|string',
                'payload.quantity' => 'nullable|integer',
                'payload.event_url' => 'required|url',
                'payload.sold_at' => 'required|date',
                'payload.ticket_holders' => 'required|array|min:1',
                'payload.ticket_holders.*.first_name' => 'required|string',
                'payload.ticket_holders.*.last_name' => 'required|string',
                'payload.ticket_holders.*.email' => 'required|email',
                'payload.ticket_holders.*.mobile_number' => 'required|string',
                'payload.ticket_holders.*.contact_preferences_user_response' => 'required|string',
            ]);

            $response = Http::withHeaders([
                'User-Agent' => 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36'
            ])->get($validated['payload']['event_url']);

            $html = $response->body();
            $performanceDateTime = null;

            if (preg_match('/<script id="__NEXT_DATA__" type="application\/json">(.*?)<\/script>/', $html, $matches)) {
                $jsonData = json_decode($matches[1], true);
                $eventData = $jsonData['props']['pageProps']['meta'] ?? null;

                if ($eventData && isset($eventData['openTimeVenueLocalised'])) {
                    $performanceDateTime = $eventData['openTimeVenueLocalised'];
                } else {
                    logger()->warning('No openTimeVenueLocalised found in event data');
                }
            } else {
                logger()->warning('Could not extract __NEXT_DATA__ from HTML');
            }

            if ($performanceDateTime) {
                // Get show based on event_name
                $showName = strtolower($validated['payload']['event_name']);
                $show = Show::whereRaw('LOWER(name) = ?', [$showName])->first();

                if ($show) {
                    // Parse the performance datetime
                    $performanceDate = Carbon::parse($performanceDateTime);

                    // Find the matching performance
                    $performance = $show->performances()
                        ->whereDate('date', $performanceDate->toDateString())
                        ->whereTime('start_time', $performanceDate->toTimeString())
                        ->first();

                    $performanceId = $performance?->id;
                }
            }

            // Build records for each ticket holder
            $records = [];
            foreach ($validated['payload']['ticket_holders'] as $holder) {
                $rec = [
                    'show' => $validated['payload']['event_name'],
                    'sold_at' => Carbon::parse($validated['payload']['sold_at'])->setTimezone('America/Guayaquil')->format('Y-m-d H:i:s'),
                    'first_name' => $holder['first_name'],
                    'last_name' => $holder['last_name'],
                    'email' => $holder['email'],
                    'mobile_number' => $holder['mobile_number'],
                    'contact_preferences_user_response' => $holder['contact_preferences_user_response'],
                    'performance' => $performanceDateTime ? Carbon::parse($performanceDateTime)->format('Y-m-d H:i:s') : null,
                    'performance_id' => $performanceId ?? null,
                    'quantity' => $validated['payload']['quantity']
                ];

                $records[] = $rec;
            }

            // Insert into database and send emails
            $insertedCount = 0;
            $emailsSent = 0;
            $emailsFailed = 0;

            foreach ($records as $rec) {
                try {
                    TicketSale::create($rec);
                    $insertedCount++;

                    // Send notification email
                    try {
                        Mail::to(config('mail.to.address'))
                            ->send(new TicketSaleMailer($rec));
                        $emailsSent++;
                    } catch (Exception $e) {
                        $emailsFailed++;
                        logger()->error('Failed to send ticket sale email', [
                            'error' => $e->getMessage(),
                            'trace' => $e->getTraceAsString(),
                            'rec' => $rec
                        ]);
                    }
                } catch (Exception $e) {
                    logger()->error('Failed to insert ticket sale record', [
                        'error' => $e->getMessage(),
                        'trace' => $e->getTraceAsString(),
                        'rec' => $rec
                    ]);

                    return response()->json([
                        'status' => 'error',
                        'message' => 'Failed to insert ticket sale record'
                    ], 500);
                }
            }

            return response()->json([
                'status' => 'success',
                'records_inserted' => $insertedCount,
                'emails_sent' => $emailsSent
            ], 200);
        } catch (\Illuminate\Validation\ValidationException $e) {
            logger()->error('Webhook validation failed', [
                'errors' => $e->errors(),
                'payload' => $request->all()
            ]);

            return response()->json([
                'status' => 'error',
                'message' => 'Validation failed',
                'errors' => $e->errors()
            ], 422);
        } catch (Exception $e) {
            logger()->error('Webhook processing failed', [
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString(),
                'payload' => $request->all()
            ]);

            return response()->json([
                'status' => 'error',
                'message' => 'Internal server error'
            ], 500);
        }
    }
}
