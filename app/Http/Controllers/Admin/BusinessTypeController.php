<?php

namespace App\Http\Controllers\Admin;

use Illuminate\Support\Str;
use Illuminate\Http\Request;
use App\Models\StorageSetting;
use App\Models\BusinessCategory;
use App\Models\BusinessSubCategory;
use App\Models\BusinessType;
use App\Models\BusinessProduct;
use App\Http\Controllers\Controller;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Facades\Validator;

class BusinessTypeController extends Controller
{
    public function __construct()
    {
        // $this->middleware('permission:BusinessType');
    }

    public function index()
    {
        $index['data'] = BusinessType::with('business_sub_category.business_category')->withCount('products')->paginate(50);
        return view("business_type.index", $index);
    }

    public function create()
    {
        $index['category'] = BusinessCategory::where('status',1)->get();
        return view("business_type.create",$index);
    }

    public function store(Request $request)
    {
        $validation = Validator::make($request->all(), [
            'name' => 'required',
            "business_sub_category_id" => 'required',
            "icon" => "nullable|mimes:jpg,png,jpeg",
        ]);

        if ($validation->fails()) {
            return back()->withErrors($validation)->withInput();
        } else {
            $id = BusinessType::create([
                "name" => $request->get("name"),
                "business_sub_category_id" => $request->get("business_sub_category_id"),
            ])->id;

            if(StorageSetting::getStorageSetting("storage") == "DigitalOcean")
            {
                if ($request->file("icon") && $request->file('icon')->isValid()) {
                    $image = $request->file('icon');
                    $file = Str::uuid().'.'.$image->getClientOriginalExtension();
            
                    $path = Storage::disk('spaces')->put('uploads/'.$file, file_get_contents($image),'public');
                    
                    $c = BusinessType::find($id);
                    $c->icon = $file;
                    $c->save();
                }
            }
            else
            {
                if ($request->file("icon") && $request->file('icon')->isValid()) {
                    $this->upload_image($request->file("icon"),"icon", $id);
                }
            }

            return redirect()->route("business-type.index");
        }
    }

    public function business_type_status(Request $request)
    {
        $type = BusinessType::find($request->get("id"));
        $type->status = ($request->get("checked")=="true")?1:0;
        $type->save();
    }

    public function edit($id)
    {
        $type = BusinessType::find($id);
        $businessCategory = BusinessCategory::where('status',1)->get();
        $businessSubCategory = BusinessSubCategory::where('business_category_id', $type->business_sub_category->business_category_id ?? null)->where('status',1)->get();
        return view("business_type.edit", compact("type","businessCategory", "businessSubCategory"));
    }

    public function update(Request $request, $id)
    {
        $validation = Validator::make($request->all(), [
            'name' => 'required',
            "business_sub_category_id" => 'required',
            "icon" => "nullable|mimes:jpg,png,jpeg",
        ]);

        if ($validation->fails()) {
            return back()->withErrors($validation)->withInput();
        } else {
            $type = BusinessType::find($request->get("id"));
            $type->name = $request->get("name");
            $type->business_sub_category_id = $request->get("business_sub_category_id");
            $type->save();

            if(StorageSetting::getStorageSetting("storage") == "DigitalOcean")
            {
                if ($request->file("icon") && $request->file('icon')->isValid()) {
                    $image = $request->file('icon');
                    $file = Str::uuid().'.'.$image->getClientOriginalExtension();
            
                    $path = Storage::disk('spaces')->put('uploads/'.$file, file_get_contents($image),'public');
                    
                    $c = BusinessType::find($request->get('id'));
                    $c->icon = $file;
                    $c->save();
                }
            }
            else
            {
                if ($request->file("icon") && $request->file('icon')->isValid()) {
                    $this->upload_image($request->file("icon"),"icon", $id);
                }
            }

            return redirect()->route('business-type.index');
        }
    }

    private function _deleteType($id)
    {
        $businessType = BusinessType::find($id);
        if(!$businessType) return;
        
        if(StorageSetting::getStorageSetting("storage") == "DigitalOcean")
        {
            if($businessType->icon) Storage::disk('spaces')->delete('uploads/'.$businessType->icon);
        }
        else
        {
            if($businessType->icon && file_exists(public_path('uploads/').$businessType->icon)) {
                unlink(public_path('uploads/').$businessType->icon);
            }
        }

        BusinessType::find($id)->delete();
        BusinessProduct::where('business_type_id',$id)->update(['business_type_id' => null]);
    }

    public function destroy($id)
    {
        $this->_deleteType($id);
        return redirect()->route('business-type.index');
    }

    public function bulkDelete(Request $request)
    {
        $ids = $request->input('ids');
        if (is_array($ids) && count($ids) > 0) {
            foreach ($ids as $id) {
                $this->_deleteType($id);
            }
            return response()->json(['success' => true, 'message' => count($ids) . ' types deleted successfully.']);
        }
        return response()->json(['success' => false, 'message' => 'No types selected.'], 400);
    }

    public function search(Request $request)
    {
        $query = $request->input('query');
        if (empty($query)) {
            $data = BusinessType::with('business_sub_category.business_category')->withCount('products')->paginate(50);
        } else {
            $data = BusinessType::with('business_sub_category.business_category')->withCount('products')
                ->where('name', 'LIKE', "%{$query}%")
                ->orWhereHas('business_sub_category', function($q) use ($query) {
                    $q->where('name', 'LIKE', "%{$query}%")
                      ->orWhereHas('business_category', function($q2) use ($query) {
                          $q2->where('name', 'LIKE', "%{$query}%");
                      });
                })->paginate(50);
        }
        
        $data->appends(['query' => $query]);

        $isDO = StorageSetting::getStorageSetting('storage') == 'DigitalOcean';
        $data->getCollection()->transform(function ($item) use ($isDO) {
            $item->icon_url = $item->icon ? ($isDO ? Storage::disk('spaces')->url('uploads/'.$item->icon) : asset('uploads/'.$item->icon)) : '';
            $item->sub_category_name = $item->business_sub_category ? $item->business_sub_category->name : '--';
            $item->category_name = ($item->business_sub_category && $item->business_sub_category->business_category) ? $item->business_sub_category->business_category->name : '--';
            return $item;
        });

        return response()->json([
            'success' => true, 
            'data' => $data->items(),
            'pagination' => (string) $data->links('pagination::bootstrap-4')
        ]);
    }

    // Helper for ajax dependent dropdown
    public function getSubCategories(Request $request)
    {
        $subCategories = BusinessSubCategory::where('business_category_id', $request->category_id)->where('status', 1)->get();
        return response()->json($subCategories);
    }

    private function upload_image($file,$field,$id)
    {
        $destinationPath = public_path('uploads');
        $extension = $file->getClientOriginalExtension();
        $fileName = Str::uuid() . '.' . $extension;
        $file->move($destinationPath, $fileName);
        
        $image = BusinessType::find($id);
        $image->$field = $fileName;
        $image->save();
    }

    public function export(Request $request)
    {
        $fileName = 'business_types.csv';
        $query = $request->input('query');
        
        $dbQuery = BusinessType::with('business_sub_category.business_category');
        
        if (!empty($query)) {
            $dbQuery->where('name', 'LIKE', "%{$query}%")
                ->orWhereHas('business_sub_category', function($q) use ($query) {
                    $q->where('name', 'LIKE', "%{$query}%")
                      ->orWhereHas('business_category', function($q2) use ($query) {
                          $q2->where('name', 'LIKE', "%{$query}%");
                      });
                });
        }
        
        $types = $dbQuery->get();

        $headers = array(
            "Content-type"        => "text/csv",
            "Content-Disposition" => "attachment; filename=$fileName",
            "Pragma"              => "no-cache",
            "Cache-Control"       => "must-revalidate, post-check=0, pre-check=0",
            "Expires"             => "0"
        );

        $columns = array('ID', 'Business Type Details', 'Parent Sub Category', 'Parent Category', 'Status');

        $callback = function() use($types, $columns) {
            $file = fopen('php://output', 'w');
            fputcsv($file, $columns);

            foreach ($types as $task) {
                $row['ID']  = $task->id;
                $row['Business Type Details']    = $task->name;
                $row['Parent Sub Category'] = $task->business_sub_category ? $task->business_sub_category->name : '';
                $row['Parent Category']  = ($task->business_sub_category && $task->business_sub_category->business_category) ? $task->business_sub_category->business_category->name : '';
                $row['Status']  = $task->status;

                fputcsv($file, array($row['ID'], $row['Business Type Details'], $row['Parent Sub Category'], $row['Parent Category'], $row['Status']));
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
            if (count($row) >= 5) {
                $id = trim($row[0]);
                $name = trim($row[1]);
                $status = trim($row[4]) === '1' ? 1 : 0;
                
                if (empty($name)) continue;

                if (!empty($id)) {
                    $type = BusinessType::find($id);
                    if ($type) {
                        $type->update(['name' => $name, 'status' => $status]);
                    }
                } else {
                    // We can't properly assign sub category without its ID, so we skip new creations or assign a default if possible.
                    // Assuming for now that import updates existing primarily, or if created, it will be unassigned (which might violate DB schema).
                    // Best is to just attempt to create with null subcategory or ignore new.
                    // For safety, we will just update existing ones based on ID.
                }
            }
        }
        return redirect()->back()->with('success', 'Business Types imported successfully. (Note: Only existing records can be updated via import as sub-category mapping is required for new types).');
    }
}
