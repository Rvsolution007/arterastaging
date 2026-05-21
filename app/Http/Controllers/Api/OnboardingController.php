<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;

class OnboardingController extends Controller
{
    /**
     * Mark an onboarding step as complete for the authenticated user.
     * Expects: {'step': 'created_first_post'}
     */
    public function completeStep(Request $request)
    {
        $user = auth()->user();
        
        if (!$user) {
            return response()->json(['status' => 'error', 'message' => 'Unauthenticated'], 401);
        }

        $step = $request->input('step');
        if (!$step) {
            return response()->json(['status' => 'error', 'message' => 'Step name is required'], 400);
        }

        // Decode existing steps or initialize empty array
        $steps = json_decode($user->completed_onboarding_steps, true) ?? [];

        // Add the step if not already present
        if (!in_array($step, $steps)) {
            $steps[] = $step;
            $user->completed_onboarding_steps = json_encode($steps);
            $user->save();
        }

        return response()->json([
            'status' => 'success',
            'message' => 'Onboarding step marked as complete',
            'completed_steps' => $steps
        ]);
    }
}
