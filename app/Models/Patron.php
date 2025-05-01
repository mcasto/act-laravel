<?php
namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Patron extends Model
{
 /** @use HasFactory<\Database\Factories\PatronFactory> */
 use HasFactory;

 protected $fillable = [
  'name',
  'mailchimp_member_id',
  'email',
  'phone',
 ];

 public static function validate($data)
 {
  $validator = validator($data, [
   'name'                => ['required', 'string', 'max:255'],
   'mailchimp_member_id' => ['required', 'exists:mailchimp_members,id'],
   'email'               => ['required', 'string', 'email', 'max:255'],
   'phone'               => ['required', 'string', 'max:20'], // Adjust max length as needed
  ]);

  if ($validator->fails()) {
   return $validator->errors()->toArray();
  }

  return $validator->validated();
 }

 /**
  * Relationship to donations
  */
 public function donations()
 {
  return $this->hasMany(Donation::class);
 }

 /**
  * Relationship to tickets
  */
 public function tickets()
 {
  return $this->hasMany(Ticket::class);
 }

 /**
  * Relationship to mailchimp_members
  */
 public function mailchimpMember()
 {
  return $this->belongsTo(MailchimpMember::class);
 }
}
