<?php

namespace App\Http\Controllers\Admin;

use App\Models\Offer;
use Illuminate\Support\Str;
use App\Models\Subscription;
use Illuminate\Http\Request;
use App\Models\StorageSetting;
use App\Http\Controllers\Controller;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Facades\Validator;

class OfferController extends Controller
{
    public function __construct()
    {
        $this->middleware('permission:Offer');
    }

    public function index()
    {
        $index['data'] = Offer::get();
        return view("offer.index", $index);
    }

    public function create()
    {
        $index['subscription'] = Subscription::where('status',1)->get();
        return view("offer.create",$index);
    }

    public function store(Request $request)
    {
        $validation = Validator::make($request->all(), [
            'name' => 'required',
            "image" => "nullable|mimes:jpg,png,jpeg",
            "banner" => "nullable|mimes:jpg,png,jpeg",
            'subscription_id' => 'required',
        ]);

        if ($validation->fails()) {
            return back()->withErrors($validation)->withInput();
        } else {
            $id = Offer::create([
                "name" => $request->get("name"),
                "subscription_id" => $request->get("subscription_id"),
            ])->id;

            if(StorageSetting::getStorageSetting("storage") == "DigitalOcean")
            {
                if ($request->file("image") && $request->file('image')->isValid()) {
                    $image = $request->file('image');
                    $file = Str::uuid().'.'.$image->getClientOriginalExtension();
            
                    $path = Storage::disk('spaces')->put('uploads/'.$file, file_get_contents($image),'public');
                    
                    $offer = Offer::find($id);
                    $offer->image = $file;
                    $offer->save();
                }

                if ($request->file("banner") && $request->file('banner')->isValid()) {
                    $image = $request->file('banner');
                    $file = Str::uuid().'.'.$image->getClientOriginalExtension();
            
                    $path = Storage::disk('spaces')->put('uploads/'.$file, file_get_contents($image),'public');
                    
                    $offer = Offer::find($id);
                    $offer->banner = $file;
                    $offer->save();
                }
            }
            else
            {
                if ($request->file("image") && $request->file('image')->isValid()) {
                    $this->upload_image($request->file("image"),"image", $id);
                }

                if ($request->file("banner") && $request->file('banner')->isValid()) {
                    $this->upload_image($request->file("banner"),"banner", $id);
                }
            }

            return redirect()->route("offer.index");
        }
    }

    public function offer_status(Request $request)
    {
        $offer_data = Offer::get();
        foreach($offer_data as $data)
        {
            $offer_status = Offer::find($data->id);
            $offer_status->status = 0;
            $offer_status->save();
        }
        $offer = Offer::find($request->get("id"));
        $offer->status = ($request->get("checked")=="true")?1:0;
        $offer->save();
    }

    public function edit($id)
    {
        $offer = Offer::find($id);
        $subscription = Subscription::where('status',1)->get();
        return view("offer.edit", compact("offer","subscription"));
    }

    public function update(Request $request, $id)
    {
        $validation = Validator::make($request->all(), [
            'name' => 'required',
            "image" => "nullable|mimes:jpg,png,jpeg",
            "banner" => "nullable|mimes:jpg,png,jpeg",
            'subscription_id' => 'required',
        ]);

        if ($validation->fails()) {
            return back()->withErrors($validation)->withInput();
        } else {
            $offer = Offer::find($request->get("id"));
            $offer->name = $request->get("name");
            $offer->subscription_id = $request->get("subscription_id");
            $offer->save();

            if(StorageSetting::getStorageSetting("storage") == "DigitalOcean")
            {
                if ($request->file("image") && $request->file('image')->isValid()) {
                    $image = $request->file('image');
                    $file = Str::uuid().'.'.$image->getClientOriginalExtension();
            
                    $path = Storage::disk('spaces')->put('uploads/'.$file, file_get_contents($image),'public');
                    
                    $offer = Offer::find($request->get("id"));
                    $offer->image = $file;
                    $offer->save();
                }

                if ($request->file("banner") && $request->file('banner')->isValid()) {
                    $image = $request->file('banner');
                    $file = Str::uuid().'.'.$image->getClientOriginalExtension();
            
                    $path = Storage::disk('spaces')->put('uploads/'.$file, file_get_contents($image),'public');
                    
                    $offer = Offer::find($request->get('id'));
                    $offer->banner = $file;
                    $offer->save();
                }
            }
            else
            {
                if ($request->file("image") && $request->file('image')->isValid()) {
                    $this->upload_image($request->file("image"),"image", $id);
                }

                if ($request->file("banner") && $request->file('banner')->isValid()) {
                    $this->upload_image($request->file("banner"),"banner", $id);
                }
            }

            return redirect()->route('offer.index');
        }
    }

    public function destroy($id)
    {
        $offer = Offer::find($id);
        if(StorageSetting::getStorageSetting("storage") == "DigitalOcean")
        {
            Storage::disk('spaces')->delete('uploads/'.$offer->image);
            Storage::disk('spaces')->delete('uploads/'.$offer->banner);
        }
        else
        {
            unlink('./uploads/'.$offer->image);
            unlink('./uploads/'.$offer->banner);
        }

        Offer::find($id)->delete();
    
        return redirect()->route('offer.index');
    }

    private function upload_image($file,$field,$id)
    {
        $destinationPath = './uploads';
        $extension = $file->getClientOriginalExtension();
        $fileName = Str::uuid() . '.' . $extension;
        $file->move($destinationPath, $fileName);
        
        $image = Offer::find($id);
        $image->$field = $fileName;
        $image->save();
    }
}
