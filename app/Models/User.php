<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;
use Laravel\Sanctum\HasApiTokens;

class User extends Authenticatable
{
  use HasApiTokens, HasFactory, Notifiable;

  protected $fillable = [
    'name',
    'nickname',
    'email',
    'password',
    'google_id',
    'avatar',
    'email_verified_at',
    'fcm_token'
  ];

  protected $hidden = [
    'password',
    'remember_token',
    'fcm_token'
  ];

  protected $casts = [
    'email_verified_at' => 'datetime',
    'nickname' => 'string',
    'google_id' => 'string',
    'avatar' => 'string',
    'fcm_token' => 'string',
  ];


//   use Notifiable;

//   public function routeNotificationForFcm()
//   {
//       // デバイストークンを返します
//       return $this->fcm_token;
//   }
}
