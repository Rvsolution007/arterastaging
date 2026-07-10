<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\Category;
use App\Models\Festivals;
use App\Models\Business;
use App\Models\AppSetting;
use App\Models\UserNotification;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Str;
use Illuminate\Support\Facades\Validator;

class MainController extends Controller
{
    private function getCommonData()
    {
        $business = Business::where('user_id', Auth::id())->where('is_default', 1)->first();
        if (!$business) {
            $business = Business::where('user_id', Auth::id())->first();
        }
        $app_setting = AppSetting::pluck('key_value', 'key_name')->toArray();
        $user = Auth::user();
        $notification_count = UserNotification::query();
        if ($user && $user->last_notification_read_at) {
            $notification_count->where('created_at', '>', $user->last_notification_read_at);
        }
        $notification_count = $notification_count->count();
        return compact('business', 'app_setting', 'notification_count');
    }

    public function index()
    {
        $data = $this->getCommonData();
        $categories = Category::where('status', 1)->get();
        $festivals = Festivals::where('status', 1)
            ->whereDate('festivals_date', '>=', date('Y-m-d'))
            ->orderBy('festivals_date', 'asc')
            ->take(20)
            ->get();
        $custom_posts = \App\Models\CustomPost::where('status', 1)->get();
        $videos = \App\Models\Video::where('status', 1)->get();
        
        $business_categories = \App\Models\CustomFramePurpose::where('status', 1)->get();
        $poster_categories = \App\Models\PosterCategory::where('status', 1)->get();
        $news = \App\Models\News::orderBy('id', 'desc')->get();

        // Fetch active stories (unexpired and sequenced)
        $stories = \App\Models\Story::where('status', 1)
            ->where(function($q) {
                $q->whereNull('expires_at')
                  ->orWhere('expires_at', '>', now());
            })
            ->orderBy('sort_order', 'asc')
            ->orderBy('id', 'desc')
            ->get();

        // Load custom frame previews (same as custom page)
        $frameData = $this->getMyCustomFrames($data);
        $my_custom_frames = $frameData['my_custom_frames'];
        $my_custom_frames_raw = $frameData['my_custom_frames_raw'];

        return view('client.index', array_merge($data, compact('categories', 'festivals', 'custom_posts', 'videos', 'stories', 'business_categories', 'poster_categories', 'news', 'my_custom_frames', 'my_custom_frames_raw')));
    }

    public function custom()
    {
        $data = $this->getCommonData();
        $custom_posts = \App\Models\CustomPost::where('status', 1)->get();
        
        $frameData = $this->getMyCustomFrames($data);
        $my_custom_frames = $frameData['my_custom_frames'];
        $my_custom_frames_raw = $frameData['my_custom_frames_raw'];

        return view('client.custom', array_merge($data, compact('custom_posts', 'my_custom_frames', 'my_custom_frames_raw')));
    }

    /**
     * Shared method to load custom frame previews for both homepage and custom page.
     */
    private function getMyCustomFrames($data)
    {
        $my_custom_frames = collect();
        $my_custom_frames_raw = collect();

        if (isset($data['business']) && !empty($data['business']->business_sub_category_ids)) {
            $rawIds = $data['business']->business_sub_category_ids;
            $subCategoryIds = json_decode($rawIds, true);
            
            // Handle double-encoded JSON like '"[\"5\"]"'
            if (is_string($subCategoryIds)) {
                $decodedAgain = json_decode($subCategoryIds, true);
                if (is_array($decodedAgain)) {
                    $subCategoryIds = $decodedAgain;
                }
            }
            
            if (!is_array($subCategoryIds)) {
                $cleanString = str_replace(['"', '[', ']', '\\'], '', $rawIds);
                $subCategoryIds = array_map('trim', explode(',', $cleanString));
            }
            $subCategoryIds = array_filter(array_map('intval', $subCategoryIds));
            $imageTypeIds = \DB::table('image_type_sub_category')
                               ->whereIn('business_sub_category_id', $subCategoryIds)
                               ->pluck('custom_frame_image_type_id');

            if ($imageTypeIds->count() > 0) {
                $business_custom_frames = \App\Models\BusinessCustomFrame::whereIn('custom_frame_image_type_id', $imageTypeIds)
                                            ->where('status', 1)
                                            ->get();

                // Fetch the default frame overlay (PosterMaker) for accurate thumbnail preview
                $defaultFrameConfig = null;
                $poster_frames = \App\Models\PosterMaker::orderBy('id', 'desc')->get();
                foreach ($poster_frames as $pf) {
                    $pmZipName = $pf->zip_name ?? '';
                    if (!$pmZipName) continue;
                    
                    $pmSkinsDir = base_path('uploads/template/' . $pmZipName . '/skins');
                    $pmSkinFolder = '';
                    if (is_dir($pmSkinsDir)) {
                        $dirs = array_filter(glob($pmSkinsDir . '/*'), 'is_dir');
                        if (!empty($dirs)) {
                            $pmSkinFolder = basename(reset($dirs));
                        }
                    }
                    
                    $pmJsonDir = base_path('uploads/template/' . $pmZipName . '/json');
                    $pmConfig = null;
                    if (is_dir($pmJsonDir)) {
                        $jsonFiles = glob($pmJsonDir . '/*.json');
                        if (!empty($jsonFiles)) {
                            $pmConfig = json_decode(file_get_contents($jsonFiles[0]), true);
                        }
                    }
                    
                    if ($pmConfig && isset($pmConfig['layers'])) {
                        $defaultFrameConfig = [
                            'config' => $pmConfig,
                            'zip_name' => $pmZipName,
                            'skin_folder' => $pmSkinFolder,
                        ];
                        break;
                    }
                }

                // Store raw frame data with pre-loaded AI content + product images
                $userId = \Auth::id();
                $my_custom_frames_raw = $business_custom_frames->map(function ($f) use ($defaultFrameConfig, $userId) {
                    $zipName = pathinfo($f->zip_file_path, PATHINFO_FILENAME);
                    $skinFolder = $zipName;
                    $skinsPath = public_path('uploads/template/' . $zipName . '/skins');
                    if (file_exists($skinsPath) && is_dir($skinsPath)) {
                        $dirs = array_filter(glob($skinsPath . '/*'), 'is_dir');
                        if (count($dirs) > 0) {
                            $skinFolder = basename(reset($dirs));
                        }
                    }

                    $cachedContent = null;
                    $productImages = [];
                    if ($userId) {
                        $cached = \App\Models\UserCustomFrameContent::where('user_id', $userId)
                            ->where('business_custom_frame_id', $f->id)->first();
                        if ($cached && !empty($cached->generated_content)) {
                            $cachedContent = $cached->generated_content;
                            if ($cached->product_id) {
                                $product = \App\Models\Product::find($cached->product_id);
                                if ($product && $product->image) {
                                    $productImages['image1'] = 'uploads/' . $product->image;
                                }
                            }
                        }
                    }
                    
                    return [
                        'db_id' => $f->id,
                        'json_rules' => $f->json_rules ? json_decode($f->json_rules, true) : null,
                        'zip_name' => $zipName,
                        'skin_folder' => $skinFolder,
                        'default_frame' => $defaultFrameConfig,
                        'cached_content' => $cachedContent,
                        'product_images' => $productImages,
                    ];
                })->filter(fn($f) => !empty($f['json_rules']));

                $business_custom_frames->transform(function ($frame) {
                    $frame->zip_name = pathinfo($frame->zip_file_path, PATHINFO_FILENAME);
                    $extractPath = public_path('uploads/template/' . $frame->zip_name);
                    if(!is_dir($extractPath)) {
                        $zip = new \ZipArchive;
                        $zipRes = (\App\Models\StorageSetting::getStorageSetting("storage") == "DigitalOcean") ? 
                                  $zip->open(\Illuminate\Support\Facades\Storage::disk('spaces')->url('uploads/custom_frames_zips/' . $frame->zip_file_path)) :
                                  $zip->open(public_path('uploads/custom_frames_zips/' . $frame->zip_file_path));
                        
                        if($zipRes === TRUE) {
                            $zip->extractTo($extractPath);
                            $zip->close();
                        }
                    }
                    return $frame;
                });

                $my_custom_frames = $this->extractFramesFromTemplates($business_custom_frames, true);
            }
        }

        return compact('my_custom_frames', 'my_custom_frames_raw');
    }

    public function business()
    {
        $data = $this->getCommonData();
        return view('client.business', $data);
    }

    public function notifications()
    {
        $data = $this->getCommonData();
        $data['notifications'] = UserNotification::orderBy('created_at', 'desc')->get();

        // Update user's last read timestamp
        $user = Auth::user();
        if ($user) {
            $user->last_notification_read_at = now();
            $user->save();
        }

        return view('client.notifications', $data);
    }

    public function support()
    {
        $data = $this->getCommonData();
        return view('client.support', $data);
    }

    public function faqs()
    {
        $data = $this->getCommonData();
        return view('client.faqs', $data);
    }

    public function aitrends()
    {
        $data = $this->getCommonData();
        $custom_posts = \App\Models\CustomPost::where('status', 1)->get();
        $videos = \App\Models\Video::where('status', 1)->get();
        return view('client.aitrends', array_merge($data, compact('custom_posts', 'videos')));
    }

    public function updatePreferredLanguages(Request $request)
    {
        $user = Auth::user();
        if (!$user) {
            return response()->json(['success' => false, 'message' => 'Unauthorized'], 401);
        }

        $languages = $request->input('languages', []);
        $user->preferred_languages = implode(', ', $languages);
        $user->save();

        return response()->json(['success' => true, 'preferred_languages' => $user->preferred_languages]);
    }

    public function more()
    {
        $data = $this->getCommonData();
        return view('client.more', $data);
    }

    public function festival_details($id)
    {
        $data = $this->getCommonData();
        $festival = Festivals::findOrFail($id);
        $languages = \App\Models\Language::all();
        $frames = \App\Models\FestivalsPost::where('festivals_id', $id)
            ->where('status', 1)
            ->orderBy('id', 'desc')
            ->get();
        $videos = \App\Models\Video::where('status', 1)
            ->where('type', 'festival')
            ->where('festival_id', $id)
            ->get();
        return view('client.festival_details', array_merge($data, compact('festival', 'languages', 'frames', 'videos')));
    }

    public function festival_edit($id)
    {
        return redirect()->route('universal.edit', ['type' => 'festival', 'id' => $id]);
    }

    public function business_edit()
    {
        $data = $this->getCommonData();
        $categories = \App\Models\BusinessCategory::where('status', 1)->get();
        return view('client.business_edit', array_merge($data, compact('categories')));
    }

    public function business_update(Request $request)
    {
        $business = Business::where('user_id', Auth::id())->where('is_default', 1)->first();
        if (!$business) {
            $business = Business::where('user_id', Auth::id())->first();
        }

        $validator = Validator::make($request->all(), [
            'name' => 'required|string|max:255',
            'email' => 'required|email|unique:business,email,' . $business->id,
            'mobile_no' => 'required|string',
            'business_category_id' => 'required|exists:business_category,id',
            'business_sub_category_ids' => 'nullable|array',
            'logo' => 'nullable|image|mimes:jpeg,png,jpg,webp|max:2048',
        ]);

        if ($validator->fails()) {
            return back()->withErrors($validator)->withInput();
        }

        // Handle Logo Upload
        if ($request->hasFile('logo')) {
            $file = $request->file('logo');
            $logoName = Str::uuid() . '.' . $file->getClientOriginalExtension();
            $file->move(public_path('uploads'), $logoName);
            @copy(public_path('uploads/' . $logoName), base_path('uploads/' . $logoName));

            // Sync with user logo
            $user = Auth::user();
            $user->image = $logoName;
            $user->save();

            $business->logo = $logoName;
        }

        $business->name = $request->name;
        $business->email = $request->email;
        $business->website = $request->website;
        $business->mobile_no = $request->mobile_no;
        $business->address = $request->address;
        $business->business_category_id = $request->business_category_id;
        $business->business_sub_category_ids = $request->business_sub_category_ids ? json_encode($request->business_sub_category_ids) : null;
        if ($request->has('hidden_frame_fields')) {
            $business->hidden_frame_fields = is_string($request->hidden_frame_fields) ? json_decode($request->hidden_frame_fields, true) : $request->hidden_frame_fields;
        }
        $business->save();

        $user = Auth::user();
        if ($user) {
            $updateUserData = ['name' => $request->name];
            if (isset($business->logo)) {
                $updateUserData['image'] = $business->logo;
            }
            $user->update($updateUserData);
        }

        return redirect()->route('business')->with('success', 'Business details updated successfully!');
    }

    public function getFestivalsByDate(Request $request)
    {
        $date = $request->input('date');
        
        if (!$date) {
            $festivals = Festivals::where('status', 1)
                ->whereDate('festivals_date', '>=', date('Y-m-d'))
                ->orderBy('festivals_date', 'asc')
                ->take(20)
                ->get();
        } else {
            $festivals = Festivals::where('status', 1)
                ->whereDate('festivals_date', $date)
                ->get();
        }

        $html = '';
        foreach ($festivals as $festival) {
            $url = route('universal.details', ['type' => 'festival', 'id' => $festival->id]);
            $img = asset('uploads/' . $festival->image);
            $dayNum = \Carbon\Carbon::parse($festival->festivals_date)->format('d');

            $html .= '<a href="' . $url . '" class="min-w-[155px] aspect-[4/5] rounded-[2rem] overflow-hidden relative shadow-sm cursor-pointer active:scale-95 transition-all">';
            $html .= '<img src="' . $img . '" class="absolute inset-0 w-full h-full object-cover">';
            $html .= '<div class="absolute bottom-4 left-4 bg-black/20 backdrop-blur-[2px] text-white text-[15px] font-black px-2 rounded-lg">' . $dayNum . '</div>';
            $html .= '</a>';
        }

        if (count($festivals) == 0) {
            $html = '<div class="w-full text-center py-8 text-gray-400 font-medium">No festivals found for this date</div>';
        }

        return response()->json(['success' => true, 'html' => $html]);
    }

    public function universal_details($type, $id)
    {
        $data = $this->getCommonData();
        $item = null;
        $frames = [];
        $languages = \App\Models\Language::all();

        // Filter videos by the specific post type and ID
        $videosQuery = \App\Models\Video::where('status', 1);
        if ($type == 'festival') {
            $videosQuery->where('type', 'festival')->where('festival_id', $id);
        } elseif ($type == 'category') {
            $videosQuery->where('type', 'category')->where('category_id', $id);
        } elseif ($type == 'custom') {
            $videosQuery->where('type', 'businessCategory')->where('business_category_id', $id);
        } else {
            $videosQuery->whereRaw('1 = 0'); // No videos for other types
        }
        $videos = $videosQuery->get();

        if ($type == 'festival') {
            $item = Festivals::findOrFail($id);
            $item->display_name = $item->title;
            $item->display_image = $item->image;
            $frames = \App\Models\FestivalsPost::where('festivals_id', $id)->where('status', 1)->orderBy('id', 'desc')->get();
        } elseif ($type == 'category') {
            $item = Category::findOrFail($id);
            $item->display_name = $item->name;
            $item->display_image = $item->icon;
            $frames = \App\Models\CategoryPost::where('category_id', $id)->where('status', 1)->orderBy('id', 'desc')->get();
        } elseif ($type == 'custom') {
            $item = \App\Models\CustomPost::findOrFail($id);
            $item->display_name = $item->name;
            $item->display_image = $item->icon;
            $frames = \App\Models\CustomPostFrame::where('custom_post_id', $id)->where('status', 1)->orderBy('id', 'desc')->get();
        } elseif ($type == 'post') {
            $item = \App\Models\GeneralPost::find($id);
            if (!$item) {
                $item = \App\Models\PosterMaker::findOrFail($id);
            }
            $item->display_name = "New Post";
            $item->display_image = $item->frame_image ?? $item->post_thumb;
            $frames = collect([$item]);
        }

        // For AI posts (GeneralPost with ai_generated_content), resolve the template config for the preview
        $templateConfig = null;
        if ($type == 'post' && $item instanceof \App\Models\GeneralPost && $item->ai_generated_content) {
            $templateConfig = $item->getTemplateConfig();
        }

        return view('client.universal_details', array_merge($data, compact('item', 'frames', 'languages', 'videos', 'type', 'id', 'templateConfig')));
    }

    public function extractFramesFromTemplates($templates, $isFrameOverlay = false)
    {
        $frames_list = collect();
        foreach ($templates as $t) {
            // GeneralPost stores full file name (e.g., '123.zip') but PosterMaker stores just the folder name (e.g., 'POST123').
            $baseExtractLocation = $t->zip_name;
            if (Str::endsWith(strtolower($baseExtractLocation), '.zip')) {
                $baseExtractLocation = pathinfo($baseExtractLocation, PATHINFO_FILENAME);
            }
            // Some zip names might still retain .zip or not be direct folders if extracted by new code.
            // Let's standardise the path it should look at based on the current system behavior 
            // where uploads/template/XYZ is the folder.

            $zipPath = 'uploads/template/' . $t->zip_name . '/skins';
            
            // Check if it exists under the raw zip_name, else try without .zip
            if (!is_dir(base_path($zipPath))) {
                $zipPath = 'uploads/template/' . $baseExtractLocation . '/skins';
            }
            
            // If neither has skins directly, it's probably nested inside a root folder (e.g. Frame2026...)
            if (!is_dir(base_path($zipPath))) {
                $possibleRoots = glob(base_path('uploads/template/' . $t->zip_name . '/*'), GLOB_ONLYDIR);
                if (empty($possibleRoots)) {
                    $possibleRoots = glob(base_path('uploads/template/' . $baseExtractLocation . '/*'), GLOB_ONLYDIR);
                }
                
                foreach ($possibleRoots as $pr) {
                    if (is_dir($pr . '/skins')) {
                        $zipPath = 'uploads/template/' . basename(dirname($pr)) . '/' . basename($pr) . '/skins';
                        $baseExtractLocation = basename(dirname($pr)) . '/' . basename($pr); // Update base for JSON path fallback
                        break;
                    }
                }
            }

            $fullPath = base_path($zipPath);

            if (is_dir($fullPath)) {
                $skinFolders = array_filter(glob($fullPath . '/*'), 'is_dir');
                foreach ($skinFolders as $folderPath) {
                    $folderName = basename($folderPath);
                    $framePngLocal = $folderPath . DIRECTORY_SEPARATOR . 'frame.png';
                    
                    // JSON path
                    $jsonDir = base_path(str_replace('/skins', '/json', $zipPath));
                    $jsonPath = $jsonDir . '/' . $folderName . '.json';
                    
                    if (!file_exists($jsonPath) && is_dir($jsonDir)) {
                        $jsonFiles = glob($jsonDir . '/*.json');
                        if (!empty($jsonFiles)) {
                            $jsonPath = $jsonFiles[0];
                        }
                    }

                    $hasFramePng = file_exists($framePngLocal);
                    $hasJson = file_exists($jsonPath);

                    \Illuminate\Support\Facades\Log::info("Extract Debug:", [
                        'folderName' => $folderName,
                        'jsonDir' => $jsonDir,
                        'jsonPath' => $jsonPath,
                        'hasJson' => $hasJson,
                        'hasFramePng' => $hasFramePng
                    ]);

                    if ($hasFramePng || $hasJson) {
                        $config = null;
                        if ($hasJson) {
                            $config = json_decode(file_get_contents($jsonPath));
                        }

                        $thumb = '';
                        if (!$isFrameOverlay && isset($t->post_thumb)) {
                            $thumb = $t->post_thumb;
                        } elseif (isset($t->frame_image)) {
                            $thumb = $t->frame_image;
                        }
                        
                        // SMART THUMBNAIL LOGIC
                        $frameFileName = 'frame.png'; // Default
                        if (!$hasFramePng && is_dir($folderPath) && empty($thumb)) {
                            // 1. Try to find it in JSON first
                            if ($config && isset($config->layers)) {
                                foreach ($config->layers as $layer) {
                                    if (
                                        $layer->type === 'image' &&
                                        !str_contains($layer->src, 'bg.png') &&
                                        !str_contains($layer->src, 'logo.png') &&
                                        (!isset($layer->name) || !str_contains($layer->name, 'bg')) &&
                                        (!isset($layer->name) || !str_contains($layer->name, 'logo'))
                                    ) {
                                        $fname = basename($layer->src);
                                        if (file_exists($folderPath . DIRECTORY_SEPARATOR . $fname)) {
                                            $frameFileName = $fname;
                                            $hasFramePng = true;
                                            break;
                                        }
                                    }
                                }
                            }

                            // 2. If still not found, scan directory for candidates
                            if (!$hasFramePng) {
                                $files = glob($folderPath . '/*.png');
                                foreach ($files as $file) {
                                    $bname = basename($file);
                                    if (
                                        $bname !== 'bg.png' &&
                                        $bname !== 'logo.png' &&
                                        !str_starts_with($bname, 'ic_') &&
                                        !str_starts_with($bname, 'shape')
                                    ) {
                                        $frameFileName = $bname;
                                        $hasFramePng = true;
                                        break;
                                    }
                                }
                            }
                        }

                        $logicUrl = asset($zipPath . '/' . $folderName . '/' . $frameFileName);
                        
                        // Universal thumb handling
                        $thumbUrl = $hasFramePng ? $logicUrl : asset('uploads/' . $thumb);

                        $typePrefix = strtolower(class_basename($t));

                        // Detect image type (full vs transparent) from DB relationship or JSON config
                        $imageTypeName = 'full'; // default
                        if (isset($t->custom_frame_image_type_id) && $t->custom_frame_image_type_id) {
                            $imgType = \App\Models\CustomFrameImageType::find($t->custom_frame_image_type_id);
                            if ($imgType) {
                                $imageTypeName = strtolower($imgType->name);
                            }
                        } elseif ($config && isset($config->image)) {
                            $imageTypeName = strtolower($config->image);
                        }

                        $frames_list->push((object) [
                            'id' => $typePrefix . '_' . $t->id . '_' . $folderName,
                            'db_id' => $t->id,
                            'full_url' => $logicUrl,
                            'thumbnail_url' => $thumbUrl,
                            'language_id' => 'all',
                            'category_id' => $t->poster_category_id ?? 'all',
                            'theme' => $t->theme ?? 'all',
                            'req_address' => $t->req_address ?? 0,
                            'req_email' => $t->req_email ?? 0,
                            'req_phone' => $t->req_phone ?? 0,
                            'req_website' => $t->req_website ?? 0,
                            'config' => $config,
                            'image_type' => $imageTypeName,
                        ]);
                    }
                }
            }
        }
        return $frames_list;
    }

    public function injectDynamicBackgroundImage($config, $businessCategoryId)
    {
        \Illuminate\Support\Facades\Log::info("DEBUG WEB injectBG: Started. BusinessCategoryId=$businessCategoryId");
        if (!$config || !isset($config->layers) || !$businessCategoryId) {
            \Illuminate\Support\Facades\Log::info("DEBUG WEB injectBG: Aborting (missing config, layers, or categoryId).");
            return $config;
        }

        foreach ($config->layers as $layer) {
            $isBg = false;
            if ($layer->type === 'image') {
                if (isset($layer->is_background) && $layer->is_background == true) {
                    $isBg = true;
                } elseif (isset($layer->name)) {
                    $name = strtolower($layer->name);
                    if (str_contains($name, 'background') || str_contains($name, 'bg')) {
                        $isBg = true;
                    }
                }
            }

            if ($isBg && isset($layer->w) && isset($layer->h) && $layer->h > 0) {
                \Illuminate\Support\Facades\Log::info("DEBUG WEB injectBG: Found BG layer.");
                $ratio = $layer->w / $layer->h;
                $aspectRatioEnum = null;
                
                if (abs($ratio - 1) <= 0.25) {
                    $aspectRatioEnum = '1:1';
                } elseif ($ratio >= 1.25) {
                    $aspectRatioEnum = '16:9';
                } elseif ($ratio <= 0.75) {
                    $aspectRatioEnum = '9:16';
                } else {
                    $aspectRatioEnum = '1:1'; // Fallback
                }

                \Illuminate\Support\Facades\Log::info("DEBUG WEB injectBG: Layer dimensions {$layer->w}x{$layer->h}, ratio $ratio, mapped to aspect enum: $aspectRatioEnum");

                if ($aspectRatioEnum) {
                    $randomBg = \App\Models\CategoryBackgroundImage::where('business_category_id', $businessCategoryId)
                                    ->where('aspect_ratio', $aspectRatioEnum)
                                    ->inRandomOrder()
                                    ->first();
                    if ($randomBg) {
                        $layer->src = url($randomBg->image);
                        \Illuminate\Support\Facades\Log::info("DEBUG WEB injectBG: Successfully replaced BG with image ID {$randomBg->id}. URL generated: {$layer->src}");
                    } else {
                        \Illuminate\Support\Facades\Log::info("DEBUG WEB injectBG: No random background found in DB for category $businessCategoryId and ratio $aspectRatioEnum");
                    }
                }
            }
        }

        return $config;
    }

    public function universal_edit($type, $id)
    {
        $data = $this->getCommonData();
        $item = null;
        $languages = \App\Models\Language::all();
        $item_frames = collect();

        if ($type == 'festival') {
            $item = Festivals::findOrFail($id);
            $item->display_name = $item->title;
            $item->display_image = $item->image;
            $item_frames = \App\Models\FestivalsPost::where('festivals_id', $id)->where('status', 1)->orderBy('id', 'desc')->get();
        } elseif ($type == 'category') {
            $item = Category::findOrFail($id);
            $item->display_name = $item->name;
            $item->display_image = $item->icon;
            $item_frames = \App\Models\CategoryPost::where('category_id', $id)->where('status', 1)->orderBy('id', 'desc')->get();
        } elseif ($type == 'custom') {
            $item = \App\Models\CustomPost::findOrFail($id);
            $item->display_name = $item->name;
            $item->display_image = $item->icon;
            $item_frames = \App\Models\CustomPostFrame::where('custom_post_id', $id)->where('status', 1)->orderBy('id', 'desc')->get();
        } elseif ($type == 'post') {
            $item = \App\Models\GeneralPost::with(['business_sub_category', 'product'])->find($id);
            if (!$item) {
                $item = \App\Models\PosterMaker::findOrFail($id);
            }
            $item->display_name = "New Post";
            $item->display_image = $item->frame_image ?? $item->post_thumb;
            $item_frames = collect([$item]); // Treat the post as its own frame container
        } elseif ($type == 'business_custom_frame') {
            $item = \App\Models\BusinessCustomFrame::findOrFail($id);
            $item->display_name = "Custom Post";
            $item->display_image = "";
            $item->zip_name = pathinfo($item->zip_file_path, PATHINFO_FILENAME);
            $item_frames = collect([$item]);
        }

        $user_id = \Auth::id();
        $favoriteFrames = [];
        if ($user_id) {
            $favoriteFrames = \App\Models\UserFavoriteFrame::where('user_id', $user_id)->pluck('frame_identifier')->toArray();
        }

        // 1. Extract frames from the SPECIFIC item frames (High Priority)
        $post_template = null;
        if ($type == 'business_custom_frame') {
            // The inner folder inside "skins" might not match the zip_name (UUID)
            $skinFolder = $item->zip_name;
            $skinsPath = public_path('uploads/template/' . $item->zip_name . '/skins');
            if (file_exists($skinsPath) && is_dir($skinsPath)) {
                $dirs = array_filter(glob($skinsPath . '/*'), 'is_dir');
                if (count($dirs) > 0) {
                    $skinFolder = basename(reset($dirs));
                }
            }

            $post_template = (object) [
                'id' => 'bcf_' . $item->id,
                'config' => json_decode($item->json_rules),
                'full_url' => asset('uploads/template/' . $item->zip_name . '/skins/' . $skinFolder . '/dummy.png')
            ];

            // Inject AI-generated content into template config layers
            $userId = auth()->id();
            if ($userId && $post_template->config && isset($post_template->config->layers)) {
                $aiContent = \App\Models\UserCustomFrameContent::where('user_id', $userId)
                    ->where('business_custom_frame_id', $item->id)
                    ->first();
                if ($aiContent && !empty($aiContent->generated_content)) {
                    foreach ($post_template->config->layers as &$layer) {
                        if (isset($layer->type) && $layer->type === 'text' && isset($layer->name)) {
                            if (isset($aiContent->generated_content[$layer->name])) {
                                $layer->text = $aiContent->generated_content[$layer->name];
                            }
                        }
                    }
                    unset($layer);
                    \Illuminate\Support\Facades\Log::info("DEBUG universal_edit: AI content injected for user $userId, frame $id");
                }
            }

            $businessCategoryId = isset($data['business']) && $data['business'] ? $data['business']->business_category_id : null;
            if ($post_template && $post_template->config) {
                $post_template->config = $this->injectDynamicBackgroundImage($post_template->config, $businessCategoryId);
            }

            $frames_list = collect();
        } else {
            // Try to extract ZIP-based templates first
            $frames_list = $this->extractFramesFromTemplates($item_frames, false);

            $businessCategoryId = isset($data['business']) && $data['business'] ? $data['business']->business_category_id : null;
            $frames_list->transform(function($frame) use ($businessCategoryId) {
                if ($frame->config) {
                    $frame->config = $this->injectDynamicBackgroundImage($frame->config, $businessCategoryId);
                }
                return $frame;
            });
            
            if ($type == 'custom') {
                $post_template = $frames_list->first();
                $frames_list = collect();
            }
        }

        // 1.5 Fetch PNG-based BusinessFrames bound to the user's business sub categories
        // We ALLOW this for business_custom_frame so users can add their standard contact info borders.
        if (isset($data['business']) && !empty($data['business']->business_sub_category_ids)) {
            $subCategoryIds = json_decode($data['business']->business_sub_category_ids, true);
            if (!is_array($subCategoryIds)) {
                $subCategoryIds = array_map('trim', explode(',', $data['business']->business_sub_category_ids));
            }

            // Fetch PNG-based BusinessFrames bound to the user's business sub categories
            $business_frames = \App\Models\BusinessFrame::whereIn('business_sub_category_id', $subCategoryIds)
                                          ->where('status', 1)
                                          ->get();
            foreach ($business_frames as $bf) {
                $frames_list->push((object) [
                    'id' => 'bf_' . $bf->id,
                    'db_id' => $bf->id,
                    'full_url' => asset('uploads/' . $bf->frame_image),
                    'thumbnail_url' => asset('uploads/' . $bf->frame_image),
                    'language_id' => 'all',
                    'category_id' => 'all',
                    'theme' => $bf->theme ?? 'all',
                    'req_address' => $bf->req_address ?? 0,
                    'req_email' => $bf->req_email ?? 0,
                    'req_phone' => $bf->req_phone ?? 0,
                    'req_website' => $bf->req_website ?? 0,
                    'config' => null,
                    'image_type' => 'full',
                ]);
            }
        }

        // 2. Extra: Fetch standard PNG-based CustomFrames (generic borders)
        // We now allow these for ALL types, including custom posts, so the user has generic PNG borders available.
        $custom_frames = \App\Models\CustomFrame::where('status', 1)->get();
            foreach ($custom_frames as $f) {
                $frames_list->push((object) [
                    'id' => 'custom_' . $f->id,
                    'full_url' => asset('uploads/' . $f->frame_image),
                    'thumbnail_url' => asset('uploads/' . $f->frame_image),
                    'language_id' => $f->language_id ?? 'all',
                    'category_id' => 'all',
                    'theme' => $f->theme ?? 'all',
                    'req_address' => $f->req_address ?? 0,
                    'req_email' => $f->req_email ?? 0,
                    'req_phone' => $f->req_phone ?? 0,
                    'req_website' => $f->req_website ?? 0,
                    'config' => null,
                    'image_type' => 'full',
                ]);
            }


        // 3. Add PosterMaker frames (Frame -> Frame admin page) as overlay options
        // These are ZIP-based frames with JSON configs containing header/footer/logo layers.
        // They overlay ON TOP of the base template (festival/category/custom design).
        $poster_frames = \App\Models\PosterMaker::orderBy('id', 'desc')->get();
        foreach ($poster_frames as $pf) {
            $zipName = $pf->zip_name ?? '';
            if (!$zipName) continue;

            // Find the first skin folder for the full_url (used by applyFrameConfig in editor)
            $skinsDir = base_path('uploads/template/' . $zipName . '/skins');
            $skinFolder = '';
            $skinFiles = [];
            if (is_dir($skinsDir)) {
                $dirs = array_filter(glob($skinsDir . '/*'), 'is_dir');
                if (!empty($dirs)) {
                    $skinFolderPath = reset($dirs);
                    $skinFolder = basename($skinFolderPath);
                    $skinFiles = glob($skinFolderPath . '/*');
                }
            }

            // Read JSON config and fix layer sources
            $jsonDir = base_path('uploads/template/' . $zipName . '/json');
            $config = null;
            if (is_dir($jsonDir)) {
                $jsonFiles = glob($jsonDir . '/*.json');
                if (!empty($jsonFiles)) {
                    $config = json_decode(file_get_contents($jsonFiles[0]));
                    // Fix filename mismatches between JSON and actual files
                    if ($config && isset($config->layers) && !empty($skinFiles)) {
                        foreach ($config->layers as $layer) {
                            if ($layer->type === 'image' && isset($layer->src)) {
                                $layerSrc = basename($layer->src);
                                $found = false;
                                foreach ($skinFiles as $file) {
                                    if (basename($file) === $layerSrc) {
                                        $found = true;
                                        break;
                                    }
                                }
                                if (!$found) {
                                    $cleanSrc = strtolower(str_replace([' ', '_', '-'], '', $layerSrc));
                                    foreach ($skinFiles as $file) {
                                        if (strtolower(str_replace([' ', '_', '-'], '', basename($file))) === $cleanSrc) {
                                            // Update the layer src with the CORRECT filename
                                            // Make sure to prepend the same path structure so frontend string splitting works
                                            $layer->src = dirname($layer->src) . '/' . basename($file);
                                            break;
                                        }
                                    }
                                }
                            }
                        }
                    }
                }
            }

            // Use post_thumb as the preview thumbnail in the scroller
            $thumbUrl = $pf->post_thumb ? asset('uploads/' . $pf->post_thumb) : '';

            // Use the first layer image as the full_url for overlay rendering
            $fullUrl = $skinFolder ? asset('uploads/template/' . $zipName . '/skins/' . $skinFolder . '/BG.png') : $thumbUrl;

            $frames_list->push((object) [
                'id' => 'poster_' . $pf->id,
                'db_id' => $pf->id,
                'full_url' => $fullUrl,
                'thumbnail_url' => $thumbUrl,
                'language_id' => 'all',
                'category_id' => $pf->poster_category_id ?? 'all',
                'theme' => $pf->theme ?? 'all',
                'req_address' => $pf->req_address ?? 0,
                'req_email' => $pf->req_email ?? 0,
                'req_phone' => $pf->req_phone ?? 0,
                'req_website' => $pf->req_website ?? 0,
                'config' => $config,
                'image_type' => 'full',
            ]);
        }


        // STRICT FILTERING BASED ON VISIBLE DETAILS
        if (isset($data['business']) && $data['business']) {
            $frames_list = $this->filterFramesByBusinessData($frames_list, $data['business']);
        }

        $frames = $frames_list;

        // Check if user has products (for smart template sorting + My Products panel)
        $hasProducts = \App\Models\Product::where('user_id', Auth::id())->exists();

        // ═══ SMART RELEVANCE SCORING (Phase 4) ═══
        // Sort by: 1) Favorites first, 2) Relevant image_type first, 3) Original order
        // Has products → transparent/cutout templates first (user needs product cutout templates)
        // No products → full image templates first (user needs background-based templates)
        $frames = $frames->sortByDesc(function ($frame) use ($favoriteFrames, $hasProducts) {
            $score = 0;
            
            // Favorites get highest priority (+100)
            if (in_array($frame->id, $favoriteFrames)) {
                $score += 100;
            }
            
            // Image type relevance (+10)
            $imageType = $frame->image_type ?? 'full';
            if ($hasProducts && $imageType === 'transparent') {
                $score += 10; // User has products → transparent templates are more relevant
            } elseif (!$hasProducts && $imageType === 'full') {
                $score += 10; // User has no products → full image templates are more relevant
            }
            
            return $score;
        })->values();

        $sticker_categories = \App\Models\StickerCategory::where('status', 1)->get();
        $stickers = \App\Models\Sticker::where('status', 1)->get();
        $poster_categories = \App\Models\PosterCategory::where('status', 1)->get();
        $frame_id = request()->get('frame_id');

        return response(view('client.universal_edit', array_merge($data, compact('item', 'languages', 'item_frames', 'frames', 'sticker_categories', 'stickers', 'poster_categories', 'type', 'id', 'frame_id', 'post_template', 'favoriteFrames', 'hasProducts'))))
            ->header('Cache-Control', 'no-store, no-cache, must-revalidate, post-check=0, pre-check=0')
            ->header('Pragma', 'no-cache')
            ->header('Expires', '0');
    }

    public function post_edit($id)
    {
        return redirect()->route('universal.edit', ['type' => 'post', 'id' => $id]);
    }

    public function general_posts_client(Request $request)
    {
        $data = $this->getCommonData();
        $query = \App\Models\GeneralPost::where('status', 1)
            ->where('process_status', 'success');

        if ($request->has('category_id') && $request->category_id != 'all') {
            $query->where('business_category_id', $request->category_id);
        }

        $perPage = 12;
        $offset = (int) $request->input('offset', 0);

        $allCount = (clone $query)->count();
        $general_posts = $query->orderBy('id', 'desc')->skip($offset)->take($perPage)->get();
        $hasMore = ($offset + $perPage) < $allCount;
        $nextOffset = $offset + $perPage;

        // AJAX load-more request
        if ($request->ajax()) {
            $html = '';
            foreach ($general_posts as $post) {
                $html .= view('client.partials.general_post_card', compact('post'))->render();
            }
            return response()->json([
                'html' => $html,
                'hasMore' => $hasMore,
                'nextOffset' => $nextOffset,
            ]);
        }

        $categories = \App\Models\BusinessCategory::where('status', 1)->get();

        return view('client.general_posts', array_merge($data, compact('general_posts', 'categories', 'hasMore', 'nextOffset')));
    }
    public function getSubCategories($category_id)
    {
        $subCategories = \App\Models\BusinessSubCategory::where('business_category_id', $category_id)
                            ->where('status', 1)
                            ->get(['id', 'name']);
                            
        return response()->json(['success' => true, 'data' => $subCategories]);
    }

    /**
     * Lazy AI Generation endpoint for Custom Frame content.
     * Uses Just-In-Time strategy: checks DB cache, generates only on miss.
     */
    public function generateFrameContent(Request $request)
    {
        $frameId = $request->input('frame_id');
        $userId = Auth::id();

        if (!$frameId || !$userId) {
            return response()->json(['success' => false, 'message' => 'Missing parameters'], 400);
        }

        $content = \App\Services\CustomFrameAIService::generateForUser((int) $frameId, $userId);

        if ($content === null) {
            return response()->json(['success' => false, 'message' => 'Could not generate content'], 500);
        }

        return response()->json([
            'success' => true,
            'frame_id' => $frameId,
            'content' => $content,
        ]);
    }

    /**
     * Manual AI Generation endpoint for Custom Frame content (Editor).
     */
    public function generateManualAiContent(Request $request)
    {
        $frameId = $request->input('frame_id');
        $productId = $request->input('product_id');
        $manualPrompt = $request->input('manual_prompt');
        $canvasLayers = $request->input('canvas_layers', []);
        $language = $request->input('language', 'English');
        $userId = Auth::id();

        \Log::info("Manual AI Generate called", [
            'frame_id' => $frameId, 'user_id' => $userId, 
            'canvas_layers_count' => count($canvasLayers),
            'language' => $language
        ]);

        if (!$userId) {
            return response()->json(['success' => false, 'message' => 'Not authenticated'], 401);
        }

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

    /**
     * My Products Panel — Returns user's product images grouped by unique column.
     * Used in the editor sidebar for one-click product image insertion.
     */
    public function getProductImages(Request $request)
    {
        $userId = Auth::id();
        if (!$userId) {
            return response()->json(['success' => false, 'products' => [], 'groups' => []], 401);
        }

        $templateId = $request->input('template_id', null);

        // Get all products for this user
        $products = \App\Models\Product::where('user_id', $userId)
            ->with('customValues')
            ->get();

        // Find the unique column for grouping
        $uniqueColumn = \App\Models\CatalogueCustomColumn::where('user_id', $userId)
            ->where('is_unique', true)->first();

        // Find the category column for grouping fallback
        $categoryColumn = \App\Models\CatalogueCustomColumn::where('user_id', $userId)
            ->where('is_category', true)->first();

        // Get used product IDs for this template (to show ✅ indicator)
        $usedProductIds = [];
        if ($templateId) {
            $usedProductIds = \DB::table('editor_product_selections')
                ->where('user_id', $userId)
                ->where('template_id', $templateId)
                ->pluck('product_id')
                ->toArray();
        }

        // Build product list with grouping info
        $grouped = [];
        foreach ($products as $p) {
            // Determine group name
            $groupName = 'All Products';

            // Try unique column first
            if ($uniqueColumn) {
                $val = $p->customValues->firstWhere('column_id', $uniqueColumn->id);
                if ($val && !empty($val->value)) {
                    $groupName = $val->value;
                }
            }
            // Fallback to category column
            elseif ($categoryColumn) {
                $val = $p->customValues->firstWhere('column_id', $categoryColumn->id);
                if ($val && !empty($val->value)) {
                    $decoded = json_decode($val->value, true);
                    $groupName = is_array($decoded) ? implode(', ', $decoded) : $val->value;
                }
            }
            // Fallback to product category_name
            elseif (!empty($p->category_name)) {
                $groupName = $p->category_name;
            }

            if (!isset($grouped[$groupName])) {
                $grouped[$groupName] = [];
            }

            $imageUrl = $p->image ? asset('uploads/' . $p->image) : null;

            $grouped[$groupName][] = [
                'id' => $p->id,
                'name' => $p->display_name,
                'image_url' => $imageUrl,
                'has_image' => !empty($p->image),
                'is_used' => in_array($p->id, $usedProductIds),
            ];
        }

        return response()->json([
            'success' => true,
            'has_products' => $products->count() > 0,
            'total_count' => $products->count(),
            'groups' => $grouped,
        ]);
    }

    /**
     * Track product image selection in editor (for ✅ indicator).
     */
    public function saveProductSelection(Request $request)
    {
        $userId = Auth::id();
        if (!$userId) {
            return response()->json(['success' => false], 401);
        }

        $validated = $request->validate([
            'product_id' => 'required|integer',
            'template_id' => 'nullable|string',
            'image_url' => 'required|string',
            'image_mode' => 'required|in:full,transparent',
        ]);

        \DB::table('editor_product_selections')->updateOrInsert(
            [
                'user_id' => $userId,
                'product_id' => $validated['product_id'],
                'template_id' => $validated['template_id'],
            ],
            [
                'image_url' => $validated['image_url'],
                'image_mode' => $validated['image_mode'],
                'updated_at' => now(),
                'created_at' => now(),
            ]
        );

        return response()->json(['success' => true]);
    }
    public function filterFramesByBusinessData($frames_list, $business)
    {
        $visiblePhones = 0;
        $visibleEmails = 0;
        $visibleAddresses = 0;
        $visibleWebsites = 0;

        if ($business) {
            $hiddenFields = [];
            if (!empty($business->hidden_frame_fields)) {
                $hiddenFields = is_string($business->hidden_frame_fields) ? json_decode($business->hidden_frame_fields, true) : $business->hidden_frame_fields;
            }

        // Visible Phones
        $visiblePhones = 0;
        $hPhones = $hiddenFields['mobile_numbers'] ?? [];
        if (!empty($business->mobile_no) && !in_array($business->mobile_no, $hPhones)) $visiblePhones++;
        if (!empty($business->extra_mobile_numbers)) {
            $extras = is_string($business->extra_mobile_numbers) ? json_decode($business->extra_mobile_numbers, true) : $business->extra_mobile_numbers;
            if (is_array($extras)) {
                foreach ($extras as $val) {
                    if (!in_array($val, $hPhones) && !empty($val)) $visiblePhones++;
                }
            }
        }

        // Visible Emails
        $visibleEmails = 0;
        $hEmails = $hiddenFields['emails'] ?? [];
        if (!empty($business->email) && !in_array($business->email, $hEmails)) $visibleEmails++;
        if (!empty($business->extra_emails)) {
            $extras = is_string($business->extra_emails) ? json_decode($business->extra_emails, true) : $business->extra_emails;
            if (is_array($extras)) {
                foreach ($extras as $val) {
                    if (!in_array($val, $hEmails) && !empty($val)) $visibleEmails++;
                }
            }
        }

        // Visible Addresses
        $visibleAddresses = 0;
        $hAddresses = $hiddenFields['addresses'] ?? [];
        if (!empty($business->address) && !in_array($business->address, $hAddresses)) $visibleAddresses++;
        if (!empty($business->extra_addresses)) {
            $extras = is_string($business->extra_addresses) ? json_decode($business->extra_addresses, true) : $business->extra_addresses;
            if (is_array($extras)) {
                foreach ($extras as $val) {
                    if (!in_array($val, $hAddresses) && !empty($val)) $visibleAddresses++;
                }
            }
        }

        // Visible Websites
        $visibleWebsites = 0;
        $hWebsites = $hiddenFields['websites'] ?? [];
        if (!empty($business->website) && !in_array($business->website, $hWebsites)) $visibleWebsites++;
        if (!empty($business->extra_websites)) {
            $extras = is_string($business->extra_websites) ? json_decode($business->extra_websites, true) : $business->extra_websites;
            if (is_array($extras)) {
                foreach ($extras as $val) {
                    if (!in_array($val, $hWebsites) && !empty($val)) $visibleWebsites++;
                }
            }
        }

        } // End of if ($business) check

        $filterClosure = function($f) use ($visiblePhones, $visibleEmails, $visibleAddresses, $visibleWebsites) {
            $is_array = is_array($f);
            $req_phone = (int)($is_array ? ($f['req_phone'] ?? 0) : ($f->req_phone ?? 0));
            $req_email = (int)($is_array ? ($f['req_email'] ?? 0) : ($f->req_email ?? 0));
            $req_address = (int)($is_array ? ($f['req_address'] ?? 0) : ($f->req_address ?? 0));
            $req_website = (int)($is_array ? ($f['req_website'] ?? 0) : ($f->req_website ?? 0));
            
            // User must have AT LEAST as many visible fields as frame requires
            // Also: if frame requires 0, user can have any amount (no restriction)
            return ($visiblePhones >= $req_phone) &&
                   ($visibleEmails >= $req_email) &&
                   ($visibleAddresses >= $req_address) &&
                   ($visibleWebsites >= $req_website);
        };

        if ($frames_list instanceof \Illuminate\Support\Collection) {
            return $frames_list->filter($filterClosure)->values();
        } else if (is_array($frames_list)) {
            return array_values(array_filter($frames_list, $filterClosure));
        }

        return $frames_list;
    }
}
