<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use App\Models\Entry;
use Illuminate\Support\Facades\Validator;
use Illuminate\Support\Str;

class EntryController extends Controller
{
    public function __construct()
    {
        $this->middleware('permission:Entry');
    }

    public function index()
    {
        $index['data'] = Entry::get();
        return view("entry.index", $index);
    }

    public function destroy($id)
    {
        Entry::find($id)->delete();
        return redirect()->route('entry.index');
    }
}
