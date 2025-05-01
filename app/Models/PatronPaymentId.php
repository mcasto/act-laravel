<?php
namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class PatronPaymentId extends Model
{
 /** @use HasFactory<\Database\Factories\PatronPaymentIdFactory> */
 use HasFactory;

 protected $fillable = [
  'patron_id',
  'payment_method_id',
  'external_id',
 ];

 /**
  * Relationship to patrons
  */
 public function patron()
 {
  return $this->belongsTo(Patron::class);
 }

 /**
  * Relationship to payment_methods
  */
 public function paymentMethod()
 {
  return $this->belongsTo(PaymentMethod::class);
 }
}
