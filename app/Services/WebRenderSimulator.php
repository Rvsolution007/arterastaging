<?php

namespace App\Services;

/**
 * Simulates the Web Editor's export calculations (template_builder.js → exportArteraSchema).
 * For each layer, computes the FINAL values that would be exported at a given render_version.
 *
 * THIS IS A PURE MATH ENGINE — no rendering, no canvas, no browser.
 */
class WebRenderSimulator
{
    /**
     * Compute the web-side export values for all layers in a template JSON.
     *
     * @param  array  $templateJson   The raw template JSON (schema_json or legacy_json)
     * @param  int    $targetVersion  The render_version to simulate
     * @return array  Per-layer computed values: ['layerName' => ['canvasX'=>..., 'canvasW'=>...]]
     */
    public function compute(array $templateJson, int $targetVersion): array
    {
        $results = [];
        $layers = $templateJson['layers'] ?? $templateJson['objects'] ?? [];

        foreach ($layers as $layer) {
            $name = $layer['name'] ?? $layer['id'] ?? 'unknown';
            $type = $layer['type'] ?? 'unknown';

            // Raw values from JSON
            $x = floatval($layer['x'] ?? $layer['left'] ?? 0);
            $y = floatval($layer['y'] ?? $layer['top'] ?? 0);
            $w = floatval($layer['w'] ?? $layer['width'] ?? 0);
            $h = floatval($layer['h'] ?? $layer['height'] ?? 0);
            $scaleX = floatval($layer['scaleX'] ?? 1);
            $scaleY = floatval($layer['scaleY'] ?? 1);
            $fontSize = floatval($layer['fontSize'] ?? $layer['font_size'] ?? $layer['size'] ?? 0);

            // === BAKING FORMULAS (matches exportArteraSchema logic) ===
            if ($targetVersion >= 4) {
                // V4+: Dimensions are baked (width * scaleX), coordinates are absolute top-left
                $bakedW = round($w * $scaleX);
                $bakedH = round($h * $scaleY);
                $bakedX = $x; // Already top-left from setCoords() in web editor
                $bakedY = $y;
                $bakedFontSize = $fontSize > 0 ? round($fontSize * abs($scaleY)) : null;
                $finalScaleX = 1;
                $finalScaleY = 1;
            } else {
                // V1-V3: Raw values preserved (no baking)
                $bakedW = $w;
                $bakedH = $h;
                $bakedX = $x;
                $bakedY = $y;
                $bakedFontSize = $fontSize > 0 ? $fontSize : null;
                $finalScaleX = $scaleX;
                $finalScaleY = $scaleY;
            }

            // V4+ text Y offset adjustment
            $yOffset = 0;
            if ($type === 'text' && $targetVersion >= 4 && $bakedFontSize > 0) {
                $yOffset = $bakedFontSize * 0.12; // Default Y offset factor
            }

            $results[$name] = [
                'canvasX' => round($bakedX, 2),
                'canvasY' => round($bakedY + $yOffset, 2),
                'canvasW' => round($bakedW, 2),
                'canvasH' => round($bakedH, 2),
                'computedFontSize' => $bakedFontSize,
                'scaleX' => $finalScaleX,
                'scaleY' => $finalScaleY,
                'type' => $type,
            ];
        }

        return $results;
    }
}
