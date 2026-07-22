<?php

namespace App\Http\Controllers\Admin;

use Auth;
use App\Models\User;
use App\Models\Business;
use Illuminate\Support\Str;
use Illuminate\Http\Request;
use App\Models\StorageSetting;
use App\Models\BusinessCategory;
use App\Http\Controllers\Controller;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Facades\Validator;

class BusinessController extends Controller
{
    public function __construct()
    {
        $this->middleware('permission:Businesses');
    }

    public function index(Request $request)
    {
        // NEW tag logic: grab last seen max ID before updating
        $lastSeenBusinessId = session('admin_last_seen_business_id', 0);
        $currentMaxBusinessId = Business::max('id') ?? 0;

        if($request->search)
        {
            $user = User::where('name','like', '%'.$request->search.'%')->get()->pluck('id')->toArray();
            
            $index['data'] = Business::where('name','like', '%'.$request->search.'%')
            ->orWhere('mobile_no','like', '%'.$request->search.'%')
            ->orWhereIn('user_id',$user)
            ->select('id','name','user_id','mobile_no','logo','status')->orderBy('id', 'desc')->paginate(10);
        }
        else
        {
            $index['data'] = Business::select('id','name','user_id','mobile_no','logo','status')->orderBy('id', 'desc')->paginate(10);
        }

        $index['last_seen_business_id'] = $lastSeenBusinessId;

        // Update session with current max ID so next visit won't show NEW for these
        session(['admin_last_seen_business_id' => $currentMaxBusinessId]);

        return view("business.index", $index);
    }

    // public function create()
    // {
    //     return view("business.create");
    // }

    public function user_business($id)
    {
        $index['data'] = $id;
        $index['category'] =  BusinessCategory::where('status',1)->get();
        return view("business.create",$index);
    }

    public function show($id)
    {
        $index['data'] = Business::find($id);
        return view('business.show',$index);
    }

    public function business_status(Request $request)
    {
        $business = Business::find($request->get("id"));
        $business->status = ($request->get("checked")=="true")?1:0;
        $business->save();
    }

    public function store(Request $request)
    {
        $validation = Validator::make($request->all(), [
            'name' => 'required',
            'address' => 'required',
            "mobile_no" => 'required|numeric',
            'email' => 'required|email|unique:business,email,' . \Request::get("id"),
            "logo" => "nullable|mimes:jpg,png,jpeg",
            "website" => 'required',
            "business_category_id" => 'required',
            "business_sub_category_ids" => 'nullable|array',
        ]);

        if ($validation->fails()) {
            return back()->withErrors($validation)->withInput();
        } 
        else 
        {
            $business_data = Business::where('user_id',$request->get("user_id"))->where('is_default',1)->get();
            if(!$business_data->isEmpty())
            {
                foreach ($business_data as $value){
                    $b = Business::find($value->id);
                    $b->is_default = 0;
                    $b->save();
                }
            }

            $id = Business::create([
                "name" => $request->get("name"),
                "email" => $request->get("email"),
                "mobile_no" => $request->get("mobile_no"),
                "address" => $request->get("address"),
                "website" => $request->get("website"),
                "user_id" => $request->get("user_id"),
                "business_category_id" => $request->get("business_category_id"),
                "business_sub_category_ids" => $request->get("business_sub_category_ids") ? json_encode($request->get("business_sub_category_ids")) : null,
                "is_default" => 1,
                "extra_emails" => $request->has('extra_emails') ? array_values(array_filter($request->get('extra_emails'))) : null,
                "extra_mobile_numbers" => $request->has('extra_mobile_numbers') ? array_values(array_filter($request->get('extra_mobile_numbers'))) : null,
                "extra_websites" => $request->has('extra_websites') ? array_values(array_filter($request->get('extra_websites'))) : null,
                "extra_addresses" => $request->has('extra_addresses') ? array_values(array_filter($request->get('extra_addresses'))) : null,
            ])->id;

            if(StorageSetting::getStorageSetting("storage") == "DigitalOcean")
            {
                if ($request->file("logo") && $request->file('logo')->isValid()) {
                    $image = $request->file('logo');
                    $file = Str::uuid().'.'.$image->getClientOriginalExtension();
            
                    $path = Storage::disk('spaces')->put('uploads/'.$file, file_get_contents($image),'public');
                    
                    $b = Business::find($id);
                    $b->logo = $file;
                    $b->save();
                }
            }
            else
            {
                if ($request->file("logo") && $request->file('logo')->isValid()) {
                    $this->upload_image($request->file("logo"),"logo", $id);
                }
            }
           
            if($request->get("user_id") == Auth::user()->id)
            {
                return redirect()->route("business.index");
            }
            else
            {
                return redirect('admin/user/'.$request->get("user_id"));
            }
        }
    }

    public function edit($id)
    {
        $business = Business::find($id);
        $category =  BusinessCategory::where('status',1)->get();
        $user_data = User::find($business->user_id);
        $subscription = \App\Models\Subscription::where('status',1)->get();
        $transaction = \App\Models\Transaction::where('user_id',$business->user_id)->get();
        return view("business.edit", compact("business","category","user_data","subscription","transaction"));
    }

    public function update(Request $request, $id)
    {
        $validation = Validator::make($request->all(), [
            'name' => 'required',
            "mobile_no" => 'required|numeric',
            'email' => 'required|email|unique:business,email,' . \Request::get("id"),
            "logo" => "nullable|mimes:jpg,png,jpeg",
            "website" => 'required',
            'address' => 'required',
            "business_category_id" => 'required',
            "business_sub_category_ids" => 'nullable|array',
        ]);

        if ($validation->fails()) {
            return back()->withErrors($validation)->withInput();
        } else {
            $business = Business::whereId($request->get("id"))->first();
            $business->name = $request->get("name");
            $business->email = $request->get("email");
            $business->mobile_no = $request->get("mobile_no");
            $business->website = $request->get("website");
            $business->address = $request->get("address");
            $business->business_category_id = $request->business_category_id;
            $business->business_sub_category_ids = $request->business_sub_category_ids ? json_encode($request->business_sub_category_ids) : null;
            $business->extra_emails = $request->has('extra_emails') ? array_values(array_filter($request->get('extra_emails'))) : null;
            $business->extra_mobile_numbers = $request->has('extra_mobile_numbers') ? array_values(array_filter($request->get('extra_mobile_numbers'))) : null;
            $business->extra_websites = $request->has('extra_websites') ? array_values(array_filter($request->get('extra_websites'))) : null;
            $business->extra_addresses = $request->has('extra_addresses') ? array_values(array_filter($request->get('extra_addresses'))) : null;
            $business->save();

            if(StorageSetting::getStorageSetting("storage") == "DigitalOcean")
            {
                if ($request->file("logo") && $request->file('logo')->isValid()) {
                    $image = $request->file('logo');
                    $file = Str::uuid().'.'.$image->getClientOriginalExtension();
            
                    $path = Storage::disk('spaces')->put('uploads/'.$file, file_get_contents($image),'public');
                    
                    $b = Business::find($request->get("id"));
                    $b->logo = $file;
                    $b->save();
                }
            }
            else
            {
                if($request->file("logo") && $request->file('logo')->isValid()) {
                    $this->upload_image($request->file("logo"),"logo", $id);
                }
            }

            return redirect()->route('business.index');
        }
    }

    public function destroy($id)
    {
        $business = Business::find($id);
        if ($business->logo) {
            if(StorageSetting::getStorageSetting("storage") == "DigitalOcean")
            {
                Storage::disk('spaces')->delete('uploads/'.$business->logo);
            }
            else
            {
                $filePath = public_path('uploads/').$business->logo;
                if (file_exists($filePath)) {
                    unlink($filePath);
                }
            }
        }

        Business::find($id)->delete();
        return redirect()->route('business.index');
    }

    private function upload_image($file,$field,$id)
    {
        $destinationPath = public_path('uploads');
        $extension = $file->getClientOriginalExtension();
        $fileName = Str::uuid() . '.' . $extension;
        $file->move($destinationPath, $fileName);
        
        $image = Business::find($id);
        $image->$field = $fileName;
        $image->save();
    }
}
