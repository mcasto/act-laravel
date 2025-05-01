<?php
namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Performance extends Model
{
 /** @use HasFactory<\Database\Factories\PerformanceFactory> */
 use HasFactory;

 protected $fillable = [
  'show_id',
  'date',
  'sold_out_target',

 ];

 public static function validate($data)
 {
  $validator = validator($data, [
   'id'              => ['integer', 'min:1'],
   'show_id'         => ['required', 'exists:shows,id'],
   'date'            => ['required', 'date'],
   'sold_out_target' => ['required', 'integer', 'min:1'],

  ]);

  if ($validator->fails()) {
   return ['errors' => $validator->errors()->toArray()];
  }

  return $validator->validated();
 }

 /**
  * Relationship to shows
  */
 public function show()
 {
  return $this->belongsTo(Show::class);
 }

 /**
  * relationship to tickets
  */
 public function tickets()
 {
  return $this->hasMany(Ticket::class);
 }
}
