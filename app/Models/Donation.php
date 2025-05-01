<?php
namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Donation extends Model
{
 /** @use HasFactory<\Database\Factories\DonationFactory> */
 use HasFactory;

 protected $fillable = [
  'patron_id',
  'amount',
  'donation_level',
  'date',
 ];

 public static function validate($data)
 {
  $validator = validator($data, [
   'patron_id'         => ['required', 'exists:patrons,id'],
   'amount'            => ['required', 'integer', 'min:1'],
   'donation_level_id' => ['required', 'exists:donation_levels,id'],
   'date'              => ['required', 'date'],
  ]);

  if ($validator->fails()) {
   return $validator->errors()->toArray();
  }

  return $validator->validated();
 }

 /**
  * Relation to patrons
  */
 public function patron()
 {
  return $this->belongsTo(Patron::class);
 }

 /**
  * Relation to donation_levels
  */
 public function donationLevel()
 {
  return $this->belongsTo(DonationLevel::class);
 }
}
