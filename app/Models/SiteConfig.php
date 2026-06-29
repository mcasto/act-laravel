<?php
namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Support\Facades\Cache;

class SiteConfig extends Model
{
 /** @use HasFactory<\Database\Factories\SiteConfigFactory> */
 use HasFactory, SoftDeletes;

 protected static function booted(): void
 {
  static::saved(fn() => Cache::forget('site-config'));
  static::deleted(fn() => Cache::forget('site-config'));
 }

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
