<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use App\Models\AiTokenLog;
use Illuminate\Support\Facades\DB;

class AiAnalyticsController extends Controller
{
    public function index(Request $request)
    {
        $startDate = $request->input('start_date');
        $endDate = $request->input('end_date');

        $query = AiTokenLog::query();

        if ($startDate) {
            $query->whereDate('created_at', '>=', $startDate);
        }
        if ($endDate) {
            $query->whereDate('created_at', '<=', $endDate);
        }

        // Summary KPI
        $summary = (clone $query)->select(
            DB::raw('COUNT(id) as total_requests'),
            DB::raw('SUM(prompt_tokens) as total_prompt'),
            DB::raw('SUM(completion_tokens) as total_completion'),
            DB::raw('SUM(cost_inr) as total_cost')
        )->first();

        // Feature-wise Breakdown
        $featureStats = (clone $query)->select(
            'feature_name',
            DB::raw('COUNT(id) as requests'),
            DB::raw('SUM(total_tokens) as tokens'),
            DB::raw('SUM(cost_inr) as cost')
        )->groupBy('feature_name')->get();

        // User-wise Breakdown
        $userStats = (clone $query)->select(
            'user_id',
            DB::raw('COUNT(id) as requests'),
            DB::raw('SUM(total_tokens) as tokens'),
            DB::raw('SUM(cost_inr) as cost')
        )->with('user')->groupBy('user_id')->get();
        
        // Model-wise Breakdown
        $modelStats = (clone $query)->select(
            'model',
            DB::raw('COUNT(id) as requests'),
            DB::raw('SUM(total_tokens) as tokens'),
            DB::raw('SUM(cost_inr) as cost')
        )->groupBy('model')->get();

        return view('backend.ai_analytics', compact('summary', 'featureStats', 'userStats', 'modelStats', 'startDate', 'endDate'));
    }
}
