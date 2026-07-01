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

class BusinessCategoryController extends Controller
{
    public function __construct()
    {
        $this->middleware('permission:BusinessCategory');
    }

    public function index()
    {
        $index['data'] = BusinessCategory::withCount(['subCategories', 'types', 'products'])->get();
        return view("business_category.index", $index);
    }

    public function create()
    {
        return view("business_category.create");
    }

    public function store(Request $request)
    {
        $validation = Validator::make($request->all(), [
            'name' => 'required',
            "icon" => "nullable|mimes:jpg,png,jpeg",
        ]);

        if ($validation->fails()) {
            return back()->withErrors($validation)->withInput();
        } else {
            $id = BusinessCategory::create([
                "name" => $request->get("name"),
            ])->id;

            if(StorageSetting::getStorageSetting("storage") == "DigitalOcean")
            {
                if ($request->file("icon") && $request->file('icon')->isValid()) {
                    $image = $request->file('icon');
                    $file = Str::uuid().'.'.$image->getClientOriginalExtension();
            
                    $path = Storage::disk('spaces')->put('uploads/'.$file, file_get_contents($image),'public');
                    
                    $c = BusinessCategory::find($id);
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

            return redirect()->route("business-category.index");
        }
    }

    public function business_category_status(Request $request)
    {
        $category = BusinessCategory::find($request->get("id"));
        $category->status = ($request->get("checked")=="true")?1:0;
        $category->save();
    }

    public function edit($id)
    {
        $category = BusinessCategory::find($id);
        return view("business_category.edit", compact("category"));
    }

    public function update(Request $request, $id)
    {
        $validation = Validator::make($request->all(), [
            'name' => 'required',
            "icon" => "nullable|mimes:jpg,png,jpeg",
        ]);

        if ($validation->fails()) {
            return back()->withErrors($validation)->withInput();
        } else {
            $category = BusinessCategory::find($request->get("id"));
            $category->name = $request->get("name");
            $category->save();

            if(StorageSetting::getStorageSetting("storage") == "DigitalOcean")
            {
                if ($request->file("icon") && $request->file('icon')->isValid()) {
                    $image = $request->file('icon');
                    $file = Str::uuid().'.'.$image->getClientOriginalExtension();
            
                    $path = Storage::disk('spaces')->put('uploads/'.$file, file_get_contents($image),'public');
                    
                    $c = BusinessCategory::find($request->get('id'));
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

            return redirect()->route('business-category.index');
        }
    }

    private function _deleteCategory($id)
    {
        $businessCategory = BusinessCategory::find($id);
        if(!$businessCategory) return;
        
        $businessFrame = BusinessFrame::where('business_category_id',$id)->get();
        $video = Video::where("business_category_id",$id)->get();
        $businessSubCategory = BusinessSubCategory::where("business_category_id",$id)->get();

        if(StorageSetting::getStorageSetting("storage") == "DigitalOcean")
        {
            Storage::disk('spaces')->delete('uploads/'.$businessCategory->icon);
            foreach($businessFrame as $frame)
            {
                Storage::disk('spaces')->delete('uploads/'.$frame->frame_image);
            }
            foreach($video as $v)
            {
                Storage::disk('spaces')->delete('uploads/video/'.$v->video);
            }
            foreach($businessSubCategory as $subCategory)
            {
                Storage::disk('spaces')->delete('uploads/'.$subCategory->icon);
            }
        }
        else
        {
            if($businessCategory->icon && file_exists(public_path('uploads/').$businessCategory->icon)) {
                unlink(public_path('uploads/').$businessCategory->icon);
            }
            foreach($businessFrame as $frame)
            {
                if($frame->frame_image && file_exists(public_path('uploads/').$frame->frame_image)) unlink(public_path('uploads/').$frame->frame_image);
            }
            foreach($video as $v)
            {
                if($v->video && file_exists('./uploads/video/'.$v->video)) unlink('./uploads/video/'.$v->video);
            }
            foreach($businessSubCategory as $subCategory)
            {
                if($subCategory->icon && file_exists(public_path('uploads/').$subCategory->icon)) unlink(public_path('uploads/').$subCategory->icon);
            }
        }

        BusinessCategory::find($id)->delete();
        BusinessFrame::where('business_category_id',$id)->delete();
        Video::where("business_category_id",$id)->delete();
        BusinessSubCategory::where("business_category_id",$id)->delete();
    }

    public function destroy($id)
    {
        $this->_deleteCategory($id);
        return redirect()->route('business-category.index');
    }

    public function bulkDelete(Request $request)
    {
        $ids = $request->input('ids', []);
        if (is_array($ids) && count($ids) > 0) {
            foreach ($ids as $id) {
                $this->_deleteCategory($id);
            }
            return response()->json(['success' => true, 'message' => count($ids) . ' categories deleted successfully.']);
        }
        return response()->json(['success' => false, 'message' => 'No categories selected.'], 400);
    }

    public function search(Request $request)
    {
        $query = $request->input('query');
        if (empty($query)) {
            $data = BusinessCategory::withCount(['subCategories', 'types', 'products'])->get();
        } else {
            $data = BusinessCategory::withCount(['subCategories', 'types', 'products'])->where('name', 'LIKE', "%{$query}%")->get();
        }
        
        $isDO = StorageSetting::getStorageSetting('storage') == 'DigitalOcean';
        $data->map(function ($item) use ($isDO) {
            $item->icon_url = $item->icon ? ($isDO ? Storage::disk('spaces')->url('uploads/'.$item->icon) : asset('uploads/'.$item->icon)) : '';
            return $item;
        });

        return response()->json(['success' => true, 'data' => $data]);
    }

    private function upload_image($file,$field,$id)
    {
        $destinationPath = public_path('uploads');
        $extension = $file->getClientOriginalExtension();
        $fileName = Str::uuid() . '.' . $extension;
        $file->move($destinationPath, $fileName);
        
        $image = BusinessCategory::find($id);
        $image->$field = $fileName;
        $image->save();
    }

    public function export(Request $request)
    {
        $fileName = 'business_categories.csv';
        $query = $request->input('query');
        
        $dbQuery = BusinessCategory::withCount(['subCategories', 'types', 'products']);
        if (!empty($query)) {
            $dbQuery->where('name', 'LIKE', "%{$query}%");
        }
        $categories = $dbQuery->get();

        $headers = array(
            "Content-type"        => "text/csv",
            "Content-Disposition" => "attachment; filename=$fileName",
            "Pragma"              => "no-cache",
            "Cache-Control"       => "must-revalidate, post-check=0, pre-check=0",
            "Expires"             => "0"
        );

        $columns = array('ID', 'Category Details', 'Sub Categories', 'Business Types', 'Connected Products', 'Status');

        $callback = function() use($categories, $columns) {
            $file = fopen('php://output', 'w');
            fputcsv($file, $columns);

            foreach ($categories as $task) {
                $row['ID']  = $task->id;
                $row['Category Details']    = $task->name;
                $row['Sub Categories'] = $task->sub_categories_count;
                $row['Business Types'] = $task->types_count;
                $row['Connected Products'] = $task->products_count;
                $row['Status']  = $task->status;

                fputcsv($file, array($row['ID'], $row['Category Details'], $row['Sub Categories'], $row['Business Types'], $row['Connected Products'], $row['Status']));
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
                $status = trim($row[3]) === '1' ? 1 : 0;
                
                if (empty($name)) continue;

                if (!empty($id)) {
                    $category = BusinessCategory::find($id);
                    if ($category) {
                        $category->update(['name' => $name, 'status' => $status]);
                    }
                } else {
                    BusinessCategory::create(['name' => $name, 'status' => $status]);
                }
            }
        }
        return redirect()->back()->with('success', 'Business Categories imported successfully.');
    }
}
