<?php

namespace App\Http\Controllers\Admin;

use Auth;
use App\Models\User;
use Illuminate\Support\Str;
use Illuminate\Http\Request;
use App\Models\StorageSetting;
use App\Models\WhatsappMessage;
use App\Models\WhatsAppSetting;
use App\Http\Controllers\Controller;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Facades\Validator;

class WhatsappMessageController extends Controller
{
    public function __construct()
    {
        $this->middleware('permission:WhatsAppMessage');
    }

    public function index()
    {
        $index['data'] = WhatsappMessage::get();
        return view("whatsapp_message.index", $index);
    }

    public function create()
    {
        return view("whatsapp_message.create");
    }

    public function store(Request $request)
    {
        $validation = Validator::make($request->all(), [
            'message' => 'required',
            'type' => 'required',
            "image" => "nullable|mimes:jpg,png,jpeg",
        ]);

        if ($validation->fails()) {
            return back()->withErrors($validation)->withInput();
        } else {

            $id = WhatsappMessage::create([
                "message" => $request->get("message"),
                "type" => $request->get("type"),
                "btn1" => $request->get("btn1"),
                "btn1_type" => $request->get("btn1type"),
                "btn1_value" => $request->get("btn1value"),
                "btn2" => $request->get("btn2"),
                "btn2_type" => $request->get("btn2type"),
                "btn2_value" => $request->get("btn2value"),
                "footer" => $request->get("footer"),
            ])->id;
           
            if(StorageSetting::getStorageSetting("storage") == "DigitalOcean")
            {
                if ($request->file("image") && $request->file('image')->isValid()) {
                    $image = $request->file('image');
                    $file = Str::uuid().'.'.$image->getClientOriginalExtension();
            
                    $path = Storage::disk('spaces')->put('uploads/'.$file, file_get_contents($image),'public');
                    
                    $message = WhatsappMessage::find($id);
                    $message->image = $file;
                    $message->save();
                }
            }
            else
            {
                if ($request->file("image") && $request->file('image')->isValid()) {
                    $this->upload_image($request->file("image"),"image", $id);
                }
            }

            return redirect()->route("whatsapp-message.index");
        }
    }

    public function edit($id)
    {
        $index['message'] = WhatsappMessage::find($id);
        return view("whatsapp_message.edit", $index);
    }

    public function update(Request $request, $id)
    {
        $validation = Validator::make($request->all(), [
            'message' => 'required',
            'type' => 'required',
            "image" => "nullable|mimes:jpg,png,jpeg",
        ]);

        if ($validation->fails()) {
            return back()->withErrors($validation)->withInput();
        } 
        else 
        {
            $msg = WhatsappMessage::find($request->get("id"));
            $msg->message = $request->get("message");
            $msg->type = $request->get("type");
            $msg->btn1 = $request->get("btn1");
            $msg->btn1_type = $request->get("btn1type");
            $msg->btn1_value = $request->get("btn1value");
            $msg->btn2 = $request->get("btn2");
            $msg->btn2_type = $request->get("btn2type");
            $msg->btn2_value = $request->get("btn2value");
            $msg->footer = $request->get("footer");
            $msg->save();

            if(StorageSetting::getStorageSetting("storage") == "DigitalOcean")
            {
                if ($request->file("image") && $request->file('image')->isValid()) {
                    $image = $request->file('image');
                    $file = Str::uuid().'.'.$image->getClientOriginalExtension();
            
                    $path = Storage::disk('spaces')->put('uploads/'.$file, file_get_contents($image),'public');
                    
                    $message = WhatsappMessage::find($id);
                    $message->image = $file;
                    $message->save();
                }
            }
            else
            {
                if ($request->file("image") && $request->file('image')->isValid()) {
                    $this->upload_image($request->file("image"),"image", $id);
                }
            }

            return redirect()->route("whatsapp-message.index");
        }
    }

    public function send_whatsapp_msg_user(Request $request)
    {
        $whatsappMsg = WhatsappMessage::find($request->msg_id);
        $user = User::find($request->user_id);
        
        $data['apikey'] = WhatsAppSetting::getWhatsAppSetting('api_key');
        $data['instance'] = WhatsAppSetting::getWhatsAppSetting('instance_id');
        $data['msg'] = $whatsappMsg->message;
        $url = "https://app.wapify.net/api/text-message.php";
        
        $data['number'] = $user->mobile_no;

        if($whatsappMsg->type == "media"){
            $url = "https://app.wapify.net/api/media-message.php";
            if(StorageSetting::getStorageSetting('storage') == 'DigitalOcean')
            {
                $data['media'] = Storage::disk('spaces')->url('uploads/'.$whatsappMsg->image);
            }
            else
            {
                $data['media'] = asset('uploads/'.$whatsappMsg->image);
            }
        }
        
        if($whatsappMsg->type == "button"){
            $url = "https://app.wapify.net/api/button-message.php";
            $data['btn1'] = $whatsappMsg->btn1;
            $data['btn1value'] = $whatsappMsg->btn1_value;
            $data['btn1type'] = $whatsappMsg->btn1_type;
            
            if($whatsappMsg->btn2 != "" && $whatsappMsg->btn2_value != "" && $whatsappMsg->btn2_type != ""){
                $data['btn2'] = $whatsappMsg->btn2;
                $data['btn2value'] = $whatsappMsg->btn2_value;
                $data['btn2type'] = $whatsappMsg->btn2_type;
            }
            
            $data['footer'] = $whatsappMsg->footer;
            
            if($whatsappMsg->image != ""){
                if(StorageSetting::getStorageSetting('storage') == 'DigitalOcean')
                {
                    $data['media'] = Storage::disk('spaces')->url('uploads/'.$whatsappMsg->image);
                }
                else
                {
                    $data['media'] = asset('uploads/'.$whatsappMsg->image);
                }
            }
        }

        $ch = curl_init();
        curl_setopt($ch, CURLOPT_HTTPHEADER, ['Content-Type: application/x-www-form-urlencoded']);
        curl_setopt($ch, CURLOPT_CUSTOMREQUEST, 'POST');
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($ch, CURLOPT_POSTFIELDS, http_build_query($data));
        curl_setopt($ch, CURLOPT_URL, $url);
        curl_setopt($ch, CURLOPT_SSL_VERIFYHOST, 0);
        curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, 0);
        $result = curl_exec($ch);
        curl_close($ch);
        
        return $result;
    }

    public function send_whatsapp_msg(Request $request)
    {
        $whatsappMsg = WhatsappMessage::find($request->id);
        
        $data['apikey'] = WhatsAppSetting::getWhatsAppSetting('api_key');
        $data['instance'] = WhatsAppSetting::getWhatsAppSetting('instance_id');
        $data['msg'] = $whatsappMsg->message;
        $url = "https://app.wapify.net/api/text-message.php";
        
        $user = array();
        if($request->user_type == "random")
        {
            $user = User::where('mobile_no','!=','')->where('user_type','User')->inRandomOrder()->take($request->quantity)->get();
        }
        if($request->user_type == "older")
        {
            $user = User::where('mobile_no','!=','')->where('user_type','User')->whereBetween('created_at',[date('Y-m-d H:i:s',strtotime('-60 days')),date('Y-m-d H:i:s',strtotime('today'))])->take($request->quantity)->get();
        }
        else
        {
            $user = User::where('mobile_no','!=','')->where('user_type','User')->latest()->take($request->quantity)->get();
        }

        $users_final = "";
        foreach ($user as $key => $user_val){
            if($users_final == "")
            {
                $users_final = "+91".$user_val->mobile_no;
            }
            else
            {
                $users_final = $users_final.","."+91".$user_val->mobile_no;
            }
        }

        $data['number'] = $users_final;
        if($whatsappMsg->type == "media"){
            $url = "https://app.wapify.net/api/media-message.php";
            if(StorageSetting::getStorageSetting('storage') == 'DigitalOcean')
            {
                $data['media'] = Storage::disk('spaces')->url('uploads/'.$whatsappMsg->image);
            }
            else
            {
                $data['media'] = asset('uploads/'.$whatsappMsg->image);
            }
        }
        
        if($whatsappMsg->type == "button"){
            $url = "https://app.wapify.net/api/button-message.php";
            $data['btn1'] = $whatsappMsg->btn1;
            $data['btn1value'] = $whatsappMsg->btn1_value;
            $data['btn1type'] = $whatsappMsg->btn1_type;
            
            if($whatsappMsg->btn2 != "" && $whatsappMsg->btn2_value != "" && $whatsappMsg->btn2_type != ""){
                $data['btn2'] = $whatsappMsg->btn2;
                $data['btn2value'] = $whatsappMsg->btn2_value;
                $data['btn2type'] = $whatsappMsg->btn2_type;
            }
            
            $data['footer'] = $whatsappMsg->footer;
            
            if($whatsappMsg->image != ""){
                if(StorageSetting::getStorageSetting('storage') == 'DigitalOcean')
                {
                    $data['media'] = Storage::disk('spaces')->url('uploads/'.$whatsappMsg->image);
                }
                else
                {
                    $data['media'] = asset('uploads/'.$whatsappMsg->image);
                }
            }
        }

        $ch = curl_init();
        curl_setopt($ch, CURLOPT_HTTPHEADER, ['Content-Type: application/x-www-form-urlencoded']);
        curl_setopt($ch, CURLOPT_CUSTOMREQUEST, 'POST');
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($ch, CURLOPT_POSTFIELDS, http_build_query($data));
        curl_setopt($ch, CURLOPT_URL, $url);
        curl_setopt($ch, CURLOPT_SSL_VERIFYHOST, 0);
        curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, 0);
        $result = curl_exec($ch);
        curl_close($ch);
        
        return $result;
    }

    public function destroy($id)
    {
        WhatsappMessage::find($id)->delete();
        return redirect()->route('whatsapp-message.index');
    }

    private function upload_image($file,$field,$id)
    {
        $destinationPath = './uploads';
        $extension = $file->getClientOriginalExtension();
        $fileName = Str::uuid() . '.' . $extension;
        $file->move($destinationPath, $fileName);
        
        $image = WhatsappMessage::find($id);
        $image->$field = $fileName;
        $image->save();
    }
}
