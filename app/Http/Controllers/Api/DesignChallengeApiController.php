<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use App\Models\DesignChallenge;
use App\Models\ChallengeParticipant;

class DesignChallengeApiController extends Controller
{
    public function getActiveChallenges()
    {
        $challenges = DesignChallenge::where('is_active', 1)
            ->whereDate('end_date', '>=', now())
            ->orderBy('end_date', 'asc')
            ->get();

        return response()->json([
            'status' => 'success',
            'data' => $challenges
        ]);
    }

    public function submitChallenge(Request $request)
    {
        $request->validate([
            'user_id' => 'required|exists:users,id',
            'challenge_id' => 'required|exists:design_challenges,id',
            'post_id' => 'nullable|integer'
        ]);

        // Check if already submitted
        $exists = ChallengeParticipant::where('user_id', $request->user_id)
            ->where('challenge_id', $request->challenge_id)
            ->exists();

        if ($exists) {
            return response()->json([
                'status' => 'error',
                'message' => 'You have already participated in this challenge.'
            ], 400);
        }

        ChallengeParticipant::create([
            'user_id' => $request->user_id,
            'challenge_id' => $request->challenge_id,
            'post_id' => $request->post_id,
        ]);

        return response()->json([
            'status' => 'success',
            'message' => 'Challenge submitted successfully!'
        ]);
    }
}
