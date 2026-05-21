<?php

namespace App\Http\Controllers\Api;

use App\Models\User;
use App\Mail\EmailVerify;
use App\Jobs\SendVerificationEmailJob;
use App\Jobs\SendForgotPasswordEmailJob;
use Illuminate\Support\Str;
use App\Mail\ForgotPassword;
use App\Models\AndroidLogin;
use Illuminate\Http\Request;
use App\Models\EmailVerified;
use App\Models\PasswordReset;
use App\Models\EarningHistory;
use App\Models\ReferralSystem;
use App\Models\StorageSetting;
use App\Models\ReferralRegister;
use App\Http\Controllers\Controller;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Facades\Password;
use Illuminate\Support\Facades\Validator;
use Symfony\Component\HttpFoundation\Response;

class AuthApi extends Controller
{
    public function login(Request $request)
    {
        $email = $request->get("email");
        $password = $request->get("password");
        
        if (Auth::attempt(['email' => $email, 'password' => $password])) 
        {
            $user = User::find(Auth::user()->id);
            $ReferralRegister = ReferralRegister::where('user_id',Auth::user()->id)->first();
            
            $res = array(
                'userId' => $user->id,  
                'userName' => $user->name,
                'emailId' => $user->email, 
                'password' => "",
                'country' => $user->country,
                'phoneNumber' => $user->mobile_no,
                'useReferral' => ($ReferralRegister)?$ReferralRegister->referral_code:"",
                'planName' => $user->active_subscription ? $user->active_subscription->plan_name : "",
                'planDuration' => $user->active_subscription ? $user->active_subscription->duration." ".$user->active_subscription->duration_type : "",
                'planStartDate' => ($user->subscription_start_date)?$user->subscription_start_date:"",
                'planEndDate' => ($user->subscription_start_date)?$user->subscription_end_date:"",
                'isSubscribe' => ($user->is_subscribe)?(date_format(date_create(implode("", preg_split("/[-\s:,]/", $user->subscription_end_date))),"Y-m-d") >= date("Y-m-d",strtotime('today')))?true:false:false,
                'is_email_verify' => ($user->email_verified_at != null)?true:false,
                'userType' => $user->login_type,
                'isPartner' => ($user->is_partner == 1) ? true : false, 
                'businessLimit' => (date_format(date_create(implode("", preg_split("/[-\s:,]/", $user->subscription_end_date))),"Y-m-d") >= date("Y-m-d",strtotime('today')))?$user->business_limit:1,   
                'profileImage' => ($user->image)?(substr($user->image, 0, 4)=="http")?$user->image:((StorageSetting::getStorageSetting('storage') == 'DigitalOcean')?Storage::disk('spaces')->url('uploads/'.$user->image):asset('uploads/'.$user->image)):"",
                                'createdAt' => date('Y-m-d H:i:s', strtotime($user->created_at)),
                'adConfig' => $user->getAdConfigPayload()    
            );
        } 
        else 
        {
            return response()->json([
                'status' => "Error",
                'message' => "Invalid Login Credentials",
            ], 404);
        }

        return response()->json($res);
    }

    public function registration(Request $request)
    {
        if($request->get('referralCode'))
        {
            $referral_exist = User::where('referral_code', $request->get('referralCode'))->first();
            if($referral_exist == null)
            {
                return response()->json([
                    'status' => "Error",
                    'message' => "Invalid Referral Code",
                ], 404);
            }
        }

        $exist = User::where('email', $request->get('email'))->first();
        $validation = Validator::make($request->all(), [
            'name' => 'required',
            'password' => 'required|min:8',
            'email' => 'required|email|unique:users,email,' . \Request::get("id"),
            'country' => 'nullable|numeric',
            'mobile_no' => 'required|numeric|unique:users,mobile_no',
            'image' => "nullable|mimes:jpg,png,jpeg",
        ]);

        if ($validation->fails()) {
            $errors = [];
            foreach ($validation->errors()->messages() as $key => $value) {
                $errors[] = is_array($value) ? implode(',', $value) : $value;
            }

            return response()->json([
              'status' => "Error",
              'message' => $errors,
            ], 404);
        }
        else
        {
            $id = User::create([
                'name' => $request->get('name'),
                'email' => $request->get('email'), 
                'password' => bcrypt($request->get('password')), 
                'country' => $request->get('country'),
                'mobile_no' => $request->get('mobile_no'),
                'api_token' => str::random(60),
                'login_type' => "normal",
                "referral_code" => strtoupper(str::random(10)),
                "user_type" => "User",
            ])->id;

            if($request->get('referralCode')) {
                ReferralRegister::create([
                    "user_id" => $id,
                    "referral_code" => $request->get('referralCode')
                ]);

                $referral_user = User::where('referral_code',$request->get('referralCode'))->first();
                if($referral_user) {
                    $isFraud = \App\Services\FraudDetectionService::isFraudulentSignup($request, $request->get('email'));

                    if (!$isFraud) {
                        $referral_user->current_balance = $referral_user->current_balance + ReferralSystem::getReferralSystem('register_point');
                        $referral_user->total_balance = $referral_user->total_balance + ReferralSystem::getReferralSystem('register_point');
                        $referral_user->save();
                    }

                    EarningHistory::create([
                        "user_id" => $referral_user->id,
                        "amount" => ReferralSystem::getReferralSystem('register_point'),
                        "amount_type" => 1,
                        "refer_user" => $id,
                        "status" => $isFraud ? 'fraud' : 'pending' // Set status to fraud or pending
                    ]);

                    // B2C Package Reward Logic
                    $required_invites = \App\Models\ReferralSystem::getReferralSystem('referral_invite_count') ?? 5;
                    $reward_days = \App\Models\ReferralSystem::getReferralSystem('referral_reward_days') ?? 15;
                    $reward_package_id = \App\Models\ReferralSystem::getReferralSystem('referral_reward_package_id');

                    $total_invites = \App\Models\ReferralRegister::where('referral_code', $request->get('referralCode'))->count();

                    if ($total_invites > 0 && $total_invites % $required_invites == 0) {
                        $sub = \App\Models\Subscription::find($reward_package_id);
                        if($sub) {
                            $referral_user->is_subscribe = 1;
                            $referral_user->subscription_id = $sub->id;
                            $current_end = $referral_user->subscription_end_date && strtotime($referral_user->subscription_end_date) > time() ? $referral_user->subscription_end_date : date('Y-m-d');
                            $referral_user->subscription_end_date = date('Y-m-d', strtotime($current_end . " + {$reward_days} days"));
                            $referral_user->save();
                        }
                    }
                }
            }

            if(StorageSetting::getStorageSetting("storage") == "DigitalOcean")
            {
                if ($request->file("image") && $request->file('image')->isValid()) {
                    $image = $request->file('image');
                    $file = Str::uuid().'.'.$image->getClientOriginalExtension();
            
                    $path = Storage::disk('spaces')->put('uploads/'.$file, file_get_contents($image),'public');
                    
                    $user = User::find($id);
                    $user->image = $file;
                    $user->save();
                }
            }
            else
            {
                if ($request->file("image") && $request->file('image')->isValid()) {
                    $this->upload_image($request->file("image"),"image", $id);
                }
            }

            $user = User::find($id);
            $email = $user->email;
            $name = $user->name;
            //$code = Str::random(10);
            $code = mt_rand(100000, 999999);

            $token = Str::random(60);
            EmailVerified::where('user_id', $id)->delete();
            EmailVerified::create(['user_id' => $id, 'code' => $code, 'created_at' => date('Y-m-d H:i:s')]);
            SendVerificationEmailJob::dispatch($email, $token, $name, $code);
            $ReferralRegister = ReferralRegister::where('user_id',$id)->first();

            $data = array(
                'userId' => $user->id, 
                'userName' => $user->name,
                'emailId' => $user->email, 
                'password' => "",
                'country' => $user->country,
                'phoneNumber' => $user->mobile_no,
                'useReferral' => ($ReferralRegister)?$ReferralRegister->referral_code:"",
                'planName' => $user->active_subscription ? $user->active_subscription->plan_name : "",
                'planDuration' => $user->active_subscription ? $user->active_subscription->duration." ".$user->active_subscription->duration_type : "",
                'planStartDate' => ($user->subscription_start_date)?$user->subscription_start_date:"",
                'planEndDate' => ($user->subscription_start_date)?$user->subscription_end_date:"",
                'isSubscribe' => ($user->is_subscribe)?(date_format(date_create(implode("", preg_split("/[-\s:,]/", $user->subscription_end_date))),"Y-m-d") >= date("Y-m-d",strtotime('today')))?true:false:false,
                'userType' => $user->login_type,
                'isPartner' => ($user->is_partner == 1) ? true : false, 
                'businessLimit' => (date_format(date_create(implode("", preg_split("/[-\s:,]/", $user->subscription_end_date))),"Y-m-d") >= date("Y-m-d",strtotime('today')))?$user->business_limit:1,   
                'profileImage' => ($user->image)?(substr($user->image, 0, 4)=="http")?$user->image:((StorageSetting::getStorageSetting('storage') == 'DigitalOcean')?Storage::disk('spaces')->url('uploads/'.$user->image):asset('uploads/'.$user->image)):"",
                                'createdAt' => date('Y-m-d H:i:s', strtotime($user->created_at)),
                'adConfig' => $user->getAdConfigPayload()
            );
        }

        return $data;
    }

    public function resendVerifyCode(Request $request)
    {
        $user = User::find($request->userId);
        if(!empty($user))
        {
            $email = $user->email;
            $name = $user->name;
            // $code = Str::random(10);
            $code = mt_rand(100000, 999999);

            $token = Str::random(60);
            EmailVerified::where('user_id', $request->userId)->delete();
            EmailVerified::create(['user_id' => $request->userId, 'code' => $code, 'created_at' => date('Y-m-d H:i:s')]);
            SendVerificationEmailJob::dispatch($email, $token, $name, $code);

            return response()->json([
                'status' => "success",
                'message' => "Resend Email Verification Code Successfully!",
            ], 200);
        }
        else
        {
            return response()->json([
                'status' => "Error",
                'message' => "Invalid userId",
            ], 404);
        }
    }

    public function verifyAccount(Request $request)
    {
        $exist = EmailVerified::where('user_id', $request->get('userId'))->where('code',$request->get('code'))->first();
        if($exist != null)
        {
            $user = User::find($request->get('userId'));
            if(!empty($user))
            {
                $user->email_verified_at = date('Y-m-d H:i:s');
                $user->save();

                return response()->json([
                    'status' => "success",
                    'message' => "Email Verification Successfully!",
                ], 200);
            }
            else
            {
                return response()->json([
                    'status' => "Error",
                    'message' => "Invalid userId",
                ], 404);
            }
        }
        else
        {
            return response()->json([
                'status' => "Error",
                'message' => "Invalid userId && Code!",
            ], 404);
        }
    }

    public function google_registration(Request $request)
    {
        $exist = User::where('email', $request->get('email'))->first();
        if($exist != null)
        {
            $user = User::where('email', $request->get('email'))->first();
            $ReferralRegister = ReferralRegister::where('user_id',$user->id)->first();

            $data = array(
                'userId' => $user->id, 
                'userName' => $user->name,
                'emailId' => $user->email, 
                'password' => "",
                'country' => $user->country,
                'phoneNumber' => $user->mobile_no,
                'useReferral' => ($ReferralRegister)?$ReferralRegister->referral_code:"",
                'planName' => $user->active_subscription ? $user->active_subscription->plan_name : "",
                'planDuration' => $user->active_subscription ? $user->active_subscription->duration." ".$user->active_subscription->duration_type : "",
                'planStartDate' => ($user->subscription_start_date)?$user->subscription_start_date:"",
                'planEndDate' => ($user->subscription_start_date)?$user->subscription_end_date:"",
                'isSubscribe' => ($user->is_subscribe)?(date_format(date_create(implode("", preg_split("/[-\s:,]/", $user->subscription_end_date))),"Y-m-d") >= date("Y-m-d",strtotime('today')))?true:false:false,
                'userType' => $user->login_type,
                'isPartner' => ($user->is_partner == 1) ? true : false, 
                'businessLimit' => (date_format(date_create(implode("", preg_split("/[-\s:,]/", $user->subscription_end_date))),"Y-m-d") >= date("Y-m-d",strtotime('today')))?$user->business_limit:1,   
                'profileImage' => ($user->image)?(substr($user->image, 0, 4)=="http")?$user->image:((StorageSetting::getStorageSetting('storage') == 'DigitalOcean')?Storage::disk('spaces')->url('uploads/'.$user->image):asset('uploads/'.$user->image)):"",
                                'createdAt' => date('Y-m-d H:i:s', strtotime($user->created_at)),
                'adConfig' => $user->getAdConfigPayload()
            );
        }
        else
        {
            $user_data = User::where('email', "brandusergoogle@gmail.com")->where('name', "Brand_User_Google")->first();
            if($user_data != null)
            {
                $user = User::where('email', "brandusergoogle@gmail.com")->where('name', "Brand_User_Google")->first();
                $ReferralRegister = ReferralRegister::where('user_id',$user->id)->first();

                $data = array(
                    'userId' => $user->id, 
                    'userName' => $user->name,
                    'emailId' => $user->email, 
                    'password' => "",
                    'country' => ($user->country)?$user->country:"",
                    'phoneNumber' => ($user->mobile_no)?$user->mobile_no:"",
                    'useReferral' => ($ReferralRegister)?$ReferralRegister->referral_code:"",
                    'planName' => $user->active_subscription ? $user->active_subscription->plan_name : "",
                    'planDuration' => $user->active_subscription ? $user->active_subscription->duration." ".$user->active_subscription->duration_type : "",
                    'planStartDate' => ($user->subscription_start_date)?$user->subscription_start_date:"",
                    'planEndDate' => ($user->subscription_start_date)?$user->subscription_end_date:"",
                    'isSubscribe' => ($user->is_subscribe)?(date_format(date_create(implode("", preg_split("/[-\s:,]/", $user->subscription_end_date))),"Y-m-d") >= date("Y-m-d",strtotime('today')))?true:false:false,
                    'userType' => $user->login_type,
                'isPartner' => ($user->is_partner == 1) ? true : false, 
                    'businessLimit' => (date_format(date_create(implode("", preg_split("/[-\s:,]/", $user->subscription_end_date))),"Y-m-d") >= date("Y-m-d",strtotime('today')))?$user->business_limit:1,   
                    'profileImage' => ($user->image)?(substr($user->image, 0, 4)=="http")?$user->image:((StorageSetting::getStorageSetting('storage') == 'DigitalOcean')?Storage::disk('spaces')->url('uploads/'.$user->image):asset('uploads/'.$user->image)):"",
                                    'createdAt' => date('Y-m-d H:i:s', strtotime($user->created_at)),
                'adConfig' => $user->getAdConfigPayload()
                );
            }
            else
            {
                $id = User::create([
                    'name' => "Brand_User_Google",
                    'email' => "brandusergoogle@gmail.com",
                    'password' => null, 
                    'api_token' => str::random(60),
                    'login_type' => "google",
                    'image' => $request->get('image'),
                    'email_verified_at' => date('Y-m-d H:i:s'),
                    "referral_code" => strtoupper(str::random(10)),
                    "user_type" => "User",
                ])->id;

                $user = User::find($id);
                $ReferralRegister = ReferralRegister::where('user_id',$id)->first();

                $data = array(
                    'userId' => $user->id, 
                    'userName' => $user->name,
                    'emailId' => $user->email, 
                    'password' => "",
                    'country' => ($user->country)?$user->country:"",
                    'phoneNumber' => ($user->mobile_no)?$user->mobile_no:"",
                    'useReferral' => ($ReferralRegister)?$ReferralRegister->referral_code:"",
                    'planName' => $user->active_subscription ? $user->active_subscription->plan_name : "",
                    'planDuration' => $user->active_subscription ? $user->active_subscription->duration." ".$user->active_subscription->duration_type : "",
                    'planStartDate' => ($user->subscription_start_date)?$user->subscription_start_date:"",
                    'planEndDate' => ($user->subscription_start_date)?$user->subscription_end_date:"",
                    'isSubscribe' => ($user->is_subscribe)?(date_format(date_create(implode("", preg_split("/[-\s:,]/", $user->subscription_end_date))),"Y-m-d") >= date("Y-m-d",strtotime('today')))?true:false:false,
                    'userType' => $user->login_type,
                'isPartner' => ($user->is_partner == 1) ? true : false, 
                    'businessLimit' => (date_format(date_create(implode("", preg_split("/[-\s:,]/", $user->subscription_end_date))),"Y-m-d") >= date("Y-m-d",strtotime('today')))?$user->business_limit:1,   
                    'profileImage' => ($user->image)?(substr($user->image, 0, 4)=="http")?$user->image:((StorageSetting::getStorageSetting('storage') == 'DigitalOcean')?Storage::disk('spaces')->url('uploads/'.$user->image):asset('uploads/'.$user->image)):"",
                                    'createdAt' => date('Y-m-d H:i:s', strtotime($user->created_at)),
                'adConfig' => $user->getAdConfigPayload()
                );
            }
        }
        return $data;
    }

    public function phone_login(Request $request)
    {
        $exist = User::where('mobile_no', $request->get('phoneNumber'))->first();
        if($exist != null)
        {
            $user = User::where('mobile_no', $request->get('phoneNumber'))->first();
            $ReferralRegister = ReferralRegister::where('user_id',$user->id)->first();

            $data = array(
                'userId' => $user->id, 
                'userName' => $user->name,
                'emailId' => $user->email, 
                'password' => "",
                'country' => $user->country,
                'phoneNumber' => $user->mobile_no,
                'useReferral' => ($ReferralRegister)?$ReferralRegister->referral_code:"",
                'planName' => $user->active_subscription ? $user->active_subscription->plan_name : "",
                'planDuration' => $user->active_subscription ? $user->active_subscription->duration." ".$user->active_subscription->duration_type : "",
                'planStartDate' => ($user->subscription_start_date)?$user->subscription_start_date:"",
                'planEndDate' => ($user->subscription_start_date)?$user->subscription_end_date:"",
                'isSubscribe' => ($user->is_subscribe)?(date_format(date_create(implode("", preg_split("/[-\s:,]/", $user->subscription_end_date))),"Y-m-d") >= date("Y-m-d",strtotime('today')))?true:false:false,
                'userType' => $user->login_type,
                'isPartner' => ($user->is_partner == 1) ? true : false, 
                'businessLimit' => (date_format(date_create(implode("", preg_split("/[-\s:,]/", $user->subscription_end_date))),"Y-m-d") >= date("Y-m-d",strtotime('today')))?$user->business_limit:1,   
                'profileImage' => ($user->image)?(substr($user->image, 0, 4)=="http")?$user->image:((StorageSetting::getStorageSetting('storage') == 'DigitalOcean')?Storage::disk('spaces')->url('uploads/'.$user->image):asset('uploads/'.$user->image)):"",
                                'createdAt' => date('Y-m-d H:i:s', strtotime($user->created_at)),
                'adConfig' => $user->getAdConfigPayload()
            );
        }
        else
        {
            $user_data = User::where('email', $request->get('email'))->where('name', $request->get('name'))->first();
            if($user_data != null)
            {
                $user = User::find($user_data->id);
                $user->name = $request->get("name");
                $user->email = $request->get("email");
                $user->country = $request->get("country");
                $user->mobile_no = $request->get("phoneNumber");
                $user->save();
                $ReferralRegister = ReferralRegister::where('user_id',$user->id)->first();

                $data = array(
                    'userId' => $user->id, 
                    'userName' => $user->name,
                    'emailId' => $user->email, 
                    'password' => "",
                    'country' => $user->country,
                    'phoneNumber' => $user->mobile_no,
                    'useReferral' => ($ReferralRegister)?$ReferralRegister->referral_code:"",
                    'planName' => $user->active_subscription ? $user->active_subscription->plan_name : "",
                    'planDuration' => $user->active_subscription ? $user->active_subscription->duration." ".$user->active_subscription->duration_type : "",
                    'planStartDate' => ($user->subscription_start_date)?$user->subscription_start_date:"",
                    'planEndDate' => ($user->subscription_start_date)?$user->subscription_end_date:"",
                    'isSubscribe' => ($user->is_subscribe)?(date_format(date_create(implode("", preg_split("/[-\s:,]/", $user->subscription_end_date))),"Y-m-d") >= date("Y-m-d",strtotime('today')))?true:false:false,
                    'userType' => $user->login_type,
                'isPartner' => ($user->is_partner == 1) ? true : false, 
                    'businessLimit' => (date_format(date_create(implode("", preg_split("/[-\s:,]/", $user->subscription_end_date))),"Y-m-d") >= date("Y-m-d",strtotime('today')))?$user->business_limit:1,   
                    'profileImage' => ($user->image)?(substr($user->image, 0, 4)=="http")?$user->image:((StorageSetting::getStorageSetting('storage') == 'DigitalOcean')?Storage::disk('spaces')->url('uploads/'.$user->image):asset('uploads/'.$user->image)):"",
                                    'createdAt' => date('Y-m-d H:i:s', strtotime($user->created_at)),
                'adConfig' => $user->getAdConfigPayload()
                );
            }
            else
            {
                $id = User::create([
                    'name' => $request->get('name'),
                    'email' => $request->get('email'),
                    'country' => $request->get('country'),
                    'mobile_no' => $request->get('phoneNumber'),
                    'password' => null, 
                    'api_token' => str::random(60),
                    'login_type' => "phone",
                    'image' => "911065a3-c074-43db-9fae-c4f94a5d754a.png",
                    'email_verified_at' => date('Y-m-d H:i:s'),
                    "referral_code" => strtoupper(str::random(10)),
                    "user_type" => "User",
                ])->id;

                $user = User::find($id);
                $ReferralRegister = ReferralRegister::where('user_id',$user->id)->first();

                $data = array(
                    'userId' => $user->id, 
                    'userName' => $user->name,
                    'emailId' => $user->email, 
                    'password' => "",
                    'country' => $user->country,
                    'phoneNumber' => $user->mobile_no,
                    'useReferral' => ($ReferralRegister)?$ReferralRegister->referral_code:"",
                    'planName' => $user->active_subscription ? $user->active_subscription->plan_name : "",
                    'planDuration' => $user->active_subscription ? $user->active_subscription->duration." ".$user->active_subscription->duration_type : "",
                    'planStartDate' => ($user->subscription_start_date)?$user->subscription_start_date:"",
                    'planEndDate' => ($user->subscription_start_date)?$user->subscription_end_date:"",
                    'isSubscribe' => ($user->is_subscribe)?(date_format(date_create(implode("", preg_split("/[-\s:,]/", $user->subscription_end_date))),"Y-m-d") >= date("Y-m-d",strtotime('today')))?true:false:false,
                    'userType' => $user->login_type,
                'isPartner' => ($user->is_partner == 1) ? true : false, 
                    'businessLimit' => (date_format(date_create(implode("", preg_split("/[-\s:,]/", $user->subscription_end_date))),"Y-m-d") >= date("Y-m-d",strtotime('today')))?$user->business_limit:1,   
                    'profileImage' => ($user->image)?(substr($user->image, 0, 4)=="http")?$user->image:((StorageSetting::getStorageSetting('storage') == 'DigitalOcean')?Storage::disk('spaces')->url('uploads/'.$user->image):asset('uploads/'.$user->image)):"",
                                    'createdAt' => date('Y-m-d H:i:s', strtotime($user->created_at)),
                'adConfig' => $user->getAdConfigPayload()
                );
            }
        }
        return $data;
    }

    public function user_data(Request $request)
    {
        $user = User::find($request->id);

        if (!empty($user))
        {
            $ReferralRegister = ReferralRegister::where('user_id',$user->id)->first();

            $res = array(
                'userId' => $user->id,  
                'userName' => $user->name,
                'emailId' => $user->email, 
                'password' => "",
                'country' => $user->country,
                'phoneNumber' => $user->mobile_no,
                'useReferral' => ($ReferralRegister)?$ReferralRegister->referral_code:"",
                'planName' => $user->active_subscription ? $user->active_subscription->plan_name : "",
                'planDuration' => $user->active_subscription ? $user->active_subscription->duration." ".$user->active_subscription->duration_type : "",
                'planStartDate' => ($user->subscription_start_date)?$user->subscription_start_date:"",
                'planEndDate' => ($user->subscription_start_date)?$user->subscription_end_date:"",
                'isSubscribe' => ($user->is_subscribe)?(date_format(date_create(implode("", preg_split("/[-\s:,]/", $user->subscription_end_date))),"Y-m-d") >= date("Y-m-d",strtotime('today')))?true:false:false,
                'userType' => $user->login_type,
                'isPartner' => ($user->is_partner == 1) ? true : false, 
                'businessLimit' => (date_format(date_create(implode("", preg_split("/[-\s:,]/", $user->subscription_end_date))),"Y-m-d") >= date("Y-m-d",strtotime('today')))?$user->business_limit:1,   
                'profileImage' => ($user->image)?(substr($user->image, 0, 4)=="http")?$user->image:((StorageSetting::getStorageSetting('storage') == 'DigitalOcean')?Storage::disk('spaces')->url('uploads/'.$user->image):asset('uploads/'.$user->image)):"",
                                'createdAt' => date('Y-m-d H:i:s', strtotime($user->created_at)),
                'adConfig' => $user->getAdConfigPayload()
            );
            return response()->json($res);
        } 
        else 
        {
            return response()->json([
                'status' => "Error",
                'message' => "Invalid userId",
            ], 404);
        }
    }

    public function profile_update(Request $request)
    {
        if($request->get('referralCode'))
        {
            $referral_exist = User::where('referral_code', $request->get('referralCode'))->first();
            if($referral_exist != null)
            {
                $user = User::find($request->id);
                if(!empty($user))
                {
                    if($user->user_type != "Demo")
                    {
                        $validation = Validator::make($request->all(), [
                            'name' => 'required',
                            'email' => 'required|email|unique:users,email,' . \Request::get("id"),
                            'mobile_no' => 'nullable|numeric|unique:users,mobile_no,' . \Request::get("id"),
                            "image" => "nullable|mimes:jpg,png,jpeg",
                        ]);
                
                        if ($validation->fails()) {
                            $errors = [];
                            foreach ($validation->errors()->messages() as $key => $value) {
                                $errors[] = is_array($value) ? implode(',', $value) : $value;
                            }
                
                            return response()->json([
                                'status' => "Error",
                                'message' => $errors,
                            ], 404);
                        }
                        else
                        {
                            if($user->email_verified_at != null)
                            {
                                $user_mobile = User::where("mobile_no",$request->get("mobile_no"))->first();
                                if($request->get("mobile_no") == null || empty($user_mobile) || $user_mobile->id == $user->id)
                                {
                                    $user = User::find($request->id);
                                    $user->name = $request->get("name");
                                    $user->email = $request->get("email");
                                    $user->country = $request->get("country");
                                    $user->mobile_no = $request->get("mobile_no");
                                    $user->save();
                        
                                    if(StorageSetting::getStorageSetting("storage") == "DigitalOcean")
                                    {
                                        if ($request->file("image") && $request->file('image')->isValid()) {
                                            $image = $request->file('image');
                                            $file = Str::uuid().'.'.$image->getClientOriginalExtension();
                                    
                                            $path = Storage::disk('spaces')->put('uploads/'.$file, file_get_contents($image),'public');
                                            
                                            $user = User::find($request->id);
                                            $user->image = $file;
                                            $user->save();
                                        }
                                    }
                                    else
                                    {
                                        if ($request->file("image") && $request->file('image')->isValid()) {
                                            $this->upload_image($request->file("image"),"image", $request->id);
                                        }
                                    }

                                    $rr = ReferralRegister::where("user_id",$request->id)->where("referral_code",$request->get('referralCode'))->first();
                                    if($rr == null && $request->get('referralCode'))
                                    {
                                        ReferralRegister::create([
                                            "user_id" => $request->id,
                                            "referral_code" => $request->get('referralCode')
                                        ]);

                                        $referral_user = User::where('referral_code',$request->get('referralCode'))->first();
                                        
                                        $isFraud = \App\Services\FraudDetectionService::isFraudulentSignup($request, $request->get('email'));

                                        if (!$isFraud) {
                                            $referral_user->current_balance = $referral_user->current_balance + ReferralSystem::getReferralSystem('register_point');
                                            $referral_user->total_balance = $referral_user->total_balance + ReferralSystem::getReferralSystem('register_point');
                                            $referral_user->save();
                                        }

                                        EarningHistory::create([
                                            "user_id" => $referral_user->id,
                                            "amount" => ReferralSystem::getReferralSystem('register_point'),
                                            "amount_type" => 1,
                                            "refer_user" => $request->id,
                                            "status" => $isFraud ? 'fraud' : 'pending'
                                        ]);

                                        // B2C Package Reward Logic
                                        $required_invites = \App\Models\ReferralSystem::getReferralSystem('referral_invite_count') ?? 5;
                                        $reward_days = \App\Models\ReferralSystem::getReferralSystem('referral_reward_days') ?? 15;
                                        $reward_package_id = \App\Models\ReferralSystem::getReferralSystem('referral_reward_package_id');

                                        $total_invites = \App\Models\ReferralRegister::where('referral_code', $request->get('referralCode'))->count();

                                        if ($total_invites > 0 && $total_invites % $required_invites == 0) {
                                            $sub = \App\Models\Subscription::find($reward_package_id);
                                            if($sub) {
                                                $referral_user->is_subscribe = 1;
                                                $referral_user->subscription_id = $sub->id;
                                                $current_end = $referral_user->subscription_end_date && strtotime($referral_user->subscription_end_date) > time() ? $referral_user->subscription_end_date : date('Y-m-d');
                                                $referral_user->subscription_end_date = date('Y-m-d', strtotime($current_end . " + {$reward_days} days"));
                                                $referral_user->save();
                                            }
                                        }
                                    }

                                    $user = User::find($request->id);
                                    $ReferralRegister = ReferralRegister::where('user_id',$user->id)->first();

                                    $data = array(
                                        'userId' => $user->id, 
                                        'userName' => $user->name,
                                        'emailId' => $user->email, 
                                        'password' => "",
                                        'country' => $user->country,
                                        'phoneNumber' => $user->mobile_no,
                                        'useReferral' => ($ReferralRegister)?$ReferralRegister->referral_code:"",
                                        'planName' => $user->active_subscription ? $user->active_subscription->plan_name : "",
                                        'planDuration' => $user->active_subscription ? $user->active_subscription->duration." ".$user->active_subscription->duration_type : "",
                                        'planStartDate' => ($user->subscription_start_date)?$user->subscription_start_date:"",
                                        'planEndDate' => ($user->subscription_start_date)?$user->subscription_end_date:"",
                                        'isSubscribe' => ($user->is_subscribe)?(date_format(date_create(implode("", preg_split("/[-\s:,]/", $user->subscription_end_date))),"Y-m-d") >= date("Y-m-d",strtotime('today')))?true:false:false,
                                        'userType' => $user->login_type,
                'isPartner' => ($user->is_partner == 1) ? true : false, 
                                        'businessLimit' => (date_format(date_create(implode("", preg_split("/[-\s:,]/", $user->subscription_end_date))),"Y-m-d") >= date("Y-m-d",strtotime('today')))?$user->business_limit:1,   
                                        'profileImage' => ($user->image)?(substr($user->image, 0, 4)=="http")?$user->image:((StorageSetting::getStorageSetting('storage') == 'DigitalOcean')?Storage::disk('spaces')->url('uploads/'.$user->image):asset('uploads/'.$user->image)):"",
                                                        'createdAt' => date('Y-m-d H:i:s', strtotime($user->created_at)),
                'adConfig' => $user->getAdConfigPayload()
                                    );
                                }
                                else
                                {
                                    return response()->json([
                                        'status' => "Error",
                                        'message' => "Mobile No Already Register!",
                                    ], 404);
                                }
                            }
                            else
                            {
                                return response()->json([
                                    'status' => "Error",
                                    'message' => "Please Verify Email Id!",
                                ], 404);
                            }
                        }
                    }
                    else
                    {
                        return response()->json([
                            'status' => "Error",
                            'message' => "This Function not work for Demo User!",
                        ], 404);
                    }
                }
                else 
                {
                    return response()->json([
                        'status' => "Error",
                        'message' => "Invalid userId",
                    ], 404);
                }
            }
            else
            {
                return response()->json([
                    'status' => "Error",
                    'message' => "Invalid Referral Code",
                ], 404);
            }
        }
        else
        {
            $user = User::find($request->id);
            if(!empty($user))
            {
                $validation = Validator::make($request->all(), [
                    'name' => 'required',
                    'email' => 'required|email|unique:users,email,' . \Request::get("id"),
                    "image" => "nullable|mimes:jpg,png,jpeg",
                ]);
        
                if ($validation->fails()) {
                    $errors = [];
                    foreach ($validation->errors()->messages() as $key => $value) {
                        $errors[] = is_array($value) ? implode(',', $value) : $value;
                    }
        
                    return response()->json([
                        'status' => "Error",
                        'message' => $errors,
                    ], 404);
                }
                else
                {
                    if($user->email_verified_at != null)
                    {
                        $user_mobile = User::where("mobile_no",$request->get("mobile_no"))->first();
                        if($request->get("mobile_no") == null || empty($user_mobile) || $user_mobile->id == $user->id)
                        {
                            $user = User::find($request->id);
                            $user->name = $request->get("name");
                            $user->email = $request->get("email");
                            $user->country = $request->get("country");
                            $user->mobile_no = $request->get("mobile_no");
                            $user->save();
                
                            if(StorageSetting::getStorageSetting("storage") == "DigitalOcean")
                            {
                                if ($request->file("image") && $request->file('image')->isValid()) {
                                    $image = $request->file('image');
                                    $file = Str::uuid().'.'.$image->getClientOriginalExtension();
                            
                                    $path = Storage::disk('spaces')->put('uploads/'.$file, file_get_contents($image),'public');
                                    
                                    $user = User::find($request->id);
                                    $user->image = $file;
                                    $user->save();
                                }
                            }
                            else
                            {
                                if($request->file("image") && $request->file('image')->isValid()) {
                                    $this->upload_image($request->file("image"),"image", $request->id);
                                }
                            }
                            
                            $user = User::find($request->id);
                            $ReferralRegister = ReferralRegister::where('user_id',$user->id)->first();
                            
                            $data = array(
                                'userId' => $user->id, 
                                'userName' => $user->name,
                                'emailId' => $user->email, 
                                'password' => "",
                                'country' => $user->country,
                                'phoneNumber' => $user->mobile_no,
                                'useReferral' => ($ReferralRegister)?$ReferralRegister->referral_code:"",
                                'planName' => $user->active_subscription ? $user->active_subscription->plan_name : "",
                                'planDuration' => $user->active_subscription ? $user->active_subscription->duration." ".$user->active_subscription->duration_type : "",
                                'planStartDate' => ($user->subscription_start_date)?$user->subscription_start_date:"",
                                'planEndDate' => ($user->subscription_start_date)?$user->subscription_end_date:"",
                                'isSubscribe' => ($user->is_subscribe)?(date_format(date_create(implode("", preg_split("/[-\s:,]/", $user->subscription_end_date))),"Y-m-d") >= date("Y-m-d",strtotime('today')))?true:false:false,
                                'userType' => $user->login_type,
                'isPartner' => ($user->is_partner == 1) ? true : false, 
                                'businessLimit' => (date_format(date_create(implode("", preg_split("/[-\s:,]/", $user->subscription_end_date))),"Y-m-d") >= date("Y-m-d",strtotime('today')))?$user->business_limit:1,   
                                'profileImage' => ($user->image)?(substr($user->image, 0, 4)=="http")?$user->image:((StorageSetting::getStorageSetting('storage') == 'DigitalOcean')?Storage::disk('spaces')->url('uploads/'.$user->image):asset('uploads/'.$user->image)):"",
                                                'createdAt' => date('Y-m-d H:i:s', strtotime($user->created_at)),
                'adConfig' => $user->getAdConfigPayload()
                            );
                        }
                        else
                        {
                            return response()->json([
                                'status' => "Error",
                                'message' => "Mobile No Already Register!",
                            ], 404);
                        }
                    }
                    else
                    {
                        return response()->json([
                            'status' => "Error",
                            'message' => "Please Verify Email Id!",
                        ], 404);
                    }
                }
            }
            else 
            {
                return response()->json([
                    'status' => "Error",
                    'message' => "Invalid userId",
                ], 404);
            }
        }

        return $data;
    }

    private function upload_image($file,$field,$id)
    {
        $destinationPath = './uploads';
        $extension = $file->getClientOriginalExtension();
        $fileName = Str::uuid() . '.' . $extension;
        $file->move($destinationPath, $fileName);
        
        $image = User::find($id);
        $image->$field = $fileName;
        $image->save();
    }

    public function forgot_password(Request $request)
    {
        $user = User::where('email', $request->email)->get()->toArray();
        if (!empty($user)) 
        {
            $this->validateEmail($request);
            $email = $request->email;
            $name = $user[0]['name'];
            //$newPassword = Str::random(10);
            $newPassword = mt_rand(100000, 999999);

            $user = User::find($user[0]['id']);
            $user->password =  bcrypt($newPassword);
            $user->save();

            $token = Str::random(60);
            PasswordReset::where('email', $email)->delete();
            PasswordReset::create(['email' => $email, 'token' => Hash::make($token), 'created_at' => date('Y-m-d H:i:s')]);
            SendForgotPasswordEmailJob::dispatch($email, $token, $name, $newPassword);

            return response()->json([
                'status' => "Success",
                'message' => "Email Send Your Email Address.",
            ], 200);
        } 
        else 
        {
            return response()->json([
                'status' => "Error",
                'message' => "Please Enter Valid Email Address...",
            ], 404);
        }
    }

    protected function validateEmail(Request $request)
    {
        $this->validate($request, ['email' => 'required|email']);
    }

    public function change_password(Request $request)
    {
        $user = User::find($request->get('userId'));
        $validation = Validator::make($request->all(), [
            'newPassword' => 'required',
        ]);

        if ($validation->fails()) {
            $errors = [];
            foreach ($validation->errors()->messages() as $key => $value) {
                $errors[] = is_array($value) ? implode(',', $value) : $value;
            }

            return response()->json([
              'status' => "Error",
              'message' => $errors,
            ], 404);
        }
        else
        {
            if ($user == null) {
                return response()->json([
                    'status' => 'Error',
                    'message' => "Invalid User Id!",
                    'data' => null,
                ], 404);
            } 
            else
            {
                $user->password = bcrypt($request->get('newPassword'));
                $user->save();

                $data['status'] = 'Success';
                $data['message'] = "Your Password has been Updated Successfully.";
            }
        }
        return $data;
    }

    public function register_fcm(Request $request)
    {
        $validation = Validator::make($request->all(), [
            'fcmToken' => 'required',
            'userId' => 'required|integer',
            'deviceId' => 'required',
        ]);

        $errors = $validation->errors();
        if (count($errors) > 0) {
            $data['status'] = 401;
            $data['message'] = "Unable to update FCM ID and deviceId";
            $data['data'] = "";

        } else {
            $user = User::find($request->userId);
            if (!empty($user)){
                $id = AndroidLogin::create([
                    'userId' => $request->get('userId'),
                    'fcmToken' => $request->get('fcmToken'), 
                    'deviceId' => $request->get('deviceId'), 
                ])->id;


                $data['status'] = 0;
                $data['message'] = "FCM Token Register Successfully!";
                $data['data'] = "";
            } else {
                $data['status'] = 401;
                $data['message'] = "Invalid userId.";
                $data['data'] = "";
            }

        }
        return $data;
    }

    public function logout(Request $request)
    {
        $val = AndroidLogin::where('userId',$request->userId)->where('deviceId',$request->deviceId)->get();

        if (!empty($val))
        {
            AndroidLogin::where('userId',$request->userId)->where('deviceId',$request->deviceId)->delete();
            $data['status'] = 0;
            $data['message'] = "User Logout Successfully!";
            $data['data'] = "";
        }
        else
        {
            $data['status'] = 404;
            $data['message'] = "Invalid Data!";
            $data['data'] = "";
        }

        return $data;
    }

    public function delete_user_account(Request $request) {
		try {
            \DB::beginTransaction();

            $data = User::where('id', $request->get('userId'))->first();
			
			if ($data) {
                $emaildata = $data->email."_deleted";
                User::where('id', $request->get('userId'))->update(['email' => $emaildata]);

				$data->delete();
                \DB::commit();

				return response()->json([
                    'status' => "success",
                    'message' => "User Account Deleted Successfully!",
                ], 200);
			} else {
                
                return response()->json([
                    'status' => "Error",
                    'message' => "Invalid userId",
                ], 404);
			}
		} catch (\Throwable $th) {
            \DB::rollback();
            return response()->json([
                'status' => "Error",
                'message' => "Server Error",
            ], 500);
		}
	}

    public function reportError(Request $request)
    {
        $userId = $request->userId;
        if(!$userId) {
            return response()->json(['status' => 'error', 'message' => 'User ID required'], 400);
        }

        \App\Models\ClientError::create([
            'user_id' => $userId,
            'error_code' => $request->error_code,
            'error_message' => $request->error_message,
            'device_info' => $request->device_info ?? $request->header('User-Agent'),
            'status' => 'Pending',
        ]);

        return response()->json([
            'status' => 'success',
            'message' => 'Error reported successfully'
        ], 200);
    }


    public function trackActivity(Request $request)
    {
        $userId = $request->get('userId') ?? $request->get('user_id');
        $action = $request->get('action');
        $itemType = $request->get('item_type');
        $itemId = $request->get('item_id');
        $downloadedImageBase64 = $request->get('downloaded_image');

        if (!$userId) {
             return response()->json(['status' => 'error', 'message' => 'User ID required'], 400);
        }

        $downloadedImagePath = null;
        if ($downloadedImageBase64) {
            if (str_starts_with($downloadedImageBase64, 'data:image')) {
                try {
                    $imgData = explode(',', $downloadedImageBase64);
                    if (count($imgData) > 1) {
                        $decodedData = base64_decode($imgData[1]);
                        if ($decodedData) {
                            $filename = 'download_' . time() . '_' . uniqid() . '.jpg';
                            $dir = public_path('uploads/downloads');
                            if (!is_dir($dir)) {
                                mkdir($dir, 0755, true);
                            }
                            file_put_contents($dir . '/' . $filename, $decodedData);
                            $downloadedImagePath = 'uploads/downloads/' . $filename;
                        }
                    }
                } catch (\Exception $e) {
                    \Log::error('Failed to save downloaded image preview: ' . $e->getMessage());
                }
            } else {
                $path = parse_url($downloadedImageBase64, PHP_URL_PATH);
                if ($path) {
                    if (str_contains($path, 'uploads/')) {
                        $downloadedImagePath = 'uploads/' . explode('uploads/', $path)[1];
                    } else {
                        $downloadedImagePath = $downloadedImageBase64;
                    }
                } else {
                    $downloadedImagePath = $downloadedImageBase64;
                }
            }
        }

        \App\Models\UserActivity::create([
            'user_id' => $userId,
            'action' => $action,
            'payload' => [
                'item_type' => $itemType,
                'item_id' => $itemId,
                'platform' => $request->get('platform') ?? 'Mobile',
                'downloaded_image' => $downloadedImagePath,
            ],
            'ip_address' => $request->ip(),
            'url' => $request->fullUrl(),
            'method' => $request->method(),
            'user_agent' => $request->header('User-Agent'),
        ]);

        $this->checkMilestoneBadges($userId);

        // ── Consume subscription feature limit on download ──
        if ($action === 'download_template' && $itemType) {
            $user = User::find($userId);
            if ($user) {
                // Map the template item_type to the subscription feature key
                $featureMap = [
                    'festival'               => 'festival_post',
                    'category'               => 'business_category_post',
                    'custom'                 => 'custom_post',
                    'business_custom_frame'  => 'custom_post',
                    'business_frame'         => 'custom_post',
                    'business_custom'        => 'custom_post',
                ];

                $featureKey = $featureMap[$itemType] ?? null;

                if ($featureKey) {
                    $user->consumeFeature($featureKey);
                }
            }
        }

        return response()->json(['status' => 'success']);
    }

    /**
     * Batch track ad events from the Flutter app.
     * Accepts an array of ad events and bulk inserts them for minimal server load.
     * 
     * Expected payload:
     * {
     *   "userId": "123",
     *   "events": [
     *     {"ad_type": "banner", "event": "impression", "timestamp": "2026-05-19 14:00:00"},
     *     {"ad_type": "interstitial", "event": "impression", "timestamp": "2026-05-19 14:01:00"},
     *     {"ad_type": "rewarded", "event": "completed", "timestamp": "2026-05-19 14:02:00"}
     *   ]
     * }
     */
    public function trackAdEvents(Request $request)
    {
        $userId = $request->get('userId') ?? $request->get('user_id');
        $events = $request->get('events', []);

        if (empty($events) || !is_array($events)) {
            return response()->json(['status' => 'error', 'message' => 'No events provided'], 400);
        }

        $validTypes = ['banner', 'interstitial', 'rewarded'];
        $validEvents = ['impression', 'click', 'completed'];

        $rows = [];
        foreach ($events as $evt) {
            $adType = $evt['ad_type'] ?? null;
            $event = $evt['event'] ?? 'impression';

            if (!$adType || !in_array($adType, $validTypes)) continue;
            if (!in_array($event, $validEvents)) $event = 'impression';

            $rows[] = [
                'user_id' => $userId,
                'ad_type' => $adType,
                'event' => $event,
                'created_at' => $evt['timestamp'] ?? now(),
            ];
        }

        if (!empty($rows)) {
            // Bulk insert for minimal DB overhead
            \App\Models\AdEvent::insert($rows);
        }

        return response()->json(['status' => 'success', 'tracked' => count($rows)]);
    }

    private function checkMilestoneBadges($userId)
    {
        $postCount = \App\Models\UserActivity::where('user_id', $userId)
            ->whereIn('action', ['download_template', 'create_custom_post', 'create_festival_post', 'magic_cloner_use'])
            ->count();

        $milestones = [
            10 => ['name' => '10 Posts Created!', 'icon' => 'fa-star'],
            50 => ['name' => '50 Posts Created!', 'icon' => 'fa-medal'],
            100 => ['name' => '100 Posts Created!', 'icon' => 'fa-crown'],
            500 => ['name' => '500 Posts Created!', 'icon' => 'fa-trophy'],
        ];

        if (array_key_exists($postCount, $milestones)) {
            $badge = $milestones[$postCount];
            
            $exists = \Illuminate\Support\Facades\DB::table('user_achievements')
                ->where('user_id', $userId)
                ->where('badge_name', $badge['name'])
                ->exists();

            if (!$exists) {
                \Illuminate\Support\Facades\DB::table('user_achievements')->insert([
                    'user_id' => $userId,
                    'badge_name' => $badge['name'],
                    'badge_icon' => $badge['icon'],
                    'description' => 'Awarded for creating ' . $postCount . ' posts/designs.',
                    'earned_at' => now(),
                    'created_at' => now(),
                    'updated_at' => now(),
                ]);

                \Illuminate\Support\Facades\DB::table('user_notifications')->insert([
                    'user_id' => $userId,
                    'title' => 'Achievement Unlocked: ' . $badge['name'],
                    'message' => 'Congratulations! You have unlocked the ' . $badge['name'] . ' badge.',
                    'action_url' => '',
                    'status' => 'unread',
                    'created_at' => now(),
                    'updated_at' => now(),
                ]);
            }
        }
    }
}