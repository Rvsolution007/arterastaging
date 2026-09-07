<?php

namespace App\Http\Middleware;

use App\Services\AdLiveInternalRequestVerifier;
use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;
use Symfony\Component\HttpFoundation\Response;

/** Runs before CORS and JSON transformations for signed AdLive internal APIs. */
class ProtectAdLiveProfileUpdates
{
    public function handle(Request $request, Closure $next): Response
    {
        $isInternalAdLive = $request->is('api/internal/adlive/*');
        $isCredentialVerification = $request->is('api/internal/adlive/credentials/verify');
        $acceptsJson = str_contains(
            strtolower((string) $request->header('Accept')),
            'application/json',
        );
        if (! $isInternalAdLive) {
            return $next($request);
        }

        $request->headers->set('Accept', 'application/json');

        // Browser requests and preflights must never reach CORS or the action.
        // These checks supplement HMAC; they are not an authentication scheme.
        if ($request->isMethod('OPTIONS') || $request->hasHeader('Origin')
            || collect(array_keys($request->headers->all()))->contains(fn ($name) => str_starts_with($name, 'sec-fetch-'))) {
            return $this->finish(response()->json(['message' => 'Server-to-server requests only.'], 403));
        }

        // Every internal endpoint is JSON-only. This is a server contract, not
        // a browser API, so callers must explicitly request JSON.
        if (! $acceptsJson) {
            return $this->finish(response()->json(['message' => 'Accept must be application/json.'], 406));
        }

        if (! $request->isMethod('POST')) {
            return $this->finish(response()->json(['message' => 'Method not allowed.'], 405, ['Allow' => 'POST']));
        }

        if (strlen($request->getContent()) > 131072) {
            return $this->finish(response()->json(['message' => 'The request body is too large.'], 413));
        }

        try {
            $authenticated = app(AdLiveInternalRequestVerifier::class)->verify(
                $request,
                $isCredentialVerification
                    ? '/api/internal/adlive/credentials/verify'
                    : null,
                $isCredentialVerification,
            );
        } catch (\Throwable $exception) {
            // Never report an exception object with request headers or bindings.
            Log::error('AdLive internal request verification is unavailable.');

            return $this->finish(response()->json(['message' => 'AdLive internal service is temporarily unavailable.'], 503));
        }

        if (! $authenticated) {
            return $this->finish(response()->json(['message' => 'Unauthorized.'], 401));
        }

        if (strtolower(trim(explode(';', $request->header('Content-Type', ''))[0])) !== 'application/json') {
            return $this->finish(response()->json(['message' => 'Content-Type must be application/json.'], 415));
        }

        if ($request->getQueryString() !== null || $request->hasHeader('Authorization')
            || $request->hasHeader('Cookie') || $request->hasHeader('X-Artera-AdLive-Secret')) {
            return $this->finish(response()->json(['message' => 'Use only the signed headers and JSON body.'], 400));
        }

        $request->attributes->set('adlive_profile_authenticated', true);

        return $this->finish($next($request));
    }

    private function finish(Response $response): Response
    {
        $response->headers->set('Cache-Control', 'no-store, private');
        foreach (array_keys($response->headers->all()) as $name) {
            if (str_starts_with($name, 'access-control-')) {
                $response->headers->remove($name);
            }
        }

        return $response;
    }
}
