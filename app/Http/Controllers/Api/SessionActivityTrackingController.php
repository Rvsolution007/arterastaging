<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\UserActivity;
use Illuminate\Database\QueryException;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;
use Illuminate\Support\Str;
use Symfony\Component\HttpFoundation\Response;

/**
 * Records non-financial mobile session telemetry independently from download
 * entitlement accounting. Client event IDs make mobile retries idempotent.
 */
class SessionActivityTrackingController extends Controller
{
    public function store(Request $request)
    {
        $user = auth('sanctum')->user();
        if (! $user) {
            return response()->json([
                'status' => 'error',
                'message' => 'Authentication is required.',
            ], Response::HTTP_UNAUTHORIZED);
        }

        $allowed = ['event_id', 'action', 'item_type', 'item_id', 'platform'];
        if (array_diff(array_keys($request->all()), $allowed)) {
            return response()->json([
                'message' => 'The given data was invalid.',
                'errors' => ['body' => ['Use only the documented tracking fields.']],
            ], Response::HTTP_UNPROCESSABLE_ENTITY);
        }

        $data = Validator::make($request->all(), [
            'event_id' => ['required', 'uuid'],
            'action' => ['required', 'in:home_visit,select_template'],
            'item_type' => ['nullable', 'string', 'max:100'],
            'item_id' => ['nullable', 'string', 'max:255'],
            'platform' => ['nullable', 'in:Mobile,Web'],
        ])->validate();

        $attributes = [
            'user_id' => $user->id,
            'client_event_id' => Str::lower($data['event_id']),
        ];
        $values = [
            'action' => $data['action'],
            'payload' => [
                'item_type' => $data['item_type'] ?? null,
                'item_id' => $data['item_id'] ?? null,
                'platform' => $data['platform'] ?? 'Mobile',
            ],
            'ip_address' => $request->ip(),
            // Persist the route only. Query strings can contain unnecessary
            // identifiers and are not needed by the session timeline.
            'url' => '/'.$request->path(),
            'method' => $request->method(),
            'user_agent' => $request->userAgent(),
        ];

        try {
            $activity = UserActivity::query()->firstOrCreate($attributes, $values);
            $created = $activity->wasRecentlyCreated;
        } catch (QueryException $exception) {
            // A concurrent mobile retry can lose the unique-index race. It is
            // still a successful idempotent delivery, never a duplicate row.
            $activity = UserActivity::query()->where($attributes)->first();
            if (! $activity) {
                throw $exception;
            }
            $created = false;
        }

        return response()->json([
            'status' => 'success',
            'event_id' => (string) $activity->client_event_id,
            'duplicate' => ! $created,
        ]);
    }
}
