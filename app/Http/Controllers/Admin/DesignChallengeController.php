<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use App\Models\DesignChallenge;

class DesignChallengeController extends Controller
{
    public function index()
    {
        $challenges = DesignChallenge::orderBy('created_at', 'desc')->get();
        return view('admin.challenges.index', compact('challenges'));
    }

    public function store(Request $request)
    {
        $request->validate([
            'title' => 'required|string|max:255',
            'description' => 'nullable|string',
            'start_date' => 'required|date',
            'end_date' => 'required|date|after_or_equal:start_date',
            'reward_points' => 'nullable|string'
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
