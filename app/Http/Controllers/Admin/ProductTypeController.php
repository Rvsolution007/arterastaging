<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use App\Models\ProductType;
use Illuminate\Support\Str;

class ProductTypeController extends Controller
{
    public function index()
    {
        $data = ProductType::orderBy('id', 'DESC')->paginate(50);
        return view('product_type.index', compact('data'));
    }

    public function create()
    {
        return view('product_type.create');
    }

    public function store(Request $request)
    {
        $request->validate([
            'name' => 'required|string|max:255',
        ]);

        $slug = Str::slug($request->name);
        // Ensure unique slug
        $count = ProductType::where('slug', 'LIKE', "{$slug}%")->count();
        if ($count > 0) {
            $slug = $slug . '-' . ($count + 1);
        }

        ProductType::create([
            'name' => $request->name,
            'slug' => $slug,
            'status' => $request->status ? 1 : 0
        ]);

        return redirect()->route('product-type.index')->with('success', 'Product Type created successfully.');
    }

    public function edit($id)
    {
        $type = ProductType::findOrFail($id);
        return view('product_type.edit', compact('type'));
    }

    public function update(Request $request, $id)
    {
        $request->validate([
            'name' => 'required|string|max:255',
        ]);

        $type = ProductType::findOrFail($id);
        
        $slug = Str::slug($request->name);
        // Ensure unique slug excluding self
        $count = ProductType::where('slug', 'LIKE', "{$slug}%")->where('id', '!=', $id)->count();
        if ($count > 0) {
            $slug = $slug . '-' . ($count + 1);
        }

        $type->update([
            'name' => $request->name,
            'slug' => $slug,
            'status' => $request->status ? 1 : 0
        ]);

        return redirect()->route('product-type.index')->with('success', 'Product Type updated successfully.');
    }

    public function destroy($id)
    {
        $type = ProductType::findOrFail($id);
        $type->delete();
        return redirect()->route('product-type.index')->with('success', 'Product Type deleted successfully.');
    }

    public function bulkDelete(\Illuminate\Http\Request $request)
    {
        $ids = $request->input('ids');
        if (is_array($ids) && count($ids) > 0) {
            ProductType::whereIn('id', $ids)->delete();
            return response()->json(['success' => true, 'message' => count($ids) . ' product types deleted successfully.']);
        }
        return response()->json(['success' => false, 'message' => 'No product types selected.'], 400);
    }

    public function search(Request $request)
    {
        $query = $request->input('query');
        
        $types = clone ProductType::query();
        if ($query) {
            $types->where('name', 'like', '%' . $query . '%')
                  ->orWhere('slug', 'like', '%' . $query . '%');
        }
        
        $data = $types->orderBy('id', 'desc')->get();
        
        return response()->json([
            'success' => true,
            'data' => $data
        ]);
    }

    public function export(Request $request)
    {
        $fileName = 'product_types.csv';
        $query = $request->input('query');
        
        $dbQuery = ProductType::query();
        if (!empty($query)) {
            $dbQuery->where('name', 'LIKE', "%{$query}%")
                    ->orWhere('slug', 'LIKE', "%{$query}%");
        }
        $types = $dbQuery->get();

        $headers = array(
            "Content-type"        => "text/csv",
            "Content-Disposition" => "attachment; filename=$fileName",
            "Pragma"              => "no-cache",
            "Cache-Control"       => "must-revalidate, post-check=0, pre-check=0",
            "Expires"             => "0"
        );

        $columns = array('ID', 'Name', 'Slug', 'Status');

        $callback = function() use($types, $columns) {
            $file = fopen('php://output', 'w');
            fputcsv($file, $columns);

            foreach ($types as $task) {
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
                    $type = ProductType::find($id);
                    if ($type) {
                        $type->update(['name' => $name, 'slug' => $slug, 'status' => $status]);
                    }
                } else {
                    ProductType::create(['name' => $name, 'slug' => $slug, 'status' => $status]);
                }
            }
        }
        return redirect()->back()->with('success', 'Product Types imported successfully.');
    }
}
