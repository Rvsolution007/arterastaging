<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use App\Models\User;

class JourneyController extends Controller
{
    public function index()
    {
        $stats = [
            'onboarding' => User::where('journey_stage', 'onboarding')->count(),
            'activation' => User::where('journey_stage', 'activation')->count(),
            'engagement' => User::where('journey_stage', 'engagement')->count(),
            'retention' => User::where('journey_stage', 'retention')->count(),
            'winback' => User::where('journey_stage', 'winback')->count(),
        ];

        $users = User::orderBy('created_at', 'desc')->paginate(15);

        return view('admin.journey.index', compact('stats', 'users'));
    }
}
