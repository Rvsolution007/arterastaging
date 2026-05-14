<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\Subscription;

class LandingController extends Controller
{
    public function home()
    {
        return view('landing.home');
    }

    public function authGate()
    {
        // Need Business categories because the register view needs it
        $categories = \App\Models\BusinessCategory::where('status', 1)->get();
        return view('landing.auth-gate', compact('categories'));
    }

    public function appGateway()
    {
        return view('landing.app-gateway');
    }

    public function about()
    {
        return view('landing.about');
    }

    public function features()
    {
        return view('landing.features');
    }

    public function packages()
    {
        $packages = Subscription::where('status', '1')->get();
        return view('landing.packages', compact('packages'));
    }

    public function reviews()
    {
        return view('landing.reviews');
    }

    public function contact()
    {
        return view('landing.contact');
    }
}
