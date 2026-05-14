<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use App\Models\Inquiry;
use App\Models\User;
use Illuminate\Support\Facades\Validator;
use Illuminate\Support\Str;

class InquiryController extends Controller
{
    public function __construct()
    {
        $this->middleware('permission:Inquiry');
    }

    public function index()
    {
        $index['data'] = Inquiry::get();
        return view("inquiry.index", $index);
    }

    public function destroy($id)
    {
        Inquiry::find($id)->delete();
        return redirect()->route('inquiry.index');
    }
}
