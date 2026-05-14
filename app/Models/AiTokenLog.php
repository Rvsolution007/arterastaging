<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class AiTokenLog extends Model
{
    use HasFactory;

    protected $table = 'ai_token_logs';

    protected $fillable = [
        'user_id',
        'feature_name',
        'provider',
        'model',
        'prompt_tokens',
        'completion_tokens',
        'total_tokens',
        'cost_inr',
    ];

    public function user()
    {
        return $this->belongsTo(User::class);
    }

    /**
     * Helper to log token usage and calculate cost.
     * Cost formula: 
     *  - $0.075 per 1M prompt tokens
     *  - $0.30 per 1M completion tokens
     *  - Exchange rate: 1 USD = 90 INR
     */
    public static function logUsage($userId, $featureName, $provider, $model, $usageMetadata)
    {
        if (empty($usageMetadata)) {
            return null; // Silent skip if no usage data returned by API
        }

        $promptTokens = $usageMetadata['promptTokenCount'] ?? 0;
        $completionTokens = $usageMetadata['candidatesTokenCount'] ?? 0;
        $totalTokens = $usageMetadata['totalTokenCount'] ?? ($promptTokens + $completionTokens);

        // Calculate Cost in USD (Gemini Flash baseline)
        // 1 Million tokens
        $usdCost = ($promptTokens / 1000000 * 0.075) + ($completionTokens / 1000000 * 0.30);
        
        // Convert to INR based on approved rate $1 = 90 INR
        $inrCost = round($usdCost * 90, 4);

        return self::create([
            'user_id' => $userId,
            'feature_name' => $featureName,
            'provider' => $provider,
            'model' => $model,
            'prompt_tokens' => $promptTokens,
            'completion_tokens' => $completionTokens,
            'total_tokens' => $totalTokens,
            'cost_inr' => $inrCost,
        ]);
    }
}
