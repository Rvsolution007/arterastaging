<?php

namespace App\Services;

use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

class RembgService
{
    /**
     * Remove the background of an image using the local Python Rembg API.
     *
     * @param string $imagePath Absolute path to the image file.
     * @param string $model Which model to use (u2net, u2netp, isnet-general-use)
     * @return string|null Path to the processed image, or null on failure.
     */
    public static function removeBackground($imagePath, $model = 'u2netp')
    {
        try {
            // The local python rembg server URL
            $url = config('services.rembg.url', 'http://localhost:5000/api/remove');

            // Send image via multipart form-data to the python API
            $response = Http::timeout(60)->attach(
                'file', file_get_contents($imagePath), basename($imagePath)
            )->post($url, [
                'model' => $model
            ]);

            if ($response->successful()) {
                // Generate a unique filename for the processed PNG
                $filename = 'no_bg_' . time() . '_' . uniqid() . '.png';
                $savePath = storage_path('app/public/removed-bg/' . $filename);
                
                // Ensure directory exists
                if (!file_exists(dirname($savePath))) {
                    mkdir(dirname($savePath), 0755, true);
                }

                // Save the raw PNG bytes returned by the API
                file_put_contents($savePath, $response->body());

                return 'removed-bg/' . $filename;
            } else {
                Log::error('Rembg API Error: ' . $response->body());
                return null;
            }
        } catch (\Exception $e) {
            Log::error('Rembg Service Exception: ' . $e->getMessage());
            return null;
        }
    }
}
