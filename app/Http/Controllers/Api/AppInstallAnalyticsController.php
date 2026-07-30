<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\AppInstallEvent;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;

class AppInstallAnalyticsController extends Controller
{
    /**
     * Receives an install directly from the mobile app. The local install ID
     * makes retries safe while a fresh app install creates a new total install.
     */
    public function recordInstall(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'device_id' => 'required|string|max:255',
            'install_id' => 'required|string|max:128',
            'userId' => 'nullable|integer',
            'platform' => 'nullable|string|max:20',
            'app_version' => 'nullable|string|max:50',
            'device_model' => 'nullable|string|max:120',
            'os_version' => 'nullable|string|max:120',
        ]);

        if ($validator->fails()) {
            return response()->json([
                'status' => 'error',
                'message' => $validator->errors()->first(),
            ], 422);
        }

        $data = $validator->validated();
        $authenticatedUser = $request->user('sanctum');

        // This endpoint is intentionally available before login so a fresh
        // install can be counted. A client-provided user ID must therefore
        // never be trusted unless it matches the authenticated mobile user.
        $userId = null;
        if ($authenticatedUser) {
            if (isset($data['userId']) && (int) $data['userId'] !== $authenticatedUser->id) {
                return response()->json([
                    'status' => 'error',
                    'message' => 'The install event does not belong to the authenticated user.',
                ], 403);
            }

            $userId = $authenticatedUser->id;
        }

        $event = AppInstallEvent::recordInstall($data['device_id'], $data['install_id'], [
            'user_id' => $userId,
            'platform' => $data['platform'] ?? 'android',
            'app_version' => $data['app_version'] ?? null,
            'device_model' => $data['device_model'] ?? null,
            'os_version' => $data['os_version'] ?? null,
        ]);

        return response()->json([
            'status' => 'success',
            'event_id' => $event->id,
        ]);
    }
}
