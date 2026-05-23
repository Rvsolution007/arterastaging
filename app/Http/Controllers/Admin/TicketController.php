<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use App\Models\Ticket;
use App\Models\TicketMessage;
use App\Events\TicketMessageCreated;

class TicketController extends Controller
{
    public function index()
    {
        $tickets = Ticket::with('user')->orderBy('updated_at', 'desc')->paginate(20);
        
        $stats = [
            'open' => Ticket::where('status', 'open')->count(),
            'in_progress' => Ticket::where('status', 'in_progress')->count(),
            'ai_resolved' => Ticket::where('status', 'ai_resolved')->count(),
        ];

        return view('admin.tickets.index', compact('tickets', 'stats'));
    }

    public function show($id)
    {
        $ticket = Ticket::with(['user', 'messages'])->findOrFail($id);
        
        // If an admin opens a new ticket, change its status to in_progress
        if ($ticket->status === 'open') {
            $ticket->update(['status' => 'in_progress']);
        }

        // Determine AI sentiment and suggested replies
        // In a real system, this could call an OpenAI API or read pre-calculated values
        $suggestedReply = "Hi {$ticket->user->name}, I see you're having trouble. Let me help you with that.";
        if ($ticket->sentiment_score < 4) {
            $suggestedReply = "Hi {$ticket->user->name}, I apologize for the inconvenience. We're prioritizing your issue right now.";
        }

        return response()->json([
            'ticket' => $ticket,
            'messages' => $ticket->messages,
            'suggested_reply' => $suggestedReply
        ]);
    }

    public function reply(Request $request, $id)
    {
        $request->validate([
            'message' => 'required|string',
            'is_internal' => 'boolean'
        ]);

        $ticket = Ticket::findOrFail($id);

        $msg = TicketMessage::create([
            'ticket_id' => $ticket->id,
            'user_id' => auth()->id() ?? null, // Assuming admin is authenticated
            'sender_type' => 'admin',
            'message' => $request->message,
            'is_internal_note' => $request->is_internal ?? false
        ]);

        $ticket->touch(); // Update updated_at

        if (!$request->is_internal) {
            try {
                broadcast(new TicketMessageCreated($msg));
            } catch (\Exception $e) {
                // Ignore Pusher/WebSockets broadcasting errors
                \Illuminate\Support\Facades\Log::warning("Failed to broadcast ticket message: " . $e->getMessage());
            }
        }

        return response()->json(['success' => true, 'message' => $msg]);
    }

    public function updateStatus(Request $request, $id)
    {
        $ticket = Ticket::findOrFail($id);
        $ticket->update(['status' => $request->status]);

        return response()->json(['success' => true]);
    }
}
