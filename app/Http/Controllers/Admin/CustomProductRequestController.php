<?php

namespace App\Http\Controllers\Admin;

use Illuminate\Http\Request;
use App\Models\BusinessProductRequest;
use App\Models\BusinessProductMapping;
use App\Models\BusinessProduct;
use App\Http\Controllers\Controller;

class CustomProductRequestController extends Controller
{
    public function __construct()
    {
        $this->middleware('permission:BusinessCategory');
    }

    public function index()
    {
        $requests = BusinessProductRequest::with(['business', 'subCategory', 'resolvedProduct'])
            ->orderBy('created_at', 'desc')
            ->get();
            
        $products = BusinessProduct::where('status', 1)->get();
        return view("custom_product_request.index", compact("requests", "products"));
    }

    public function resolve(Request $request, $id)
    {
        $productRequest = BusinessProductRequest::findOrFail($id);
        
        $action = $request->input('action'); // 'approve' or 'reject'
        
        if ($action == 'approve') {
            $resolved_product_id = $request->input('resolved_product_id');
            if(!$resolved_product_id) {
                return back()->withErrors(['message' => 'Please select a master product to resolve this request.']);
            }
            
            $productRequest->status = 'approved';
            $productRequest->resolved_product_id = $resolved_product_id;
            $productRequest->save();
            
            // Create the mapping for the business
            BusinessProductMapping::firstOrCreate([
                'business_id' => $productRequest->business_id,
                'business_product_id' => $resolved_product_id
            ]);
            
        } elseif ($action == 'reject') {
            $productRequest->status = 'rejected';
            $productRequest->save();
        }
        
        return redirect()->route('custom-product-request.index')->with('success', 'Request resolved successfully.');
    }
}
