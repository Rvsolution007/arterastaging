<?php

namespace App\Services;

use App\Models\ClientError;
use App\Models\AiSetting;
use Illuminate\Support\Facades\Log;

class ErrorAnalysisService
{
    /**
     * Analyze a single client error using AI via the existing VertexAIService.
     * Returns true on success, false on failure.
     */
    public static function analyze(int $errorId): bool
    {
        $error = ClientError::find($errorId);
        if (!$error) return false;

        try {
            $prompt = self::buildPrompt($error);
            
            // Use the existing VertexAIService which handles Vertex/Gemini/ChatGPT auth
            $aiService = new \App\Services\VertexAIService($error->user_id ?? 0);
            
            if (!$aiService->isConfigured()) {
                Log::error("ErrorAnalysis: AI is not configured. Check AI settings.");
                return false;
            }

            $systemPrompt = "You are a senior QA engineer and mobile app developer with 20 years of experience. Always respond with valid JSON only. No markdown, no explanations outside JSON.";
            $messages = [
                ['role' => 'user', 'text' => $prompt]
            ];

            $response = $aiService->generateContent($systemPrompt, $messages);
            $text = $response['text'] ?? '';

            if (empty($text) || str_contains($text, 'unable to process')) {
                Log::warning("ErrorAnalysis: AI returned empty/error for error #{$errorId}");
                return false;
            }

            $result = self::parseJsonResponse($text);

            if (!$result) {
                Log::warning("ErrorAnalysis: Could not parse AI response for error #{$errorId}", ['text' => substr($text, 0, 300)]);
                return false;
            }

            $error->update([
                'ai_severity'      => $result['severity'] ?? 'medium',
                'ai_category'      => $result['category'] ?? 'Unknown',
                'ai_root_cause'    => $result['root_cause'] ?? null,
                'ai_suggested_fix' => $result['suggested_fix'] ?? null,
                'ai_confidence'    => min(100, max(0, (int)($result['confidence'] ?? 50))),
                'ai_is_ux_bug'     => filter_var($result['is_ux_bug'] ?? false, FILTER_VALIDATE_BOOLEAN),
                'ai_pattern_group' => $result['pattern_group'] ?? null,
                'ai_analyzed_at'   => now(),
            ]);

            return true;
        } catch (\Exception $e) {
            Log::error("ErrorAnalysis: Failed for error #{$errorId}: " . $e->getMessage());
            return false;
        }
    }

    /**
     * Batch analyze unanalyzed errors. Returns count of successfully analyzed.
     */
    public static function batchAnalyze(int $limit = 50): int
    {
        $errors = ClientError::whereNull('ai_analyzed_at')
            ->orderBy('created_at', 'desc')
            ->limit($limit)
            ->pluck('id');

        $success = 0;
        foreach ($errors as $id) {
            if (self::analyze($id)) {
                $success++;
            }
            // Small delay to avoid rate limiting
            usleep(800000); // 0.8 second
        }

        return $success;
    }

    /**
     * Build the AI prompt for analyzing an error.
     */
    private static function buildPrompt(ClientError $error): string
    {
        $errorCode = $error->error_code ?? 'UNKNOWN';
        $errorMessage = $error->error_message ?? 'No message';
        $deviceInfo = $error->device_info ?? 'Unknown device';
        $reportedAt = $error->created_at ? $error->created_at->format('Y-m-d H:i') : 'Unknown';

        return <<<PROMPT
Analyze this client-side error report from a mobile app (a design/poster maker Flutter app). Think like a seasoned tester who has seen thousands of bugs.

Error Code: {$errorCode}
Error Message: {$errorMessage}
Device Info: {$deviceInfo}
Reported At: {$reportedAt}

Provide your analysis as a strict JSON object (no markdown, no explanation outside JSON):

{
  "severity": "critical|high|medium|low|info",
  "category": "UI Bug|UX Flow Issue|Network Error|Data Error|App Crash|Performance|Security|Configuration|Unknown",
  "is_ux_bug": true or false,
  "root_cause": "Simple 2-3 sentence explanation of what went wrong, understandable by a non-technical person",
  "suggested_fix": "Clear actionable steps for the developer to fix this issue",
  "confidence": 0-100,
  "pattern_group": "short_snake_case_key to group similar errors e.g. image_load_403, null_pointer_crash, network_timeout"
}

Rules:
- severity "critical" = app crashes or major feature broken for many users
- severity "high" = feature partially broken or data loss risk
- severity "medium" = visual glitch or minor flow issue
- severity "low" = cosmetic issue or edge case
- severity "info" = expected behavior or informational
- is_ux_bug = true ONLY if it directly affects what the user sees or interacts with (button not working, screen not loading, element invisible, scroll not working, text overlap, touch not responding)
- Keep root_cause in simple Hinglish-friendly language
- pattern_group should be consistent: same root cause = same group key
PROMPT;
    }

    /**
     * Parse AI response text into a validated JSON array.
     */
    private static function parseJsonResponse(string $text): ?array
    {
        // Remove markdown code fences if present
        $text = preg_replace('/^```(?:json)?\s*/i', '', $text);
        $text = preg_replace('/\s*```$/i', '', $text);
        $text = trim($text);

        $result = json_decode($text, true);

        if (!$result || !is_array($result)) {
            // Try to extract JSON from within text
            if (preg_match('/\{[^{}]*"severity"[^{}]*\}/s', $text, $matches)) {
                $result = json_decode($matches[0], true);
            }
            
            if (!$result || !is_array($result)) {
                return null;
            }
        }

        // Validate severity
        $validSeverities = ['critical', 'high', 'medium', 'low', 'info'];
        if (isset($result['severity'])) {
            $result['severity'] = strtolower(trim($result['severity']));
            if (!in_array($result['severity'], $validSeverities)) {
                $result['severity'] = 'medium';
            }
        }

        // Validate confidence range
        if (isset($result['confidence'])) {
            $result['confidence'] = min(100, max(0, (int)$result['confidence']));
        }

        // Clean pattern_group
        if (isset($result['pattern_group'])) {
            $result['pattern_group'] = strtolower(preg_replace('/[^a-zA-Z0-9_]/', '_', $result['pattern_group']));
            $result['pattern_group'] = preg_replace('/_+/', '_', trim($result['pattern_group'], '_'));
        }

        return $result;
    }

    /**
     * Check if AI error analysis is enabled in settings.
     */
    public static function isAutoAnalyzeEnabled(): bool
    {
        $setting = AiSetting::getAiSetting('error_auto_analyze');
        return $setting === '1' || $setting === 'true' || $setting === true;
    }
}
