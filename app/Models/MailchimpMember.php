<?php
namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class MailchimpMember extends Model
{
 /** @use HasFactory<\Database\Factories\MailchimpMemberFactory> */
 use HasFactory;

 protected $fillable = [
  'mailchimp_list_id',
  'email',
  'status',
 ];

 public static function validate($data)
 {
  $validator = validator($data, [
   'mailchimp_list_id' => ['required', 'exists:mailchimp_lists,id'],
   'email'             => ['required', 'email', 'unique:mailchimp_members,email'],
   'status'            => ['required', 'string', 'in:subscribed,unsubscribed,pending'],
  ]);

  if ($validator->fails()) {
   return $validator->errors()->toArray(); // Return validation errors
  }

  return $validator->validated();
 }

 /**
  * Relationship to mailchimp_lists
  */
 public function mailchimpList()
 {
  return $this->belongsTo(MailchimpList::class);
 }

 /**
  * Relationship to patrons
  */
 public function patrons()
 {
  return $this->hasMany(Patron::class);
 }
}
