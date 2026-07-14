<?php

namespace App\Services;

use App\Models\GoldenRender;

/**
 * Orchestrates Dual Engine Validation by comparing
 * Web + Native simulator results against Golden Snapshot baselines.
 */
class DualEngineValidator
{
    private WebRenderSimulator $webSim;
    private NativeRenderSimulator $nativeSim;

    // Tolerance thresholds (in pixels)
    const EXACT_MATCH_TOLERANCE = 0;     // 0px = perfect match
    const MINOR_DRIFT_TOLERANCE = 2;     // ≤2px = acceptable drift
    const MISMATCH_THRESHOLD = 2;        // >2px = mismatch (requires review)

    public function __construct()
    {
        $this->webSim = new WebRenderSimulator();
        $this->nativeSim = new NativeRenderSimulator();
    }

    /**
     * Validate a single frame's migration from currentVersion to targetVersion.
     *
     * @param  int    $frameId
     * @param  array  $templateJson  The frame's template JSON
     * @param  int    $currentVersion
     * @param  int    $targetVersion
     * @return array  Validation result
     */
    public function validate(int $frameId, array $templateJson, int $currentVersion, int $targetVersion): array
    {
        // Get Golden Baseline
        $golden = GoldenRender::where('frame_id', $frameId)
            ->where('render_version', $currentVersion)
            ->first();

        if (!$golden) {
            return [
                'status' => 'NO_BASELINE',
                'message' => "No golden baseline found for frame #$frameId at V$currentVersion",
                'web_mismatches' => [],
                'native_mismatches' => [],
            ];
        }

        // Compute new values at target version
        $newWeb = $this->webSim->compute($templateJson, $targetVersion);
        $newNative = $this->nativeSim->compute($templateJson, $targetVersion);

        // Compare against golden baselines
        $webMismatches = $this->compareComputed(
            $golden->web_computed ?? [],
            $newWeb,
            'web'
        );

        $nativeMismatches = $this->compareComputed(
            $golden->native_computed ?? [],
            $newNative,
            'native'
        );

        // Determine overall status
        $allMismatches = array_merge($webMismatches, $nativeMismatches);
        $maxDiff = 0;
        foreach ($allMismatches as $m) {
            $maxDiff = max($maxDiff, abs($m['diff']));
        }

        if (empty($allMismatches)) {
            $status = 'MATCH';
        } elseif ($maxDiff <= self::MINOR_DRIFT_TOLERANCE) {
            $status = 'MINOR_DRIFT';
        } else {
            $status = 'MISMATCH';
        }

        return [
            'status' => $status,
            'frame_id' => $frameId,
            'current_version' => $currentVersion,
            'target_version' => $targetVersion,
            'max_diff_px' => round($maxDiff, 2),
            'web_mismatches' => $webMismatches,
            'native_mismatches' => $nativeMismatches,
            'new_web_computed' => $newWeb,
            'new_native_computed' => $newNative,
        ];
    }

    /**
     * Compare golden computed values vs new computed values.
     */
    private function compareComputed(array $golden, array $newValues, string $engine): array
    {
        $mismatches = [];
        $propsToCompare = ['canvasX', 'canvasY', 'canvasW', 'canvasH', 'computedFontSize',
                           'finalX', 'finalY', 'finalW', 'finalH', 'finalFontSize'];

        foreach ($newValues as $layerName => $newProps) {
            $goldenProps = $golden[$layerName] ?? null;
            if ($goldenProps === null) continue; // New layer, no baseline to compare

            foreach ($propsToCompare as $prop) {
                if (!isset($newProps[$prop]) || !isset($goldenProps[$prop])) continue;

                $oldVal = floatval($goldenProps[$prop]);
                $newVal = floatval($newProps[$prop]);
                $diff = $newVal - $oldVal;

                if (abs($diff) > self::EXACT_MATCH_TOLERANCE) {
                    $mismatches[] = [
                        'engine' => $engine,
                        'layer' => $layerName,
                        'property' => $prop,
                        'golden_value' => $oldVal,
                        'new_value' => $newVal,
                        'diff' => round($diff, 2),
                        'severity' => abs($diff) <= self::MINOR_DRIFT_TOLERANCE ? 'minor' : 'major',
                        'auto_compensatable' => in_array($prop, ['canvasX','canvasY','canvasW','canvasH','finalX','finalY','finalW','finalH']),
                    ];
                }
            }
        }

        return $mismatches;
    }
}
