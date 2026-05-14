<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use App\Models\UserFavoriteFrame;
use App\Models\Business;
use App\Models\BusinessFrame;
use App\Models\BusinessCustomFrame;
use App\Models\PosterMaker;

class FrameApiController extends Controller
{
    public function toggleFavorite(Request $request)
    {
        $userId = auth('sanctum')->id() ?? auth()->id();
        if (!$userId) {
            return response()->json(['success' => false, 'message' => 'Unauthorized'], 401);
        }

        $frameId = $request->input('frame_id');
        if (!$frameId) {
            return response()->json(['success' => false, 'message' => 'Frame ID is required'], 400);
        }

        $existing = UserFavoriteFrame::where('user_id', $userId)
            ->where('frame_identifier', $frameId)
            ->first();

        if ($existing) {
            $existing->delete();
            return response()->json(['success' => true, 'is_favorite' => false]);
        } else {
            UserFavoriteFrame::create([
                'user_id' => $userId,
                'frame_identifier' => $frameId
            ]);
            return response()->json(['success' => true, 'is_favorite' => true]);
        }
    }

    public function getFavorites(Request $request)
    {
        $userId = auth('sanctum')->id() ?? auth()->id();
        if (!$userId) {
            return response()->json(['success' => false, 'message' => 'Unauthorized'], 401);
        }

        $favorites = UserFavoriteFrame::where('user_id', $userId)->pluck('frame_identifier');
        return response()->json(['success' => true, 'data' => $favorites]);
    }

    public function getAllFrames(Request $request)
    {
        $mainController = app(\App\Http\Controllers\MainController::class);
        $frames_list = collect();
        
        // 1. BusinessFrame — simple image-based frames (no zip/template)
        if ($request->has('business_category_id') && $request->business_category_id) {
            $business_frames = BusinessFrame::where('business_category_id', $request->business_category_id)
                ->where('status', 1)->get();
            foreach ($business_frames as $bf) {
                $imageUrl = '';
                if ($bf->frame_image) {
                    if (\App\Models\StorageSetting::getStorageSetting('storage') == 'DigitalOcean') {
                        $imageUrl = \Illuminate\Support\Facades\Storage::disk('spaces')->url('uploads/'.$bf->frame_image);
                    } else {
                        $imageUrl = asset('uploads/'.$bf->frame_image);
                    }
                }
                $frames_list->push((object) [
                    'id' => 'bf_' . $bf->id,
                    'db_id' => $bf->id,
                    'full_url' => $imageUrl,
                    'thumbnail_url' => $imageUrl,
                    'language_id' => 'all',
                    'category_id' => $bf->business_category_id ?? 'all',
                    'theme' => 'all',
                    'req_address' => 0,
                    'req_email' => 0,
                    'req_phone' => 0,
                    'req_website' => 0,
                    'config' => null,
                ]);
            }
        }

        // 2. PosterMaker — template-based frames with zip_name (works with extractFramesFromTemplates)
        $poster_frames = \App\Models\PosterMaker::orderBy('id', 'desc')->get();
        $frames_list = $frames_list->merge($mainController->extractFramesFromTemplates($poster_frames));

        return response()->json(['success' => true, 'data' => $frames_list->values()]);
    }
}
