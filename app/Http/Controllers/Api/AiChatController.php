<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use App\Models\Ticket;
use App\Models\TicketMessage;
use App\Models\KnowledgeBase;

class AiChatController extends Controller
{
    /**
     * Handle incoming user messages from the Flutter App
     */
    public function sendMessage(Request $request)
    {
        $request->validate([
            'message' => 'required|string',
            'user_id' => 'required|integer',
            'ticket_id' => 'nullable|integer'
        ]);

        $userId = $request->user_id;
        $userMessageText = $request->message;
        $ticketId = $request->ticket_id;

        // 1. Find the specific ticket or create a new one
        if ($ticketId) {
            $ticket = Ticket::where('id', $ticketId)->where('user_id', $userId)->firstOrFail();
        } else {
            $ticket = Ticket::create([
                'user_id' => $userId,
                'status' => 'open',
                'subject' => substr($userMessageText, 0, 50) . '...',
                'priority' => 'low',
                'sentiment_score' => 5.0
            ]);
        }

        // 2. Save User Message
        $userMsg = TicketMessage::create([
            'ticket_id' => $ticket->id,
            'user_id' => $userId,
            'sender_type' => 'user',
            'message' => $userMessageText
        ]);

        $ticket->touch(); // Update updated_at

        // 3. AI LLM Logic (Search Knowledge Base & Translate)
        $allKb = KnowledgeBase::where('status', 1)->get();
        $faqContext = "";
        foreach ($allKb as $kb) {
            $faqContext .= "FAQ ID: {$kb->id}\nQuestion: {$kb->question}\nAnswer: {$kb->answer}\nKeywords: {$kb->keywords}\n\n";
        }

        $systemPrompt = "You are a helpful customer support AI agent for an application. 
Your task is to answer the user's question by matching their intent with one of the provided FAQs.
Here is the Knowledge Base (list of FAQs):
-----
{$faqContext}
-----
Instructions:
1. Understand the user's question, no matter what language they use (English, Hindi, Gujarati, etc.).
2. Find the FAQ that best matches their question.
3. If a matching FAQ is found, you MUST return the EXACT ANSWER from that FAQ, but TRANSLATED into the EXACT same language the user used.
4. Do NOT add any extra conversational text like 'Sure, here is the answer'. ONLY output the translated answer.
5. If the user's question does NOT match ANY of the provided FAQs, then reply with exactly this message, but translated into the user's language: 'I couldn't find an automatic answer for that. I have assigned Ticket #{$ticket->id} to our human support team. They will review this shortly!'";

        $aiService = new \App\Services\VertexAIService($userId);
        $aiResponse = $aiService->generateContent($systemPrompt, [
            ['role' => 'user', 'text' => $userMessageText]
        ]);

        $aiReplyText = $aiResponse['text'];

        // If the AI generated the fallback message (translated or not), ensure ticket remains open
        // (Ticket is already open by default, so we don't strictly need to do anything here)

        // 4. Save AI Reply
        $aiMsg = TicketMessage::create([
            'ticket_id' => $ticket->id,
            'user_id' => null,
            'sender_type' => 'ai',
            'message' => $aiReplyText
        ]);

        // Fire Broadcast Events
        try {
            broadcast(new \App\Events\TicketMessageCreated($userMsg));
            broadcast(new \App\Events\TicketMessageCreated($aiMsg));
        } catch (\Exception $e) {
            \Illuminate\Support\Facades\Log::warning("Broadcasting failed: " . $e->getMessage());
        }

        return response()->json([
            'success' => true,
            'ticket_id' => $ticket->id,
            'reply' => $aiMsg->message,
            'timestamp' => $aiMsg->created_at->toDateTimeString()
        ]);
    }

    /**
     * Get all tickets for a user
     */
    public function getTickets(Request $request)
    {
        $userId = $request->user_id;
        if (!$userId) return response()->json(['success' => false, 'message' => 'user_id required'], 400);

        $tickets = Ticket::where('user_id', $userId)->orderBy('updated_at', 'desc')->get();
        return response()->json([
            'success' => true,
            'tickets' => $tickets
        ]);
    }

    /**
     * Get chat history for a specific ticket
     */
    public function getHistory(Request $request)
    {
        $userId = $request->user_id;
        $ticketId = $request->ticket_id;
        
        if (!$userId || !$ticketId) return response()->json(['success' => false, 'message' => 'user_id and ticket_id required'], 400);

        $ticket = Ticket::where('id', $ticketId)->where('user_id', $userId)->first();
        if (!$ticket) {
            return response()->json(['success' => false, 'message' => 'Ticket not found'], 404);
        }

        $messages = TicketMessage::where('ticket_id', $ticket->id)
            ->where('is_internal_note', false)
            ->orderBy('created_at', 'asc')
            ->get(['id', 'sender_type', 'message', 'created_at']);

        return response()->json([
            'success' => true,
            'ticket_id' => $ticket->id,
            'status' => $ticket->status,
            'messages' => $messages
        ]);
    }
}
