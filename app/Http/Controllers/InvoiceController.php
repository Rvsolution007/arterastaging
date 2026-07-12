<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\Transaction;

class InvoiceController extends Controller
{
    /**
     * Show invoice for a transaction.
     * Security: Requires authentication and verifies ownership.
     */
    public function show($id)
    {
        // SECURITY FIX: Require authentication — abort if not logged in
        if (!auth()->check()) {
            abort(401, 'Authentication required');
        }

        $transaction = Transaction::with(['user', 'subscription'])->findOrFail($id);
        
        // Security: Only allow transaction owner or Super Admin to view invoice
        if (auth()->id() != $transaction->user_id) {
            if (!auth()->user()->user_type || auth()->user()->user_type != 'Super Admin') {
                abort(403, 'Unauthorized');
            }
        }
        
        return view('invoice', compact('transaction'));
    }
}
