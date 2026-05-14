<?php

namespace App\Http\Controllers\Admin;

use Illuminate\Support\Str;
use App\Models\BusinessCard;
use Illuminate\Http\Request;
use App\Models\StorageSetting;
use App\Http\Controllers\Controller;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Facades\Validator;

class BusinessCardController extends Controller
{
    public function __construct()
    {
        $this->middleware('permission:BusinessCard');
    }

    public function index()
    {
        $index['data'] = BusinessCard::get();
        return view("business_card.index", $index);
    }

    public function create()
    {
        return view("business_card.create");
    }

    public function store(Request $request)
    {
        $validation = Validator::make($request->all(), [
            'name' => 'required',
        ]);

        if ($validation->fails()) {
            return back()->withErrors($validation)->withInput();
        } else {
            $id = BusinessCard::create([
                "name" => $request->get("name"),
            ])->id;

            if(StorageSetting::getStorageSetting("storage") == "DigitalOcean")
            {
                if ($request->file("image") && $request->file('image')->isValid()) {
                    $image = $request->file('image');
                    $file = Str::uuid().'.'.$image->getClientOriginalExtension();
            
                    $path = Storage::disk('spaces')->put('uploads/'.$file, file_get_contents($image),'public');
                    
                    $b = BusinessCard::find($id);
                    $b->image = $file;
                    $b->save();
                }
            }
            else
            {
                if ($request->file("image") && $request->file('image')->isValid()) {
                    $this->upload_image($request->file("image"),"image", $id);
                }
            }

            return redirect()->route("business-card.index");
        }
    }

    public function business_card_status(Request $request)
    {
        $business_card = BusinessCard::find($request->get("id"));
        $business_card->status = ($request->get("checked")=="true")?1:0;
        $business_card->save();
    }

    public function edit($id)
    {
        $business_card = BusinessCard::find($id);
        return view("business_card.edit", compact("business_card"));
    }

    public function update(Request $request, $id)
    {
        $validation = Validator::make($request->all(), [
            'name' => 'required',
        ]);

        if ($validation->fails()) {
            return back()->withErrors($validation)->withInput();
        } else {
            $business_card = BusinessCard::find($request->get("id"));
            $business_card->name = $request->get("name");
            $business_card->save();

            if(StorageSetting::getStorageSetting("storage") == "DigitalOcean")
            {
                if ($request->file("image") && $request->file('image')->isValid()) {
                    $image = $request->file('image');
                    $file = Str::uuid().'.'.$image->getClientOriginalExtension();
            
                    $path = Storage::disk('spaces')->put('uploads/'.$file, file_get_contents($image),'public');
                    
                    $b = BusinessCard::find($request->get('id'));
                    $b->image = $file;
                    $b->save();
                }
            }
            else
            {
                if ($request->file("image") && $request->file('image')->isValid()) {
                    $this->upload_image($request->file("image"),"image", $id);
                }
            }

            return redirect()->route('business-card.index');
        }
    }

    public function destroy($id)
    {
        $businessCard = BusinessCard::find($id);
        if(StorageSetting::getStorageSetting("storage") == "DigitalOcean")
        {
            Storage::disk('spaces')->delete('uploads/'.$businessCard->image);
        }
        else
        {
            unlink('./uploads/'.$businessCard->image);
        }

        BusinessCard::find($id)->delete();

        return redirect()->route('business-card.index');
    }

    private function upload_image($file,$field,$id)
    {
        $destinationPath = './uploads';
        $extension = $file->getClientOriginalExtension();
        $fileName = Str::uuid() . '.' . $extension;
        $file->move($destinationPath, $fileName);
        
        $image = BusinessCard::find($id);
        $image->$field = $fileName;
        $image->save();
    }
}
