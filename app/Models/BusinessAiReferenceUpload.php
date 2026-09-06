<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

/** A private, user-owned device image that may be attached to Custom Post AI. */
class BusinessAiReferenceUpload extends Model
{
    use HasFactory;

    protected $fillable = [
        'user_id', 'public_id', 'original_name', 'mime_type', 'size', 'path', 'last_used_at',
    ];

    protected $casts = [
        'last_used_at' => 'datetime',
    ];

    public function user()
    {
        return $this->belongsTo(User::class);
    }
}
