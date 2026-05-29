<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class AndroidLogin extends Model
{
    use HasFactory;

    protected $table = 'android_logins';
    protected $fillable = ['userId', 'fcmToken', 'deviceId'];
}
