<?php

namespace App\Http\Controllers;

use App\Models\User;
use App\Models\Business;
use App\Models\BusinessCategory;
use App\Models\BusinessSubCategory;
use App\Models\BusinessType;
use App\Models\BusinessProduct;
use App\Models\BusinessProductMapping;
use App\Models\StorageSetting;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Facades\Validator;
use Illuminate\Support\Str;

class BusinessRegistrationController extends Controller
{
    public function showForm()
    {
        $categories = BusinessCategory::where('status', 1)->get();
        return view('landing.business_registration', compact('categories'));
    }

    public function getSubCategories(Request $request)
    {
        $categoryId = $request->get('category_id');
        $subCategories = BusinessSubCategory::where('business_category_id', $categoryId)->where('status', 1)->get();
        return response()->json(['data' => $subCategories]);
    }

    public function getBusinessTypes(Request $request)
    {
        $subCategoryIds = $request->get('sub_category_ids');
        if (!$subCategoryIds) {
            return response()->json(['data' => []]);
        }
        $ids = explode(',', $subCategoryIds);
        $types = BusinessType::whereIn('business_sub_category_id', $ids)->where('status', 1)->get();
        return response()->json(['data' => $types]);
    }

    public function getProducts(Request $request)
    {
        $subCategoryIds = $request->get('sub_category_ids');
        $businessTypeIds = $request->get('business_type_ids');

        $query = BusinessProduct::where('status', 1);

        if ($businessTypeIds) {
            $ids = explode(',', $businessTypeIds);
            $query->whereIn('business_type_id', $ids);
        } elseif ($subCategoryIds) {
            $ids = explode(',', $subCategoryIds);
            $query->whereIn('business_sub_category_id', $ids);
        } else {
            return response()->json(['data' => []]);
        }

        $products = $query->get();
        return response()->json(['data' => $products]);
    }

    public function register(Request $request)
    {
        $validation = Validator::make($request->all(), [
            'name' => 'required',
            'email' => 'required|email|unique:users,email',
            'mobile_no' => 'required|numeric|unique:users,mobile_no',
            'password' => 'required|min:6',
            'bussinessName' => 'required',
            'businessCategoryId' => 'required',
            'image' => 'nullable|image|mimes:jpeg,png,jpg,webp|max:2048'
        ]);

        if ($validation->fails()) {
            return back()->withErrors($validation)->withInput();
        }

        // Create User
        $id = User::create([
            'name' => $request->get('name'),
            'email' => $request->get('email'),
            'password' => bcrypt($request->get('password')),
            'country' => '91',
            'mobile_no' => $request->get('mobile_no'),
            'api_token' => str::random(60),
            'login_type' => 'normal',
            'referral_code' => strtoupper(str::random(10)),
            'user_type' => 'User',
        ])->id;

        // Image Upload
        if ($request->hasFile('image') && $request->file('image')->isValid()) {
            $image = $request->file('image');
            $file = Str::uuid() . '.' . $image->getClientOriginalExtension();
            
            if (StorageSetting::getStorageSetting('storage') == 'DigitalOcean') {
                Storage::disk('spaces')->put('uploads/' . $file, file_get_contents($image), 'public');
            } else {
                $image->move(public_path('uploads'), $file);
                @copy(public_path('uploads/' . $file), base_path('uploads/' . $file));
            }

            $user = User::find($id);
            $user->image = $file;
            $user->save();
        }

        // Create Business
        $bId = Business::create([
            "name" => $request->get("bussinessName"),
            "email" => $request->get("bussinessEmail") ?? $request->get("email"),
            "mobile_no" => $request->get("bussinessNumber") ?? $request->get("mobile_no"),
            "address" => $request->get("bussinessAddress") ?? '',
            "website" => $request->get("bussinessWebsite") ?? '',
            "user_id" => $id,
            "business_category_id" => $request->get("businessCategoryId") ?? 1,
            "is_default" => 1,
            "logo" => isset($file) ? $file : null
        ])->id;

        $businessUpdate = Business::find($bId);

        if ($request->get('businessSubCategoryIds')) {
            $subCatIds = $request->get('businessSubCategoryIds');
            if (is_string($subCatIds)) {
                $subCatIds = json_decode($subCatIds, true) ?? explode(',', $subCatIds);
            }
            if (is_array($subCatIds)) {
                $businessUpdate->sub_categories()->sync(array_filter($subCatIds));
                $businessUpdate->business_sub_category_ids = array_filter($subCatIds);
            }
        }

        if ($request->get('businessTypeIds')) {
            $typeIds = $request->get('businessTypeIds');
            if (is_string($typeIds)) {
                $typeIds = json_decode($typeIds, true) ?? explode(',', $typeIds);
            }
            if (is_array($typeIds)) {
                $businessUpdate->types()->sync(array_filter($typeIds));
            }
        }
        $businessUpdate->save();

        $productIds = $request->get('product_ids');
        if ($productIds) {
            if (is_string($productIds)) {
                $productIds = json_decode($productIds, true) ?? explode(',', $productIds);
            }
            if (is_array($productIds)) {
                foreach ($productIds as $pId) {
                    if ($pId) {
                        BusinessProductMapping::create(['business_id' => $bId, 'business_product_id' => $pId]);
                    }
                }
            }
        }

        return redirect()->route('business.registration.success');
    }

    public function success()
    {
        return view('landing.business_registration_success');
    }
}
