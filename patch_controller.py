import re

filepath = r"c:\xampp\htdocs\Artera\app\Http\Controllers\Admin\BusinessFrameController.php"

with open(filepath, "r", encoding="utf-8") as f:
    content = f.read()

# 1. Change how FontValidationService is handled at line 448
# From:
#                 $validationResult = \App\Services\FontValidationService::validateZipFonts($zipFile->getRealPath());
#                 if ($validationResult !== true) {
#                     $allWarnings[] = "{$originalName}: Upload failed! Missing fonts: " . implode(', ', $validationResult) . '.';
#                     continue;
#                 }
# To:
#                 $fontValidationResult = \App\Services\FontValidationService::validateZipFonts($zipFile->getRealPath());
# (remove the 'if' block)
content = re.sub(
    r'\$validationResult\s*=\s*\\App\\Services\\FontValidationService::validateZipFonts\(\$zipFile->getRealPath\(\)\);\s*if\s*\(\$validationResult\s*!==\s*true\)\s*\{\s*\$allWarnings\[\]\s*=\s*"\{\$originalName\}:\s*Upload failed!\s*Missing fonts:\s*"\s*\.\s*implode\(\', \',\s*\$validationResult\)\s*\.\s*\'.\';\s*continue;\s*\}',
    r'$fontValidationResult = \\App\\Services\\FontValidationService::validateZipFonts($zipFile->getRealPath());',
    content
)

# 2. Change the structural validation logic (lines 508-569) to collect all errors and stop saving if any exist
# Also remove the physical font file check inside the ZIP (lines 537-559)

structural_validation_pattern = r'(\s*// Validate the template structure before saving\s*\$validationWarnings = \[\];\s*if \(!\$jsonRules\) \{).*?(if \(!empty\(\$validationWarnings\)\) \{\s*\$allWarnings\[\] = "\{\$originalName\}: " \. implode\(\' \| \', \$validationWarnings\);\s*\})'

new_structural_validation = """                // Validate the template structure before saving
                $validationErrors = [];
                
                if ($fontValidationResult !== true) {
                    $validationErrors[] = "Missing fonts: " . implode(', ', $fontValidationResult);
                }

                if (!$jsonRules) {
                    $validationErrors[] = 'No JSON found';
                } else {
                    $jsonData = json_decode($jsonRules, true);
                    if (!$jsonData) {
                        $validationErrors[] = 'JSON invalid';
                    } else {
                        if (!isset($jsonData['layers']) || !is_array($jsonData['layers']) || count($jsonData['layers']) === 0) {
                            $validationErrors[] = 'No layers array';
                        } else {
                            $skinsPath = $extractPath;
                            $skinsDirs = glob($skinsPath . '/*/skins/*', GLOB_ONLYDIR);
                            if (empty($skinsDirs)) {
                                $skinsDirs = glob($skinsPath . '/skins/*', GLOB_ONLYDIR);
                            }
                            if (!empty($skinsDirs)) {
                                $skinDir = $skinsDirs[0];
                                foreach ($jsonData['layers'] as $layer) {
                                    if (($layer['type'] ?? '') === 'image') {
                                        $imgFile = basename($layer['src'] ?? '');
                                        if ($imgFile && !file_exists($skinDir . '/' . $imgFile)) {
                                            $validationErrors[] = "Missing skin: {$imgFile}";
                                        }
                                    }
                                }
                            }
                        }
                        if (!isset($jsonData['info']['width']) || !isset($jsonData['info']['height'])) {
                            $validationErrors[] = 'Missing info dimensions';
                        }
                    }
                }

                if (!empty($validationErrors)) {
                    $allWarnings[] = "{$originalName}: Upload failed! " . implode(' | ', $validationErrors);
                    
                    // Cleanup extracted files if upload failed
                    if (file_exists($extractPath)) {
                        \\Illuminate\\Support\\Facades\\File::deleteDirectory($extractPath);
                    }
                    if (StorageSetting::getStorageSetting("storage") != "DigitalOcean" && isset($fileName) && file_exists(public_path('uploads/custom_frames_zips/' . $fileName))) {
                        @unlink(public_path('uploads/custom_frames_zips/' . $fileName));
                    }
                    continue;
                }"""

content = re.sub(structural_validation_pattern, new_structural_validation, content, flags=re.DOTALL)

with open(filepath, "w", encoding="utf-8") as f:
    f.write(content)
print("Patch applied successfully.")
