<?php

namespace App\Http\Controllers;

use App\Helpers\RefId;
use App\Models\Patron;
use App\Models\PaymentMethod;
use App\Models\TicketSale;
use Carbon\Carbon;
use Exception;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Http;
use App\Mail\PurchaseConfirmationMailer;
use App\Mail\TicketSaleMailer;
use Illuminate\Support\Facades\Mail;
use App\Models\Show;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;

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
        $filename = "logs/fixr_webhook_" . date('Y_m_d_H_i_s') . ".log";
        Storage::put($filename, print_r($request->all(), true));

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

            $creditCardMethod = PaymentMethod::where('value', 'credit_card')->first();

            // Build records for each ticket holder
            $records = [];
            foreach ($validated['payload']['ticket_holders'] as $holder) {
                $patron = Patron::firstOrCreate(
                    ['email' => $holder['email']],
                    [
                        'first_name' => $holder['first_name'],
                        'last_name' => $holder['last_name'],
                        'phone' => $holder['mobile_number'],
                    ]
                );

                $rec = [
                    'patron_id' => $patron->id,
                    'payment_method_id' => $creditCardMethod?->id,
                    'sold_at' => Carbon::parse($validated['payload']['sold_at'])->setTimezone('America/Guayaquil')->format('Y-m-d H:i:s'),
                    'performance_id' => $performanceId ?? null,
                    'quantity' => $validated['payload']['quantity']
                ];

                $records[] = ['rec' => $rec, 'patron' => $patron];
            }

            // Insert into database and send emails
            $insertedCount = 0;
            $emailsSent = 0;
            $emailsFailed = 0;

            foreach ($records as $entry) {
                $rec = $entry['rec'];
                $patron = $entry['patron'];

                try {
                    $ticketSale = TicketSale::create($rec);
                    $ticketSale->transaction_id = RefId::ref_id($rec['id']);
                    $ticketSale->save();

                    $insertedCount++;

                    // Send notification email
                    try {
                        $ticketData = [
                            'show'           => $show?->name ?? $validated['payload']['event_name'],
                            'performance'    => $performanceDateTime ? Carbon::parse($performanceDateTime)->format('Y-m-d H:i:s') : null,
                            'first_name'     => $patron->first_name,
                            'last_name'      => $patron->last_name,
                            'email'          => $patron->email,
                            'mobile_number'  => $patron->phone,
                            'payment_method' => $creditCardMethod?->label,
                            'quantity'       => $rec['quantity'],
                            'sold_at'        => $rec['sold_at'],
                        ];

                        Mail::to(config('mail.admin_to.address'))
                            ->send(new TicketSaleMailer($ticketData));

                        $performanceDateParsed = $performanceDateTime ? Carbon::parse($performanceDateTime) : null;

                        Mail::to($patron->email)->send(new PurchaseConfirmationMailer([
                            'view'             => 'purchase-confirmation',
                            'name'             => $patron->first_name . ' ' . $patron->last_name,
                            'show_name'        => $show?->name ?? $validated['payload']['event_name'],
                            'num_tickets'      => $rec['quantity'],
                            'performance_date' => $performanceDateParsed?->format('F j, Y'),
                            'performance_time' => $performanceDateParsed?->format('g:i A'),
                        ]));

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
