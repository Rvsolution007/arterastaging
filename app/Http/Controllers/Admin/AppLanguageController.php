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

    public function export(Request $request)
    {
        if ($request->has('ids') && !empty($request->ids)) {
            $ids = explode(',', $request->ids);
            $languages = AppTranslation::whereIn('id', $ids)->get();
        } else {
            $languages = AppTranslation::all();
        }
        $fileName = 'app_languages_' . date('Y_m_d_H_i_s') . '.json';
        
        $headers = [
            'Content-type' => 'application/json',
            'Content-Disposition' => sprintf('attachment; filename="%s"', $fileName),
        ];

        return response()->make($languages->toJson(JSON_PRETTY_PRINT), 200, $headers);
    }

    public function import(Request $request)
    {
        $request->validate([
            'json_file' => 'required|file' // Removing mimetypes restriction as some browsers upload json as text/plain or application/octet-stream
        ]);

        $file = $request->file('json_file');
        $json = json_decode(file_get_contents($file->getRealPath()), true);

        if (!$json || !is_array($json)) {
            return back()->withErrors('Invalid JSON format. Please upload a valid exported language file.');
        }

        $importedCount = 0;
        foreach ($json as $langData) {
            if (isset($langData['language_code']) && isset($langData['title'])) {
                $existing = AppTranslation::where('language_code', $langData['language_code'])->first();
                if ($existing) {
                    // Merge translations
                    $currentTranslations = is_array($existing->translations) ? $existing->translations : [];
                    $newTranslations = (isset($langData['translations']) && is_array($langData['translations'])) ? $langData['translations'] : [];
                    $mergedTranslations = array_merge($currentTranslations, $newTranslations);
                    
                    $existing->update([
                        'title' => $langData['title'],
                        'translations' => $mergedTranslations,
                    ]);
                } else {
                    // Create new
                    AppTranslation::create([
                        'language_code' => $langData['language_code'],
                        'title' => $langData['title'],
                        'status' => $langData['status'] ?? 0,
                        'translations' => (isset($langData['translations']) && is_array($langData['translations'])) ? $langData['translations'] : [],
                    ]);
                }
                $importedCount++;
            }
        }

        return redirect()->route('app-language.index')->with('success', "$importedCount languages imported successfully. Existing languages were updated/merged.");
    }
}
