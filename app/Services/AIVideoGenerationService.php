<?php

namespace App\Services;

use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;
use App\Models\AiVideoGeneration;

class AIVideoGenerationService
{
    private string $runpodApiKey;
    private string $runpodEndpointId;
    private VertexAIService $vertexAI;

    public function __construct()
    {
        $this->runpodApiKey = config('services.runpod.api_key') ?: env('RUNPOD_API_KEY', '');
        $this->runpodEndpointId = config('services.runpod.wan_endpoint_id') ?: env('RUNPOD_WAN_ENDPOINT_ID', '');
        $this->vertexAI = new VertexAIService(auth()->id() ?? 1);
    }

    /**
     * Helper: Convert a relative file path to base64 string
     */
    private function fileToBase64(string $relativePath): ?string
    {
        $fullPath = public_path($relativePath);
        if (file_exists($fullPath)) {
            return base64_encode(file_get_contents($fullPath));
        }
        return null;
    }

    /**
     * Helper: Get MIME type from file path
     */
    private function getMimeType(string $relativePath): string
    {
        $ext = strtolower(pathinfo($relativePath, PATHINFO_EXTENSION));
        $map = ['jpg' => 'image/jpeg', 'jpeg' => 'image/jpeg', 'png' => 'image/png', 'gif' => 'image/gif', 'webp' => 'image/webp'];
        return $map[$ext] ?? 'image/jpeg';
    }

    /**
     * Start the video generation process (async)
     */
    public function generateVideo(AiVideoGeneration $videoJob): bool
    {
        if (empty($this->runpodApiKey) || empty($this->runpodEndpointId)) {
            $videoJob->update([
                'status' => 'failed',
                'error_message' => 'RunPod Serverless is not configured for Wan 2.2.'
            ]);
            return false;
        }

        try {
            // 1. Expand the prompt using VertexAI
            $videoJob->update(['status' => 'processing', 'progress' => 10]);
            
            $expandedPrompt = $this->expandPrompt($videoJob);
            $videoJob->update([
                'expanded_prompt' => $expandedPrompt,
                'progress' => 25
            ]);

            // 2. Submit to RunPod
            $payload = $this->buildRunPodPayload($expandedPrompt, $videoJob);
            $runsyncEndpoint = "https://api.runpod.ai/v2/{$this->runpodEndpointId}/run";

            Log::info('RunPod Wan 2.2 Request', ['endpoint' => $runsyncEndpoint]);

            $response = Http::withHeaders([
                'Authorization' => 'Bearer ' . $this->runpodApiKey,
                'Content-Type'  => 'application/json'
            ])->timeout(120)->post($runsyncEndpoint, $payload);

            if (!$response->successful()) {
                Log::error('RunPod API Error: ' . $response->body());
                throw new \Exception("Failed to submit job to RunPod: " . $response->body());
            }

            $data = $response->json();

            if (isset($data['id'])) {
                $videoJob->update([
                    'runpod_job_id' => $data['id'],
                    'progress' => 30
                ]);
                return true;
            } else {
                throw new \Exception("Invalid RunPod response: no job ID returned.");
            }

        } catch (\Exception $e) {
            Log::error("Video Generation Error: " . $e->getMessage());
            $videoJob->update([
                'status' => 'failed',
                'error_message' => $e->getMessage()
            ]);
            return false;
        }
    }

    /**
     * Expand simple prompt to a detailed cinematic prompt using AI
     */
    private function expandPrompt(AiVideoGeneration $videoJob): string
    {
        $simplePrompt = $videoJob->user_prompt;
        $style = $videoJob->style;
        $mode = $videoJob->mode;

        if (!$this->vertexAI->isConfigured()) {
            return $simplePrompt . ($style ? " in {$style} style." : "");
        }

        if ($mode === 'multi_image_composition' && !empty($videoJob->reference_images)) {
            $systemPrompt = "You are an expert AI Video Prompt Engineer for the Wan 2.2 video generation model. 
The user wants to generate a video combining elements from the provided reference images.
Carefully analyze the provided images (e.g., identify the specific person/face, identify the specific product/shoes/clothes).
The user's idea is: '{$simplePrompt}'.
Write a highly detailed, descriptive, and visually rich prompt for the video generation model.
Describe the camera movement, lighting, atmosphere, and environment.
MOST IMPORTANTLY: Describe the person's facial features and the product's details EXTREMELY specifically based on the images, so the video model can recreate them accurately.
" . ($style ? "Requested Style: {$style}." : "") . "
Output ONLY the expanded prompt text, without any introductory or concluding text.";

            $imagesForGemini = [];
            foreach ($videoJob->reference_images as $imgPath) {
                $base64Data = $this->fileToBase64($imgPath);
                if ($base64Data) {
                    $imagesForGemini[] = [
                        'mimeType' => $this->getMimeType($imgPath),
                        'data' => $base64Data
                    ];
                }
            }

            try {
                $response = $this->vertexAI->generateMultiVisionContentBase64($systemPrompt, $imagesForGemini);
                if (!empty($response['text'])) {
                    return $response['text'];
                }
            } catch (\Exception $e) {
                Log::error("Multi-Image Prompt Expansion Failed: " . $e->getMessage());
            }

        } else {
            $systemPrompt = "You are an expert AI Video Prompt Engineer for the Wan 2.2 video generation model. 
The user will provide a simple idea for a video. Your job is to expand it into a highly detailed, descriptive, and visually rich prompt.
Describe the camera movement, lighting, subject details, atmosphere, and environment.
If a specific style is provided, heavily emphasize that style.
Output ONLY the expanded prompt text, without any introductory or concluding text.";

            $userMessage = "Simple idea: {$simplePrompt}\nRequested Style: " . ($style ?? "Cinematic/Realistic");

            try {
                $response = $this->vertexAI->generateContent($systemPrompt, [
                    ['role' => 'user', 'text' => $userMessage]
                ]);

                if (!empty($response['text']) && !str_contains(strtolower($response['text']), 'sorry')) {
                    return $response['text'];
                }
            } catch (\Exception $e) {
                Log::error("Prompt Expansion Failed: " . $e->getMessage());
            }
        }

        // Fallback
        return $simplePrompt . ($style ? ", {$style} style, detailed, high quality" : "");
    }

    /**
     * Build the payload based on the requested mode
     */
    private function buildRunPodPayload(string $prompt, AiVideoGeneration $videoJob): array
    {
        $input = [
            "prompt" => $prompt
        ];

        // Add start image if applicable
        if (in_array($videoJob->mode, ['image_to_video', 'start_end_video', 'multi_image_composition']) && !empty($videoJob->start_image)) {
            $base64 = $this->fileToBase64($videoJob->start_image);
            if ($base64) {
                $input['image'] = $base64;
            }
        }

        // Add end image if applicable
        if ($videoJob->mode === 'start_end_video' && !empty($videoJob->end_image)) {
            $base64End = $this->fileToBase64($videoJob->end_image);
            if ($base64End) {
                $input['end_image'] = $base64End;
            }
        }

        return [
            "input" => $input
        ];
    }

    /**
     * Check status from RunPod
     */
    public function checkStatus(AiVideoGeneration $videoJob): array
    {
        if (empty($videoJob->runpod_job_id)) {
            return ['status' => 'failed', 'progress' => 0];
        }

        $statusEndpoint = "https://api.runpod.ai/v2/{$this->runpodEndpointId}/status/{$videoJob->runpod_job_id}";

        try {
            $response = Http::withHeaders([
                'Authorization' => 'Bearer ' . $this->runpodApiKey,
                'Content-Type'  => 'application/json'
            ])->timeout(30)->get($statusEndpoint);

            if ($response->successful()) {
                $data = $response->json();
                $status = $data['status'] ?? 'UNKNOWN';

                if ($status === 'COMPLETED') {
                    // Extract video
                    $videoUrlOrBase64 = $this->extractVideoFromRunPodData($data);
                    
                    if ($videoUrlOrBase64) {
                        $savedPath = $this->saveVideo($videoUrlOrBase64, $videoJob);
                        $videoJob->update([
                            'status' => 'completed',
                            'progress' => 100,
                            'video_path' => $savedPath,
                            'generation_time' => $data['executionTime'] ?? 0
                        ]);
                        return ['status' => 'completed', 'progress' => 100, 'video_url' => asset('uploads/ai_videos/' . $savedPath)];
                    } else {
                        $videoJob->update(['status' => 'failed', 'error_message' => 'No video data in response']);
                        return ['status' => 'failed', 'progress' => 0, 'error' => 'No video data in response'];
                    }
                } elseif ($status === 'FAILED') {
                    $error = $data['error'] ?? 'Unknown RunPod error';
                    $videoJob->update(['status' => 'failed', 'error_message' => $error]);
                    return ['status' => 'failed', 'progress' => 0, 'error' => $error];
                } elseif ($status === 'IN_PROGRESS') {
                    // Estimate progress based on typical time or return constant
                    $currentProgress = $videoJob->progress;
                    $newProgress = min(95, $currentProgress + rand(2, 5));
                    $videoJob->update(['progress' => $newProgress]);
                    return ['status' => 'processing', 'progress' => $newProgress];
                } elseif ($status === 'IN_QUEUE') {
                    return ['status' => 'processing', 'progress' => 30];
                }
            }
        } catch (\Exception $e) {
            Log::error("RunPod Check Status Error: " . $e->getMessage());
        }

        return ['status' => $videoJob->status, 'progress' => $videoJob->progress];
    }

    private function extractVideoFromRunPodData(array $data): ?string
    {
        // 1. Check direct output string (could be base64 or URL)
        if (isset($data['output']['video'])) {
            return $data['output']['video'];
        }
        
        if (isset($data['output']['message']) && is_string($data['output']['message'])) {
            return $data['output']['message'];
        }

        // 2. ComfyUI worker might return array
        if (isset($data['output']['videos']) && is_array($data['output']['videos'])) {
            $first = reset($data['output']['videos']);
            if (is_array($first) && isset($first['data'])) {
                return $first['data']; // base64
            }
            if (is_string($first)) {
                return $first;
            }
        }

        if (isset($data['output']) && is_string($data['output'])) {
            return $data['output'];
        }

        return null;
    }

    private function saveVideo(string $videoData, AiVideoGeneration $videoJob): string
    {
        $fileName = 'wan_video_' . $videoJob->id . '_' . time() . '.mp4';
        $uploadPath = public_path('uploads/ai_videos');
        
        if (!file_exists($uploadPath)) {
            mkdir($uploadPath, 0777, true);
        }

        $fullPath = $uploadPath . '/' . $fileName;

        if (str_starts_with($videoData, 'http')) {
            // Download from URL
            $videoContent = file_get_contents($videoData);
            file_put_contents($fullPath, $videoContent);
        } else {
            // Decode Base64
            $base64 = $videoData;
            if (str_contains($base64, ',')) {
                $parts = explode(',', $base64);
                $base64 = end($parts);
            }
            file_put_contents($fullPath, base64_decode($base64));
        }

        return $fileName;
    }
}
