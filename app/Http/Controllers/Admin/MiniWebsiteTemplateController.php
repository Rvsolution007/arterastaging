<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;

class MiniWebsiteTemplateController extends Controller
{
    public function __construct()
    {
        // Assuming there's a permission system. We can use a generic permission or remove it.
        // $this->middleware('permission:MiniWebsiteTemplate');
    }

    public function index()
    {
        $templates = \App\Models\MiniWebsiteTemplate::orderBy('id', 'desc')->get();
        return view('admin.mini_website_template.index', compact('templates'));
    }

    public function create()
    {
        return view('admin.mini_website_template.create');
    }

    public function store(Request $request)
    {
        $request->validate([
            'name' => 'required',
            'html_content' => 'required',
        ]);

        $template = new \App\Models\MiniWebsiteTemplate();
        $template->name = $request->name;
        $template->html_content = $request->html_content;

        if ($request->hasFile('preview_image')) {
            $destinationPath = public_path('uploads');
            $extension = $request->file('preview_image')->getClientOriginalExtension();
            $fileName = \Illuminate\Support\Str::uuid() . '.' . $extension;
            $request->file('preview_image')->move($destinationPath, $fileName);
            $template->preview_image = $fileName;
        }

        $template->save();

        return redirect()->route('mini-website-template.index')->with('success', 'Template created successfully');
    }

    public function edit($id)
    {
        $template = \App\Models\MiniWebsiteTemplate::findOrFail($id);
        return view('admin.mini_website_template.edit', compact('template'));
    }

    public function update(Request $request, $id)
    {
        $request->validate([
            'name' => 'required',
            'html_content' => 'required',
        ]);

        $template = \App\Models\MiniWebsiteTemplate::findOrFail($id);
        $template->name = $request->name;
        $template->html_content = $request->html_content;

        if ($request->hasFile('preview_image')) {
            $destinationPath = public_path('uploads');
            $extension = $request->file('preview_image')->getClientOriginalExtension();
            $fileName = \Illuminate\Support\Str::uuid() . '.' . $extension;
            $request->file('preview_image')->move($destinationPath, $fileName);
            
            // Delete old file if exists
            if ($template->preview_image && file_exists(public_path('uploads/' . $template->preview_image))) {
                unlink(public_path('uploads/' . $template->preview_image));
            }
            $template->preview_image = $fileName;
        }

        $template->save();

        return redirect()->route('mini-website-template.index')->with('success', 'Template updated successfully');
    }

    public function destroy($id)
    {
        $template = \App\Models\MiniWebsiteTemplate::findOrFail($id);
        if ($template->preview_image && file_exists(public_path('uploads/' . $template->preview_image))) {
            unlink(public_path('uploads/' . $template->preview_image));
        }
        $template->delete();

        return redirect()->route('mini-website-template.index')->with('success', 'Template deleted successfully');
    }

    public function status(Request $request)
    {
        $template = \App\Models\MiniWebsiteTemplate::findOrFail($request->id);
        $template->status = ($request->checked == "true") ? 1 : 0;
        $template->save();
        return response()->json(['success' => true]);
    }
}
