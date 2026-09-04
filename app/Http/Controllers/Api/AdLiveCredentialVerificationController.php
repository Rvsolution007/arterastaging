<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Business;
use App\Models\User;
use App\Services\AdLiveBusinessProfileService;
use App\Services\AdLiveInternalRequestVerifier;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Str;
use Symfony\Component\HttpFoundation\Response;

/** Server-only shared credential verification for the AdLive client portal. */
class AdLiveCredentialVerificationController extends Controller
{
    public function verify(
        Request $request,
        AdLiveInternalRequestVerifier $requestVerifier,
        AdLiveBusinessProfileService $businessProfiles,
    ) {
        if (! $requestVerifier->verify($request)) {
            return response()->json(['message' => 'Unauthorized.'], Response::HTTP_UNAUTHORIZED);
        }

        $data = $request->validate([
            'email' => ['required', 'email', 'max:255'],
            'password' => ['required', 'string', 'max:255'],
        ]);

        $user = User::query()
            ->where('email', Str::lower(trim($data['email'])))
            ->where('status', 1)
            ->first();
        $business = $user
            ? Business::query()
                ->where('user_id', $user->id)
                ->where('status', 1)
                ->orderByDesc('is_default')
                ->orderBy('id')
                ->first()
            : null;

        // One generic response prevents account enumeration through AdLive.
        if (! $user || ! Hash::check($data['password'], $user->password)) {
            return response()->json(['message' => 'Invalid credentials.'], Response::HTTP_UNAUTHORIZED);
        }

        $identity = $businessProfiles->identity($user);
        if ($business) {
            $identity['business'] = $businessProfiles->snapshot($user, $business)['business'];
        }

        return response()->json([
            'identity' => array_merge($identity, [
                'signup_source' => $user->registration_source === 'adlive' ? 'adlive' : 'artera_pixel',
                'consent_version' => 'shared-identity-v1',
                'email_verified' => (bool) $user->email_verified_at,
            ]),
        ]);
    }
}
