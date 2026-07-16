<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use App\Models\CouponCode;
use App\Models\FestivalsPost;
use App\Models\CategoryPost;
use App\Models\CustomPostFrame;
use Illuminate\Support\Facades\Validator;
use Illuminate\Support\Str;

class CouponCodeController extends Controller
{
    public function __construct()
    {
        $this->middleware('permission:CouponCode');
    }

    public function index()
    {
        $index['data'] = CouponCode::get();
        return view("coupon_code.index", $index);
    }

    public function create()
    {
        $partners = \App\Models\User::where('is_partner', 1)->pluck('name', 'id')->toArray();
        $subscriptions = \App\Models\Subscription::pluck('plan_name', 'id')->toArray();
        return view("coupon_code.create", compact('partners', 'subscriptions'));
    }

    public function store(Request $request)
    {
        $validation = Validator::make($request->all(), [
            'code' => 'required',
            'discount' => 'required',
            'limit' => 'required',
        ]);

        if ($validation->fails()) {
            return back()->withErrors($validation)->withInput();
        } else {
            CouponCode::create([
                "code" => $request->get("code"),
                "discount" => $request->get("discount"),
                "limit" => $request->get("limit"),
                "partner_id" => $request->get("partner_id"),
                "subscription_id" => $request->get("subscription_id"),
                "is_first_time_only" => $request->has("is_first_time_only") ? 1 : 0,
            ]);

            return redirect()->route("coupon-code.index");
        }
    }

    public function coupon_code_status(Request $request)
    {
        $couponCode = CouponCode::find($request->get("id"));
        $couponCode->status = ($request->get("checked")=="true")?1:0;
        $couponCode->save();
    }

    public function edit($id)
    {
        $couponCode = CouponCode::find($id);
        $partners = \App\Models\User::where('is_partner', 1)->pluck('name', 'id')->toArray();
        $subscriptions = \App\Models\Subscription::pluck('plan_name', 'id')->toArray();
        return view("coupon_code.edit", compact("couponCode", "partners", "subscriptions"));
    }

    public function update(Request $request, $id)
    {
        $validation = Validator::make($request->all(), [
            'code' => 'required',
            'discount' => 'required',
            'limit' => 'required',
        ]);

        if ($validation->fails()) {
            return back()->withErrors($validation)->withInput();
        } else {
            $couponCode = CouponCode::find($request->get("id"));
            $couponCode->code = $request->get("code");
            $couponCode->discount = $request->get("discount");
            $couponCode->limit = $request->get("limit");
            $couponCode->partner_id = $request->get("partner_id");
            $couponCode->subscription_id = $request->get("subscription_id");
            $couponCode->is_first_time_only = $request->has("is_first_time_only") ? 1 : 0;
            $couponCode->save();

            return redirect()->route('coupon-code.index');
        }
    }

    public function destroy($id)
    {
        CouponCode::find($id)->delete();
        return redirect()->route('coupon-code.index');
    }
}
