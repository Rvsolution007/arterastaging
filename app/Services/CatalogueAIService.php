<?php

namespace App\Services;

use App\Models\Setting;
use App\Models\CatalogueCustomColumn;
use PhpOffice\PhpSpreadsheet\Spreadsheet;
use PhpOffice\PhpSpreadsheet\Cell\Coordinate;
use PhpOffice\PhpSpreadsheet\Style\Fill;
use PhpOffice\PhpSpreadsheet\Style\Alignment;
use PhpOffice\PhpSpreadsheet\Style\Border;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Cache;
use Illuminate\Http\UploadedFile;

class CatalogueAIService
{
    private VertexAIService $vertexAI;
    private int $userId;

    public function __construct(int $userId)
    {
        $this->userId = $userId;
        $this->vertexAI = new VertexAIService($userId);
    }

    public function extractTextFromPDF(UploadedFile $file): string
    {
        $originalMemory = ini_get('memory_limit');
        ini_set('memory_limit', '4G');
        try {
            return $this->doExtractTextFromPDF($file);
        } catch (\Error $e) {
            Log::error('PDF extraction fatal error: ' . $e->getMessage());
            return '';
        } finally {
            ini_set('memory_limit', $originalMemory);
        }
    }

    private function doExtractTextFromPDF(UploadedFile $file): string
    {
        $pdfPathToParse = $file->getRealPath();
        
        // Sample pages across the document to get a representative schema structure without memory crash
        $totalPages = $this->vertexAI->getPDFPageCount($pdfPathToParse);
        $reducedPath = null;
        if ($totalPages > 10) {
            $reducedPath = $this->vertexAI->extractSampledPDFPages($pdfPathToParse, 10);
            if ($reducedPath && file_exists($reducedPath)) {
                $pdfPathToParse = $reducedPath;
            }
        }

        $text = '';
        if (class_exists(\Smalot\PdfParser\Parser::class)) {
            try {
                $parser = new \Smalot\PdfParser\Parser();
                $pdf = $parser->parseFile($pdfPathToParse);
                $text = $pdf->getText();
                if ($this->isTextQualityGood($text)) {
                    $text = $this->cleanExtractedText($text);
                } else {
                    $text = '';
                }
            } catch (\Exception $e) {
                Log::warning('PDF Parser failed', ['error' => $e->getMessage()]);
            } catch (\Error $e) {
                Log::error('PDF Parser fatal error: ' . $e->getMessage());
            }
        }

        if (empty($text)) {
            $content = @file_get_contents($pdfPathToParse);
            if ($content !== false) {
                $textRaw = $this->extractTextFromPDFStream($content);
                unset($content);
                if ($this->isTextQualityGood($textRaw)) {
                    $text = $this->cleanExtractedText($textRaw);
                }
            }
        }
        
        if ($reducedPath && file_exists($reducedPath)) {
            @unlink($reducedPath);
        }

        return $text;
    }

    private function isTextQualityGood(string $text): bool
    {
        $text = trim($text);
        if (strlen($text) < 200) return false;
        $meaningful = preg_match_all('/[a-zA-Z0-9\p{L}]/u', $text);
        $total = strlen($text);
        if ($total > 0 && ($meaningful / $total) < 0.3) return false;
        $wordCount = preg_match_all('/[a-zA-Z\p{L}]{3,}/u', $text);
        if ($wordCount < 10) return false;
        return true;
    }

    private function extractTextFromPDFStream(string $content): string
    {
        $text = '';
        if (preg_match_all('/BT\s*(.*?)\s*ET/s', $content, $matches)) {
            foreach ($matches[1] as $block) {
                if (preg_match_all('/\((.*?)\)\s*Tj/s', $block, $texts)) $text .= implode(' ', $texts[1]) . "\n";
                if (preg_match_all('/\[(.*?)\]\s*TJ/s', $block, $arrays)) {
                    foreach ($arrays[1] as $arr) {
                        if (preg_match_all('/\((.*?)\)/s', $arr, $parts)) $text .= implode('', $parts[1]) . ' ';
                    }
                    $text .= "\n";
                }
            }
        }
        return $text;
    }

    private function cleanExtractedText(string $text): string
    {
        $text = preg_replace('/\s{3,}/', "\n", $text);
        $text = preg_replace('/[^\P{C}\n\t]/u', '', $text);
        $text = preg_replace('/\n{3,}/', "\n\n", $text);
        if (mb_strlen($text) > 200000) $text = mb_substr($text, 0, 200000) . "\n\n[... content truncated ...]";
        return trim($text);
    }

    public function analyzeCatalogueFromPDF(string $pdfPath): array
    {
        if (!$this->vertexAI->isConfigured()) throw new \RuntimeException('AI is not configured.');

        $customPrompt = Setting::getGlobalValue('setup_tour', 'column_analysis_prompt', '');
        $systemPrompt = !empty($customPrompt) ? $customPrompt : $this->getDefaultColumnAnalysisPrompt();

        $userMessage = "SOURCE TYPE: pdf\n\nPlease analyze this product catalogue PDF and identify the optimal database column structure. ONLY include columns for data that is ACTUALLY VISIBLE in the pages.";

        $analysisPath = $pdfPath;
        $totalPages = $this->vertexAI->getPDFPageCount($pdfPath);
        $pdfSizeMB = filesize($pdfPath) / 1024 / 1024;

        // SCHEMA EXTRACTION ONLY: Sample 10 pages spread across the document (e.g., 10%, 20%...)
        // ALWAYS use sampling even for small PDFs, because extractSampledPDFPages specifically skips
        // page 1 (cover/about us) which pollutes the column schema.
        $maxAnalysisPages = 10;

        $reduced = $this->vertexAI->extractSampledPDFPages($pdfPath, $maxAnalysisPages);
        if ($reduced && file_exists($reduced)) {
            $analysisPath = $reduced;
        }

        $result = $this->vertexAI->generateContentWithPDF($systemPrompt, $analysisPath, $userMessage, 8192);
        if ($analysisPath !== $pdfPath && file_exists($analysisPath)) @unlink($analysisPath);

        $json = $this->extractJSONFromResponse($result['text']);
        if (!$json || !isset($json['columns'])) {
            throw new \RuntimeException('AI could not analyze the catalogue structure from the PDF.');
        }

        $columns = $this->sanitizeColumns($json['columns']);
        
        // Ensure "Image" column exists if AI suggested it or if we want to be safe
        // (User wants it to show up if images exist in PDF)
        // For now, let's keep the auto-injection but maybe label it as optional
        
        return [
            'columns' => $columns,
            'source_summary' => $json['source_summary'] ?? 'Catalogue analyzed from PDF',
            'confidence' => $json['confidence'] ?? 80,
            'ai_tokens' => $result['total_tokens'] ?? 0,
            'business_details' => $json['business_details'] ?? null,
        ];
    }

    public function extractProductDataFromPDF(string $pdfPath, array $columns): array
    {
        if (!$this->vertexAI->isConfigured()) throw new \RuntimeException('AI is not configured.');

        $systemPrompt = $this->getProductExtractionPrompt($columns);
        $totalPages = $this->vertexAI->getPDFPageCount($pdfPath);
        $pagesPerChunk = 4; // Reduced from 10 to 4 to prevent AI laziness/token limits on dense tables

        $allProducts = []; $totalTokens = 0; $chunkPaths = [];

        if ($totalPages <= 4 && filesize($pdfPath) <= 5 * 1024 * 1024) {
            return $this->extractProductsFromSinglePDF($systemPrompt, $pdfPath);
        }

        for ($startPage = 1; $startPage <= $totalPages; $startPage += $pagesPerChunk) {
            $endPage = min($startPage + $pagesPerChunk - 1, $totalPages);
            $chunkPath = $this->vertexAI->extractPDFPageRange($pdfPath, $startPage, $endPage);
            if (!$chunkPath || !file_exists($chunkPath)) continue;
            $chunkPaths[] = $chunkPath;
            if (filesize($chunkPath) > 18 * 1024 * 1024) continue;

            try {
                $result = $this->vertexAI->generateContentWithPDF($systemPrompt, $chunkPath,
                    "CRITICAL INSTRUCTION: Extract 100% of the individual products from pages {$startPage} to {$endPage} without skipping any rows or summarizing. Each model/SKU must be a completely separate product row. Return ONLY JSON, no markdown.", 32768);
                $totalTokens += $result['total_tokens'] ?? 0;
                $json = $this->extractJSONFromResponse($result['text']);
                if ($json && !empty($json['products'])) $allProducts = array_merge($allProducts, $json['products']);
            } catch (\Exception $e) {
                Log::error("CatalogueAI: Chunk failed", ['error' => $e->getMessage()]);
            }
        }

        foreach ($chunkPaths as $cp) { if (file_exists($cp)) @unlink($cp); }
        $allProducts = $this->deduplicateProducts($allProducts, $columns);

        if (empty($allProducts)) throw new \RuntimeException('AI could not extract any products from the PDF.');

        return ['products' => $allProducts, 'total' => count($allProducts), 'ai_tokens' => $totalTokens];
    }

    private function extractProductsFromSinglePDF(string $systemPrompt, string $pdfPath): array
    {
        $result = $this->vertexAI->generateContentWithPDF($systemPrompt, $pdfPath,
            "CRITICAL INSTRUCTION: Extract 100% of the individual products from this catalogue PDF without skipping any rows or summarizing. Each model/SKU must be a completely separate product row. Return ONLY JSON, no markdown.", 32768);
        $json = $this->extractJSONFromResponse($result['text']);
        if (!$json || !isset($json['products'])) throw new \RuntimeException('AI could not extract product data from the PDF.');
        return ['products' => $json['products'] ?? [], 'total' => count($json['products'] ?? []), 'ai_tokens' => $result['total_tokens'] ?? 0];
    }

    private function deduplicateProducts(array $products, array $columns): array
    {
        if (empty($products)) return $products;
        $uniqueColName = null;
        foreach ($columns as $col) { if ($col['is_unique'] ?? false) { $uniqueColName = $col['name']; break; } }
        if (!$uniqueColName) {
            foreach ($columns as $col) {
                if (in_array(mb_strtolower($col['name']), ['model number', 'model', 'sku', 'item code'])) { $uniqueColName = $col['name']; break; }
            }
        }
        if (!$uniqueColName) return $products;

        $seen = []; $unique = [];
        foreach ($products as $p) {
            $val = $p[$uniqueColName] ?? '';
            if (empty($val)) { $unique[] = $p; continue; }
            $key = mb_strtolower(trim($val));
            if (!isset($seen[$key])) { $seen[$key] = true; $unique[] = $p; }
        }
        return $unique;
    }

    public function scrapeWebsite(string $url): string
    {
        try {
            $response = Http::withHeaders([
                'User-Agent' => 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36',
            ])->timeout(30)->get($url);
            if (!$response->successful()) throw new \RuntimeException("Could not access website (HTTP {$response->status()}).");
            return $this->extractTextFromHTML($response->body());
        } catch (\Illuminate\Http\Client\ConnectionException $e) {
            throw new \RuntimeException('Could not connect to the website.');
        }
    }

    private function extractTextFromHTML(string $html): string
    {
        $html = preg_replace('/<script[^>]*>.*?<\/script>/si', '', $html);
        $html = preg_replace('/<style[^>]*>.*?<\/style>/si', '', $html);
        $html = preg_replace('/<nav[^>]*>.*?<\/nav>/si', '', $html);
        $html = preg_replace('/<footer[^>]*>.*?<\/footer>/si', '', $html);
        $html = preg_replace('/<header[^>]*>.*?<\/header>/si', '', $html);
        $html = preg_replace('/<\/td>\s*<td/i', ' | <td', $html);
        $html = preg_replace('/<\/tr>/i', "\n", $html);
        $html = preg_replace('/<br\s*\/?>/i', "\n", $html);
        $html = preg_replace('/<\/p>/i', "\n", $html);
        $text = strip_tags($html);
        $text = html_entity_decode($text, ENT_QUOTES, 'UTF-8');
        return $this->cleanExtractedText($text);
    }

    public function analyzeCatalogueSource(string $content, string $sourceType): array
    {
        if (!$this->vertexAI->isConfigured()) throw new \RuntimeException('AI is not configured.');

        $customPrompt = Setting::getGlobalValue('setup_tour', 'column_analysis_prompt', '');
        $systemPrompt = !empty($customPrompt) ? $customPrompt : $this->getDefaultColumnAnalysisPrompt();

        $result = $this->vertexAI->generateContent($systemPrompt, [['role' => 'user', 'text' => "SOURCE TYPE: {$sourceType}\n\nCATALOGUE CONTENT:\n\n{$content}"]]);
        $json = $this->extractJSONFromResponse($result['text']);
        if (!$json || !isset($json['columns'])) throw new \RuntimeException('AI could not analyze the catalogue structure.');

        $columns = $this->sanitizeColumns($json['columns']);

        return [
            'columns' => $columns,
            'source_summary' => $json['source_summary'] ?? 'Catalogue analyzed',
            'confidence' => $json['confidence'] ?? 80,
            'ai_tokens' => $result['total_tokens'] ?? 0,
            'business_details' => $json['business_details'] ?? null,
        ];
    }

    public function extractProductData(string $content, array $columns): array
    {
        if (!$this->vertexAI->isConfigured()) throw new \RuntimeException('AI is not configured.');

        $customPrompt = Setting::getGlobalValue('setup_tour', 'product_extraction_prompt', '');
        $systemPrompt = !empty($customPrompt) ? $customPrompt : $this->getProductExtractionPrompt($columns);

        $result = $this->vertexAI->generateContent($systemPrompt, [['role' => 'user', 'text' => "CATALOGUE CONTENT:\n\n{$content}"]]);
        $json = $this->extractJSONFromResponse($result['text']);
        if (!$json || !isset($json['products'])) throw new \RuntimeException('AI could not extract product data.');

        return ['products' => $json['products'] ?? [], 'total' => count($json['products'] ?? []), 'ai_tokens' => $result['total_tokens'] ?? 0];
    }

    public function extractProductDataFromImage(string $base64Image, string $mimeType, array $columns): array
    {
        if (!$this->vertexAI->isConfigured()) throw new \RuntimeException('AI is not configured.');

        $customPrompt = Setting::getGlobalValue('setup_tour', 'product_extraction_prompt', '');
        $systemPrompt = !empty($customPrompt) ? $customPrompt : $this->getProductExtractionPrompt($columns);
        $systemPrompt .= "\n\nPlease extract ALL products found in the provided image.";

        $result = $this->vertexAI->generateVisionContentFromBase64($systemPrompt, $base64Image, $mimeType, true);
        
        $json = $this->extractJSONFromResponse($result['text']);
        if (!$json || !isset($json['products'])) throw new \RuntimeException('AI could not extract product data from the image.');

        $products = $json['products'] ?? [];
        $uniqueProducts = [];
        $seen = [];
        
        foreach ($products as $p) {
            // Find title column
            $titleCol = collect($columns)->firstWhere('is_title', true);
            $titleKey = $titleCol ? $titleCol['name'] : 'title';
            $title = strtolower(trim($p[$titleKey] ?? $p['title'] ?? ''));
            
            if (!empty($title) && !isset($seen[$title])) {
                $seen[$title] = true;
                $uniqueProducts[] = $p;
            } elseif (empty($title)) {
                $uniqueProducts[] = $p; // If no title found, just include it
            }
        }

        $tokens = $result['raw']['usageMetadata']['totalTokenCount'] ?? 0;

        return ['products' => $uniqueProducts, 'total' => count($uniqueProducts), 'ai_tokens' => $tokens];
    }

    private function extractJSONFromResponse(string $text): ?array
    {
        // 1. Try direct decode
        $decoded = json_decode($text, true);
        if ($decoded !== null) return $decoded;

        // 2. Try ```json ... ``` block (with closing backticks)
        if (preg_match('/```(?:json)?\s*\n?(.*?)\n?```/s', $text, $matches)) {
            $decoded = json_decode(trim($matches[1]), true);
            if ($decoded !== null) return $decoded;
        }

        // 3. Try ```json block WITHOUT closing backticks (truncated response)
        if (preg_match('/```(?:json)?\s*\n?(.*)/s', $text, $matches)) {
            $jsonContent = trim($matches[1]);
            // Remove trailing ``` if present
            $jsonContent = preg_replace('/```\s*$/', '', $jsonContent);
            $decoded = json_decode($jsonContent, true);
            if ($decoded !== null) return $decoded;

            Log::warning('CatalogueAI: JSON decode failed. Raw response snippet: ' . substr($jsonContent, 0, 1000) . ' ... END: ' . substr($jsonContent, -1000));
            
            // Try to repair truncated JSON
            $repaired = $this->repairTruncatedJSON($jsonContent);
            if ($repaired) {
                $decoded = json_decode($repaired, true);
                if ($decoded !== null) {
                    Log::info('CatalogueAI: Repaired truncated JSON successfully');
                    return $decoded;
                }
            }
        }

        // 4. Try findBalancedJSON on known keys
        foreach (['columns', 'products'] as $key) {
            if (preg_match('/\{[\s\S]*"' . $key . '"[\s\S]*\}/s', $text, $matches)) {
                $json = $this->findBalancedJSON($matches[0]);
                if ($json) { $decoded = json_decode($json, true); if ($decoded !== null) return $decoded; }
            }
        }

        // 5. Try findBalancedJSON on first { 
        if (preg_match('/\{/', $text)) {
            $json = $this->findBalancedJSON(substr($text, strpos($text, '{')));
            if ($json) { $decoded = json_decode($json, true); if ($decoded !== null) return $decoded; }
        }
        
        // 6. Last resort: try to repair from the first {
        $firstBrace = strpos($text, '{');
        if ($firstBrace !== false) {
            $jsonPart = substr($text, $firstBrace);
            $repaired = $this->repairTruncatedJSON($jsonPart);
            if ($repaired) {
                $decoded = json_decode($repaired, true);
                if ($decoded !== null) {
                    Log::info('CatalogueAI: Repaired truncated JSON from raw text');
                    return $decoded;
                }
            }
        }

        Log::warning('CatalogueAI: Could not extract JSON', ['preview' => substr($text, 0, 500)]);
        return null;
    }

    /**
     * Attempt to repair truncated JSON by closing open brackets/braces.
     * Common when AI hits token limit mid-response.
     */
    private function repairTruncatedJSON(string $json): ?string
    {
        // Remove any trailing incomplete string value (e.g., "some text that got cu)
        // Find last complete key-value or array element
        $json = rtrim($json);
        
        // Remove trailing comma
        $json = rtrim($json, ',');
        
        // Remove incomplete string at end (e.g., "some incomplete val...)
        // If the last non-whitespace before any brackets is inside a string, close it
        if (preg_match('/,\s*"[^"]*$/s', $json)) {
            // Truncated in middle of a string value - remove the incomplete entry
            $json = preg_replace('/,\s*"[^"]*$/s', '', $json);
        }
        if (preg_match('/,\s*\{[^}]*$/s', $json)) {
            // Truncated in middle of an object - remove the incomplete object
            $json = preg_replace('/,\s*\{[^}]*$/s', '', $json);
        }
        
        // Count open/close brackets
        $openBraces = substr_count($json, '{');
        $closeBraces = substr_count($json, '}');
        $openBrackets = substr_count($json, '[');
        $closeBrackets = substr_count($json, ']');
        
        // Add missing closing brackets/braces
        $json .= str_repeat(']', max(0, $openBrackets - $closeBrackets));
        $json .= str_repeat('}', max(0, $openBraces - $closeBraces));
        
        // Verify it's valid
        $test = json_decode($json, true);
        if ($test !== null) return $json;
        
        // Try more aggressive repair: strip trailing incomplete entries and reclose
        // Remove last incomplete array element or object property
        $json = rtrim($json, '}]');
        $json = rtrim($json);
        $json = rtrim($json, ',');
        
        $openBraces = substr_count($json, '{');
        $closeBraces = substr_count($json, '}');
        $openBrackets = substr_count($json, '[');
        $closeBrackets = substr_count($json, ']');
        
        $json .= str_repeat(']', max(0, $openBrackets - $closeBrackets));
        $json .= str_repeat('}', max(0, $openBraces - $closeBraces));
        
        $test = json_decode($json, true);
        return $test !== null ? $json : null;
    }

    private function findBalancedJSON(string $str): ?string
    {
        $depth = 0; $inString = false; $escape = false;
        for ($i = 0; $i < strlen($str); $i++) {
            $char = $str[$i];
            if ($escape) { $escape = false; continue; }
            if ($char === '\\') { $escape = true; continue; }
            if ($char === '"') { $inString = !$inString; continue; }
            if (!$inString) {
                if ($char === '{') $depth++;
                if ($char === '}') { $depth--; if ($depth === 0) return substr($str, 0, $i + 1); }
            }
        }
        return null;
    }

    private function sanitizeColumns(array $columns): array
    {
        $validTypes = ['text', 'textarea', 'number', 'select', 'multiselect', 'boolean'];
        $sanitized = [];
        foreach ($columns as $index => $col) {
            if (empty($col['name'])) continue;
            $type = $col['type'] ?? 'text';
            if (!in_array($type, $validTypes)) $type = 'text';
            $options = [];
            if (in_array($type, ['select', 'multiselect']) && !empty($col['options'])) {
                $options = is_array($col['options']) ? array_values(array_filter($col['options'])) : [];
            }
            if (!empty($col['is_combo']) && !empty($col['options'])) {
                $options = is_array($col['options']) ? array_values(array_filter($col['options'])) : [];
                $type = 'select';
            }
            $sanitized[] = [
                'name' => substr(trim($col['name']), 0, 100), 'type' => $type,
                'is_unique' => (bool)($col['is_unique'] ?? false), 'is_required' => (bool)($col['is_required'] ?? false),
                'is_category' => (bool)($col['is_category'] ?? false), 'is_title' => (bool)($col['is_title'] ?? false),
                'is_combo' => (bool)($col['is_combo'] ?? false), 'is_variation_field' => (bool)($col['is_variation_field'] ?? false),
                'options' => $options, 'show_in_ai' => (bool)($col['show_in_ai'] ?? true),
                'sort_order' => $col['sort_order'] ?? ($index + 1),
            ];
        }
        return $sanitized;
    }

    private function getDefaultColumnAnalysisPrompt(): string
    {
        return <<<'PROMPT'
You are a world-class Product Catalogue Data Architect with 20+ years of experience in e-commerce, manufacturing, and wholesale catalogue digitization.

YOUR MISSION: Analyze the catalogue and design the OPTIMAL database column structure. You MUST follow the 2-PHASE methodology below.

â”â”â”â”â”â”â”â”â”â”â”â”â”â”â”â”â”â”â”â”â”â”â”â”â”â”â”â”â”â”â”â”â”â”â”â”â”â”â”â”â”
âš ï¸ GOLDEN RULES:
â”â”â”â”â”â”â”â”â”â”â”â”â”â”â”â”â”â”â”â”â”â”â”â”â”â”â”â”â”â”â”â”â”â”â”â”â”â”â”â”â”

ðŸš« NEVER add columns for data NOT visible in the catalogue.
ðŸš« NEVER hallucinate fields like price, GST, HSN, description unless CLEARLY shown.
âœ… ONLY include columns for attributes you can SEE in the catalogue.
ðŸ“¸ IMAGE DETECTION: If the catalogue contains product images, photos, or thumbnails, you MUST include a column named "Image" (type="text", is_system=false).

â”â”â”â”â”â”â”â”â”â”â”â”â”â”â”â”â”â”â”â”â”â”â”â”â”â”â”â”â”â”â”â”â”â”â”â”â”â”â”â”â”â”â”â”â”â”â”â”â”â”â”â”â”â”â”â”â”â”â”â”â”â”â”â”â”â”â”â”â”â”â”â”â”â”â”â”â”â”â”
ðŸ§  PHASE 1: MENTAL RAW SCAN (do this FIRST before defining any columns)
â”â”â”â”â”â”â”â”â”â”â”â”â”â”â”â”â”â”â”â”â”â”â”â”â”â”â”â”â”â”â”â”â”â”â”â”â”â”â”â”â”â”â”â”â”â”â”â”â”â”â”â”â”â”â”â”â”â”â”â”â”â”â”â”â”â”â”â”â”â”â”â”â”â”â”â”â”â”â”

Before defining columns, scan EVERY product/item in the catalogue and mentally build a raw list:

For EACH product block/card you see, note:
  â€¢ The FULL heading text exactly as shown (e.g., "HANDY CHOPPER 750ML", "NEO PUSH CHOPPER 500ML")
  â€¢ ALL visible attributes/specifications for that product
  â€¢ ALL pricing/measurement data shown

Do NOT output this list. Just build it mentally. You need it for Phase 2.

â”â”â”â”â”â”â”â”â”â”â”â”â”â”â”â”â”â”â”â”â”â”â”â”â”â”â”â”â”â”â”â”â”â”â”â”â”â”â”â”â”â”â”â”â”â”â”â”â”â”â”â”â”â”â”â”â”â”â”â”â”â”â”â”â”â”â”â”â”â”â”â”â”â”â”â”â”â”â”
ðŸ” PHASE 2: PATTERN DETECTION (this determines your column definitions)
â”â”â”â”â”â”â”â”â”â”â”â”â”â”â”â”â”â”â”â”â”â”â”â”â”â”â”â”â”â”â”â”â”â”â”â”â”â”â”â”â”â”â”â”â”â”â”â”â”â”â”â”â”â”â”â”â”â”â”â”â”â”â”â”â”â”â”â”â”â”â”â”â”â”â”â”â”â”â”

Using your Phase 1 raw list, perform these pattern analyses IN ORDER:

â”€â”€â”€ STEP A: HEADING DECOMPOSITION â”€â”€â”€

For every product heading, SPLIT IT into components:
  "HANDY CHOPPER 750ML" â†’ "HANDY CHOPPER" + "750ML"
  "HANDY CHOPPER 500ML" â†’ "HANDY CHOPPER" + "500ML"
  "NEO PUSH CHOPPER 900ML" â†’ "NEO PUSH CHOPPER" + "900ML"
  "NEO PUSH CHOPPER COMBO CONTAINER" â†’ "NEO PUSH CHOPPER COMBO CONTAINER" (no split â€” standalone)

â”€â”€â”€ STEP B: FIND THE CATEGORY (broadest grouping) â”€â”€â”€

Look at ALL products. What is the BROADEST group they belong to?
  â€¢ "Handy Chopper", "Neo Push Chopper" â†’ ALL are "Chopper" category
  â€¢ "Manual Juicer", "Electric Juicer" â†’ ALL are "Juicer" category
  â€¢ If the catalogue has Choppers, Juicers, Lunch Boxes â†’ Categories = ["Chopper", "Juicer", "Lunch Box"]

The category is the TOP-LEVEL group. Multiple product lines share the same category.
â†’ This becomes is_category=true, type="select"

â”€â”€â”€ STEP C: FIND THE PRODUCT NAME (product line identity) â”€â”€â”€

Within a category, group products by their COMMON NAME:
  "Handy Chopper 750ML" + "Handy Chopper 500ML" + "Handy Chopper 900ML"
  â†’ Common name = "Handy Chopper" (this appears 3 times with different suffixes)

  "Neo Push Chopper 900ML" + "Neo Push Chopper 500ML"
  â†’ Common name = "Neo Push Chopper"

  "Neo Push Chopper Combo Container" â†’ standalone, no split

âš ï¸ CRITICAL: The Product Name is the COMMON/SHARED part of the heading.
  â€¢ "Handy Chopper" is ONE product (not 3 products!)
  â€¢ "Neo Push Chopper" is ONE product (not 2 products!)
  â€¢ The varying suffix (750ML, 500ML) is NOT part of the product name

â†’ Product Name becomes is_title=true AND is_unique=true

â”€â”€â”€ STEP D: FIND COMBO/VARIATION FIELDS â”€â”€â”€

Now look at what VARIES within the same product name:
  "Handy Chopper" â†’ has 500ML, 750ML, 900ML â†’ "Capacity" is a COMBO field
  "Neo Push Chopper" â†’ has 500ML, 900ML â†’ "Capacity" is a COMBO field

Also check traditional combos:
  â€¢ If a product shows sizes: 4", 6", 8" â†’ Size is a COMBO field
  â€¢ If a product shows finishes: Black, Gold, Silver â†’ Finish is a COMBO field
  â€¢ If a product shows colors: Red, Blue, Green â†’ Color is a COMBO field

âš ï¸ COMBO means: ONE product has MULTIPLE variant options. Not every product needs a combo value.
  "Neo Push Chopper Combo Container" â†’ has NO capacity variant â†’ combo field will be empty for this product. That's fine!

â†’ Combo fields become is_combo=true, type="multiselect"
â†’ DO NOT collect all variant values. Provide exactly 3 examples in the options array to demonstrate the data type.

â”€â”€â”€ STEP E: FIND REQUIRED PER-PRODUCT FIELDS â”€â”€â”€

What data appears per EACH product variant (per item block)?
  â€¢ Price fields: "WITH GST: 168/-", "PRICE: 142/-", "MRP: 451/-" â†’ REQUIRED
  â€¢ Tax: "GST: 18%" â†’ REQUIRED
  â€¢ Codes: "HSN CODE: 392410" â†’ REQUIRED
  â€¢ Pack info: "MASTER PACK: 144 NOS.", "INNER PACK: 12 NOS." â†’ REQUIRED

â†’ These become is_required=true fields
â†’ If these field VALUES DIFFER per variant (e.g., 750ML has price 168, 500ML has price 146), also set is_variation_field=true
â†’ is_variation_field=true means: this field appears in the variation pricing table, each variant gets its own value

â”€â”€â”€ STEP F: FIND DESCRIPTIVE/OPTIONAL FIELDS â”€â”€â”€

Any additional text like:
  â€¢ "CHOPPED, GREVY, CHUTNEY, PASTE" (usage/tagline)
  â€¢ "SHARPNESS STAINLESS STEEL 5 BLADES WITH CUTTING" (features/specs)

â†’ These become non-flagged columns (no special flags, is_required=false)

â”â”â”â”â”â”â”â”â”â”â”â”â”â”â”â”â”â”â”â”â”â”â”â”â”â”â”â”â”â”â”â”â”â”â”â”â”â”â”â”â”
COLUMN TYPE RULES:
â”â”â”â”â”â”â”â”â”â”â”â”â”â”â”â”â”â”â”â”â”â”â”â”â”â”â”â”â”â”â”â”â”â”â”â”â”â”â”â”â”

â€¢ Category â†’ ALWAYS type="select" with exactly 3 EXAMPLES in the options array. DO NOT EXTRACT ALL CATEGORIES.
â€¢ Product Name â†’ type="text" (is_title=true, is_unique=true)
â€¢ Combo fields â†’ type="multiselect", is_combo=true, with exactly 3 EXAMPLES in the options array.
â€¢ Fields with limited distinct values (< 30) â†’ type="select" with exactly 3 EXAMPLES in the options array.
â€¢ Free-text â†’ type="text" (short) or type="textarea" (long)
â€¢ Numeric (prices, percentages, quantities) â†’ type="number"
â€¢ Yes/No â†’ type="boolean"

â”â”â”â”â”â”â”â”â”â”â”â”â”â”â”â”â”â”â”â”â”â”â”â”â”â”â”â”â”â”â”â”â”â”â”â”â”â”â”â”â”
FLAG ASSIGNMENT RULES:
â”â”â”â”â”â”â”â”â”â”â”â”â”â”â”â”â”â”â”â”â”â”â”â”â”â”â”â”â”â”â”â”â”â”â”â”â”â”â”â”â”

â€¢ is_category=true â†’ EXACTLY ONE column (the broadest group â€” Category Linked)
â€¢ is_title=true â†’ EXACTLY ONE column (Quote/Lead Title â€” the product line name)
â€¢ is_unique=true â†’ EXACTLY ONE column (Unique Identifier â€” usually same as title for named products)
â€¢ is_combo=true â†’ Only for multiselect fields creating the Variation Matrix
â€¢ is_variation_field=true â†’ Per-Variation Field. Fields whose VALUE changes per variant. NEVER on combo columns.
â€¢ is_required=true â†’ Required Field. Fields every product MUST have
â€¢ show_in_ai=true â†’ Fields useful for WhatsApp chatbot matching

âš ï¸ is_title and is_unique CAN be on the same column
âš ï¸ is_combo and is_variation_field are MUTUALLY EXCLUSIVE (a column cannot be both)

â”â”â”â”â”â”â”â”â”â”â”â”â”â”â”â”â”â”â”â”â”â”â”â”â”â”â”â”â”â”â”â”â”â”â”â”â”â”â”â”â”
SORTING ORDER:
â”â”â”â”â”â”â”â”â”â”â”â”â”â”â”â”â”â”â”â”â”â”â”â”â”â”â”â”â”â”â”â”â”â”â”â”â”â”â”â”â”

1. Category (is_category)
2. Product Name (is_title)
3. Combo/Variation fields (is_combo)
4. Key specs/material
5. Required per-product fields (pricing, codes)
6. Optional descriptive fields

â”â”â”â”â”â”â”â”â”â”â”â”â”â”â”â”â”â”â”â”â”â”â”â”â”â”â”â”â”â”â”â”â”â”â”â”â”â”â”â”â”
EXAMPLE â€” Homeware Catalogue (Choppers, Juicers, etc.):
â”â”â”â”â”â”â”â”â”â”â”â”â”â”â”â”â”â”â”â”â”â”â”â”â”â”â”â”â”â”â”â”â”â”â”â”â”â”â”â”â”

Raw items seen:
  "Handy Chopper 750ML", "Handy Chopper 500ML", "Handy Chopper 900ML",
  "Neo Push Chopper 900ML", "Neo Push Chopper 500ML",
  "Neo Push Chopper Combo Container",
  "Manual Juicer", "Coffee Mug 2 Pcs Set"

Pattern analysis:
  â†’ Categories: "Chopper", "Juicer", "Mug"
  â†’ Product Names: "Handy Chopper", "Neo Push Chopper", "Neo Push Chopper Combo Container", "Manual Juicer", "Coffee Mug 2 Pcs Set"
  â†’ Capacity COMBO: "500ML", "750ML", "900ML" (varies within Handy Chopper and Neo Push Chopper)
  â†’ Some products have NO capacity variant (Combo Container, Juicer) â€” that's perfectly fine

Result:
{
  "columns": [
    {"name": "Category", "type": "select", "is_unique": false, "is_required": true, "is_category": true, "is_title": false, "is_combo": false, "is_variation_field": false, "options": ["Chopper", "Juicer", "Mug"], "show_in_ai": true, "sort_order": 1},
    {"name": "Product Name", "type": "text", "is_unique": true, "is_required": true, "is_category": false, "is_title": true, "is_combo": false, "is_variation_field": false, "options": [], "show_in_ai": true, "sort_order": 2},
    {"name": "Capacity", "type": "multiselect", "is_unique": false, "is_required": false, "is_category": false, "is_title": false, "is_combo": true, "is_variation_field": false, "options": ["500ML", "750ML", "900ML", "900 & 500ML"], "show_in_ai": true, "sort_order": 3},
    {"name": "Sale Price (with GST)", "type": "number", "is_unique": false, "is_required": true, "is_category": false, "is_title": false, "is_combo": false, "is_variation_field": true, "options": [], "show_in_ai": true, "sort_order": 4},
    {"name": "Base Price (without GST)", "type": "number", "is_unique": false, "is_required": true, "is_category": false, "is_title": false, "is_combo": false, "is_variation_field": true, "options": [], "show_in_ai": true, "sort_order": 5},
    {"name": "GST Percentage", "type": "number", "is_unique": false, "is_required": true, "is_category": false, "is_title": false, "is_combo": false, "is_variation_field": true, "options": [], "show_in_ai": true, "sort_order": 6},
    {"name": "HSN Code", "type": "text", "is_unique": false, "is_required": true, "is_category": false, "is_title": false, "is_combo": false, "is_variation_field": true, "options": [], "show_in_ai": false, "sort_order": 7},
    {"name": "MRP", "type": "number", "is_unique": false, "is_required": true, "is_category": false, "is_title": false, "is_combo": false, "is_variation_field": true, "options": [], "show_in_ai": true, "sort_order": 8},
    {"name": "Master Pack Quantity", "type": "number", "is_unique": false, "is_required": true, "is_category": false, "is_title": false, "is_combo": false, "is_variation_field": true, "options": [], "show_in_ai": false, "sort_order": 10},
    {"name": "Inner Pack Quantity", "type": "number", "is_unique": false, "is_required": true, "is_category": false, "is_title": false, "is_combo": false, "is_variation_field": true, "options": [], "show_in_ai": false, "sort_order": 11}
  ]
}

â”â”â”â”â”â”â”â”â”â”â”â”â”â”â”â”â”â”â”â”â”â”â”â”â”â”â”â”â”â”â”â”â”â”â”â”â”â”â”â”â”
EXAMPLE â€” Hardware/Fitting Catalogue (handles, hinges by Code No.):
â”â”â”â”â”â”â”â”â”â”â”â”â”â”â”â”â”â”â”â”â”â”â”â”â”â”â”â”â”â”â”â”â”â”â”â”â”â”â”â”â”

Result:
{
  "columns": [
    {"name": "Category", "type": "select", "is_unique": false, "is_required": true, "is_category": true, "is_title": true, "is_combo": false, "is_variation_field": false, "options": ["Conceal Handle", "Door Handle"], "show_in_ai": true, "sort_order": 1},
    {"name": "Code No.", "type": "text", "is_unique": true, "is_required": true, "is_category": false, "is_title": false, "is_combo": false, "is_variation_field": false, "options": [], "show_in_ai": true, "sort_order": 2},
    {"name": "Size", "type": "multiselect", "is_unique": false, "is_required": false, "is_category": false, "is_title": false, "is_combo": true, "is_variation_field": false, "options": ["300mm", "450mm", "600mm"], "show_in_ai": true, "sort_order": 3},
    {"name": "Finish", "type": "multiselect", "is_unique": false, "is_required": false, "is_category": false, "is_title": false, "is_combo": true, "is_variation_field": false, "options": ["Black", "Rose Gold", "SS"], "show_in_ai": true, "sort_order": 4}
  ]
}

â”â”â”â”â”â”â”â”â”â”â”â”â”â”â”â”â”â”â”â”â”â”â”â”â”â”â”â”â”â”â”â”â”â”â”â”â”â”â”â”â”
BUSINESS DETAILS EXTRACTION:
â”â”â”â”â”â”â”â”â”â”â”â”â”â”â”â”â”â”â”â”â”â”â”â”â”â”â”â”â”â”â”â”â”â”â”â”â”â”â”â”â”

While analyzing, ALSO look for BUSINESS INFORMATION:
â€¢ Company/Brand Name, Contact, Email, Website
â€¢ Address, GST Number, Social media
If found, include in "business_details". If not, set to null.

â”â”â”â”â”â”â”â”â”â”â”â”â”â”â”â”â”â”â”â”â”â”â”â”â”â”â”â”â”â”â”â”â”â”â”â”â”â”â”â”â”
OUTPUT FORMAT (STRICT JSON â€” NO MARKDOWN):
â”â”â”â”â”â”â”â”â”â”â”â”â”â”â”â”â”â”â”â”â”â”â”â”â”â”â”â”â”â”â”â”â”â”â”â”â”â”â”â”â”

{
  "columns": [...],
  "source_summary": "description of catalogue",
  "confidence": 85,
  "business_details": "Company: XYZ\nPhone: +91-xxx\nWebsite: www.xyz.com" or null
}

â”â”â”â”â”â”â”â”â”â”â”â”â”â”â”â”â”â”â”â”â”â”â”â”â”â”â”â”â”â”â”â”â”â”â”â”â”â”â”â”â”
FINAL CHECKLIST â€” VERIFY BEFORE OUTPUT:
â”â”â”â”â”â”â”â”â”â”â”â”â”â”â”â”â”â”â”â”â”â”â”â”â”â”â”â”â”â”â”â”â”â”â”â”â”â”â”â”â”

âœ— Did you put "750ML" or any variant suffix INTO Product Name? â†’ REMOVE it! Product Name = common part only!
âœ— Did you create separate products for "X 500ML" and "X 750ML" instead of ONE product "X" with Capacity combo? â†’ FIX!
âœ— Did you add columns for data NOT visible in the catalogue? â†’ REMOVE!
âœ— Do you have more than ONE is_category? â†’ FIX: only ONE allowed.
âœ— Do you have more than ONE is_title? â†’ FIX: only ONE allowed.
âœ— Do you have more than ONE is_unique? â†’ FIX: only ONE allowed.
âœ— Is is_category column type NOT "select"? â†’ FIX: must be "select".
âœ— Did you miss a capacity/size/finish/color that varies within products? â†’ ADD it as is_combo=true multiselect.
âœ— Does a product have NO combo value? â†’ That's OK! Not every product needs combo variants.
âœ— Did you create "Product Name" when no descriptive names exist? â†’ REMOVE, put is_title on Category.
PROMPT;
    }

    private function getProductExtractionPrompt(array $columns): string
    {
        $columnList = "";
        foreach ($columns as $col) {
            $name = $col['name'];
            $type = $col['type'] ?? 'text';
            $flags = [];
            if ($col['is_category'] ?? false) $flags[] = 'CATEGORY';
            if ($col['is_unique'] ?? false) $flags[] = 'UNIQUE IDENTIFIER';
            if ($col['is_title'] ?? false) $flags[] = 'DISPLAY TITLE';
            if ($col['is_combo'] ?? false) $flags[] = 'COMBO/VARIATION';
            $flagStr = count($flags) > 0 ? ' [' . implode(', ', $flags) . ']' : '';
            $optStr = '';
            if (!empty($col['options'])) {
                $optStr = ' Options: ' . implode(', ', array_slice($col['options'], 0, 20));
            }
            $columnList .= "  - \"{$name}\" (type: {$type}){$flagStr}{$optStr}\n";
        }

        return <<<PROMPT
You are a world-class Product Data Extraction Specialist with 20+ years of experience digitizing product catalogues into structured databases.

YOUR MISSION: Extract EVERY SINGLE individual product/model from the provided catalogue and map each to the defined column structure.

â”â”â”â”â”â”â”â”â”â”â”â”â”â”â”â”â”â”â”â”â”â”â”â”â”â”â”â”â”â”â”â”â”â”â”â”â”â”â”â”â”
DEFINED COLUMNS (extract data for these exact fields):
â”â”â”â”â”â”â”â”â”â”â”â”â”â”â”â”â”â”â”â”â”â”â”â”â”â”â”â”â”â”â”â”â”â”â”â”â”â”â”â”â”

{$columnList}

â”â”â”â”â”â”â”â”â”â”â”â”â”â”â”â”â”â”â”â”â”â”â”â”â”â”â”â”â”â”â”â”â”â”â”â”â”â”â”â”â”
CRITICAL EXTRACTION RULES:
â”â”â”â”â”â”â”â”â”â”â”â”â”â”â”â”â”â”â”â”â”â”â”â”â”â”â”â”â”â”â”â”â”â”â”â”â”â”â”â”â”

1. âš ï¸ EACH MODEL NUMBER / SKU = ONE SEPARATE PRODUCT ROW
   - Do NOT group multiple models into one row
   - If a catalogue page shows 10 different models, you must create 10 separate product entries

2. âš ï¸ CATEGORY COLUMN [CATEGORY flag] â€” VERY IMPORTANT:
   - The Category value is the PRODUCT GROUP/FAMILY name only
   - NEVER append model numbers, code numbers, or unique identifiers to the category
   - âŒ WRONG: Category = "A X" (group name + code combined)
   - âœ… CORRECT: Category = "A" (group name only, code goes in its own column)
   - Category should have FAR FEWER unique values than the number of products

3. âš ï¸ PRODUCT NAME / TITLE COLUMN [TITLE flag]:
   - Product Name must NEVER contain the code/model number
   - âŒ WRONG: Product Name = "A X" (group + code combined)
   - âœ… CORRECT: Product Name = "A" (group name only)

4. ⚠️ For COMBO/VARIATION fields (e.g. Size, Color, Finish): 
   - If the catalogue shows a table with MULTIPLE sizes or variations for a SINGLE model/code, you MUST extract ALL of them.
   - Do NOT just pick the first value. Combine ALL available options for that model using " | " (pipe with spaces).
   - Example: If a table lists sizes 200mm, 300mm, 450mm for one model, output "200mm | 300mm | 450mm".

5. For PRICES: use numeric values only (no currency symbols)

6. If a field value is not visible, use empty string ""

7. ⚠️ For the "Image" or "Product Image" column: ALWAYS output an empty string "". DO NOT attempt to extract image URLs or descriptions.

8. Extract ALL products from ALL visible pages

â”â”â”â”â”â”â”â”â”â”â”â”â”â”â”â”â”â”â”â”â”â”â”â”â”â”â”â”â”â”â”â”â”â”â”â”â”â”â”â”â”
EXAMPLE â€” Using abstract names A, B and codes X1, X2, Y1:
â”â”â”â”â”â”â”â”â”â”â”â”â”â”â”â”â”â”â”â”â”â”â”â”â”â”â”â”â”â”â”â”â”â”â”â”â”â”â”â”â”

âœ… CORRECT â€” each model is its own row, names are group-only, code is separate:
{"products": [
  {"[CATEGORY col]": "A", "[TITLE col]": "A", "[UNIQUE col]": "X1", ...},
  {"[CATEGORY col]": "A", "[TITLE col]": "A", "[UNIQUE col]": "X2", ...},
  {"[CATEGORY col]": "B", "[TITLE col]": "B", "[UNIQUE col]": "Y1", ...}
]}

â”â”â”â”â”â”â”â”â”â”â”â”â”â”â”â”â”â”â”â”â”â”â”â”â”â”â”â”â”â”â”â”â”â”â”â”â”â”â”â”â”
OUTPUT FORMAT (STRICT JSON â€” NO MARKDOWN):
â”â”â”â”â”â”â”â”â”â”â”â”â”â”â”â”â”â”â”â”â”â”â”â”â”â”â”â”â”â”â”â”â”â”â”â”â”â”â”â”â”

{
  "products": [
    {
      "Column Name 1": "value",
      "Column Name 2": "value"
    }
  ]
}

â”â”â”â”â”â”â”â”â”â”â”â”â”â”â”â”â”â”â”â”â”â”â”â”â”â”â”â”â”â”â”â”â”â”â”â”â”â”â”â”â”
FINAL CHECKLIST:
â”â”â”â”â”â”â”â”â”â”â”â”â”â”â”â”â”â”â”â”â”â”â”â”â”â”â”â”â”â”â”â”â”â”â”â”â”â”â”â”â”
- Output ONLY valid JSON. No markdown. No explanation text.
- Use the EXACT column names as defined above (case-sensitive)
- Extract up to 500 individual products/models
- Prices should be numbers only, no â‚¹ or Rs or $ symbols
- âš ï¸ Did you create one row per MODEL? Not one row per CATEGORY!
- âš ï¸ CATEGORY must be GROUP NAME only â€” NEVER append code/model numbers!
- âš ï¸ PRODUCT NAME must be GROUP NAME only â€” NEVER append code/model numbers!
PROMPT;
    }

    public function getDefaultColumnAnalysisPromptPublic(): string { return $this->getDefaultColumnAnalysisPrompt(); }
    public function getDefaultProductExtractionPromptPublic(): string
    {
        return $this->getProductExtractionPrompt([
            ['name' => 'Category', 'type' => 'select', 'is_category' => true, 'is_unique' => false, 'is_title' => true, 'is_combo' => false, 'options' => ['Sample A']],
            ['name' => 'Code No.', 'type' => 'text', 'is_category' => false, 'is_unique' => true, 'is_title' => false, 'is_combo' => false, 'options' => []],
        ]);
    }

    public function generateColumnsExcel(array $columns): Spreadsheet
    {
        $spreadsheet = new Spreadsheet();
        $sheet = $spreadsheet->getActiveSheet()->setTitle('Catalogue Columns');
        $headers = ['Name', 'Type', 'Options (comma-separated)', 'Is Required', 'Is Unique', 'Is Category', 'Is Title', 'Is Combo', 'Is Variation Field', 'Show In AI', 'Sort Order'];
        foreach ($headers as $i => $h) {
            $l = Coordinate::stringFromColumnIndex($i + 1);
            $sheet->setCellValue($l . '1', $h);
            $sheet->getColumnDimension($l)->setAutoSize(true);
        }
        $ll = Coordinate::stringFromColumnIndex(count($headers));
        $sheet->getStyle("A1:{$ll}1")->applyFromArray([
            'font' => ['bold' => true, 'color' => ['rgb' => 'FFFFFF']],
            'fill' => ['fillType' => Fill::FILL_SOLID, 'startColor' => ['rgb' => '7C3AED']],
        ]);
        foreach ($columns as $i => $col) {
            $r = $i + 2;
            $sheet->setCellValue("A{$r}", $col['name']);
            $sheet->setCellValue("B{$r}", $col['type']);
            $sheet->setCellValue("C{$r}", implode(', ', $col['options'] ?? []));
            $sheet->setCellValue("D{$r}", ($col['is_required'] ?? false) ? 'Yes' : 'No');
            $sheet->setCellValue("E{$r}", ($col['is_unique'] ?? false) ? 'Yes' : 'No');
            $sheet->setCellValue("F{$r}", ($col['is_category'] ?? false) ? 'Yes' : 'No');
            $sheet->setCellValue("G{$r}", ($col['is_title'] ?? false) ? 'Yes' : 'No');
            $sheet->setCellValue("H{$r}", ($col['is_combo'] ?? false) ? 'Yes' : 'No');
            $sheet->setCellValue("I{$r}", ($col['is_variation_field'] ?? false) ? 'Yes' : 'No');
            $sheet->setCellValue("J{$r}", ($col['show_in_ai'] ?? true) ? 'Yes' : 'No');
            $sheet->setCellValue("K{$r}", $col['sort_order'] ?? ($i + 1));
        }
        return $spreadsheet;
    }

    public function generateProductsExcel(array $products, array $columns): Spreadsheet
    {
        $spreadsheet = new Spreadsheet();
        $sheet = $spreadsheet->getActiveSheet()->setTitle('Products');
        $ci = 1; $hm = [];
        foreach ($columns as $col) {
            $l = Coordinate::stringFromColumnIndex($ci);
            $sheet->setCellValue($l . '1', $col['name'] . (($col['is_combo'] ?? false) ? ' (combo)' : ''));
            $sheet->getColumnDimension($l)->setAutoSize(true);
            $hm[$ci] = $col['name'];
            $ci++;
        }
        $ll = Coordinate::stringFromColumnIndex(max($ci - 1, 1));
        $sheet->getStyle("A1:{$ll}1")->applyFromArray([
            'font' => ['bold' => true, 'color' => ['rgb' => 'FFFFFF']],
            'fill' => ['fillType' => Fill::FILL_SOLID, 'startColor' => ['rgb' => '4F46E5']],
        ]);
        foreach ($products as $ri => $p) {
            $r = $ri + 2;
            foreach ($hm as $c => $cn) {
                $sheet->setCellValue(Coordinate::stringFromColumnIndex($c) . $r, $p[$cn] ?? '');
            }
        }
        $sheet->freezePane('A2');
        return $spreadsheet;
    }
}
