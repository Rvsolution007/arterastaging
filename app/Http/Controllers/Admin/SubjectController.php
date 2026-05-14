<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use App\Models\Subject;
use App\Models\Entry;
use Illuminate\Support\Facades\Validator;
use Illuminate\Support\Str;

class SubjectController extends Controller
{
    public function __construct()
    {
        $this->middleware('permission:Subject');
    }

    public function index()
    {
        $index['data'] = Subject::get();
        return view("subject.index", $index);
    }

    public function create()
    {
        return view("subject.create");
    }

    public function store(Request $request)
    {
        $validation = Validator::make($request->all(), [
            'title' => 'required',
        ]);

        if ($validation->fails()) {
            return back()->withErrors($validation)->withInput();
        } else {
            $id = Subject::create([
                "title" => $request->get("title"),
            ])->id;

            return redirect()->route("subject.index");
        }
    }

    public function edit($id)
    {
        $subject = Subject::find($id);
        return view("subject.edit", compact("subject"));
    }

    public function update(Request $request, $id)
    {
        $validation = Validator::make($request->all(), [
            'title' => 'required',
        ]);

        if ($validation->fails()) {
            return back()->withErrors($validation)->withInput();
        } else {
            $subject = Subject::find($request->get("id"));
            $subject->title = $request->get("title");
            $subject->save();

            return redirect()->route('subject.index');
        }
    }

    public function destroy($id)
    {
        Subject::find($id)->delete();
        $entry = Entry::where("subject_id",$id)->get();
        foreach($entry as $e)
        {
            Entry::find($e->id)->delete();
        }
        return redirect()->route('subject.index');
    }
}
