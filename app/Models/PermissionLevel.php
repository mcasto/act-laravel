<?php
namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class PermissionLevel extends Model
{
 /** @use HasFactory<\Database\Factories\PermissionLevelFactory> */
 use HasFactory, SoftDeletes;

 protected $fillable = [
  'label',
  'value',
 ];

 /**
  * Relationship to user_permissions
  */
 public function userPermissions()
 {
  return $this->hasMany(UserPermission::class);
 }

 public static function validate($data)
 {
  $validator = validator($data, [
   'label' => ['required', 'string', 'max:255'],
   'value' => ['required', 'string', 'max:255'],
  ]);

  if ($validator->fails()) {
   return $validator->errors()->toArray();
  }

  return $validator->validated();
 }

}
