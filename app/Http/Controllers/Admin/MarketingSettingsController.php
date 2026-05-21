<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use App\Models\Festivals;

class MarketingSettingsController extends Controller
{
    public function index()
    {
        // Fetch upcoming festivals from the existing database table that you already manage via /admin/festivals
        // We will show them here specifically highlighting when the AI will trigger campaigns.
        $upcomingFestivals = Festivals::where('activation_date', '>=', now())
                                        ->orderBy('activation_date', 'asc')
                                        ->get();

        return view('admin.marketing.settings', compact('upcomingFestivals'));
    }
}
