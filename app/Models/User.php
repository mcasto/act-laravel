<?php
namespace App\Models;

// use Illuminate\Contracts\Auth\MustVerifyEmail;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Support\Str;
use Laravel\Sanctum\HasApiTokens;

class User extends Authenticatable
{
 /** @use HasFactory<\Database\Factories\UserFactory> */
 use HasFactory, HasApiTokens;

 /**
  * The attributes that are mass assignable.
  *
  * @var list<string>
  */
 protected $fillable = [
  'name',
  'email',
  'password',

 ];

 /**
  * The attributes that should be hidden for serialization.
  *
  * @var list<string>
  */
 protected $hidden = [
  'password',
  'remember_token',
 ];

 /**
  * Get the attributes that should be cast.
  *
  * @return array<string, string>
  */
 protected function casts(): array
 {
  return [
   'password' => 'hashed',
  ];
 }

 /**
  * Get the user's initials
  */
 public function initials(): string
 {
  return Str::of($this->name)
   ->explode(' ')
   ->map(fn(string $name) => Str::of($name)->substr(0, 1))
   ->implode('');
 }

 /**
  * Relationship to user_permissions
  */
 public function permissions()
 {
  return $this->hasMany(UserPermission::class);
 }

 /**
  * Validation
  */
 public static function validate($data)
 {
  $validator = validator($data, [
   'name'     => ['required', 'string', 'max:255'],
   'email'    => ['required', 'email', 'max:255', 'unique:users,email'],
   'password' => ['required', 'string', 'min:8'],

  ]);

  if ($validator->fails()) {
   return ['errors' => $validator->errors()->toArray()];
  }

  return $validator->validated();
 }

 /**
  * Bootstrap
  */
 protected static function boot()
 {
  parent::boot();

  static::creating(function ($model) {
   self::validate($model->attributes);
  });

  static::updating(function ($model) {
   self::validate($model->attributes);
  });
 }
}
