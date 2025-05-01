<?php
namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class AuditionSession extends Model
{
 /** @use HasFactory<\Database\Factories\AuditionSessionFactory> */
 use HasFactory;

 public function audition()
 {
  return $this->belongsTo(Audition::class);
 }
}
