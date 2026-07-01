<?php

namespace App\Services;

use App\Models\Setting;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Crypt;
use App\Models\AiTokenLog;
use Illuminate\Support\Facades\Route;

class VertexAIService
{
    private string $projectId;
    private string $location;
    private string $model;
    private array $serviceAccount;
    private string $apiKey;

    private string $provider;
    private int $userId;

    public function __construct(int $userId)
    {
        $this->userId = $userId;
        $this->projectId = \App\Models\AiSetting::getAiSetting('google_cloud_project_id') ?: '';
        $this->location = \App\Models\AiSetting::getAiSetting('vertex_location') ?: 'us-central1';
        $this->model = \App\Models\AiSetting::getAiSetting('ai_model') ?: \App\Models\AiSetting::getAiSetting('gemini_model') ?: 'gemini-2.0-flash';
        $this->provider = \App\Models\AiSetting::getAiSetting('ai_provider') ?: 'vertex';

        // Load Service Account: try encrypted DB format first, then legacy file-path
        $this->serviceAccount = $this->loadServiceAccount();
        
        $this->apiKey = \App\Models\AiSetting::getAiSetting('gemini_api_key') ?: \App\Models\AiSetting::getAiSetting('ai_api_key') ?: '';
    }

    /**
     * Load Service Account credentials from encrypted DB or legacy file path.
     */
    private function loadServiceAccount(): array
    {
        // Try new encrypted format first (AES-256-CBC via Laravel Crypt)
        $encrypted = \App\Models\AiSetting::getAiSetting('google_application_credentials_encrypted');
        if ($encrypted) {
            try {
                $json = Crypt::decryptString($encrypted);
                $sa = json_decode($json, true);
                if ($sa && isset($sa['client_email']) && isset($sa['private_key'])) {
                    return $sa;
                }
            } catch (\Exception $e) {
                Log::error('VertexAI: Failed to decrypt Service Account credentials: ' . $e->getMessage());
            }
        }

        // Fallback: legacy file-path format (backward compatibility)
        $saPath = \App\Models\AiSetting::getAiSetting('google_application_credentials');
        if ($saPath && file_exists(base_path($saPath))) {
            $sa = json_decode(file_get_contents(base_path($saPath)), true);
            if ($sa && is_array($sa)) {
                return $sa;
            }
        }

        return [];
    }

    public function generateContent(string $systemPrompt, array $messages, ?string $imageUrl = null): array
    {
        if (!$this->isConfigured()) {
            Log::error('VertexAI: Service not configured');
            return ['text' => 'AI bot is not configured. Please set up Vertex AI in Settings.', 'prompt_tokens' => 0, 'completion_tokens' => 0, 'total_tokens' => 0];
        }

        try {
            $endpoint = $this->getEndpoint();
            $requestBody = $this->buildRequestBody($systemPrompt, $messages, $imageUrl);

            $headers = ['Content-Type' => 'application/json'];
            if (!$this->isUsingGeminiStudio()) {
                $headers['Authorization'] = "Bearer " . $this->getAccessToken();
            }

            $maxRetries = 2;
            $retryCount = 0;
            
            while ($retryCount <= $maxRetries) {
                // Check if provider switched to gemini during token fetch
                $endpoint = $this->getEndpoint();
                if ($this->isUsingGeminiStudio() && isset($headers['Authorization'])) {
                    unset($headers['Authorization']); // Remove token if switched to Gemini
                }
                $response = Http::withHeaders($headers)->timeout(120)->post($endpoint, $requestBody);

                if ($response->status() === 429 && $retryCount < $maxRetries) {
                    $responseBody = $response->body();
                    if (str_contains(strtolower($responseBody), 'quota exceeded') || str_contains($responseBody, 'limit: 0')) {
                        $fallbackModel = $this->isUsingGeminiStudio() ? 'gemini-1.5-flash' : 'gemini-1.5-flash-002';
                        Log::warning("VertexAI: Hard Quota Limit detected for {$this->model}. Automatically switching to {$fallbackModel}!");
                        $this->model = $fallbackModel;
                        $retryCount++;
                        continue; // instantly retry with new model
                    }
                    
                    $retryCount++;
                    Log::warning("VertexAI: Rate limit hit (HTTP 429). Retrying in 15 seconds... (Attempt $retryCount of $maxRetries)");
                    sleep(15);
                    continue;
                }
                
                break;
            }

            if (!$response->successful()) {
                Log::error('VertexAI: API error', ['status' => $response->status(), 'body' => $response->body()]);
                return ['text' => 'Sorry, I am unable to process your request right now.', 'prompt_tokens' => 0, 'completion_tokens' => 0, 'total_tokens' => 0];
            }

            $data = $response->json();
            $text = $data['candidates'][0]['content']['parts'][0]['text'] ?? '';
            $usage = $data['usageMetadata'] ?? [];

            // Log Token Usage
            AiTokenLog::logUsage($this->userId, $this->detectFeature(), $this->provider, $this->model, $usage);

            return [
                'text' => trim($text) ?: 'Sorry, I could not generate a response.',
                'prompt_tokens' => $usage['promptTokenCount'] ?? 0,
                'completion_tokens' => $usage['candidatesTokenCount'] ?? 0,
                'total_tokens' => ($usage['promptTokenCount'] ?? 0) + ($usage['candidatesTokenCount'] ?? 0),
            ];

        } catch (\Exception $e) {
            Log::error('VertexAI: Exception - ' . $e->getMessage());
            return ['text' => 'Sorry, an error occurred. Please try again later.', 'prompt_tokens' => 0, 'completion_tokens' => 0, 'total_tokens' => 0];
        }
    }

    public function generateVisionContentFromBase64(string $systemPrompt, string $base64Image, string $mimeType, bool $forceJson = false): array
    {
        if (!$this->isConfigured()) {
            throw new \Exception('AI bot is not configured. Please set up Vertex AI or Gemini in Settings.');
        }

        $endpoint = $this->getEndpoint($forceJson);
        
        $body = [
            "contents" => [
                [
                    "role" => "user",
                    "parts" => [
                        ["text" => $systemPrompt],
                        [
                            "inlineData" => [
                                "mimeType" => $mimeType,
                                "data" => $base64Image
                            ]
                        ]
                    ]
                ]
            ],
            "generationConfig" => [
                "temperature" => 0.1
            ]
        ];

        if ($forceJson) {
            $body["generationConfig"]["responseMimeType"] = "application/json";
        }

        $headers = ['Content-Type' => 'application/json'];
        if (!$this->isUsingGeminiStudio()) {
            $headers['Authorization'] = "Bearer " . $this->getAccessToken();
        }

        $response = Http::withHeaders($headers)->timeout(300)->post($endpoint, $body);

        if (!$response->successful()) {
            $err = $response->json();
            $msg = $err['error']['message'] ?? $response->body();
            if ($response->status() === 429 || stripos($msg, 'Quota exceeded') !== false) {
                throw new \Exception('Google AI Quota Exceeded: You have reached the request limit. Please wait or update billing plan.');
            }
            throw new \Exception('AI Vision Error: ' . $msg);
        }

        $data = $response->json();
        $text = $data['candidates'][0]['content']['parts'][0]['text'] ?? '';
        $usage = $data['usageMetadata'] ?? [];

        // Log Token Usage
        AiTokenLog::logUsage($this->userId, $this->detectFeature(), $this->provider, $this->model, $usage);

        return [
            'text' => trim($text),
            'raw' => $data
        ];
    }

    public function generateMultiVisionContentBase64(string $systemPrompt, array $images, bool $forceJson = false): array
    {
        if (!$this->isConfigured()) {
            throw new \Exception('AI bot is not configured. Please set up Vertex AI or Gemini in Settings.');
        }

        $endpoint = $this->getEndpoint($forceJson);
        
        $parts = [
            ["text" => $systemPrompt]
        ];

        foreach ($images as $img) {
            $parts[] = [
                "inlineData" => [
                    "mimeType" => $img['mimeType'] ?? 'image/jpeg',
                    "data" => $img['data']
                ]
            ];
        }
        
        $body = [
            "contents" => [
                [
                    "role" => "user",
                    "parts" => $parts
                ]
            ],
            "generationConfig" => [
                "temperature" => 0.1
            ]
        ];

        if ($forceJson) {
            $body["generationConfig"]["responseMimeType"] = "application/json";
        }

        $headers = ['Content-Type' => 'application/json'];
        if (!$this->isUsingGeminiStudio()) {
            $headers['Authorization'] = "Bearer " . $this->getAccessToken();
        }

        $response = Http::withHeaders($headers)->timeout(120)->post($endpoint, $body);

        if (!$response->successful()) {
            $err = $response->json();
            $msg = $err['error']['message'] ?? $response->body();
            if ($response->status() === 429 || stripos($msg, 'Quota exceeded') !== false) {
                throw new \Exception('Google AI Quota Exceeded: You have reached the request limit.');
            }
            throw new \Exception('AI Vision Error: ' . $msg);
        }

        $data = $response->json();
        $text = $data['candidates'][0]['content']['parts'][0]['text'] ?? '';
        $usage = $data['usageMetadata'] ?? [];

        // Log Token Usage
        AiTokenLog::logUsage($this->userId, $this->detectFeature(), $this->provider, $this->model, $usage);

        return [
            'text' => trim($text),
            'raw' => $data
        ];
    }

    public function classifyContent(string $prompt): array
    {
        if (!$this->isConfigured()) {
            return ['text' => 'NONE', 'prompt_tokens' => 0, 'completion_tokens' => 0, 'total_tokens' => 0];
        }

        try {
            $endpoint = $this->getEndpoint();

            $body = [
                'contents' => [['role' => 'user', 'parts' => [['text' => $prompt]]]],
                'generationConfig' => ['temperature' => 0.1, 'topP' => 0.8, 'maxOutputTokens' => 50],
            ];

            $headers = ['Content-Type' => 'application/json'];
            if (!$this->isUsingGeminiStudio()) {
                $headers['Authorization'] = "Bearer {$accessToken}";
            }

            $maxRetries = 2;
            $retryCount = 0;
            
            while ($retryCount <= $maxRetries) {
                $endpoint = $this->getEndpoint();
                $response = Http::withHeaders($headers)->timeout(60)->post($endpoint, $body);

                if ($response->status() === 429 && $retryCount < $maxRetries) {
                    $responseBody = $response->body();
                    if (str_contains(strtolower($responseBody), 'quota exceeded') || str_contains($responseBody, 'limit: 0')) {
                        $fallbackModel = $this->isUsingGeminiStudio() ? 'gemini-1.5-flash' : 'gemini-1.5-flash-002';
                        Log::warning("VertexAI: Hard Quota Limit detected for {$this->model}. Automatically switching to {$fallbackModel}!");
                        $this->model = $fallbackModel;
                        $retryCount++;
                        continue;
                    }
                    $retryCount++;
                    sleep(15);
                    continue;
                }
                break;
            }

            if (!$response->successful()) {
                return ['text' => 'NONE', 'prompt_tokens' => 0, 'completion_tokens' => 0, 'total_tokens' => 0];
            }

            $data = $response->json();
            $text = $data['candidates'][0]['content']['parts'][0]['text'] ?? 'NONE';
            $usage = $data['usageMetadata'] ?? [];

            // Log Token Usage
            AiTokenLog::logUsage($this->userId, $this->detectFeature(), $this->provider, $this->model, $usage);

            return [
                'text' => trim($text),
                'prompt_tokens' => $usage['promptTokenCount'] ?? 0,
                'completion_tokens' => $usage['candidatesTokenCount'] ?? 0,
                'total_tokens' => ($usage['promptTokenCount'] ?? 0) + ($usage['candidatesTokenCount'] ?? 0),
            ];
        } catch (\Exception $e) {
            return ['text' => 'NONE', 'prompt_tokens' => 0, 'completion_tokens' => 0, 'total_tokens' => 0];
        }
    }

    private function isUsingGeminiStudio(): bool
    {
        // Force Gemini if explicitly selected OR if Vertex is selected but service account is missing and API key exists
        return $this->provider === 'gemini' || ($this->provider === 'vertex' && empty($this->serviceAccount) && !empty($this->apiKey));
    }

    private function getEndpoint(bool $needsBeta = false): string
    {
        if ($this->isUsingGeminiStudio()) {
            $cleanModel = preg_replace('/^models\//', '', $this->model);
            $apiVersion = 'v1beta'; // Always use v1beta for Gemini to support systemInstruction
            return "https://generativelanguage.googleapis.com/{$apiVersion}/models/" . urlencode($cleanModel) . ":generateContent?key=" . $this->apiKey;
        }

        return sprintf(
            'https://%s-aiplatform.googleapis.com/v1/projects/%s/locations/%s/publishers/google/models/%s:generateContent',
            $this->location, $this->projectId, $this->location, $this->model
        );
    }

    private function buildRequestBody(string $systemPrompt, array $messages, ?string $imageUrl): array
    {
        $contents = [];
        foreach ($messages as $msg) {
            $role = $msg['role'] === 'user' ? 'user' : 'model';
            $parts = [];
            if (!empty($msg['text'])) $parts[] = ['text' => $msg['text']];
            if ($imageUrl && $role === 'user' && $msg === end($messages)) {
                $parts[] = ['fileData' => ['mimeType' => $this->guessImageMimeType($imageUrl), 'fileUri' => $imageUrl]];
            }
            if (!empty($parts)) $contents[] = ['role' => $role, 'parts' => $parts];
        }

        $body = [
            'contents' => $contents,
            'generationConfig' => ['temperature' => 0.7, 'topP' => 0.95, 'maxOutputTokens' => 8192],
        ];

        if (!empty($systemPrompt)) {
            $body['systemInstruction'] = ['parts' => [['text' => $systemPrompt]]];
        }
        return $body;
    }

    private function getAccessToken(): string
    {
        $cacheKey = 'vertex_ai_token_' . md5($this->projectId);
        try {
            return Cache::remember($cacheKey, 3300, function () {
                return $this->generateAccessToken();
            });
        } catch (\Exception $e) {
            Log::warning("VertexAI: Access token failed, falling back to Gemini Studio. Error: " . $e->getMessage());
            $this->provider = 'gemini';
            return '';
        }
    }

    private function generateAccessToken(): string
    {
        $now = time();
        $header = base64url_encode(json_encode(['alg' => 'RS256', 'typ' => 'JWT']));

        $claimSet = [
            'iss' => $this->serviceAccount['client_email'],
            'scope' => 'https://www.googleapis.com/auth/cloud-platform',
            'aud' => 'https://oauth2.googleapis.com/token',
            'iat' => $now,
            'exp' => $now + 3600,
        ];

        $payload = base64url_encode(json_encode($claimSet));
        $signingInput = "{$header}.{$payload}";

        $privateKeyString = $this->serviceAccount['private_key'] ?? '';
        $privateKeyString = str_replace(['\\\\n', '\\\\r'], ["\n", ""], $privateKeyString);
        $privateKeyString = str_replace("\r", "", $privateKeyString);
        $privateKeyString = trim($privateKeyString);

        $privateKey = openssl_pkey_get_private($privateKeyString);
        if (!$privateKey) {
            Log::error('VertexAI: Private key parse failed', [
                'key_length' => strlen($privateKeyString),
                'starts_with' => substr($privateKeyString, 0, 40),
                'has_begin' => str_contains($privateKeyString, '-----BEGIN'),
                'openssl_error' => openssl_error_string(),
            ]);
            throw new \RuntimeException('VertexAI: Invalid private key in service account');
        }

        openssl_sign($signingInput, $signature, $privateKey, OPENSSL_ALGO_SHA256);
        $jwt = $signingInput . '.' . base64url_encode($signature);

        $response = Http::asForm()->post('https://oauth2.googleapis.com/token', [
            'grant_type' => 'urn:ietf:params:oauth:grant-type:jwt-bearer',
            'assertion' => $jwt,
        ]);

        if (!$response->successful()) {
            throw new \RuntimeException('VertexAI: Failed to get access token - ' . $response->body());
        }

        return $response->json('access_token');
    }

    private function guessImageMimeType(string $url): string
    {
        $ext = strtolower(pathinfo(parse_url($url, PHP_URL_PATH) ?? '', PATHINFO_EXTENSION));
        return match ($ext) { 'png' => 'image/png', 'webp' => 'image/webp', 'gif' => 'image/gif', default => 'image/jpeg' };
    }

    public function isConfigured(): bool
    {
        if ($this->isUsingGeminiStudio()) {
            return !empty($this->apiKey);
        }
        return !empty($this->projectId) && !empty($this->serviceAccount);
    }

    public function generateContentWithPDF(string $systemPrompt, string $pdfPath, string $userMessage, int $maxOutputTokens = 8192): array
    {
        if (!$this->isConfigured()) {
            return ['text' => 'AI bot is not configured.', 'prompt_tokens' => 0, 'completion_tokens' => 0, 'total_tokens' => 0];
        }

        $originalMemory = ini_get('memory_limit');
        ini_set('memory_limit', '1G');

        try {
            $endpoint = $this->getEndpoint();

            $fileSize = filesize($pdfPath);
            $fileSizeMB = round($fileSize / 1024 / 1024, 2);
            Log::info('VertexAI PDF: Processing file', ['size_mb' => $fileSizeMB]);

            $pdfToSend = $pdfPath;
            if ($fileSize > 40 * 1024 * 1024) {
                foreach ([15, 10, 5] as $pageLimit) {
                    $reduced = $this->reducePDFPages($pdfPath, $pageLimit);
                    if ($reduced !== $pdfPath && file_exists($reduced) && filesize($reduced) < $fileSize) {
                        $pdfToSend = $reduced;
                        if (filesize($reduced) <= 50 * 1024 * 1024) break;
                        @unlink($reduced);
                        $pdfToSend = $pdfPath;
                    }
                }
            }

            if (filesize($pdfToSend) > 50 * 1024 * 1024) {
                throw new \RuntimeException("PDF exceeds the 50MB AI limit. Please compress your PDF.");
            }

            $pdfContent = file_get_contents($pdfToSend);
            if ($pdfContent === false) throw new \RuntimeException('Could not read PDF file.');
            $pdfBase64 = base64_encode($pdfContent);
            unset($pdfContent);

            if ($pdfToSend !== $pdfPath && file_exists($pdfToSend)) @unlink($pdfToSend);

            $body = [
                'contents' => [[
                    'role' => 'user',
                    'parts' => [
                        ['inlineData' => ['mimeType' => 'application/pdf', 'data' => $pdfBase64]],
                        ['text' => $userMessage],
                    ],
                ]],
                'generationConfig' => ['temperature' => 0.2, 'topP' => 0.8, 'maxOutputTokens' => $maxOutputTokens],
            ];
            unset($pdfBase64);

            if (!empty($systemPrompt)) {
                $body['systemInstruction'] = ['parts' => [['text' => $systemPrompt]]];
            }

            $headers = [
                'Content-Type: application/json',
                'Expect:',
            ];
            
            if (!$this->isUsingGeminiStudio()) {
                $accessToken = $this->getAccessToken();
                $headers[] = 'Authorization: Bearer ' . $accessToken;
            }

            $maxRetries = 4;
            $retryCount = 0;
            $httpCode = 0;
            
            while ($retryCount <= $maxRetries) {
                $endpoint = $this->getEndpoint(); // Determine inside loop
                $ch = curl_init($endpoint);
                $jsonBody = json_encode($body);

                curl_setopt_array($ch, [
                    CURLOPT_RETURNTRANSFER => true,
                    CURLOPT_POST => true,
                    CURLOPT_POSTFIELDS => $jsonBody,
                    CURLOPT_HTTPHEADER => $headers,
                    CURLOPT_TIMEOUT => 600,
                    CURLOPT_CONNECTTIMEOUT => 60,
                ]);

                $responseBody = curl_exec($ch);
                $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
                $curlError = curl_error($ch);
                curl_close($ch);

                if ($httpCode === 429 && $retryCount < $maxRetries) {
                    if (str_contains(strtolower($responseBody), 'quota exceeded') || str_contains($responseBody, 'limit: 0')) {
                        $fallbackModel = $this->isUsingGeminiStudio() ? 'gemini-1.5-flash' : 'gemini-1.5-flash-002';
                        Log::warning("VertexAI PDF: Hard Quota Limit detected for {$this->model}. Automatically switching to {$fallbackModel}!");
                        $this->model = $fallbackModel;
                        $retryCount++;
                        continue; // silently swap and instantly retry
                    }

                    $retryCount++;
                    $sleepTime = $retryCount * 20; // 20s, 40s, 60s, 80s
                    Log::warning("VertexAI PDF: Rate limit hit (HTTP 429). Retrying in {$sleepTime} seconds... (Attempt $retryCount of $maxRetries)");
                    sleep($sleepTime);
                    continue;
                }
                
                break;
            }
            unset($body);

            if ($curlError) throw new \RuntimeException('VertexAI PDF: cURL error - ' . $curlError);
            if ($httpCode < 200 || $httpCode >= 300) {
                try {
                    $errorData = json_decode($responseBody, true);
                    $errMsg = $errorData['error']['message'] ?? $responseBody;
                } catch (\Exception $e) {
                    $errMsg = $responseBody;
                }
                throw new \RuntimeException("VertexAI PDF: API error (HTTP {$httpCode}) - {$errMsg}");
            }

            $data = json_decode($responseBody, true);
            unset($responseBody);
            if (!$data) throw new \RuntimeException('VertexAI PDF: Invalid JSON response');

            $text = $data['candidates'][0]['content']['parts'][0]['text'] ?? '';
            $usage = $data['usageMetadata'] ?? [];

            // Log Token Usage
            AiTokenLog::logUsage($this->userId, $this->detectFeature(), $this->provider, $this->model, $usage);

            return [
                'text' => trim($text) ?: 'No response from AI.',
                'prompt_tokens' => $usage['promptTokenCount'] ?? 0,
                'completion_tokens' => $usage['candidatesTokenCount'] ?? 0,
                'total_tokens' => ($usage['promptTokenCount'] ?? 0) + ($usage['candidatesTokenCount'] ?? 0),
            ];

        } catch (\Exception $e) {
            Log::error('VertexAI PDF: Exception', ['error' => $e->getMessage()]);
            throw $e;
        } finally {
            ini_set('memory_limit', $originalMemory);
        }
    }

    private function reducePDFPages(string $pdfPath, int $maxPages = 10): string
    {
        $tempDir = dirname($pdfPath);
        $reducedPath = $tempDir . DIRECTORY_SEPARATOR . 'reduced_' . basename($pdfPath);

        $gsPath = $this->findCommand('gs');
        if ($gsPath) {
            $cmd = sprintf('%s -dNOPAUSE -dBATCH -dSAFER -sDEVICE=pdfwrite -dFirstPage=1 -dLastPage=%d -sOutputFile=%s %s 2>&1',
                escapeshellarg($gsPath), $maxPages, escapeshellarg($reducedPath), escapeshellarg($pdfPath));
            exec($cmd, $output, $returnCode);
            if ($returnCode === 0 && file_exists($reducedPath) && filesize($reducedPath) > 0) return $reducedPath;
        }

        $pdftkPath = $this->findCommand('pdftk');
        if ($pdftkPath) {
            exec(sprintf('%s %s cat 1-%d output %s 2>&1', escapeshellarg($pdftkPath), escapeshellarg($pdfPath), $maxPages, escapeshellarg($reducedPath)), $output, $returnCode);
            if ($returnCode === 0 && file_exists($reducedPath) && filesize($reducedPath) > 0) return $reducedPath;
        }

        $qpdfPath = $this->findCommand('qpdf');
        if ($qpdfPath) {
            exec(sprintf('%s %s --pages . 1-%d -- %s 2>&1', escapeshellarg($qpdfPath), escapeshellarg($pdfPath), $maxPages, escapeshellarg($reducedPath)), $output, $returnCode);
            if ($returnCode === 0 && file_exists($reducedPath) && filesize($reducedPath) > 0) return $reducedPath;
        }

        if (class_exists(\setasign\Fpdi\Fpdi::class)) {
            try {
                $fpdi = new \setasign\Fpdi\Fpdi();
                $pageCount = $fpdi->setSourceFile($pdfPath);
                $lastPage = min($maxPages, $pageCount);
                for ($i = 1; $i <= $lastPage; $i++) {
                    $templateId = $fpdi->importPage($i);
                    $size = $fpdi->getTemplateSize($templateId);
                    $fpdi->AddPage($size['orientation'], [$size['width'], $size['height']]);
                    $fpdi->useTemplate($templateId);
                }
                $fpdi->Output('F', $reducedPath);
                if (file_exists($reducedPath) && filesize($reducedPath) > 0) return $reducedPath;
            } catch (\Exception $e) {}
        }

        return $pdfPath;
    }

    private function findCommand(string $command): ?string
    {
        // Check for locally bundled qpdf first
        if ($command === 'qpdf') {
            $localQpdf = base_path('bin_qpdf/qpdf-11.9.0-mingw64/bin/qpdf.exe');
            if (file_exists($localQpdf)) return $localQpdf;
            
            $linuxQpdf = base_path('bin_qpdf/qpdf');
            if (file_exists($linuxQpdf)) return $linuxQpdf;
        }

        $cmd = PHP_OS_FAMILY === 'Windows' ? "where {$command}" : "which {$command}";
        exec($cmd . ' 2>nul', $output, $returnCode);
        return ($returnCode === 0 && !empty($output[0])) ? trim($output[0]) : null;
    }

    public function extractPDFPageRange(string $pdfPath, int $firstPage, int $lastPage): ?string
    {
        $tempDir = dirname($pdfPath);
        $chunkPath = $tempDir . DIRECTORY_SEPARATOR . "chunk_{$firstPage}_{$lastPage}_" . basename($pdfPath);

        $gsPath = $this->findCommand('gs');
        if ($gsPath) {
            exec(sprintf('%s -dNOPAUSE -dBATCH -dSAFER -sDEVICE=pdfwrite -dFirstPage=%d -dLastPage=%d -sOutputFile=%s %s 2>&1',
                escapeshellarg($gsPath), $firstPage, $lastPage, escapeshellarg($chunkPath), escapeshellarg($pdfPath)), $output, $returnCode);
            if ($returnCode === 0 && file_exists($chunkPath) && filesize($chunkPath) > 0) return $chunkPath;
        }

        $pdftkPath = $this->findCommand('pdftk');
        if ($pdftkPath) {
            exec(sprintf('%s %s cat %d-%d output %s 2>&1', escapeshellarg($pdftkPath), escapeshellarg($pdfPath), $firstPage, $lastPage, escapeshellarg($chunkPath)), $output, $returnCode);
            if ($returnCode === 0 && file_exists($chunkPath) && filesize($chunkPath) > 0) return $chunkPath;
        }

        $qpdfPath = $this->findCommand('qpdf');
        if ($qpdfPath) {
            exec(sprintf('%s %s --pages . %d-%d -- %s 2>&1', escapeshellarg($qpdfPath), escapeshellarg($pdfPath), $firstPage, $lastPage, escapeshellarg($chunkPath)), $output, $returnCode);
            if ($returnCode === 0 && file_exists($chunkPath) && filesize($chunkPath) > 0) return $chunkPath;
        }

        if (class_exists(\setasign\Fpdi\Fpdi::class)) {
            try {
                $fpdi = new \setasign\Fpdi\Fpdi();
                $pageCount = $fpdi->setSourceFile($pdfPath);
                $actualLast = min($lastPage, $pageCount);
                for ($i = $firstPage; $i <= $actualLast; $i++) {
                    $templateId = $fpdi->importPage($i);
                    $size = $fpdi->getTemplateSize($templateId);
                    $fpdi->AddPage($size['orientation'], [$size['width'], $size['height']]);
                    $fpdi->useTemplate($templateId);
                }
                $fpdi->Output('F', $chunkPath);
                if (file_exists($chunkPath) && filesize($chunkPath) > 0) return $chunkPath;
            } catch (\Exception $e) {}
        }

        return null;
    }

    public function extractSampledPDFPages(string $pdfPath, int $totalTargetPages = 10): ?string
    {
        $pageCount = $this->getPDFPageCount($pdfPath);
        
        $tempDir = dirname($pdfPath);
        $chunkPath = $tempDir . DIRECTORY_SEPARATOR . "sampled_{$totalTargetPages}_" . basename($pdfPath);

        $pages = [];
        
        // Always try to take consecutive pages starting from Page 2 to avoid the cover page.
        // If a PDF is 30 pages, taking pages 2, 3, 4, 5, 6, 7, 8, 9, 10, 11 provides a much denser 
        // and continuous block of product data for schema analysis than taking every 3rd page.
        $startPage = 2;
        if ($pageCount == 1) {
            $startPage = 1;
        }

        $endPage = min($startPage + $totalTargetPages - 1, $pageCount);

        for ($i = $startPage; $i <= $endPage; $i++) {
            $pages[] = $i;
        }
        
        $pages = array_unique($pages);
        sort($pages);

        $pdftkPath = $this->findCommand('pdftk');
        if ($pdftkPath) {
            $pagesArg = implode(' ', $pages);
            exec(sprintf('%s %s cat %s output %s 2>&1', escapeshellarg($pdftkPath), escapeshellarg($pdfPath), $pagesArg, escapeshellarg($chunkPath)), $output, $returnCode);
            if ($returnCode === 0 && file_exists($chunkPath) && filesize($chunkPath) > 0) return $chunkPath;
        }

        $qpdfPath = $this->findCommand('qpdf');
        if ($qpdfPath) {
            $pagesArg = implode(',', $pages);
            exec(sprintf('%s %s --pages . %s -- %s 2>&1', escapeshellarg($qpdfPath), escapeshellarg($pdfPath), escapeshellarg($pagesArg), escapeshellarg($chunkPath)), $output, $returnCode);
            if ($returnCode === 0 && file_exists($chunkPath) && filesize($chunkPath) > 0) return $chunkPath;
        }

        if (class_exists(\setasign\Fpdi\Fpdi::class)) {
            try {
                $fpdi = new \setasign\Fpdi\Fpdi();
                $actualPageCount = $fpdi->setSourceFile($pdfPath);
                
                $added = 0;
                foreach ($pages as $p) {
                    if ($p <= $actualPageCount) {
                        $templateId = $fpdi->importPage($p);
                        $size = $fpdi->getTemplateSize($templateId);
                        $fpdi->AddPage($size['orientation'], [$size['width'], $size['height']]);
                        $fpdi->useTemplate($templateId);
                        $added++;
                    }
                }
                if ($added > 0) {
                    $fpdi->Output('F', $chunkPath);
                    if (file_exists($chunkPath) && filesize($chunkPath) > 0) return $chunkPath;
                }
            } catch (\Exception $e) {}
        }

        return null;
    }

    public function getPDFPageCount(string $pdfPath): int
    {
        $pdfinfoPath = $this->findCommand('pdfinfo');
        if ($pdfinfoPath) {
            exec(escapeshellarg($pdfinfoPath) . ' ' . escapeshellarg($pdfPath) . ' 2>/dev/null', $output, $returnCode);
            if ($returnCode === 0) {
                foreach ($output as $line) {
                    if (preg_match('/^Pages:\s+(\d+)/i', $line, $m)) return (int)$m[1];
                }
            }
        }

        $content = file_get_contents($pdfPath);
        if ($content !== false) {
            $count = preg_match_all('/\/Type\s*\/Page[^s]/i', $content);
            if ($count > 0) return $count;
        }

        return max(1, (int)ceil(filesize($pdfPath) / 1024 / 1024 * 10));
    }

    /**
     * Map current route name to a feature name string
     */
    private function detectFeature(): string
    {
        $routeName = Route::currentRouteName() ?? '';

        if (str_starts_with($routeName, 'setup.')) {
            return 'AI Setup Wizard';
        }



        return 'General Chat/Classification API';
    }
}

if (!function_exists('App\Services\base64url_encode')) {
    function base64url_encode(string $data): string
    {
        return rtrim(strtr(base64_encode($data), '+/', '-_'), '=');
    }
}
