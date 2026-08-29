<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class AdLiveAuthorizationCode extends Model
{
    public $incrementing = false;

    protected $keyType = 'string';

    protected $fillable = [
        'id',
        'code_hash',
        'client_id',
        'redirect_uri',
        'code_challenge',
        'artera_user_id',
        'artera_business_id',
        'payload',
        'expires_at',
        'used_at',
    ];

    protected $casts = [
        'payload' => 'array',
        'expires_at' => 'datetime',
        'used_at' => 'datetime',
    ];
}
