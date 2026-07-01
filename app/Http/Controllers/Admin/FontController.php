<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Font;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\File;

class FontController extends Controller
{
    public function index()
    {
        $fonts = Font::orderBy('name', 'asc')->get();
        return view('admin.fonts.index', compact('fonts'));
    }

    public function create()
    {
        return view('admin.fonts.create');
    }

    public function store(Request $request)
    {
        $request->validate([
            'file' => 'required|file|max:20480' // Allow up to 20MB
        ]);

        if ($request->hasFile('file')) {
            $file = $request->file('file');
            $extension = strtolower($file->getClientOriginalExtension());
            
            // Manually validate extension since font MIME types are inconsistent
            if (!in_array($extension, ['ttf', 'otf', 'woff', 'woff2'])) {
                return response()->json(['success' => false, 'message' => 'The file must be a file of type: ttf, otf, woff, woff2.'], 422);
            }
            $originalName = $file->getClientOriginalName();
            $fontName = pathinfo($originalName, PATHINFO_FILENAME);
            $filename = time() . '_' . $originalName;
            
            $file->move(public_path('uploads/fonts'), $filename);

            // Check if font already exists, if so, update path, otherwise create
            $font = Font::updateOrCreate(
                ['name' => $fontName],
                [
                    'file_path' => 'uploads/fonts/' . $filename,
                    'status' => 1
                ]
            );

            return response()->json([
                'success' => true, 
                'message' => 'Font uploaded successfully',
                'font' => $font
            ]);
        }

        return response()->json(['success' => false, 'message' => 'No file uploaded'], 400);
    }

    public function update(Request $request, $id)
    {
        $request->validate([
            'name' => 'required|string|max:255'
        ]);

        $font = Font::findOrFail($id);
        $font->name = $request->name;
        $font->save();

        return redirect()->route('admin.fonts.index')->with('success', 'Font name updated successfully.');
    }

    public function destroy($id)
    {
        $font = Font::findOrFail($id);
        
        // Delete file
        if (File::exists(public_path($font->file_path))) {
            File::delete(public_path($font->file_path));
        }

        $font->delete();
        return redirect()->route('admin.fonts.index')->with('success', 'Font deleted successfully.');
    }
}
