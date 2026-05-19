<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use App\Models\User;
use App\Models\UserActivity;
use App\Models\AdEvent;
use App\Models\Transaction;
use Illuminate\Support\Facades\DB;

class UserPerformanceController extends Controller
{
    public function index(Request $request)
    {
        $period = $request->input('period');
        $startDate = $request->input('start_date');
        $endDate = $request->input('end_date', now()->format('Y-m-d'));

        if ($period) {
            switch ($period) {
                case 'hour':
                    $startDate = now()->subHour()->format('Y-m-d H:i:s');
                    $endDate = now()->format('Y-m-d H:i:s');
                    break;
                case 'day':
                    $startDate = now()->startOfDay()->format('Y-m-d H:i:s');
                    $endDate = now()->endOfDay()->format('Y-m-d H:i:s');
                    break;
                case 'month':
                    $startDate = now()->startOfMonth()->format('Y-m-d H:i:s');
                    $endDate = now()->endOfMonth()->format('Y-m-d H:i:s');
                    break;
                case 'year':
                    $startDate = now()->startOfYear()->format('Y-m-d H:i:s');
                    $endDate = now()->endOfYear()->format('Y-m-d H:i:s');
                    break;
            }
        }

        if (empty($startDate)) {
            $firstUser = User::whereNotIn('user_type', ['A', 'Super Admin'])->orderBy('created_at', 'asc')->first();
            $startDate = $firstUser ? $firstUser->created_at->format('Y-m-d') : '2026-01-01';
        }
        if (empty($endDate)) {
            $endDate = now()->format('Y-m-d');
        }

        // Summary Stats (Excluding Admins) - Always showing lifetime totals
        $totalRegistered = User::whereNotIn('user_type', ['A', 'Super Admin'])->count();
        $totalPurchased = User::whereNotIn('user_type', ['A', 'Super Admin'])->whereNotNull('subscription_id')->count();
        
        $totalBusinesses = DB::table('business')->count();
        $businessStats = DB::table('business')
            ->join('business_category', 'business.business_category_id', '=', 'business_category.id')
            ->select('business_category.name', DB::raw('count(*) as count'))
            ->groupBy('business_category.name')
            ->orderBy('count', 'desc')
            ->get();

        // Activity Counts (Usage Stats - Excluding Admins)
        $usageStatsData = UserActivity::join('users', 'user_activities.user_id', '=', 'users.id')
            ->whereNotIn('users.user_type', ['A', 'Super Admin'])
            ->where('user_activities.action', 'download_template')
            ->whereDate('user_activities.created_at', '>=', $startDate)
            ->whereDate('user_activities.created_at', '<=', $endDate)
            ->select(
                DB::raw('JSON_UNQUOTE(JSON_EXTRACT(user_activities.payload, "$.item_type")) as item_type'),
                DB::raw('count(*) as count')
            )
            ->groupBy(DB::raw('JSON_UNQUOTE(JSON_EXTRACT(user_activities.payload, "$.item_type"))'))
            ->get();

        $formattedUsage = ['festival' => 0, 'category' => 0, 'custom' => 0];
        foreach($usageStatsData as $s) {
            $type = $s->item_type;
            if ($type == 'festival') $formattedUsage['festival'] = $s->count;
            elseif ($type == 'category') $formattedUsage['category'] = $s->count;
            elseif (in_array($type, ['custom', 'business_custom_frame', 'business_frame'])) $formattedUsage['custom'] += $s->count;
        }
        $usageStats = collect($formattedUsage);

        // Funnel Stats (User Counts - Excluding Admins)
        $funnel = [
            'step1_home' => UserActivity::join('users', 'user_activities.user_id', '=', 'users.id')
                ->whereNotIn('users.user_type', ['A', 'Super Admin'])
                ->whereIn('user_activities.action', ['home_visit', 'MainController@index'])
                ->whereDate('user_activities.created_at', '>=', $startDate)
                ->whereDate('user_activities.created_at', '<=', $endDate)
                ->distinct('user_activities.user_id')
                ->count(),
            'step2_template' => UserActivity::join('users', 'user_activities.user_id', '=', 'users.id')
                ->whereNotIn('users.user_type', ['A', 'Super Admin'])
                ->whereIn('user_activities.action', ['select_template', 'MainController@universal_details', 'MainController@festival_details'])
                ->whereDate('user_activities.created_at', '>=', $startDate)
                ->whereDate('user_activities.created_at', '<=', $endDate)
                ->distinct('user_activities.user_id')
                ->count(),
            'step3_download' => UserActivity::join('users', 'user_activities.user_id', '=', 'users.id')
                ->whereNotIn('users.user_type', ['A', 'Super Admin'])
                ->where('user_activities.action', 'download_template')
                ->whereDate('user_activities.created_at', '>=', $startDate)
                ->whereDate('user_activities.created_at', '<=', $endDate)
                ->distinct('user_activities.user_id')
                ->count(),
        ];

        // ── Ad Analytics (from ad_events table) ──
        $adAnalytics = ['banner' => 0, 'interstitial' => 0, 'rewarded' => 0, 'daily' => [], 'revenue' => 0];
        if (\Schema::hasTable('ad_events')) {
            $adCounts = AdEvent::whereDate('created_at', '>=', $startDate)
                ->whereDate('created_at', '<=', $endDate)
                ->select('ad_type', DB::raw('count(*) as count'))
                ->groupBy('ad_type')
                ->get();
            foreach ($adCounts as $ac) {
                $adAnalytics[$ac->ad_type] = $ac->count;
            }

            // Daily trend for chart (last 14 days or date range)
            $adDaily = AdEvent::whereDate('created_at', '>=', $startDate)
                ->whereDate('created_at', '<=', $endDate)
                ->select(
                    DB::raw('DATE(created_at) as date'),
                    'ad_type',
                    DB::raw('count(*) as count')
                )
                ->groupBy(DB::raw('DATE(created_at)'), 'ad_type')
                ->orderBy('date')
                ->get();

            $dailyMap = [];
            foreach ($adDaily as $d) {
                $dailyMap[$d->date][$d->ad_type] = $d->count;
            }
            $adAnalytics['daily'] = $dailyMap;

            // Estimated Revenue (India eCPM rates in INR)
            // Banner: ₹25/1000, Interstitial: ₹65/1000, Rewarded: ₹170/1000
            $adAnalytics['revenue'] = round(
                ($adAnalytics['banner'] * 25 / 1000) +
                ($adAnalytics['interstitial'] * 65 / 1000) +
                ($adAnalytics['rewarded'] * 170 / 1000),
                2
            );
        }

        // Detailed Activity Log (Excluding Admins)
        $userId = $request->input('user_id');
        $trackedUser = null;
        
        $activityLogsQuery = UserActivity::with('user')
            ->whereHas('user', function($q) {
                $q->whereNotIn('user_type', ['A', 'Super Admin']);
            })
            ->whereDate('created_at', '>=', $startDate)
            ->whereDate('created_at', '<=', $endDate);

        if ($userId) {
            $activityLogsQuery->where('user_id', $userId);
            $trackedUser = User::find($userId);
        }

        $activityLogs = $activityLogsQuery->orderBy('created_at', 'desc')
            ->paginate(15);

        // Registered Users List (Excluding Admins)
        $searchUser = $request->input('search_user');
        $usersQuery = User::with(['subscription'])
            ->whereNotIn('user_type', ['A', 'Super Admin']);

        if ($searchUser) {
            $usersQuery->where(function($q) use ($searchUser) {
                $q->where('name', 'like', "%{$searchUser}%")
                  ->orWhere('email', 'like', "%{$searchUser}%")
                  ->orWhere('mobile_no', 'like', "%{$searchUser}%");
            });
        }

        $users = $usersQuery->orderBy('created_at', 'desc')
            ->paginate(15);

        // AJAX handling for live search
        if ($request->ajax()) {
            return view('backend.user_performance', compact(
                'users', 'totalRegistered', 'totalPurchased', 'totalBusinesses', 'businessStats', 'usageStats', 'funnel', 'activityLogs', 'startDate', 'endDate', 'userId', 'trackedUser', 'searchUser', 'adAnalytics'
            ))->render();
        }

        return view('backend.user_performance', compact(
            'users', 'totalRegistered', 'totalPurchased', 'totalBusinesses', 'businessStats', 'usageStats', 'funnel', 'activityLogs', 'startDate', 'endDate', 'userId', 'trackedUser', 'searchUser', 'adAnalytics'
        ));
    }

    public function details(Request $request)
    {
        $type = $request->input('type');
        $startDate = $request->input('start_date');
        $endDate = $request->input('end_date', now()->format('Y-m-d'));
        $userId = $request->input('user_id');
        $postType = $request->input('post_type'); // festival, custom, general

        // Default start date logic (same as index)
        if (empty($startDate)) {
            $firstUser = User::whereNotIn('user_type', ['A', 'Super Admin'])->orderBy('created_at', 'asc')->first();
            $startDate = $firstUser ? $firstUser->created_at->format('Y-m-d') : '2026-01-01';
        }

        $data = [];
        $title = "Details";
        $summary = null;

        if ($type == 'registrations' || $type == 'premium') {
            $query = User::with(['subscription'])
                ->whereNotIn('user_type', ['A', 'Super Admin'])
                ->whereDate('created_at', '>=', $startDate)
                ->whereDate('created_at', '<=', $endDate);

            if ($type == 'premium') {
                $query->whereNotNull('subscription_id');
                $title = "Premium Users";
            } elseif ($type == 'registrations') {
                $title = "Registered Users";
            }

            if ($userId) {
                $query->where('id', $userId);
            }

            $data = $query->orderBy('created_at', 'desc')->paginate(15);
        } elseif ($type == 'businesses') {
            $title = "Registered Businesses";
            $query = DB::table('business')
                ->leftJoin('users', 'business.user_id', '=', 'users.id')
                ->leftJoin('business_category', 'business.business_category_id', '=', 'business_category.id')
                ->select('business.*', 'users.name as user_name', 'business_category.name as category_name')
                ->whereDate('business.created_at', '>=', $startDate)
                ->whereDate('business.created_at', '<=', $endDate);
            
            $data = $query->orderBy('business.created_at', 'desc')->paginate(15);
        } elseif ($type == 'funnel_step3') {
            $title = "Downloaded Templates (Step 3)";
            $query = UserActivity::with('user')
                ->whereHas('user', function($q) {
                    $q->whereNotIn('user_type', ['A', 'Super Admin']);
                })
                ->where('action', 'download_template')
                ->whereDate('created_at', '>=', $startDate)
                ->whereDate('created_at', '<=', $endDate);

            if ($userId) {
                $query->where('user_id', $userId);
            }

            $data = $query->orderBy('created_at', 'desc')->paginate(15);
        } elseif ($type == 'template_performance') {
            $postType = $request->input('post_type'); // festival, category, custom
            $search = $request->input('search');
            $title = "All " . ucfirst($postType) . " Templates Performance";

            // 1. Get download counts for all templates in this category
            $countsQuery = UserActivity::where('action', 'download_template')
                ->whereDate('created_at', '>=', $startDate)
                ->whereDate('created_at', '<=', $endDate);

            if ($postType == 'custom') {
                $countsQuery->whereIn(DB::raw('JSON_UNQUOTE(JSON_EXTRACT(payload, "$.item_type"))'), ['custom', 'business_custom_frame', 'business_frame']);
            } elseif ($postType == 'festival') {
                $countsQuery->whereRaw('JSON_UNQUOTE(JSON_EXTRACT(payload, "$.item_type")) = "festival"');
            } else {
                $countsQuery->whereRaw('JSON_UNQUOTE(JSON_EXTRACT(payload, "$.item_type")) = "category"');
            }

            $countsData = $countsQuery->select(
                    DB::raw('JSON_UNQUOTE(JSON_EXTRACT(payload, "$.item_type")) as item_type'),
                    DB::raw('JSON_UNQUOTE(JSON_EXTRACT(payload, "$.item_id")) as item_id'),
                    DB::raw('count(*) as download_count')
                )
                ->groupBy(
                    DB::raw('JSON_UNQUOTE(JSON_EXTRACT(payload, "$.item_type"))'),
                    DB::raw('JSON_UNQUOTE(JSON_EXTRACT(payload, "$.item_id"))')
                )
                ->get();

            // 2. Fetch all templates from the primary table(s)
            $dataList = [];
            if ($postType == 'festival') {
                $query = DB::table('festivals_post')
                    ->leftJoin('festivals', 'festivals_post.festivals_id', '=', 'festivals.id')
                    ->select('festivals_post.*', 'festivals.title as festival_title');
                if ($search) {
                    $cleanSearch = preg_replace('/^FEST-/i', '', $search);
                    $query->where(function($q) use ($cleanSearch, $search) {
                        $q->where('festivals_post.id', 'LIKE', "%{$cleanSearch}%")
                          ->orWhere('festivals.title', 'LIKE', "%{$search}%");
                    });
                }
                foreach ($query->orderBy('festivals_post.id', 'desc')->get() as $template) {
                    $downloadCount = $countsData->where('item_id', $template->id)->where('item_type', 'festival')->first()->download_count ?? 0;
                    $dataList[] = (object)[
                        'id' => 'FEST-' . $template->id,
                        'frame_image' => $template->frame_image ?? null,
                        'name' => $template->festival_title ?? "Festival Post #" . $template->id,
                        'download_count' => $downloadCount
                    ];
                }
            } elseif ($postType == 'custom') {
                // business_frame
                $bfQuery = DB::table('business_frame')
                    ->leftJoin('business_category', 'business_frame.business_category_id', '=', 'business_category.id')
                    ->select('business_frame.*', 'business_category.name as category_name');
                if ($search) {
                    $cleanSearch = preg_replace('/^CUST-/i', '', $search);
                    $bfQuery->where(function($q) use ($cleanSearch, $search) {
                        $q->where('business_frame.id', 'LIKE', "%{$cleanSearch}%")
                          ->orWhere('business_category.name', 'LIKE', "%{$search}%");
                    });
                }
                foreach ($bfQuery->orderBy('business_frame.id', 'desc')->get() as $template) {
                    $downloadCount = $countsData->where('item_id', $template->id)
                        ->whereIn('item_type', ['custom', 'business_frame'])
                        ->sum('download_count');
                    
                    $dataList[] = (object)[
                        'id' => 'CUST-' . $template->id,
                        'frame_image' => $template->frame_image ?? null,
                        'name' => $template->category_name ?? "Custom Frame #" . $template->id,
                        'download_count' => $downloadCount
                    ];
                }

                // business_custom_frames
                $bcfQuery = DB::table('business_custom_frames')
                    ->leftJoin('custom_frame_purposes', 'business_custom_frames.custom_frame_purpose_id', '=', 'custom_frame_purposes.id')
                    ->select('business_custom_frames.*', 'custom_frame_purposes.name as purpose_name');
                if ($search) {
                    $cleanSearch = preg_replace('/^CUST-/i', '', $search);
                    $bcfQuery->where(function($q) use ($cleanSearch, $search) {
                        $q->where('business_custom_frames.id', 'LIKE', "%{$cleanSearch}%")
                          ->orWhere('custom_frame_purposes.name', 'LIKE', "%{$search}%");
                    });
                }
                foreach ($bcfQuery->orderBy('business_custom_frames.id', 'desc')->get() as $template) {
                    $downloadCount = $countsData->where('item_id', $template->id)->where('item_type', 'business_custom_frame')->first()->download_count ?? 0;
                    
                    // Try to fetch the latest download to show as a preview
                    $lastDownload = UserActivity::where('action', 'download_template')
                        ->whereRaw('JSON_UNQUOTE(JSON_EXTRACT(payload, "$.item_type")) = "business_custom_frame"')
                        ->whereRaw('JSON_UNQUOTE(JSON_EXTRACT(payload, "$.item_id")) = ?', [$template->id])
                        ->latest()
                        ->first();
                    
                    $previewImage = null;
                    if ($lastDownload && isset($lastDownload->payload['downloaded_image'])) {
                        $previewImage = $lastDownload->payload['downloaded_image'];
                    }

                    $dataList[] = (object)[
                        'id' => 'CUST-' . $template->id,
                        'frame_image' => $previewImage, // BusinessCustomFrames use the last download as preview
                        'name' => $template->purpose_name ?? "Business Custom Frame #" . $template->id,
                        'download_count' => $downloadCount
                    ];
                }
            } else {
                $query = DB::table('category_post')
                    ->leftJoin('category', 'category_post.category_id', '=', 'category.id')
                    ->select('category_post.*', 'category.name as category_name');
                if ($search) {
                    $cleanSearch = preg_replace('/^CAT-/i', '', $search);
                    $query->where(function($q) use ($cleanSearch, $search) {
                        $q->where('category_post.id', 'LIKE', "%{$cleanSearch}%")
                          ->orWhere('category.name', 'LIKE', "%{$search}%");
                    });
                }
                foreach ($query->orderBy('category_post.id', 'desc')->get() as $template) {
                    $downloadCount = $countsData->where('item_id', $template->id)->where('item_type', 'category')->first()->download_count ?? 0;
                    $dataList[] = (object)[
                        'id' => 'CAT-' . $template->id,
                        'frame_image' => $template->frame_image ?? null,
                        'name' => $template->category_name ?? "Category Post #" . $template->id,
                        'download_count' => $downloadCount
                    ];
                }
            }

            // 3. Sort by download count descending
            $data = collect($dataList)->sortByDesc('download_count');
        } elseif ($type == 'user_session_tracking') {
            $userId = $request->input('user_id');
            $trackedUser = User::find($userId);
            
            if (!$trackedUser) {
                return redirect()->route('admin.user_performance')->with('error', 'User not found');
            }

            $title = "Live Activity Tracking: " . $trackedUser->name;

            // Fetch all activities for this user
            $activities = UserActivity::where('user_id', $userId)
                ->whereDate('created_at', '>=', $startDate)
                ->whereDate('created_at', '<=', $endDate)
                ->orderBy('created_at', 'asc')
                ->get();

            // Group by sessions (1 hour gap)
            $sessions = [];
            $currentSession = null;
            foreach ($activities as $act) {
                $time = $act->created_at;
                if ($currentSession === null || $time->diffInMinutes($currentSession['last_time']) > 60) {
                    if ($currentSession) $sessions[] = $currentSession;
                    $currentSession = [
                        'id' => uniqid(),
                        'start_time' => $time,
                        'last_time' => $time,
                        'ip' => $act->ip_address,
                        'platform' => $act->payload['platform'] ?? 'Web',
                        'funnel' => [
                            'step1' => null, // home
                            'step2' => [],   // browsed templates
                            'step3' => []    // downloaded templates
                        ]
                    ];
                }

                $currentSession['last_time'] = $time;
                
                // Map to funnel steps
                if (in_array($act->action, ['home_visit', 'MainController@index'])) {
                    $currentSession['funnel']['step1'] = $time;
                } elseif (in_array($act->action, ['select_template', 'MainController@universal_details', 'MainController@festival_details'])) {
                    $itemId = $act->payload['item_id'] ?? null;
                    $itemType = $act->payload['item_type'] ?? null;
                    if (in_array($itemType, ['business_custom_frame', 'business_frame'])) $itemType = 'custom';

                    // Fallback for web browsing where payload is empty
                    if (!$itemId && $act->url) {
                        if (preg_match('/details\/(festival|category|custom|business_custom_frame)\/(\d+)/i', $act->url, $matches)) {
                            $itemType = $matches[1];
                            if (in_array($itemType, ['business_custom_frame', 'business_frame'])) $itemType = 'custom';
                            $itemId = $matches[2];
                        }
                    }

                    if ($itemId) {
                        $currentSession['funnel']['step2'][] = [
                            'time' => $time,
                            'item_id' => $itemId,
                            'type' => $itemType ?? 'category'
                        ];
                    }
                } elseif ($act->action == 'download_template') {
                    $itemId = $act->payload['item_id'] ?? '?';
                    $itemType = $act->payload['item_type'] ?? '?';
                    if (in_array($itemType, ['business_custom_frame', 'business_frame'])) $itemType = 'custom';

                    $currentSession['funnel']['step3'][] = [
                        'time' => $time,
                        'item_id' => $itemId,
                        'item_type' => $itemType,
                        'image' => $act->payload['downloaded_image'] ?? null
                    ];
                }
            }
            if ($currentSession) $sessions[] = $currentSession;
            
            // Order sessions by latest first
            $data = array_reverse($sessions);
        }

        $allUsers = User::whereNotIn('user_type', ['A', 'Super Admin'])->orderBy('name')->get();

        return view('backend.user_performance_details', compact('data', 'type', 'title', 'startDate', 'endDate', 'summary', 'userId', 'postType', 'allUsers'));
    }
}

