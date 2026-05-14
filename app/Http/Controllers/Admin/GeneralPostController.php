<?php

namespace App\Http\Controllers\Admin;

use Auth;
use App\Models\Language;
use Illuminate\Support\Str;
use Illuminate\Http\Request;
use App\Models\GeneralPost;
use App\Models\BusinessCategory;
use App\Models\BusinessSubCategory;
use App\Models\PostPurpose;
use App\Models\Product;
use App\Models\StorageSetting;
use App\Http\Controllers\Controller;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Facades\Validator;
use ZipArchive;
use File;
use App\Models\ZipFileManager;
use App\Models\AiSetting;
use Illuminate\Support\Facades\Log;

class GeneralPostController extends Controller
{
    public function __construct()
    {
        $this->middleware('permission:BusinessFrame');
    }

    public function index()
    {
        $index['category'] = BusinessCategory::get();
        $index['data'] = GeneralPost::orderBy('id', 'DESC')->paginate(12);
        $index['subcategories'] = collect([]);
        $index['purposes'] = PostPurpose::where('status', 1)->get();
        return view("general_post.index", $index);
    }

    public function create()
    {
        $index['category'] = BusinessCategory::where('status', 1)->get();
        $index['product'] = Product::where('status', 1)->get();
        $index['purposes'] = PostPurpose::where('status', 1)->get();
        return view("general_post.create", $index);
    }

    public function store(Request $request)
    {
        $validation = Validator::make($request->all(), [
            "zip_file"           => "nullable|file|mimes:zip",
            "ai_content_subject" => "nullable|string",
            "task_name"          => "nullable|string",
            "task_images"        => "nullable|array",
            "task_images.*"      => "nullable|array",
            "task_images.*.*"    => "nullable|image|mimes:jpg,jpeg,png,webp",
        ]);

        if ($validation->fails()) {
            if ($request->ajax()) {
                return response()->json(['success' => false, 'message' => $validation->errors()->first()], 422);
            }
            return back()->withErrors($validation)->withInput();
        }

        $subcategories = $request->get("business_sub_category_id");
        if (empty($subcategories)) {
            // Fallback for if no subcategory selected but category is
            $subcategories = [null];
        }

        // Upload task-specific images submitted per subcategory
        $taskImageUrls = []; 
        $allFiles = $request->allFiles();
        
        if (isset($allFiles['task_images'])) {
            Log::info("Task images found in request. Processing subcategories: " . implode(', ', array_keys($allFiles['task_images'])));
            foreach ($allFiles['task_images'] as $subcatId => $files) {
                $urls = [];
                // Handle both single files and arrays of files
                $fileList = is_array($files) ? $files : [$files];
                foreach ($fileList as $file) {
                    if ($file instanceof \Illuminate\Http\UploadedFile) {
                        $fileName = Str::uuid() . '.' . $file->getClientOriginalExtension();
                        if (StorageSetting::getStorageSetting('storage') == 'DigitalOcean') {
                            Storage::disk('spaces')->put('uploads/' . $fileName, file_get_contents($file), 'public');
                        } else {
                            $file->move(public_path('uploads'), $fileName);
                        }
                        $urls[] = $fileName;
                    }
                }
                if (!empty($urls)) {
                    $taskImageUrls[(string)$subcatId] = $urls;
                }
            }
        }
        
        // CRITICAL: Put the successfully uploaded images into the session for the bulk generator
        session(['task_images' => $taskImageUrls]);
        Log::info("Stored " . count($taskImageUrls) . " subcategory image sets in session.");

        if ($request->hasFile('zip_file') || $request->get('zip_file_id')) {
            Log::info("Starting bulk post processing...");
            $zip = new ZipArchive;
            $tempZipPath = null;
            $zipName = null;

            if ($request->hasFile('zip_file')) {
                $zipRes = $zip->open($request->file('zip_file'));
                $zipName = $request->file('zip_file')->getClientOriginalName();
                Log::info("Uploaded ZIP file: " . $zipName);
            } else {
                $zipManager = ZipFileManager::find($request->get('zip_file_id'));
                if ($zipManager) {
                    $zipName = $zipManager->zip_file;
                    Log::info("Using existing ZIP from manager: " . $zipName);
                    if (StorageSetting::getStorageSetting("storage") == "DigitalOcean") {
                        $tempZipPath = public_path('temp_' . Str::uuid() . '.zip');
                        file_put_contents($tempZipPath, file_get_contents(Storage::disk('spaces')->url('uploads/zips/' . $zipManager->zip_file)));
                        $zipRes = $zip->open($tempZipPath);
                    } else {
                        $zipRes = $zip->open(public_path('uploads/zips/' . $zipManager->zip_file));
                    }
                } else {
                    Log::error("ZIP Manager entry not found for ID: " . $request->get('zip_file_id'));
                    if ($request->ajax()) {
                        return response()->json(['success' => false, 'message' => 'Selected ZIP file not found.'], 404);
                    }
                    return back()->with('error', 'Selected ZIP file not found.')->withInput();
                }
            }

            if ($zipRes === TRUE) {
                // Determine if we need to keep this permanently for AI layers
                $isAiBulk = !empty($request->ai_content_subject);
                
                if ($isAiBulk) {
                    $extractPath = public_path('uploads/template/' . $zipName);
                } else {
                    $extractPath = public_path('temp_zip_' . time());
                }

                if (!file_exists($extractPath)) {
                    mkdir($extractPath, 0777, true);
                }
                $zip->extractTo($extractPath);
                $zip->close();
                Log::info("ZIP extracted to: " . $extractPath);

                if ($tempZipPath && file_exists($tempZipPath))
                    unlink($tempZipPath);

                $files = File::allFiles($extractPath);
                Log::info("Found " . count($files) . " files in extracted ZIP.");

                $processedDesigns = [];
                $successCount = 0;
                $failCount = 0;
                foreach ($files as $file) {
                    $ext = strtolower($file->getExtension());
                    if (in_array($ext, ['jpg', 'jpeg', 'png'])) {
                        $pathName = $file->getPathname();
                        $pathParts = explode(DIRECTORY_SEPARATOR, $pathName);
                        
                        // Check if file is in skins/DesignName/
                        $skinsIndex = array_search('skins', $pathParts);
                        $designName = null;
                        if ($skinsIndex !== false && isset($pathParts[$skinsIndex + 1])) {
                            $designName = $pathParts[$skinsIndex + 1];
                        }

                        if ($designName) {
                            if (isset($processedDesigns[$designName])) continue;
                            
                            // Prefer frame.png or thumb.png or the first image we find
                            $fileName = strtolower($file->getFilename());
                            if ($fileName !== 'frame.png' && $fileName !== 'thumb.png' && $fileName !== 'image-1.png') {
                                // Check if frame.png exists in this folder
                                $dir = dirname($pathName);
                                if (file_exists($dir . DIRECTORY_SEPARATOR . 'frame.png') || 
                                    file_exists($dir . DIRECTORY_SEPARATOR . 'thumb.png') ||
                                    file_exists($dir . DIRECTORY_SEPARATOR . 'image-1.png')) {
                                    continue; // Skip this file, we'll pick the preferred one later or we already did
                                }
                            }
                            $processedDesigns[$designName] = true;
                        } else {
                            // If it's not in a design folder (skins/), skip it in bulk mode
                            Log::info("Skipping non-design asset: " . $file->getFilename());
                            continue;
                        }

                        Log::info("Processing image: " . $file->getFilename() . " for design: " . ($designName ?? 'default'));
                        foreach ($subcategories as $subId) {
                            $res = $this->createPost($pathName, $request, $subId, $zipName, $taskImageUrls);
                            if ($res) {
                                $successCount++;
                            } else {
                                $failCount++;
                            }
                        }
                    }
                }
                
                // Only delete if it's a temporary extraction
                if (!$isAiBulk) {
                    File::deleteDirectory($extractPath);
                    Log::info("Deleted temporary ZIP extraction");
                } else {
                    Log::info("Kept ZIP extraction permanently for AI layers: " . $extractPath);
                }

            } else {
                Log::error("Failed to open ZIP. Error code: " . $zipRes);
                if ($request->ajax()) {
                    return response()->json(['success' => false, 'message' => 'Failed to open ZIP file.'], 422);
                }
                return back()->with('error', 'Failed to open ZIP file.')->withInput();
            }
        } else {
            Log::warning("No ZIP file or ZIP ID provided in request.");
            if ($request->ajax()) {
                return response()->json(['success' => false, 'message' => 'Please select a ZIP file.'], 422);
            }
            return back()->with('error', 'Please select a ZIP file.')->withInput();
        }

        if (isset($successCount) && isset($failCount)) {
            $msg = "Posts generation complete. Success: {$successCount}, Failed: {$failCount}.";
            if ($request->ajax()) {
                return response()->json([
                    'success' => true,
                    'warning' => $failCount > 0,
                    'message' => $msg
                ]);
            }
            if ($failCount > 0) {
                return redirect()->route("general-post.index")->with('warning', $msg);
            } else {
                return redirect()->route("general-post.index")->with('success', $msg);
            }
        }

        if ($request->ajax()) {
            return response()->json(['success' => true, 'message' => 'Posts created successfully.']);
        }
        return redirect()->route("general-post.index")->with('success', 'Posts created successfully.');
    }

    private function createPost($file, $request, $subId = null, $zipName = null, $taskImageUrls = [])
    {
        try {
            $isUploadedFile = $file instanceof \Illuminate\Http\UploadedFile;
            $filePath = $isUploadedFile ? $file->getPathname() : $file;
            $extension = $isUploadedFile ? $file->getClientOriginalExtension() : pathinfo($file, PATHINFO_EXTENSION);

            $size = getimagesize($filePath);
            if (!$size) {
                throw new \Exception("Invalid image file or format.");
            }

            $type = "square";
            if ($size[0] > $size[1])
                $type = "landscape";
            elseif ($size[0] < $size[1])
                $type = "portrait";

            $categoryId = $request->get("business_category_id");
            if (is_array($categoryId)) {
                $categoryId = !empty($categoryId) ? $categoryId[0] : null;
            }

            $subcategoryName = "";
            if ($subId) {
                $sub = BusinessSubCategory::find($subId);
                if ($sub) {
                    $categoryId = $sub->business_category_id;
                    $subcategoryName = $sub->name;
                }
            }

            $aiGeneratedContent = null;
            $aiSubject = $request->get("ai_content_subject");
            $purposeId = $request->get("post_purpose_id");
            $purposeName = "";
            if ($purposeId) {
                $purpose = PostPurpose::find($purposeId);
                if ($purpose) {
                    $purposeName = $purpose->name;
                }
            }

            if ($aiSubject && !$isUploadedFile) {

                // Try to find matching JSON for AI content
                $pathParts = explode(DIRECTORY_SEPARATOR, $filePath);
                
                $skinsIndex = array_search('skins', $pathParts);
                $designName = null;
                $jsonPath = null;

                if ($skinsIndex !== false && isset($pathParts[$skinsIndex + 1])) {
                    $designName = $pathParts[$skinsIndex + 1];
                    $rootPath = implode(DIRECTORY_SEPARATOR, array_slice($pathParts, 0, $skinsIndex));
                    $jsonPath = $rootPath . DIRECTORY_SEPARATOR . 'json' . DIRECTORY_SEPARATOR . $designName . '.json';
                } else {
                    $fileNameWithExt = end($pathParts);
                    $designName = pathinfo($fileNameWithExt, PATHINFO_FILENAME);
                    $rootPath = implode(DIRECTORY_SEPARATOR, array_slice($pathParts, 0, -1));
                    $jsonPath = $rootPath . DIRECTORY_SEPARATOR . $designName . '.json';
                }

                if ($jsonPath && file_exists($jsonPath)) {
                    Log::info("Found JSON for AI: " . $jsonPath);
                    // Pass task-specific images for this subcategory (if any)
                    $subKey = !empty($subId) ? (string)$subId : "null";
                    $taskImgsForSub = isset($taskImageUrls[$subKey]) ? $taskImageUrls[$subKey] : [];
                    
                    if (empty($taskImgsForSub) && !empty($taskImageUrls)) {
                        // Fallback: If no images specifically for this subcategory, but some were uploaded for the task, use the first set
                        // This helps if there was a slight ID mismatch or if only one set was provided for multiple subcategories
                        $firstKey = array_key_first($taskImageUrls);
                        $taskImgsForSub = $taskImageUrls[$firstKey];
                        Log::info("No specific images for subcategory {$subKey}, falling back to images for subcategory {$firstKey}");
                    }

                    Log::info("Mapping " . count($taskImgsForSub) . " task images for subcategory: " . ($subcategoryName ?: 'default'));
                    $aiGeneratedContent = $this->generateAiContent($aiSubject, $subcategoryName, $jsonPath, $purposeName, $taskImgsForSub);
                } else {
                    Log::warning("JSON not found for design: " . ($designName ?? 'unknown') . " at " . ($jsonPath ?? 'N/A'));
                }

                if (!$aiGeneratedContent) {
                    throw new \Exception("AI Content Generation Failed or JSON template missing.");
                }
            }

            $post = GeneralPost::create([
                "business_category_id" => $categoryId,
                "business_sub_category_id" => $subId,
                "product_id" => $request->get("product_id"),
                "user_id" => Auth::user()->id,
                "paid" => 1,
                "height" => $size[1],
                "width" => $size[0],
                "image_type" => $type,
                "aspect_ratio" => $this->getAspectRatio($size[0], $size[1]),
                "zip_file_id" => $request->get("zip_file_id"),
                "zip_name" => $zipName,
                "ai_content_subject" => $aiSubject,
                "ai_generated_content" => $aiGeneratedContent,
                "task_name" => $request->get("task_name"),
                "post_purpose_id" => $purposeId,
                "process_status" => 'success',
            ]);

            $fileName = Str::uuid() . '.' . $extension;

            if (StorageSetting::getStorageSetting("storage") == "DigitalOcean") {
                Storage::disk('spaces')->put('uploads/' . $fileName, file_get_contents($filePath), 'public');
                $post->frame_image = $fileName;
                $post->save();
            } else {
                $destinationPath = public_path('uploads');
                if (!file_exists($destinationPath)) {
                    mkdir($destinationPath, 0777, true);
                }
                if ($isUploadedFile) {
                    $file->move($destinationPath, $fileName);
                } else {
                    File::copy($filePath, $destinationPath . '/' . $fileName);
                }
                $post->frame_image = $fileName;
                $post->save();
            }
            return true;
        } catch (\Exception $e) {
            // Create a failed record if we can at least determine the category
            $categoryId = $request->get("business_category_id");
            if (is_array($categoryId)) {
                $categoryId = !empty($categoryId) ? $categoryId[0] : null;
            }

            GeneralPost::create([
                "business_category_id" => $categoryId,
                "business_sub_category_id" => $subId,
                "product_id" => $request->get("product_id"),
                "user_id" => Auth::user()->id,
                "paid" => 1,
                "zip_file_id" => $request->get("zip_file_id"),
                "zip_name" => $zipName,
                "task_name" => $request->get("task_name"),
                "process_status" => 'failed',
                "failure_reason" => $e->getMessage(),
            ]);
            return false;
        }
    }

    public function general_post_status(Request $request)
    {
        $post = GeneralPost::find($request->get("id"));
        $post->status = ($request->get("checked") == "true") ? 1 : 0;
        $post->save();
    }

    public function general_post_action(Request $request)
    {
        $ids = explode(",", $request->select_post);
        if ($request->select_post != null) {
            foreach ($ids as $id) {
                $post = GeneralPost::find($id);
                if (!$post)
                    continue;

                if ($request->action_type == "enable") {
                    $post->status = 1;
                    $post->save();
                } elseif ($request->action_type == "disable") {
                    $post->status = 0;
                    $post->save();
                } elseif ($request->action_type == "delete") {
                    if (StorageSetting::getStorageSetting("storage") == "DigitalOcean") {
                        Storage::disk('spaces')->delete('uploads/' . $post->frame_image);
                    } else {
                        if ($post->frame_image && file_exists(public_path('uploads/' . $post->frame_image))) {
                            unlink(public_path('uploads/' . $post->frame_image));
                        }
                    }
                    $post->delete();
                }
            }
        }
        return redirect()->route("general-post.index");
    }

    public function business_category_get($id)
    {
        $index['category'] = BusinessCategory::get();
        $index['data'] = GeneralPost::where('business_category_id', $id)->paginate(12);
        $c_name = BusinessCategory::find($id);
        $index['name'] = $c_name->name;
        $index['subcategories'] = BusinessSubCategory::where('business_category_id', $id)->where('status', 1)->get();
        $index['purposes'] = PostPurpose::where('status', 1)->get();
        $index['selected_category_id'] = $id;
        return view("general_post.index", $index);
    }

    public function get_business_sub_category(Request $request)
    {
        $id = $request->id;
        if (is_array($id)) {
            $data = BusinessSubCategory::whereIn('business_category_id', $id)->where('status', 1)->get();
        } else {
            $data = BusinessSubCategory::where('business_category_id', $id)->where('status', 1)->get();
        }
        return response()->json($data);
    }

    public function general_post_type(Request $request)
    {
        $post = GeneralPost::find($request->get("id"));
        $post->paid = ($request->get("checked") == "true") ? 1 : 0;
        $post->save();
        return $post->paid;
    }

    public function edit($id)
    {
        $index['post'] = GeneralPost::find($id);
        $index['category'] = BusinessCategory::where('status', 1)->get();
        $index['subcategory'] = BusinessSubCategory::where("business_category_id", $index['post']->business_category_id)->where('status', 1)->get();
        $index['product'] = Product::where('status', 1)->get();
        $index['purposes'] = PostPurpose::where('status', 1)->get();
        return view("general_post.edit", $index);
    }

    public function update(Request $request, $id)
    {
        $validation = Validator::make($request->all(), [
            "frame_image" => "nullable|image|mimes:jpg,png,jpeg",
            "task_name" => "nullable|string",
        ]);

        if ($validation->fails()) {
            return back()->withErrors($validation)->withInput();
        }

        $post = GeneralPost::find($id);
        $post->business_category_id = $request->get("business_category_id");
        $post->business_sub_category_id = $request->get("business_sub_category_id");
        $post->product_id = $request->get("product_id");
        $post->task_name = $request->get("task_name");
        $post->post_purpose_id = $request->get("post_purpose_id");
        $post->save();

        if ($request->hasFile('frame_image')) {
            $image = $request->file('frame_image');
            $size = getimagesize($image);

            $type = "square";
            if ($size[0] > $size[1])
                $type = "landscape";
            elseif ($size[0] < $size[1])
                $type = "portrait";

            $post->height = $size[1];
            $post->width = $size[0];
            $post->image_type = $type;
            $post->aspect_ratio = $this->getAspectRatio($size[0], $size[1]);

            $fileName = Str::uuid() . '.' . $image->getClientOriginalExtension();

            if (StorageSetting::getStorageSetting("storage") == "DigitalOcean") {
                Storage::disk('spaces')->delete('uploads/' . $post->frame_image);
                Storage::disk('spaces')->put('uploads/' . $fileName, file_get_contents($image), 'public');
                $post->frame_image = $fileName;
            } else {
                if ($post->frame_image && file_exists(public_path('uploads/' . $post->frame_image))) {
                    unlink(public_path('uploads/' . $post->frame_image));
                }
                $image->move(public_path('uploads'), $fileName);
                $post->frame_image = $fileName;
            }
            $post->save();
        }

        return redirect()->route('general-post.index');
    }

    public function destroy($id)
    {
        $post = GeneralPost::find($id);
        if (StorageSetting::getStorageSetting("storage") == "DigitalOcean") {
            Storage::disk('spaces')->delete('uploads/' . $post->frame_image);
        } else {
            if ($post->frame_image && file_exists(public_path('uploads/' . $post->frame_image))) {
                unlink(public_path('uploads/' . $post->frame_image));
            }
        }
        $post->delete();
        return redirect()->route('general-post.index');
    }

    public function update_subcategory_images(Request $request)
    {
        $subcategory = BusinessSubCategory::find($request->get('id'));
        if (!$subcategory) {
            return response()->json(['error' => 'Subcategory not found'], 404);
        }

        $field = $request->get('field'); // 'image_1', 'image_2', or 'more_images'
        if (!in_array($field, ['image_1', 'image_2', 'more_images'])) {
            return response()->json(['error' => 'Invalid field'], 400);
        }

        // Handle Deletion
        if ($request->get('action') == 'delete') {
            if ($field == 'more_images') {
                $images = $subcategory->more_images ?? [];
                $index = $request->get('index');
                if (isset($images[$index])) {
                    $fileName = $images[$index];
                    if (StorageSetting::getStorageSetting('storage') == 'DigitalOcean') {
                        Storage::disk('spaces')->delete('uploads/' . $fileName);
                    } else {
                        if (file_exists(public_path('uploads/' . $fileName))) {
                            unlink(public_path('uploads/' . $fileName));
                        }
                    }
                    array_splice($images, $index, 1);
                    $subcategory->more_images = $images;
                    $subcategory->save();
                    return response()->json(['success' => true]);
                }
                return response()->json(['error' => 'Image index not found'], 404);
            } else {
                if (StorageSetting::getStorageSetting('storage') == 'DigitalOcean') {
                    if ($subcategory->$field) {
                        Storage::disk('spaces')->delete('uploads/' . $subcategory->$field);
                    }
                } else {
                    if ($subcategory->$field && file_exists(public_path('uploads/' . $subcategory->$field))) {
                        unlink(public_path('uploads/' . $subcategory->$field));
                    }
                }
                $subcategory->$field = null;
                $subcategory->save();
                return response()->json(['success' => true]);
            }
        }

        // Bulk Upload Handling
        if ($request->hasFile('images')) {
            $uploadedFiles = $request->file('images');
            $images = $subcategory->more_images ?? [];
            $newImageUrls = [];

            foreach ($uploadedFiles as $file) {
                $fileName = Str::uuid() . '.' . $file->getClientOriginalExtension();

                if (StorageSetting::getStorageSetting('storage') == 'DigitalOcean') {
                    Storage::disk('spaces')->put('uploads/' . $fileName, file_get_contents($file), 'public');
                } else {
                    $file->move(public_path('uploads'), $fileName);
                }

                $images[] = $fileName;
                $newImageUrls[] = (StorageSetting::getStorageSetting('storage') == 'DigitalOcean')
                    ? Storage::disk('spaces')->url('uploads/' . $fileName)
                    : asset('uploads/' . $fileName);
            }

            $subcategory->more_images = $images;
            $subcategory->save();

            return response()->json([
                'success' => true,
                'bulk' => true,
                'image_urls' => $newImageUrls,
                'subcategory' => $subcategory
            ]);
        }

        // Single Upload Handling (for Slot 1/2)
        if ($request->hasFile('image')) {
            $image = $request->file('image');
            $fileName = Str::uuid() . '.' . $image->getClientOriginalExtension();

            if (StorageSetting::getStorageSetting('storage') == 'DigitalOcean') {
                if ($field != 'more_images' && $subcategory->$field) {
                    Storage::disk('spaces')->delete('uploads/' . $subcategory->$field);
                }
                Storage::disk('spaces')->put('uploads/' . $fileName, file_get_contents($image), 'public');
            } else {
                if ($field != 'more_images' && $subcategory->$field && file_exists(public_path('uploads/' . $subcategory->$field))) {
                    unlink(public_path('uploads/' . $subcategory->$field));
                }
                $image->move(public_path('uploads'), $fileName);
            }

            if ($field == 'more_images') {
                $images = $subcategory->more_images ?? [];
                $images[] = $fileName;
                $subcategory->more_images = $images;
            } else {
                $subcategory->$field = $fileName;
            }
            $subcategory->save();

            $imageUrl = (StorageSetting::getStorageSetting('storage') == 'DigitalOcean')
                ? Storage::disk('spaces')->url('uploads/' . $fileName)
                : asset('uploads/' . $fileName);

            return response()->json(['success' => true, 'image_url' => $imageUrl, 'index' => ($field == 'more_images' ? count($subcategory->more_images) - 1 : null)]);
        }

        return response()->json(['error' => 'No image provided'], 400);
    }

    public function getAspectRatio(int $width, int $height)
    {
        $a = $width;
        $b = $height;
        while ($b != 0) {
            $t = $b;
            $b = $a % $b;
            $a = $t;
        }
        return $width / $a . ':' . $height / $a;
    }

    private function generateAiContent($subject, $subcategory, $jsonPath, $purpose = "", $taskImageUrls = [])
    {
        try {
            $config = json_decode(file_get_contents($jsonPath), true);
            if (!$config || !isset($config['layers'])) return null;


            $aiLayers = [];
            foreach ($config['layers'] as $layer) {
                if (isset($layer['ai_role']) && $layer['type'] == 'text') {
                    $maxChars = $layer['ai_max_chars'] ?? 50;
                    $aiLayers[] = [
                        'name' => $layer['name'],
                        'role' => $layer['ai_role'],
                        'max_chars' => $maxChars
                    ];
                }
            }

            if (empty($aiLayers)) return null;

            // Build a strict character-limited field list
            $rolesString = "";
            foreach ($aiLayers as $l) {
                $rolesString .= "- \"{$l['name']}\": {$l['role']} — STRICT LIMIT: {$l['max_chars']} characters maximum (including spaces & newlines)\n";
            }

            // Add unique seed to force variety across multiple calls with the same inputs
            $uniqueSeed = 'Variation-' . Str::random(8) . '-' . microtime(true);
            $prompt = "You are a creative social media content writer. Generate SHORT, PUNCHY text for a social media post template.\n";
            $prompt .= "UNIQUENESS SEED: {$uniqueSeed} — You MUST generate completely DIFFERENT and FRESH content every time. NEVER repeat the same words or phrases from previous generations. Be wildly creative and surprising.\n\n";
            $prompt .= "CRITICAL RULES — YOU MUST FOLLOW THESE EXACTLY:\n";
            $prompt .= "1. Each field has a STRICT character limit. You MUST NOT exceed it. Count every character including spaces, punctuation, and newline characters (\\n counts as 1 character).\n";
            $prompt .= "2. If the limit is 22 characters, your text MUST be 22 characters or fewer. Example: \"Digital\\nMarketing\\nAgency\" = 22 chars ✓, \"Boost Your Dairy & Livestock Performance\" = 41 chars ✗ TOO LONG.\n";
            $prompt .= "3. Use short, impactful words. Prefer 1-2 word phrases over long sentences.\n";
            $prompt .= "4. For multi-line fields (headings), use \\n to break lines. Each \\n counts as 1 character.\n";
            $prompt .= "5. BEFORE outputting each value, mentally count its characters. If it exceeds the limit, shorten it.\n";
            $prompt .= "6. NEVER use newline characters (\\n) unless the ai_role explicitly allows it.\n\n";
            // Inject ai_global_rule if present in the template JSON
            if (!empty($config['ai_global_rule'])) {
                $prompt .= "IMPORTANT GLOBAL RULE: " . $config['ai_global_rule'] . "\n\n";
            }
            $prompt .= "Target Subcategory: {$subcategory}\n";
            $prompt .= "Subject: {$subject}\n";
            if ($purpose) {
                $prompt .= "Post Purpose: {$purpose}\n";
            }
            $prompt .= "\nFields to generate (with STRICT character limits):\n{$rolesString}";
            $prompt .= "\nReturn ONLY a pure JSON object where keys are the field names and values are the generated text. No markdown, no code blocks, no extra text. Double-check every value is within its character limit before responding.";

            $provider = AiSetting::getAiSetting('ai_provider') ?: 'vertex';
            
            if ($provider == 'gemini') {
                $apiKey = AiSetting::getAiSetting('gemini_api_key');
                $model = trim(AiSetting::getAiSetting('gemini_model') ?: 'gemini-1.5-flash');
                // Clean model name - remove 'models/' prefix if present
                $cleanModel = preg_replace('/^models\//', '', $model);
                $url = "https://generativelanguage.googleapis.com/v1/models/" . urlencode($cleanModel) . ":generateContent?key={$apiKey}";
                $payload = [
                    'contents' => [['role' => 'user', 'parts' => [['text' => $prompt]]]],
                    'generationConfig' => [
                        'maxOutputTokens' => 8192,
                        'temperature' => 1.2,
                        'responseMimeType' => 'application/json'
                    ]
                ];
                $headers = ['Content-Type: application/json'];
            } elseif ($provider == 'chatgpt') {
                $apiKey = AiSetting::getAiSetting('chatgpt_api_key');
                $model = trim(AiSetting::getAiSetting('chatgpt_model') ?: 'gpt-4o-mini');
                $url = "https://api.openai.com/v1/chat/completions";
                $payload = [
                    'model' => $model,
                    'messages' => [['role' => 'user', 'content' => $prompt]],
                    'max_tokens' => 1000,
                    'temperature' => 1.2,
                    'response_format' => ['type' => 'json_object']
                ];
                $headers = [
                    'Content-Type: application/json',
                    'Authorization: Bearer ' . $apiKey
                ];
            } else {
                $model = AiSetting::getAiSetting('ai_model') ?: 'gemini-2.0-flash';
                $accessToken = $this->getVertexAccessToken();

                if ($accessToken) {
                    $projectId = AiSetting::getAiSetting('google_cloud_project_id');
                    $location = AiSetting::getAiSetting('vertex_location');
                    $url = "https://{$location}-aiplatform.googleapis.com/v1/projects/{$projectId}/locations/{$location}/publishers/google/models/{$model}:generateContent";
                    $headers = [
                        'Content-Type: application/json',
                        'Authorization: Bearer ' . $accessToken,
                    ];
                } else {
                    Log::error('Vertex AI: Service Account credentials not available.');
                    return null;
                }

                $payload = [
                    'contents' => [['role' => 'user', 'parts' => [['text' => $prompt]]]],
                    'generationConfig' => [
                        'maxOutputTokens' => 8192,
                        'temperature' => 1.2,
                        'responseMimeType' => 'application/json',
                        'thinkingConfig' => [
                            'thinkingBudget' => 1024
                        ]
                    ]
                ];
            }

            $ch = curl_init();
            curl_setopt_array($ch, [
                CURLOPT_URL => $url,
                CURLOPT_POST => true,
                CURLOPT_POSTFIELDS => json_encode($payload),
                CURLOPT_HTTPHEADER => $headers,
                CURLOPT_RETURNTRANSFER => true,
                CURLOPT_TIMEOUT => 120,
                CURLOPT_SSL_VERIFYPEER => true,
            ]);

            $response = curl_exec($ch);
            $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
            curl_close($ch);

            if (!$response) {
                Log::error("AI API Error: No response from provider. HTTP Code: $httpCode");
                throw new \Exception("AI Provider Unreachable (HTTP $httpCode)");
            }

            $data = json_decode($response, true);
            if (isset($data['error'])) {
                Log::error("AI API Error Message: " . json_encode($data['error']));
                $errCode = $data['error']['code'] ?? $httpCode;
                $errMsg = $data['error']['message'] ?? 'Unknown Error';
                if ($errCode == 503 || strpos($errMsg, 'high demand') !== false) {
                    throw new \Exception("AI Model Overloaded. Please try again later.");
                }
                if ($errCode == 429 || strpos($errMsg, 'quota') !== false) {
                    throw new \Exception("AI Quota Exceeded. Please check API limits.");
                }
                throw new \Exception("AI Error: " . $errMsg);
            }

            if (isset($data['candidates'][0]['content']['parts'][0]['text']) || isset($data['choices'][0]['message']['content'])) {
                $text = isset($data['choices'][0]['message']['content']) ? $data['choices'][0]['message']['content'] : $data['candidates'][0]['content']['parts'][0]['text'];
                
                // Clean markdown if AI included it
                $text = trim($text);
                if (strpos($text, '```json') === 0) {
                    $text = substr($text, 7);
                    $text = substr($text, 0, -3);
                } elseif (strpos($text, '```') === 0) {
                    $text = substr($text, 3);
                    $text = substr($text, 0, -3);
                }

                // Merge AI text with Subcategory images
                $aiData = json_decode($text, true);
                if (empty($aiData)) {
                    \Log::error("AI returned invalid/truncated JSON. Raw text: " . mb_substr($text, 0, 500));
                    throw new \Exception("AI returned incomplete response. Please try again.");
                }
                \Log::info("AI parsed successfully. Keys: " . implode(', ', array_keys($aiData)));
                
                // SERVER-SIDE SAFETY NET: Enforce character limits by truncating
                $maxCharsMap = [];
                foreach ($aiLayers as $l) {
                    $maxCharsMap[$l['name']] = $l['max_chars'];
                }
                foreach ($aiData as $key => $value) {
                    if (isset($maxCharsMap[$key]) && is_string($value)) {
                        $limit = $maxCharsMap[$key];
                        if (mb_strlen($value) > $limit) {
                            Log::warning("AI exceeded char limit for '{$key}': got " . mb_strlen($value) . " chars, max {$limit}. Truncating.");
                            // Smart truncate: try to break at a word boundary
                            $truncated = mb_substr($value, 0, $limit);
                            // If we cut mid-word, trim back to last space (but keep at least 60% of limit)
                            $lastSpace = mb_strrpos($truncated, ' ');
                            if ($lastSpace !== false && $lastSpace > $limit * 0.6) {
                                $truncated = mb_substr($truncated, 0, $lastSpace);
                            }
                            // Also handle newline-based content: trim back to last newline if it makes sense
                            $lastNewline = mb_strrpos($truncated, "\n");
                            if ($lastNewline !== false && $lastNewline > $limit * 0.6 && $lastNewline > ($lastSpace ?: 0)) {
                                $truncated = mb_substr($truncated, 0, $lastNewline);
                            }
                            $aiData[$key] = $truncated;
                        }
                    }
                }

                if (!empty($taskImageUrls)) {
                    $mappings = [];
                    // Use only task-specific images for this task
                    // Map up to 5 images with redundant keys for better template compatibility
                    for ($i = 0; $i < min(5, count($taskImageUrls)); $i++) {
                        $url = $taskImageUrls[$i];
                        $idx = $i + 1;
                        
                        // Redundant keys for Maximum Compatibility
                        $mappings['image' . $idx] = $url;
                        $mappings['image-' . $idx] = $url;
                        $mappings['image_' . $idx] = $url;
                        $mappings['image ' . $idx] = $url;
                        
                        // Special fallback for the first image
                        if ($idx === 1) {
                            $mappings['main_image'] = $url;
                            $mappings['main-image'] = $url;
                            $mappings['main image'] = $url;
                            $mappings['bg'] = $url;
                            $mappings['background'] = $url;
                        }
                    }
                    Log::info("Using " . count($taskImageUrls) . " task-specific image(s) for AI mapping.");
                    
                    $aiData['_image_mappings'] = $mappings;
                }

                return json_encode($aiData);
            }
        } catch (\Exception $e) {
            \Log::error("AI Content Generation Error: " . $e->getMessage());
            throw $e;
        }
        return null;
    }

    /**
     * Get OAuth2 access token from encrypted Service Account JSON stored in DB.
     */
    private function getVertexAccessToken()
    {
        $sa = null;

        // Try new encrypted format first
        $encrypted = AiSetting::getAiSetting('google_application_credentials_encrypted');
        if ($encrypted) {
            try {
                $json = \Illuminate\Support\Facades\Crypt::decryptString($encrypted);
                $sa = json_decode($json, true);
            } catch (\Exception $e) {
                \Log::error('Failed to decrypt Service Account credentials: ' . $e->getMessage());
            }
        }

        // Fallback: legacy file-path format
        if (!$sa) {
            $credentialsPath = AiSetting::getAiSetting('google_application_credentials');
            if ($credentialsPath && file_exists(base_path($credentialsPath))) {
                $sa = json_decode(file_get_contents(base_path($credentialsPath)), true);
            }
        }

        if (!$sa || !isset($sa['client_email']) || !isset($sa['private_key'])) {
            return null;
        }

        $now = time();
        $header = json_encode(['alg' => 'RS256', 'typ' => 'JWT']);
        $claims = json_encode([
            'iss' => $sa['client_email'],
            'scope' => 'https://www.googleapis.com/auth/cloud-platform',
            'aud' => 'https://oauth2.googleapis.com/token',
            'iat' => $now,
            'exp' => $now + 3600,
        ]);

        $b64Header = rtrim(strtr(base64_encode($header), '+/', '-_'), '=');
        $b64Claims = rtrim(strtr(base64_encode($claims), '+/', '-_'), '=');
        $signingInput = $b64Header . '.' . $b64Claims;

        openssl_sign($signingInput, $signature, $sa['private_key'], OPENSSL_ALGO_SHA256);
        $b64Signature = rtrim(strtr(base64_encode($signature), '+/', '-_'), '=');
        $jwt = $signingInput . '.' . $b64Signature;

        $ch = curl_init();
        curl_setopt_array($ch, [
            CURLOPT_URL => 'https://oauth2.googleapis.com/token',
            CURLOPT_POST => true,
            CURLOPT_POSTFIELDS => http_build_query([
                'grant_type' => 'urn:ietf:params:oauth:grant-type:jwt-bearer',
                'assertion' => $jwt,
            ]),
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_TIMEOUT => 10,
            CURLOPT_SSL_VERIFYPEER => true,
        ]);

        $response = curl_exec($ch);
        curl_close($ch);

        $data = json_decode($response, true);
        return $data['access_token'] ?? null;
    }
}
