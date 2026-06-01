<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use App\Models\DesignChallenge;
use App\Models\PushNotificationLog;
use App\Models\Setting;

class DesignChallengeController extends Controller
{
    public function index()
    {
        $challenges = DesignChallenge::orderBy('created_at', 'desc')->get();
        $pushLogs = PushNotificationLog::with('user')->orderBy('created_at', 'desc')->limit(100)->get();
        $badgePostCount = Setting::getGlobalValue('gamification', 'badge_post_count', 100);
        $streakGoalDays = Setting::getGlobalValue('gamification', 'streak_goal_days', 7);
        $smartPushEnabled = Setting::getGlobalValue('gamification', 'smart_push_enabled', 1);
        
        return view('admin.challenges.index', compact('challenges', 'pushLogs', 'badgePostCount', 'streakGoalDays', 'smartPushEnabled'));
    }

    public function storeSettings(Request $request)
    {
        $request->validate([
            'badge_post_count' => 'required|integer',
            'streak_goal_days' => 'required|integer',
            'smart_push_enabled' => 'required|boolean'
        ]);

        Setting::setValue('gamification', 'badge_post_count', $request->badge_post_count);
        Setting::setValue('gamification', 'streak_goal_days', $request->streak_goal_days);
        Setting::setValue('gamification', 'smart_push_enabled', $request->smart_push_enabled);

        return back()->with('success', 'Gamification settings updated successfully.');
    }

    public function store(Request $request)
    {
        $request->validate([
            'title' => 'required|string|max:255',
            'description' => 'nullable|string',
            'start_date' => 'required|date',
            'end_date' => 'required|date|after_or_equal:start_date',
            'type' => 'nullable|string|in:any_post,festival_post,custom_post,ai_trends_post',
            'target_count' => 'required|integer|min:1',
            'target_id' => 'nullable|integer',
            'reward_points' => 'nullable|integer|min:0'
        ]);

        DesignChallenge::create($request->all());

        return back()->with('success', 'Design Challenge created successfully.');
    }

    public function update(Request $request, $id)
    {
        $challenge = DesignChallenge::findOrFail($id);
        
        $request->validate([
            'title' => 'required|string|max:255',
            'start_date' => 'required|date',
            'end_date' => 'required|date|after_or_equal:start_date',
            'type' => 'nullable|string|in:any_post,festival_post,custom_post,ai_trends_post',
            'target_count' => 'required|integer|min:1',
            'target_id' => 'nullable|integer',
            'reward_points' => 'nullable|integer|min:0'
        ]);

        $challenge->update($request->all());

        return back()->with('success', 'Design Challenge updated successfully.');
    }

    public function destroy($id)
    {
        DesignChallenge::findOrFail($id)->delete();
        return back()->with('success', 'Design Challenge deleted successfully.');
    }

    public function toggleStatus($id)
    {
        $challenge = DesignChallenge::findOrFail($id);
        $challenge->is_active = !$challenge->is_active;
        $challenge->save();

        return back()->with('success', 'Challenge status updated.');
    }
}
