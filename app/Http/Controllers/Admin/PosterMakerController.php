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

    public function index()
    {
        $index['data'] = PosterMaker::orderBy('id', 'DESC')->paginate(12);
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
}
