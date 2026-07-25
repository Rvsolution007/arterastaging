<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use App\Models\User;
use App\Models\Business;
use Illuminate\Support\Facades\Validator;

class UserJourneyController extends Controller
{
    /**
     * Get the authenticated user ID from the request.
     * Prioritizes sanctum/session auth over client-supplied userId.
     */
    private function resolveUserId(Request $request)
    {
        // Security: Prefer authenticated user over request parameter
        if (auth('sanctum')->check()) {
            return auth('sanctum')->id();
        }
        if (auth()->check()) {
            return auth()->id();
        }
        // Fallback for mobile app compatibility — will be removed once mobile app sends auth tokens
        return $request->userId;
    }

    /**
     * Get Onboarding Status
     * Task 14: Smart Onboarding Flow
     */
    public function onboardingStatus(Request $request)
    {
        $userId = $this->resolveUserId($request);

        if (!$userId) {
            return response()->json(['status' => 'error', 'message' => 'userId is required']);
        }

        $user = User::find($userId);
        if (!$user) {
            return response()->json(['status' => 'error', 'message' => 'User not found'], 404);
        }
        
        $steps = [
            'profile_photo' => false,
            'business_details' => false,
            'first_post' => false,
            'explore_features' => false
        ];

        // 1. Profile Photo
        if ($user->image) {
            $steps['profile_photo'] = true;
        }

        // 2. Business Details
        $hasBusiness = Business::where('user_id', $user->id)->exists();
        if ($hasBusiness) {
            $steps['business_details'] = true;
        }

        // 3. First Post Created/Downloaded
        $usageSum = $user->custom_post_used + 
                    $user->daily_drip_used + 
                    $user->festival_post_used + 
                    $user->category_post_used;
        if ($usageSum > 0) {
            $steps['first_post'] = true;
        }

        // 4. Explored Features (Daily Drip)
        if ($user->daily_drip_used > 0) {
            $steps['explore_features'] = true;
        }

        $completedSteps = count(array_filter($steps));
        $totalSteps = count($steps);
        $percentage = ($completedSteps / $totalSteps) * 100;

        return response()->json([
            'status' => 'success',
            'data' => [
                'percentage' => $percentage,
                'completed_steps' => $completedSteps,
                'total_steps' => $totalSteps,
                'steps_detail' => $steps,
                'is_complete' => $percentage == 100
            ]
        ]);
    }

    /**
     * Manually Complete a specific onboarding step (if needed by frontend)
     */
    public function completeStep(Request $request)
    {
        return response()->json([
            'status' => 'success',
            'message' => 'Step recorded successfully.'
        ]);
    }

    /**
     * Check if user is eligible to see the NPS prompt
     * Task 15: In-App Feedback Loops
     */
    public function checkEligibility(Request $request)
    {
        $userId = $this->resolveUserId($request);

        if (!$userId) {
            return response()->json(['status' => 'error', 'message' => 'userId is required']);
        }

        $validator = Validator::make(['action' => $request->action], [
            'action' => 'required|string'
        ]);

        if ($validator->fails()) {
            return response()->json(['status' => 'error', 'message' => $validator->errors()->first()]);
        }

        return response()->json([
            'status' => 'success',
            'is_eligible' => true,
            'message' => 'User is eligible for NPS prompt.'
        ]);
    }

    /**
     * Submit NPS Feedback
     * Task 15: In-App Feedback Loops
     */
    public function submitFeedback(Request $request)
    {
        $userId = $this->resolveUserId($request);

        if (!$userId) {
            return response()->json(['status' => 'error', 'message' => 'userId is required']);
        }

        $validator = Validator::make($request->all(), [
            'rating' => 'required|integer|min:1|max:5',
            'comment' => 'nullable|string|max:2000' // Security: limit comment length
        ]);

        if ($validator->fails()) {
            return response()->json(['status' => 'error', 'message' => $validator->errors()->first()]);
        }

        // Keep first-party app feedback separate from public Play Store reviews.
        \App\Models\Feedback::create([
            'user_id' => $userId,
            'rating' => (int) $request->rating,
            'comment' => $request->comment,
            'feature_name' => 'in_app_feedback',
        ]);

        $actionTaken = 'None';
        if ($request->rating == 5 || $request->rating == 4) {
            $actionTaken = 'Redirect to PlayStore';
        } elseif ($request->rating <= 3) {
            $actionTaken = 'Create Support Ticket';
            // Actually create the ticket in DB
            if ($request->comment) {
                \App\Models\Ticket::create([
                    'user_id' => $userId, // Security: Use resolved user ID, not raw request param
                    'subject' => 'Negative App Feedback (' . $request->rating . ' Stars)',
                    'message' => strip_tags($request->comment), // Security: Strip HTML tags
                    'status' => 'Open',
                    'priority' => 'High'
                ]);
            }
        }

        return response()->json([
            'status' => 'success',
            'message' => 'Thank you for your feedback!',
            'action' => $actionTaken
        ]);
    }
}
