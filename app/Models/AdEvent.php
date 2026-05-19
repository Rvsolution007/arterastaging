<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class AdEvent extends Model
{
    public $timestamps = false;

    protected $fillable = ['user_id', 'ad_type', 'event', 'created_at'];

    protected $casts = [
        'created_at' => 'datetime',
    ];

    public function user()
    {
        return $this->belongsTo(User::class);
    }
}
