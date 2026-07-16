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
        // SECURITY FIX: Require authentication OR valid signed URL
        if (!auth()->check() && !request()->hasValidSignature()) {
            abort(401, 'Authentication required');
        }

        $transaction = Transaction::with(['user', 'subscription'])->findOrFail($id);
        
        // Security: Only allow transaction owner or Super Admin to view invoice (unless valid signed URL is present)
        if (!request()->hasValidSignature()) {
            if (auth()->id() != $transaction->user_id) {
                if (!auth()->check() || auth()->user()->user_type != 'Super Admin') {
                    abort(403, 'Unauthorized');
                }
            }
        }
        
        return view('invoice', compact('transaction'));
    }
}
