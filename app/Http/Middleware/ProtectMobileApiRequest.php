<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class ProtectMobileApiRequest
{
    /**
     * Public reads remain available to guests. Any mutation, or request that
     * names a user, must carry a valid Sanctum mobile bearer token.
     */
    public function handle(Request $request, Closure $next)
    {
        $hasUserIdentifier = $request->filled('userId') || $request->filled('user_id');
        $isMutation = !in_array($request->method(), ['GET', 'HEAD', 'OPTIONS'], true);
        $hasBearerToken = trim((string) $request->bearerToken()) !== '';

        if (!$isMutation && !$hasUserIdentifier && !$hasBearerToken) {
            return $next($request);
        }

        $user = auth('sanctum')->user();
        if (!$user || !$user->currentAccessToken()) {
            return response()->json(['status' => 'Error', 'message' => 'Authentication is required for this request.'], Response::HTTP_UNAUTHORIZED);
        }

        $token = $user->currentAccessToken();
        if (($token->expires_at && now()->greaterThanOrEqualTo($token->expires_at)) || !$token->can('mobile:access')) {
            if ($token->expires_at && now()->greaterThanOrEqualTo($token->expires_at)) {
                $token->delete();
            }
            return response()->json(['status' => 'Error', 'message' => 'Your session is invalid or expired. Please sign in again.'], Response::HTTP_UNAUTHORIZED);
        }

        foreach (['userId', 'user_id'] as $field) {
            if ($request->filled($field) && (int) $request->input($field) !== $user->id) {
                \Log::warning('Blocked API ownership mismatch.', [
                    'route' => optional($request->route())->uri(),
                    'authenticated_user_id' => $user->id,
                    'requested_user_id' => $request->input($field),
                    'ip' => $request->ip(),
                ]);

                return response()->json(['status' => 'Error', 'message' => 'You are not allowed to access another user account.'], Response::HTTP_FORBIDDEN);
            }
        }

        $request->attributes->set('authenticated_mobile_user_id', $user->id);

        return $next($request);
    }
}
