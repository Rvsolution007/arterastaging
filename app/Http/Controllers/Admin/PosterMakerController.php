<?php

namespace App\Http\Controllers\Admin;

use App\Models\PosterMaker;
use Illuminate\Support\Str;
use Illuminate\Http\Request;
use App\Models\PosterCategory;
use App\Models\StorageSetting;
use App\Http\Controllers\Controller;
use Illuminate\Support\Facades\File;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Facades\Validator;

class PosterMakerController extends Controller
{
    public function __construct()
    {
        $this->middleware('permission:PosterMaker');
    }

    public function index(Request $request)
    {
        $query = PosterMaker::query();

        if ($request->has('req_address') && $request->req_address !== null) {
            $query->where('req_address', $request->req_address);
        }
        if ($request->has('req_email') && $request->req_email !== null) {
            $query->where('req_email', $request->req_email);
        }
        if ($request->has('req_phone') && $request->req_phone !== null) {
            $query->where('req_phone', $request->req_phone);
        }
        if ($request->has('req_website') && $request->req_website !== null) {
            $query->where('req_website', $request->req_website);
        }
        $index['data'] = $query->orderByRaw("
            IF(zip_name LIKE 'Frame_%', 0, 1) ASC,
            (
                CAST(SUBSTRING(SUBSTRING_INDEX(SUBSTRING_INDEX(zip_name, '_', -2), '_', 1), 1, 1) AS UNSIGNED) +
                CAST(SUBSTRING(SUBSTRING_INDEX(SUBSTRING_INDEX(zip_name, '_', -2), '_', 1), 2, 1) AS UNSIGNED) +
                CAST(SUBSTRING(SUBSTRING_INDEX(SUBSTRING_INDEX(zip_name, '_', -2), '_', 1), 3, 1) AS UNSIGNED) +
                CAST(SUBSTRING(SUBSTRING_INDEX(SUBSTRING_INDEX(zip_name, '_', -2), '_', 1), 4, 1) AS UNSIGNED)
            ) ASC,
            CAST(SUBSTRING_INDEX(SUBSTRING_INDEX(zip_name, '_', -2), '_', 1) AS UNSIGNED) DESC,
            CAST(SUBSTRING_INDEX(zip_name, '_', -1) AS UNSIGNED) ASC
        ")->paginate(12)->withQueryString();
        
        $index['req_address'] = $request->req_address;
        $index['req_email'] = $request->req_email;
        $index['req_phone'] = $request->req_phone;
        $index['req_website'] = $request->req_website;

        return view("poster_maker.index", $index);
    }

    public function create()
    {
        $index['category'] = PosterCategory::where('status', 1)->get();
        return view("poster_maker.create", $index);
    }

    public function store(Request $request)
    {
        $validation = Validator::make($request->all(), [
            'poster_category_id' => 'required',
            'template_type' => 'required',
            'zip' => 'required|mimes:zip',
            'post_thumb' => "nullable|mimes:jpg,png,jpeg",
        ]);

        if ($validation->fails()) {
            return back()->withErrors($validation)->withInput();
        } else {
            if (StorageSetting::getStorageSetting("storage") == "DigitalOcean") {
                $zip_name = "Frame" . date("YmdHis");
                $zip_original_name = pathinfo($request->file("zip")->getClientOriginalName(), PATHINFO_FILENAME);
                $extension = $request->file("zip")->getClientOriginalExtension();
                $file_name = $zip_name . "." . $extension;
                $request->file("zip")->move('./uploads/template', $file_name);

                $zipPath = public_path('uploads/template/' . $file_name);
                $validationResult = \App\Services\FontValidationService::validateZipFonts($zipPath);

                if ($validationResult !== true) {
                    unlink($zipPath);
                    $errorMessage = 'Upload failed! The following fonts are missing in the central system: ' . implode(', ', $validationResult) . '. Please upload them to the Font Section first.';
                    return back()->withErrors(['zip' => $errorMessage])->withInput();
                }

                File::makeDirectory('./uploads/template/' . $zip_name);

                $zip = new \ZipArchive;
                if ($zip->open('./uploads/template/' . $file_name) === TRUE) {
                    $zip->extractTo('./uploads/template/' . $zip_name);
                    $zip->close();
                }
                unlink('./uploads/template/' . $file_name);
                $this->organizeZip($zip_name, $zip_original_name);

                // $local = File::allFiles('./uploads/template/'.$zip_name);
                // foreach($local as $l)
                // {
                //     Storage::disk('spaces')->put('/uploads/template/'.$zip_name.'/'.$l->getrelativePathname(), file_get_contents($l), 'public');
                // }

                $fonts = File::allFiles('./uploads/template/' . $zip_name . '/fonts/');
                foreach ($fonts as $f) {
                    Storage::disk('spaces')->put('/uploads/template/' . $zip_name . '/fonts/' . $f->getrelativePathname(), file_get_contents($f), 'public');
                }

                $json = File::allFiles('./uploads/template/' . $zip_name . '/json/');
                foreach ($json as $j) {
                    Storage::disk('spaces')->put('/uploads/template/' . $zip_name . '/json/' . $j->getrelativePathname(), file_get_contents($j), 'public');
                }

                $logs = File::allFiles('./uploads/template/' . $zip_name . '/logs/');
                Storage::disk('spaces')->makeDirectory('/uploads/template/' . $zip_name . '/logs/');
                foreach ($logs as $log) {
                    Storage::disk('spaces')->put('/uploads/template/' . $zip_name . '/logs/' . $log->getrelativePathname(), file_get_contents($log), 'public');
                }

                $skins = File::allFiles('./uploads/template/' . $zip_name . '/skins/');
                foreach ($skins as $s) {
                    Storage::disk('spaces')->put('/uploads/template/' . $zip_name . '/skins/' . $s->getrelativePathname(), file_get_contents($s), 'public');
                }

                $this->rrmdir('./uploads/template/' . $zip_name);
            } else {
                $zip_name = "Frame" . date("YmdHis");
                $zip_original_name = pathinfo($request->file("zip")->getClientOriginalName(), PATHINFO_FILENAME);
                $extension = $request->file("zip")->getClientOriginalExtension();
                $file_name = $zip_name . "." . $extension;
                $request->file("zip")->move('./uploads/template', $file_name);

                $zipPath = public_path('uploads/template/' . $file_name);
                $validationResult = \App\Services\FontValidationService::validateZipFonts($zipPath);

                if ($validationResult !== true) {
                    unlink($zipPath);
                    $errorMessage = 'Upload failed! The following fonts are missing in the central system: ' . implode(', ', $validationResult) . '. Please upload them to the Font Section first.';
                    return back()->withErrors(['zip' => $errorMessage])->withInput();
                }

                File::makeDirectory('./uploads/template/' . $zip_name);

                $zip = new \ZipArchive;
                if ($zip->open('./uploads/template/' . $file_name) === TRUE) {
                    $zip->extractTo('./uploads/template/' . $zip_name);
                    $zip->close();
                }
                unlink('./uploads/template/' . $file_name);
                $this->organizeZip($zip_name, $zip_original_name);
            }

            $id = PosterMaker::create([
                "poster_category_id" => $request->get("poster_category_id"),
                "template_type" => $request->get("template_type"),
                "zip_name" => $zip_name,
                "theme" => $request->get("theme", "all"),
                "req_address" => $request->get("req_address", 0),
                "req_email" => $request->get("req_email", 0),
                "req_phone" => $request->get("req_phone", 0),
                "req_website" => $request->get("req_website", 0),
            ])->id;

            if (StorageSetting::getStorageSetting("storage") == "DigitalOcean") {
                if ($request->file("post_thumb") && $request->file('post_thumb')->isValid()) {
                    $image = $request->file('post_thumb');
                    $file = Str::uuid() . '.' . $image->getClientOriginalExtension();

                    $path = Storage::disk('spaces')->put('uploads/' . $file, file_get_contents($image), 'public');

                    $poster = PosterMaker::find($id);
                    $poster->post_thumb = $file;
                    $poster->save();
                }
            } else {
                if ($request->file("post_thumb") && $request->file('post_thumb')->isValid()) {
                    $this->upload_image($request->file("post_thumb"), "post_thumb", $id);
                }
            }

            return redirect()->route("Frame.index");
        }
    }

    public function poster_maker_frame_type(Request $request)
    {
        $poster = PosterMaker::find($request->get("id"));
        $poster->paid = ($request->get("checked") == "true") ? 1 : 0;
        $poster->save();

        if ($poster->paid == 1) {
            return 1;
        } else {
            return 0;
        }
    }

    public function edit($id)
    {
        $poster = PosterMaker::find($id);
        $category = PosterCategory::where('status', 1)->get();
        return view("poster_maker.edit", compact("poster", "category"));
    }

    public function update(Request $request, $id)
    {
        $validation = Validator::make($request->all(), [
            'poster_category_id' => 'required',
            'template_type' => 'required',
            'zip' => 'nullable|mimes:zip',
            'post_thumb' => "nullable|mimes:jpg,png,jpeg",
        ]);

        if ($validation->fails()) {
            return back()->withErrors($validation)->withInput();
        } else {
            $poster = PosterMaker::find($request->get("id"));
            $poster->poster_category_id = $request->get("poster_category_id");
            $poster->template_type = $request->get("template_type");
            $poster->theme = $request->get("theme", "all");
            $poster->req_address = $request->get("req_address", 0);
            $poster->req_email = $request->get("req_email", 0);
            $poster->req_phone = $request->get("req_phone", 0);
            $poster->req_website = $request->get("req_website", 0);
            $poster->save();

            if (StorageSetting::getStorageSetting("storage") == "DigitalOcean") {
                if ($request->file("zip")) {
                    $this->rrmdir('./uploads/template/' . $poster->zip_name);
                    $zip_name = "Frame" . date("YmdHis");
                    $zip_original_name = pathinfo($request->file("zip")->getClientOriginalName(), PATHINFO_FILENAME);
                    $extension = $request->file("zip")->getClientOriginalExtension();
                    $file_name = $zip_name . "." . $extension;
                    $request->file("zip")->move('./uploads/template', $file_name);

                    $zipPath = public_path('uploads/template/' . $file_name);
                    $validationResult = \App\Services\FontValidationService::validateZipFonts($zipPath);

                    if ($validationResult !== true) {
                        unlink($zipPath);
                        $errorMessage = 'Upload failed! The following fonts are missing in the central system: ' . implode(', ', $validationResult) . '. Please upload them to the Font Section first.';
                        return back()->withErrors(['zip' => $errorMessage])->withInput();
                    }

                    File::makeDirectory('./uploads/template/' . $zip_name);

                    $zip = new \ZipArchive;
                    if ($zip->open('./uploads/template/' . $file_name) === TRUE) {
                        $zip->extractTo('./uploads/template/' . $zip_name);
                        $zip->close();
                    }
                    unlink('./uploads/template/' . $file_name);
                    $this->organizeZip($zip_name, $zip_original_name);

                    // $local = File::allFiles('./uploads/template/'.$zip_name);
                    // foreach($local as $l)
                    // {
                    //     Storage::disk('spaces')->put('/uploads/template/'.$zip_name.'/'.$l->getrelativePathname(), file_get_contents($l), 'public');
                    // }

                    $fonts = File::allFiles('./uploads/template/' . $zip_name . '/fonts/');
                    foreach ($fonts as $f) {
                        Storage::disk('spaces')->put('/uploads/template/' . $zip_name . '/fonts/' . $f->getrelativePathname(), file_get_contents($f), 'public');
                    }

                    $json = File::allFiles('./uploads/template/' . $zip_name . '/json/');
                    foreach ($json as $j) {
                        Storage::disk('spaces')->put('/uploads/template/' . $zip_name . '/json/' . $j->getrelativePathname(), file_get_contents($j), 'public');
                    }

                    $logs = File::allFiles('./uploads/template/' . $zip_name . '/logs/');
                    Storage::disk('spaces')->makeDirectory('/uploads/template/' . $zip_name . '/logs/');
                    foreach ($logs as $log) {
                        Storage::disk('spaces')->put('/uploads/template/' . $zip_name . '/logs/' . $log->getrelativePathname(), file_get_contents($log), 'public');
                    }

                    $skins = File::allFiles('./uploads/template/' . $zip_name . '/skins/');
                    foreach ($skins as $s) {
                        Storage::disk('spaces')->put('/uploads/template/' . $zip_name . '/skins/' . $s->getrelativePathname(), file_get_contents($s), 'public');
                    }

                    $this->rrmdir('./uploads/template/' . $zip_name);

                    $poster_one = PosterMaker::find($request->get("id"));
                    $poster_one->zip_name = $zip_name;
                    $poster_one->save();
                }
            } else {
                if ($request->file("zip")) {
                    $this->rrmdir('./uploads/template/' . $poster->zip_name);
                    $zip_name = "Frame" . date("YmdHis");
                    $zip_original_name = pathinfo($request->file("zip")->getClientOriginalName(), PATHINFO_FILENAME);
                    $extension = $request->file("zip")->getClientOriginalExtension();
                    $file_name = $zip_name . "." . $extension;
                    $request->file("zip")->move('./uploads/template', $file_name);

                    $zipPath = public_path('uploads/template/' . $file_name);
                    $validationResult = \App\Services\FontValidationService::validateZipFonts($zipPath);

                    if ($validationResult !== true) {
                        unlink($zipPath);
                        $errorMessage = 'Upload failed! The following fonts are missing in the central system: ' . implode(', ', $validationResult) . '. Please upload them to the Font Section first.';
                        return back()->withErrors(['zip' => $errorMessage])->withInput();
                    }

                    File::makeDirectory('./uploads/template/' . $zip_name);

                    $zip = new \ZipArchive;
                    if ($zip->open('./uploads/template/' . $file_name) === TRUE) {
                        $zip->extractTo('./uploads/template/' . $zip_name);
                        $zip->close();
                    }
                    unlink('./uploads/template/' . $file_name);
                    $this->organizeZip($zip_name, $zip_original_name);

                    $poster_one = PosterMaker::find($request->get("id"));
                    $poster_one->zip_name = $zip_name;
                    $poster_one->save();
                }
            }

            if (StorageSetting::getStorageSetting("storage") == "DigitalOcean") {
                if ($request->file("post_thumb") && $request->file('post_thumb')->isValid()) {
                    $image = $request->file('post_thumb');
                    $file = Str::uuid() . '.' . $image->getClientOriginalExtension();

                    $path = Storage::disk('spaces')->put('uploads/' . $file, file_get_contents($image), 'public');

                    $poster = PosterMaker::find($request->get("id"));
                    $poster->post_thumb = $file;
                    $poster->save();
                }
            } else {
                if ($request->file("post_thumb") && $request->file('post_thumb')->isValid()) {
                    $this->upload_image($request->file("post_thumb"), "post_thumb", $id);
                }
            }

            return redirect()->route('Frame.index');
        }
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


    public function destroy($id)
    {
        $posterMaker = PosterMaker::find($id);
        
        if (!$posterMaker) {
            return redirect()->route('Frame.index');
        }

        if ($posterMaker->post_thumb) {
            if (StorageSetting::getStorageSetting("storage") == "DigitalOcean") {
                Storage::disk('spaces')->delete('uploads/' . $posterMaker->post_thumb);
            } else {
                if (file_exists(public_path('uploads/') . $posterMaker->post_thumb)) {
                    unlink(public_path('uploads/') . $posterMaker->post_thumb);
                }
            }
        }

        if (!empty($posterMaker->zip_name)) {
            $this->rrmdir('./uploads/template/' . $posterMaker->zip_name);
        }
        
        $posterMaker->delete();

        return redirect()->route('Frame.index');
    }

    private function upload_image($file, $field, $id)
    {
        $destinationPath = public_path('uploads');
        $extension = $file->getClientOriginalExtension();
        $fileName = Str::uuid() . '.' . $extension;
        $file->move($destinationPath, $fileName);

        $image = PosterMaker::find($id);
        $image->$field = $fileName;
        $image->save();
    }
    private function organizeZip($zip_name, $zip_original_name)
    {
        $extractPath = './uploads/template/' . $zip_name;
        $sourcePath = $extractPath . '/' . $zip_original_name;

        if (!File::isDirectory($sourcePath)) {
            // Check for any single directory
            $directories = File::directories($extractPath);
            if (count($directories) === 1) {
                $sourcePath = $directories[0];
            } else {
                // Already in root or mixed structure we can't handle easily
                return;
            }
        }

        if ($sourcePath !== $extractPath) {
            if (File::exists($sourcePath . '/fonts'))
                rename($sourcePath . '/fonts', $extractPath . '/fonts');
            if (File::exists($sourcePath . '/json'))
                rename($sourcePath . '/json', $extractPath . '/json');
            if (File::exists($sourcePath . '/logs'))
                rename($sourcePath . '/logs', $extractPath . '/logs');
            if (File::exists($sourcePath . '/skins'))
                rename($sourcePath . '/skins', $extractPath . '/skins');
            $this->rrmdir($sourcePath);
        }
    }
    public function duplicate(Request $request)
    {
        $request->validate([
            'id' => 'required|exists:poster_maker,id',
            'zip_name' => 'required|string|unique:poster_maker,zip_name'
        ], [
            'zip_name.unique' => 'same name ki duplicate publish nhi hogi, kripya unique name dalein.'
        ]);

        $original = PosterMaker::find($request->id);
        if (!$original) {
            return response()->json(['success' => false, 'message' => 'Frame not found']);
        }

        $newZipName = $request->zip_name;

        // Duplicate the DB record
        $newFrame = $original->replicate();
        $newFrame->zip_name = $newZipName;
        // Optionally duplicate the thumb file
        if ($original->post_thumb && File::exists(public_path('uploads/' . $original->post_thumb))) {
            $ext = pathinfo($original->post_thumb, PATHINFO_EXTENSION);
            $newThumbName = \Illuminate\Support\Str::uuid() . '.' . $ext;
            File::copy(public_path('uploads/' . $original->post_thumb), public_path('uploads/' . $newThumbName));
            $newFrame->post_thumb = $newThumbName;
            
            if (StorageSetting::getStorageSetting("storage") == "DigitalOcean") {
                Storage::disk('spaces')->put('uploads/' . $newThumbName, file_get_contents(public_path('uploads/' . $newThumbName)), 'public');
            }
        }
        $newFrame->save();

        // Duplicate template folder
        $oldTemplatePath = public_path('uploads/template/' . $original->zip_name);
        $newTemplatePath = public_path('uploads/template/' . $newZipName);

        if (File::exists($oldTemplatePath)) {
            File::copyDirectory($oldTemplatePath, $newTemplatePath);
            
            if (StorageSetting::getStorageSetting("storage") == "DigitalOcean") {
                // To avoid downloading all S3 files, we'll just push local copies to S3
                $localFiles = File::allFiles($newTemplatePath);
                foreach ($localFiles as $file) {
                    $relPath = $file->getRelativePathname();
                    Storage::disk('spaces')->put('/uploads/template/' . $newZipName . '/' . str_replace('\\', '/', $relPath), file_get_contents($file), 'public');
                }
            }
        }

        // If it was a web template, duplicate EditorTemplate too so it remains editable in Web Editor natively
        if (str_starts_with($original->zip_name, 'Template_')) {
            $oldUuid = str_replace(['Template_', '.zip'], '', $original->zip_name);
            $oldEditorTpl = \App\Models\EditorTemplate::where('uuid', $oldUuid)->first();
            
            if ($oldEditorTpl && str_starts_with($newZipName, 'Template_')) {
                $newUuid = str_replace(['Template_', '.zip'], '', $newZipName);
                $newEditorTpl = $oldEditorTpl->replicate();
                $newEditorTpl->uuid = $newUuid;
                $newEditorTpl->title = $oldEditorTpl->title . ' (Copy)';
                $newEditorTpl->save();

                $oldEditorPath = public_path('uploads/editor/templates/' . $oldUuid);
                $newEditorPath = public_path('uploads/editor/templates/' . $newUuid);
                if (File::exists($oldEditorPath)) {
                    File::copyDirectory($oldEditorPath, $newEditorPath);
                }
            }
        }

        return response()->json(['success' => true, 'message' => 'Frame duplicated successfully.']);
    }

    public function bulkDelete(Request $request)
    {
        $ids = $request->input('ids');
        if (!is_array($ids) || empty($ids)) {
            return response()->json(['success' => false, 'message' => 'No frames selected.']);
        }

        foreach ($ids as $id) {
            $posterMaker = PosterMaker::find($id);
            if ($posterMaker) {
                // Delete thumbnail
                if (StorageSetting::getStorageSetting("storage") == "DigitalOcean") {
                    Storage::disk('spaces')->delete('uploads/' . $posterMaker->post_thumb);
                } else {
                    if (file_exists(public_path('uploads/') . $posterMaker->post_thumb)) {
                        unlink(public_path('uploads/') . $posterMaker->post_thumb);
                    }
                }
                
                // Delete ZIP directory
                $this->rrmdir('./uploads/template/' . $posterMaker->zip_name);
                
                // Delete record
                $posterMaker->delete();
            }
        }

        return response()->json(['success' => true, 'message' => 'Selected frames deleted successfully.']);
    }

    public function exportFrames(Request $request)
    {
        $ids = $request->input('ids');
        if ($ids) {
            $idArray = explode(',', $ids);
            $frames = \App\Models\PosterMaker::with('poster_category')->whereIn('id', $idArray)->get();
        } else {
            $frames = \App\Models\PosterMaker::with('poster_category')->get();
        }
        
        if ($frames->isEmpty()) {
            return redirect()->back()->with('error', 'No frames found to export.');
        }

        $exportData = [];
        $zipFilePath = public_path('uploads/exported_frames_'.time().'.zip');
        
        $zip = new \ZipArchive();
        if ($zip->open($zipFilePath, \ZipArchive::CREATE) === TRUE) {
            foreach ($frames as $frame) {
                $frameArr = $frame->toArray();
                
                // Fetch EditorTemplate schema if it exists
                if (str_starts_with($frame->zip_name, 'Template_')) {
                    $uuid = str_replace(['Template_', '.zip'], '', $frame->zip_name);
                    $editorTemplate = \App\Models\EditorTemplate::where('uuid', $uuid)->first();
                    if ($editorTemplate) {
                        $frameArr['_schema_json'] = $editorTemplate->schema_json;
                        $frameArr['_legacy_json'] = $editorTemplate->legacy_json;
                        $frameArr['_editor_uuid'] = $editorTemplate->uuid;
                    }
                }
                $exportData[] = $frameArr;
                
                // Add thumbnail to archive
                $thumbContent = null;
                $thumbExt = 'webp';
                if ($frame->post_thumb) {
                    $thumbExt = pathinfo($frame->post_thumb, PATHINFO_EXTENSION);
                    if (!$thumbExt) $thumbExt = 'webp';
                    
                    if(\App\Models\StorageSetting::getStorageSetting("storage") == "DigitalOcean") {
                        if (\Illuminate\Support\Facades\Storage::disk('spaces')->exists('uploads/' . $frame->post_thumb)) {
                            $thumbContent = \Illuminate\Support\Facades\Storage::disk('spaces')->get('uploads/' . $frame->post_thumb);
                            $zip->addFromString('thumbnails/' . basename($frame->post_thumb), $thumbContent);
                        }
                    } else {
                        $thumbLocalPath = public_path('uploads/' . $frame->post_thumb);
                        if (file_exists($thumbLocalPath)) {
                            $thumbContent = file_get_contents($thumbLocalPath);
                            $zip->addFile($thumbLocalPath, 'thumbnails/' . basename($frame->post_thumb));
                        }
                    }
                }
                
                // Add zip to archive
                $templateZipName = $frame->zip_name . '.zip';
                $tmpZipPath = public_path('uploads/temp_export_modify_' . time() . '_' . $templateZipName);
                $hasValidZip = false;

                if(\App\Models\StorageSetting::getStorageSetting("storage") == "DigitalOcean") {
                    if (\Illuminate\Support\Facades\Storage::disk('spaces')->exists('uploads/custom_frames_zips/' . $templateZipName)) {
                        $fileContent = \Illuminate\Support\Facades\Storage::disk('spaces')->get('uploads/custom_frames_zips/' . $templateZipName);
                        file_put_contents($tmpZipPath, $fileContent);
                        $hasValidZip = true;
                    }
                } else {
                    $localPath = public_path('uploads/custom_frames_zips/' . $templateZipName);
                    if (file_exists($localPath)) {
                        copy($localPath, $tmpZipPath);
                        $hasValidZip = true;
                    } else {
                        // Fallback for legacy frames: if zip doesn't exist, check template folder
                        $templateDirPath = public_path('uploads/template/' . $frame->zip_name);
                        if (is_dir($templateDirPath)) {
                            $tempZip = new \ZipArchive();
                            if ($tempZip->open($tmpZipPath, \ZipArchive::CREATE | \ZipArchive::OVERWRITE) === TRUE) {
                                $files = new \RecursiveIteratorIterator(new \RecursiveDirectoryIterator($templateDirPath), \RecursiveIteratorIterator::LEAVES_ONLY);
                                foreach ($files as $name => $file) {
                                    if (!$file->isDir()) {
                                        $filePath = $file->getRealPath();
                                        $relativePath = substr($filePath, strlen($templateDirPath) + 1);
                                        $tempZip->addFile($filePath, $relativePath);
                                    }
                                }
                                $tempZip->close();
                                $hasValidZip = true;
                            }
                        }
                    }
                }

                // Inject the preview into the frame's zip if we have thumbnail content
                if ($hasValidZip && $thumbContent) {
                    $modifyZip = new \ZipArchive();
                    if ($modifyZip->open($tmpZipPath) === TRUE) {
                        // Always inject as preview.webp or preview.png based on extension
                        $modifyZip->addFromString('preview.' . $thumbExt, $thumbContent);
                        // Ensure legacy apps that look for exactly preview.png or preview.webp find it
                        if ($thumbExt !== 'webp') $modifyZip->addFromString('preview.webp', $thumbContent);
                        $modifyZip->close();
                    }
                }

                if ($hasValidZip) {
                    $zip->addFile($tmpZipPath, 'templates/' . $templateZipName);
                    // Register the temp file for deletion after ZipArchive is closed using register_shutdown_function
                    register_shutdown_function(function() use ($tmpZipPath) {
                        @unlink($tmpZipPath);
                    });
                }
            }
            
            $zip->addFromString('data.json', json_encode($exportData, JSON_PRETTY_PRINT));
            $zip->close();
            
            return response()->download($zipFilePath)->deleteFileAfterSend(true);
        }
        
        return redirect()->back()->with('error', 'Could not create export zip file.');
    }

    public function importFrames(Request $request)
    {
        $request->validate([
            'import_file' => 'required|mimes:zip'
        ]);

        $zipFile = $request->file('import_file');
        $zip = new \ZipArchive();
        $tempDir = public_path('uploads/temp_poster_import_'.time());

        if ($zip->open($zipFile->getPathname()) === TRUE) {
            $zip->extractTo($tempDir);
            $zip->close();

            if (file_exists($tempDir . '/data.json')) {
                $jsonData = file_get_contents($tempDir . '/data.json');
                $frames = json_decode($jsonData, true);
                
                $importedCount = 0;
                $skippedCount = 0;
                $skippedNames = [];

                foreach ($frames as $frameData) {
                    // Check if already exists
                    $existing = \App\Models\PosterMaker::where('zip_name', $frameData['zip_name'])->first();
                    if (!$existing) {
                        // Create poster_category if missing
                        $categoryId = $frameData['poster_category_id'];
                        if (isset($frameData['poster_category'])) {
                            $category = \App\Models\PosterCategory::firstOrCreate(
                                ['name' => $frameData['poster_category']['name']]
                            );
                            $categoryId = $category->id;
                        }

                        // Copy template zip
                        $templateZipName = $frameData['zip_name'] . '.zip';
                        $sourcePath = $tempDir . '/templates/' . $templateZipName;
                        if (file_exists($sourcePath)) {
                            if(\App\Models\StorageSetting::getStorageSetting("storage") == "DigitalOcean") {
                                \Illuminate\Support\Facades\Storage::disk('spaces')->put('uploads/custom_frames_zips/' . $templateZipName, file_get_contents($sourcePath), 'public');
                            } else {
                                if (!file_exists(public_path('uploads/custom_frames_zips'))) {
                                    mkdir(public_path('uploads/custom_frames_zips'), 0777, true);
                                }
                                copy($sourcePath, public_path('uploads/custom_frames_zips/' . $templateZipName));
                            }
                            
                            // Unzip to template folder
                            $innerZip = new \ZipArchive();
                            $zipNameWithoutExt = $frameData['zip_name'];
                            $extractPath = public_path('uploads/template/' . $zipNameWithoutExt);
                            if ($innerZip->open($sourcePath) === TRUE) {
                                if (!file_exists($extractPath)) {
                                    mkdir($extractPath, 0777, true);
                                }
                                $innerZip->extractTo($extractPath);
                                $innerZip->close();

                                // Check for extracted preview image BEFORE DO upload deletes local files
                                $previewPath = null;
                                if (file_exists($extractPath . '/preview.webp')) {
                                    $previewPath = 'template/' . $zipNameWithoutExt . '/preview.webp';
                                } elseif (file_exists($extractPath . '/preview.png')) {
                                    $previewPath = 'template/' . $zipNameWithoutExt . '/preview.png';
                                } elseif (file_exists($extractPath . '/preview.jpg')) {
                                    $previewPath = 'template/' . $zipNameWithoutExt . '/preview.jpg';
                                }

                                if(\App\Models\StorageSetting::getStorageSetting("storage") == "DigitalOcean") {
                                    // Upload extracted files to space
                                    $files = \Illuminate\Support\Facades\File::allFiles($extractPath);
                                    foreach($files as $file) {
                                        $relativePath = str_replace($extractPath.'\\', '', $file->getPathname());
                                        $relativePath = str_replace($extractPath.'/', '', $relativePath);
                                        $relativePath = str_replace('\\', '/', $relativePath);
                                        \Illuminate\Support\Facades\Storage::disk('spaces')->put('uploads/template/'.$zipNameWithoutExt.'/'.$relativePath, file_get_contents($file->getPathname()), 'public');
                                    }
                                    // Remove local folder
                                    \Illuminate\Support\Facades\File::deleteDirectory($extractPath);
                                }
                                }

                            // Insert into database
                            $posterMaker = \App\Models\PosterMaker::create([
                                'poster_category_id' => $categoryId,
                                'template_type' => $frameData['template_type'],
                                'zip_name' => $frameData['zip_name'],
                                'post_thumb' => $previewPath ? $previewPath : $frameData['post_thumb'],
                                'theme' => $frameData['theme'] ?? 'all',
                                'req_address' => $frameData['req_address'] ?? 0,
                                'req_email' => $frameData['req_email'] ?? 0,
                                'req_phone' => $frameData['req_phone'] ?? 0,
                                'req_website' => $frameData['req_website'] ?? 0,
                                'paid' => $frameData['paid'] ?? 1
                            ]);
                            
                            // Restore EditorTemplate schema if included
                            if (isset($frameData['_schema_json']) && isset($frameData['_editor_uuid'])) {
                                $uuid = $frameData['_editor_uuid'];
                                $existingTpl = \App\Models\EditorTemplate::where('uuid', $uuid)->first();
                                if (!$existingTpl) {
                                    \App\Models\EditorTemplate::create([
                                        'uuid' => $uuid,
                                        'title' => $frameData['zip_name'],
                                        'canvas_width' => $frameData['_schema_json']['canvas']['width'] ?? 1080,
                                        'canvas_height' => $frameData['_schema_json']['canvas']['height'] ?? 1080,
                                        'schema_json' => $frameData['_schema_json'],
                                        'legacy_json' => $frameData['_legacy_json'] ?? null,
                                        'status' => 'published',
                                        'author_id' => auth()->id() ?? 1,
                                    ]);
                                }
                            }
                            
                            // Restore thumbnail
                            if (!empty($frameData['post_thumb'])) {
                                $thumbSource = $tempDir . '/thumbnails/' . basename($frameData['post_thumb']);
                                if (file_exists($thumbSource)) {
                                    if(\App\Models\StorageSetting::getStorageSetting("storage") == "DigitalOcean") {
                                        \Illuminate\Support\Facades\Storage::disk('spaces')->put('uploads/' . $frameData['post_thumb'], file_get_contents($thumbSource), 'public');
                                    } else {
                                        $thumbDest = public_path('uploads/' . $frameData['post_thumb']);
                                        $thumbDir = dirname($thumbDest);
                                        if (!file_exists($thumbDir)) mkdir($thumbDir, 0777, true);
                                        copy($thumbSource, $thumbDest);
                                    }
                                }
                            }

                            // Verify thumbnail actually exists; if not, generate from template skins
                            $thumbOk = false;
                            $currentThumb = $posterMaker->post_thumb;
                            if ($currentThumb) {
                                if (\App\Models\StorageSetting::getStorageSetting("storage") == "DigitalOcean") {
                                    $thumbOk = \Illuminate\Support\Facades\Storage::disk('spaces')->exists('uploads/' . $currentThumb);
                                } else {
                                    $thumbOk = file_exists(public_path('uploads/' . $currentThumb));
                                }
                            }

                            if (!$thumbOk) {
                                // Try to generate preview from extracted template skin images
                                $tplFolder = public_path('uploads/template/' . $frameData['zip_name']);
                                $skinFolder = $tplFolder . '/skins/' . $frameData['zip_name'];
                                $generatedPreview = null;

                                if (is_dir($skinFolder)) {
                                    // Find the first non-shape PNG (asset_ files are frame overlays)
                                    $skinFiles = glob($skinFolder . '/asset_*.png');
                                    if (empty($skinFiles)) {
                                        // Fallback to any PNG
                                        $skinFiles = glob($skinFolder . '/*.png');
                                    }
                                    if (!empty($skinFiles)) {
                                        $sourceImg = $skinFiles[0];
                                        $previewDest = $tplFolder . '/preview.webp';
                                        
                                        // Convert/copy to preview.webp
                                        if (function_exists('imagecreatefrompng')) {
                                            $img = @imagecreatefrompng($sourceImg);
                                            if ($img) {
                                                imagewebp($img, $previewDest, 80);
                                                imagedestroy($img);
                                                $generatedPreview = 'template/' . $frameData['zip_name'] . '/preview.webp';
                                            }
                                        }
                                        
                                        // Fallback: just copy the PNG as preview
                                        if (!$generatedPreview) {
                                            $previewDestPng = $tplFolder . '/preview.png';
                                            copy($sourceImg, $previewDestPng);
                                            $generatedPreview = 'template/' . $frameData['zip_name'] . '/preview.png';
                                        }
                                    }
                                }

                                if ($generatedPreview) {
                                    // Upload to DO if needed
                                    if (\App\Models\StorageSetting::getStorageSetting("storage") == "DigitalOcean") {
                                        $localPreview = public_path('uploads/' . $generatedPreview);
                                        if (file_exists($localPreview)) {
                                            \Illuminate\Support\Facades\Storage::disk('spaces')->put('uploads/' . $generatedPreview, file_get_contents($localPreview), 'public');
                                        }
                                    }
                                    $posterMaker->post_thumb = $generatedPreview;
                                    $posterMaker->save();
                                    \Log::info("[importFrames] Auto-generated preview for {$frameData['zip_name']}: $generatedPreview");
                                } else {
                                    \Log::warning("[importFrames] No preview could be generated for {$frameData['zip_name']}");
                                }
                            }
                            $importedCount++;
                        }
                    } else {
                        $skippedCount++;
                        $skippedNames[] = $frameData['zip_name'];
                    }
                }

                if (file_exists($tempDir)) {
                    \Illuminate\Support\Facades\File::deleteDirectory($tempDir);
                }
                
                $msg = "$importedCount frames imported successfully.";
                if ($skippedCount > 0) {
                    $msg .= " $skippedCount frames skipped due to duplicate names: " . implode(', ', $skippedNames);
                    return redirect()->back()->with('warning', $msg);
                }
                return redirect()->back()->with('success', $msg);
            }
        }
        return redirect()->back()->with('error', 'Invalid import file.');
    }

    public function versionControl(Request $request)
    {
        $query = PosterMaker::query()->with('poster_category');

        // Version filter
        if ($request->has('version') && $request->version !== null && $request->version !== '') {
            $query->where('render_version', $request->version);
        }

        // Search filter
        if ($request->has('search') && $request->search !== null && $request->search !== '') {
            $query->where('zip_name', 'like', '%' . $request->search . '%');
        }
        $data = $query->orderByRaw("
            IF(zip_name LIKE 'Frame_%', 0, 1) ASC,
            (
                CAST(SUBSTRING(SUBSTRING_INDEX(SUBSTRING_INDEX(zip_name, '_', -2), '_', 1), 1, 1) AS UNSIGNED) +
                CAST(SUBSTRING(SUBSTRING_INDEX(SUBSTRING_INDEX(zip_name, '_', -2), '_', 1), 2, 1) AS UNSIGNED) +
                CAST(SUBSTRING(SUBSTRING_INDEX(SUBSTRING_INDEX(zip_name, '_', -2), '_', 1), 3, 1) AS UNSIGNED) +
                CAST(SUBSTRING(SUBSTRING_INDEX(SUBSTRING_INDEX(zip_name, '_', -2), '_', 1), 4, 1) AS UNSIGNED)
            ) ASC,
            CAST(SUBSTRING_INDEX(SUBSTRING_INDEX(zip_name, '_', -2), '_', 1) AS UNSIGNED) DESC,
            CAST(SUBSTRING_INDEX(zip_name, '_', -1) AS UNSIGNED) ASC
        ")->paginate(50)->withQueryString();

        // Get distinct versions for filter dropdown
        $versions = PosterMaker::select('render_version')
            ->distinct()
            ->orderBy('render_version')
            ->pluck('render_version');

        // Current max version (from the JS constant)
        $currentMaxVersion = 9;

        return view('poster_maker.version_control', [
            'data' => $data,
            'versions' => $versions,
            'currentMaxVersion' => $currentMaxVersion,
            'selectedVersion' => $request->version,
            'searchQuery' => $request->search,
        ]);
    }

    public function bulkMigrateVersion(Request $request)
    {
        $request->validate([
            'ids' => 'required|array',
            'ids.*' => 'integer|exists:poster_maker,id',
            'target_version' => 'required|string',
            'upgrade_icons' => 'nullable|boolean'
        ]);

        $targetVersion = $request->target_version;
        $upgradeIcons = filter_var($request->upgrade_icons, FILTER_VALIDATE_BOOLEAN);
        $forceCommit = filter_var($request->force_commit, FILTER_VALIDATE_BOOLEAN);
        $errors = [];

        $validator = new \App\Services\DualEngineValidator();
        $validationResults = [];
        $autoCommitted = [];
        $needsReview = [];

        foreach ($request->ids as $id) {
            try {
                $frame = PosterMaker::findOrFail($id);
                $currentVersion = $frame->render_version ?? 1;
                $targetVersionInt = ($targetVersion !== 'none') ? (int)$targetVersion : $currentVersion;

                // Skip if already at target version
                if ($currentVersion === $targetVersionInt) {
                    $autoCommitted[] = ['id' => $id, 'name' => $frame->zip_name, 'status' => 'ALREADY_SAME'];
                    continue;
                }

                $jsonPath = public_path("uploads/template/{$frame->zip_name}/json/{$frame->zip_name}.json");

                if (!file_exists($jsonPath)) {
                    $errors[] = "Frame #{$id} ({$frame->zip_name}): JSON file not found";
                    continue;
                }

                $json = json_decode(file_get_contents($jsonPath), true);
                if (!$json) {
                    $errors[] = "Frame #{$id}: Invalid JSON";
                    continue;
                }

                // Run Dual Engine Validation
                $result = $validator->validate($id, $json, $currentVersion, $targetVersionInt);
                $result['zip_name'] = $frame->zip_name;

                $validationResults[] = $result;

                if ($forceCommit || $result['status'] === 'MATCH' || $result['status'] === 'MINOR_DRIFT') {
                    $jsonModified = false;

                    // Legacy Icon Upgrade Logic
                    $vectorIconsMap = [];
                    $iconKeywordMap = [];
                    $upgradeLayerToVector = null;

                    if ($upgradeIcons) {
                        $vectorIconsMap = [
                            'facebook' => [
                                'iconName' => 'ic:baseline-facebook',
                                'svgPath' => 'M22 12c0-5.52-4.48-10-10-10S2 6.48 2 12c0 4.84 3.44 8.87 8 9.8V15H8v-3h2V9.5C10 7.57 11.57 6 13.5 6H16v3h-2c-.55 0-1 .45-1 1v2h3v3h-3v6.95c5.05-.5 9-4.76 9-9.95',
                                'originalSvg' => '<svg xmlns="http://www.w3.org/2000/svg" width="1em" height="1em" viewBox="0 0 24 24"><path fill="currentColor" d="M22 12c0-5.52-4.48-10-10-10S2 6.48 2 12c0 4.84 3.44 8.87 8 9.8V15H8v-3h2V9.5C10 7.57 11.57 6 13.5 6H16v3h-2c-.55 0-1 .45-1 1v2h3v3h-3v6.95c5.05-.5 9-4.76 9-9.95"/></svg>'
                            ],
                            'instagram' => [
                                'iconName' => 'mdi:instagram',
                                'svgPath' => 'M7.8 2h8.4C19.4 2 22 4.6 22 7.8v8.4a5.8 5.8 0 0 1-5.8 5.8H7.8C4.6 22 2 19.4 2 16.2V7.8A5.8 5.8 0 0 1 7.8 2m-.2 2A3.6 3.6 0 0 0 4 7.6v8.8C4 18.39 5.61 20 7.6 20h8.8a3.6 3.6 0 0 0 3.6-3.6V7.6C20 5.61 18.39 4 16.4 4zm9.65 1.5a1.25 1.25 0 0 1 1.25 1.25A1.25 1.25 0 0 1 17.25 8A1.25 1.25 0 0 1 16 6.75a1.25 1.25 0 0 1 1.25-1.25M12 7a5 5 0 0 1 5 5a5 5 0 0 1-5 5a5 5 0 0 1-5-5a5 5 0 0 1 5-5m0 2a3 3 0 0 0-3 3a3 3 0 0 0 3 3a3 3 0 0 0 3-3a3 3 0 0 0-3-3',
                                'originalSvg' => '<svg xmlns="http://www.w3.org/2000/svg" width="1em" height="1em" viewBox="0 0 24 24"><path fill="currentColor" d="M7.8 2h8.4C19.4 2 22 4.6 22 7.8v8.4a5.8 5.8 0 0 1-5.8 5.8H7.8C4.6 22 2 19.4 2 16.2V7.8A5.8 5.8 0 0 1 7.8 2m-.2 2A3.6 3.6 0 0 0 4 7.6v8.8C4 18.39 5.61 20 7.6 20h8.8a3.6 3.6 0 0 0 3.6-3.6V7.6C20 5.61 18.39 4 16.4 4zm9.65 1.5a1.25 1.25 0 0 1 1.25 1.25A1.25 1.25 0 0 1 17.25 8A1.25 1.25 0 0 1 16 6.75a1.25 1.25 0 0 1 1.25-1.25M12 7a5 5 0 0 1 5 5a5 5 0 0 1-5 5a5 5 0 0 1-5-5a5 5 0 0 1 5-5m0 2a3 3 0 0 0-3 3a3 3 0 0 0 3 3a3 3 0 0 0 3-3a3 3 0 0 0-3-3"/></svg>'
                            ],
                            'twitter' => [
                                'iconName' => 'mdi:twitter',
                                'svgPath' => 'M22.46 6c-.77.35-1.6.58-2.46.69c.88-.53 1.56-1.37 1.88-2.38c-.83.5-1.75.85-2.72 1.05C18.37 4.5 17.26 4 16 4c-2.35 0-4.27 1.92-4.27 4.29c0 .34.04.67.11.98C8.28 9.09 5.11 7.38 3 4.79c-.37.63-.58 1.37-.58 2.15c0 1.49.75 2.81 1.91 3.56c-.71 0-1.37-.2-1.95-.5v.03c0 2.08 1.48 3.82 3.44 4.21a4.2 4.2 0 0 1-1.93.07a4.28 4.28 0 0 0 4 2.98a8.52 8.52 0 0 1-5.33 1.84q-.51 0-1.02-.06C3.44 20.29 5.7 21 8.12 21C16 21 20.33 14.46 20.33 8.79c0-.19 0-.37-.01-.56c.84-.6 1.56-1.36 2.14-2.23',
                                'originalSvg' => '<svg xmlns="http://www.w3.org/2000/svg" width="1em" height="1em" viewBox="0 0 24 24"><path fill="currentColor" d="M22.46 6c-.77.35-1.6.58-2.46.69c.88-.53 1.56-1.37 1.88-2.38c-.83.5-1.75.85-2.72 1.05C18.37 4.5 17.26 4 16 4c-2.35 0-4.27 1.92-4.27 4.29c0 .34.04.67.11.98C8.28 9.09 5.11 7.38 3 4.79c-.37.63-.58 1.37-.58 2.15c0 1.49.75 2.81 1.91 3.56c-.71 0-1.37-.2-1.95-.5v.03c0 2.08 1.48 3.82 3.44 4.21a4.2 4.2 0 0 1-1.93.07a4.28 4.28 0 0 0 4 2.98a8.52 8.52 0 0 1-5.33 1.84q-.51 0-1.02-.06C3.44 20.29 5.7 21 8.12 21C16 21 20.33 14.46 20.33 8.79c0-.19 0-.37-.01-.56c.84-.6 1.56-1.36 2.14-2.23"/></svg>'
                            ],
                            'youtube' => [
                                'iconName' => 'mdi:youtube',
                                'svgPath' => 'm10 15l5.19-3L10 9zm11.56-7.83c.13.47.22 1.1.28 1.9c.07.8.1 1.49.1 2.09L22 12c0 2.19-.16 3.8-.44 4.83c-.25.9-.83 1.48-1.73 1.73c-.47.13-1.33.22-2.65.28c-1.3.07-2.49.1-3.59.1L12 19c-4.19 0-6.8-.16-7.83-.44c-.9-.25-1.48-.83-1.73-1.73c-.13-.47-.22-1.1-.28-1.9c-.07-.8-.1-1.49-.1-2.09L2 12c0-2.19.16-3.8.44-4.83c.25-.9.83-1.48 1.73-1.73c.47-.13 1.33-.22 2.65-.28c1.3-.07 2.49-.1 3.59-.1L12 5c4.19 0 6.8.16 7.83.44c.9.25 1.48.83 1.73 1.73',
                                'originalSvg' => '<svg xmlns="http://www.w3.org/2000/svg" width="1em" height="1em" viewBox="0 0 24 24"><path fill="currentColor" d="m10 15l5.19-3L10 9zm11.56-7.83c.13.47.22 1.1.28 1.9c.07.8.1 1.49.1 2.09L22 12c0 2.19-.16 3.8-.44 4.83c-.25.9-.83 1.48-1.73 1.73c-.47.13-1.33.22-2.65.28c-1.3.07-2.49.1-3.59.1L12 19c-4.19 0-6.8-.16-7.83-.44c-.9-.25-1.48-.83-1.73-1.73c-.13-.47-.22-1.1-.28-1.9c-.07-.8-.1-1.49-.1-2.09L2 12c0-2.19.16-3.8.44-4.83c.25-.9.83-1.48 1.73-1.73c.47-.13 1.33-.22 2.65-.28c1.3-.07 2.49-.1 3.59-.1L12 5c4.19 0 6.8.16 7.83.44c.9.25 1.48.83 1.73 1.73"/></svg>'
                            ],
                            'whatsapp' => [
                                'iconName' => 'mdi:whatsapp',
                                'svgPath' => 'M12.04 2c-5.46 0-9.91 4.45-9.91 9.91c0 1.75.46 3.45 1.32 4.95L2.05 22l5.25-1.38c1.45.79 3.08 1.21 4.74 1.21c5.46 0 9.91-4.45 9.91-9.91c0-2.65-1.03-5.14-2.9-7.01A9.82 9.82 0 0 0 12.04 2m.01 1.67c2.2 0 4.26.86 5.82 2.42a8.23 8.23 0 0 1 2.41 5.83c0 4.54-3.7 8.23-8.24 8.23c-1.48 0-2.93-.39-4.19-1.15l-.3-.17l-3.12.82l.83-3.04l-.2-.32a8.2 8.2 0 0 1-1.26-4.38c.01-4.54 3.7-8.24 8.25-8.24M8.53 7.33c-.16 0-.43.06-.66.31c-.22.25-.87.86-.87 2.07c0 1.22.89 2.39 1 2.56c.14.17 1.76 2.67 4.25 3.73c.59.27 1.05.42 1.41.53c.59.19 1.13.16 1.56.1c.48-.07 1.46-.6 1.67-1.18s.21-1.07.15-1.18c-.07-.1-.23-.16-.48-.27c-.25-.14-1.47-.74-1.69-.82c-.23-.08-.37-.12-.56.12c-.16.25-.64.81-.78.97c-.15.17-.29.19-.53.07c-.26-.13-1.06-.39-2-1.23c-.74-.66-1.23-1.47-1.38-1.72c-.12-.24-.01-.39.11-.5c.11-.11.27-.29.37-.44c.13-.14.17-.25.25-.41c.08-.17.04-.31-.02-.43c-.06-.11-.56-1.35-.77-1.84c-.2-.48-.4-.42-.56-.43c-.14 0-.3-.01-.47-.01',
                                'originalSvg' => '<svg xmlns="http://www.w3.org/2000/svg" width="1em" height="1em" viewBox="0 0 24 24"><path fill="currentColor" d="M12.04 2c-5.46 0-9.91 4.45-9.91 9.91c0 1.75.46 3.45 1.32 4.95L2.05 22l5.25-1.38c1.45.79 3.08 1.21 4.74 1.21c5.46 0 9.91-4.45 9.91-9.91c0-2.65-1.03-5.14-2.9-7.01A9.82 9.82 0 0 0 12.04 2m.01 1.67c2.2 0 4.26.86 5.82 2.42a8.23 8.23 0 0 1 2.41 5.83c0 4.54-3.7 8.23-8.24 8.23c-1.48 0-2.93-.39-4.19-1.15l-.3-.17l-3.12.82l.83-3.04l-.2-.32a8.2 8.2 0 0 1-1.26-4.38c.01-4.54 3.7-8.24 8.25-8.24M8.53 7.33c-.16 0-.43.06-.66.31c-.22.25-.87.86-.87 2.07c0 1.22.89 2.39 1 2.56c.14.17 1.76 2.67 4.25 3.73c.59.27 1.05.42 1.41.53c.59.19 1.13.16 1.56.1c.48-.07 1.46-.6 1.67-1.18s.21-1.07.15-1.18c-.07-.1-.23-.16-.48-.27c-.25-.14-1.47-.74-1.69-.82c-.23-.08-.37-.12-.56.12c-.16.25-.64.81-.78.97c-.15.17-.29.19-.53.07c-.26-.13-1.06-.39-2-1.23c-.74-.66-1.23-1.47-1.38-1.72c-.12-.24-.01-.39.11-.5c.11-.11.27-.29.37-.44c.13-.14.17-.25.25-.41c.08-.17.04-.31-.02-.43c-.06-.11-.56-1.35-.77-1.84c-.2-.48-.4-.42-.56-.43c-.14 0-.3-.01-.47-.01"/></svg>'
                            ],
                            'call' => [
                                'iconName' => 'material-symbols:call',
                                'svgPath' => 'M19.95 21q-3.125 0-6.175-1.362t-5.55-3.863t-3.862-5.55T3 4.05q0-.45.3-.75t.75-.3H8.1q.35 0 .625.238t.325.562l.65 3.5q.05.4-.025.675T9.4 8.45L6.975 10.9q.5.925 1.187 1.787t1.513 1.663q.775.775 1.625 1.438T13.1 17l2.35-2.35q.225-.225.588-.337t.712-.063l3.45.7q.35.1.575.363T21 15.9v4.05q0 .45-.3.75t-.75.3',
                                'originalSvg' => '<svg xmlns="http://www.w3.org/2000/svg" width="1em" height="1em" viewBox="0 0 24 24"><path fill="currentColor" d="M19.95 21q-3.125 0-6.175-1.362t-5.55-3.863t-3.862-5.55T3 4.05q0-.45.3-.75t.75-.3H8.1q.35 0 .625.238t.325.562l.65 3.5q.05.4-.025.675T9.4 8.45L6.975 10.9q.5.925 1.187 1.787t1.513 1.663q.775.775 1.625 1.438T13.1 17l2.35-2.35q.225-.225.588-.337t.712-.063l3.45.7q.35.1.575.363T21 15.9v4.05q0 .45-.3.75t-.75.3"/></svg>'
                            ],
                            'email' => [
                                'iconName' => 'material-symbols:mail',
                                'svgPath' => 'M4 20q-.825 0-1.412-.587T2 18V6q0-.825.588-1.412T4 4h16q.825 0 1.413.588T22 6v12q0 .825-.587 1.413T20 20zm8-7L4 8v10h16V8zm0-2l8-5H4zM4 8V6v12z',
                                'originalSvg' => '<svg xmlns="http://www.w3.org/2000/svg" width="1em" height="1em" viewBox="0 0 24 24"><path fill="currentColor" d="M4 20q-.825 0-1.412-.587T2 18V6q0-.825.588-1.412T4 4h16q.825 0 1.413.588T22 6v12q0 .825-.587 1.413T20 20zm8-7L4 8v10h16V8zm0-2l8-5H4zM4 8V6v12z"/></svg>'
                            ],
                            'web' => [
                                'iconName' => 'material-symbols:language',
                                'svgPath' => 'M11.99 2C6.47 2 2 6.48 2 12s4.47 10 9.99 10C17.52 22 22 17.52 22 12S17.52 2 11.99 2m6.93 6h-2.95a15.7 15.7 0 0 0-1.38-3.56A8.03 8.03 0 0 1 18.92 8M12 4.04c.83 1.2 1.48 2.53 1.91 3.96h-3.82c.43-1.43 1.08-2.76 1.91-3.96M4.26 14C4.1 13.36 4 12.69 4 12s.1-1.36.26-2h3.38c-.08.66-.14 1.32-.14 2s.06 1.34.14 2zm.82 2h2.95c.32 1.25.78 2.45 1.38 3.56A7.99 7.99 0 0 1 5.08 16m2.95-8H5.08a7.99 7.99 0 0 1 3.95-3.56A15.7 15.7 0 0 0 7.65 8m6.17 11.96c-.83-1.2-1.48-2.53-1.91-3.96h3.82c-.43 1.43-1.08 2.76-1.91 3.96M14.34 14H9.66c-.09-.66-.16-1.32-.16-2s.07-1.35.16-2h4.68c.09.65.16 1.32.16 2s-.07 1.34-.16 2m.25 5.56c.6-1.11 1.06-2.31 1.38-3.56h2.95a8.03 8.03 0 0 1-3.95 3.56q-.18-.17-.38-.04m2.95-8h-3.38c.08-.66.14-1.32.14-2s-.06-1.34-.14-2h3.38c.16.64.26 1.31.26 2s-.1 1.36-.26 2',
                                'originalSvg' => '<svg xmlns="http://www.w3.org/2000/svg" width="1em" height="1em" viewBox="0 0 24 24"><path fill="currentColor" d="M11.99 2C6.47 2 2 6.48 2 12s4.47 10 9.99 10C17.52 22 22 17.52 22 12S17.52 2 11.99 2m6.93 6h-2.95a15.7 15.7 0 0 0-1.38-3.56A8.03 8.03 0 0 1 18.92 8M12 4.04c.83 1.2 1.48 2.53 1.91 3.96h-3.82c.43-1.43 1.08-2.76 1.91-3.96M4.26 14C4.1 13.36 4 12.69 4 12s.1-1.36.26-2h3.38c-.08.66-.14 1.32-.14 2s.06 1.34.14 2zm.82 2h2.95c.32 1.25.78 2.45 1.38 3.56A7.99 7.99 0 0 1 5.08 16m2.95-8H5.08a7.99 7.99 0 0 1 3.95-3.56A15.7 15.7 0 0 0 7.65 8m6.17 11.96c-.83-1.2-1.48-2.53-1.91-3.96h3.82c-.43 1.43-1.08 2.76-1.91 3.96M14.34 14H9.66c-.09-.66-.16-1.32-.16-2s.07-1.35.16-2h4.68c.09.65.16 1.32.16 2s-.07 1.34-.16 2m.25 5.56c.6-1.11 1.06-2.31 1.38-3.56h2.95a8.03 8.03 0 0 1-3.95 3.56q-.18-.17-.38-.04m2.95-8h-3.38c.08-.66.14-1.32.14-2s-.06-1.34-.14-2h3.38c.16.64.26 1.31.26 2s-.1 1.36-.26 2"/></svg>'
                            ],
                            'location' => [
                                'iconName' => 'material-symbols:location-on',
                                'svgPath' => 'M12 12q.825 0 1.413-.587T14 10t-.587-1.412T12 8t-1.412.588T10 10t.588 1.413T12 12m0 9.8q-4.025-3.425-6.012-6.362T4 10.2q0-3.75 2.413-5.975T12 2t5.588 2.225T20 10.2q0 2.5-1.987 5.438T12 21.8',
                                'originalSvg' => '<svg xmlns="http://www.w3.org/2000/svg" width="1em" height="1em" viewBox="0 0 24 24"><path fill="currentColor" d="M12 12q.825 0 1.413-.587T14 10t-.587-1.412T12 8t-1.412.588T10 10t.588 1.413T12 12m0 9.8q-4.025-3.425-6.012-6.362T4 10.2q0-3.75 2.413-5.975T12 2t5.588 2.225T20 10.2q0 2.5-1.987 5.438T12 21.8"/></svg>'
                            ],
                            'linkedin' => [
                                'iconName' => 'mdi:linkedin',
                                'svgPath' => 'M19 3a2 2 0 0 1 2 2v14a2 2 0 0 1-2 2H5a2 2 0 0 1-2-2V5a2 2 0 0 1 2-2zm-.5 15.5v-5.3a3.26 3.26 0 0 0-3.26-3.26c-.85 0-1.84.52-2.32 1.3v-1.11h-2.79v8.37h2.79v-4.93c0-.77.62-1.4 1.39-1.4a1.4 1.4 0 0 1 1.4 1.4v4.93zM6.88 8.56a1.68 1.68 0 0 0 1.68-1.68c0-.93-.75-1.69-1.68-1.69a1.69 1.69 0 0 0-1.69 1.69c0 .93.76 1.68 1.69 1.68m1.39 9.94v-8.37H5.5v8.37z',
                                'originalSvg' => '<svg xmlns="http://www.w3.org/2000/svg" width="1em" height="1em" viewBox="0 0 24 24"><path fill="currentColor" d="M19 3a2 2 0 0 1 2 2v14a2 2 0 0 1-2 2H5a2 2 0 0 1-2-2V5a2 2 0 0 1 2-2zm-.5 15.5v-5.3a3.26 3.26 0 0 0-3.26-3.26c-.85 0-1.84.52-2.32 1.3v-1.11h-2.79v8.37h2.79v-4.93c0-.77.62-1.4 1.39-1.4a1.4 1.4 0 0 1 1.4 1.4v4.93zM6.88 8.56a1.68 1.68 0 0 0 1.68-1.68c0-.93-.75-1.69-1.68-1.69a1.69 1.69 0 0 0-1.69 1.69c0 .93.76 1.68 1.69 1.68m1.39 9.94v-8.37H5.5v8.37z"/></svg>'
                            ]
                        ];

                        $iconKeywordMap = [
                            'facebook' => 'facebook', 'fb' => 'facebook',
                            'instagram' => 'instagram', 'insta' => 'instagram',
                            'twitter' => 'twitter', 'youtube' => 'youtube', 'linkedin' => 'linkedin',
                            'whatsapp' => 'whatsapp', 'call' => 'call', 'phone' => 'call',
                            'mail' => 'email', 'email' => 'email',
                            'web' => 'web', 'website' => 'web',
                            'location' => 'location', 'address' => 'location'
                        ];

                        $upgradeLayerToVector = function(&$layer, &$modifiedFlag) use ($vectorIconsMap, $iconKeywordMap) {
                            if (isset($layer['type']) && $layer['type'] === 'image' && isset($layer['src'])) {
                                $src = strtolower($layer['src']);
                                $name = strtolower(isset($layer['name']) ? $layer['name'] : '');
                                
                                $w = isset($layer['w']) ? $layer['w'] : (isset($layer['width']) ? $layer['width'] : 0);
                                $h = isset($layer['h']) ? $layer['h'] : (isset($layer['height']) ? $layer['height'] : 0);
                                
                                if ($w > 200 || $h > 200) return;
                                if (strpos($name, 'bg') !== false || strpos($name, 'background') !== false || strpos($name, 'main') !== false) return;

                                $matchedKey = null;

                                foreach ($iconKeywordMap as $kw => $key) {
                                    if (strpos($src, $kw) !== false || strpos($name, $kw) !== false) {
                                        $matchedKey = $key;
                                        break;
                                    }
                                }

                                if ($matchedKey && isset($vectorIconsMap[$matchedKey])) {
                                    $vectorData = $vectorIconsMap[$matchedKey];
                                    
                                    // Transform the layer into a vector icon
                                    $layer['type'] = 'icon';
                                    $layer['shapeType'] = 'path';
                                    $layer['iconName'] = $vectorData['iconName'];
                                    $layer['iconProvider'] = 'iconify';
                                    $layer['svgPath'] = $vectorData['svgPath'];
                                    
                                    // Use exact previous color
                                    $oldColor = $layer['tint_color'] ?? $layer['color'] ?? $layer['font_color'] ?? '#333333';
                                    $layer['color'] = $oldColor;
                                    
                                    // Fix specific name to avoid issues
                                    $layer['name'] = 'icon_' . $matchedKey;
                                    
                                    // Safely ensure is_shape is false so it doesn't collide
                                    $layer['is_shape'] = false;

                                    $layer['_source_meta'] = [
                                        'type' => 'icon',
                                        'iconName' => $vectorData['iconName'],
                                        'provider' => 'iconify',
                                        'originalSvg' => $vectorData['originalSvg']
                                    ];
                                    
                                    $modifiedFlag = true;
                                }
                            }
                        };

                        if (isset($json['layers']) && is_array($json['layers'])) {
                            foreach ($json['layers'] as &$layer) {
                                $upgradeLayerToVector($layer, $jsonModified);
                            }
                        }
                    }

                    // Update render_version in JSON
                    if ($targetVersion !== 'none') {
                        $json['render_version'] = (int) $targetVersion;
                        $jsonModified = true;
                    }

                    // Save back to file
                    if ($jsonModified) {
                        file_put_contents($jsonPath, json_encode($json, JSON_UNESCAPED_SLASHES | JSON_PRETTY_PRINT));
                    }

                    // Update DB column and layers_json
                    if ($targetVersion !== 'none') {
                        $frame->render_version = (int) $targetVersion;
                    }
                    
                    if ($jsonModified) {
                        $jsonString = json_encode($json, JSON_UNESCAPED_SLASHES);
                        $frame->layers_json = $jsonString;
                        
                        // Update the ZIP file directly so Web Editor load doesn't revert to old JSON
                        $zipPath = public_path("uploads/template/{$frame->zip_name}.zip");
                        if (file_exists($zipPath)) {
                            $zip = new \ZipArchive();
                            if ($zip->open($zipPath) === true) {
                                $zip->addFromString('frame.json', $jsonString);
                                $zip->close();
                            }
                        }
                    }
                    $frame->save();

                    // Invalidate Redis Cache
                    \Illuminate\Support\Facades\Cache::forget("template_json:{$frame->zip_name}");
                    \Illuminate\Support\Facades\Cache::forget("template_json:v2:{$frame->zip_name}");
                    \Illuminate\Support\Facades\Cache::forget("template_json:v2:" . preg_replace('/^Template_/i', '', $frame->zip_name));

                    // Also update EditorTemplate if exists
                    $editorTemplate = \App\Models\EditorTemplate::where('uuid', $frame->zip_name)->first();
                    if ($editorTemplate) {
                        $schema = is_string($editorTemplate->schema_json) ? json_decode($editorTemplate->schema_json, true) : $editorTemplate->schema_json;
                        $legacy = is_string($editorTemplate->legacy_json) ? json_decode($editorTemplate->legacy_json, true) : $editorTemplate->legacy_json;
                        $dbModified = false;

                        if ($upgradeIcons && $upgradeLayerToVector) {
                            $updateLayers = function(&$layers) use ($upgradeLayerToVector, &$dbModified) {
                                if (is_array($layers)) {
                                    foreach ($layers as &$layer) {
                                        $upgradeLayerToVector($layer, $dbModified);
                                    }
                                }
                            };
                            if (is_array($schema) && isset($schema['objects'])) $updateLayers($schema['objects']);
                            if (is_array($legacy) && isset($legacy['layers'])) $updateLayers($legacy['layers']);
                        }

                        if ($targetVersion !== 'none') {
                            if (is_array($schema)) { $schema['render_version'] = (int) $targetVersion; }
                            if (is_array($legacy)) { $legacy['render_version'] = (int) $targetVersion; }
                            $editorTemplate->render_version = (int) $targetVersion;
                            $dbModified = true;
                        }
                        
                        if ($dbModified) {
                            if (is_array($schema)) $editorTemplate->schema_json = $schema;
                            if (is_array($legacy)) $editorTemplate->legacy_json = $legacy;
                            $editorTemplate->save();
                            \Illuminate\Support\Facades\Cache::forget("template_json:{$editorTemplate->uuid}");
                            \Illuminate\Support\Facades\Cache::forget("template_json:v2:{$editorTemplate->uuid}");
                            \Illuminate\Support\Facades\Cache::forget("template_json:v2:" . preg_replace('/^Template_/i', '', $editorTemplate->uuid));
                        }
                    }

                    $autoCommitted[] = $result;

                } else {
                    // Needs manual review
                    $needsReview[] = $result;
                }
            } catch (\Exception $e) {
                $errors[] = "Frame #{$id}: " . $e->getMessage();
            }
        }

        return response()->json([
            'success' => true,
            'total' => count($request->ids),
            'auto_committed' => count($autoCommitted),
            'needs_review' => count($needsReview),
            'auto_committed_frames' => $autoCommitted,
            'review_frames' => $needsReview,
            'errors' => $errors,
        ]);
    }

    /**
     * Auto-compensate a frame's JSON values to match golden baseline at new version.
     * Only works for simple linear properties (x, y, w, h).
     */
    public function autoCompensate(Request $request)
    {
        $request->validate([
            'frame_id' => 'required|integer',
            'target_version' => 'required|integer',
            'mismatches' => 'required|array',
        ]);

        $frame = PosterMaker::findOrFail($request->frame_id);
        $jsonPath = public_path('uploads/template/'.$frame->zip_name.'/json/'.$frame->zip_name.'.json');
        
        if (!file_exists($jsonPath)) {
            return response()->json(['success' => false, 'message' => 'JSON file not found'], 404);
        }

        $json = json_decode(file_get_contents($jsonPath), true);

        $compensated = [];
        $manualRequired = [];

        foreach ($request->mismatches as $mismatch) {
            $layer = $mismatch['layer'];
            $property = $mismatch['property'];
            $goldenValue = floatval($mismatch['golden_value']);
            $newValue = floatval($mismatch['new_value']);

            // Only auto-compensate simple linear properties
            $linearProps = ['canvasX', 'canvasY', 'canvasW', 'canvasH', 'finalX', 'finalY', 'finalW', 'finalH'];

            if (!in_array($property, $linearProps)) {
                $manualRequired[] = $mismatch;
                continue;
            }

            // Find the layer in JSON and calculate correction factor
            $layers = &$json['layers'];
            foreach ($layers as &$jsonLayer) {
                $lName = $jsonLayer['name'] ?? $jsonLayer['id'] ?? '';
                if ($lName === $layer) {
                    // Map computed property back to JSON property
                    $jsonProp = $this->mapComputedToJsonProp($property);
                    if ($jsonProp && isset($jsonLayer[$jsonProp]) && $newValue != 0) {
                        $currentJsonVal = floatval($jsonLayer[$jsonProp]);
                        $correctionFactor = $goldenValue / $newValue;
                        $correctedVal = round($currentJsonVal * $correctionFactor, 2);

                        $compensated[] = [
                            'layer' => $layer,
                            'property' => $jsonProp,
                            'old_json_value' => $currentJsonVal,
                            'new_json_value' => $correctedVal,
                            'correction_factor' => round($correctionFactor, 4),
                        ];

                        $jsonLayer[$jsonProp] = $correctedVal;
                    }
                    break;
                }
            }
        }

        // Save corrected JSON back
        file_put_contents($jsonPath, json_encode($json, JSON_UNESCAPED_SLASHES | JSON_PRETTY_PRINT));

        // Update render_version
        $json['render_version'] = $request->target_version;
        $frame->render_version = $request->target_version;
        $frame->save();

        // Clear Redis cache
        \Illuminate\Support\Facades\Cache::forget("template_json:{$frame->zip_name}");

        return response()->json([
            'success' => true,
            'compensated' => $compensated,
            'manual_required' => $manualRequired,
            'message' => count($compensated) . ' properties auto-compensated, ' . count($manualRequired) . ' need manual review',
        ]);
    }

    private function mapComputedToJsonProp(string $computedProp): ?string
    {
        return match($computedProp) {
            'canvasX', 'finalX' => 'x',
            'canvasY', 'finalY' => 'y',
            'canvasW', 'finalW' => 'w',
            'canvasH', 'finalH' => 'h',
            'computedFontSize', 'finalFontSize' => null, // Font size is complex — manual review
            default => null,
        };
    }
}
