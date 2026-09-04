<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\AdLiveAccessTicket;
use App\Models\Business;
use App\Models\User;
use App\Services\AdLiveBusinessProfileService;
use App\Services\AdLiveInternalRequestVerifier;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Str;
use Symfony\Component\HttpFoundation\Response;

class AdLiveSsoController extends Controller
{
    /**
     * Issue an opaque, one-time ticket for a signed-in Artera mobile user.
     * The ticket is exchanged by AdLive server-to-server; its payload never
     * travels in the Flutter app.
     */
    public function issue(Request $request, AdLiveBusinessProfileService $businessProfiles)
    {
        $request->validate([
            'business_id' => ['required', 'integer'],
            'consent_version' => ['nullable', 'string', 'max:32'],
        ]);

        $user = $request->user('sanctum');
        if (! $user) {
            return response()->json([
                'status' => 'Error',
                'message' => 'Please sign in again before opening AdLive.',
            ], Response::HTTP_UNAUTHORIZED);
        }

        $email = mb_strtolower(trim((string) $user->email));
        if (! filter_var($email, FILTER_VALIDATE_EMAIL)) {
            return response()->json([
                'status' => 'Error',
                'message' => 'Add a valid email address in Artera before connecting AdLive.',
            ], Response::HTTP_UNPROCESSABLE_ENTITY);
        }

        $business = Business::query()
            ->with('business_category')
            ->whereKey($request->integer('business_id'))
            ->where('user_id', $user->id)
            ->where('status', 1)
            ->first();

        if (! $business) {
            return response()->json([
                'status' => 'Error',
                'message' => 'Choose one of your active Artera businesses for AdLive.',
            ], Response::HTTP_NOT_FOUND);
        }

        $profile = $businessProfiles->snapshot($user, $business);
        $identity = $profile['identity'];
        $businessSnapshot = $profile['business'];
        $plainTicket = rtrim(strtr(base64_encode(random_bytes(48)), '+/', '-_'), '=');
        $expiresAt = now()->addSeconds(max(30, config('adlive.ticket_ttl_seconds')));

        AdLiveAccessTicket::create([
            'id' => (string) Str::uuid(),
            'ticket_hash' => hash('sha256', $plainTicket),
            'artera_user_id' => $user->id,
            'artera_business_id' => $business->id,
            'payload' => [
                'artera_user_id' => $identity['artera_user_id'],
                'name' => $identity['name'],
                'email' => $identity['email'],
                'phone' => $identity['phone'],
                'business' => $businessSnapshot,
                'signup_source' => $identity['signup_source'] ?? 'artera_pixel',
                'email_verified' => (bool) ($identity['email_verified'] ?? false),
                'consent_version' => $request->input('consent_version', 'adlive-mobile-v1'),
            ],
            'expires_at' => $expiresAt,
        ]);

        return response()->json([
            'ticket' => $plainTicket,
            'expires_at' => $expiresAt->toIso8601String(),
        ]);
    }

    /**
     * Internal endpoint called only by the AdLive server. It atomically marks
     * the opaque ticket consumed, then returns the minimal approved claims.
     */
    public function consume(Request $request, AdLiveInternalRequestVerifier $requestVerifier)
    {
        $sharedSecret = (string) config('adlive.shared_secret');
        $providedSecret = (string) $request->header('X-Artera-AdLive-Secret');

        if ($sharedSecret === '') {
            Log::critical('AdLive SSO consume attempted before its shared secret was configured.');

            return response()->json(['message' => 'AdLive SSO is not configured.'], Response::HTTP_SERVICE_UNAVAILABLE);
        }

        $signedRequest = $requestVerifier->hasSignatureHeaders($request);
        $authorized = $signedRequest
            ? $requestVerifier->verify($request)
            : (! config('adlive.require_signed_requests') && hash_equals($sharedSecret, $providedSecret));

        if (! $authorized) {
            Log::warning('Rejected an unauthenticated AdLive SSO ticket consume request.', [
                'ip' => $request->ip(),
            ]);

            return response()->json(['message' => 'Unauthorized.'], Response::HTTP_UNAUTHORIZED);
        }

        $request->validate(['ticket' => ['required', 'string', 'max:512']]);

        $payload = DB::transaction(function () use ($request) {
            $ticket = AdLiveAccessTicket::query()
                ->where('ticket_hash', hash('sha256', (string) $request->input('ticket')))
                ->lockForUpdate()
                ->first();

            if (! $ticket || $ticket->used_at || $ticket->expires_at->isPast()) {
                return null;
            }

            $ticket->forceFill(['used_at' => now()])->save();

            return $ticket->payload;
        });

        if (! $payload) {
            return response()->json(['message' => 'This AdLive access ticket is invalid or has expired.'], Response::HTTP_UNAUTHORIZED);
        }

        return response()->json(['claims' => $payload]);
    }

    /**
     * Signed endpoint used by AdLive's reconciliation job. It returns only
     * the requesting identity's active business profile and taxonomy.
     */
    public function businessSnapshot(Request $request, AdLiveInternalRequestVerifier $requestVerifier, AdLiveBusinessProfileService $businessProfiles)
    {
        if (! $requestVerifier->verify($request)) {
            return response()->json(['message' => 'Unauthorized.'], Response::HTTP_UNAUTHORIZED);
        }

        $request->validate([
            'artera_user_id' => ['required', 'integer'],
            'business_id' => ['required', 'integer'],
        ]);

        $user = User::query()->find($request->integer('artera_user_id'));
        $business = $user
            ? Business::query()
                ->whereKey($request->integer('business_id'))
                ->where('user_id', $user->id)
                ->where('status', 1)
                ->first()
            : null;

        if (! $business) {
            return response()->json(['message' => 'Business profile not found.'], Response::HTTP_NOT_FOUND);
        }

        return response()->json(['profile' => $businessProfiles->snapshot($user, $business)]);
    }
}
