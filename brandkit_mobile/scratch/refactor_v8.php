<?php
$file = 'c:/xampp/htdocs/Artera/brandkit_mobile/lib/controllers/native_editor_controller.dart';
$content = file_get_contents($file);

// Find _asyncApplyBrightness
$startAsync = strpos($content, '  Future<void> _asyncApplyBrightness(');
$endAsync = strpos($content, '  bool _applyDynamicTextColor(', $startAsync);

if ($startAsync === false || $endAsync === false) {
    die("Failed to find _asyncApplyBrightness boundaries.");
}

$asyncCode = substr($content, $startAsync, $endAsync - $startAsync);

// Create V7 async (original)
$asyncV7 = str_replace('  Future<void> _asyncApplyBrightness(', '  Future<void> _asyncApplyBrightnessV7(', $asyncCode);
// Revert V7 back to 128 (since the file currently has 160 due to git restore)
$asyncV7 = str_replace('luminance < 160', 'luminance < 128', $asyncV7);
$asyncV7 = str_replace('avgBrightness < 160', 'avgBrightness < 128', $asyncV7);

// Create V8 async
$asyncV8 = str_replace('  Future<void> _asyncApplyBrightness(', '  Future<void> _asyncApplyBrightnessV8(', $asyncCode);
// Ensure V8 uses the new 160 threshold (it already has 160 but just in case)
$asyncV8 = str_replace('luminance < 128', 'luminance < 160', $asyncV8);
$asyncV8 = str_replace('avgBrightness < 128', 'avgBrightness < 160', $asyncV8);
// Also route V8 to _applyDynamicTextColorV8
$asyncV8 = str_replace('_applyDynamicTextColor', '_applyDynamicTextColorV8', $asyncV8);

// Create Router for async
$asyncRouter = "  Future<void> _asyncApplyBrightness(
    Map<String, dynamic> newFrameJson,
    List<dynamic> newLayers,
    List<dynamic> preservedLayers,
    List<Map<String, dynamic>> shapeLayers
  ) async {
    int renderVersion = (newFrameJson['render_version'] ?? templateConfig['render_version'] ?? 1) as int;
    if (renderVersion >= 8) {
      return _asyncApplyBrightnessV8(newFrameJson, newLayers, preservedLayers, shapeLayers);
    }
    return _asyncApplyBrightnessV7(newFrameJson, newLayers, preservedLayers, shapeLayers);
  }\n\n";

// Replace async block with Router + V7 + V8
$content = substr_replace($content, $asyncRouter . $asyncV7 . $asyncV8, $startAsync, $endAsync - $startAsync);

// ---------------------------------------------------------
// Find _applyDynamicTextColor
$startApply = strpos($content, '  bool _applyDynamicTextColor(');
$endApply = strpos($content, '  double _computeContrastRatio(', $startApply);

if ($startApply === false || $endApply === false) {
    die("Failed to find _applyDynamicTextColor boundaries.");
}

$applyCode = substr($content, $startApply, $endApply - $startApply);

// Create V7 apply (original)
$applyV7 = str_replace('  bool _applyDynamicTextColor(', '  bool _applyDynamicTextColorV7(', $applyCode);
// Revert to original logic for V7 (since the file currently has 160 and 4.5 due to git restore)
$applyV7 = str_replace('luminance < 160', 'luminance < 128', $applyV7);
$applyV7 = str_replace('if (contrastRatio >= 4.5) {', 'if (contrastRatio >= 2.0) {', $applyV7);
$applyV7 = str_replace('// Use standard WCAG contrast ratio threshold of 4.5', '', $applyV7);

// Create V8 apply
$applyV8 = str_replace('  bool _applyDynamicTextColor(', '  bool _applyDynamicTextColorV8(', $applyCode);
// Keep new logic for V8 (it already has 160 and 4.5, but just to be sure)
$applyV8 = str_replace('luminance < 128', 'luminance < 160', $applyV8);
$applyV8 = str_replace('if (contrastRatio >= 2.0) {', 'if (contrastRatio >= 4.5) {', $applyV8);
// Fix the missing fill bug
$applyV8 = str_replace(
    "layer['original_color'] = layer['color'] ?? layer['tint_color'] ?? '0xFFFFFFFF';", 
    "layer['original_color'] = layer['color'] ?? layer['tint_color'] ?? layer['fill'] ?? '0xFFFFFFFF';", 
    $applyV8
);

// Create Router for apply
$applyRouter = "  bool _applyDynamicTextColor(
    Map<String, dynamic> layer,
    bool templateIsDark,
    List<Map<String, dynamic>> shapeLayers,
    {String? matchedColor, int renderVersion = 1}
  ) {
    if (renderVersion >= 8) {
      return _applyDynamicTextColorV8(layer, templateIsDark, shapeLayers, matchedColor: matchedColor, renderVersion: renderVersion);
    }
    return _applyDynamicTextColorV7(layer, templateIsDark, shapeLayers, matchedColor: matchedColor, renderVersion: renderVersion);
  }\n\n";

// Replace apply block with Router + V7 + V8
$content = substr_replace($content, $applyRouter . $applyV7 . $applyV8, $startApply, $endApply - $startApply);

file_put_contents($file, $content);
echo "Successfully refactored native_editor_controller.dart to use versioned function isolation for V8.";
?>
