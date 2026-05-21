<?php

namespace App\Http\Controllers\Partner;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Auth;
use Illuminate\Support\Str;
use Illuminate\Support\Facades\Storage;

class ProfileController extends Controller
{
    public function index()
    {
        $user = Auth::user();
        return view('partner.profile.index', compact('user'));
    }

    public function update(Request $request)
    {
        $user = Auth::user();
        
        $request->validate([
            'tax_id' => 'nullable|string|max:255',
            'tax_document' => 'nullable|file|mimes:pdf,jpg,jpeg,png|max:2048',
        ]);

        $user->tax_id = $request->tax_id;

        if ($request->hasFile('tax_document')) {
            $file = $request->file('tax_document');
            $filename = Str::uuid() . '.' . $file->getClientOriginalExtension();
            $file->move(public_path('uploads/partner_docs'), $filename);
            $user->tax_document = $filename;
        }

        $user->save();

        return redirect()->back()->with('success', 'Compliance documents updated successfully.');
    }
}
