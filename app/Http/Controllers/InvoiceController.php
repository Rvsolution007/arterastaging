<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\Transaction;

class InvoiceController extends Controller
{
    public function show($id)
    {
        $transaction = Transaction::with(['user', 'subscription'])->findOrFail($id);
        
        return view('invoice', compact('transaction'));
    }
}
