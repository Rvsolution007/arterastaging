<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\FestivalAiGeneration;
use Illuminate\Http\Request;

class FestivalAiGenerationController extends Controller
{
    public function __construct()
    {
        $this->middleware('permission:Festival');
    }

    public function index(Request $request)
    {
        $query = FestivalAiGeneration::with(['user', 'festival', 'language', 'style', 'imageModel'])->latest();

        if ($request->filled('status')) {
            $query->where('status', $request->input('status'));
        }

        return view('festivals.ai_generations', [
            'generations' => $query->paginate(30)->withQueryString(),
            'statuses' => ['queued', 'processing', 'completed', 'failed'],
        ]);
    }
}
