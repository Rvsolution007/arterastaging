<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\User;
use App\Models\AIImageGeneration;
use App\Services\AIImageGenerationService;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;

class AIImageGenerationController extends Controller
{
    private AIImageGenerationService $aiImageService;

    public function __construct(AIImageGenerationService $aiImageService)
    {
        $this->aiImageService = $aiImageService;
    }

    public function generate(Request $request)
    {
        \Illuminate\Support\Facades\Log::info('AI Generate endpoint hit.', $request->only(['prompt', 'aspect_ratio', 'template_id']));
        
        $request->validate([
            'prompt' => 'required|string|max:1000',
            'aspect_ratio' => 'nullable|string', // e.g. 1:1, 9:16, 16:9, 3:4, 4:3
            'template_id' => 'nullable|integer',
            'slot_width' => 'nullable|numeric',
            'slot_height' => 'nullable|numeric',
            'reference_image' => 'nullable|string' // base64
        ]);

        $user = $request->user();

        // Check if user has AI Image Generation limits remaining
        if (!$user->canUseFeature('ai_image')) {
            return response()->json([
                'success' => false,
                'message' => 'You have reached your AI Image generation limit. Please upgrade your plan or watch an ad to get more.'
            ], 403);
        }

        try {
            $prompt = $request->input('prompt');
            $aspectRatio = $request->input('aspect_ratio', '1:1');
            $referenceImage = $request->input('reference_image');

            // Map frontend aspect ratios to Imagen 3 allowed list: 1:1, 3:4, 4:3, 9:16, 16:9
            // A simple normalizer if we calculate it from width/height instead of passing directly
            if (!$request->has('aspect_ratio') && $request->has('slot_width') && $request->has('slot_height')) {
                $w = $request->input('slot_width');
                $h = $request->input('slot_height');
                $ratio = $w / $h;
                if ($ratio > 1.7) $aspectRatio = '16:9';
                elseif ($ratio > 1.2) $aspectRatio = '4:3';
                elseif ($ratio < 0.6) $aspectRatio = '9:16';
                elseif ($ratio < 0.8) $aspectRatio = '3:4';
                else $aspectRatio = '1:1';
            }

            $base64Image = $this->aiImageService->generateImage($prompt, $aspectRatio, $referenceImage);

            // Save the generated image to public/uploads/ai_images
            $fileName = Str::uuid() . '.png';
            $imageBinary = base64_decode($base64Image);
            
            $destinationPath = base_path('uploads/ai_images');
            if (!file_exists($destinationPath)) {
                mkdir($destinationPath, 0755, true);
            }
            file_put_contents($destinationPath . '/' . $fileName, $imageBinary);
            $url = asset('uploads/ai_images/' . $fileName);

            // Consume the limit
            $user->consumeFeature('ai_image');

            // Log the generation
            AIImageGeneration::create([
                'user_id' => $user->id,
                'prompt' => $prompt,
                'reference_image_used' => $referenceImage ? 1 : 0,
                'slot_width' => $request->input('slot_width'),
                'slot_height' => $request->input('slot_height'),
                'generated_image_path' => $url,
                'template_id' => $request->input('template_id'),
            ]);

            return response()->json([
                'success' => true,
                'url' => $url
            ]);

        } catch (\Exception $e) {
            \Illuminate\Support\Facades\Log::error('AI Generation Exception: ' . $e->getMessage() . "\n" . $e->getTraceAsString());
            return response()->json([
                'success' => false,
                'message' => 'AI Generation failed: ' . $e->getMessage()
            ], 500);
        }
    }
}
