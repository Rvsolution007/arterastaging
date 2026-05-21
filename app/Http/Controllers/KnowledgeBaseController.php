<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\KnowledgeBase;

class KnowledgeBaseController extends Controller
{
    /**
     * Ingest a new FAQ / Knowledge base article
     */
    public function ingest(Request $request)
    {
        $request->validate([
            'question' => 'required|string',
            'answer' => 'required|string',
            'category' => 'nullable|string',
            'keywords' => 'nullable|string',
            'embedding' => 'nullable|array'
        ]);

        $kb = KnowledgeBase::create([
            'question' => $request->question,
            'answer' => $request->answer,
            'category' => $request->category ?? 'general',
            'keywords' => $request->keywords,
            'embedding' => $request->embedding,
            'status' => true
        ]);

        return response()->json([
            'success' => true,
            'message' => 'Knowledge base article ingested successfully',
            'data' => $kb
        ]);
    }

    /**
     * Search FAQs for AI integration
     * Uses simple keyword matching fallback if vector search isn't enabled
     */
    public function search(Request $request)
    {
        $query = $request->input('query');
        
        if (!$query) {
            return response()->json(['success' => false, 'message' => 'Query is required'], 400);
        }

        // V1: Simple Full-text / Keyword search
        // In V2 (with Vector DB), this would compute cosine similarity against the `embedding` column
        $results = KnowledgeBase::where('status', 1)
            ->where(function($q) use ($query) {
                $q->where('question', 'LIKE', "%{$query}%")
                  ->orWhere('keywords', 'LIKE', "%{$query}%");
            })
            ->limit(3)
            ->get(['id', 'question', 'answer', 'category']);

        return response()->json([
            'success' => true,
            'results' => $results
        ]);
    }
}
