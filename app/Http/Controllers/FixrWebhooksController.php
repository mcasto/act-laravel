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
 * Utility to find patron id if necessary and it exists
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
  * Receives data from fixr and logs it into database
  * - Also send email to SiteConfig->ticketEmail if the event is a ticket sale
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
