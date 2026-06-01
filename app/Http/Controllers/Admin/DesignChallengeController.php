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
        
        return view('admin.challenges.index', compact('challenges', 'pushLogs'));
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
            'reward_points' => 'nullable|integer|min:0',
            'streak_goal_days' => 'nullable|integer|min:1',
            'push_notification_enabled' => 'boolean'
        ]);

        $challenge = DesignChallenge::create($request->all());

        if ($request->push_notification_enabled) {
            \App\Models\PushNotificationLog::create([
                'user_id' => auth()->id() ?? 1,
                'title' => 'New Challenge: ' . $challenge->title,
                'message' => 'A new challenge is available! Check it out.',
                'status' => 'success',
                'error_reason' => null
            ]);
        }

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
            'reward_points' => 'nullable|integer|min:0',
            'streak_goal_days' => 'nullable|integer|min:1',
            'push_notification_enabled' => 'boolean'
        ]);

        $challenge->update($request->all());

        if ($request->push_notification_enabled) {
            \App\Models\PushNotificationLog::create([
                'user_id' => auth()->id() ?? 1,
                'title' => 'Challenge Updated: ' . $challenge->title,
                'message' => 'Check out the updated challenge details!',
                'status' => 'success',
                'error_reason' => null
            ]);
        }

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
