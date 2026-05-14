<?php

namespace App\Services;

use Illuminate\Support\Facades\Log;

class TemplateFingerprintService
{
    /**
     * Extract a structural fingerprint from an uploaded ZIP template directory.
     * 
     * The fingerprint captures the template's "DNA":
     * - How many product image slots it has
     * - Each slot's normalized position, size, and type (transparent/full)
     * - Canvas dimensions and aspect ratio
     * - Count of decorative shapes, text layers
     * - Background layer z_index for color application
     *
     * @param string $templateDir Absolute path to the extracted ZIP directory (e.g., uploads/template/POST20260411173135)
     * @return array|null The fingerprint array, or null if extraction fails
     */
    public function extractFromZip(string $templateDir): ?array
    {
        // Find the JSON config file
        $jsonPath = $this->findTemplateJson($templateDir);
        if (!$jsonPath) {
            Log::warning("FingerprintService: No JSON found in {$templateDir}");
            return null;
        }

        $config = json_decode(file_get_contents($jsonPath), true);
        if (!$config || !isset($config['layers'])) {
            Log::warning("FingerprintService: Invalid JSON in {$jsonPath}");
            return null;
        }

        // Canvas dimensions
        $canvasW = $config['info']['width'] ?? 1080;
        $canvasH = $config['info']['height'] ?? 1080;

        // Analyze all layers
        $imgSlots = [];
        $shapeCount = 0;
        $textCount = 0;
        $totalLayers = count($config['layers']);
        $bgZIndex = 999;
        $allZIndexes = [];

        foreach ($config['layers'] as $layer) {
            $type = $layer['type'] ?? '';
            $name = $layer['name'] ?? '';
            $zIndex = $layer['z_index'] ?? 999;

            if ($type === 'text') {
                $textCount++;
                continue;
            }

            if ($type === 'image') {
                // Check if this is a product image slot
                if ($this->isProductSlot($layer)) {
                    $imgSlots[] = [
                        'x' => round(($layer['x'] ?? 0) / $canvasW, 3),
                        'y' => round(($layer['y'] ?? 0) / $canvasH, 3),
                        'w' => round(($layer['w'] ?? 0) / $canvasW, 3),
                        'h' => round(($layer['h'] ?? 0) / $canvasH, 3),
                        'type' => $this->resolveImgType($layer, $templateDir),
                    ];
                } elseif ($this->isBackground($layer, $canvasW, $canvasH)) {
                    // Background layer — record its z_index
                    $bgZIndex = min($bgZIndex, $zIndex);
                } else {
                    // Decorative shape/element
                    $shapeCount++;
                }
            }
        }

        // If no explicit background found, use the lowest z_index
        if ($bgZIndex >= 999) {
            $bgZIndex = 1;
        }

        return [
            'img_count' => count($imgSlots),
            'img_slots' => $imgSlots,
            'canvas_w' => $canvasW,
            'canvas_h' => $canvasH,
            'canvas_ratio' => $this->getCanvasRatio($canvasW, $canvasH),
            'shape_count' => $shapeCount,
            'text_count' => $textCount,
            'total_layers' => $totalLayers,
            'bg_z_index' => $bgZIndex,
        ];
    }

    /**
     * Determine if a layer is a product image slot.
     * 
     * Priority:
     * 1. Explicit "is_slot": true tag (most reliable)
     * 2. Fallback: name matches pattern like "image1", "image-1", "image_2"
     */
    private function isProductSlot(array $layer): bool
    {
        // Explicit tag — most reliable
        if (isset($layer['is_slot']) && $layer['is_slot'] === true) {
            return true;
        }

        // Fallback: name-based detection for legacy ZIPs without tags
        $name = strtolower($layer['name'] ?? '');
        return (bool) preg_match('/^image[-_]?\d+$/', $name);
    }

    /**
     * Determine if a layer is the background.
     * 
     * Priority:
     * 1. Explicit "is_background": true tag
     * 2. Heuristic: covers >=90% of canvas area at position near (0,0)
     */
    private function isBackground(array $layer, int $canvasW, int $canvasH): bool
    {
        // Explicit tag
        if (isset($layer['is_background']) && $layer['is_background'] === true) {
            return true;
        }

        // Heuristic: Full canvas coverage with low position values
        $w = $layer['w'] ?? 0;
        $h = $layer['h'] ?? 0;
        $x = abs($layer['x'] ?? 0);
        $y = abs($layer['y'] ?? 0);

        $coversWidth = $w >= ($canvasW * 0.9);
        $coversHeight = $h >= ($canvasH * 0.9);
        $nearOrigin = $x <= ($canvasW * 0.1) && $y <= ($canvasH * 0.1);

        return $coversWidth && $coversHeight && $nearOrigin;
    }

    /**
     * Resolve the image type (transparent cutout vs full rectangular image).
     * 
     * Priority:
     * 1. Explicit "img_type" tag in JSON
     * 2. Fallback: Check PNG alpha channel via GD
     * 3. Default: "full" if cannot determine
     */
    private function resolveImgType(array $layer, string $templateDir): string
    {
        // Explicit tag — most reliable
        if (isset($layer['img_type'])) {
            return $layer['img_type']; // "transparent" or "full"
        }

        // Fallback: try to detect from actual image file
        $src = $layer['src'] ?? '';
        if ($src) {
            $imgPath = $this->resolveImagePath($src, $templateDir);
            if ($imgPath && file_exists($imgPath)) {
                return $this->detectTransparencyFromFile($imgPath);
            }
        }

        return 'full'; // Default
    }

    /**
     * Resolve relative image src path to absolute filesystem path.
     */
    private function resolveImagePath(string $src, string $templateDir): ?string
    {
        // src is like "../skins/Frame_Squre_2/image.png"
        // templateDir might be: public/uploads/template/POST20260411173135
        // JSON is in: templateDir/json/Custom_post_1.json
        // So "../skins" from json/ = templateDir/skins

        // Strip the "../" prefix
        $cleanSrc = preg_replace('/^\.\.\//', '', $src);
        $path = $templateDir . '/' . $cleanSrc;

        // Also check with subdirectory structure
        if (!file_exists($path)) {
            // Try finding in subdirectories
            $subDirs = glob($templateDir . '/*', GLOB_ONLYDIR);
            foreach ($subDirs as $subDir) {
                $altPath = $subDir . '/' . $cleanSrc;
                if (file_exists($altPath)) {
                    return $altPath;
                }
            }
        }

        return file_exists($path) ? $path : null;
    }

    /**
     * Detect if a PNG image has significant transparency (cutout product).
     * Checks corner pixels — if corners are transparent, it's likely a cutout.
     */
    private function detectTransparencyFromFile(string $filePath): string
    {
        $ext = strtolower(pathinfo($filePath, PATHINFO_EXTENSION));

        // JPG/JPEG cannot have transparency
        if (in_array($ext, ['jpg', 'jpeg'])) {
            return 'full';
        }

        if ($ext !== 'png') {
            return 'full'; // WebP/other — default to full
        }

        try {
            $img = @imagecreatefrompng($filePath);
            if (!$img) {
                return 'full';
            }

            $w = imagesx($img);
            $h = imagesy($img);

            // Check 4 corners for transparency
            $corners = [
                [0, 0],
                [$w - 1, 0],
                [0, $h - 1],
                [$w - 1, $h - 1],
            ];

            $transparentCorners = 0;
            foreach ($corners as [$cx, $cy]) {
                $color = imagecolorat($img, $cx, $cy);
                $alpha = ($color >> 24) & 0x7F;
                if ($alpha > 64) { // More than 50% transparent
                    $transparentCorners++;
                }
            }

            imagedestroy($img);

            // If 3+ corners are transparent, it's a cutout
            return ($transparentCorners >= 3) ? 'transparent' : 'full';
        } catch (\Exception $e) {
            Log::warning("FingerprintService: GD transparency check failed for {$filePath}: " . $e->getMessage());
            return 'full';
        }
    }

    /**
     * Find the first JSON template file in the template directory.
     * Handles both flat structure (json/file.json) and nested structure (SubDir/json/file.json).
     */
    private function findTemplateJson(string $templateDir): ?string
    {
        // Direct: templateDir/json/*.json
        $directJsons = glob($templateDir . '/json/*.json');
        if (!empty($directJsons)) {
            sort($directJsons);
            return $directJsons[0];
        }

        // Nested: templateDir/SubDir/json/*.json
        $subDirs = glob($templateDir . '/*', GLOB_ONLYDIR);
        foreach ($subDirs as $subDir) {
            $nestedJsons = glob($subDir . '/json/*.json');
            if (!empty($nestedJsons)) {
                sort($nestedJsons);
                return $nestedJsons[0];
            }
        }

        return null;
    }

    /**
     * Get human-readable canvas ratio.
     */
    private function getCanvasRatio(int $w, int $h): string
    {
        $ratio = $w / max($h, 1);
        if (abs($ratio - 1.0) < 0.1) return 'square';
        if ($ratio < 1.0) return 'portrait';
        return 'landscape';
    }
}
