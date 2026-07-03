<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class AiReviewReply extends Model
{
    use HasFactory;

    protected $fillable = [
        'reviewer_name', 'rating', 'review_text', 'ai_reply_draft', 'status'
    ];
}
