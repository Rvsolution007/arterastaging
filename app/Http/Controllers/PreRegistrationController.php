<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\PreRegistration;

class PreRegistrationController extends Controller
{
    public function index()
    {
        return view('landing.pre_registration');
    }

    public function store(Request $request)
    {
        $request->validate([
            'name' => 'required|string|max:255',
            'business_name' => 'required|string|max:255',
            'mobile' => 'required|numeric|unique:pre_registrations,mobile',
            'email' => 'nullable|email',
            'category' => 'required|string|max:255',
        ]);

        PreRegistration::create([
            'name' => $request->name,
            'business_name' => $request->business_name,
            'mobile' => $request->mobile,
            'email' => $request->email,
            'category' => $request->category,
        ]);

        return back()->with('success', 'Registration successful! You will receive a 50% discount on your first use when we launch.');
    }
}
