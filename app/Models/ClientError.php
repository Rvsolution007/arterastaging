<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class ClientError extends Model
{
    use HasFactory;

    protected $fillable = [
        'user_id',
        'error_code',
        'error_message',
        'device_info',
        'status',
        'ai_severity',
        'ai_category',
        'ai_root_cause',
        'ai_suggested_fix',
        'ai_confidence',
        'ai_is_ux_bug',
        'ai_pattern_group',
        'ai_analyzed_at',
    ];

    protected $casts = [
        'ai_is_ux_bug' => 'boolean',
        'ai_confidence' => 'integer',
        'ai_analyzed_at' => 'datetime',
    ];

    public function user()
    {
        return $this->belongsTo(User::class);
    }

    /**
     * Check if this error has been analyzed by AI.
     */
    public function isAnalyzed(): bool
    {
        return !is_null($this->ai_analyzed_at);
    }

    /**
     * Get severity badge color for UI.
     */
    public function getSeverityColorAttribute(): string
    {
        return match ($this->ai_severity) {
            'critical' => '#dc3545',
            'high'     => '#fd7e14',
            'medium'   => '#ffc107',
            'low'      => '#28a745',
            'info'     => '#6c757d',
            default    => '#adb5bd',
        };
    }

    /**
     * Get severity background color (lighter) for cards.
     */
    public function getSeverityBgAttribute(): string
    {
        return match ($this->ai_severity) {
            'critical' => 'rgba(220,53,69,0.1)',
            'high'     => 'rgba(253,126,20,0.1)',
            'medium'   => 'rgba(255,193,7,0.1)',
            'low'      => 'rgba(40,167,69,0.1)',
            'info'     => 'rgba(108,117,125,0.1)',
            default    => 'rgba(173,181,189,0.1)',
        };
    }

    /**
     * Get severity icon.
     */
    public function getSeverityIconAttribute(): string
    {
        return match ($this->ai_severity) {
            'critical' => 'fa-skull-crossbones',
            'high'     => 'fa-exclamation-triangle',
            'medium'   => 'fa-exclamation-circle',
            'low'      => 'fa-info-circle',
            'info'     => 'fa-comment-dots',
            default    => 'fa-question-circle',
        };
    }

    /**
     * Scope: only UX/UI bugs.
     */
    public function scopeUxBugs($query)
    {
        return $query->where('ai_is_ux_bug', true);
    }

    /**
     * Scope: filter by severity.
     */
    public function scopeBySeverity($query, $level)
    {
        return $query->where('ai_severity', $level);
    }

    /**
     * Scope: only analyzed errors.
     */
    public function scopeAnalyzed($query)
    {
        return $query->whereNotNull('ai_analyzed_at');
    }

    /**
     * Scope: only unanalyzed errors.
     */
    public function scopeUnanalyzed($query)
    {
        return $query->whereNull('ai_analyzed_at');
    }

    public function getSimpleMessageAttribute()
    {
        $message = $this->error_message;
        
        if (!$message) return null;

        $lowerMessage = strtolower($message);

        if (str_contains($lowerMessage, 'invalid statuscode: 403') && str_contains($lowerMessage, 'uploads/http')) {
            return "Double URL Issue: The app is trying to load an image using a broken link that contains 'http' twice.";
        }
        
        if (str_contains($lowerMessage, 'invalid statuscode: 403') || str_contains($lowerMessage, 'status code: 403')) {
            return "Access Denied: The server blocked the app from loading this resource.";
        }
        
        if (str_contains($lowerMessage, 'invalid statuscode: 404') || str_contains($lowerMessage, 'status code: 404')) {
            return "Not Found: The app tried to load an image or data that does not exist on the server.";
        }
        
        if (str_contains($lowerMessage, 'socketexception') || str_contains($lowerMessage, 'connection refused') || str_contains($lowerMessage, 'network is unreachable')) {
            return "Network Error: The device could not connect to the server. This could be an internet issue or the server might be offline.";
        }

        if (str_contains($lowerMessage, 'typeerror') && str_contains($lowerMessage, 'null')) {
            return "Data Error: The app expected some data but received 'null' (nothing), causing it to crash or fail.";
        }
        
        if (str_contains($lowerMessage, 'out of memory')) {
            return "Memory Error: The device ran out of RAM while trying to process an image or data.";
        }

        if (str_contains($lowerMessage, 'formatexception') || str_contains($lowerMessage, 'json')) {
            return "Data Format Error: The app received data from the server in an unexpected format and couldn't read it.";
        }

        return "Unknown Error: A technical issue occurred while executing a process.";
    }
}
