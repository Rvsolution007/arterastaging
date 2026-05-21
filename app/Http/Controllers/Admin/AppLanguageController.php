<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use App\Models\AppTranslation;
use Illuminate\Support\Facades\Validator;

class AppLanguageController extends Controller
{
    public function index()
    {
        $data = AppTranslation::orderBy('id', 'ASC')->paginate(15);
        return view('app_language.index', compact('data'));
    }

    public function create()
    {
        $english = AppTranslation::where('language_code', 'en')->first();
        $englishKeys = $english ? $english->translations : [];
        return view('app_language.edit', compact('englishKeys'));
    }

    public function store(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'language_code' => 'required|unique:app_translations',
            'title' => 'required',
        ]);

        if ($validator->fails()) {
            return back()->withErrors($validator)->withInput();
        }

        AppTranslation::create([
            'language_code' => $request->language_code,
            'title' => $request->title,
            'status' => $request->status ? 1 : 0,
            'translations' => $request->translations ?? [],
        ]);

        return redirect()->route('app-language.index')->with('success', 'Language added successfully');
    }

    public function edit($id)
    {
        $language = AppTranslation::findOrFail($id);
        $english = AppTranslation::where('language_code', 'en')->first();
        $englishKeys = $english ? $english->translations : [];
        
        return view('app_language.edit', compact('language', 'englishKeys'));
    }

    public function update(Request $request, $id)
    {
        $validator = Validator::make($request->all(), [
            'language_code' => 'required|unique:app_translations,language_code,' . $id,
            'title' => 'required',
        ]);

        if ($validator->fails()) {
            return back()->withErrors($validator)->withInput();
        }

        $language = AppTranslation::findOrFail($id);
        $language->update([
            'language_code' => $request->language_code,
            'title' => $request->title,
            'status' => $request->status ? 1 : 0,
            'translations' => $request->translations ?? [],
        ]);

        return redirect()->route('app-language.index')->with('success', 'Language updated successfully');
    }

    public function destroy(Request $request)
    {
        $ids = explode(",", $request->select_post);
        if ($request->select_post != null) {
            if ($request->action_type == "enable") {
                AppTranslation::whereIn('id', $ids)->update(['status' => 1]);
            }
            if ($request->action_type == "disable") {
                AppTranslation::whereIn('id', $ids)->update(['status' => 0]);
            }
            if ($request->action_type == "delete") {
                AppTranslation::whereIn('id', $ids)->delete();
            }
        }
        return back();
    }
    
    public function status(Request $request)
    {
        $lang = AppTranslation::find($request->id);
        $lang->status = ($request->checked == "true") ? 1 : 0;
        $lang->save();
        return response()->json(['success' => true]);
    }
}
