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
            'user_id' => 'required|integer' // Ideally authenticated via auth:api
        ]);

        $userId = $request->user_id;
        $userMessageText = $request->message;

        // 1. Find an open ticket or create a new one
        $ticket = Ticket::firstOrCreate(
            ['user_id' => $userId, 'status' => 'open'],
            ['subject' => substr($userMessageText, 0, 50) . '...', 'priority' => 'low', 'sentiment_score' => 5.0]
        );

        // 2. Save User Message
        $userMsg = TicketMessage::create([
            'ticket_id' => $ticket->id,
            'user_id' => $userId,
            'sender_type' => 'user',
            'message' => $userMessageText
        ]);

        // 3. Simple AI RAG Logic (Search Knowledge Base)
        // In a full n8n setup, this would dispatch a Job or Webhook. 
        // Here we give an immediate response using the KB we built in Task 4.
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
        } else {
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
        broadcast(new \App\Events\TicketMessageCreated($userMsg));
        broadcast(new \App\Events\TicketMessageCreated($aiMsg));

        return response()->json([
            'success' => true,
            'ticket_id' => $ticket->id,
            'reply' => $aiMsg->message,
            'timestamp' => $aiMsg->created_at->toDateTimeString()
        ]);
    }

    /**
     * Get chat history for a user
     */
    public function getHistory(Request $request)
    {
        $userId = $request->user_id;
        if (!$userId) return response()->json(['success' => false, 'message' => 'user_id required'], 400);

        $ticket = Ticket::where('user_id', $userId)->orderBy('created_at', 'desc')->first();
        if (!$ticket) {
            return response()->json(['success' => true, 'messages' => []]);
        }

        $messages = TicketMessage::where('ticket_id', $ticket->id)
            ->where('is_internal_note', false)
            ->orderBy('created_at', 'asc')
            ->get(['id', 'sender_type', 'message', 'created_at']);

        return response()->json([
            'success' => true,
            'ticket_id' => $ticket->id,
            'messages' => $messages
        ]);
    }
}
