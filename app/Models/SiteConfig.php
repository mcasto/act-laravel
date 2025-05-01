<?php
namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class SiteConfig extends Model
{
 /** @use HasFactory<\Database\Factories\SiteConfigFactory> */
 use HasFactory;

 protected $fillable = [
  'ticket_price',
  'ticket_email',
  'contact_email',
  'dev_email',
  'sold_out_target',
 ];

 public static function validate($data)
 {
  $validator = validator($data, [
   'ticket_price'    => ['required', 'integer', 'min:0'],
   'ticket_email'    => ['required', 'email'],
   'contact_email'   => ['required', 'email'],
   'dev_email'       => ['required', 'email'],
   'sold_out_target' => ['required', 'integer', 'min:0'],
  ]);

  if ($validator->fails()) {
   return $validator->errors()->toArray();
  }

  return $validator->validated();
 }

}
