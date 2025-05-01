<?php
namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class DonationPerk extends Model
{
 /** @use HasFactory<\Database\Factories\DonationPerkFactory> */
 use HasFactory;

 protected $fillable = [
  'donation_level_id',
  'perk',
 ];

 public static function validate($data)
 {
  $validator = validator($data, [
   'donation_level_id' => ['required', 'exists:donation_levels,id'],
   'perk'              => ['required', 'string', 'max:255'],
  ]);

  if ($validator->fails()) {
   return $validator->errors()->toArray();
  }

  return $validator->validated();
 }

 /**
  * Relation to donation_levels
  */
 public function donationLevel()
 {
  return $this->belongsTo(DonationLevel::class);
 }
}
