<?php
namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class DonationLevel extends Model
{
 /** @use HasFactory<\Database\Factories\DonationLevelFactory> */
 use HasFactory;

 protected $fillable = [
  'label',
  'value',
  'amount_min',
  'amount_max',
 ];

 /**
  * Relation to donations
  */
 public function donations()
 {
  return $this->hasMany(Donation::class);
 }

 /**
  * Relation to donation_perks
  */
 public function donationPerks()
 {
  return $this->hasMany(DonationPerk::class);
 }

 public static function validate($data)
 {
  $validator = validator($data, [
   'label'      => ['required', 'string', 'max:255'],
   'value'      => ['required', 'string', 'max:255', 'unique:donation_levels,value'],
   'amount_min' => ['required', 'integer', 'min:0'],
   'amount_max' => ['required', 'integer', 'gte:amount_min'],
  ]);

  if ($validator->fails()) {
   return $validator->errors()->toArray();
  }

  return $validator->validated();
 }

}
