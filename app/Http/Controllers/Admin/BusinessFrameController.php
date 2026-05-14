<?php

namespace App\Http\Controllers\Admin;

use Auth;
use App\Models\Language;
use Illuminate\Support\Str;
use Illuminate\Http\Request;
use App\Models\BusinessFrame;
use App\Models\StorageSetting;
use App\Models\BusinessCategory;
use App\Models\BusinessSubCategory;
use App\Http\Controllers\Controller;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Facades\Validator;

class BusinessFrameController extends Controller
{
    public function __construct()
    {
        $this->middleware('permission:BusinessFrame');
    }

    public function index()
    {
        $index['category'] = BusinessCategory::get();
        $index['data'] = BusinessFrame::orderBy('id', 'DESC')->paginate(12);

        // Custom Frames Tabs Data
        $index['purposes'] = \App\Models\CustomFramePurpose::orderBy('id', 'desc')->get();
        
        $index['image_types'] = \App\Models\CustomFrameImageType::with('subCategories')->orderBy('id', 'desc')->get();
        
        $index['business_custom_frames'] = \App\Models\BusinessCustomFrame::with(['purpose', 'imageType'])->orderBy('id', 'desc')->paginate(12);
        
        $index['business_sub_categories'] = BusinessSubCategory::where('status', 1)->get();
        
        // Special requested AI tags
        $index['dynamic_tags'] = [
            '{col_is_category}',
            '{col_is_unique}',
            '{col_is_combo}',
            '{col_is_Normal Regular Field}'
        ];

        return view("business_frame.index", $index);
    }

    public function create()
    {
        $index['category'] = BusinessCategory::where('status', 1)->get();
        return view("business_frame.create", $index);
    }

    public function store(Request $request)
    {
        $validation = Validator::make($request->all(), [
            "business_category_id" => 'required',
            "frame_image" => "required",
        ]);

        if ($validation->fails()) {
            return back()->withErrors($validation)->withInput();
        } else {
            if ($request->file("frame_image")) {
                $images = $request->file('frame_image');
                foreach ($images as $image) {
                    $size = getimagesize($image);
                    if (!$size)
                        continue;

                    if ($size[0] > $size[1]) {
                        $type = "landscape";
                    } elseif ($size[0] < $size[1]) {
                        $type = "portrait";
                    } else {
                        $type = "square";
                    }

                    $id = BusinessFrame::create([
                        "business_category_id" => $request->get("business_category_id"),
                        "business_sub_category_id" => $request->get("business_sub_category_id"),
                        "user_id" => Auth::User()->id,
                        "paid" => 1,
                        "height" => $size[1],
                        "width" => $size[0],
                        "image_type" => $type,
                        "aspect_ratio" => $this->getAspectRatio($size[0], $size[1]),
                    ])->id;

                    if (StorageSetting::getStorageSetting("storage") == "DigitalOcean") {
                        $file = Str::uuid() . '.' . $image->getClientOriginalExtension();
                        $path = Storage::disk('spaces')->put('uploads/' . $file, file_get_contents($image), 'public');

                        $f = BusinessFrame::find($id);
                        $f->frame_image = $file;
                        $f->save();
                    } else {
                        $this->upload_image($image, "frame_image", $id);
                    }
                }
            }

            return redirect()->route("business-frame.index");
        }
    }

    public function business_frame_status(Request $request)
    {
        $category = BusinessFrame::find($request->get("id"));
        $category->status = ($request->get("checked") == "true") ? 1 : 0;
        $category->save();
    }

    public function get_business_sub_category(Request $request)
    {
        $category = BusinessSubCategory::where("business_category_id", $request->get("id"))->get();
        return $category;
    }

    public function business_frame_action(Request $request)
    {
        $ids = explode(",", $request->select_post);
        if ($request->select_post != null) {
            if ($request->action_type == "enable") {
                foreach ($ids as $id) {
                    $category = BusinessFrame::find($id);
                    $category->status = 1;
                    $category->save();
                }
            }

            if ($request->action_type == "disable") {
                foreach ($ids as $id) {
                    $category = BusinessFrame::find($id);
                    $category->status = 0;
                    $category->save();
                }
            }

            if ($request->action_type == "delete") {
                foreach ($ids as $id) {
                    BusinessFrame::find($id)->delete();
                }
            }
        }

        return redirect()->route("business-frame.index");
    }

    public function business_category_get($id)
    {
        $index['category'] = BusinessCategory::get();
        $index['data'] = BusinessFrame::where('business_category_id', $id)->paginate(12);
        $c_name = BusinessCategory::find($id);
        $index['name'] = $c_name->name;

        return view("business_frame.index", $index);
    }

    public function business_frame_type(Request $request)
    {
        $category = BusinessFrame::find($request->get("id"));
        $category->paid = ($request->get("checked") == "true") ? 1 : 0;
        $category->save();

        if ($category->paid == 1) {
            return 1;
        } else {
            return 0;
        }
    }

    public function edit($id)
    {
        $index['businessFrame'] = BusinessFrame::find($id);
        $index['businessSubCategory'] = BusinessSubCategory::where("business_category_id", $index['businessFrame']->business_category_id)->where('status', 1)->get();
        $index['category'] = BusinessCategory::where('status', 1)->get();
        return view("business_frame.edit", $index);
    }

    public function update(Request $request, $id)
    {
        $validation = Validator::make($request->all(), [
            "business_category_id" => 'required',
            // "business_sub_category_id" => 'required',
            "frame_image" => "nullable|mimes:jpg,png,jpeg",
        ]);

        if ($validation->fails()) {
            return back()->withErrors($validation)->withInput();
        } else {
            $category = BusinessFrame::find($request->get("id"));
            $category->business_category_id = $request->get("business_category_id");
            $category->business_sub_category_id = $request->get("business_sub_category_id");
            $category->save();

            if ($request->file("frame_image") && $request->file('frame_image')->isValid()) {
                $size = getimagesize($request->file('frame_image'));
                if ($size[0] > $size[1]) {
                    $type = "landscape";
                }
                if ($size[0] < $size[1]) {
                    $type = "portrait";
                }
                if ($size[0] == $size[1]) {
                    $type = "square";
                }

                $frame = BusinessFrame::find($request->get("id"));
                $frame->height = $size[1];
                $frame->width = $size[0];
                $frame->image_type = $type;
                $frame->aspect_ratio = $this->getAspectRatio($size[0], $size[1]);
                $frame->save();

                if (StorageSetting::getStorageSetting("storage") == "DigitalOcean") {
                    if ($request->file("frame_image")) {
                        $image = $request->file('frame_image');
                        $file = Str::uuid() . '.' . $image->getClientOriginalExtension();

                        $path = Storage::disk('spaces')->put('uploads/' . $file, file_get_contents($image), 'public');

                        $f = BusinessFrame::find($request->get("id"));
                        $f->frame_image = $file;
                        $f->save();
                    }
                } else {
                    $this->upload_image($request->file("frame_image"), "frame_image", $id);
                }
            }

            return redirect()->route('business-frame.index');
        }
    }

    public function destroy($id)
    {
        $businessFrame = BusinessFrame::find($id);
        if (StorageSetting::getStorageSetting("storage") == "DigitalOcean") {
            Storage::disk('spaces')->delete('uploads/' . $businessFrame->frame_image);
        } else {
            unlink('./uploads/' . $businessFrame->frame_image);
        }

        BusinessFrame::find($id)->delete();
        return redirect()->route('business-frame.index');
    }

    private function upload_image($file, $field, $id)
    {
        $destinationPath = './uploads';
        $extension = $file->getClientOriginalExtension();
        $fileName = Str::uuid() . '.' . $extension;
        $file->move($destinationPath, $fileName);

        $image = BusinessFrame::find($id);
        $image->$field = $fileName;
        $image->save();
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
        $divisor = $a;
        return $width / $divisor . ':' . $height / $divisor;
    }

    public function createCustomFramePurpose(Request $request)
    {
        $validation = Validator::make($request->all(), [
            "name" => 'required|string|max:255',
            "icon" => 'required|image|mimes:jpeg,png,jpg,gif|max:2048',
        ]);

        if ($validation->fails()) {
            return back()->withErrors($validation)->withInput();
        }

        $iconPath = null;
        if ($request->hasFile('icon')) {
            $imageName = (string) Str::uuid() . '.' . $request->file('icon')->getClientOriginalExtension();
            if (\App\Models\StorageSetting::getStorageSetting('storage') == 'DigitalOcean') {
                Storage::disk('spaces')->put('uploads/' . $imageName, file_get_contents($request->file('icon')), 'public');
            } else {
                $request->file('icon')->move(public_path('uploads/'), $imageName);
            }
            $iconPath = $imageName;
        }

        \App\Models\CustomFramePurpose::create([
            'name' => $request->name,
            'icon' => $iconPath,
            'status' => 1
        ]);

        return redirect()->back()->with('success', 'Purpose created successfully!');
    }

    public function storeCustomFramePurpose(Request $request)
    {
        $validation = Validator::make($request->all(), [
            "purpose_id" => 'required|exists:custom_frame_purposes,id',
            "ai_prompt" => "required|string",
            "data_requirement" => "required|in:single_column,basic_columns,full_row",
        ]);

        if ($validation->fails()) {
            return back()->withErrors($validation)->withInput();
        }

        $purpose = \App\Models\CustomFramePurpose::findOrFail($request->purpose_id);
        $purpose->update([
            'ai_prompt' => $request->ai_prompt,
            'data_requirement' => $request->data_requirement,
        ]);

        return redirect()->back()->with('success', 'Purpose AI configured successfully!');
    }

    public function deleteCustomFramePurpose($id)
    {
        \App\Models\CustomFramePurpose::findOrFail($id)->delete();
        return redirect()->back()->with('success', 'Purpose deleted successfully!');
    }

    public function updateCustomFramePurpose(Request $request, $id)
    {
        $purpose = \App\Models\CustomFramePurpose::findOrFail($id);

        $validation = Validator::make($request->all(), [
            "name" => 'required|string|max:255',
            "icon" => 'nullable|image|mimes:jpeg,png,jpg,gif|max:2048',
        ]);

        if ($validation->fails()) {
            return back()->withErrors($validation)->withInput();
        }

        $purpose->name = $request->name;

        if ($request->hasFile('icon')) {
            // Delete old icon if exists
            if ($purpose->icon) {
                if (\App\Models\StorageSetting::getStorageSetting('storage') == 'DigitalOcean') {
                    Storage::disk('spaces')->delete('uploads/' . $purpose->icon);
                } else {
                    $oldPath = public_path('uploads/' . $purpose->icon);
                    if (file_exists($oldPath)) {
                        @unlink($oldPath);
                    }
                }
            }

            $imageName = (string) Str::uuid() . '.' . $request->file('icon')->getClientOriginalExtension();
            if (\App\Models\StorageSetting::getStorageSetting('storage') == 'DigitalOcean') {
                Storage::disk('spaces')->put('uploads/' . $imageName, file_get_contents($request->file('icon')), 'public');
            } else {
                $request->file('icon')->move(public_path('uploads/'), $imageName);
            }
            $purpose->icon = $imageName;
        }

        $purpose->save();

        return redirect()->back()->with('success', 'Purpose updated successfully!');
    }

    public function storeCustomFrameImageType(Request $request)
    {
        $validation = Validator::make($request->all(), [
            "name" => 'required|string|max:255',
            "business_sub_category_ids" => "required|array",
        ]);

        if ($validation->fails()) {
            return back()->withErrors($validation)->withInput();
        }

        $imageType = \App\Models\CustomFrameImageType::create([
            'name' => $request->name,
            'status' => 1
        ]);

        $imageType->subCategories()->sync($request->business_sub_category_ids);

        return redirect()->back()->with('success', 'Image Type created successfully!');
    }

    public function storeBusinessCustomFrame(Request $request)
    {
        $validation = Validator::make($request->all(), [
            "custom_frame_purpose_id" => "required|exists:custom_frame_purposes,id",
            "custom_frame_image_type_id" => "required|exists:custom_frame_image_types,id",
            "zip_file" => "required|file|mimes:zip",
        ]);

        if ($validation->fails()) {
            return back()->withErrors($validation)->withInput();
        }

        if ($request->hasFile('zip_file')) {
            $zipFile = $request->file('zip_file');
            $fileName = Str::uuid() . '.zip';

            if (StorageSetting::getStorageSetting("storage") == "DigitalOcean") {
                Storage::disk('spaces')->put('uploads/custom_frames_zips/' . $fileName, file_get_contents($zipFile), 'public');
            } else {
                $destinationPath = public_path('uploads/custom_frames_zips');
                if (!file_exists($destinationPath)) {
                    mkdir($destinationPath, 0777, true);
                }
                $zipFile->move($destinationPath, $fileName);
            }

            // Extract the ZIP contents to uploads/template so the web preview can access the skin images
            $zipNameWithoutExt = pathinfo($fileName, PATHINFO_FILENAME);
            $extractPath = public_path('uploads/template/' . $zipNameWithoutExt);
            if (!file_exists($extractPath)) {
                mkdir($extractPath, 0777, true);
            }
            $zip = new \ZipArchive;
            $zipPathLocal = public_path('uploads/custom_frames_zips/' . $fileName);
            if ($zip->open($zipPathLocal) === TRUE) {
                $zip->extractTo($extractPath);
                $zip->close();
            }

            // Extract JSON from ZIP
            $jsonRules = null;
            $zip = new \ZipArchive;
            $zipPath = (StorageSetting::getStorageSetting("storage") == "DigitalOcean") ? 
                            Storage::disk('spaces')->url('uploads/custom_frames_zips/' . $fileName) : 
                            public_path('uploads/custom_frames_zips/' . $fileName);
            
            if (StorageSetting::getStorageSetting("storage") == "DigitalOcean") {
                $tempPath = public_path('temp_' . Str::uuid() . '.zip');
                file_put_contents($tempPath, file_get_contents($zipPath));
                $zipRes = $zip->open($tempPath);
            } else {
                $zipRes = $zip->open($zipPath);
            }

            if ($zipRes === TRUE) {
                for ($i = 0; $i < $zip->numFiles; $i++) {
                    $name = $zip->getNameIndex($i);
                    if (pathinfo($name, PATHINFO_EXTENSION) === 'json') {
                        $jsonContent = $zip->getFromIndex($i);
                        $jsonRules = json_encode(json_decode($jsonContent, true)); // minified format
                        break;
                    }
                }
                $zip->close();
            }

            if (isset($tempPath) && file_exists($tempPath)) {
                @unlink($tempPath);
            }

            // Validate the template structure before saving
            $validationWarnings = [];
            if (!$jsonRules) {
                $validationWarnings[] = 'No JSON configuration file found inside ZIP. Template will render as flat image only.';
            } else {
                $jsonData = json_decode($jsonRules, true);
                if (!$jsonData) {
                    $validationWarnings[] = 'JSON file found but could not be parsed. Check for syntax errors.';
                } else {
                    if (!isset($jsonData['layers']) || !is_array($jsonData['layers']) || count($jsonData['layers']) === 0) {
                        $validationWarnings[] = 'JSON has no "layers" array. Template will not render properly.';
                    } else {
                        // Check that skin image files exist for each image layer
                        $skinsPath = $extractPath;
                        // Find the actual skins directory
                        $skinsDirs = glob($skinsPath . '/*/skins/*', GLOB_ONLYDIR);
                        if (empty($skinsDirs)) {
                            $skinsDirs = glob($skinsPath . '/skins/*', GLOB_ONLYDIR);
                        }
                        if (!empty($skinsDirs)) {
                            $skinDir = $skinsDirs[0];
                            foreach ($jsonData['layers'] as $layer) {
                                if (($layer['type'] ?? '') === 'image') {
                                    $imgFile = basename($layer['src'] ?? '');
                                    if ($imgFile && !file_exists($skinDir . '/' . $imgFile)) {
                                        $validationWarnings[] = "Missing skin image: {$imgFile} (layer: {$layer['name']})";
                                    }
                                }
                            }
                        }

                        // Check for font files
                        if (isset($jsonData['layers'])) {
                            $fontsNeeded = [];
                            foreach ($jsonData['layers'] as $layer) {
                                if (($layer['type'] ?? '') === 'text' && isset($layer['font'])) {
                                    $fontsNeeded[$layer['font']] = true;
                                }
                            }
                            if (!empty($fontsNeeded)) {
                                $fontsDir = null;
                                $fontsDirPaths = glob($skinsPath . '/*/fonts', GLOB_ONLYDIR);
                                if (empty($fontsDirPaths)) {
                                    $fontsDirPaths = glob($skinsPath . '/fonts', GLOB_ONLYDIR);
                                }
                                if (!empty($fontsDirPaths)) {
                                    $fontsDir = $fontsDirPaths[0];
                                }
                                foreach (array_keys($fontsNeeded) as $fontName) {
                                    if (!$fontsDir || !file_exists($fontsDir . '/' . $fontName . '.ttf')) {
                                        $validationWarnings[] = "Missing font file: {$fontName}.ttf";
                                    }
                                }
                            }
                        }
                    }
                    if (!isset($jsonData['info']['width']) || !isset($jsonData['info']['height'])) {
                        $validationWarnings[] = 'JSON missing "info.width" or "info.height". Canvas may not size correctly.';
                    }
                }
            }

            $newFrame = \App\Models\BusinessCustomFrame::create([
                'custom_frame_purpose_id' => $request->custom_frame_purpose_id,
                'custom_frame_image_type_id' => $request->custom_frame_image_type_id,
                'original_zip_name' => $zipFile->getClientOriginalName(),
                'zip_file_path' => $fileName,
                'json_rules' => $jsonRules,
                'status' => 1
            ]);

            // Auto-dispatch AI generation batch for all users (runs in background queue)
            try {
                $purpose = \App\Models\CustomFramePurpose::find($request->custom_frame_purpose_id);
                if ($purpose && !empty($purpose->ai_prompt)) {
                    $batch = \App\Models\AiGenerationBatch::create([
                        'business_custom_frame_id' => $newFrame->id,
                        'status' => 'pending',
                    ]);
                    \App\Jobs\ProcessTemplateAiGeneration::dispatch($newFrame->id, $batch->id);
                }
            } catch (\Exception $e) {
                \Illuminate\Support\Facades\Log::warning("AI Batch dispatch failed: " . $e->getMessage());
            }

            $successMsg = 'Custom Frame uploaded successfully!';
            if (!empty($validationWarnings)) {
                $successMsg .= ' ⚠️ Warnings: ' . implode(' | ', $validationWarnings);
            }

            return redirect()->back()->with('success', $successMsg);
        }

        return back()->with('error', 'Please select a ZIP file.')->withInput();
    }

    public function deleteBusinessCustomFrame($id)
    {
        $frame = \App\Models\BusinessCustomFrame::findOrFail($id);
        
        if (StorageSetting::getStorageSetting("storage") == "DigitalOcean") {
            Storage::disk('spaces')->delete('uploads/custom_frames_zips/' . $frame->zip_file_path);
            // Delete extracted folder on DigitalOcean
            $zipNameWithoutExt = pathinfo($frame->zip_file_path, PATHINFO_FILENAME);
            Storage::disk('spaces')->deleteDirectory('uploads/template/' . $zipNameWithoutExt);
        } else {
            @unlink(public_path('uploads/custom_frames_zips/' . $frame->zip_file_path));
            // Delete extracted folder locally
            $zipNameWithoutExt = pathinfo($frame->zip_file_path, PATHINFO_FILENAME);
            \Illuminate\Support\Facades\File::deleteDirectory(public_path('uploads/template/' . $zipNameWithoutExt));
        }

        $frame->delete();
        return redirect()->back()->with('success', 'Custom Frame deleted successfully!');
    }
}
