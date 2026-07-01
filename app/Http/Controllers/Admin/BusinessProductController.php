<?php

namespace App\Http\Controllers\Admin;

use Illuminate\Support\Str;
use Illuminate\Http\Request;
use App\Models\StorageSetting;
use App\Models\BusinessSubCategory;
use App\Models\BusinessCategory;
use App\Models\BusinessType;
use App\Models\BusinessProduct;
use App\Http\Controllers\Controller;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Facades\Validator;

class BusinessProductController extends Controller
{
    public function __construct()
    {
        $this->middleware('permission:BusinessCategory'); // using same permission or maybe create new? sticking to BusinessCategory for now
    }

    public function index()
    {
        $index['data'] = BusinessProduct::with(['businessCategory', 'businessSubCategory', 'businessType', 'productType'])->paginate(50);
        return view("business_product.index", $index);
    }

    public function create()
    {
        $index['categories'] = BusinessCategory::where('status', 1)->get();
        $index['productTypes'] = \App\Models\ProductType::where('status', 1)->get();
        $index['brands'] = \App\Models\Brand::where('status', 1)->get();
        return view("business_product.create", $index);
    }

    public function store(Request $request)
    {
        $validation = Validator::make($request->all(), [
            'name' => 'required',
            'business_category_id' => 'required',
            'business_sub_category_id' => 'required',
            'product_type_id' => 'required',
            "icon" => "nullable|mimes:jpg,png,jpeg",
        ]);

        if ($validation->fails()) {
            return back()->withErrors($validation)->withInput();
        } else {
            $product = BusinessProduct::create([
                "name" => $request->get("name"),
                "slug" => \Illuminate\Support\Str::slug($request->get("name")) . '-' . time(),
                "business_category_id" => $request->get("business_category_id"),
                "business_sub_category_id" => $request->get("business_sub_category_id"),
                "business_type_id" => $request->get("business_type_id"),
                "product_type_id" => $request->get("product_type_id"),
                "brand_id" => $request->get("brand_id"),
                "keywords" => $request->get("keywords"),
                "sort_order" => $request->get("sort_order") ?? 0,
            ]);
            $id = $product->id;

            if(StorageSetting::getStorageSetting("storage") == "DigitalOcean")
            {
                if ($request->file("icon") && $request->file('icon')->isValid()) {
                    $image = $request->file('icon');
                    $file = Str::uuid().'.'.$image->getClientOriginalExtension();
            
                    $path = Storage::disk('spaces')->put('uploads/'.$file, file_get_contents($image),'public');
                    
                    $product->icon = $file;
                    $product->save();
                }
            }
            else
            {
                if ($request->file("icon") && $request->file('icon')->isValid()) {
                    $this->upload_image($request->file("icon"), "icon", $id);
                }
            }

            return redirect()->route("business-product.index");
        }
    }

    public function business_product_status(Request $request)
    {
        $product = BusinessProduct::find($request->get("id"));
        $product->status = ($request->get("checked")=="true")?1:0;
        $product->save();
    }

    public function edit($id)
    {
        $product = BusinessProduct::find($id);
        $categories = BusinessCategory::where('status', 1)->get();
        $subCategories = BusinessSubCategory::where('business_category_id', $product->business_category_id)->where('status', 1)->get();
        $businessTypes = BusinessType::where('business_sub_category_id', $product->business_sub_category_id)->where('status', 1)->get();
        $productTypes = \App\Models\ProductType::where('status', 1)->get();
        $brands = \App\Models\Brand::where('status', 1)->get();
        return view("business_product.edit", compact("product", "categories", "subCategories", "businessTypes", "productTypes", "brands"));
    }

    public function update(Request $request, $id)
    {
        $validation = Validator::make($request->all(), [
            'name' => 'required',
            'business_category_id' => 'required',
            'business_sub_category_id' => 'required',
            'product_type_id' => 'required',
            "icon" => "nullable|mimes:jpg,png,jpeg",
        ]);

        if ($validation->fails()) {
            return back()->withErrors($validation)->withInput();
        } else {
            $product = BusinessProduct::find($id);

            $product->name = $request->get("name");
            if(!$product->slug) {
                $product->slug = \Illuminate\Support\Str::slug($request->get("name")) . '-' . time();
            }
            $product->business_category_id = $request->get("business_category_id");
            $product->business_sub_category_id = $request->get("business_sub_category_id");
            $product->business_type_id = $request->get("business_type_id");
            $product->product_type_id = $request->get("product_type_id");
            $product->brand_id = $request->get("brand_id");
            $product->keywords = $request->get("keywords");
            $product->sort_order = $request->get("sort_order") ?? 0;
            $product->save();

            if(StorageSetting::getStorageSetting("storage") == "DigitalOcean")
            {
                if ($request->file("icon") && $request->file('icon')->isValid()) {
                    $image = $request->file('icon');
                    $file = Str::uuid().'.'.$image->getClientOriginalExtension();
            
                    $path = Storage::disk('spaces')->put('uploads/'.$file, file_get_contents($image),'public');
                    
                    $product->icon = $file;
                    $product->save();
                }
            }
            else
            {
                if ($request->file("icon") && $request->file('icon')->isValid()) {
                    $this->upload_image($request->file("icon"), "icon", $id);
                }
            }

            return redirect()->route('business-product.index');
        }
    }

    private function _deleteProduct($id)
    {
        $product = BusinessProduct::find($id);
        if(!$product) return;

        if($product->icon) {
            if(StorageSetting::getStorageSetting("storage") == "DigitalOcean")
            {
                Storage::disk('spaces')->delete('uploads/'.$product->icon);
            }
            else
            {
                if(file_exists(public_path('uploads/').$product->icon)) {
                    unlink(public_path('uploads/').$product->icon);
                }
            }
        }

        $product->delete();
    }

    public function destroy($id)
    {
        $product = BusinessProduct::find($id);
        $product->delete();
        return redirect()->route("business-product.index");
    }

    public function getSubCategories(Request $request)
    {
        $categoryId = $request->get('category_id');
        $subCategories = BusinessSubCategory::where('business_category_id', $categoryId)
                            ->where('status', 1)
                            ->get();
        return response()->json(['subCategories' => $subCategories]);
    }

    public function getBusinessTypes(Request $request)
    {
        $subCategoryId = $request->get('sub_category_id');
        $businessTypes = BusinessType::where('business_sub_category_id', $subCategoryId)
                            ->where('status', 1)
                            ->get();
        return response()->json(['businessTypes' => $businessTypes]);
    }

    public function bulkDelete(Request $request)
    {
        $ids = $request->input('ids', []);
        if (is_array($ids) && count($ids) > 0) {
            foreach ($ids as $id) {
                $this->_deleteProduct($id);
            }
            return response()->json(['success' => true, 'message' => count($ids) . ' products deleted successfully.']);
        }
        return response()->json(['success' => false, 'message' => 'No products selected.'], 400);
    }

    public function search(Request $request)
    {
        $query = $request->input('query');
        if (empty($query)) {
            $data = BusinessProduct::with(['businessCategory', 'businessSubCategory', 'businessType', 'productType'])->get();
        } else {
            $data = BusinessProduct::with(['businessCategory', 'businessSubCategory', 'businessType', 'productType'])
                ->where('name', 'LIKE', "%{$query}%")
                ->orWhereHas('businessCategory', function($q) use ($query) {
                    $q->where('name', 'LIKE', "%{$query}%");
                })
                ->orWhereHas('businessSubCategory', function($q) use ($query) {
                    $q->where('name', 'LIKE', "%{$query}%");
                })
                ->orWhereHas('businessType', function($q) use ($query) {
                    $q->where('name', 'LIKE', "%{$query}%");
                })->get();
        }
        
        $isDO = StorageSetting::getStorageSetting('storage') == 'DigitalOcean';
        $data->map(function ($item) use ($isDO) {
            $item->icon_url = $item->icon ? ($isDO ? Storage::disk('spaces')->url('uploads/'.$item->icon) : asset('uploads/'.$item->icon)) : '';
            $item->type_name = $item->businessType ? $item->businessType->name : '';
            $item->sub_category_name = $item->businessSubCategory ? $item->businessSubCategory->name : '--';
            $item->category_name = $item->businessCategory ? $item->businessCategory->name : '--';
            $item->product_type_name = $item->productType ? $item->productType->name : '--';
            $item->brands_list = $item->brands->pluck('name')->implode(', ') ?: '--';
            return $item;
        });

        return response()->json(['success' => true, 'data' => $data]);
    }


    private function upload_image($file, $field, $id)
    {
        $destinationPath = public_path('uploads');
        $extension = $file->getClientOriginalExtension();
        $fileName = Str::uuid() . '.' . $extension;
        $file->move($destinationPath, $fileName);
        
        $image = BusinessProduct::find($id);
        $image->$field = $fileName;
        $image->save();
    }

    public function export(Request $request)
    {
        $fileName = 'business_products.csv';
        $query = $request->input('query');
        
        $dbQuery = BusinessProduct::with(['businessCategory', 'businessSubCategory', 'businessType']);
        
        if (!empty($query)) {
            $dbQuery->where('name', 'LIKE', "%{$query}%")
                ->orWhereHas('businessCategory', function($q) use ($query) {
                    $q->where('name', 'LIKE', "%{$query}%");
                })
                ->orWhereHas('businessSubCategory', function($q) use ($query) {
                    $q->where('name', 'LIKE', "%{$query}%");
                })
                ->orWhereHas('businessType', function($q) use ($query) {
                    $q->where('name', 'LIKE', "%{$query}%");
                });
        }
        
        $products = $dbQuery->get();

        $headers = array(
            "Content-type"        => "text/csv",
            "Content-Disposition" => "attachment; filename=$fileName",
            "Pragma"              => "no-cache",
            "Cache-Control"       => "must-revalidate, post-check=0, pre-check=0",
            "Expires"             => "0"
        );

        $columns = array('ID', 'Product Details', 'Category', 'Sub Category', 'Business Type', 'Status');

        $callback = function() use($products, $columns) {
            $file = fopen('php://output', 'w');
            fputcsv($file, $columns);

            foreach ($products as $task) {
                $row['ID']  = $task->id;
                $row['Product Details']    = $task->name;
                $row['Category'] = $task->businessCategory ? $task->businessCategory->name : '';
                $row['Sub Category']  = $task->businessSubCategory ? $task->businessSubCategory->name : '';
                $row['Business Type']  = $task->businessType ? $task->businessType->name : '';
                $row['Status']  = $task->status;

                fputcsv($file, array($row['ID'], $row['Product Details'], $row['Category'], $row['Sub Category'], $row['Business Type'], $row['Status']));
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
            if (count($row) >= 6) {
                $id = trim($row[0]);
                $name = trim($row[1]);
                $status = trim($row[5]) === '1' ? 1 : 0;
                
                if (empty($name)) continue;

                if (!empty($id)) {
                    $product = BusinessProduct::find($id);
                    if ($product) {
                        $product->update(['name' => $name, 'status' => $status]);
                    }
                } else {
                    // Similar to business type, creating new ones via CSV without category/sub category mapping is complex.
                    // We only allow updating existing ones for now via import, or creating unmapped ones if DB allows.
                }
            }
        }
        return redirect()->back()->with('success', 'Business Products imported successfully. (Note: Only existing records can be updated via import as mappings are required for new products).');
    }
}
