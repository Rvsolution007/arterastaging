<?php

namespace App\Services;

use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Crypt;
use App\Models\AiSetting;

class AIImageGenerationService
{
    private string $runpodApiKey;
    private string $runpodEndpointId;

    public function __construct()
    {
        $this->runpodApiKey = config('services.runpod.api_key') ?: env('RUNPOD_API_KEY', '');
        $this->runpodEndpointId = config('services.runpod.flux_endpoint_id') ?: env('RUNPOD_FLUX_ENDPOINT_ID', '');
    }

    /**
     * Generates an image using FLUX.1 [dev] on RunPod Serverless.
     * Returns a base64 encoded image string or throws an Exception.
     */
    public function generateImage(string $prompt, string $aspectRatio = '1:1', ?string $referenceImageBase64 = null): string
    {
        if (empty($this->runpodApiKey) || empty($this->runpodEndpointId)) {
            throw new \Exception("RunPod Serverless is not configured. Please add RUNPOD_API_KEY and RUNPOD_FLUX_ENDPOINT_ID to .env");
        }

        // Clean reference image base64 if provided
        if ($referenceImageBase64 && str_contains($referenceImageBase64, ',')) {
            $parts = explode(',', $referenceImageBase64);
            $referenceImageBase64 = end($parts);
        }

        if ($referenceImageBase64) {
            return $this->generateWithFluxImageToImage($prompt, $referenceImageBase64);
        } else {
            return $this->generateWithFluxTextToImage($prompt, $aspectRatio);
        }
    }

    /**
     * Image Editing / Background Swap via FLUX.1 [dev] (Img2Img)
     */
    private function generateWithFluxImageToImage(string $prompt, string $imageBase64): string
    {
        $seed = rand(100000, 9999999);

        // 1. Enhance Prompt via Vertex AI
        $enhancedPrompt = $prompt;
        try {
            $vertex = new \App\Services\VertexAIService(auth()->id() ?? 1);
            if ($vertex->isConfigured()) {
                $systemPrompt = "You are an expert AI prompt engineer for Stable Diffusion XL. The user wants to generate an image based on their provided reference image and short prompt. Enhance their short prompt into a highly detailed, professional, comma-separated Stable Diffusion prompt. Keep the core product/subject from the reference image intact. Respond ONLY with the raw prompt text, no introductory text.";
                $response = $vertex->generateVisionContentFromBase64($systemPrompt, $imageBase64, 'image/png');
                if (!empty($response['text'])) {
                    $enhancedPrompt = $response['text'];
                    Log::info("Enhanced Prompt via Vertex AI: " . $enhancedPrompt);
                }
            }
        } catch (\Exception $e) {
            Log::error("Vertex AI Prompt Enhancement Failed: " . $e->getMessage());
        }

        // 2. ComfyUI Workflow for SDXL Img2Img (No Background Removal)
        $workflow = [
            "1" => [
                "inputs" => ["image" => "input_image.png"],
                "class_type" => "LoadImage"
            ],
            "3" => [
                "inputs" => ["ckpt_name" => "sd_xl_base_1.0.safetensors"],
                "class_type" => "CheckpointLoaderSimple"
            ],
            "4" => [
                "inputs" => ["pixels" => ["1", 0], "vae" => ["3", 2]],
                "class_type" => "VAEEncode"
            ],
            "5" => [
                "inputs" => ["text" => $enhancedPrompt, "clip" => ["3", 1]],
                "class_type" => "CLIPTextEncode"
            ],
            "6" => [
                "inputs" => ["text" => "text, watermark, ugly, low quality, bad anatomy, bad lighting, noisy", "clip" => ["3", 1]],
                "class_type" => "CLIPTextEncode"
            ],
            "7" => [
                "inputs" => [
                    "seed" => $seed,
                    "steps" => 30,
                    "cfg" => 7.0,
                    "sampler_name" => "dpmpp_2m",
                    "scheduler" => "karras",
                    "denoise" => 0.65, // 0.65 allows AI to generate background while matching product ~70%
                    "model" => ["3", 0],
                    "positive" => ["5", 0],
                    "negative" => ["6", 0],
                    "latent_image" => ["4", 0]
                ],
                "class_type" => "KSampler"
            ],
            "8" => [
                "inputs" => ["samples" => ["7", 0], "vae" => ["3", 2]],
                "class_type" => "VAEDecode"
            ],
            "10" => [
                "inputs" => ["filename_prefix" => "ComfyUI", "images" => ["8", 0]],
                "class_type" => "SaveImage"
            ]
        ];

        $payload = [
            "input" => [
                "workflow" => $workflow,
                "images" => [
                    [
                        "name" => "input_image.png",
                        "image" => $imageBase64
                    ]
                ]
            ]
        ];

        return $this->executeRunPodRequest($payload);
    }

    /**
     * Text-to-Image via FLUX.1 [dev]
     */
    private function generateWithFluxTextToImage(string $prompt, string $aspectRatio): string
    {
        // Dimensions based on aspect ratio
        $width = 1024;
        $height = 1024;
        if ($aspectRatio === '16:9') { $width = 1024; $height = 576; }
        if ($aspectRatio === '9:16') { $width = 576; $height = 1024; }
        if ($aspectRatio === '4:3') { $width = 1024; $height = 768; }
        if ($aspectRatio === '3:4') { $width = 768; $height = 1024; }

        $seed = rand(100000, 9999999);

        // Standard ComfyUI API workflow for FLUX.1 dev
        $workflow = [
            "5" => [
                "inputs" => ["width" => $width, "height" => $height, "batch_size" => 1],
                "class_type" => "EmptyLatentImage"
            ],
            "6" => [
                "inputs" => ["text" => $prompt, "clip" => ["11", 0]],
                "class_type" => "CLIPTextEncode"
            ],
            "8" => [
                "inputs" => ["samples" => ["13", 0], "vae" => ["10", 0]],
                "class_type" => "VAEDecode"
            ],
            "9" => [
                "inputs" => ["filename_prefix" => "ComfyUI", "images" => ["8", 0]],
                "class_type" => "SaveImage"
            ],
            "10" => [
                "inputs" => ["vae_name" => "ae.safetensors"],
                "class_type" => "VAELoader"
            ],
            "11" => [
                "inputs" => [
                    "clip_name1" => "t5xxl_fp8_e4m3fn.safetensors", 
                    "clip_name2" => "clip_l.safetensors", 
                    "type" => "flux"
                ],
                "class_type" => "DualCLIPLoader"
            ],
            "12" => [
                "inputs" => ["unet_name" => "flux1-dev.safetensors", "weight_dtype" => "default"],
                "class_type" => "UNETLoader"
            ],
            "13" => [
                "inputs" => [
                    "noise" => ["25", 0],
                    "guider" => ["22", 0],
                    "sampler" => ["16", 0],
                    "sigmas" => ["17", 0],
                    "latent_image" => ["5", 0]
                ],
                "class_type" => "SamplerCustomAdvanced"
            ],
            "16" => [
                "inputs" => ["sampler_name" => "euler"],
                "class_type" => "KSamplerSelect"
            ],
            "17" => [
                "inputs" => ["scheduler" => "simple", "steps" => 20, "denoise" => 1, "model" => ["12", 0]],
                "class_type" => "BasicScheduler"
            ],
            "22" => [
                "inputs" => ["model" => ["12", 0], "conditioning" => ["6", 0]],
                "class_type" => "BasicGuider"
            ],
            "25" => [
                "inputs" => ["noise_seed" => $seed],
                "class_type" => "RandomNoise"
            ]
        ];

        $payload = [
            "input" => [
                "workflow" => $workflow
            ]
        ];

        return $this->executeRunPodRequest($payload);
    }

    private function executeRunPodRequest(array $payload): string
    {
        $runsyncEndpoint = "https://api.runpod.ai/v2/{$this->runpodEndpointId}/runsync";

        Log::info('RunPod FLUX Request', ['endpoint' => $runsyncEndpoint]);

        $response = Http::withHeaders([
            'Authorization' => 'Bearer ' . $this->runpodApiKey,
            'Content-Type'  => 'application/json'
        ])->timeout(120)->post($runsyncEndpoint, $payload);

        if (!$response->successful()) {
            Log::error('RunPod API Error: ' . $response->body());
            throw new \Exception("Failed to generate image via RunPod: " . $response->body());
        }

        $data = $response->json();

        // If it returned immediately but is still in progress (happens for long generations like FLUX)
        if (isset($data['status']) && in_array($data['status'], ['IN_QUEUE', 'IN_PROGRESS'])) {
            $jobId = $data['id'];
            $data = $this->pollRunPodStatus($jobId);
        }

        return $this->extractImageFromRunPodData($data);
    }

    private function pollRunPodStatus(string $jobId): array
    {
        $statusEndpoint = "https://api.runpod.ai/v2/{$this->runpodEndpointId}/status/{$jobId}";
        $maxRetries = 60; // 60 * 5 = 300 seconds (5 minutes)
        
        for ($i = 0; $i < $maxRetries; $i++) {
            sleep(5); // Wait 5 seconds between checks

            $response = Http::withHeaders([
                'Authorization' => 'Bearer ' . $this->runpodApiKey,
                'Content-Type'  => 'application/json'
            ])->timeout(30)->get($statusEndpoint);

            if ($response->successful()) {
                $data = $response->json();
                $status = $data['status'] ?? 'UNKNOWN';

                if ($status === 'COMPLETED') {
                    return $data;
                }

                if ($status === 'FAILED') {
                    Log::error('RunPod Job Failed', ['data' => $data]);
                    throw new \Exception("RunPod AI generation failed: " . ($data['error'] ?? 'Unknown error'));
                }
            }
        }

        throw new \Exception("RunPod AI generation timed out.");
    }

    private function extractImageFromRunPodData(array $data): string
    {
        // RunPod returns results in the 'output' array. 
        if (isset($data['output']['message'])) {
            $result = $data['output']['message'];
            if (is_string($result) && !empty($result)) {
                return $result;
            }
        }

        // RunPod ComfyUI worker might return images array directly
        if (isset($data['output']['images']) && is_array($data['output']['images'])) {
            $first = reset($data['output']['images']);
            if (is_array($first) && isset($first['data'])) {
                return $first['data'];
            }
            if (is_string($first) && strlen($first) > 100) {
                return $first;
            }
        }
        
        if (isset($data['output']) && is_array($data['output'])) {
            $first = reset($data['output']);
            if (is_string($first) && strlen($first) > 100) {
                return $first;
            }
        }

        Log::error('RunPod: No image in response', ['response' => $data]);
        throw new \Exception("RunPod AI did not return a valid image base64.");
    }
}
