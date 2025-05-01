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
  'permission_level_id',
  'access',
 ];

 public static function validate($data)
 {
  $validator = validator($data, [
   'user_id'             => ['required', 'exists:users,id'],
   'permission_level_id' => ['required', 'exists:permission_levels,id'],
   'access'              => ['required', 'string', 'max:255'],
  ]);

  if ($validator->fails()) {
   return $validator->errors()->toArray();
  }

  return $validator->validated();
 }

 /**
  * Relationship to users
  */
 public function user()
 {
  return $this->belongsTo(User::class);
 }

 /**
  * Relationship to permission_levels
  */
 public function permissionLevel()
 {
  return $this->belongsTo(PermissionLevel::class);
 }
}
