<?php

$filepath = 'app/Http/Controllers/Admin/BusinessFrameController.php';
$content = file_get_contents($filepath);

// 1. Remove font continue
$old1 = <<<'EOD'
                $validationResult = \App\Services\FontValidationService::validateZipFonts($zipFile->getRealPath());
                if ($validationResult !== true) {
                    $allWarnings[] = "{$originalName}: Upload failed! Missing fonts: " . implode(', ', $validationResult) . '.';
                    continue;
                }
EOD;

$new1 = <<<'EOD'
                $fontValidationResult = \App\Services\FontValidationService::validateZipFonts($zipFile->getRealPath());
EOD;

$content = str_replace($old1, $new1, $content);


// 2. Replace structural validation
$old2 = <<<'EOD'
                // Validate the template structure before saving
                $validationWarnings = [];
                if (!$jsonRules) {
                    $validationWarnings[] = 'No JSON found';
                } else {
                    $jsonData = json_decode($jsonRules, true);
                    if (!$jsonData) {
                        $validationWarnings[] = 'JSON invalid';
                    } else {
                        if (!isset($jsonData['layers']) || !is_array($jsonData['layers']) || count($jsonData['layers']) === 0) {
                            $validationWarnings[] = 'No layers array';
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
                                            $validationWarnings[] = "Missing skin: {$imgFile}";
                                        }
                                    }
                                }
                            }

                            if (isset($jsonData['layers'])) {
                                $fontsNeeded = [];
                                foreach ($jsonData['layers'] as $layer) {
                                    if (($layer['type'] ?? '') === 'text' && isset($layer['font'])) {
                                        $fontsNeeded[$layer['font']] = true;
                                    }
                                }
                                if (!empty($fontsNeeded)) {
                                    $fontsDir = null;
                                    $fontsDirPaths = glob($skinsPath . '/*/fonts', GLOB_ONLYDIR);
                                    if (empty($fontsDirPaths)) {
                                        $fontsDirPaths = glob($skinsPath . '/fonts', GLOB_ONLYDIR);
                                    }
                                    if (!empty($fontsDirPaths)) {
                                        $fontsDir = $fontsDirPaths[0];
                                    }
                                    foreach (array_keys($fontsNeeded) as $fontName) {
                                        if (!$fontsDir || !file_exists($fontsDir . '/' . $fontName . '.ttf')) {
                                            $validationWarnings[] = "Missing font: {$fontName}.ttf";
                                        }
                                    }
                                }
                            }
                        }
                        if (!isset($jsonData['info']['width']) || !isset($jsonData['info']['height'])) {
                            $validationWarnings[] = 'Missing info dimensions';
                        }
                    }
                }

                if (!empty($validationWarnings)) {
                    $allWarnings[] = "{$originalName}: " . implode(' | ', $validationWarnings);
                }
EOD;

$new2 = <<<'EOD'
                // Validate the template structure before saving
                $validationErrors = [];
                
                if (isset($fontValidationResult) && is_array($fontValidationResult) && count($fontValidationResult) > 0) {
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
                    
                    if (file_exists($extractPath)) {
                        \Illuminate\Support\Facades\File::deleteDirectory($extractPath);
                    }
                    if (StorageSetting::getStorageSetting("storage") != "DigitalOcean" && isset($fileName) && file_exists(public_path('uploads/custom_frames_zips/' . $fileName))) {
                        @unlink(public_path('uploads/custom_frames_zips/' . $fileName));
                    }
                    continue;
                }
EOD;

$content = str_replace($old2, $new2, $content);
file_put_contents($filepath, $content);
echo "Done";
