<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class AuthenticateMobileApi
{
    public function handle(Request $request, Closure $next)
    {
        $user = auth('sanctum')->user();
        if (!$user || !$user->currentAccessToken()) {
            return $this->unauthorized('Please sign in again to continue.');
        }

        $token = $user->currentAccessToken();
        if ($token->expires_at && now()->greaterThanOrEqualTo($token->expires_at)) {
            $token->delete();
            return $this->unauthorized('Your session has expired. Please sign in again.');
        }

        if (!$token->can('mobile:access')) {
            return response()->json(['status' => 'Error', 'message' => 'This session is not allowed to access the mobile API.'], Response::HTTP_FORBIDDEN);
        }

        $request->attributes->set('authenticated_mobile_user_id', $user->id);

        return $next($request);
    }

    private function unauthorized(string $message)
    {
        return response()->json(['status' => 'Error', 'message' => $message], Response::HTTP_UNAUTHORIZED);
    }
}
