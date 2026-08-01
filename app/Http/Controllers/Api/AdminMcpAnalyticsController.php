<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use Carbon\Carbon;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Schema;

/**
 * Read-only, least-privilege analytics contract for the Artera MCP server.
 *
 * It deliberately returns aggregates and data-minimised user summaries only.
 * Do not add write operations, secrets, payment IDs, IP addresses, device IDs,
 * ticket bodies, or raw activity payloads to this controller.
 */
class AdminMcpAnalyticsController extends Controller
{
    public function overview(Request $request)
    {
        [$start, $end] = $this->dateRange($request);
        $sales = $this->salesData($start, $end);
        $tickets = $this->ticketData($start, $end);
        $installs = $this->installData($start, $end);

        return $this->respond($request, [
            'period' => $this->period($start, $end),
            'registrations' => $this->registeredUsers($start, $end),
            'installs' => $installs,
            'package_sales' => $sales,
            'support' => $tickets,
            'ad_revenue' => $this->adRevenueData($start, $end),
        ]);
    }

    public function installs(Request $request)
    {
        [$start, $end] = $this->dateRange($request);

        return $this->respond($request, [
            'period' => $this->period($start, $end),
            ...$this->installData($start, $end),
        ]);
    }

    public function sales(Request $request)
    {
        [$start, $end] = $this->dateRange($request);

        return $this->respond($request, [
            'period' => $this->period($start, $end),
            ...$this->salesData($start, $end),
        ]);
    }

    public function adRevenue(Request $request)
    {
        [$start, $end] = $this->dateRange($request);

        return $this->respond($request, [
            'period' => $this->period($start, $end),
            ...$this->adRevenueData($start, $end),
        ]);
    }

    public function tickets(Request $request)
    {
        [$start, $end] = $this->dateRange($request);

        return $this->respond($request, [
            'period' => $this->period($start, $end),
            ...$this->ticketData($start, $end),
        ]);
    }

    public function templates(Request $request)
    {
        [$start, $end] = $this->dateRange($request);
        $limit = $this->limit($request, 10, 25);
        $type = $request->query('type');
        abort_unless(in_array($type, [null, 'festival', 'category', 'custom'], true), 422, 'Type must be festival, category, or custom.');

        $downloads = DB::table('user_activities')
            ->where('action', 'download_template')
            ->whereBetween('created_at', [$start, $end])
            ->selectRaw("JSON_UNQUOTE(JSON_EXTRACT(payload, '$.item_type')) as item_type")
            ->selectRaw("JSON_UNQUOTE(JSON_EXTRACT(payload, '$.item_id')) as item_id")
            ->selectRaw('COUNT(*) as downloads')
            ->groupBy(DB::raw("JSON_UNQUOTE(JSON_EXTRACT(payload, '$.item_type'))"))
            ->groupBy(DB::raw("JSON_UNQUOTE(JSON_EXTRACT(payload, '$.item_id'))"))
            ->orderByDesc('downloads')
            ->limit(100)
            ->get();

        $templates = $downloads
            ->map(function ($row) {
                $normalisedType = $this->normaliseTemplateType((string) $row->item_type);

                return [
                    'template_id' => is_numeric($row->item_id) ? (int) $row->item_id : $row->item_id,
                    'template_type' => $normalisedType,
                    'downloads' => (int) $row->downloads,
                    'title' => $this->templateTitle($normalisedType, $row->item_id),
                ];
            })
            ->filter(fn (array $template) => $type === null || $template['template_type'] === $type)
            ->take($limit)
            ->values();

        return $this->respond($request, [
            'period' => $this->period($start, $end),
            'metric' => 'download_template events',
            'templates' => $templates,
        ]);
    }

    public function reviews(Request $request)
    {
        [$start, $end] = $this->dateRange($request);
        if (!Schema::hasTable('play_store_reviews')) {
            return $this->respond($request, [
                'period' => $this->period($start, $end),
                'available' => false,
                'message' => 'The Play Store review cache has not been migrated yet.',
            ]);
        }

        $base = DB::table('play_store_reviews')->whereBetween('review_date', [$start, $end]);
        $ratings = (clone $base)
            ->select('star_rating', DB::raw('COUNT(*) as count'))
            ->groupBy('star_rating')
            ->pluck('count', 'star_rating');

        return $this->respond($request, [
            'period' => $this->period($start, $end),
            'available' => true,
            'total_reviews' => (clone $base)->count(),
            'average_rating' => round((float) ((clone $base)->avg('star_rating') ?: 0), 2),
            'positive_reviews' => (clone $base)->where('star_rating', '>=', 4)->count(),
            'rating_breakdown' => collect(range(1, 5))->mapWithKeys(fn (int $rating) => [(string) $rating => (int) ($ratings[$rating] ?? 0)]),
        ]);
    }

    public function searchUsers(Request $request)
    {
        $validated = $request->validate([
            'query' => ['required', 'string', 'min:2', 'max:120'],
            'page' => ['nullable', 'integer', 'min:1'],
            'per_page' => ['nullable', 'integer', 'min:1', 'max:' . config('mcp_analytics.max_page_size', 50)],
        ]);
        $query = trim($validated['query']);
        $perPage = (int) ($validated['per_page'] ?? 20);

        $users = DB::table('users as u')
            ->leftJoin('subscription as s', 'u.subscription_id', '=', 's.id')
            ->where(function ($builder) use ($query) {
                $builder->where('u.name', 'like', "%{$query}%")
                    ->orWhere('u.email', 'like', "%{$query}%")
                    ->orWhere('u.mobile_no', 'like', "%{$query}%")
                    ->orWhere('u.id', is_numeric($query) ? (int) $query : 0);
            })
            ->where(fn ($builder) => $builder->whereNull('u.user_type')->orWhereNotIn('u.user_type', ['A', 'Super Admin']))
            ->orderByDesc('u.created_at')
            ->paginate($perPage, ['u.id', 'u.name', 'u.email', 'u.mobile_no', 'u.created_at', 'u.is_subscribe', 'u.subscription_end_date', 's.plan_name'], 'page', $validated['page'] ?? 1);

        return $this->respond($request, [
            'query' => $query,
            'page' => $users->currentPage(),
            'per_page' => $users->perPage(),
            'total' => $users->total(),
            'users' => collect($users->items())->map(fn ($user) => [
                'id' => (int) $user->id,
                'name' => $user->name,
                'email' => $user->email,
                'mobile_no' => $user->mobile_no,
                'plan_name' => $user->plan_name,
                'is_subscribed' => (bool) $user->is_subscribe,
                'subscription_end_date' => $user->subscription_end_date,
                'registered_at' => optional(Carbon::parse($user->created_at))->toIso8601String(),
            ])->values(),
        ]);
    }

    public function userDetails(Request $request, int $userId)
    {
        $user = DB::table('users as u')
            ->leftJoin('subscription as s', 'u.subscription_id', '=', 's.id')
            ->where('u.id', $userId)
            ->where(fn ($builder) => $builder->whereNull('u.user_type')->orWhereNotIn('u.user_type', ['A', 'Super Admin']))
            ->first(['u.id', 'u.name', 'u.email', 'u.mobile_no', 'u.created_at', 'u.is_subscribe', 'u.subscription_start_date', 'u.subscription_end_date', 'u.current_streak', 'u.max_streak', 's.plan_name']);
        abort_unless($user, 404, 'User not found.');

        $businesses = DB::table('business as b')
            ->leftJoin('business_category as c', 'b.business_category_id', '=', 'c.id')
            ->where('b.user_id', $userId)
            ->orderByDesc('b.is_default')
            ->get(['b.id', 'b.name', 'b.is_default', 'b.status', 'c.name as category_name'])
            ->map(fn ($business) => [
                'id' => (int) $business->id,
                'name' => $business->name,
                'category_name' => $business->category_name,
                'is_default' => (bool) $business->is_default,
                'is_active' => (bool) $business->status,
            ])->values();

        $payments = DB::table('transaction')
            ->where('user_id', $userId)
            ->orderByDesc('created_at')
            ->limit(10)
            ->get(['total_paid', 'payment_type', 'status', 'date', 'created_at'])
            ->map(fn ($payment) => [
                'amount' => (float) $payment->total_paid,
                'payment_type' => $payment->payment_type,
                'status' => $payment->status,
                'date' => $payment->date,
                'recorded_at' => optional(Carbon::parse($payment->created_at))->toIso8601String(),
            ])->values();

        $ticketCounts = Schema::hasTable('tickets')
            ? DB::table('tickets')->where('user_id', $userId)->select('status', DB::raw('COUNT(*) as count'))->groupBy('status')->pluck('count', 'status')
            : collect();

        return $this->respond($request, [
            'user' => [
                'id' => (int) $user->id,
                'name' => $user->name,
                'email' => $user->email,
                'mobile_no' => $user->mobile_no,
                'plan_name' => $user->plan_name,
                'is_subscribed' => (bool) $user->is_subscribe,
                'subscription_start_date' => $user->subscription_start_date,
                'subscription_end_date' => $user->subscription_end_date,
                'registered_at' => optional(Carbon::parse($user->created_at))->toIso8601String(),
                'current_streak' => (int) ($user->current_streak ?? 0),
                'max_streak' => (int) ($user->max_streak ?? 0),
            ],
            'businesses' => $businesses,
            'recent_payments' => $payments,
            'ticket_counts' => $ticketCounts,
            'activity_summary' => [
                'total_events' => DB::table('user_activities')->where('user_id', $userId)->count(),
                'template_downloads' => DB::table('user_activities')->where('user_id', $userId)->where('action', 'download_template')->count(),
                'last_activity_at' => DB::table('user_activities')->where('user_id', $userId)->max('created_at'),
            ],
        ]);
    }

    public function userActivity(Request $request, int $userId)
    {
        $validated = $request->validate([
            'page' => ['nullable', 'integer', 'min:1'],
            'per_page' => ['nullable', 'integer', 'min:1', 'max:' . config('mcp_analytics.max_page_size', 50)],
            'start_date' => ['nullable', 'date_format:Y-m-d'],
            'end_date' => ['nullable', 'date_format:Y-m-d'],
        ]);
        [$start, $end] = $this->dateRange($request);
        $perPage = (int) ($validated['per_page'] ?? 20);

        $activity = DB::table('user_activities')
            ->where('user_id', $userId)
            ->whereBetween('created_at', [$start, $end])
            ->orderByDesc('created_at')
            ->paginate($perPage, ['id', 'action', 'method', 'created_at'], 'page', $validated['page'] ?? 1);

        return $this->respond($request, [
            'user_id' => $userId,
            'period' => $this->period($start, $end),
            'page' => $activity->currentPage(),
            'per_page' => $activity->perPage(),
            'total' => $activity->total(),
            'activities' => collect($activity->items())->map(fn ($item) => [
                'id' => (int) $item->id,
                'action' => $item->action,
                'method' => $item->method,
                'occurred_at' => optional(Carbon::parse($item->created_at))->toIso8601String(),
            ])->values(),
        ]);
    }

    private function installData(Carbon $start, Carbon $end): array
    {
        if (!Schema::hasTable('app_install_events')) {
            return ['available' => false, 'message' => 'App install telemetry is unavailable.'];
        }

        $installs = DB::table('app_install_events')->where('event_type', 'install')->whereBetween('occurred_at', [$start, $end]);
        $uniqueInstalls = DB::table('app_install_events')
            ->select('device_hash')
            ->where('event_type', 'install')
            ->groupBy('device_hash')
            ->havingRaw('MIN(occurred_at) BETWEEN ? AND ?', [$start, $end])
            ->count();

        return [
            'available' => true,
            'total_installs' => (clone $installs)->count(),
            'unique_first_installs' => $uniqueInstalls,
            'uninstalls' => DB::table('app_install_events')->where('event_type', 'uninstall')->whereBetween('occurred_at', [$start, $end])->count(),
        ];
    }

    private function salesData(Carbon $start, Carbon $end): array
    {
        $sales = DB::table('transaction as t')
            ->leftJoin('subscription as s', 't.subscription_id', '=', 's.id')
            ->whereBetween('t.created_at', [$start, $end])
            ->whereIn('t.status', ['1', 1, 'Completed', 'completed']);

        return [
            'completed_purchases' => (clone $sales)->count(),
            'revenue_inr' => round((float) ((clone $sales)->sum('t.total_paid') ?: 0), 2),
            'paying_users' => (clone $sales)->distinct('t.user_id')->count('t.user_id'),
            'by_plan' => (clone $sales)
                ->selectRaw("COALESCE(s.plan_name, 'Unknown plan') as plan_name")
                ->selectRaw('COUNT(*) as purchases')
                ->selectRaw('COALESCE(SUM(t.total_paid), 0) as revenue_inr')
                ->groupBy('s.plan_name')
                ->orderByDesc('revenue_inr')
                ->limit(20)
                ->get()
                ->map(fn ($plan) => ['plan_name' => $plan->plan_name, 'purchases' => (int) $plan->purchases, 'revenue_inr' => (float) $plan->revenue_inr])
                ->values(),
        ];
    }

    private function adRevenueData(Carbon $start, Carbon $end): array
    {
        if (!Schema::hasTable('ad_events')) {
            return ['available' => false, 'message' => 'Ad event telemetry is unavailable.'];
        }

        $counts = DB::table('ad_events')
            ->whereBetween('created_at', [$start, $end])
            ->select('ad_type', DB::raw('COUNT(*) as count'))
            ->groupBy('ad_type')
            ->pluck('count', 'ad_type');
        $banner = (int) ($counts['banner'] ?? 0);
        $interstitial = (int) ($counts['interstitial'] ?? 0);
        $rewarded = (int) ($counts['rewarded'] ?? 0);

        return [
            'available' => true,
            'revenue_type' => 'estimated',
            'actual_revenue_available' => false,
            'message' => 'This is an event-count estimate only. Configure AdMob reporting before relying on it for accounting.',
            'events' => ['banner' => $banner, 'interstitial' => $interstitial, 'rewarded' => $rewarded],
            'estimated_revenue_inr' => round(($banner * 25 / 1000) + ($interstitial * 65 / 1000) + ($rewarded * 170 / 1000), 2),
        ];
    }

    private function ticketData(Carbon $start, Carbon $end): array
    {
        if (!Schema::hasTable('tickets')) {
            return ['available' => false, 'message' => 'Support tickets are unavailable.'];
        }

        $tickets = DB::table('tickets')->whereBetween('created_at', [$start, $end]);
        $byStatus = (clone $tickets)->select('status', DB::raw('COUNT(*) as count'))->groupBy('status')->pluck('count', 'status');
        $byCategory = (clone $tickets)->select('category', DB::raw('COUNT(*) as count'))->groupBy('category')->orderByDesc('count')->limit(10)->get();

        return [
            'available' => true,
            'total_tickets' => (clone $tickets)->count(),
            'open' => (int) ($byStatus['open'] ?? 0),
            'in_progress' => (int) ($byStatus['in_progress'] ?? 0),
            'ai_resolved' => (int) ($byStatus['ai_resolved'] ?? 0),
            'closed' => (int) ($byStatus['closed'] ?? 0),
            'by_category' => $byCategory->map(fn ($row) => ['category' => $row->category, 'count' => (int) $row->count])->values(),
        ];
    }

    private function registeredUsers(Carbon $start, Carbon $end): int
    {
        return DB::table('users')
            ->where(fn ($builder) => $builder->whereNull('user_type')->orWhereNotIn('user_type', ['A', 'Super Admin']))
            ->whereBetween('created_at', [$start, $end])
            ->count();
    }

    private function templateTitle(string $type, $id): string
    {
        if (!is_numeric($id)) {
            return 'Unknown template';
        }

        if ($type === 'festival') {
            return (string) (DB::table('festivals_post as post')->leftJoin('festivals as festival', 'post.festivals_id', '=', 'festival.id')->where('post.id', $id)->value('festival.title') ?? "Festival template #{$id}");
        }
        if ($type === 'category') {
            return (string) (DB::table('category_post as post')->leftJoin('category as category', 'post.category_id', '=', 'category.id')->where('post.id', $id)->value('category.name') ?? "Category template #{$id}");
        }

        return (string) (DB::table('business_frame as frame')->leftJoin('business_category as category', 'frame.business_category_id', '=', 'category.id')->where('frame.id', $id)->value('category.name') ?? "Custom template #{$id}");
    }

    private function normaliseTemplateType(string $type): string
    {
        return in_array($type, ['custom', 'business_custom_frame', 'business_frame'], true) ? 'custom' : $type;
    }

    private function dateRange(Request $request): array
    {
        $request->validate([
            'start_date' => ['nullable', 'date_format:Y-m-d'],
            'end_date' => ['nullable', 'date_format:Y-m-d'],
        ]);
        $start = $request->filled('start_date') ? Carbon::createFromFormat('Y-m-d', $request->input('start_date'))->startOfDay() : now()->startOfDay();
        $end = $request->filled('end_date') ? Carbon::createFromFormat('Y-m-d', $request->input('end_date'))->endOfDay() : now()->endOfDay();
        abort_if($end->lt($start), 422, 'End date must not be before start date.');
        abort_if($start->diffInDays($end) > config('mcp_analytics.max_date_range_days', 366), 422, 'Requested date range is too large.');

        return [$start, $end];
    }

    private function limit(Request $request, int $default, int $maximum): int
    {
        $value = $request->query('limit', $default);
        abort_unless(filter_var($value, FILTER_VALIDATE_INT) !== false && (int) $value >= 1 && (int) $value <= $maximum, 422, 'Invalid limit.');

        return (int) $value;
    }

    private function period(Carbon $start, Carbon $end): array
    {
        return ['start_date' => $start->toDateString(), 'end_date' => $end->toDateString()];
    }

    private function respond(Request $request, array $data)
    {
        Log::info('Read-only MCP analytics request completed.', [
            'actor_id' => $request->attributes->get('mcp_analytics_user_id'),
            'endpoint' => optional($request->route())->uri(),
        ]);

        return response()->json(['success' => true, 'data' => $data]);
    }
}
