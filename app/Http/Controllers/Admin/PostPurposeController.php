<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\PostPurpose;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;
use Auth;

class PostPurposeController extends Controller
{
    public function __construct()
    {
        $this->middleware('permission:BusinessFrame');
    }

    public function index()
    {
        $data = PostPurpose::orderBy('id', 'DESC')->get();
        return view('post_purpose.index', compact('data'));
    }

    public function create()
    {
        return view('post_purpose.create');
    }

    public function store(Request $request)
    {
        $validation = Validator::make($request->all(), [
            'name' => 'required|string|max:255',
        ]);

        if ($validation->fails()) {
            return back()->withErrors($validation)->withInput();
        }

        PostPurpose::create([
            'name' => $request->name,
            'status' => 1,
        ]);

        return redirect()->route('post-purpose.index')->with('success', 'Post Purpose created successfully.');
    }

    public function edit($id)
    {
        $data = PostPurpose::find($id);
        return view('post_purpose.edit', compact('data'));
    }

    public function update(Request $request, $id)
    {
        $validation = Validator::make($request->all(), [
            'name' => 'required|string|max:255',
        ]);

        if ($validation->fails()) {
            return back()->withErrors($validation)->withInput();
        }

        $purpose = PostPurpose::find($id);
        $purpose->name = $request->name;
        $purpose->save();

        return redirect()->route('post-purpose.index')->with('success', 'Post Purpose updated successfully.');
    }

    public function destroy($id)
    {
        PostPurpose::find($id)->delete();
        return redirect()->route('post-purpose.index')->with('success', 'Post Purpose deleted successfully.');
    }

    public function post_purpose_status(Request $request)
    {
        $purpose = PostPurpose::find($request->id);
        $purpose->status = ($request->checked == "true") ? 1 : 0;
        $purpose->save();
        return response()->json(['success' => true]);
    }
}
