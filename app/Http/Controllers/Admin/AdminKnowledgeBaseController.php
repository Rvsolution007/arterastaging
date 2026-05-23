<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use App\Models\KnowledgeBase;

class AdminKnowledgeBaseController extends Controller
{
    public function index()
    {
        $kbs = KnowledgeBase::orderBy('id', 'desc')->paginate(15);
        return view('admin.knowledge_base.index', compact('kbs'));
    }

    public function create()
    {
        return view('admin.knowledge_base.create');
    }

    public function store(Request $request)
    {
        $request->validate([
            'question' => 'required|string',
            'answer' => 'required|string',
            'category' => 'required|string',
            'keywords' => 'nullable|string',
            'status' => 'required|boolean'
        ]);

        KnowledgeBase::create([
            'question' => $request->question,
            'answer' => $request->answer,
            'category' => $request->category,
            'keywords' => $request->keywords,
            'status' => $request->status,
        ]);

        return redirect()->route('admin.knowledge_base')->with('success', 'Knowledge Base entry created successfully.');
    }

    public function edit($id)
    {
        $kb = KnowledgeBase::findOrFail($id);
        return view('admin.knowledge_base.edit', compact('kb'));
    }

    public function update(Request $request, $id)
    {
        $kb = KnowledgeBase::findOrFail($id);

        $request->validate([
            'question' => 'required|string',
            'answer' => 'required|string',
            'category' => 'required|string',
            'keywords' => 'nullable|string',
            'status' => 'required|boolean'
        ]);

        $kb->update([
            'question' => $request->question,
            'answer' => $request->answer,
            'category' => $request->category,
            'keywords' => $request->keywords,
            'status' => $request->status,
        ]);

        return redirect()->route('admin.knowledge_base')->with('success', 'Knowledge Base entry updated successfully.');
    }

    public function destroy($id)
    {
        $kb = KnowledgeBase::findOrFail($id);
        $kb->delete();
        return redirect()->route('admin.knowledge_base')->with('success', 'Knowledge Base entry deleted successfully.');
    }
}
