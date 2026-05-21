<?php

namespace App\Services;

use Illuminate\Http\Request;
use App\Models\User;
use Illuminate\Support\Facades\Log;

class FraudDetectionService
{
    /**
     * Check if the signup is potentially fraudulent based on IP, email, etc.
     * Returns true if fraud is detected, false otherwise.
     */
    public static function isFraudulentSignup(Request $request, $email)
    {
        $ip = $request->ip();

        // 1. Check if multiple accounts registered from same IP in last 24 hours
        $recentSignupsFromIp = User::where('created_at', '>=', now()->subHours(24))
                                ->get()
                                ->filter(function ($user) use ($ip) {
                                    // In a real scenario we might store signup_ip in DB.
                                    // For now, if we had it, we would check here.
                                    // Let's assume we implement a basic check on email domain first.
                                    return false; 
                                })->count();

        // 2. Check for disposable email domains
        $disposableDomains = [
            'tempmail.com', '10minutemail.com', 'guerrillamail.com', 'mailinator.com', 
            'yopmail.com', 'temp-mail.org', 'throwawaymail.com', 'example.com'
        ];
        
        $emailParts = explode('@', $email);
        $domain = strtolower(end($emailParts));

        if (in_array($domain, $disposableDomains)) {
            Log::warning("Fraud Detection: Disposable email used ($email)");
            return true;
        }

        // 3. Very short or random name check
        $name = $request->get('name');
        if (strlen($name) < 3 || preg_match('/[0-9]{4,}/', $name)) {
            Log::warning("Fraud Detection: Suspicious name ($name)");
            return true;
        }

        return false;
    }
}
