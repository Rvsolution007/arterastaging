<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use App\Models\User;
use App\Models\Lead;

class LeadController extends Controller
{
    public function index()
    {
        // Hot Leads: Free users sorted by their AI-calculated Lead Score
        $hotLeads = User::where(function($query) {
                            $query->where('is_subscribe', 0)
                                  ->orWhereNull('is_subscribe');
                        })
                        ->where('user_type', '!=', 'Super Admin') // Exclude Admin
                        ->orderBy('lead_score', 'desc')
                        ->take(50)
                        ->get();

        // Cold Leads: Non-registered B2B targets
        $coldLeads = Lead::orderBy('created_at', 'desc')->get();

        return view('admin.leads.index', compact('hotLeads', 'coldLeads'));
    }

    public function draftEmail($id)
    {
        $lead = Lead::findOrFail($id);
        
        $aiService = new \App\Services\VertexAIService(1);
        $systemInstruction = "You are a top-tier B2B sales SDR for Artera SaaS. Write a short, highly personalized cold email. Return ONLY valid JSON: {\"subject\": \"Subject here\", \"body\": \"Body text here\"}";
        $prompt = "Write a cold email to a lead in the {$lead->industry} industry. Pitch our automated AI design tool.";

        try {
            $response = $aiService->generateContent($systemInstruction, [
                ['role' => 'user', 'text' => $prompt]
            ]);

            if (isset($response['text'])) {
                $jsonStr = trim($response['text']);
                if(str_starts_with($jsonStr, '```json')) {
                    $jsonStr = str_replace(['```json', '```'], '', $jsonStr);
                }
                $jsonStr = trim($jsonStr);
                $result = json_decode($jsonStr, true);
                
                if (json_last_error() === JSON_ERROR_NONE && isset($result['subject'])) {
                    return redirect()->back()->with('email_draft', $result);
                }
            }
        } catch (\Exception $e) {
            return redirect()->back()->with('error', 'Failed to generate email: ' . $e->getMessage());
        }

        return redirect()->back()->with('error', 'Failed to parse AI response.');
    }
}
