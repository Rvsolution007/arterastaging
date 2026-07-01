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

    public function export(Request $request)
    {
        $fileName = 'fonts.csv';
        $query = $request->input('query');
        
        $dbQuery = Font::query();
        if (!empty($query)) {
            $dbQuery->where('name', 'LIKE', "%{$query}%");
        }
        $fonts = $dbQuery->get();

        $headers = array(
            "Content-type"        => "text/csv",
            "Content-Disposition" => "attachment; filename=$fileName",
            "Pragma"              => "no-cache",
            "Cache-Control"       => "must-revalidate, post-check=0, pre-check=0",
            "Expires"             => "0"
        );

        $columns = array('ID', 'Name', 'File Path', 'Status');

        $callback = function() use($fonts, $columns) {
            $file = fopen('php://output', 'w');
            fputcsv($file, $columns);

            foreach ($fonts as $font) {
                $row['ID']  = $font->id;
                $row['Name']    = $font->name;
                $row['File Path'] = $font->file_path;
                $row['Status']  = $font->status;

                fputcsv($file, array($row['ID'], $row['Name'], $row['File Path'], $row['Status']));
            }

            fclose($file);
        };

        return response()->stream($callback, 200, $headers);
    }

    public function import(Request $request)
    {
        $request->validate([
            'file' => 'required|file'
        ]);

        $file = $request->file('file');
        $extension = strtolower($file->getClientOriginalExtension());
        if (!in_array($extension, ['csv', 'txt', 'xls', 'xlsx'])) {
            return redirect()->back()->with('error', 'Please upload a valid CSV file.');
        }

        $handle = fopen($file->path(), "r");
        if (!$handle) {
            return redirect()->back()->with('error', 'Unable to read the uploaded file.');
        }

        $header = fgetcsv($handle, 10000, ",");
        if (!$header) {
            fclose($handle);
            return redirect()->back()->with('error', 'CSV file is empty.');
        }

        while (($row = fgetcsv($handle, 10000, ",")) !== false) {
            if (empty($row) || (count($row) === 1 && trim($row[0]) === '')) continue;

            $id = isset($row[0]) ? trim($row[0]) : '';
            $name = isset($row[1]) ? trim($row[1]) : '';
            
            if (empty($name)) continue;

            $filePath = isset($row[2]) ? trim($row[2]) : '';

            $statusVal = isset($row[3]) ? trim($row[3]) : '1';
            $status = in_array(strtolower($statusVal), ['1', 'active', 'enabled', 'true', 'yes']) ? 1 : (in_array(strtolower($statusVal), ['0', 'inactive', 'disabled', 'false', 'no']) ? 0 : 1);

            if (!empty($id) && is_numeric($id)) {
                $font = Font::find($id);
                if ($font) {
                    $font->update(['name' => $name, 'file_path' => $filePath, 'status' => $status]);
                    continue;
                }
            }

            $font = Font::where('name', $name)->first();
            if ($font) {
                $font->update(['file_path' => $filePath, 'status' => $status]);
            } else {
                Font::create(['name' => $name, 'file_path' => $filePath, 'status' => $status]);
            }
        }
        fclose($handle);

        return redirect()->back()->with('success', 'Fonts imported successfully.');
    }
}
