<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Ticket extends Model
{
    use HasFactory;

    protected $guarded = ['id'];

    protected $casts = [
        'resolved_at' => 'datetime',
        'sentiment_score' => 'float'
    ];

    public function user()
    {
        return $this->belongsTo(User::class);
    }

    public function messages()
    {
        return $this->hasMany(TicketMessage::class);
    }

    protected static function boot()
    {
        parent::boot();

        static::creating(function ($ticket) {
            $ticket->sentiment = self::analyzeSentiment($ticket->subject . ' ' . $ticket->description);
        });

        static::updating(function ($ticket) {
            if ($ticket->isDirty('description') || $ticket->isDirty('subject')) {
                $ticket->sentiment = self::analyzeSentiment($ticket->subject . ' ' . $ticket->description);
            }
        });
    }

    private static function analyzeSentiment($text)
    {
        $text = strtolower($text);
        
        $negativeWords = ['bad', 'terrible', 'worst', 'hate', 'broken', 'error', 'fail', 'crash', 'sucks', 'stupid', 'awful', 'not working', 'issue', 'problem', 'stuck', 'refund'];
        $positiveWords = ['good', 'great', 'excellent', 'love', 'amazing', 'best', 'awesome', 'thanks', 'thank you', 'perfect', 'beautiful'];

        $negativeCount = 0;
        $positiveCount = 0;

        foreach ($negativeWords as $word) {
            if (strpos($text, $word) !== false) $negativeCount++;
        }

        foreach ($positiveWords as $word) {
            if (strpos($text, $word) !== false) $positiveCount++;
        }

        if ($negativeCount > $positiveCount) return 'negative';
        if ($positiveCount > $negativeCount) return 'positive';
        return 'neutral';
    }
}
