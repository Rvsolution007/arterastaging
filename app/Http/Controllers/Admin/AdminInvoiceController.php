<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use App\Models\Subscription;
use App\Models\EarningHistory;

class AdminInvoiceController extends Controller
{
    public function index()
    {
        // Assuming EarningHistory tracks payments that would have invoices
        $invoices = EarningHistory::with(['user', 'subscription'])->latest()->paginate(20);
        return view('admin.invoices.index', compact('invoices'));
    }
}
