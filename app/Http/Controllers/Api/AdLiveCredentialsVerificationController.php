<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Business;
use App\Models\User;
use App\Services\AdLiveBusinessProfileService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Str;
use Symfony\Component\HttpFoundation\Response;

/**
 * Server-only verification of a normal Pixel email/password login.
 *
 * The route is guarded by ProtectAdLiveProfileUpdates before this action is
 * reached. A plaintext password exists only in this request handler long
 * enough for Hash::check; it is never included in a log, job, exception, or
 * response.
 */
class AdLiveCredentialsVerificationController extends Controller
{
    public function verify(Request $request, AdLiveBusinessProfileService $businessProfiles)
    {
        if ($request->attributes->get('adlive_profile_authenticated') !== true) {
            return response()->json(['message' => 'Unauthorized.'], Response::HTTP_UNAUTHORIZED);
        }

        try {
            $data = json_decode($request->getContent(), true, 16, JSON_THROW_ON_ERROR);
        } catch (\JsonException) {
            return $this->invalidCredentials();
        }

        if (! is_array($data)
            || array_diff(array_keys($data), ['email', 'password'])
            || ! isset($data['email'], $data['password'])
            || ! is_string($data['email'])
            || ! is_string($data['password'])) {
            return $this->invalidCredentials();
        }

        $email = Str::lower(trim($data['email']));
        if ($email === '' || mb_strlen($email) > 255 || filter_var($email, FILTER_VALIDATE_EMAIL) === false
            || $data['password'] === '' || mb_strlen($data['password']) > 1024) {
            return $this->invalidCredentials();
        }

        try {
            $user = User::query()
                ->whereRaw('LOWER(email) = ?', [$email])
                ->where('status', 1)
                ->first();

            // This response intentionally does not distinguish a missing
            // email, disabled account, malformed stored hash, or bad password.
            if (! $user || ! Hash::check($data['password'], (string) $user->password)) {
                return $this->invalidCredentials();
            }

            $business = Business::query()
                ->where('user_id', $user->id)
                ->where('status', 1)
                ->orderByDesc('is_default')
                ->orderBy('id')
                ->first();
            if (! $business) {
                return response()->json([
                    'message' => 'The active business profile is unavailable.',
                ], Response::HTTP_CONFLICT);
            }

            $profile = $businessProfiles->sharedSnapshot($user, $business);

            return response()->json([
                'identity' => array_merge($profile['identity'], [
                    'email_verified' => (bool) $user->email_verified_at,
                    'signup_source' => $user->registration_source === 'adlive' ? 'adlive' : 'artera_pixel',
                    'consent_version' => (string) config('adlive.identity_consent_version'),
                    'business' => $profile['business'],
                ]),
            ]);
        } catch (\Throwable) {
            // Never report an exception object from a password-bearing request.
            Log::error('AdLive credential verification is temporarily unavailable.');

            return response()->json([
                'message' => 'Credential verification is temporarily unavailable.',
            ], Response::HTTP_SERVICE_UNAVAILABLE);
        }
    }

    private function invalidCredentials()
    {
        return response()->json([
            'message' => 'Invalid credentials.',
        ], Response::HTTP_UNAUTHORIZED);
    }
}
