<?php

namespace App\Http\Controllers\Api;

use App\Models\CustomPostFrame;
use App\Models\MagicClonerSetting;
use Illuminate\Http\Request;
use App\Models\AiSetting;
use App\Http\Controllers\Controller;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

class AiMagicClonerController extends Controller
{
    /**
     * Phase 7: AI Magic Cloner - Template Fingerprinting & Weighted Scoring Engine
     * Uses Google Vertex AI (gemini-2.0-flash) to analyze an inspiration image
     * and matches it against fingerprinted templates using weighted structural scoring.
     */
    public function cloneVibe(Request $request)
    {
        $user = auth()->guard('api')->user();
        if (!$user) {
            return response()->json(['success' => false, 'message' => 'Unauthorized'], 401);
        }

        if (!$request->hasFile('inspiration_image')) {
            return response()->json(['success' => false, 'message' => 'Please upload an inspiration image.'], 400);
        }

        $image = $request->file('inspiration_image');
        $base64Image = base64_encode(file_get_contents($image->getPathname()));
        $mimeType = $image->getClientMimeType();

        // Build the structural analysis prompt
        $prompt = $this->buildAnalysisPrompt();

        // Connect to Vertex AI / Gemini API
        $apiKey = AiSetting::getAiSetting('ai_api_key') ?: AiSetting::getAiSetting('gemini_api_key');
        if (!$apiKey) {
            return response()->json(['success' => false, 'message' => 'AI System configuration is missing.'], 500);
        }

        $model = AiSetting::getAiSetting('ai_model') ?: 'gemini-2.0-flash';
        $cleanModel = preg_replace('/^models\//', '', $model);
        $endpoint = "https://generativelanguage.googleapis.com/v1/models/{$cleanModel}:generateContent?key={$apiKey}";

        $payload = [
            "contents" => [
                [
                    "parts" => [
                        ["text" => $prompt],
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
                "temperature" => 0.1,
                "responseMimeType" => "application/json"
            ]
        ];

        try {
            $response = Http::post($endpoint, $payload);

            if (!$response->successful()) {
                Log::error("MagicCloner Vertex API Error: " . $response->body());
                return response()->json(['success' => false, 'message' => 'AI Vision analysis failed.'], 500);
            }

            $result = $response->json();
            $aiText = $result['candidates'][0]['content']['parts'][0]['text'] ?? '';

            // Clean up potential markdown blocks
            $aiText = trim(str_replace(['```json', '```'], '', $aiText));
            $designSystem = json_decode($aiText, true);

            // Fallback if AI response is corrupted
            if (!$designSystem || !is_array($designSystem)) {
                $designSystem = [
                    'detected_img_count' => 1,
                    'detected_img_slots' => [['x' => 0.3, 'y' => 0.2, 'w' => 0.4, 'h' => 0.5, 'type' => 'transparent']],
                    'background_color' => '#ffffff',
                    'accent_colors' => ['#000000'],
                    'canvas_ratio' => 'square',
                    'text_count_approx' => 3,
                    'shape_count_approx' => 2,
                ];
            }

            // Fetch all templates with fingerprints and score them
            $allTemplates = CustomPostFrame::whereNotNull('fingerprint')
                ->where('status', 1)
                ->get();

            // Fallback to all editable templates if none fingerprinted
            if ($allTemplates->isEmpty()) {
                $allTemplates = CustomPostFrame::where('custom_frame_type', 'editable')
                    ->where('status', 1)
                    ->get();
            }

            // Score each template
            $scoredTemplates = $allTemplates->map(function ($template) use ($designSystem) {
                $fingerprint = $template->fingerprint;
                $score = 0;

                if ($fingerprint && is_array($fingerprint)) {
                    $score = $this->calculateMatchScore($fingerprint, $designSystem);
                }

                return [
                    'template' => $template,
                    'score' => $score,
                ];
            });

            // Sort by score descending and take top 3
            $topMatches = $scoredTemplates->sortByDesc('score')->take(3);

            $matchedTemplates = $topMatches->values()->map(function ($item) use ($designSystem) {
                $template = $item['template'];
                $score = $item['score'];

                // Get the actual template preview from the ZIP's skin images
                $thumb = $this->getTemplatePreviewThumb($template);

                return [
                    'id' => $template->id,
                    'thumb' => $thumb,
                    'zip_name' => $template->zip_name,
                    'match_score' => round($score),
                    'ai_analysis_data' => [
                        'background_color' => $designSystem['background_color'] ?? '#ffffff',
                        'accent_colors' => $designSystem['accent_colors'] ?? [],
                        'detected_img_count' => $designSystem['detected_img_count'] ?? 0,
                    ],
                    'frontend_mapping_rules' => json_decode(
                        MagicClonerSetting::getSetting('mapping_rules', '[]'), true
                    ) ?? [],
                ];
            });

            // Consume the limit since generation succeeded
            $user->consumeFeature('magic_cloner');

            return response()->json([
                'success' => true,
                'design_vibe_detected' => $designSystem,
                'suggested_templates' => $matchedTemplates,
                'remaining_uses' => $user->getRemainingUsage('magic_cloner')
            ]);

        } catch (\Exception $e) {
            Log::error("MagicCloner Exception: " . $e->getMessage());
            return response()->json(['success' => false, 'message' => 'Internal AI System Error.'], 500);
        }
    }

    /**
     * Build the structural analysis prompt for the AI Vision model.
     */
    private function buildAnalysisPrompt(): string
    {
        $customPrompt = MagicClonerSetting::getSetting('ai_prompt', '');

        if (!empty($customPrompt) && strlen($customPrompt) > 50) {
            return $customPrompt;
        }

        return 'You are an expert graphic design analyzer. Analyze this marketing/promotional image and extract its structural layout properties.

Return a strict JSON object (NO markdown, NO backticks) with these exact keys:
{
  "detected_img_count": <integer: number of distinct product/person photographs visible in the design>,
  "detected_img_slots": [
    {"x": <float 0-1 normalized from left>, "y": <float 0-1 normalized from top>, "w": <float 0-1 width ratio>, "h": <float 0-1 height ratio>, "type": "transparent|full"}
  ],
  "background_color": "#HEX of the dominant background color",
  "accent_colors": ["#HEX", "#HEX"],
  "canvas_ratio": "square|portrait|landscape",
  "text_count_approx": <integer: approximate number of distinct text blocks>,
  "shape_count_approx": <integer: number of decorative shapes, badges, circles, etc>
}

IMPORTANT RULES for counting images:
- Count ONLY main product photos or person photographs
- Do NOT count: background gradients, decorative shapes, shadows, reflections, badges, labels, stickers, icons, or logos
- "transparent" = product is a cutout floating without its own background
- "full" = product sits inside a rectangular photo frame with its own background visible
- Normalize all positions to 0-1 where (0,0) is top-left corner of the canvas
- For canvas_ratio: if roughly equal width/height = "square", taller than wide = "portrait", wider than tall = "landscape"';
    }

    /**
     * Calculate weighted match score between a template fingerprint and AI-detected properties.
     * 
     * Priority weights (total = 100):
     *   1. Image count match:    40 points (HIGHEST)
     *   2. Image size match:     25 points
     *   3. Image type match:     20 points
     *   4. Position match:       15 points
     */
    private function calculateMatchScore(array $fingerprint, array $detected): float
    {
        $score = 0.0;

        $fpImgCount = $fingerprint['img_count'] ?? 0;
        $aiImgCount = $detected['detected_img_count'] ?? 0;
        $fpSlots = $fingerprint['img_slots'] ?? [];
        $aiSlots = $detected['detected_img_slots'] ?? [];

        // 1. IMAGE COUNT MATCH — 40 points
        if ($fpImgCount == $aiImgCount) {
            $score += 40;
        } else {
            $diff = abs($fpImgCount - $aiImgCount);
            $score += max(0, 40 - ($diff * 15));
        }

        // 2. IMAGE SIZE MATCH — 25 points
        if (!empty($fpSlots) && !empty($aiSlots)) {
            $pairCount = min(count($fpSlots), count($aiSlots));
            $sizeScore = 0;

            for ($i = 0; $i < $pairCount; $i++) {
                $fpArea = ($fpSlots[$i]['w'] ?? 0) * ($fpSlots[$i]['h'] ?? 0);
                $aiArea = ($aiSlots[$i]['w'] ?? 0) * ($aiSlots[$i]['h'] ?? 0);

                if ($fpArea > 0 && $aiArea > 0) {
                    $areaRatio = min($fpArea, $aiArea) / max($fpArea, $aiArea);
                    $sizeScore += $areaRatio * (25.0 / $pairCount);
                }
            }
            $score += $sizeScore;
        } elseif ($fpImgCount == 0 && $aiImgCount == 0) {
            $score += 25;
        }

        // 3. IMAGE TYPE MATCH — 20 points
        if (!empty($fpSlots) && !empty($aiSlots)) {
            $pairCount = min(count($fpSlots), count($aiSlots));
            $typeScore = 0;

            for ($i = 0; $i < $pairCount; $i++) {
                $fpType = $fpSlots[$i]['type'] ?? 'full';
                $aiType = $aiSlots[$i]['type'] ?? 'full';
                if ($fpType === $aiType) {
                    $typeScore += (20.0 / $pairCount);
                }
            }
            $score += $typeScore;
        } elseif ($fpImgCount == 0 && $aiImgCount == 0) {
            $score += 20;
        }

        // 4. POSITION MATCH — 15 points
        if (!empty($fpSlots) && !empty($aiSlots)) {
            $pairCount = min(count($fpSlots), count($aiSlots));
            $posScore = 0;

            for ($i = 0; $i < $pairCount; $i++) {
                $fpCenterX = ($fpSlots[$i]['x'] ?? 0) + (($fpSlots[$i]['w'] ?? 0) / 2);
                $fpCenterY = ($fpSlots[$i]['y'] ?? 0) + (($fpSlots[$i]['h'] ?? 0) / 2);
                $aiCenterX = ($aiSlots[$i]['x'] ?? 0) + (($aiSlots[$i]['w'] ?? 0) / 2);
                $aiCenterY = ($aiSlots[$i]['y'] ?? 0) + (($aiSlots[$i]['h'] ?? 0) / 2);

                $distance = sqrt(pow($fpCenterX - $aiCenterX, 2) + pow($fpCenterY - $aiCenterY, 2));
                $proximity = max(0, 1 - ($distance / 1.414));
                $posScore += $proximity * (15.0 / $pairCount);
            }
            $score += $posScore;
        } elseif ($fpImgCount == 0 && $aiImgCount == 0) {
            $score += 15;
        }

        // BONUS: Canvas ratio match — +5 points
        $fpRatio = $fingerprint['canvas_ratio'] ?? 'square';
        $aiRatio = $detected['canvas_ratio'] ?? 'square';
        if ($fpRatio === $aiRatio) {
            $score += 5;
        }

        return $score;
    }

    /**
     * Get the actual template preview thumbnail from the ZIP's skins folder.
     */
    private function getTemplatePreviewThumb($template): string
    {
        $zipName = $template->zip_name;
        if (!$zipName) {
            return asset('uploads/' . $template->frame_image);
        }

        $templateDir = public_path('uploads/template/' . $zipName);
        $skinsDirs = [];

        if (is_dir($templateDir . '/skins')) {
            $skinsDirs = glob($templateDir . '/skins/*', GLOB_ONLYDIR);
        }

        if (empty($skinsDirs)) {
            $subDirs = glob($templateDir . '/*', GLOB_ONLYDIR);
            foreach ($subDirs as $subDir) {
                if (is_dir($subDir . '/skins')) {
                    $skinsDirs = glob($subDir . '/skins/*', GLOB_ONLYDIR);
                    break;
                }
            }
        }

        if (empty($skinsDirs)) {
            return asset('uploads/' . $template->frame_image);
        }

        $skinDir = $skinsDirs[0];
        $priorityFiles = ['Shape-1.png', 'BG.png', 'image-1.png', 'image.png', 'frame.png'];

        foreach ($priorityFiles as $file) {
            $path = $skinDir . '/' . $file;
            if (file_exists($path)) {
                $relativePath = str_replace(public_path(), '', $path);
                $relativePath = str_replace('\\', '/', $relativePath);
                return asset($relativePath);
            }
        }

        $allPngs = glob($skinDir . '/*.{png,jpg,jpeg}', GLOB_BRACE);
        if (!empty($allPngs)) {
            usort($allPngs, function ($a, $b) {
                return filesize($b) - filesize($a);
            });
            $relativePath = str_replace(public_path(), '', $allPngs[0]);
            $relativePath = str_replace('\\', '/', $relativePath);
            return asset($relativePath);
        }

        return asset('uploads/' . $template->frame_image);
    }
}
