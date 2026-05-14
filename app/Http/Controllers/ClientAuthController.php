<?php

namespace App\Http\Controllers;

use App\Models\User;
use App\Models\Business;
use App\Models\BusinessCategory;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Validator;
use Illuminate\Support\Str;
use Laravel\Socialite\Facades\Socialite;
use Illuminate\Support\Facades\Mail;
use App\Mail\PasswordResetOtp;
use App\Models\PasswordReset;

class ClientAuthController extends Controller
{
    public function showLoginForm()
    {
        return view('client.auth.login');
    }

    public function login(Request $request)
    {
        $credentials = $request->validate([
            'email' => ['required', 'email'],
            'password' => ['required'],
        ]);

        if (Auth::attempt($credentials, $request->remember)) {
            $request->session()->regenerate();
            return redirect()->intended('/dashboard');
        }

        return back()->withErrors([
            'email' => 'The provided credentials do not match our records.',
        ])->onlyInput('email');
    }

    public function showRegistrationForm()
    {
        $categories = BusinessCategory::where('status', 1)->get();
        return view('client.auth.register', compact('categories'));
    }

    public function register(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'name' => 'required|string|max:255',
            'email' => 'required|email|unique:users,email',
            'password' => 'required|min:6',
            'mobile_no' => 'required|string',
            'business_category_id' => 'required|exists:business_category,id',
            'business_sub_category_ids' => 'nullable|array',
            'logo' => 'nullable|image|mimes:jpeg,png,jpg,webp|max:2048',
        ]);

        if ($validator->fails()) {
            return back()->withErrors($validator)->withInput();
        }

        // Handle Logo Upload
        $logoName = null;
        if ($request->hasFile('logo')) {
            $file = $request->file('logo');
            $logoName = Str::uuid() . '.' . $file->getClientOriginalExtension();
            $file->move(public_path('uploads'), $logoName);
            @copy(public_path('uploads/' . $logoName), base_path('uploads/' . $logoName));
        }

        // 1. Create User
        $user = User::create([
            'name' => $request->name,
            'email' => $request->email,
            'password' => Hash::make($request->password),
            'mobile_no' => $request->mobile_no,
            'image' => $logoName, // Sync logo to user image
            'user_type' => 'O',
            'status' => 1,
        ]);

        // 2. Create Business
        Business::create([
            'user_id' => $user->id,
            'name' => $request->name,
            'email' => $request->email,
            'mobile_no' => $request->mobile_no,
            'logo' => $logoName,
            'website' => $request->website,
            'address' => $request->address,
            'business_category_id' => $request->business_category_id,
            'business_sub_category_ids' => $request->business_sub_category_ids ? json_encode($request->business_sub_category_ids) : null,
            'status' => 1,
            'is_default' => 1,
        ]);

        Auth::login($user);

        // If registration came from landing site auth-gate, show app-gateway
        if ($request->has('from_landing') && $request->from_landing == '1') {
            return redirect('/app-gateway');
        }

        return redirect('/dashboard');
    }

    /**
     * Redirect user to Google OAuth consent screen.
     */
    public function redirectToGoogle()
    {
        return Socialite::driver('google')->redirect();
    }

    /**
     * Handle callback from Google OAuth.
     * If the email already exists → log them in.
     * If the email is new → create user + default business → log them in.
     * Both paths end at /app-gateway.
     */
    public function handleGoogleCallback()
    {
        try {
            $googleUser = Socialite::driver('google')->user();
        } catch (\Exception $e) {
            return redirect('/auth-gate')->withErrors(['google' => 'Google authentication failed. Please try again.']);
        }

        // Check if user already exists
        $existingUser = User::where('email', $googleUser->getEmail())->first();

        if ($existingUser) {
            // Existing user → just log them in
            Auth::login($existingUser, true);
            return redirect('/app-gateway');
        }

        // New user → auto-register with Google data
        $user = User::create([
            'name' => $googleUser->getName(),
            'email' => $googleUser->getEmail(),
            'password' => Hash::make(Str::random(16)), // random password for security
            'image' => $googleUser->getAvatar(),
            'login_type' => 'google',
            'user_type' => 'O',
            'status' => 1,
            'email_verified_at' => now(), // Google emails are pre-verified
            'referral_code' => strtoupper(Str::random(10)),
            'api_token' => Str::random(60),
        ]);

        // Create a default Business profile so they can start immediately
        $defaultCategory = BusinessCategory::where('status', 1)->first();
        Business::create([
            'user_id' => $user->id,
            'name' => $googleUser->getName(),
            'email' => $googleUser->getEmail(),
            'business_category_id' => $defaultCategory ? $defaultCategory->id : null,
            'status' => 1,
            'is_default' => 1,
        ]);

        Auth::login($user, true);

        return redirect('/app-gateway');
    }

    /**
     * Show forgot password form (multi-step).
     */
    public function showForgotForm(Request $request)
    {
        $step = $request->query('step', 'email');
        return view('client.auth.forgot-password', compact('step'));
    }

    /**
     * Step 1: Validate email and send 6-digit OTP.
     */
    public function sendOtp(Request $request)
    {
        $request->validate(['email' => 'required|email']);

        $user = User::where('email', $request->email)->first();

        if (!$user) {
            return back()->withInput()->withErrors(['email' => 'No account found with this email address.']);
        }

        // Generate 6-digit OTP
        $otp = mt_rand(100000, 999999);

        // Store OTP in password_resets table
        PasswordReset::where('email', $request->email)->delete();
        PasswordReset::create([
            'email' => $request->email,
            'token' => Hash::make($otp),
            'created_at' => now(),
        ]);

        // Store plain OTP in session for verification
        session(['reset_email' => $request->email, 'reset_otp' => (string)$otp, 'reset_otp_time' => now()]);

        // Send OTP email
        try {
            Mail::to($request->email)->send(new PasswordResetOtp($request->email, $user->name, $otp));
        } catch (\Exception $e) {
            // If mail fails, still proceed (OTP is in session)
        }

        return redirect()->route('password.forgot', ['step' => 'otp'])
            ->with('success', 'A 6-digit OTP has been sent to ' . $request->email);
    }

    /**
     * Step 2: Verify the OTP code.
     */
    public function verifyOtp(Request $request)
    {
        $enteredOtp = $request->d1 . $request->d2 . $request->d3 . $request->d4 . $request->d5 . $request->d6;

        $storedOtp = session('reset_otp');
        $otpTime = session('reset_otp_time');

        if (!$storedOtp || !$otpTime) {
            return redirect()->route('password.forgot')->with('error', 'Session expired. Please request a new OTP.');
        }

        // Check if OTP expired (10 minutes)
        if (now()->diffInMinutes($otpTime) > 10) {
            session()->forget(['reset_otp', 'reset_otp_time']);
            return redirect()->route('password.forgot')->with('error', 'OTP expired. Please request a new one.');
        }

        if ($enteredOtp !== $storedOtp) {
            return redirect()->route('password.forgot', ['step' => 'otp'])
                ->withErrors(['otp' => 'Invalid OTP code. Please try again.']);
        }

        // OTP verified — allow password reset
        session(['otp_verified' => true]);
        session()->forget(['reset_otp', 'reset_otp_time']);

        return redirect()->route('password.forgot', ['step' => 'reset']);
    }

    /**
     * Step 3: Update the password.
     */
    public function updatePassword(Request $request)
    {
        if (!session('otp_verified') || !session('reset_email')) {
            return redirect()->route('password.forgot')->with('error', 'Session expired. Please start again.');
        }

        $request->validate([
            'password' => 'required|min:8|confirmed',
        ]);

        $user = User::where('email', session('reset_email'))->first();

        if (!$user) {
            return redirect()->route('password.forgot')->with('error', 'User not found.');
        }

        $user->password = Hash::make($request->password);
        $user->save();

        // Clean up all reset session data
        PasswordReset::where('email', session('reset_email'))->delete();
        session()->forget(['reset_email', 'otp_verified']);

        return redirect()->route('client.login')->with('status', 'Password updated successfully! Please login with your new password.');
    }

    public function webviewLogin(Request $request)
    {
        $userId = $request->query('user_id');
        $redirectUrl = $request->query('redirect', '/dashboard');

        if (!$userId) {
            return redirect('/login')->with('error', 'Authentication failed in WebView.');
        }

        $user = User::find($userId);

        if ($user) {
            Auth::login($user, true);
            return redirect($redirectUrl);
        }

        return redirect('/login')->with('error', 'Invalid token.');
    }

    public function logout(Request $request)
    {
        Auth::logout();
        $request->session()->invalidate();
        $request->session()->regenerateToken();
        return redirect('/login');
    }
}
