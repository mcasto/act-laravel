<?php
namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Audition extends Model
{
 /** @use HasFactory<\Database\Factories\AuditionFactory> */
 use HasFactory;

 public function show()
 {
  return $this->belongsTo(Show::class);
 }

 public function sessions()
 {
  return $this->hasMany(AuditionSession::class)->orderBy('session');
 }

 public function roles()
 {
  return $this->hasMany(AuditionRole::class)->orderBy('name');
 }
}
