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

        // 3. Simple AI RAG Logic (Search Knowledge Base)
        $kbResult = KnowledgeBase::where('status', 1)
            ->where(function($q) use ($userMessageText) {
                // Split words to find rough keyword matches
                $words = explode(' ', $userMessageText);
                foreach($words as $word) {
                    if (strlen($word) > 3) {
                        $q->orWhere('keywords', 'LIKE', "%{$word}%");
                        $q->orWhere('question', 'LIKE', "%{$word}%");
                    }
                }
            })->first();

        if ($kbResult) {
            $aiReplyText = $kbResult->answer;
            // Optionally set ticket to resolved if AI answers
            // $ticket->update(['status' => 'ai_resolved']);
        } else {
            // Human escalation required
            $ticket->update(['status' => 'open']);
            $aiReplyText = "I couldn't find an automatic answer for that. I have assigned Ticket #{$ticket->id} to our human support team. They will review this shortly!";
        }

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
