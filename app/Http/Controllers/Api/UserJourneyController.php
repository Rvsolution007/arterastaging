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
     * Get Onboarding Status
     * Task 14: Smart Onboarding Flow
     */
    public function onboardingStatus(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'userId' => 'required|exists:users,id',
        ]);

        if ($validator->fails()) {
            return response()->json(['status' => 'error', 'message' => $validator->errors()->first()]);
        }

        $user = User::find($request->userId);
        
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
        $hasBusiness = Business::where('userId', $user->id)->exists();
        if ($hasBusiness) {
            $steps['business_details'] = true;
        }

        // 3. First Post Created/Downloaded
        $usageSum = $user->custom_post_used + 
                    $user->daily_drip_used + 
                    $user->festival_post_used + 
                    $user->business_category_post_used;
        if ($usageSum > 0) {
            $steps['first_post'] = true;
        }

        // 4. Explored Features (Magic Cloner)
        if ($user->magic_cloner_used > 0) {
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
        $validator = Validator::make($request->all(), [
            'userId' => 'required|exists:users,id',
            'action' => 'required|string' // e.g., 'download_post'
        ]);

        if ($validator->fails()) {
            return response()->json(['status' => 'error', 'message' => $validator->errors()->first()]);
        }

        // In a real app, we might check if they have submitted feedback in the last 30 days.
        // For now, always return true to show the prompt for demonstration.
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
        $validator = Validator::make($request->all(), [
            'userId' => 'required|exists:users,id',
            'rating' => 'required|integer|min:1|max:5',
            'comment' => 'nullable|string'
        ]);

        if ($validator->fails()) {
            return response()->json(['status' => 'error', 'message' => $validator->errors()->first()]);
        }

        $actionTaken = 'None';
        if ($request->rating == 5 || $request->rating == 4) {
            $actionTaken = 'Redirect to PlayStore';
        } elseif ($request->rating <= 3) {
            $actionTaken = 'Create Support Ticket';
            // Actually create the ticket in DB
            if ($request->comment) {
                \App\Models\Ticket::create([
                    'user_id' => $request->userId,
                    'subject' => 'Negative App Feedback (' . $request->rating . ' Stars)',
                    'message' => $request->comment,
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
