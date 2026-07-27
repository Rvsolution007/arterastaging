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
        'request_type',
        'prompt_tokens',
        'completion_tokens',
        'total_tokens',
        'image_count',
        'usage_source',
        'parameters',
        'source_reference',
        'cost_inr',
    ];

    protected $casts = [
        'parameters' => 'array',
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

    /**
     * Stores one completed Festival AI image generation in the same analytics
     * stream as text requests. OpenAI image responses can return usage, but
     * some image models do not; in that case only the input text is estimated
     * and the dashboard is explicitly told it is an estimate.
     */
    public static function logFestivalImageUsage(FestivalAiGeneration $generation, array $usageMetadata = []): self
    {
        $providerInputTokens = data_get($usageMetadata, 'input_tokens');
        $providerOutputTokens = data_get($usageMetadata, 'output_tokens');
        $providerTotalTokens = data_get($usageMetadata, 'total_tokens');
        $hasProviderUsage = is_numeric($providerInputTokens)
            || is_numeric($providerOutputTokens)
            || is_numeric($providerTotalTokens);

        $promptTokens = $hasProviderUsage
            ? (int) ($providerInputTokens ?? 0)
            : self::estimatePromptTokens((string) $generation->final_prompt);
        $completionTokens = $hasProviderUsage ? (int) ($providerOutputTokens ?? 0) : 0;
        $totalTokens = $hasProviderUsage
            ? (int) ($providerTotalTokens ?? ($promptTokens + $completionTokens))
            : $promptTokens;

        $model = $generation->imageModel;
        $pricing = (array) optional($model)->pricing_config;
        $inputRate = (float) ($pricing['input_per_million_usd'] ?? 0);
        $outputRate = (float) ($pricing['output_per_million_usd'] ?? 0);
        $imageRate = (float) ($pricing['image_per_unit_usd'] ?? 0);
        $exchangeRate = (float) ($pricing['usd_to_inr'] ?? 90);
        $usdCost = ($promptTokens / 1000000 * $inputRate)
            + ($completionTokens / 1000000 * $outputRate)
            + $imageRate;

        $sourceReference = self::festivalSourceReference($generation->id);
        $diagnostics = (array) $generation->request_diagnostics;
        $actualReferenceCount = (int) data_get(
            $diagnostics,
            'attached_reference_count',
            $generation->actual_reference_count ?? 0
        );
        $actualEndpoint = (string) data_get($diagnostics, 'endpoint', '');

        return self::firstOrCreate(['source_reference' => $sourceReference], [
            'user_id' => $generation->user_id,
            'feature_name' => 'Festival AI Image',
            'provider' => $generation->provider,
            'model' => $generation->provider_model_id,
            'request_type' => 'image_generation',
            'prompt_tokens' => $promptTokens,
            'completion_tokens' => $completionTokens,
            'total_tokens' => $totalTokens,
            'image_count' => 1,
            'usage_source' => $hasProviderUsage ? 'provider' : 'estimated_prompt_only',
            // Never store the prompt, product information, business snapshot,
            // API credentials, or raw provider response in analytics.
            'parameters' => [
                'quality' => $generation->quality,
                'size' => $generation->size_value,
                'size_key' => $generation->size_key,
                'reference_image_count' => $actualReferenceCount,
                'mode' => $actualEndpoint === '/v1/images/edits' || $actualReferenceCount > 0
                    ? 'edit_with_reference'
                    : 'generate',
            ],
            'source_reference' => $sourceReference,
            'cost_inr' => round($usdCost * $exchangeRate, 4),
        ]);
    }

    public static function festivalSourceReference(int $generationId): string
    {
        return 'festival-ai:' . $generationId;
    }

    private static function estimatePromptTokens(string $prompt): int
    {
        $characters = mb_strlen(trim($prompt));

        return max(1, (int) ceil($characters / 4));
    }
}
