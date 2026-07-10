<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use App\Models\Blog;

class BlogController extends Controller
{
    public function index()
    {
        $blogs = Blog::orderBy('created_at', 'desc')->get();
        return view('admin.blogs.index', compact('blogs'));
    }

    public function edit($id)
    {
        $blog = Blog::findOrFail($id);
        return view('admin.blogs.edit', compact('blog'));
    }

    public function update(Request $request, $id)
    {
        $blog = Blog::findOrFail($id);
        
        $updateData = [
            'title' => $request->title,
            'content' => $request->content,
            'meta_keywords' => $request->meta_keywords,
            'status' => $request->status,
        ];

        if ($request->hasFile('og_image')) {
            $file = $request->file('og_image');
            $filename = time() . '_' . $file->getClientOriginalName();
            $destinationPath = public_path('uploads/blogs');
            
            if (!file_exists($destinationPath)) {
                mkdir($destinationPath, 0777, true);
            }
            
            $file->move($destinationPath, $filename);
            $updateData['og_image'] = 'uploads/blogs/' . $filename;
        }

        $blog->update($updateData);

        return redirect()->route('admin.blogs')->with('success', 'Blog updated successfully.');
    }

    public function destroy($id)
    {
        Blog::findOrFail($id)->delete();
        return redirect()->route('admin.blogs')->with('success', 'Blog deleted successfully.');
    }
}
