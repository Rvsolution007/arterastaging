<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use App\Models\ClientError;
use App\Models\AiSetting;
use App\Services\ErrorAnalysisService;

class ClientErrorController extends Controller
{
    public function index(Request $request)
    {
        $query = ClientError::with('user')->orderBy('created_at', 'desc');

        // Apply filters
        if ($request->filled('severity')) {
            $query->where('ai_severity', $request->severity);
        }
        if ($request->filled('category')) {
            $query->where('ai_category', $request->category);
        }
        if ($request->filled('ux_only') && $request->ux_only == '1') {
            $query->where('ai_is_ux_bug', true);
        }
        if ($request->filled('status')) {
            $query->where('status', $request->status);
        }
        if ($request->filled('pattern')) {
            $query->where('ai_pattern_group', $request->pattern);
        }
        if ($request->filled('analyzed')) {
            if ($request->analyzed == 'yes') {
                $query->whereNotNull('ai_analyzed_at');
            } else {
                $query->whereNull('ai_analyzed_at');
            }
        }

        $errors = $query->paginate(20);

        // AI Stats
        $totalErrors = ClientError::count();
        $analyzedCount = ClientError::analyzed()->count();
        $criticalCount = ClientError::where('ai_severity', 'critical')->count();
        $highCount = ClientError::where('ai_severity', 'high')->count();
        $uxBugCount = ClientError::where('ai_is_ux_bug', true)->count();
        $pendingAnalysis = ClientError::unanalyzed()->count();

        // Pattern groups with counts
        $patternGroups = ClientError::whereNotNull('ai_pattern_group')
            ->selectRaw('ai_pattern_group, COUNT(*) as count, MAX(ai_severity) as max_severity')
            ->groupBy('ai_pattern_group')
            ->orderByRaw("FIELD(MAX(ai_severity), 'critical', 'high', 'medium', 'low', 'info')")
            ->orderBy('count', 'desc')
            ->limit(10)
            ->get();

        // Top critical issues
        $criticalIssues = ClientError::whereIn('ai_severity', ['critical', 'high'])
            ->whereNotNull('ai_analyzed_at')
            ->orderByRaw("FIELD(ai_severity, 'critical', 'high')")
            ->orderBy('created_at', 'desc')
            ->limit(5)
            ->get();

        // Categories distribution
        $categories = ClientError::whereNotNull('ai_category')
            ->selectRaw('ai_category, COUNT(*) as count')
            ->groupBy('ai_category')
            ->orderBy('count', 'desc')
            ->get();

        // Auto-analyze setting
        $autoAnalyzeEnabled = ErrorAnalysisService::isAutoAnalyzeEnabled();

        return view('admin.client_errors.index', compact(
            'errors', 'totalErrors', 'analyzedCount', 'criticalCount', 'highCount',
            'uxBugCount', 'pendingAnalysis', 'patternGroups', 'criticalIssues',
            'categories', 'autoAnalyzeEnabled'
        ));
    }

    /**
     * Analyze a single error with AI (AJAX).
     */
    public function analyzeWithAi($id)
    {
        $success = ErrorAnalysisService::analyze($id);

        if ($success) {
            $error = ClientError::find($id);
            return response()->json([
                'status' => 'success',
                'message' => 'AI analysis complete!',
                'data' => [
                    'severity' => $error->ai_severity,
                    'category' => $error->ai_category,
                    'root_cause' => $error->ai_root_cause,
                    'suggested_fix' => $error->ai_suggested_fix,
                    'confidence' => $error->ai_confidence,
                    'is_ux_bug' => $error->ai_is_ux_bug,
                    'pattern_group' => $error->ai_pattern_group,
                    'severity_color' => $error->severity_color,
                ]
            ]);
        }

        return response()->json([
            'status' => 'error',
            'message' => 'AI analysis failed. Check AI settings in admin.'
        ], 500);
    }

    /**
     * Batch analyze all unanalyzed errors (AJAX).
     */
    public function batchAnalyze(Request $request)
    {
        $limit = (int)($request->limit ?? 20);
        $count = ErrorAnalysisService::batchAnalyze($limit);

        return response()->json([
            'status' => 'success',
            'message' => "AI analyzed {$count} errors successfully.",
            'analyzed' => $count,
        ]);
    }

    /**
     * Toggle auto-analyze setting (AJAX).
     */
    public function toggleAutoAnalyze(Request $request)
    {
        $enabled = $request->enabled ? '1' : '0';
        
        $setting = AiSetting::where('key_name', 'error_auto_analyze')->first();
        if ($setting) {
            $setting->update(['key_value' => $enabled]);
        } else {
            AiSetting::create(['key_name' => 'error_auto_analyze', 'key_value' => $enabled]);
        }

        return response()->json([
            'status' => 'success',
            'message' => $enabled === '1' ? 'Auto-analyze enabled. AI will scan errors daily.' : 'Auto-analyze disabled.',
            'enabled' => $enabled === '1',
        ]);
    }

    public function destroy($id)
    {
        $error = ClientError::findOrFail($id);
        $error->delete();
        return redirect()->back()->with('success', 'Error report deleted successfully.');
    }

    public function updateStatus(Request $request, $id)
    {
        $error = ClientError::findOrFail($id);
        $error->update(['status' => $request->status]);
        return response()->json(['status' => 'success', 'message' => 'Error status updated to ' . $request->status]);
    }

    public function bulkUpdateStatus(Request $request)
    {
        $ids = $request->ids;
        $status = $request->status;
        if (is_array($ids) && count($ids) > 0) {
            ClientError::whereIn('id', $ids)->update(['status' => $status]);
            return response()->json(['status' => 'success', 'message' => 'Selected error reports marked as ' . $status]);
        }
        return response()->json(['status' => 'error', 'message' => 'No items selected.'], 400);
    }

    public function bulk_destroy(Request $request)
    {
        $ids = $request->ids;
        if (is_array($ids) && count($ids) > 0) {
            ClientError::whereIn('id', $ids)->delete();
            return response()->json(['status' => 'success', 'message' => 'Selected error reports deleted successfully.']);
        }
        return response()->json(['status' => 'error', 'message' => 'No items selected.'], 400);
    }
}
