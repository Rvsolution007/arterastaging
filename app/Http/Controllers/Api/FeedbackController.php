<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use App\Models\Feedback;
use Carbon\Carbon;

class FeedbackController extends Controller
{
    /**
     * Check if the user is eligible to see the In-App Feedback NPS prompt.
     */
    public function checkEligibility(Request $request)
    {
        $user = auth()->user();
        if (!$user) {
            return response()->json(['eligible' => false], 401);
        }

        // Logic: Only ask if they haven't been asked in the last 30 days
        if ($user->last_feedback_asked_at) {
            $lastAsked = Carbon::parse($user->last_feedback_asked_at);
            if ($lastAsked->diffInDays(now()) < 30) {
                return response()->json(['eligible' => false, 'reason' => 'asked_recently']);
            }
        }

        // Logic: Ask only if they have used features significantly (e.g., total_usage_count > 3)
        // Adjust the field to whatever tracks global usage
        $usageCount = $user->total_usage_count ?? 0;
        if ($usageCount < 3) {
            return response()->json(['eligible' => false, 'reason' => 'insufficient_usage']);
        }

        return response()->json(['eligible' => true]);
    }

    /**
     * Submit the feedback from the mobile app.
     */
    public function submit(Request $request)
    {
        $user = auth()->user();
        if (!$user) {
            return response()->json(['status' => 'error', 'message' => 'Unauthenticated'], 401);
        }

        $request->validate([
            'rating' => 'required|integer|min:1|max:5',
            'comment' => 'nullable|string',
            'feature_name' => 'nullable|string'
        ]);

        Feedback::create([
            'user_id' => $user->id,
            'rating' => $request->input('rating'),
            'comment' => $request->input('comment'),
            'feature_name' => $request->input('feature_name'),
        ]);

        // Mark that we asked them today
        $user->last_feedback_asked_at = now();
        $user->save();

        return response()->json([
            'status' => 'success',
            'message' => 'Thank you for your feedback!'
        ]);
    }
}
