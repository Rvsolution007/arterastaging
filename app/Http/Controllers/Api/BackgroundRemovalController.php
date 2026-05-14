<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use App\Models\ApiSetting;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

class BackgroundRemovalController extends Controller
{
    public function removeBackground(Request $request)
    {
        $user = auth()->user();

        // Check if Photoroom API is enabled globally
        $isPhotoroomEnabled = ApiSetting::getApiSetting('photoroom_api_enable') == 1;

        if (!$isPhotoroomEnabled) {
            return response()->json([
                'success' => false,
                'fallback' => true,
                'message' => 'Photoroom API is disabled globally. Falling back to default.'
            ]);
        }

        $apiKey = ApiSetting::getApiSetting('photoroom_api_key');
        if (empty($apiKey)) {
            return response()->json([
                'success' => false,
                'fallback' => true,
                'message' => 'Photoroom API key is missing. Falling back to default.'
            ]);
        }

        // Ensure user has an active subscription and available limit
        if (!$user || !$user->canUseFeature('photoroom_bg')) {
            return response()->json([
                'success' => false,
                'fallback' => true,
                'message' => 'Background removal limit exceeded or no active subscription. Falling back to default.'
            ]);
        }

        // Validate the request contains an image
        $request->validate([
            'image_base64' => 'required|string',
        ]);

        $base64Image = $request->input('image_base64');
        
        // Remove the data URI scheme if present (e.g. data:image/png;base64,)
        if (preg_match('/^data:image\/(\w+);base64,/', $base64Image, $type)) {
            $base64Image = substr($base64Image, strpos($base64Image, ',') + 1);
        }

        $imageContent = base64_decode($base64Image);

        try {
            // Call Photoroom API
            $response = Http::withHeaders([
                'x-api-key' => $apiKey,
                'Accept' => 'image/png'
            ])->attach(
                'image_file', 
                $imageContent, 
                'image.png'
            )->post('https://image-api.photoroom.com/v2/edit', [
                // Optional: add any photoroom specific parameters here
                // e.g., 'format' => 'png',
                // 'background.color' => 'transparent',
            ]);

            if ($response->successful()) {
                // Consume the user's limit
                $user->consumeFeature('photoroom_bg');

                // Return the result as base64
                $resultBase64 = base64_encode($response->body());
                
                return response()->json([
                    'success' => true,
                    'fallback' => false,
                    'image' => 'data:image/png;base64,' . $resultBase64,
                    'remaining_limit' => $user->getRemainingUsage('photoroom_bg')
                ]);
            }

            Log::error('Photoroom API Error', [
                'status' => $response->status(),
                'body' => $response->body()
            ]);

            // If API fails, trigger fallback
            return response()->json([
                'success' => false,
                'fallback' => true,
                'message' => 'Photoroom API failed. Falling back to default.'
            ]);

        } catch (\Exception $e) {
            Log::error('Photoroom API Exception: ' . $e->getMessage());

            return response()->json([
                'success' => false,
                'fallback' => true,
                'message' => 'Internal server error while calling Photoroom API. Falling back to default.'
            ]);
        }
    }
}
