<?php
namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class FixrWebhookResponse extends Model
{
 /** @use HasFactory<\Database\Factories\FixrWebhookResponseFactory> */
 use HasFactory;

 protected $fillable = [
  'patron_id',
  'event',
  'payload',
  'message_id',
 ];

 public static function validate($data)
 {
  $validator = validator($data, [
   'patron_id'  => ['required', 'exists:patrons,id'],
   'event'      => ['required', 'string', 'max:255'],
   'payload'    => ['required', 'json'],
   'message_id' => ['required', 'string', 'max:255'],
  ]);

  if ($validator->fails()) {
   return $validator->errors()->toArray();
  }

  return $validator->validated();
 }

 /**
  * Relationship to tickets
  */
 public function tickets()
 {
  return $this->hasMany(Ticket::class);
 }

 /**
  * Relationship to patrons
  */
 public function patron()
 {
  return $this->belongsTo(Patron::class);
 }
}
