<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use App\Models\DesignChallenge;
use App\Models\ChallengeParticipant;

class DesignChallengeApiController extends Controller
{
    public function getActiveChallenges(Request $request)
    {
        $challenges = DesignChallenge::where('is_active', 1)
            ->whereDate('end_date', '>=', now())
            ->orderBy('end_date', 'asc')
            ->get();

        $userId = $request->get('user_id');
        if ($userId) {
            $challenges->map(function($challenge) use ($userId) {
                $participant = ChallengeParticipant::where('challenge_id', $challenge->id)
                    ->where('user_id', $userId)
                    ->first();
                
                if ($participant) {
                    $challenge->is_participated = true;
                    $challenge->progress = $participant->progress;
                    $challenge->status = $participant->status; // 'in_progress' or 'completed'
                } else {
                    $challenge->is_participated = false;
                    $challenge->progress = 0;
                    $challenge->status = 'not_started';
                }
                return $challenge;
            });
        }

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

    public function getAchievements(Request $request)
    {
        $request->validate([
            'user_id' => 'required|exists:users,id'
        ]);

        $userId = $request->user_id;
        
        $achievements = \Illuminate\Support\Facades\DB::table('user_achievements')
            ->where('user_id', $userId)
            ->get();

        $startDate = \App\Models\Setting::getGlobalValue('gamification', 'start_date', '2026-06-01 00:00:00');

        $totalPosts = \App\Models\UserActivity::where('user_id', $userId)
            ->whereIn('action', ['download_template', 'create_custom_post', 'create_festival_post', 'magic_cloner_use'])
            ->where('created_at', '>=', $startDate)
            ->count();
            
        $badgePostCount = \App\Models\Setting::getGlobalValue('gamification', 'badge_post_count', 100);

        return response()->json([
            'status' => 'success',
            'data' => [
                'achievements' => $achievements,
                'total_posts' => $totalPosts,
                'badge_post_count' => (int) $badgePostCount
            ]
        ]);
    }

    public static function incrementProgress($userId, $action, $itemType, $itemId)
    {
        if (!in_array($action, ['download_template', 'create_custom_post', 'create_festival_post'])) {
            return;
        }

        $challengeType = 'any_post';
        if ($itemType == 'festival') $challengeType = 'festival_post';
        if (in_array($itemType, ['custom', 'business_custom_frame', 'business_frame', 'business_custom'])) $challengeType = 'custom_post';
        // ai_trends_post logic if exists

        $activeParticipants = ChallengeParticipant::where('user_id', $userId)
            ->where('status', 'in_progress')
            ->get();

        foreach ($activeParticipants as $participant) {
            $challenge = DesignChallenge::find($participant->challenge_id);
            if ($challenge && $challenge->is_active && $challenge->end_date >= now()) {
                
                $isMatch = false;
                if ($challenge->type == 'any_post' || !$challenge->type) {
                    $isMatch = true;
                } else if ($challenge->type == $challengeType) {
                    if ($challenge->target_id) {
                        if ($challenge->target_id == $itemId) {
                            $isMatch = true;
                        }
                    } else {
                        $isMatch = true;
                    }
                }

                if ($isMatch) {
                    $participant->progress += 1;
                    if ($participant->progress >= $challenge->target_count) {
                        $participant->status = 'completed';
                        $participant->progress = $challenge->target_count;
                        
                        // Add reward points if any
                        if ($challenge->reward_points > 0) {
                            $user = \App\Models\User::find($userId);
                            if ($user) {
                                // Assuming user model has a way to add points. Usually user_wallets or user_points
                                // We will leave a stub for points logic here if needed
                            }
                        }
                    }
                    $participant->save();
                }
            }
        }
    }
}
