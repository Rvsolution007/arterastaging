<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class EnsureMcpAnalyticsAccess
{
    /**
     * Authorizes only a scoped Sanctum token issued for the configured owner.
     * This intentionally does not accept mobile-app tokens or browser sessions.
     */
    public function handle(Request $request, Closure $next)
    {
        $user = auth('sanctum')->user();
        $token = $user?->currentAccessToken();

        if (!$user || !$token || !$token->can('mcp:analytics')) {
            return response()->json([
                'success' => false,
                'message' => 'MCP analytics authentication is required.',
            ], Response::HTTP_UNAUTHORIZED);
        }

        if ($token->expires_at && now()->greaterThanOrEqualTo($token->expires_at)) {
            $token->delete();

            return response()->json([
                'success' => false,
                'message' => 'MCP analytics token has expired.',
            ], Response::HTTP_UNAUTHORIZED);
        }

        $allowedEmails = config('mcp_analytics.allowed_admin_emails', []);
        if (!in_array(mb_strtolower((string) $user->email), $allowedEmails, true)) {
            return response()->json([
                'success' => false,
                'message' => 'This account is not authorized for MCP analytics.',
            ], Response::HTTP_FORBIDDEN);
        }

        $request->attributes->set('mcp_analytics_user_id', $user->id);

        return $next($request);
    }
}
