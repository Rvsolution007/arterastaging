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
    ];

    public function user()
    {
        return $this->belongsTo(User::class);
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
