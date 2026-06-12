<?php

namespace App\Services;

use App\Models\Product;
use App\Models\BusinessCustomFrame;
use App\Models\CustomFramePurpose;
use App\Models\UserCustomFrameContent;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Log;

class CustomFrameAIService
{
    /**
     * Stores the last generation's debug info for the Job/Monitor to consume.
     */
    private static array $lastLog = [];

    /**
     * Get the last generation log (raw prompt, response, tokens, product_id).
     */
    public static function getLastGenerationLog(): array
    {
        return self::$lastLog;
    }

    /**
     * Generate AI content for a specific frame template for the current user.
     * Uses Just-In-Time (Lazy) strategy: only generates when user requests, caches in DB.
     *
     * @param int $frameId BusinessCustomFrame ID
     * @param int $userId
     * @param bool $forceRegenerate If true, skip cache and regenerate
     * @return array|null Generated content map keyed by layer name
     */
    public static function generateForUser(int $frameId, int $userId, bool $forceRegenerate = false): ?array
    {
        // Prevent auto-generation if user has no products
        if (!Product::where('user_id', $userId)->exists()) {
            return null;
        }

        // Reset last log
        self::$lastLog = ['product_id' => null, 'raw_prompt' => null, 'raw_response' => null, 'tokens_used' => 0, 'error' => null];

        // 1. Check cache first (DB lookup)
        if (!$forceRegenerate) {
            $existing = UserCustomFrameContent::where('user_id', $userId)
                ->where('business_custom_frame_id', $frameId)
                ->first();

            if ($existing && !empty($existing->generated_content)) {
                return $existing->generated_content;
            }
        }

        // Delegate to the shared generation logic with no specific product (random pick)
        return self::generateInternal($frameId, $userId, null, $forceRegenerate);
    }

    /**
     * Generate AI content for a specific frame using a SPECIFIC product.
     * Uses per-product caching: user + frame + product combo.
     *
     * @param int $frameId BusinessCustomFrame ID
     * @param int $userId
     * @param int $productId Specific product to generate content for
     * @param bool $forceRegenerate If true, skip cache and regenerate
     * @return array|null Generated content map keyed by layer name
     */
    public static function generateForUserWithProduct(int $frameId, int $userId, int $productId, bool $forceRegenerate = false): ?array
    {
        // Prevent auto-generation if user has no products
        if (!Product::where('user_id', $userId)->exists()) {
            return null;
        }

        // Reset last log
        self::$lastLog = ['product_id' => null, 'raw_prompt' => null, 'raw_response' => null, 'tokens_used' => 0, 'error' => null];

        // 1. Check per-product cache first (user + frame + product combo)
        if (!$forceRegenerate) {
            $existing = UserCustomFrameContent::where('user_id', $userId)
                ->where('business_custom_frame_id', $frameId)
                ->where('product_id', $productId)
                ->first();

            if ($existing && !empty($existing->generated_content)) {
                self::$lastLog['product_id'] = $productId;
                return $existing->generated_content;
            }
        }

        // Delegate to shared generation logic with specific product
        return self::generateInternal($frameId, $userId, $productId, $forceRegenerate);
    }

    /**
     * Internal shared generation logic used by both generateForUser and generateForUserWithProduct.
     */
    private static function generateInternal(int $frameId, int $userId, ?int $specificProductId, bool $forceRegenerate): ?array
    {
        // 2. Load the frame and its relationships
        $frame = BusinessCustomFrame::with('purpose')->find($frameId);
        if (!$frame || !$frame->purpose) {
            self::$lastLog['error'] = "Frame #{$frameId} or its purpose not found.";
            Log::warning("CustomFrameAI: " . self::$lastLog['error']);
            return null;
        }

        $purpose = $frame->purpose;
        $jsonRules = json_decode($frame->json_rules, true);
        if (!$jsonRules || !isset($jsonRules['layers'])) {
            self::$lastLog['error'] = "No JSON rules/layers for frame #{$frameId}.";
            Log::warning("CustomFrameAI: " . self::$lastLog['error']);
            return null;
        }

        // 3. Get the user's product based on data_requirement
        $productData = self::getProductContext($userId, $purpose->data_requirement ?? 'single_column', $specificProductId);
        self::$lastLog['product_id'] = $productData['_product_id'] ?? null;

        // 4. Build the AI text layers that need generation
        $textLayers = [];
        foreach ($jsonRules['layers'] as $layer) {
            if ($layer['type'] === 'text' && !empty($layer['ai_role'])) {
                $textLayers[] = [
                    'name' => $layer['name'],
                    'ai_role' => $layer['ai_role'],
                    'ai_max_chars' => $layer['ai_max_chars'] ?? 50,
                    'default_text' => $layer['text'] ?? '',
                ];
            }
        }

        if (empty($textLayers)) {
            // No AI text layers, return defaults
            $defaults = [];
            foreach ($jsonRules['layers'] as $layer) {
                if ($layer['type'] === 'text') {
                    $defaults[$layer['name']] = $layer['text'] ?? '';
                }
            }
            return $defaults;
        }

        // 5. Build the master prompt
        $aiGlobalRule = $jsonRules['ai_global_rule'] ?? '';
        $purposePrompt = $purpose->ai_prompt ?? '';

        // Replace product placeholders in purpose prompt
        $purposePrompt = str_replace('{product_name}', $productData['title'] ?? 'Premium Product', $purposePrompt);
        $purposePrompt = str_replace('{product_price}', $productData['price'] ?? '', $purposePrompt);
        $purposePrompt = str_replace('{product_description}', $productData['description'] ?? '', $purposePrompt);
        $purposePrompt = str_replace('{business_name}', $productData['business_name'] ?? '', $purposePrompt);

        // Dynamically replace any column tags mapped in the context (like {col_is_category})
        foreach ($productData as $key => $value) {
            if (is_string($value)) {
                $purposePrompt = str_replace('{' . $key . '}', $value, $purposePrompt);
            }
        }

        $layerInstructions = "Generate content for these text layers as a JSON object.\n";
        $layerInstructions .= "Each key is the layer name, each value is the generated text.\n\n";

        foreach ($textLayers as $tl) {
            $layerInstructions .= "- \"{$tl['name']}\": {$tl['ai_role']} (MAX {$tl['ai_max_chars']} characters)\n";
        }

        $systemPrompt = "You are a professional social media content designer AI.\n\n";
        if (!empty($aiGlobalRule)) {
            $systemPrompt .= "GLOBAL DESIGN RULE: {$aiGlobalRule}\n\n";
        }
        $systemPrompt .= "BUSINESS CONTEXT:\n{$purposePrompt}\n\n";
        $systemPrompt .= "PRODUCT DATA:\n" . json_encode($productData, JSON_UNESCAPED_UNICODE) . "\n\n";
        $systemPrompt .= "STRICT INSTRUCTIONS:\n";
        $systemPrompt .= "1. You must output ONLY a raw, perfectly valid JSON object. No markdown, no ```json formatting, no explanation.\n";
        $systemPrompt .= "2. The JSON keys must exactly match the layer names below.\n";
        $systemPrompt .= "3. EXTREMELY STRICT CHARACTER LIMITS: You must absolutely respect the MAX character limits below. Truncate your response or drop words if necessary to stay under the limit. If a layer allows 15 chars, returning 16 is a failure.\n";
        $systemPrompt .= "4. Content must be engaging social media copy related to the product data.\n\n";
        $systemPrompt .= $layerInstructions;

        // Store raw prompt in log
        self::$lastLog['raw_prompt'] = $systemPrompt;

        // 6. Call AI
        try {
            $vertexAI = new VertexAIService($userId);
            if (!$vertexAI->isConfigured()) {
                self::$lastLog['error'] = "AI not configured for user #{$userId}.";
                Log::warning("CustomFrameAI: " . self::$lastLog['error']);
                return self::getFallbackContent($textLayers);
            }

            $result = $vertexAI->generateContent($systemPrompt, [
                ['role' => 'user', 'text' => 'Generate the content now. Output ONLY the JSON object.']
            ]);

            $responseText = $result['text'] ?? '';
            self::$lastLog['raw_response'] = $responseText;
            self::$lastLog['tokens_used'] = $result['total_tokens'] ?? 0;

            // Extract JSON from markdown fences or grab the first JSON-like block
            if (preg_match('/```(?:json)?\s*(.*?)\s*```/is', $responseText, $matches)) {
                $responseText = trim($matches[1]);
            } else if (preg_match('/\{.*\}/is', $responseText, $matches)) {
                $responseText = trim($matches[0]);
            } else {
                $responseText = trim($responseText);
            }

            $generated = json_decode($responseText, true);

            if (!$generated || !is_array($generated)) {
                self::$lastLog['error'] = "Invalid JSON response from AI.";
                Log::warning("CustomFrameAI: Invalid JSON response from AI.", ['raw' => $responseText]);
                $generated = self::getFallbackContent($textLayers);
            }

            foreach ($textLayers as $tl) {
                if (isset($generated[$tl['name']]) && is_string($generated[$tl['name']])) {
                    $generated[$tl['name']] = trim($generated[$tl['name']]);
                }
            }

        } catch (\Exception $e) {
            self::$lastLog['error'] = $e->getMessage();
            Log::error("CustomFrameAI: AI generation failed.", ['error' => $e->getMessage()]);
            $generated = self::getFallbackContent($textLayers);
        }

        // 7. Save to cache table (per-product cache)
        $actualProductId = $productData['_product_id'] ?? null;
        UserCustomFrameContent::updateOrCreate(
            [
                'user_id' => $userId,
                'business_custom_frame_id' => $frameId,
                'product_id' => $actualProductId,
            ],
            [
                'generated_content' => $generated,
            ]
        );

        return $generated;
    }

    /**
     * Generate manual AI content via prompt or specific product.
     */
    public static function generateManualContent(int $frameId, int $userId, ?int $productId = null, ?string $manualPrompt = null, array $canvasLayers = [], string $language = 'English'): ?array
    {
        self::$lastLog = ['product_id' => null, 'raw_prompt' => null, 'raw_response' => null, 'tokens_used' => 0, 'error' => null];

        $textLayers = [];
        $purposeText = "A promotional marketing design.";
        $dataReq = 'single_column';
        $globalRule = '';

        // Priority 1: Use canvas_layers sent from frontend (works for ALL template types)
        if (!empty($canvasLayers)) {
            foreach ($canvasLayers as $cl) {
                $textLayers[] = [
                    'name' => $cl['name'] ?? 'text_layer',
                    'ai_role' => !empty($cl['ai_role']) ? $cl['ai_role'] : 'Write engaging marketing text suitable for this layout element.',
                    'ai_max_chars' => $cl['max_chars'] ?? 60,
                    'default_text' => $cl['current_text'] ?? '',
                ];
            }
            \Log::info("AI Generate: Using " . count($textLayers) . " canvas layers from frontend.");
        }

        // Priority 2: Try DB lookup for BusinessCustomFrame (has json_rules with ai_role etc.)
        if (empty($textLayers) && $frameId > 0) {
            $frame = BusinessCustomFrame::with('purpose')->find($frameId);
            if ($frame && $frame->json_rules) {
                $jsonRules = json_decode($frame->json_rules, true);
                if ($jsonRules && isset($jsonRules['layers'])) {
                    $globalRule = $jsonRules['ai_global_rule'] ?? '';
                    if ($frame->purpose) {
                        $purposeText = $frame->purpose->ai_prompt ?? $purposeText;
                        $dataReq = $frame->purpose->data_requirement ?? 'single_column';
                    }
                    foreach ($jsonRules['layers'] as $layer) {
                        $type = $layer['type'] ?? '';
                        if ($type === 'text' || $type === 'i-text' || $type === 'textbox') {
                            $layerName = $layer['name'] ?? $layer['id'] ?? 'text_layer';
                            $low = strtolower($layerName);
                            if (in_array($low, ['website', 'email', 'number', 'phone', 'address', 'phoneicon', 'mailicon', 'webicon', 'addressicon'])) continue;
                            $textLayers[] = [
                                'name' => $layerName,
                                'ai_role' => $layer['ai_role'] ?? 'Write engaging marketing text suitable for this layout.',
                                'ai_max_chars' => $layer['ai_max_chars'] ?? 60,
                                'default_text' => $layer['text'] ?? '',
                            ];
                        }
                    }
                }
            }
        }

        if (empty($textLayers)) {
            self::$lastLog['error'] = "No text layers found to generate content for frame #{$frameId}.";
            \Log::error("CustomFrameAIService::generateManualContent - " . self::$lastLog['error']);
            return null;
        }

        // Build prompt context
        $purposePrompt = "";
        $productData = [];

        if (!empty($manualPrompt)) {
            $purposePrompt = "User's Manual Instruction: " . $manualPrompt;
        } else {
            $productData = self::getProductContext($userId, $dataReq, $productId);
            self::$lastLog['product_id'] = $productData['_product_id'] ?? null;
            $purposePrompt = $purposeText;
            $purposePrompt = str_replace('{product_name}', $productData['title'] ?? 'Premium Product', $purposePrompt);
            $purposePrompt = str_replace('{product_price}', $productData['price'] ?? '', $purposePrompt);
            $purposePrompt = str_replace('{product_description}', $productData['description'] ?? '', $purposePrompt);
            $purposePrompt = str_replace('{business_name}', $productData['business_name'] ?? '', $purposePrompt);

            foreach ($productData as $key => $value) {
                if (is_string($value)) {
                    $purposePrompt = str_replace('{' . $key . '}', $value, $purposePrompt);
                }
            }
        }

        $layerInstructions = "Generate content for these text layers as a JSON object.\nEach key is the layer name, each value is the generated text.\n\n";
        foreach ($textLayers as $tl) {
            $layerInstructions .= "- \"{$tl['name']}\": {$tl['ai_role']} (MAX {$tl['ai_max_chars']} characters)\n";
        }

        $systemPrompt = "You are a professional social media content designer AI.\n\n";
        if (!empty($globalRule)) {
            $systemPrompt .= "GLOBAL DESIGN RULE: {$globalRule}\n\n";
        }
        $systemPrompt .= "CONTEXT:\n{$purposePrompt}\n\n";
        if (!empty($productData)) {
            $systemPrompt .= "PRODUCT DATA:\n" . json_encode($productData, JSON_UNESCAPED_UNICODE) . "\n\n";
        }
        $languageInstruction = "5. LANGUAGE: You MUST write ALL content strictly in {$language} language only. Ignore the language of the existing/current text in layers. Every single text value in your JSON output must be in {$language}.\n";
        $systemPrompt .= "STRICT INSTRUCTIONS:\n1. Output ONLY a raw, valid JSON object.\n2. Keys must exactly match the layer names (keys stay as-is, only values change).\n3. EXTREMELY STRICT CHARACTER LIMITS: Truncate response to stay under the limit.\n4. Engaging social media copy.\n" . $languageInstruction . "\n" . $layerInstructions;

        self::$lastLog['raw_prompt'] = $systemPrompt;

        try {
            $vertexAI = new VertexAIService($userId);
            if (!$vertexAI->isConfigured()) {
                self::$lastLog['error'] = "AI not configured for user #{$userId}.";
                \Log::error("CustomFrameAIService - " . self::$lastLog['error']);
                return self::getFallbackContent($textLayers);
            }

            $result = $vertexAI->generateContent($systemPrompt, [
                ['role' => 'user', 'text' => 'Generate the content now. Output ONLY the JSON object.']
            ]);

            $responseText = $result['text'] ?? '';
            self::$lastLog['raw_response'] = $responseText;
            self::$lastLog['tokens_used'] = $result['total_tokens'] ?? 0;

            if (preg_match('/```(?:json)?\s*(.*?)\s*```/is', $responseText, $matches)) {
                $responseText = trim($matches[1]);
            } else if (preg_match('/\{.*\}/is', $responseText, $matches)) {
                $responseText = trim($matches[0]);
            } else {
                $responseText = trim($responseText);
            }

            $generated = json_decode($responseText, true);

            if (!$generated || !is_array($generated)) {
                self::$lastLog['error'] = "Invalid JSON response from AI.";
                $generated = self::getFallbackContent($textLayers);
            }

            foreach ($textLayers as $tl) {
                if (isset($generated[$tl['name']]) && is_string($generated[$tl['name']])) {
                    $generated[$tl['name']] = trim($generated[$tl['name']]);
                }
            }

        } catch (\Exception $e) {
            self::$lastLog['error'] = $e->getMessage();
            \Log::error("CustomFrameAIService AI exception: " . $e->getMessage());
            $generated = self::getFallbackContent($textLayers);
        }

        return $generated;
    }

    /**
     * Get product data based on the Purpose's data_requirement setting.
     */
    private static function getProductContext(int $userId, string $dataRequirement, ?int $specificProductId = null): array
    {
        // If a specific product ID is given, fetch that exact product; otherwise pick random
        if ($specificProductId) {
            $product = Product::where('user_id', $userId)->where('id', $specificProductId)->with('customValues')->first();
        } else {
            $product = Product::where('user_id', $userId)->with('customValues')->inRandomOrder()->first();
        }
        $business = \App\Models\Business::where('user_id', $userId)->where('is_default', 1)->first();

        $context = [
            'title' => 'Premium Product',
            'business_name' => $business->name ?? 'My Business',
            '_product_id' => null,
        ];

        if (!$product) {
            return $context;
        }

        $context['_product_id'] = $product->id;

        // Always include title
        $context['title'] = $product->display_name ?? $product->title ?? 'Premium Product';

        if ($dataRequirement === 'basic_columns') {
            $context['price'] = $product->sale_price ?? $product->price ?? $product->mrp ?? '';
            $context['mrp'] = $product->mrp ?? '';
        } elseif ($dataRequirement === 'full_row') {
            $context['price'] = $product->sale_price ?? $product->price ?? $product->mrp ?? '';
            $context['mrp'] = $product->mrp ?? '';
            $context['description'] = $product->description ?? '';
            $context['category'] = $product->category_name ?? '';
            $context['sku'] = $product->sku ?? '';
        }

        // Fetch custom columns to populate special AI tags
        $columns = \App\Models\CatalogueCustomColumn::where('user_id', $userId)
            ->where('is_active', true)
            ->orderBy('sort_order')
            ->get();

        $normalFields = [];
        $context['col_is_category'] = '';
        $context['col_is_unique'] = '';
        $context['col_is_combo'] = '';

        foreach ($columns as $col) {
            $val = $product->customValues->firstWhere('column_id', $col->id);
            $displayVal = '';
            
            if ($val && !empty($val->value)) {
                $displayVal = $val->value;
                $decoded = json_decode($displayVal, true);
                if (is_array($decoded)) {
                    $displayVal = implode(', ', $decoded);
                }
            }

            if (empty($displayVal)) continue;

            $isSpecial = false;
            if ($col->is_category) {
                $context['col_is_category'] = $displayVal;
                $isSpecial = true;
            }
            if ($col->is_unique) {
                $context['col_is_unique'] = $displayVal;
                $isSpecial = true;
            }
            if ($col->is_combo) {
                $context['col_is_combo'] = $displayVal;
                $isSpecial = true;
            }
            if ($col->is_title) {
                $isSpecial = true;
            }

            if (!$isSpecial) {
                $normalFields[] = $col->name . ': ' . $displayVal;
            }
        }

        $context['col_is_Normal Regular Field'] = implode("\n", $normalFields);
        $context['custom_data'] = $context['col_is_Normal Regular Field']; // Fallback for old templates

        return $context;
    }

    /**
     * Fallback content when AI is unavailable.
     */
    private static function getFallbackContent(array $textLayers): array
    {
        $fallback = [];
        foreach ($textLayers as $tl) {
            $fallback[$tl['name']] = $tl['default_text'] ?: 'SALE';
        }
        return $fallback;
    }
}
