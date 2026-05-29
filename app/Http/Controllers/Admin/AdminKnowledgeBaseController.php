<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use App\Models\KnowledgeBase;
use Illuminate\Support\Facades\Response;
use App\Services\VertexAIService;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Storage;

class AdminKnowledgeBaseController extends Controller
{
    public function index(Request $request)
    {
        $query = KnowledgeBase::orderBy('id', 'desc');
        
        if ($request->has('category') && $request->category != '') {
            $query->where('category', $request->category);
        }
        
        $kbs = $query->paginate(15);
        $kbs->appends(['category' => $request->category]); // Keep filter on pagination

        $allKbs = KnowledgeBase::all();

        // Calculate match percentage for duplicate detection
        foreach ($kbs as $kb) {
            $maxScore = 0;
            foreach ($allKbs as $otherKb) {
                if ($kb->id !== $otherKb->id) {
                    similar_text(strtolower($kb->question), strtolower($otherKb->question), $percent);
                    if ($percent > $maxScore) {
                        $maxScore = $percent;
                    }
                }
            }
            $kb->match_percentage = round($maxScore, 1);
        }

        $categories = KnowledgeBase::select('category')->distinct()->pluck('category');
        $aiContext = $this->getContext();

        return view('admin.knowledge_base.index', compact('kbs', 'categories', 'aiContext'));
    }

    private function getDefaultAiContext()
    {
        return "- Purchasing Subscriptions: Users can purchase or upgrade subscription plans by tapping the 'Crown' icon, 'Go Premium' button, or the Subscription card in the 'More' menu. Payment gateways include Stripe, Paytm, or Offline. DO NOT mention App Store or Play Store.
- Billing History: Users view payment history and download invoices inside the app under 'Menu' -> 'More' -> 'Billing & Payments' -> 'Billing & Payment History'.
- Posts: Users select a festival or custom category, pick a frame, and tap 'Download' or 'Share'. Custom Posts let users add their own text, image, and logo.
- Editor Section: The editor has specific buttons: 'Text', 'Image', 'Background', 'Frame'.
- Profile: Users update their business details by navigating to Menu -> 'Profile'.
- Downloads: If a download fails, they should check storage permissions or try again.";
    }

    private function getContext()
    {
        return Storage::exists('ai_ui_context.txt') ? Storage::get('ai_ui_context.txt') : $this->getDefaultAiContext();
    }

    public function updateContext(Request $request)
    {
        $request->validate([
            'ai_context' => 'required|string'
        ]);
        Storage::put('ai_ui_context.txt', $request->input('ai_context'));
        return redirect()->back()->with('success', 'AI UI Context updated successfully!');
    }

    public function autoSyncUiContext()
    {
        try {
            $basePath = base_path('brandkit_mobile/lib/screens');
            $dashboardFile = $basePath . '/dashboard_screen.dart';
            $moreFile = $basePath . '/more_screen.dart';
            
            if (!file_exists($dashboardFile) || !file_exists($moreFile)) {
                return redirect()->back()->with('error', 'Flutter source files not found on server.');
            }

            $dashboardCode = file_get_contents($dashboardFile);
            $moreCode = file_get_contents($moreFile);

            $userId = Auth::id() ?? 1;
            $aiService = new VertexAIService($userId);
            
            $prompt = "You are an expert Flutter Developer and Technical Writer.
            I have provided the source code of two key Flutter files from our mobile app below.
            
            File 1 (dashboard_screen.dart):
            ```dart
            " . substr($dashboardCode, 0, 8000) . "
            ```
            
            File 2 (more_screen.dart):
            ```dart
            " . substr($moreCode, 0, 8000) . "
            ```
            
            Your task is to analyze this source code and generate a 'App UI Context Rules' guide in plain English.
            This guide will be used by an AI Customer Support Agent to know exactly how to guide users through the app.
            
            Rules for your output:
            1. List the exact names of the bottom navigation tabs.
            2. List the exact sections and buttons found inside the 'More' menu (e.g. App Preferences, Billing & Payments, Help & Support).
            3. Mention that 'Billing History' is inside 'Billing & Payments' inside the 'More' menu.
            4. Detail where users can 'Report a Problem' or find 'FAQs' based on the code.
            5. Keep it concise, clear, and bulleted. DO NOT output any code. Just plain English instructions about the UI layout.";

            $response = $aiService->generateContent(
                "You are an expert technical writer. Output only the plain English rules.",
                [['role' => 'user', 'text' => $prompt]]
            );

            Storage::put('ai_ui_context.txt', trim($response['text']));

            return redirect()->back()->with('success', 'AI UI Context successfully synced from App Source Code!');

        } catch (\Exception $e) {
            return redirect()->back()->with('error', 'Sync Failed: ' . $e->getMessage());
        }
    }

    public function create()
    {
        return view('admin.knowledge_base.create');
    }

    public function store(Request $request)
    {
        $request->validate([
            'question' => 'required|string',
            'answer' => 'required|string',
            'category' => 'required|string',
            'keywords' => 'nullable|string',
            'status' => 'required|boolean'
        ]);

        KnowledgeBase::create([
            'question' => $request->question,
            'answer' => $request->answer,
            'category' => $request->category,
            'keywords' => $request->keywords,
            'status' => $request->status,
        ]);

        return redirect()->route('admin.knowledge_base')->with('success', 'Knowledge Base entry created successfully.');
    }

    public function edit($id)
    {
        $kb = KnowledgeBase::findOrFail($id);
        return view('admin.knowledge_base.edit', compact('kb'));
    }

    public function update(Request $request, $id)
    {
        $kb = KnowledgeBase::findOrFail($id);

        $request->validate([
            'question' => 'required|string',
            'answer' => 'required|string',
            'category' => 'required|string',
            'keywords' => 'nullable|string',
            'status' => 'required|boolean'
        ]);

        $kb->update([
            'question' => $request->question,
            'answer' => $request->answer,
            'category' => $request->category,
            'keywords' => $request->keywords,
            'status' => $request->status,
        ]);

        return redirect()->route('admin.knowledge_base')->with('success', 'Knowledge Base entry updated successfully.');
    }

    public function destroy($id)
    {
        $kb = KnowledgeBase::findOrFail($id);
        $kb->delete();
        return redirect()->route('admin.knowledge_base')->with('success', 'Knowledge Base entry deleted successfully.');
    }

    public function exportCsv()
    {
        $kbs = KnowledgeBase::all();
        $csvFileName = 'knowledge_base_' . date('Y-m-d_H-i-s') . '.csv';

        $callback = function() use ($kbs) {
            $handle = fopen('php://output', 'w');
            fputcsv($handle, ['ID', 'Question', 'Answer', 'Category', 'Keywords', 'Status']);

            foreach ($kbs as $kb) {
                fputcsv($handle, [
                    $kb->id,
                    $kb->question,
                    $kb->answer,
                    $kb->category,
                    $kb->keywords,
                    $kb->status
                ]);
            }

            fclose($handle);
        };

        return response()->streamDownload($callback, $csvFileName, [
            "Content-type"        => "text/csv",
            "Pragma"              => "no-cache",
            "Cache-Control"       => "must-revalidate, post-check=0, pre-check=0",
            "Expires"             => "0"
        ]);
    }

    public function importCsv(Request $request)
    {
        $request->validate([
            'csv_file' => 'required|mimes:csv,txt|max:2048'
        ]);

        $file = $request->file('csv_file');
        $handle = fopen($file->path(), 'r');
        $header = true;
        
        $count = 0;
        while ($row = fgetcsv($handle)) {
            if ($header) {
                $header = false;
                continue;
            }
            if (count($row) < 5) continue; // Basic validation
            
            KnowledgeBase::create([
                'question' => $row[1] ?? '',
                'answer' => $row[2] ?? '',
                'category' => $row[3] ?? '',
                'keywords' => $row[4] ?? '',
                'status' => isset($row[5]) ? (bool)$row[5] : true,
            ]);
            $count++;
        }

        return redirect()->route('admin.knowledge_base')->with('success', "$count entries imported successfully.");
    }

    public function generateAiCategory(Request $request)
    {
        $category = $request->input('category');
        if (!$category) {
            return response()->json(['status' => 'error', 'message' => 'Category is required.']);
        }

        try {
            $userId = Auth::id() ?? 1;
            $aiService = new VertexAIService($userId);
            
            // Fetch existing FAQs for this category
            $existingFaqs = KnowledgeBase::where('category', $category)->get(['id', 'question', 'answer', 'keywords'])->toArray();
            $existingJson = json_encode($existingFaqs);

            $aiContextStr = $this->getContext();

            $prompt = "You are a senior support engineer for the Artera Mobile App. 
            CRITICAL APP CONTEXT:
            {$aiContextStr}
            
            We are analyzing the category '{$category}'.
            Currently, the database has these existing FAQs for this category:
            {$existingJson}

            Your task is to review these existing FAQs, update them if they are inaccurate or poorly formatted, delete any that are completely irrelevant to the app context, and add new ones if important standard questions are missing (aim for 5 to 8 total FAQs).
            CRITICAL: The app does NOT have a \"Settings\" menu. The bottom tab is called \"More\". If any existing FAQ instructs the user to go to \"Settings\", you MUST put it in the \"update\" array and change the word \"Settings\" to \"the 'More' menu\".
            Strictly provide answers using ONLY the exact UI buttons and features mentioned above. Do not invent external app store instructions.
            For each question, provide a detailed, step-by-step answer formatted in HTML.
            Use bold text for key terms and numbered lists for steps.
            
            Output MUST be a JSON object with exactly these three keys:
            {
              \"add\": [ {\"question\": \"...\", \"answer\": \"...\", \"keywords\": \"...\"} ],
              \"update\": [ {\"id\": 123, \"question\": \"...\", \"answer\": \"...\", \"keywords\": \"...\"} ],
              \"delete\": [ 123, 124 ]
            }
            Do not wrap the JSON in Markdown code blocks like ```json.";
            
            $response = $aiService->generateContent(
                "You are an expert AI database synchronizer. Output only pure JSON in the specified schema.",
                [['role' => 'user', 'text' => $prompt]]
            );

            $text = $response['text'];
            
            // Clean up markdown block if the AI ignored the instruction
            $text = preg_replace('/```json\s*/', '', $text);
            $text = preg_replace('/```\s*/', '', $text);

            $syncData = json_decode(trim($text), true);

            if (!$syncData || !is_array($syncData)) {
                return response()->json(['status' => 'error', 'message' => 'Failed to parse AI response.']);
            }

            $added = 0;
            $updated = 0;
            $deleted = 0;

            if (isset($syncData['add']) && is_array($syncData['add'])) {
                foreach ($syncData['add'] as $item) {
                    $keywords = is_array($item['keywords'] ?? '') ? implode(', ', $item['keywords']) : ($item['keywords'] ?? '');
                    KnowledgeBase::create([
                        'question' => $item['question'] ?? '',
                        'answer' => $item['answer'] ?? '',
                        'category' => $category,
                        'keywords' => $keywords,
                        'status' => 1
                    ]);
                    $added++;
                }
            }

            if (isset($syncData['update']) && is_array($syncData['update'])) {
                foreach ($syncData['update'] as $item) {
                    if (isset($item['id'])) {
                        $keywords = is_array($item['keywords'] ?? '') ? implode(', ', $item['keywords']) : ($item['keywords'] ?? '');
                        KnowledgeBase::where('id', $item['id'])->update([
                            'question' => $item['question'] ?? '',
                            'answer' => $item['answer'] ?? '',
                            'keywords' => $keywords,
                        ]);
                        $updated++;
                    }
                }
            }

            if (isset($syncData['delete']) && is_array($syncData['delete'])) {
                foreach ($syncData['delete'] as $id) {
                    KnowledgeBase::where('id', $id)->delete();
                    $deleted++;
                }
            }

            return response()->json(['status' => 'success', 'message' => "AI Sync complete! Added: {$added}, Updated: {$updated}, Deleted: {$deleted}"]);
        } catch (\Exception $e) {
            return response()->json(['status' => 'error', 'message' => $e->getMessage()]);
        }
    }

    public function generateAiQuestion(Request $request)
    {
        $question = $request->input('question');
        $category = $request->input('category');
        
        if (!$question) {
            return response()->json(['status' => 'error', 'message' => 'Question is required.']);
        }

        try {
            $userId = Auth::id() ?? 1;
            $aiService = new VertexAIService($userId);
            
            $aiContextStr = $this->getContext();

            $prompt = "You are a senior support engineer for the Artera Mobile App.
            CRITICAL APP CONTEXT:
            {$aiContextStr}
            
            An end-user of the app has asked: '{$question}'. The category is '{$category}'.
            Provide a detailed, step-by-step answer formatted in HTML targeting the end-user.
            CRITICAL: The app does NOT have a \"Settings\" menu. The bottom tab is called \"More\".
            Strictly use ONLY the exact UI buttons and features mentioned above. Do not invent external instructions.
            Use bold text for key terms and numbered lists (<ol><li>...</li></ol>) for steps.
            Output only the HTML answer, nothing else.";
            
            $response = $aiService->generateContent(
                "You are an expert AI support system. Provide the HTML response only.",
                [['role' => 'user', 'text' => $prompt]]
            );

            return response()->json(['status' => 'success', 'answer' => trim($response['text'])]);
        } catch (\Exception $e) {
            return response()->json(['status' => 'error', 'message' => $e->getMessage()]);
        }
    }
}
