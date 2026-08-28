<?php
namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class PaymentMethod extends Model
{
 /** @use HasFactory<\Database\Factories\PaymentMethodFactory> */
 use HasFactory, SoftDeletes;

 protected $fillable = [
  'label',
  'value',
  'color',
  'revenue_multiplier',
 ];

/**
 * Relation to patron_payments
 */
 public function patronPayments()
 {
  return $this->hasMany(PatronPaymentId::class);
 }

 public static function validate($data)
 {
  $validator = validator($data, [
   'label'       => ['required', 'string', 'max:255'],
   'value'       => ['required', 'string', 'max:255', 'unique:payment_methods,value'],
  ]);

  if ($validator->fails()) {
   return $validator->errors()->toArray();
  }

  return $validator->validated();
 }

}
