<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use App\Models\Brand;
use Illuminate\Support\Str;

class BrandController extends Controller
{
    public function index()
    {
        $data = Brand::orderBy('id', 'DESC')->paginate(50);
        return view('brand.index', compact('data'));
    }

    public function create()
    {
        return view('brand.create');
    }

    public function store(Request $request)
    {
        $request->validate([
            'name' => 'required|string|max:255',
        ]);

        $slug = Str::slug($request->name);
        $count = Brand::where('slug', 'LIKE', "{$slug}%")->count();
        if ($count > 0) {
            $slug = $slug . '-' . ($count + 1);
        }

        Brand::create([
            'name' => $request->name,
            'slug' => $slug,
            'status' => $request->status ? 1 : 0
        ]);

        return redirect()->route('brand.index')->with('success', 'Brand created successfully.');
    }

    public function edit($id)
    {
        $brand = Brand::findOrFail($id);
        return view('brand.edit', compact('brand'));
    }

    public function update(Request $request, $id)
    {
        $request->validate([
            'name' => 'required|string|max:255',
        ]);

        $brand = Brand::findOrFail($id);
        
        $slug = Str::slug($request->name);
        $count = Brand::where('slug', 'LIKE', "{$slug}%")->where('id', '!=', $id)->count();
        if ($count > 0) {
            $slug = $slug . '-' . ($count + 1);
        }

        $brand->update([
            'name' => $request->name,
            'slug' => $slug,
            'status' => $request->status ? 1 : 0
        ]);

        return redirect()->route('brand.index')->with('success', 'Brand updated successfully.');
    }

    public function destroy($id)
    {
        $brand = Brand::findOrFail($id);
        $brand->delete();
        return redirect()->route('brand.index')->with('success', 'Brand deleted successfully.');
    }

    public function brand_status(Request $request)
    {
        $brand = Brand::find($request->id);
        if ($brand) {
            $brand->status = $request->checked == 'true' ? 1 : 0;
            $brand->save();
            return response()->json(['success' => true]);
        }
        return response()->json(['success' => false]);
    }

    public function bulkDelete(Request $request)
    {
        if ($request->ids && is_array($request->ids)) {
            Brand::whereIn('id', $request->ids)->delete();
            return response()->json(['success' => true, 'message' => 'Selected brands deleted successfully.']);
        }
        return response()->json(['success' => false, 'message' => 'No brands selected.']);
    }

    public function search(Request $request)
    {
        $query = $request->input('query');
        
        $brands = clone Brand::query();
        if ($query) {
            $brands->where('name', 'like', '%' . $query . '%')
                  ->orWhere('slug', 'like', '%' . $query . '%');
        }
        
        $data = $brands->orderBy('id', 'desc')->get();
        
        return response()->json([
            'success' => true,
            'data' => $data
        ]);
    }

    public function export(Request $request)
    {
        $fileName = 'brands.csv';
        $query = $request->input('query');
        
        $dbQuery = Brand::query();
        if (!empty($query)) {
            $dbQuery->where('name', 'LIKE', "%{$query}%")
                    ->orWhere('slug', 'LIKE', "%{$query}%");
        }
        $brands = $dbQuery->get();

        $headers = array(
            "Content-type"        => "text/csv",
            "Content-Disposition" => "attachment; filename=$fileName",
            "Pragma"              => "no-cache",
            "Cache-Control"       => "must-revalidate, post-check=0, pre-check=0",
            "Expires"             => "0"
        );

        $columns = array('ID', 'Name', 'Slug', 'Status');

        $callback = function() use($brands, $columns) {
            $file = fopen('php://output', 'w');
            fputcsv($file, $columns);

            foreach ($brands as $task) {
                $row['ID']  = $task->id;
                $row['Name']    = $task->name;
                $row['Slug'] = $task->slug;
                $row['Status']  = $task->status;

                fputcsv($file, array($row['ID'], $row['Name'], $row['Slug'], $row['Status']));
            }

            fclose($file);
        };

        return response()->stream($callback, 200, $headers);
    }

    public function import(Request $request)
    {
        $request->validate([
            'file' => 'required|mimes:csv,txt'
        ]);

        $file = $request->file('file');
        $csvData = file_get_contents($file);
        $rows = array_map("str_getcsv", explode("\n", $csvData));
        $header = array_shift($rows);

        foreach ($rows as $row) {
            if (count($row) >= 4) {
                $id = trim($row[0]);
                $name = trim($row[1]);
                $slug = trim($row[2]);
                $status = trim($row[3]) === '1' ? 1 : 0;
                
                if (empty($name)) continue;
                if (empty($slug)) $slug = \Str::slug($name);

                if (!empty($id)) {
                    $brand = Brand::find($id);
                    if ($brand) {
                        $brand->update(['name' => $name, 'slug' => $slug, 'status' => $status]);
                    }
                } else {
                    Brand::create(['name' => $name, 'slug' => $slug, 'status' => $status]);
                }
            }
        }
        return redirect()->back()->with('success', 'Brands imported successfully.');
    }
}
