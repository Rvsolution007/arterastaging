<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\AdLiveAuthorizationCode;
use App\Models\Business;
use App\Services\AdLiveBusinessProfileService;
use App\Services\AdLiveInternalRequestVerifier;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use Symfony\Component\HttpFoundation\Response;

class AdLiveAuthorizationController extends Controller
{
    public function begin(Request $request, AdLiveBusinessProfileService $businessProfiles)
    {
        $request->validate([
            'client_id' => ['required', 'string', 'max:64'],
            'redirect_uri' => ['required', 'url', 'max:2048'],
            'state' => ['required', 'string', 'min:32', 'max:128'],
            'code_challenge' => ['required', 'string', 'regex:/^[A-Za-z0-9_-]{43,128}$/'],
            'code_challenge_method' => ['required', 'in:S256'],
            'intent' => ['nullable', 'in:login,signup'],
        ]);

        if (! $this->isAllowedClient($request->string('client_id')->toString(), $request->string('redirect_uri')->toString())) {
            abort(Response::HTTP_UNPROCESSABLE_ENTITY, 'The AdLive redirect URL is not registered.');
        }

        if (! Auth::check()) {
            $request->session()->put('url.intended', $request->fullUrl());

            return redirect()->route($request->input('intent') === 'signup' ? 'client.register' : 'client.login');
        }

        $user = Auth::user();
        $business = Business::query()
            ->where('user_id', $user->id)
            ->where('status', 1)
            ->orderByDesc('is_default')
            ->orderBy('id')
            ->first();

        if (! $business) {
            return redirect()->route('client.register')
                ->withErrors(['email' => 'Create an active Artera business before opening AdLive.']);
        }

        $plainCode = rtrim(strtr(base64_encode(random_bytes(48)), '+/', '-_'), '=');
        $expiresAt = now()->addSeconds(max(30, (int) config('adlive.authorization_code_ttl_seconds', 90)));
        $profile = $businessProfiles->snapshot($user, $business);

        AdLiveAuthorizationCode::create([
            'id' => (string) Str::uuid(),
            'code_hash' => hash('sha256', $plainCode),
            'client_id' => $request->string('client_id')->toString(),
            'redirect_uri' => $request->string('redirect_uri')->toString(),
            'code_challenge' => $request->string('code_challenge')->toString(),
            'artera_user_id' => $user->id,
            'artera_business_id' => $business->id,
            'payload' => [
                'artera_user_id' => $profile['identity']['artera_user_id'],
                'name' => $profile['identity']['name'],
                'email' => $profile['identity']['email'],
                'phone' => $profile['identity']['phone'],
                'business' => $profile['business'],
                'consent_version' => 'adlive-web-v1',
            ],
            'expires_at' => $expiresAt,
        ]);

        $separator = str_contains($request->string('redirect_uri')->toString(), '?') ? '&' : '?';

        return redirect($request->string('redirect_uri')->toString().$separator.http_build_query([
            'code' => $plainCode,
            'state' => $request->string('state')->toString(),
        ], '', '&', PHP_QUERY_RFC3986));
    }

    public function consume(Request $request, AdLiveInternalRequestVerifier $requestVerifier)
    {
        if (! $requestVerifier->verify($request)) {
            return response()->json(['message' => 'Unauthorized.'], Response::HTTP_UNAUTHORIZED);
        }

        $request->validate([
            'client_id' => ['required', 'string', 'max:64'],
            'code' => ['required', 'string', 'max:512'],
            'code_verifier' => ['required', 'string', 'min:43', 'max:128'],
            'redirect_uri' => ['required', 'url', 'max:2048'],
        ]);

        $claims = DB::transaction(function () use ($request) {
            $code = AdLiveAuthorizationCode::query()
                ->where('code_hash', hash('sha256', $request->string('code')->toString()))
                ->lockForUpdate()
                ->first();

            if (! $code || $code->used_at || $code->expires_at->isPast()
                || ! hash_equals($code->client_id, $request->string('client_id')->toString())
                || ! hash_equals($code->redirect_uri, $request->string('redirect_uri')->toString())
                || ! hash_equals($code->code_challenge, $this->codeChallenge($request->string('code_verifier')->toString()))) {
                return null;
            }

            $code->forceFill(['used_at' => now()])->save();

            return $code->payload;
        });

        if (! is_array($claims)) {
            return response()->json(['message' => 'This authorization code is invalid or expired.'], Response::HTTP_UNAUTHORIZED);
        }

        return response()->json(['claims' => $claims]);
    }

    private function codeChallenge(string $verifier): string
    {
        return rtrim(strtr(base64_encode(hash('sha256', $verifier, true)), '+/', '-_'), '=');
    }

    private function isAllowedClient(string $clientId, string $redirectUri): bool
    {
        $clients = config('adlive.web_clients', []);
        $client = is_array($clients) ? ($clients[$clientId] ?? null) : null;

        return is_array($client) && in_array($redirectUri, $client['redirect_uris'] ?? [], true);
    }
}
