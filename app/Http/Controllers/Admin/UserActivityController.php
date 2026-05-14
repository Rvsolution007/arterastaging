<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;

use App\Models\UserActivity;

class UserActivityController extends Controller
{
    public function index()
    {
        $activities = UserActivity::with('user')
            ->orderBy('created_at', 'desc')
            ->paginate(50);

        return view('admin.user_activities.index', compact('activities'));
    }
}
