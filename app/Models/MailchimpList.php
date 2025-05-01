<?php
namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class MailchimpList extends Model
{
 /** @use HasFactory<\Database\Factories\MailchimpListFactory> */
 use HasFactory;

 protected $fillable = [
  'name',
  'mailchimp_id',
 ];

 /**
  * Relationship to mailchimp_members
  */
 public function mailchimpMembers()
 {
  return $this->hasMany(MailchimpMember::class);
 }

 public static function validate($data)
 {
  $validator = validator($data, [
   'name'         => ['required', 'string', 'max:255'],
   'mailchimp_id' => ['required', 'string', 'max:255', 'unique:mailchimp_lists,mailchimp_id'],
  ]);

  if ($validator->fails()) {
   return $validator->errors()->toArray();
  }

  return $validator->validated();
 }

}
