<?php

namespace App\Console\Commands;

use App\Models\AiSetting;
use Illuminate\Console\Command;
use Illuminate\Http\Client\ConnectionException;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Schema;

class CheckFestivalAiStaging extends Command
{
    protected $signature = 'festival-ai:staging-check';

    protected $description = 'Safely verify Festival AI staging prerequisites without printing credentials';

    public function handle(): int
    {
        $requiredTables = [
            'ai_setting',
            'ai_image_models',
            'festival_ai_configs',
            'festival_ai_generations',
            'festival_ai_style_presets',
            'festival_ai_brand_chrome_presets',
            'jobs',
        ];

        $missingTables = [];
        try {
            foreach ($requiredTables as $table) {
                if (!Schema::hasTable($table)) {
                    $missingTables[] = $table;
                }
            }
        } catch (\Throwable $exception) {
            $missingTables = $requiredTables;
        }

        $apiKeyConfigured = false;
        try {
            $apiKeyConfigured = trim((string) AiSetting::getAiSetting('chatgpt_api_key')) !== '';
        } catch (\Throwable $exception) {
            // Treat unreadable credentials as unconfigured. Never output their value.
        }

        $providerReachable = false;
        $providerStatus = null;
        try {
            // This deliberately sends no Authorization header. A 401/403 proves that
            // staging can reach OpenAI without exposing or consuming the API key.
            $response = Http::acceptJson()->timeout(12)->get('https://api.openai.com/v1/models');
            $providerStatus = $response->status();
            $providerReachable = $providerStatus > 0;
        } catch (ConnectionException $exception) {
            // Connectivity failure is recorded below with no internal exception details.
        }

        $queueIsDatabase = config('queue.connections.festival-ai.driver') === 'database';
        $ready = empty($missingTables)
            && $apiKeyConfigured
            && $providerReachable
            && $queueIsDatabase;

        $result = [
            'ready' => $ready,
            'database_ready' => empty($missingTables),
            'missing_tables' => $missingTables,
            'api_key_configured' => $apiKeyConfigured,
            'provider_reachable' => $providerReachable,
            'provider_status' => $providerStatus,
            'festival_ai_queue_driver' => config('queue.connections.festival-ai.driver'),
        ];

        Log::info('Festival AI staging readiness check completed.', $result);

        $this->table(['Check', 'Result'], [
            ['Database migrations', empty($missingTables) ? 'ready' : 'missing: ' . implode(', ', $missingTables)],
            ['Artera AI key', $apiKeyConfigured ? 'configured (value hidden)' : 'not configured'],
            ['OpenAI HTTPS', $providerReachable ? 'reachable (HTTP ' . $providerStatus . ')' : 'not reachable'],
            ['Festival AI queue', $queueIsDatabase ? 'database driver ready' : 'invalid driver'],
            ['Diagnostic log', storage_path('logs/laravel.log')],
        ]);

        if (!$ready) {
            $this->error('Festival AI staging is not ready. See the safe diagnostic log entry above.');

            return self::FAILURE;
        }

        $this->info('Festival AI staging is ready. API credentials were not displayed or logged.');

        return self::SUCCESS;
    }
}
