<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\AiGenerationBatch;
use App\Models\AiGenerationLog;
use App\Models\BusinessCustomFrame;
use App\Models\User;
use App\Services\CustomFrameAIService;
use Illuminate\Http\Request;

class AiMonitorController extends Controller
{
    public function __construct()
    {
        $this->middleware('permission:CustomFrame');
    }

    /**
     * Main AI Monitor Dashboard
     */
    public function index()
    {
        $batches = AiGenerationBatch::with('customFrame.purpose')
            ->orderBy('id', 'desc')
            ->paginate(10);

        $frames = BusinessCustomFrame::with('purpose')->where('status', 1)->get();

        // Get users who have at least one product
        $users = User::whereHas('products')->select('id', 'name', 'email')->limit(100)->get();

        return view('admin.ai_monitor.index', compact('batches', 'frames', 'users'));
    }

    /**
     * View logs for a specific batch
     */
    public function batchLogs($id)
    {
        $batch = AiGenerationBatch::with('customFrame.purpose')->findOrFail($id);
        $logs = AiGenerationLog::where('ai_generation_batch_id', $id)
            ->with(['user'])
            ->orderBy('id', 'desc')
            ->paginate(25);

        return view('admin.ai_monitor.batch_logs', compact('batch', 'logs'));
    }

    /**
     * AJAX: Live Playground - Test AI connection for a specific user + frame
     */
    public function playground(Request $request)
    {
        $request->validate([
            'frame_id' => 'required|exists:business_custom_frames,id',
            'user_id' => 'required|exists:users,id',
        ]);

        $frameId = $request->frame_id;
        $userId = $request->user_id;

        // Force regeneration (bypass cache) for playground testing
        $result = CustomFrameAIService::generateForUser($frameId, $userId, true);
        $logData = CustomFrameAIService::getLastGenerationLog();

        return response()->json([
            'success' => $result !== null,
            'generated_content' => $result,
            'raw_prompt' => $logData['raw_prompt'],
            'raw_response' => $logData['raw_response'],
            'tokens_used' => $logData['tokens_used'],
            'product_id' => $logData['product_id'],
            'error' => $logData['error'],
        ]);
    }

    /**
     * AJAX: Get batch status (for live progress polling)
     */
    public function batchStatus($id)
    {
        $batch = AiGenerationBatch::findOrFail($id);
        return response()->json([
            'status' => $batch->status,
            'processed_users' => $batch->processed_users,
            'total_users' => $batch->total_users,
            'total_tokens' => $batch->total_tokens,
            'total_cost' => $batch->total_cost,
        ]);
    }
}
