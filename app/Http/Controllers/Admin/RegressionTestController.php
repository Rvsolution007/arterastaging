<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\RegressionTestLog;
use App\Models\GoldenRender;
use App\Models\PosterMaker;
use App\Services\NativeRenderSimulator;
use App\Services\WebRenderSimulator;
use App\Services\DualEngineValidator;
use Illuminate\Http\Request;

class RegressionTestController extends Controller
{
    /**
     * Show the regression test log page.
     */
    public function index()
    {
        $logs = RegressionTestLog::orderBy('id', 'desc')->paginate(20);
        $benchmarkFrames = PosterMaker::where('is_benchmark', true)->get();

        return view('admin.regression_tests.index', compact('logs', 'benchmarkFrames'));
    }

    /**
     * Run regression tests against all benchmark frames.
     */
    public function runTests(Request $request)
    {
        $benchmarkFrames = PosterMaker::where('is_benchmark', true)->get();

        if ($benchmarkFrames->isEmpty()) {
            return response()->json([
                'success' => false,
                'message' => 'No benchmark frames found. Please mark some frames as benchmarks first.',
            ]);
        }

        $validator = new DualEngineValidator();
        $results = [];
        $passed = 0;
        $failed = 0;

        foreach ($benchmarkFrames as $frame) {
            $jsonPath = public_path('uploads/template/'.$frame->zip_name.'/json/'.$frame->zip_name.'.json');
            if (!file_exists($jsonPath)) {
                $results[] = [
                    'frame_id' => $frame->id,
                    'zip_name' => $frame->zip_name,
                    'status' => 'ERROR',
                    'message' => 'JSON file not found',
                ];
                $failed++;
                continue;
            }

            $json = json_decode(file_get_contents($jsonPath), true);
            $version = $frame->render_version ?? 1;

            // Validate current version against its own golden baseline
            // (checking if current code still produces the same results)
            $result = $validator->validate($frame->id, $json, $version, $version);
            $result['zip_name'] = $frame->zip_name;

            if ($result['status'] === 'MATCH') {
                $passed++;
                $result['test_result'] = 'PASSED';
            } else {
                $failed++;
                $result['test_result'] = 'FAILED';
            }

            $results[] = $result;
        }

        // Save test log
        $log = RegressionTestLog::create([
            'trigger' => $request->input('trigger', 'manual'),
            'total_frames_tested' => count($benchmarkFrames),
            'passed' => $passed,
            'failed' => $failed,
            'results' => $results,
            'status' => 'completed',
        ]);

        return response()->json([
            'success' => true,
            'log_id' => $log->id,
            'total' => count($benchmarkFrames),
            'passed' => $passed,
            'failed' => $failed,
            'results' => $results,
        ]);
    }

    /**
     * Show benchmark frames management page.
     */
    public function benchmarks()
    {
        $frames = PosterMaker::orderBy('id', 'desc')->paginate(50);
        $benchmarkIds = PosterMaker::where('is_benchmark', true)->pluck('id')->toArray();

        return view('admin.regression_tests.benchmarks', compact('frames', 'benchmarkIds'));
    }

    /**
     * Toggle benchmark status for a frame.
     */
    public function toggleBenchmark(Request $request)
    {
        $frame = PosterMaker::findOrFail($request->frame_id);
        $frame->is_benchmark = !$frame->is_benchmark;
        $frame->save();

        return response()->json([
            'success' => true,
            'is_benchmark' => $frame->is_benchmark,
        ]);
    }
}
