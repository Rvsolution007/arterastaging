<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Business;
use App\Models\User;
use App\Services\AdLiveInternalRequestVerifier;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class AdLiveMigrationInventoryController extends Controller
{
    /**
     * Return a paginated, fingerprint-only inventory for the one-time legacy
     * migration report. Raw email addresses, passwords and access tokens are
     * never returned. This endpoint remains disabled until an administrator
     * explicitly enables the dry-run window in the server environment.
     */
    public function index(Request $request, AdLiveInternalRequestVerifier $requestVerifier)
    {
        if (! config('adlive.migration_inventory_enabled')) {
            abort(Response::HTTP_NOT_FOUND);
        }

        if (! $requestVerifier->verify($request)) {
            return response()->json(['message' => 'Unauthorized.'], Response::HTTP_UNAUTHORIZED);
        }

        $request->validate([
            'cursor' => ['nullable', 'integer', 'min:0'],
            'limit' => ['nullable', 'integer', 'min:1', 'max:500'],
        ]);

        $limit = $request->integer('limit', 100);
        $users = User::query()
            ->where('status', 1)
            ->whereNotNull('email')
            ->when($request->filled('cursor'), fn ($query) => $query->where('id', '>', $request->integer('cursor')))
            ->orderBy('id')
            ->limit($limit + 1)
            ->get(['id', 'email', 'email_verified_at']);
        $hasMore = $users->count() > $limit;
        $page = $users->take($limit);
        $businessesByUser = Business::query()
            ->where('status', 1)
            ->whereIn('user_id', $page->pluck('id'))
            ->orderByDesc('is_default')
            ->orderBy('id')
            ->get(['id', 'user_id', 'is_default'])
            ->groupBy('user_id');
        $secret = (string) config('adlive.shared_secret');

        return response()->json([
            'records' => $page->map(fn (User $user): array => [
                'artera_user_id' => (string) $user->id,
                'email_fingerprint' => hash_hmac('sha256', mb_strtolower(trim((string) $user->email)), $secret),
                'email_verified' => $user->email_verified_at !== null,
                'businesses' => ($businessesByUser->get($user->id, collect()))
                    ->map(fn (Business $business): array => [
                        'id' => (string) $business->id,
                        'is_default' => (bool) $business->is_default,
                    ])
                    ->values()
                    ->all(),
            ])->all(),
            'next_cursor' => $hasMore ? (string) $page->last()->id : null,
        ]);
    }
}
