<?php
namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Ticket extends Model
{
 /** @use HasFactory<\Database\Factories\TicketFactory> */
 use HasFactory;

 protected $fillable = [
  'performance_id',
  'patron_id',
  'assigned_name',
  'qty',
  'payment_method_id',
  'order_date',
  'payment_date',
  'fixr_webhook_response_id',
 ];

 public static function validate($data)
 {
  $validator = validator($data, [
   'performance_id'           => ['required', 'exists:performances,id'],
   'patron_id'                => ['required', 'exists:patrons,id'],
   'assigned_name'            => ['required', 'string', 'max:255'],
   'qty'                      => ['required', 'integer', 'min:1'],
   'payment_method_id'        => ['required', 'exists:payment_methods,id'],
   'order_date'               => ['required', 'date'],
   'payment_date'             => ['nullable', 'date', 'after_or_equal:order_date'],
   'fixr_webhook_response_id' => ['required', 'exists:fixr_webhook_responses,id'],
  ]);

  if ($validator->fails()) {
   return $validator->errors()->toArray();
  }

  return $validator->validated();
 }

 /**
  * Relationship to performances
  */
 public function performance()
 {
  return $this->belongsTo(Performance::class);
 }

 /**
  * Relationship to patrons
  */
 public function patron()
 {
  return $this->belongsTo(Patron::class);
 }

 /**
  * Relationship to fixr_webhook_responses
  */
 public function fixrWebhookResponse()
 {
  return $this->belongsTo(FixrWebhookResponse::class);
 }
}
