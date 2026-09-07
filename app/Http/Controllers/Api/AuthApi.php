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
use App\Models\Business;
use App\Models\ReferralSystem;
use App\Models\StorageSetting;
use App\Models\ReferralRegister;
use App\Http\Controllers\Controller;
use App\Services\FirebaseIdTokenVerifier;
use App\Services\MobileAccessTokenService;
use App\Services\AdLiveSignedSecurityEventService;
use App\Services\AdLiveIdentitySyncService;
use App\Services\AdLiveIdentityMutationService;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Facades\Password;
use Illuminate\Support\Facades\Validator;
use Symfony\Component\HttpFoundation\Response;

class AuthApi extends Controller
{
    public function login(Request $request, AdLiveIdentitySyncService $adLiveIdentitySync)
    {
        $credentials = $request->validate([
            'email' => ['required', 'email'],
            'password' => ['required', 'string'],
        ]);
        $user = User::where('email', mb_strtolower(trim($credentials['email'])))->first();

        if ($user && (int) $user->status === 1 && Hash::check($credentials['password'], $user->password))
        {
            $this->updateUserStreak($user);
            $user->refresh();
            $adLiveIdentitySync->queueForUser($user, 'identity.updated');
            $accessToken = app(MobileAccessTokenService::class)->issue($user);
            $ReferralRegister = ReferralRegister::where('user_id', $user->id)->first();
            
            $res = array(
                'userId' => $user->id,  
                'userName' => $user->name,
                'emailId' => $user->email, 
                'access_token' => $accessToken,
                'password' => "",
                'country' => $user->country,
                'phoneNumber' => $user->mobile_no,
                'useReferral' => ($ReferralRegister)?$ReferralRegister->referral_code:"",
                'planName' => $user->active_subscription ? $user->active_subscription->plan_name : "",
                'planDuration' => $user->active_subscription ? $user->active_subscription->duration." ".$user->active_subscription->duration_type : "",
                'planStartDate' => ($user->subscription_start_date)?$user->subscription_start_date:"",
                'planEndDate' => ($user->subscription_start_date)?$user->subscription_end_date:"",
                'isSubscribe' => ($user->is_subscribe && !empty($user->subscription_end_date))?(date("Y-m-d", strtotime($user->subscription_end_date)) >= date("Y-m-d",strtotime('today')))?true:false:false,
                'is_email_verify' => ($user->email_verified_at != null)?true:false,
                'userType' => $user->login_type,
                'isPartner' => ($user->is_partner == 1) ? true : false, 
                'businessLimit' => (!empty($user->subscription_end_date) && date("Y-m-d", strtotime($user->subscription_end_date)) >= date("Y-m-d",strtotime('today')))?$user->business_limit:1,   
                'profileImage' => ($user->image)?(substr($user->image, 0, 4)=="http")?$user->image:((StorageSetting::getStorageSetting('storage') == 'DigitalOcean')?Storage::disk('spaces')->url('uploads/'.$user->image):asset('uploads/'.$user->image)):"",
                                'createdAt' => date('Y-m-d H:i:s', strtotime($user->created_at)),
                'adConfig' => $user->getAdConfigPayload(),
                'currentStreak' => $user->current_streak ?? 1,
                'maxStreak' => $user->max_streak ?? 1    
            );
        } 
        else 
        {
            return response()->json([
                'status' => "Error",
                'message' => "Invalid Login Credentials",
            ], Response::HTTP_UNPROCESSABLE_ENTITY);
        }

        return response()->json($res);
    }

    public function registration(Request $request, AdLiveIdentitySyncService $adLiveIdentitySync)
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

        $validation = Validator::make($request->all(), [
            'name' => 'required|string|max:255',
            'password' => ['required', \Illuminate\Validation\Rules\Password::min(10)->mixedCase()->numbers()->symbols()],
            'email' => 'required|email|max:255|unique:users,email,NULL,id,deleted_at,NULL',
            'country' => 'nullable|numeric',
            'mobile_no' => 'required|digits_between:7,20|unique:users,mobile_no,NULL,id,deleted_at,NULL',
            'image' => "nullable|image|mimes:jpg,png,jpeg,webp|max:2048",
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
                'login_type' => "normal",
                "referral_code" => strtoupper(str::random(10)),
                "user_type" => "User",
                'registration_source' => 'artera_pixel',
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

            // Create Business if provided
            if ($request->filled('bussinessName')) {
                $bId = \App\Models\Business::create([
                    "name" => $request->get("bussinessName"),
                    "email" => $request->get("bussinessEmail") ?? $request->get("email"),
                    "mobile_no" => $request->get("bussinessNumber") ?? $request->get("mobile_no"),
                    "address" => $request->get("bussinessAddress") ?? '',
                    "website" => $request->get("bussinessWebsite") ?? '',
                    "user_id" => $id,
                    "business_category_id" => $request->get("businessCategoryId") ?? 1,
                    "is_default" => 1,
                ])->id;

                $businessUpdate = \App\Models\Business::find($bId);

                if($request->get('businessSubCategoryIds')) {
                    $subCatIds = $request->get('businessSubCategoryIds');
                    if(is_string($subCatIds)) {
                        $subCatIds = json_decode($subCatIds, true) ?? explode(',', $subCatIds);
                    }
                    if (is_array($subCatIds)) {
                        $businessUpdate->sub_categories()->sync(array_filter($subCatIds));
                        $businessUpdate->business_sub_category_ids = array_filter($subCatIds);
                    }
                }

                if($request->get('businessTypeIds')) {
                    $typeIds = $request->get('businessTypeIds');
                    if(is_string($typeIds)) {
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
                            if($pId) {
                                \App\Models\BusinessProductMapping::create(['business_id' => $bId, 'business_product_id' => $pId]);
                            }
                        }
                    }
                }
            }

            $user = User::find($id);
            $adLiveIdentitySync->queueForUser($user, 'identity.created');
            $accessToken = app(MobileAccessTokenService::class)->issue($user);
            $email = $user->email;
            $name = $user->name;
            //$code = Str::random(10);
            $code = random_int(100000, 999999);

            $token = Str::random(60);
            EmailVerified::where('user_id', $id)->delete();
            EmailVerified::create(['user_id' => $id, 'code' => $code, 'created_at' => date('Y-m-d H:i:s')]);
            SendVerificationEmailJob::dispatch($email, $token, $name, $code);
            $ReferralRegister = ReferralRegister::where('user_id',$id)->first();

            $data = array(
                'userId' => $user->id, 
                'userName' => $user->name,
                'emailId' => $user->email, 
                'access_token' => $accessToken,
                'password' => "",
                'country' => $user->country,
                'phoneNumber' => $user->mobile_no,
                'useReferral' => ($ReferralRegister)?$ReferralRegister->referral_code:"",
                'planName' => $user->active_subscription ? $user->active_subscription->plan_name : "",
                'planDuration' => $user->active_subscription ? $user->active_subscription->duration." ".$user->active_subscription->duration_type : "",
                'planStartDate' => ($user->subscription_start_date)?$user->subscription_start_date:"",
                'planEndDate' => ($user->subscription_start_date)?$user->subscription_end_date:"",
                'isSubscribe' => ($user->is_subscribe && !empty($user->subscription_end_date))?(date("Y-m-d", strtotime($user->subscription_end_date)) >= date("Y-m-d",strtotime('today')))?true:false:false,
                'userType' => $user->login_type,
                'isPartner' => ($user->is_partner == 1) ? true : false, 
                'businessLimit' => (!empty($user->subscription_end_date) && date("Y-m-d", strtotime($user->subscription_end_date)) >= date("Y-m-d",strtotime('today')))?$user->business_limit:1,   
                'profileImage' => ($user->image)?(substr($user->image, 0, 4)=="http")?$user->image:((StorageSetting::getStorageSetting('storage') == 'DigitalOcean')?Storage::disk('spaces')->url('uploads/'.$user->image):asset('uploads/'.$user->image)):"",
                                'createdAt' => date('Y-m-d H:i:s', strtotime($user->created_at)),
                'adConfig' => $user->getAdConfigPayload(),
                'currentStreak' => $user->current_streak ?? 1,
                'maxStreak' => $user->max_streak ?? 1
            );
        }

        return $data;
    }

    public function resendVerifyCode(Request $request)
    {
        $user = auth('sanctum')->user();
        if(!empty($user))
        {
            $email = $user->email;
            $name = $user->name;
            // $code = Str::random(10);
            $code = random_int(100000, 999999);

            $token = Str::random(60);
            EmailVerified::where('user_id', $user->id)->delete();
            EmailVerified::create(['user_id' => $user->id, 'code' => $code, 'created_at' => now()]);
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
        $validated = $request->validate(['code' => ['required', 'digits:6']]);
        $user = auth('sanctum')->user();
        if (!$user) {
            return response()->json(['status' => 'Error', 'message' => 'Authentication is required.'], Response::HTTP_UNAUTHORIZED);
        }

        $exist = EmailVerified::where('user_id', $user->id)->where('code', $validated['code'])->first();
        if($exist != null)
        {
            if(!empty($user))
            {
                $user->email_verified_at = now();
                $user->save();
                $exist->delete();

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
        // This endpoint used caller-supplied Google identity data, which could
        // be forged to take over any account. Mobile Google sign-in must only
        // be enabled after server-side ID-token verification is configured.
        return response()->json([
            'status' => 'Error',
            'message' => 'Mobile Google sign-in is temporarily unavailable. Please use email sign-in.',
        ], Response::HTTP_SERVICE_UNAVAILABLE);

        /*
         * Historical implementation retained only as commented reference until
         * the deprecated route can be removed in a future API release. It must
         * never run: it trusted caller-supplied identity fields.
         *
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
                'isSubscribe' => ($user->is_subscribe && !empty($user->subscription_end_date))?(date("Y-m-d", strtotime($user->subscription_end_date)) >= date("Y-m-d",strtotime('today')))?true:false:false,
                'userType' => $user->login_type,
                'isPartner' => ($user->is_partner == 1) ? true : false, 
                'businessLimit' => (!empty($user->subscription_end_date) && date("Y-m-d", strtotime($user->subscription_end_date)) >= date("Y-m-d",strtotime('today')))?$user->business_limit:1,   
                'profileImage' => ($user->image)?(substr($user->image, 0, 4)=="http")?$user->image:((StorageSetting::getStorageSetting('storage') == 'DigitalOcean')?Storage::disk('spaces')->url('uploads/'.$user->image):asset('uploads/'.$user->image)):"",
                                'createdAt' => date('Y-m-d H:i:s', strtotime($user->created_at)),
                'adConfig' => $user->getAdConfigPayload(),
                'currentStreak' => $user->current_streak ?? 1,
                'maxStreak' => $user->max_streak ?? 1
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
                    'isSubscribe' => ($user->is_subscribe && !empty($user->subscription_end_date))?(date("Y-m-d", strtotime($user->subscription_end_date)) >= date("Y-m-d",strtotime('today')))?true:false:false,
                    'userType' => $user->login_type,
                'isPartner' => ($user->is_partner == 1) ? true : false, 
                    'businessLimit' => (!empty($user->subscription_end_date) && date("Y-m-d", strtotime($user->subscription_end_date)) >= date("Y-m-d",strtotime('today')))?$user->business_limit:1,   
                    'profileImage' => ($user->image)?(substr($user->image, 0, 4)=="http")?$user->image:((StorageSetting::getStorageSetting('storage') == 'DigitalOcean')?Storage::disk('spaces')->url('uploads/'.$user->image):asset('uploads/'.$user->image)):"",
                                    'createdAt' => date('Y-m-d H:i:s', strtotime($user->created_at)),
                'adConfig' => $user->getAdConfigPayload(),
                'currentStreak' => $user->current_streak ?? 1,
                'maxStreak' => $user->max_streak ?? 1
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
                'registration_source' => 'artera_pixel',
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
                    'isSubscribe' => ($user->is_subscribe && !empty($user->subscription_end_date))?(date("Y-m-d", strtotime($user->subscription_end_date)) >= date("Y-m-d",strtotime('today')))?true:false:false,
                    'userType' => $user->login_type,
                'isPartner' => ($user->is_partner == 1) ? true : false, 
                    'businessLimit' => (!empty($user->subscription_end_date) && date("Y-m-d", strtotime($user->subscription_end_date)) >= date("Y-m-d",strtotime('today')))?$user->business_limit:1,   
                    'profileImage' => ($user->image)?(substr($user->image, 0, 4)=="http")?$user->image:((StorageSetting::getStorageSetting('storage') == 'DigitalOcean')?Storage::disk('spaces')->url('uploads/'.$user->image):asset('uploads/'.$user->image)):"",
                                    'createdAt' => date('Y-m-d H:i:s', strtotime($user->created_at)),
                'adConfig' => $user->getAdConfigPayload(),
                'currentStreak' => $user->current_streak ?? 1,
                'maxStreak' => $user->max_streak ?? 1
                );
            }
        }
        return $data;
        */
    }

    /**
     * Sign in or register a mobile user from a server-verified Firebase Google
     * ID token. The email, name, and Firebase UID are never accepted from the
     * request body because they can be forged by a modified app.
     */
    public function google_sign_in(Request $request, FirebaseIdTokenVerifier $tokenVerifier, AdLiveIdentitySyncService $adLiveIdentitySync)
    {
        $validated = $request->validate([
            'firebase_id_token' => ['required', 'string', 'max:4096'],
        ]);

        try {
            $identity = $tokenVerifier->verifyGoogleIdentity($validated['firebase_id_token']);
        } catch (\LogicException $exception) {
            Log::error('Mobile Google sign-in is not configured.', [
                'exception' => get_class($exception),
            ]);

            return response()->json([
                'status' => 'Error',
                'message' => 'Google sign-in is not configured. Please try another sign-in method.',
            ], Response::HTTP_SERVICE_UNAVAILABLE);
        } catch (\Throwable $exception) {
            Log::warning('Mobile Google sign-in rejected an ID token.', [
                'exception' => get_class($exception),
                'ip' => $request->ip(),
            ]);

            return response()->json([
                'status' => 'Error',
                'message' => 'Google sign-in could not be verified. Please try again.',
            ], Response::HTTP_UNAUTHORIZED);
        }

        try {
            [$user, $isNewUser] = DB::transaction(function () use ($identity): array {
                $user = User::where('firebase_uid', $identity['uid'])->lockForUpdate()->first();
                $isNewUser = false;

                if (!$user) {
                    $existingEmailUser = User::where('email', $identity['email'])->lockForUpdate()->first();

                    if ($existingEmailUser) {
                        // Only migrate an old Google account that was created before
                        // Firebase UID binding existed. A password account is not
                        // auto-linked by email, preventing account takeover.
                        if ($existingEmailUser->login_type !== 'google' || !empty($existingEmailUser->firebase_uid)) {
                            throw new \DomainException('This email is already protected by another sign-in method.');
                        }

                        $existingEmailUser->forceFill([
                            'firebase_uid' => $identity['uid'],
                            'google_linked_at' => now(),
                            'email_verified_at' => $existingEmailUser->email_verified_at ?? now(),
                            'image' => $existingEmailUser->image ?: $identity['photo_url'],
                        ])->save();

                        $user = $existingEmailUser;
                    } else {
                        $user = User::create([
                            'name' => $identity['name'],
                            'email' => $identity['email'],
                            'password' => Hash::make(Str::random(64)),
                            'image' => $identity['photo_url'],
                            'login_type' => 'google',
                            'email_verified_at' => now(),
                            'referral_code' => strtoupper(Str::random(10)),
                            // Product origin remains Artera Pixel; the
                            // login_type/federated UID separately record that
                            // Google was used to authenticate.
                            'registration_source' => 'artera_pixel',
                        ]);

                        $user->forceFill([
                            'user_type' => 'User',
                            'firebase_uid' => $identity['uid'],
                            'google_linked_at' => now(),
                        ])->save();
                        $isNewUser = true;
                    }
                }

                return [$user, $isNewUser];
            });
        } catch (\DomainException $exception) {
            return response()->json([
                'status' => 'Error',
                'message' => $exception->getMessage(),
            ], Response::HTTP_CONFLICT);
        } catch (\Throwable $exception) {
            Log::error('Mobile Google sign-in account provisioning failed.', [
                'exception' => get_class($exception),
            ]);

            return response()->json([
                'status' => 'Error',
                'message' => 'Unable to complete Google sign-in. Please try again.',
            ], Response::HTTP_INTERNAL_SERVER_ERROR);
        }

        $this->updateUserStreak($user);
        $user->refresh();
        $adLiveIdentitySync->queueForUser($user, $isNewUser ? 'identity.created' : 'identity.updated');
        $accessToken = app(MobileAccessTokenService::class)->issue($user);

        return response()->json($this->mobileSessionPayload($user, $accessToken, [
            'isNewUser' => $isNewUser,
        ]));
    }

    /**
     * Keep Google sign-in response data identical to password login so the
     * mobile app can use one session persistence path for both flows.
     */
    private function mobileSessionPayload(User $user, string $accessToken, array $extra = []): array
    {
        $referralRegister = ReferralRegister::where('user_id', $user->id)->first();

        return array_merge([
            'userId' => $user->id,
            'userName' => $user->name,
            'emailId' => $user->email,
            'access_token' => $accessToken,
            'password' => '',
            'country' => $user->country,
            'phoneNumber' => $user->mobile_no,
            'useReferral' => $referralRegister ? $referralRegister->referral_code : '',
            'planName' => $user->active_subscription ? $user->active_subscription->plan_name : '',
            'planDuration' => $user->active_subscription ? $user->active_subscription->duration . ' ' . $user->active_subscription->duration_type : '',
            'planStartDate' => $user->subscription_start_date ?: '',
            'planEndDate' => $user->subscription_end_date ?: '',
            'isSubscribe' => ($user->is_subscribe && !empty($user->subscription_end_date))
                ? (date('Y-m-d', strtotime($user->subscription_end_date)) >= date('Y-m-d', strtotime('today')))
                : false,
            'is_email_verify' => $user->email_verified_at !== null,
            'userType' => $user->login_type,
            'isPartner' => $user->is_partner == 1,
            'businessLimit' => (!empty($user->subscription_end_date) && date('Y-m-d', strtotime($user->subscription_end_date)) >= date('Y-m-d', strtotime('today')))
                ? $user->business_limit
                : 1,
            'profileImage' => $this->profileImageUrl($user),
            'createdAt' => date('Y-m-d H:i:s', strtotime($user->created_at)),
            'adConfig' => $user->getAdConfigPayload(),
            'currentStreak' => $user->current_streak ?? 1,
            'maxStreak' => $user->max_streak ?? 1,
        ], $extra);
    }

    private function profileImageUrl(User $user): string
    {
        if (!$user->image) {
            return '';
        }

        if (substr($user->image, 0, 4) === 'http') {
            return $user->image;
        }

        return StorageSetting::getStorageSetting('storage') === 'DigitalOcean'
            ? Storage::disk('spaces')->url('uploads/' . $user->image)
            : asset('uploads/' . $user->image);
    }

    public function phone_login(Request $request)
    {
        // Never authenticate a caller based only on a phone number. This is
        // deliberately disabled until a server-side Firebase ID-token verifier
        // is connected and validates the token's phone_number claim.
        return response()->json([
            'status' => 'Error',
            'message' => 'Phone sign-in is temporarily unavailable. Please use email sign-in.',
        ], Response::HTTP_SERVICE_UNAVAILABLE);

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
                'isSubscribe' => ($user->is_subscribe && !empty($user->subscription_end_date))?(date("Y-m-d", strtotime($user->subscription_end_date)) >= date("Y-m-d",strtotime('today')))?true:false:false,
                'userType' => $user->login_type,
                'isPartner' => ($user->is_partner == 1) ? true : false, 
                'businessLimit' => (!empty($user->subscription_end_date) && date("Y-m-d", strtotime($user->subscription_end_date)) >= date("Y-m-d",strtotime('today')))?$user->business_limit:1,   
                'profileImage' => ($user->image)?(substr($user->image, 0, 4)=="http")?$user->image:((StorageSetting::getStorageSetting('storage') == 'DigitalOcean')?Storage::disk('spaces')->url('uploads/'.$user->image):asset('uploads/'.$user->image)):"",
                                'createdAt' => date('Y-m-d H:i:s', strtotime($user->created_at)),
                'adConfig' => $user->getAdConfigPayload(),
                'currentStreak' => $user->current_streak ?? 1,
                'maxStreak' => $user->max_streak ?? 1
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
                    'isSubscribe' => ($user->is_subscribe && !empty($user->subscription_end_date))?(date("Y-m-d", strtotime($user->subscription_end_date)) >= date("Y-m-d",strtotime('today')))?true:false:false,
                    'userType' => $user->login_type,
                'isPartner' => ($user->is_partner == 1) ? true : false, 
                    'businessLimit' => (!empty($user->subscription_end_date) && date("Y-m-d", strtotime($user->subscription_end_date)) >= date("Y-m-d",strtotime('today')))?$user->business_limit:1,   
                    'profileImage' => ($user->image)?(substr($user->image, 0, 4)=="http")?$user->image:((StorageSetting::getStorageSetting('storage') == 'DigitalOcean')?Storage::disk('spaces')->url('uploads/'.$user->image):asset('uploads/'.$user->image)):"",
                                    'createdAt' => date('Y-m-d H:i:s', strtotime($user->created_at)),
                'adConfig' => $user->getAdConfigPayload(),
                'currentStreak' => $user->current_streak ?? 1,
                'maxStreak' => $user->max_streak ?? 1
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
                'registration_source' => 'artera_pixel',
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
                    'isSubscribe' => ($user->is_subscribe && !empty($user->subscription_end_date))?(date("Y-m-d", strtotime($user->subscription_end_date)) >= date("Y-m-d",strtotime('today')))?true:false:false,
                    'userType' => $user->login_type,
                'isPartner' => ($user->is_partner == 1) ? true : false, 
                    'businessLimit' => (!empty($user->subscription_end_date) && date("Y-m-d", strtotime($user->subscription_end_date)) >= date("Y-m-d",strtotime('today')))?$user->business_limit:1,   
                    'profileImage' => ($user->image)?(substr($user->image, 0, 4)=="http")?$user->image:((StorageSetting::getStorageSetting('storage') == 'DigitalOcean')?Storage::disk('spaces')->url('uploads/'.$user->image):asset('uploads/'.$user->image)):"",
                                    'createdAt' => date('Y-m-d H:i:s', strtotime($user->created_at)),
                'adConfig' => $user->getAdConfigPayload(),
                'currentStreak' => $user->current_streak ?? 1,
                'maxStreak' => $user->max_streak ?? 1
                );
            }
        }
        return $data;
    }

    public function user_data(Request $request)
    {
        $authenticatedUser = auth('sanctum')->user();
        if (!$authenticatedUser) {
            return response()->json(['status' => 'Error', 'message' => 'Authentication is required.'], Response::HTTP_UNAUTHORIZED);
        }

        $requestedId = $request->input('id') ?? $request->input('userId') ?? $authenticatedUser->id;
        if ((int) $requestedId !== $authenticatedUser->id) {
            \Log::warning('Blocked API ownership mismatch on user_data.', [
                'authenticated_user_id' => $authenticatedUser->id,
                'requested_user_id' => $requestedId,
                'ip' => $request->ip(),
            ]);

            return response()->json(['status' => 'Error', 'message' => 'Unauthorized'], Response::HTTP_FORBIDDEN);
        }

        $user = $authenticatedUser;

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
                'isSubscribe' => ($user->is_subscribe && !empty($user->subscription_end_date))?(date("Y-m-d", strtotime($user->subscription_end_date)) >= date("Y-m-d",strtotime('today')))?true:false:false,
                'userType' => $user->login_type,
                'isPartner' => ($user->is_partner == 1) ? true : false, 
                'businessLimit' => (!empty($user->subscription_end_date) && date("Y-m-d", strtotime($user->subscription_end_date)) >= date("Y-m-d",strtotime('today')))?$user->business_limit:1,   
                'profileImage' => ($user->image)?(substr($user->image, 0, 4)=="http")?$user->image:((StorageSetting::getStorageSetting('storage') == 'DigitalOcean')?Storage::disk('spaces')->url('uploads/'.$user->image):asset('uploads/'.$user->image)):"",
                                'createdAt' => date('Y-m-d H:i:s', strtotime($user->created_at)),
                'adConfig' => $user->getAdConfigPayload(),
                'currentStreak' => $user->current_streak ?? 1,
                'maxStreak' => $user->max_streak ?? 1,
                'rewardPoints' => $user->reward_points ?? 0
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
        $authenticatedUser = auth('sanctum')->user();
        if (!$authenticatedUser) {
            return response()->json(['status' => 'Error', 'message' => 'Authentication is required.'], Response::HTTP_UNAUTHORIZED);
        }

        if ($request->filled('id') && (int) $request->input('id') !== $authenticatedUser->id) {
            \Log::warning('Blocked API ownership mismatch on profile_update.', [
                'authenticated_user_id' => $authenticatedUser->id,
                'requested_user_id' => $request->input('id'),
                'ip' => $request->ip(),
            ]);

            return response()->json(['status' => 'Error', 'message' => 'Unauthorized'], Response::HTTP_FORBIDDEN);
        }

        // The bearer token, never a request parameter, decides whose profile
        // can be changed.
        $request->merge(['id' => $authenticatedUser->id]);

        \Log::info('=== PROFILE_UPDATE DEBUG START ===', [
            'request_id' => $authenticatedUser->id,
            'has_image' => $request->hasFile('image'),
        ]);

        try {

        if($request->get('referralCode'))
        {
            $referral_exist = User::where('referral_code', $request->get('referralCode'))->first();
            if($referral_exist != null)
            {
                $user = User::find($request->id);

                // Security: IDOR protection - verify ownership when authenticated
                if (auth('sanctum')->check() && auth('sanctum')->id() != $request->id) {
                    \Log::warning('IDOR attempt on profile_update', [
                        'auth_user' => auth('sanctum')->id(),
                        'target_user' => $request->id,
                        'ip' => $request->ip(),
                    ]);
                    return response()->json(['status' => 'Error', 'message' => 'Unauthorized'], 403);
                }

                if(!empty($user))
                {
                    if($user->user_type != "Demo")
                    {
                        $validation = Validator::make($request->all(), [
                            'name' => 'required',
                            'email' => 'required|email|unique:users,email,' . \Request::get("id"),
                            'mobile_no' => 'nullable|numeric|unique:users,mobile_no,' . \Request::get("id"),
                            "image" => "nullable|image|mimes:jpg,png,jpeg,webp|max:2048",
                        ]);
                
                        if ($validation->fails()) {
                            $errors = [];
                            foreach ($validation->errors()->messages() as $key => $value) {
                                $errors[] = is_array($value) ? implode(',', $value) : $value;
                            }
                
                            return response()->json([
                                'status' => "Error",
                                'message' => implode("\n", $errors),
                            ], 200);
                        }
                            // Removed email_verified_at check to allow profile updates
                            $user_mobile = User::where("mobile_no",$request->get("mobile_no"))->first();
                            if($request->get("mobile_no") == null || empty($user_mobile) || $user_mobile->id == $user->id)
                            {
                                $user = User::find($request->id);
                                $user->name = $request->get("name");
                                $user->email = $request->get("email");
                                $user->country = $request->get("country");
                                $user->mobile_no = $request->get("mobile_no");
                                $user->save();

                                $this->syncProfileToAdLive($user);
                    
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
                                    if ($request->hasFile("image")) {
                                        if ($request->file('image')->isValid()) {
                                            $this->upload_image($request->file("image"),"image", $request->id);
                                        } else {
                                            return response()->json([
                                                'status' => "Error",
                                                'message' => "Profile image upload failed. It might be too large.",
                                            ], 200);
                                        }
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
                                    'isSubscribe' => ($user->is_subscribe && !empty($user->subscription_end_date))?(date("Y-m-d", strtotime($user->subscription_end_date)) >= date("Y-m-d",strtotime('today')))?true:false:false,
                                    'userType' => $user->login_type,
            'isPartner' => ($user->is_partner == 1) ? true : false, 
                                    'businessLimit' => (!empty($user->subscription_end_date) && date("Y-m-d", strtotime($user->subscription_end_date)) >= date("Y-m-d",strtotime('today')))?$user->business_limit:1,   
                                    'profileImage' => ($user->image)?(substr($user->image, 0, 4)=="http")?$user->image:((StorageSetting::getStorageSetting('storage') == 'DigitalOcean')?Storage::disk('spaces')->url('uploads/'.$user->image):asset('uploads/'.$user->image)):"",
                                                    'createdAt' => date('Y-m-d H:i:s', strtotime($user->created_at)),
            'adConfig' => $user->getAdConfigPayload(),
            'currentStreak' => $user->current_streak ?? 1,
            'maxStreak' => $user->max_streak ?? 1
                                );
                            }
                            else
                            {
                                return response()->json([
                                    'status' => "Error",
                                    'message' => "Mobile No Already Register!",
                                ], 200);
                            }
                    }
                    else
                    {
                        return response()->json([
                            'status' => "Error",
                            'message' => "This Function not work for Demo User!",
                        ], 200);
                    }
                }
                else 
                {
                    return response()->json([
                        'status' => "Error",
                        'message' => "Invalid userId",
                    ], 200);
                }
            }
            else
            {
                return response()->json([
                    'status' => "Error",
                    'message' => "Invalid Referral Code",
                ], 200);
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
                    "image" => "nullable|image|mimes:jpg,png,jpeg,webp|max:2048",
                ]);
        
                if ($validation->fails()) {
                    $errors = [];
                    foreach ($validation->errors()->messages() as $key => $value) {
                        $errors[] = is_array($value) ? implode(',', $value) : $value;
                    }
        
                    return response()->json([
                        'status' => "Error",
                        'message' => implode("\n", $errors),
                    ], 200);
                }
                else
                {
                            // Removed email_verified_at check to allow profile updates
                            $user_mobile = User::where("mobile_no",$request->get("mobile_no"))->first();
                            if($request->get("mobile_no") == null || empty($user_mobile) || $user_mobile->id == $user->id)
                            {
                                $user = User::find($request->id);
                                $user->name = $request->get("name");
                                $user->email = $request->get("email");
                                $user->country = $request->get("country");
                                $user->mobile_no = $request->get("mobile_no");
                                $user->save();

                                $this->syncProfileToAdLive($user);
                    
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
                                    if ($request->hasFile("image")) {
                                        if ($request->file('image')->isValid()) {
                                            $this->upload_image($request->file("image"),"image", $request->id);
                                        } else {
                                            return response()->json([
                                                'status' => "Error",
                                                'message' => "Profile image upload failed. It might be too large.",
                                            ], 200);
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
                                    'isSubscribe' => ($user->is_subscribe && !empty($user->subscription_end_date))?(date("Y-m-d", strtotime($user->subscription_end_date)) >= date("Y-m-d",strtotime('today')))?true:false:false,
                                    'userType' => $user->login_type,
            'isPartner' => ($user->is_partner == 1) ? true : false, 
                                    'businessLimit' => (!empty($user->subscription_end_date) && date("Y-m-d", strtotime($user->subscription_end_date)) >= date("Y-m-d",strtotime('today')))?$user->business_limit:1,   
                                    'profileImage' => ($user->image)?(substr($user->image, 0, 4)=="http")?$user->image:((StorageSetting::getStorageSetting('storage') == 'DigitalOcean')?Storage::disk('spaces')->url('uploads/'.$user->image):asset('uploads/'.$user->image)):"",
                                                    'createdAt' => date('Y-m-d H:i:s', strtotime($user->created_at)),
            'adConfig' => $user->getAdConfigPayload(),
            'currentStreak' => $user->current_streak ?? 1,
            'maxStreak' => $user->max_streak ?? 1
                                );
                            }
                            else
                            {
                                return response()->json([
                                    'status' => "Error",
                                    'message' => "Mobile No Already Register!",
                                ], 200);
                            }
                }
            }
            else 
            {
                return response()->json([
                    'status' => "Error",
                    'message' => "Invalid userId",
                ], 200);
            }
        }

        return response()->json([
            'status' => 'Success',
            'message' => 'Profile Updated Successfully',
            'data' => $data
        ], 200);

        } catch (\Exception $e) {
            \Log::error('=== PROFILE_UPDATE ERROR ===', [
                'message' => $e->getMessage(),
                'file' => $e->getFile(),
                'line' => $e->getLine(),
                'trace' => $e->getTraceAsString(),
            ]);
            return response()->json([
                'status' => 'Error',
                'message' => 'Unable to update the profile. Please try again.',
            ], 500);
        }
    }

    /**
     * Keep AdLive's read-only identity view aligned with the Artera-owned
     * account after a Pixel client changes profile data. No password or token
     * is included in this request.
     */
    private function syncProfileToAdLive(User $user): void
    {
        app(AdLiveIdentitySyncService::class)->queueForUser($user->fresh(), 'identity.updated');
    }

    private function upload_image($file,$field,$id)
    {
        $destinationPath = public_path('uploads');
        if (!file_exists($destinationPath)) {
            mkdir($destinationPath, 0755, true);
        }
        $extension = $file->getClientOriginalExtension();
        $fileName = Str::uuid() . '.' . $extension;
        $file->move($destinationPath, $fileName);
        
        $image = User::find($id);
        $image->$field = $fileName;
        $image->save();
    }

    public function forgot_password(Request $request)
    {
        $this->validateEmail($request);
        $email = mb_strtolower(trim($request->email));
        $user = User::where('email', $email)->first();

        // Keep the response identical whether an account exists or not, so
        // this endpoint cannot be used to enumerate registered email IDs.
        if ($user) {
            $code = random_int(100000, 999999);
            PasswordReset::where('email', $email)->delete();
            PasswordReset::create([
                'email' => $email,
                'token' => Hash::make((string) $code),
                'created_at' => now(),
            ]);

            \App\Jobs\SendPasswordResetOtpJob::dispatch($email, $user->name, $code);
        }

        return response()->json([
            'status' => 'Success',
            'message' => 'If this email is registered, a password reset code has been sent.',
        ], Response::HTTP_OK);
    }

    public function verify_forgot_password_otp(Request $request)
    {
        $this->validate($request, [
            'email' => 'required|email',
            'otp' => 'required'
        ]);

        $reset = PasswordReset::where('email', $request->email)->first();

        if (!$reset || !Hash::check($request->otp, $reset->token)) {
            return response()->json([
                'status' => "Error",
                'message' => "Wrong Code",
            ], 400);
        }

        // Check if OTP expired (10 minutes)
        if (now()->diffInMinutes($reset->created_at) > 10) {
            $reset->delete();
            return response()->json([
                'status' => "Error",
                'message' => "OTP Expired. Please request a new one.",
            ], 400);
        }

        return response()->json([
            'status' => "Success",
            'message' => "OTP Verified",
        ], 200);
    }

    public function update_forgot_password(Request $request)
    {
        $validated = $request->validate([
            'email' => ['required', 'email'],
            'otp' => ['required', 'digits:6'],
            'new_password' => ['required', \Illuminate\Validation\Rules\Password::min(10)->mixedCase()->numbers()->symbols()],
        ]);

        $email = mb_strtolower(trim($validated['email']));
        $reset = PasswordReset::where('email', $email)->first();

        if (!$reset || now()->greaterThan($reset->created_at->copy()->addMinutes(10)) || !Hash::check($validated['otp'], $reset->token)) {
            if ($reset && now()->greaterThan($reset->created_at->copy()->addMinutes(10))) {
                $reset->delete();
            }

            return response()->json([
                'status' => 'Error',
                'message' => 'The reset code is invalid or has expired.',
            ], Response::HTTP_UNPROCESSABLE_ENTITY);
        }

        $user = User::where('email', $email)->first();
        if ($user) {
            if (! app(AdLiveSignedSecurityEventService::class)->revokeLinkedSessions($user, 'password_reset')) {
                return response()->json([
                    'status' => 'Error',
                    'message' => 'Your password was not changed because linked AdLive sessions could not be secured. Please try again.',
                ], Response::HTTP_SERVICE_UNAVAILABLE);
            }

            $user->password = Hash::make($validated['new_password']);
            $user->save();

            app(MobileAccessTokenService::class)->revokeAll($user);
            PasswordReset::where('email', $email)->delete();

            return response()->json([
                'status' => 'Success',
                'message' => 'Password updated successfully. Please sign in again.',
            ], Response::HTTP_OK);
        }

        return response()->json([
            'status' => 'Error',
            'message' => 'The reset code is invalid or has expired.',
        ], Response::HTTP_UNPROCESSABLE_ENTITY);
    }

    protected function validateEmail(Request $request)
    {
        $this->validate($request, ['email' => 'required|email']);
    }

    public function change_password(Request $request)
    {
        $user = auth('sanctum')->user();
        if (!$user) {
            return response()->json(['status' => 'Error', 'message' => 'Authentication is required.'], Response::HTTP_UNAUTHORIZED);
        }

        $validated = $request->validate([
            'currentPassword' => ['required', 'string'],
            'newPassword' => ['required', \Illuminate\Validation\Rules\Password::min(10)->mixedCase()->numbers()->symbols()],
        ]);

        if (!Hash::check($validated['currentPassword'], $user->password)) {
            return response()->json([
                'status' => 'Error',
                'message' => 'Your current password is incorrect.',
            ], Response::HTTP_UNPROCESSABLE_ENTITY);
        }

        if (Hash::check($validated['newPassword'], $user->password)) {
            return response()->json([
                'status' => 'Error',
                'message' => 'Your new password must be different from your current password.',
            ], Response::HTTP_UNPROCESSABLE_ENTITY);
        }

        if (! app(AdLiveSignedSecurityEventService::class)->revokeLinkedSessions($user, 'password_changed')) {
            return response()->json([
                'status' => 'Error',
                'message' => 'Your password was not changed because linked AdLive sessions could not be secured. Please try again.',
            ], Response::HTTP_SERVICE_UNAVAILABLE);
        }

        $user->password = Hash::make($validated['newPassword']);
        $user->save();
        app(MobileAccessTokenService::class)->revokeAll($user);

        return response()->json([
            'status' => 'Success',
            'message' => 'Password updated successfully. Please sign in again.',
            'reauthentication_required' => true,
        ], Response::HTTP_OK);
    }

    public function register_fcm(Request $request)
    {
        $user = auth('sanctum')->user();
        if (!$user) {
            return response()->json(['status' => 'Error', 'message' => 'Authentication is required.'], Response::HTTP_UNAUTHORIZED);
        }

        $validation = Validator::make($request->all(), [
            'fcmToken' => 'required|string|max:4096',
            'deviceId' => 'required|string|max:255',
        ]);

        $errors = $validation->errors();
        if (count($errors) > 0) {
            $data['status'] = 401;
            $data['message'] = "Unable to update FCM ID and deviceId";
            $data['data'] = "";

        } else {
            // A push token is a device credential: associate it only with the
            // bearer-token owner, not a request-provided user ID.
            AndroidLogin::where('fcmToken', $request->get('fcmToken'))->delete();

            AndroidLogin::create([
                'userId' => $user->id,
                'fcmToken' => $request->get('fcmToken'),
                'deviceId' => $request->get('deviceId'),
            ]);

            $data['status'] = 0;
            $data['message'] = "FCM Token Register Successfully!";
            $data['data'] = "";

        }
        return $data;
    }

    public function logout(Request $request)
    {
        $user = auth('sanctum')->user();
        $token = $user ? $user->currentAccessToken() : null;
        if (!$user || !$token) {
            return response()->json(['status' => 'Error', 'message' => 'Authentication is required.'], Response::HTTP_UNAUTHORIZED);
        }

        $deviceId = $request->input('deviceId');
        if (is_string($deviceId) && $deviceId !== '') {
            AndroidLogin::where('userId', $user->id)->where('deviceId', $deviceId)->delete();
        }

        $token->delete();

        return response()->json([
            'status' => 0,
            'message' => 'User logout successfully.',
            'data' => '',
        ], Response::HTTP_OK);
    }

    public function delete_user_account(Request $request, AdLiveIdentityMutationService $identityMutations)
    {
        $user = auth('sanctum')->user();
        if (! $user) {
            return response()->json(['status' => 'Error', 'message' => 'Authentication is required.'], Response::HTTP_UNAUTHORIZED);
        }
        if ($request->filled('userId') && (int) $request->input('userId') !== (int) $user->id) {
            return response()->json(['status' => 'Error', 'message' => 'Unauthorized'], Response::HTTP_FORBIDDEN);
        }
        if ($request->input('confirmation') !== 'DELETE') {
            return response()->json([
                'status' => 'Error',
                'message' => 'Confirm account deletion by sending confirmation=DELETE.',
            ], Response::HTTP_UNPROCESSABLE_ENTITY);
        }

        try {
            $identityMutations->deactivate([
                'request_id' => (string) Str::uuid(),
                'occurred_at' => now()->utc()->toIso8601String(),
                'source' => 'artera_pixel',
                'artera_user_id' => $user->id,
            ]);

            return response()->json([
                'status' => 'success',
                'message' => 'User Account Deleted Successfully!',
            ]);
        } catch (\Illuminate\Http\Exceptions\HttpResponseException $exception) {
            return $exception->getResponse();
        } catch (\Throwable) {
            Log::error('Pixel self-service account deletion failed.');

            return response()->json(['status' => 'Error', 'message' => 'Server Error'], Response::HTTP_SERVICE_UNAVAILABLE);
        }
    }

    public function reportError(Request $request)
    {
        $user = auth('sanctum')->user();
        if (!$user) {
            return response()->json(['status' => 'error', 'message' => 'Authentication is required'], Response::HTTP_UNAUTHORIZED);
        }

        $validated = $request->validate([
            'error_code' => ['nullable', 'string', 'max:100'],
            'error_message' => ['required', 'string', 'max:4000'],
            'device_info' => ['nullable', 'string', 'max:1000'],
        ]);

        \App\Models\ClientError::create([
            'user_id' => $user->id,
            'error_code' => $validated['error_code'] ?? null,
            'error_message' => $validated['error_message'],
            'device_info' => $validated['device_info'] ?? mb_substr((string) $request->userAgent(), 0, 1000),
            'status' => 'Pending',
        ]);

        return response()->json([
            'status' => 'success',
            'message' => 'Error reported successfully'
        ], 200);
    }


    public function trackActivity(Request $request)
    {
        $user = auth('sanctum')->user();
        if (!$user) {
            return response()->json(['status' => 'error', 'message' => 'Authentication is required'], Response::HTTP_UNAUTHORIZED);
        }

        $validated = $request->validate([
            'action' => ['required', 'string', 'max:100'],
            'item_type' => ['nullable', 'string', 'max:100'],
            'item_id' => ['nullable', 'string', 'max:255'],
            'platform' => ['nullable', 'string', 'max:50'],
            'is_premium' => ['nullable', 'boolean'],
            'downloaded_image' => ['nullable', 'string', 'max:1500000'],
        ]);

        $userId = $user->id;
        $action = $validated['action'];
        $itemType = $validated['item_type'] ?? null;
        $itemId = $validated['item_id'] ?? null;
        $downloadedImageBase64 = $validated['downloaded_image'] ?? null;

        $downloadedImagePath = null;
        if ($downloadedImageBase64) {
            if (preg_match('#^data:image/(jpeg|png|webp);base64,#i', $downloadedImageBase64)) {
                try {
                    $imgData = explode(',', $downloadedImageBase64, 2);
                    if (count($imgData) === 2) {
                        $decodedData = base64_decode($imgData[1], true);
                        if ($decodedData !== false && strlen($decodedData) <= 1048576 && @getimagesizefromstring($decodedData)) {
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

        $isPremium = filter_var($validated['is_premium'] ?? false, FILTER_VALIDATE_BOOLEAN);

        \App\Models\UserActivity::create([
            'user_id' => $userId,
            'action' => $action,
            'payload' => [
                'item_type' => $itemType,
                'item_id' => $itemId,
                'platform' => $validated['platform'] ?? 'Mobile',
                'downloaded_image' => $downloadedImagePath,
                'is_premium' => $isPremium,
            ],
            'ip_address' => $request->ip(),
            'url' => $request->fullUrl(),
            'method' => $request->method(),
            'user_agent' => $request->header('User-Agent'),
        ]);

        $this->checkMilestoneBadges($userId);

        // Track Design Challenge progress
        \App\Http\Controllers\Api\DesignChallengeApiController::incrementProgress($userId, $action, $itemType, $itemId);

        // ── Consume subscription feature limit on download ──
        if ($action === 'download_template' && $itemType && $isPremium) {
            $user = User::find($userId);
            if ($user) {
                // Map the template item_type to the subscription feature key
                $featureMap = [
                    'festival'               => 'festival_post',
                    'category'               => 'category_post',
                    'custom'                 => 'custom_post',
                    'business_custom_frame'  => 'custom_post',
                    'business_frame'         => 'custom_post',
                    'business_custom'        => 'custom_post',
                ];

                $featureKey = $featureMap[$itemType] ?? null;

                if ($featureKey) {
                    $user->resetLimitsIfNeeded();
                    $plan = $user->active_subscription;
                    if (!$plan) {
                        $plan = \App\Models\Subscription::where('plan_price', 0)->first();
                    }

                    if ($plan) {
                        $limitMap = [
                            'festival_post' => ['used' => 'festival_post_used', 'base' => 'festival_post_limit'],
                            'category_post' => ['used' => 'category_post_used', 'base' => 'category_post_limit'],
                            'custom_post'   => ['used' => 'custom_post_used',   'base' => 'custom_post_edit_limit'],
                        ];

                        $baseLimit = $plan->{$limitMap[$featureKey]['base']} ?? 0;
                        $used      = $user->{$limitMap[$featureKey]['used']} ?? 0;

                        if ($baseLimit > 0 && $used < $baseLimit) {
                            // Within base limit → increment base usage
                            $user->increment($limitMap[$featureKey]['used']);
                            \Log::info("trackActivity: consumed base for $featureKey (used=$used, limit=$baseLimit)");
                        } elseif (($rewardUnlock = \Illuminate\Support\Facades\DB::table('reward_credit_unlocks')
                            ->where('user_id', $user->id)
                            ->where('feature_key', $featureKey)
                            ->whereNull('consumed_at')
                            ->where(function ($query) {
                                $query->whereNull('expires_at')->orWhere('expires_at', '>', now());
                            })
                            ->orderBy('id')
                            ->first()) && \Illuminate\Support\Facades\DB::table('reward_credit_unlocks')
                                ->where('id', $rewardUnlock->id)
                                ->whereNull('consumed_at')
                                ->update(['consumed_at' => now(), 'updated_at' => now()])) {
                            // A reward point is reserved on unlock and consumed
                            // only after this premium download succeeds.
                            \Log::info("trackActivity: consumed reward-credit unlock for $featureKey");
                        } elseif ($user->isAdRewardEnabledForFeature($featureKey)) {
                            // Base limit exhausted (or 0) → consume ad reward slot
                            $user->consumeAdReward($featureKey);
                            \Log::info("trackActivity: consumed ad reward for $featureKey");
                        } else {
                            \Log::info("trackActivity: feature $featureKey fully locked, no consume");
                        }
                    }
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
        $user = auth('sanctum')->user();
        if (!$user) {
            return response()->json(['status' => 'error', 'message' => 'Authentication is required'], Response::HTTP_UNAUTHORIZED);
        }

        $events = $request->validate(['events' => ['required', 'array', 'max:100']])['events'];

        if (empty($events)) {
            return response()->json(['status' => 'error', 'message' => 'No events provided'], 400);
        }

        $validTypes = ['banner', 'interstitial', 'rewarded'];
        $validEvents = ['impression', 'click', 'completed'];

        $rows = [];
        foreach ($events as $evt) {
            if (!is_array($evt)) {
                continue;
            }

            $adType = $evt['ad_type'] ?? null;
            $event = $evt['event'] ?? 'impression';

            if (!$adType || !in_array($adType, $validTypes)) continue;
            if (!in_array($event, $validEvents)) $event = 'impression';

            $rows[] = [
                'user_id' => $user->id,
                'ad_type' => $adType,
                'event' => $event,
                'created_at' => now(),
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
        $startDate = '2026-06-01 00:00:00';

        $postCount = \App\Models\UserActivity::where('user_id', $userId)
            ->whereIn('action', ['download_template', 'create_custom_post', 'create_festival_post'])
            ->where('created_at', '>=', $startDate)
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

    private function updateUserStreak($user)
    {
        try {
            $today = now()->startOfDay();
            $lastLogin = $user->last_login_date ? \Carbon\Carbon::parse($user->last_login_date)->startOfDay() : null;

            if (!$lastLogin) {
                $user->current_streak = 1;
            } elseif ($lastLogin->equalTo($today->copy()->subDay())) {
                $user->current_streak += 1;
            } elseif ($lastLogin->lessThan($today->copy()->subDay())) {
                $user->current_streak = 1;
            }
            
            if ($user->current_streak > $user->max_streak) {
                $user->max_streak = $user->current_streak;
            }

            $user->last_login_date = now()->toDateString();
            $user->save();
        } catch (\Exception $e) {
            \Illuminate\Support\Facades\Log::error('Streak update failed: ' . $e->getMessage());
        }
    }

    public function useRewardCredit(Request $request)
    {
        $user = auth('sanctum')->user();
        if (!$user) {
            return response()->json(['status' => 'error', 'message' => 'Authentication is required.'], Response::HTTP_UNAUTHORIZED);
        }

        $featureKey = trim((string) $request->get('feature_key'));

        if ($featureKey === '' || mb_strlen($featureKey) > 100) {
            return response()->json(['status' => 'error', 'message' => 'A valid feature key is required.'], Response::HTTP_UNPROCESSABLE_ENTITY);
        }

        $remainingRewardPoints = \Illuminate\Support\Facades\DB::transaction(function () use ($user, $featureKey) {
            // Guard the decrement in SQL so two taps cannot spend one credit twice.
            $decremented = User::whereKey($user->id)
                ->where('reward_points', '>', 0)
                ->decrement('reward_points');

            if ($decremented !== 1) {
                return null;
            }

            // Persist the bypass in SQL. Cache is not a source of entitlement and
            // is not read by the download-consumption path.
            \Illuminate\Support\Facades\DB::table('reward_credit_unlocks')->insert([
                'user_id' => $user->id,
                'feature_key' => $featureKey,
                'expires_at' => now()->endOfMonth(),
                'created_at' => now(),
                'updated_at' => now(),
            ]);

            return (int) User::whereKey($user->id)->value('reward_points');
        });

        if ($remainingRewardPoints === null) {
            return response()->json(['status' => 'error', 'message' => 'Insufficient reward credits'], 400);
        }

        return response()->json([
            'status' => 'success',
            'message' => 'Reward credit reserved for your next premium download',
            'rewardPoints' => $remainingRewardPoints
        ]);
    }

    /**
     * Generate a signed webview-login URL for the mobile app.
     * The mobile app calls this endpoint with userId,
     * and receives a time-limited signed URL to open in WebView.
     */
    public function generateWebviewUrl(Request $request)
    {
        $user = auth('sanctum')->user();
        if (!$user) {
            return response()->json([
                'status' => 'Error',
                'message' => 'Authentication is required.',
            ], Response::HTTP_UNAUTHORIZED);
        }

        // Generate a signed URL that expires in 5 minutes
        $redirectPath = (string) $request->get('redirect', '/dashboard');
        $parsedRedirect = parse_url($redirectPath);
        $path = $parsedRedirect['path'] ?? '';
        if (
            isset($parsedRedirect['scheme']) ||
            isset($parsedRedirect['host']) ||
            !str_starts_with($path, '/') ||
            str_starts_with($redirectPath, '//')
        ) {
            $redirectPath = '/dashboard';
        }
        
        $signedUrl = \Illuminate\Support\Facades\URL::temporarySignedRoute(
            'webview.login',
            now()->addMinutes(5),
            [
                'user_id' => $user->id,
                'redirect' => $redirectPath,
            ]
        );

        return response()->json([
            'status' => 'Success',
            'url' => $signedUrl,
        ]);
    }
}

