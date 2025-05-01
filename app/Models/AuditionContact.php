<?php
namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class AuditionContact extends Model
{
 protected $fillable = [
  'audition_id',
  'name',
  'role',
  'email',
  'phone',
  'body',
 ];
}
