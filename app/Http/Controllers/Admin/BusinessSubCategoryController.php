<?php

namespace App\Http\Controllers\Admin;

use App\Models\Story;
use App\Models\Video;
use Illuminate\Support\Str;
use Illuminate\Http\Request;
use App\Models\BusinessFrame;
use App\Models\StorageSetting;
use App\Models\BusinessCategory;
use App\Models\BusinessSubCategory;
use App\Http\Controllers\Controller;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Facades\Validator;

class BusinessSubCategoryController extends Controller
{
    public function __construct()
    {
        $this->middleware('permission:BusinessSubCategory');
    }

    public function index(Request $request)
    {
        $sort_col = $request->input('sort_col', 'id');
        $sort_dir = $request->input('sort_dir', 'desc');

        $query = BusinessSubCategory::with('business_category')
            ->join('business_category', 'business_sub_category.business_category_id', '=', 'business_category.id')
            ->select('business_sub_category.*')
            ->withCount('types', 'products');
            
        if ($sort_col === 'types_count') {
            $query->orderBy('types_count', $sort_dir);
        } else if ($sort_col === 'products_count') {
            $query->orderBy('products_count', $sort_dir);
        } else if ($sort_col === 'category_name') {
            $query->orderBy('business_category.name', $sort_dir);
        } else {
            $query->orderBy('business_sub_category.'.$sort_col, $sort_dir);
        }
        
        $index['data'] = $query->paginate(40);
        $index['data']->appends($request->all());

        return view("business_sub_category.index", $index);
    }

    public function create()
    {
        $index['category'] = BusinessCategory::where('status',1)->get();
        return view("business_sub_category.create",$index);
    }

    public function store(Request $request)
    {
        $validation = Validator::make($request->all(), [
            'name' => 'required',
            "business_category_id" => 'required',
            "icon" => "nullable|mimes:jpg,png,jpeg",
        ]);

        if ($validation->fails()) {
            return back()->withErrors($validation)->withInput();
        } else {
            $id = BusinessSubCategory::create([
                "name" => $request->get("name"),
                "business_category_id" => $request->get("business_category_id"),
            ])->id;

            if(StorageSetting::getStorageSetting("storage") == "DigitalOcean")
            {
                if ($request->file("icon") && $request->file('icon')->isValid()) {
                    $image = $request->file('icon');
                    $file = Str::uuid().'.'.$image->getClientOriginalExtension();
            
                    $path = Storage::disk('spaces')->put('uploads/'.$file, file_get_contents($image),'public');
                    
                    $c = BusinessSubCategory::find($id);
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

            return redirect()->route("business-sub-category.index");
        }
    }

    public function business_sub_category_status(Request $request)
    {
        $category = BusinessSubCategory::find($request->get("id"));
        $category->status = ($request->get("checked")=="true")?1:0;
        $category->save();
    }

    public function updateSortOrder(Request $request)
    {
        $category = BusinessSubCategory::find($request->get("id"));
        if($category) {
            $category->sort_order = $request->get("sort_order");
            $category->save();
        }
        return response()->json(['success' => true]);
    }

    public function edit($id)
    {
        $category = BusinessSubCategory::find($id);
        $businessCategory = BusinessCategory::where('status',1)->get();
        return view("business_sub_category.edit", compact("category","businessCategory"));
    }

    public function update(Request $request, $id)
    {
        $validation = Validator::make($request->all(), [
            'name' => 'required',
            "business_category_id" => 'required',
            "icon" => "nullable|mimes:jpg,png,jpeg",
        ]);

        if ($validation->fails()) {
            return back()->withErrors($validation)->withInput();
        } else {
            $category = BusinessSubCategory::find($request->get("id"));
            $category->name = $request->get("name");
            $category->business_category_id = $request->get("business_category_id");
            $category->save();

            if(StorageSetting::getStorageSetting("storage") == "DigitalOcean")
            {
                if ($request->file("icon") && $request->file('icon')->isValid()) {
                    $image = $request->file('icon');
                    $file = Str::uuid().'.'.$image->getClientOriginalExtension();
            
                    $path = Storage::disk('spaces')->put('uploads/'.$file, file_get_contents($image),'public');
                    
                    $c = BusinessSubCategory::find($request->get('id'));
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

            return redirect()->route('business-sub-category.index');
        }
    }

    private function _deleteSubCategory($id)
    {
        $businessSubCategory = BusinessSubCategory::find($id);
        if(!$businessSubCategory) return;
        
        $businessFrame = BusinessFrame::where('business_sub_category_id',$id)->get();

        if(StorageSetting::getStorageSetting("storage") == "DigitalOcean")
        {
            Storage::disk('spaces')->delete('uploads/'.$businessSubCategory->icon);
            foreach($businessFrame as $frame)
            {
                Storage::disk('spaces')->delete('uploads/'.$frame->frame_image);
            }
        }
        else
        {
            if($businessSubCategory->icon && file_exists(public_path('uploads/').$businessSubCategory->icon)) {
                unlink(public_path('uploads/').$businessSubCategory->icon);
            }
            foreach($businessFrame as $frame)
            {
                if($frame->frame_image && file_exists(public_path('uploads/').$frame->frame_image)) unlink(public_path('uploads/').$frame->frame_image);
            }
        }

        BusinessSubCategory::find($id)->delete();
        BusinessFrame::where('business_sub_category_id',$id)->delete();
    }

    public function destroy($id)
    {
        $this->_deleteSubCategory($id);
        return redirect()->route('business-sub-category.index');
    }

    public function bulkDelete(Request $request)
    {
        $ids = $request->input('ids');
        if (is_array($ids) && count($ids) > 0) {
            foreach ($ids as $id) {
                $this->_deleteSubCategory($id);
            }
            return response()->json(['success' => true, 'message' => count($ids) . ' sub categories deleted successfully.']);
        }
        return response()->json(['success' => false, 'message' => 'No sub categories selected.'], 400);
    }

    public function search(Request $request)
    {
        $query = $request->input('query');
        $sort_col = $request->input('sort_col', 'id');
        $sort_dir = $request->input('sort_dir', 'desc');

        $dbQuery = BusinessSubCategory::with('business_category')
            ->join('business_category', 'business_sub_category.business_category_id', '=', 'business_category.id')
            ->select('business_sub_category.*')
            ->withCount('types', 'products');

        if (!empty($query)) {
            $dbQuery->where(function($q) use ($query) {
                $q->where('business_sub_category.name', 'LIKE', "%{$query}%")
                  ->orWhere('business_category.name', 'LIKE', "%{$query}%");
            });
        }

        if ($sort_col === 'types_count') {
            $dbQuery->orderBy('types_count', $sort_dir);
        } else if ($sort_col === 'products_count') {
            $dbQuery->orderBy('products_count', $sort_dir);
        } else if ($sort_col === 'category_name') {
            $dbQuery->orderBy('business_category.name', $sort_dir);
        } else {
            $dbQuery->orderBy('business_sub_category.'.$sort_col, $sort_dir);
        }

        $data = $dbQuery->paginate(40);
        
        $data->appends(['query' => $query, 'sort_col' => $sort_col, 'sort_dir' => $sort_dir]);

        $isDO = StorageSetting::getStorageSetting('storage') == 'DigitalOcean';
        $data->getCollection()->transform(function ($item) use ($isDO) {
            $item->icon_url = $item->icon ? ($isDO ? Storage::disk('spaces')->url('uploads/'.$item->icon) : asset('uploads/'.$item->icon)) : '';
            $item->category_name = $item->business_category ? $item->business_category->name : '--';
            return $item;
        });

        return response()->json([
            'success' => true, 
            'data' => $data->items(),
            'pagination' => (string) $data->links('pagination::bootstrap-4')
        ]);
    }

    private function upload_image($file,$field,$id)
    {
        $destinationPath = public_path('uploads');
        $extension = $file->getClientOriginalExtension();
        $fileName = Str::uuid() . '.' . $extension;
        $file->move($destinationPath, $fileName);
        
        $image = BusinessSubCategory::find($id);
        $image->$field = $fileName;
        $image->save();
    }
    public function export(Request $request)
    {
        $fileName = 'business_sub_categories.csv';
        
        $query = $request->input('query');
        
        $dbQuery = BusinessSubCategory::with('business_category')
            ->join('business_category', 'business_sub_category.business_category_id', '=', 'business_category.id')
            ->select('business_sub_category.*')
            ->withCount('types', 'products');
            
        if (!empty($query)) {
            $dbQuery->where(function($q) use ($query) {
                $q->where('business_sub_category.name', 'LIKE', "%{$query}%")
                  ->orWhere('business_category.name', 'LIKE', "%{$query}%");
            });
        }
        
        $subCategories = $dbQuery->get();

        $headers = array(
            "Content-type"        => "text/csv",
            "Content-Disposition" => "attachment; filename=$fileName",
            "Pragma"              => "no-cache",
            "Cache-Control"       => "must-revalidate, post-check=0, pre-check=0",
            "Expires"             => "0"
        );

        $columns = array('ID', 'Sub Category Details', 'Linked Business Types', 'Connected Products', 'Parent Category', 'Status');

        $callback = function() use($subCategories, $columns) {
            $file = fopen('php://output', 'w');
            fputcsv($file, $columns);

            foreach ($subCategories as $task) {
                $row['ID']  = $task->id;
                $row['Sub Category Details']    = $task->name;
                $row['Linked Business Types'] = $task->types_count;
                $row['Connected Products'] = $task->products_count;
                $row['Parent Category']  = $task->business_category ? $task->business_category->name : '';
                $row['Status']  = $task->status;

                fputcsv($file, array($row['ID'], $row['Sub Category Details'], $row['Linked Business Types'], $row['Connected Products'], $row['Parent Category'], $row['Status']));
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

        if ($request->hasFile('file')) {
            $file = $request->file('file');
            $handle = fopen($file->path(), "r");
            $header = true;
            
            while ($csvLine = fgetcsv($handle, 1000, ",")) {
                if ($header) {
                    $header = false;
                    continue; // Skip header row
                }
                
                $id = isset($csvLine[0]) ? trim($csvLine[0]) : '';
                $name = isset($csvLine[1]) ? trim($csvLine[1]) : '';
                $categoryId = isset($csvLine[2]) ? trim($csvLine[2]) : '';
                $status = isset($csvLine[5]) ? trim($csvLine[5]) : 1;

                if(!empty($name) && !empty($categoryId)) {
                    if (!empty($id)) {
                        BusinessSubCategory::where('id', $id)->update([
                            'name' => $name,
                            'business_category_id' => $categoryId,
                            'status' => $status
                        ]);
                    } else {
                        BusinessSubCategory::create([
                            'name' => $name,
                            'business_category_id' => $categoryId,
                            'status' => $status
                        ]);
                    }
                }
            }
            fclose($handle);
        }

        return redirect()->back()->with('success', 'Business Sub Categories imported successfully.');
    }
}
