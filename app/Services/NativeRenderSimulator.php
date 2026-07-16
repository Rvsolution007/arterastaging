<?php

namespace App\Services;

/**
 * Simulates the Native Editor's rendering calculations (editor_canvas_widget.dart → build()).
 * For each layer, computes the FINAL pixel values that Flutter would calculate.
 *
 * THIS IS A PURE MATH ENGINE — no Flutter, no widgets, no rendering.
 */
class NativeRenderSimulator
{
    /**
     * Compute native-side render values for all layers.
     *
     * @param  array  $templateJson   The template JSON
     * @param  int    $targetVersion  The render_version to simulate
     * @param  float  $deviceWidth    The device screen width (default 360)
     * @return array  Per-layer computed values
     */
    public function compute(array $templateJson, int $targetVersion, float $deviceWidth = 360): array
    {
        $results = [];

        // Design dimensions
        $info = $templateJson['info'] ?? [];
        if (is_string($info)) $info = json_decode($info, true) ?? [];
        $designW = floatval($info['width'] ?? $templateJson['width'] ?? 1080);
        $designH = floatval($info['height'] ?? $templateJson['height'] ?? 1080);
        $docPPI = floatval($info['ppi'] ?? 72);
        $ppiScale = $docPPI / 72.0;

        // Scale factor
        $scale = $deviceWidth / $designW;

        $layers = $templateJson['layers'] ?? $templateJson['objects'] ?? [];

        foreach ($layers as $layer) {
            $name = $layer['name'] ?? $layer['id'] ?? 'unknown';
            $type = $layer['type'] ?? 'unknown';

            $x = floatval($layer['x'] ?? 0);
            $y = floatval($layer['y'] ?? 0);
            $w = floatval($layer['w'] ?? $layer['width'] ?? 0);
            $h = floatval($layer['h'] ?? $layer['height'] ?? 0);
            $layerScaleX = floatval($layer['scaleX'] ?? 1);
            $layerScaleY = floatval($layer['scaleY'] ?? 1);
            $rawFontSize = floatval($layer['fontSize'] ?? $layer['font_size'] ?? $layer['size'] ?? 16);

            // === NATIVE RENDER FORMULAS ===
            // (matches editor_canvas_widget.dart build() + interactive_layer.dart)

            // Position
            $finalX = $x * $scale;
            $finalY = ($type === 'text' && $targetVersion >= 5) ? ($y - 5.0) * $scale : $y * $scale;

            // Dimensions (interactive_layer.dart Lines 77-84)
            $finalW = $w * $layerScaleX * $scale;
            $finalH = $h * $layerScaleY * $scale;

            // Font size (editor_canvas_widget.dart Lines 1060-1066)
            $finalFontSize = null;
            if ($type === 'text') {
                $finalFontSize = $rawFontSize * $ppiScale * $layerScaleY * $scale;

                // V1-V2 legacy Y offset (editor_canvas_widget.dart Line 935)
                if ($targetVersion < 3) {
                    $offsetMultiplier = 0.12; // Legacy factor
                    $finalY -= ($finalFontSize * $offsetMultiplier);
                }
            }

            $results[$name] = [
                'finalX' => round($finalX, 2),
                'finalY' => round($finalY, 2),
                'finalW' => round($finalW, 2),
                'finalH' => round($finalH, 2),
                'finalFontSize' => $finalFontSize !== null ? round($finalFontSize, 2) : null,
                'type' => $type,
            ];
        }

        return $results;
    }
}
