<?php
namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class UserPermission extends Model
{
 /** @use HasFactory<\Database\Factories\UserPermissionFactory> */
 use HasFactory;

 protected $fillable = [
  'user_id',
  'section',
  'access',
 ];

 /**
  * Relationship to users
  */
 public function user()
 {
  return $this->belongsTo(User::class);
 }
}
