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

        $startDate = '2026-06-01 00:00:00';

        $totalPosts = \App\Models\UserActivity::where('user_id', $userId)
            ->whereIn('action', ['download_template', 'create_custom_post', 'create_festival_post'])
            ->where('created_at', '>=', $startDate)
            ->count();

        // Fetch Design Challenges for the user
        $challenges = DesignChallenge::where('is_active', true)
            ->orWhereHas('participants', function($q) use ($userId) {
                $q->where('user_id', $userId)->where('status', 'completed');
            })
            ->orderBy('created_at', 'desc')
            ->get()->map(function($c) use ($userId) {
                $participant = ChallengeParticipant::where('challenge_id', $c->id)->where('user_id', $userId)->first();
                $arr = $c->toArray();
                $arr['progress'] = $participant ? $participant->progress : 0;
                $arr['status'] = $participant ? $participant->status : 'in_progress';
                $arr['is_participated'] = true; // Auto-enrolled now
                return $arr;
            });

        return response()->json([
            'status' => 'success',
            'data' => [
                'achievements' => $achievements,
                'challenges' => $challenges,
                'total_posts' => $totalPosts,
                'badge_post_count' => 100 // Default value for UI backward compatibility
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

        $activeChallenges = DesignChallenge::where('is_active', true)
            ->where('end_date', '>=', now()->startOfDay())
            ->get();

        foreach ($activeChallenges as $challenge) {
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
                $participant = ChallengeParticipant::firstOrCreate(
                    ['challenge_id' => $challenge->id, 'user_id' => $userId],
                    ['status' => 'in_progress', 'progress' => 0]
                );

                if ($participant->status != 'completed') {
                    $participant->progress += 1;
                    if ($participant->progress >= $challenge->target_count) {
                        $participant->status = 'completed';
                        $participant->progress = $challenge->target_count;
                        
                        // Add reward points if any
                        if ($challenge->reward_points > 0) {
                            $user = \App\Models\User::find($userId);
                            if ($user) {
                                $user->increment('reward_points', $challenge->reward_points);
                            }
                        }
                    }
                    $participant->save();
                }
            }
        }
    }
}
