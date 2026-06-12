<?php

namespace App\Services;

use Illuminate\Support\Facades\Log;
use Illuminate\Support\Str;

class BackgroundRemovalService
{
    /**
     * Removes the background from a base64 encoded image using local python rembg.
     *
     * @param string $base64Image The input image as base64 string.
     * @return string The output transparent image as base64 string.
     * @throws \Exception
     */
    public function removeBackground(string $base64Image): string
    {
        // Remove data URI scheme if present
        if (preg_match('/^data:image\/(\w+);base64,/', $base64Image, $type)) {
            $base64Image = substr($base64Image, strpos($base64Image, ',') + 1);
        }

        $imageContents = base64_decode($base64Image);
        if ($imageContents === false) {
            throw new \Exception("Invalid base64 string provided for background removal.");
        }

        // Generate temporary file paths
        $uuid = Str::uuid()->toString();
        $inputPath = storage_path("app/temp_in_{$uuid}.png");
        $outputPath = storage_path("app/temp_out_{$uuid}.png");

        // Save the input image
        file_put_contents($inputPath, $imageContents);

        try {
            // Path to the python script
            $scriptPath = base_path('scripts/remove_bg.py');
            
            // Construct and execute the command
            $command = "python \"{$scriptPath}\" \"{$inputPath}\" \"{$outputPath}\" 2>&1";
            $output = [];
            $returnVar = 0;
            
            exec($command, $output, $returnVar);

            $outputStr = implode("\n", $output);

            if ($returnVar !== 0 || !file_exists($outputPath)) {
                Log::error("Background Removal Failed", ['output' => $outputStr, 'command' => $command]);
                throw new \Exception("Failed to remove background: " . $outputStr);
            }

            // Read the output transparent image
            $transparentImageContents = file_get_contents($outputPath);
            $transparentBase64 = base64_encode($transparentImageContents);

            // Return with data URI scheme to match expectations if needed
            // But usually we just return the raw base64 or exactly what was passed.
            return $transparentBase64;

        } finally {
            // Cleanup temporary files
            if (file_exists($inputPath)) {
                unlink($inputPath);
            }
            if (file_exists($outputPath)) {
                unlink($outputPath);
            }
        }
    }
}
