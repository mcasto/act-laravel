<?php
namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class PaymentMethod extends Model
{
 /** @use HasFactory<\Database\Factories\PaymentMethodFactory> */
 use HasFactory;

 protected $fillable = [
  'label',
  'value',
  'user_option',
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
   'user_option' => ['required', 'boolean'],
  ]);

  if ($validator->fails()) {
   return $validator->errors()->toArray();
  }

  return $validator->validated();
 }

}
