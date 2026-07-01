<?php

namespace App\Http\Controllers\Admin;

use Auth;
use App\Models\Language;
use App\Models\CustomPost;
use Illuminate\Support\Str;
use Illuminate\Http\Request;
use App\Models\StorageSetting;
use App\Models\CustomPostFrame;
use App\Http\Controllers\Controller;
use App\Services\TemplateFingerprintService;
use Illuminate\Support\Facades\File;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Facades\Validator;

class CustomPostFrameController extends Controller
{
    public function __construct()
    {
        $this->middleware('permission:CustomFrame');
    }

    public function index()
    {
        $index['customPost'] = CustomPost::get();
        $index['data'] = CustomPostFrame::orderBy('id', 'DESC')->paginate(12);
        return view("custom_post_frame.index", $index);
    }

    public function create()
    {
        $index['customPost'] = CustomPost::where('status', 1)->get();
        $index['language'] = Language::where('status', 1)->get();
        return view("custom_post_frame.create", $index);
    }

    public function store(Request $request)
    {
        // dd($request->all());
        if ($request->custom_frame_type == "simple") {
            $validation = Validator::make($request->all(), [
                "custom_post_id" => 'required',
                "language_id" => 'required',
                "frame_image" => "required",
            ]);

            if ($validation->fails()) {
                return back()->withErrors($validation)->withInput();
            } else {
                if ($request->file("frame_image")) {
                    $removedImages = json_decode($request->get("deleted_file_ids"), true);
                    $images = $request->file('frame_image');
                    foreach ($images as $image) {
                        if ($removedImages != null) {
                            if (in_array($image->getClientOriginalName(), $removedImages)) {
                                continue;
                            }
                        }

                        $size = getimagesize($image);
                        if ($size[0] > $size[1]) {
                            $type = "landscape";
                        }
                        if ($size[0] < $size[1]) {
                            $type = "portrait";
                        }
                        if ($size[0] == $size[1]) {
                            $type = "square";
                        }

                        $id = CustomPostFrame::create([
                            "custom_frame_type" => $request->get("custom_frame_type"),
                            "custom_post_id" => $request->get("custom_post_id"),
                            "language_id" => $request->get("language_id"),
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

                            $post = CustomPostFrame::find($id);
                            $post->frame_image = $file;
                            $post->save();
                        } else {
                            $this->upload_image($image, "frame_image", $id);
                        }
                    }
                }

                return redirect()->route("custom-post-frame.index");
            }
        }
        if ($request->custom_frame_type == "editable") {
            // dd($request->all());
            $validation = Validator::make($request->all(), [
                "custom_post_id" => 'required',
                "language_id" => 'required',
                "zip" => 'required',
                "post_thumb" => "required|mimes:jpg,png,jpeg",
            ]);

            if ($validation->fails()) {
                return back()->withErrors($validation)->withInput();
            } else {
                if ($request->file('zip')) {
                    $zip_name = "POST" . date("YmdHis");
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

                    $base_extracted = './uploads/template/' . $zip_name . '/' . $zip_original_name;
                    $target_base = './uploads/template/' . $zip_name;

                    if (File::isDirectory($base_extracted . '/fonts')) {
                        rename($base_extracted . '/fonts', $target_base . '/fonts');
                    }
                    if (File::isDirectory($base_extracted . '/json')) {
                        rename($base_extracted . '/json', $target_base . '/json');
                    }
                    if (File::isDirectory($base_extracted . '/logs')) {
                        rename($base_extracted . '/logs', $target_base . '/logs');
                    }
                    if (File::isDirectory($base_extracted . '/skins')) {
                        rename($base_extracted . '/skins', $target_base . '/skins');
                    }
                    $this->rrmdir($base_extracted);
                    $this->sanitizeExtractedTemplate($target_base);

                    if (StorageSetting::getStorageSetting("storage") == "DigitalOcean") {
                        $fonts_dir = './uploads/template/' . $zip_name . '/fonts/';
                        if (File::isDirectory($fonts_dir)) {
                            $fonts = File::allFiles($fonts_dir);
                            foreach ($fonts as $f) {
                                Storage::disk('spaces')->put('/uploads/template/' . $zip_name . '/fonts/' . $f->getrelativePathname(), file_get_contents($f), 'public');
                            }
                        }

                        $json_dir = './uploads/template/' . $zip_name . '/json/';
                        if (File::isDirectory($json_dir)) {
                            $json = File::allFiles($json_dir);
                            foreach ($json as $j) {
                                Storage::disk('spaces')->put('/uploads/template/' . $zip_name . '/json/' . $j->getrelativePathname(), file_get_contents($j), 'public');
                            }
                        }

                        $logs_dir = './uploads/template/' . $zip_name . '/logs/';
                        if (File::isDirectory($logs_dir)) {
                            $logs = File::allFiles($logs_dir);
                            Storage::disk('spaces')->makeDirectory('/uploads/template/' . $zip_name . '/logs/');
                            foreach ($logs as $log) {
                                Storage::disk('spaces')->put('/uploads/template/' . $zip_name . '/logs/' . $log->getrelativePathname(), file_get_contents($log), 'public');
                            }
                        }

                        $skins_dir = './uploads/template/' . $zip_name . '/skins/';
                        if (File::isDirectory($skins_dir)) {
                            $skins = File::allFiles($skins_dir);
                            foreach ($skins as $s) {
                                Storage::disk('spaces')->put('/uploads/template/' . $zip_name . '/skins/' . $s->getrelativePathname(), file_get_contents($s), 'public');
                            }
                        }

                        $this->rrmdir('./uploads/template/' . $zip_name);
                    }
                }

                if ($request->file("post_thumb") && $request->file('post_thumb')->isValid()) {
                    if (StorageSetting::getStorageSetting("storage") == "DigitalOcean") {
                        $image = $request->file('post_thumb');
                        $fileName = Str::uuid() . '.' . $image->getClientOriginalExtension();

                        $path = Storage::disk('spaces')->put('uploads/' . $fileName, file_get_contents($image), 'public');

                        $size = getimagesize(Storage::disk('spaces')->url('uploads/' . $fileName));
                        if ($size[0] > $size[1]) {
                            $type = "landscape";
                        }
                        if ($size[0] < $size[1]) {
                            $type = "portrait";
                        }
                        if ($size[0] == $size[1]) {
                            $type = "square";
                        }
                    } else {
                        $file = $request->file('post_thumb');
                        $destinationPath = public_path('uploads');
                        $extension = $file->getClientOriginalExtension();
                        $fileName = Str::uuid() . '.' . $extension;
                        $file->move($destinationPath, $fileName);

                        $size = getimagesize(asset('uploads/' . $fileName));
                        if ($size[0] > $size[1]) {
                            $type = "landscape";
                        }
                        if ($size[0] < $size[1]) {
                            $type = "portrait";
                        }
                        if ($size[0] == $size[1]) {
                            $type = "square";
                        }
                    }
                }

                // Auto-generate fingerprint from the extracted ZIP
                $fingerprintService = new TemplateFingerprintService();
                $fingerprint = $fingerprintService->extractFromZip(public_path('uploads/template/' . $zip_name));

                CustomPostFrame::create([
                    "custom_frame_type" => $request->get("custom_frame_type"),
                    "custom_post_id" => $request->get("custom_post_id"),
                    "language_id" => $request->get("language_id"),
                    "zip_name" => $zip_name,
                    "user_id" => Auth::User()->id,
                    "paid" => 1,
                    "height" => $size[1],
                    "width" => $size[0],
                    "image_type" => $type,
                    "aspect_ratio" => $this->getAspectRatio($size[0], $size[1]),
                    "frame_image" => $fileName,
                    "fingerprint" => $fingerprint,
                ]);

                if ($request->total) {
                    $arr = explode(",", $request->total);
                    foreach ($arr as $key => $tt) {
                        if ($request->file('zip' . $tt)) {
                            $zip_name = "Frame" . date("YmdHis") . $key;
                            $zip_original_name = pathinfo($request->file("zip" . $tt)->getClientOriginalName(), PATHINFO_FILENAME);
                            $extension = $request->file("zip" . $tt)->getClientOriginalExtension();
                            $file_name = $zip_name . "." . $extension;
                            $request->file("zip" . $tt)->move('./uploads/template', $file_name);

                            $zipPath = public_path('uploads/template/' . $file_name);
                            $validationResult = \App\Services\FontValidationService::validateZipFonts($zipPath);

                            if ($validationResult !== true) {
                                unlink($zipPath);
                                $errorMessage = 'Upload failed for one of the files! The following fonts are missing in the central system: ' . implode(', ', $validationResult) . '. Please upload them to the Font Section first.';
                                return back()->withErrors(['zip' => $errorMessage])->withInput();
                            }

                            File::makeDirectory('./uploads/template/' . $zip_name);

                            $zip = new \ZipArchive;
                            if ($zip->open('./uploads/template/' . $file_name) === TRUE) {
                                $zip->extractTo('./uploads/template/' . $zip_name);
                                $zip->close();
                            }
                            unlink('./uploads/template/' . $file_name);

                            $base_extracted = './uploads/template/' . $zip_name . '/' . $zip_original_name;
                            $target_base = './uploads/template/' . $zip_name;

                            if (File::isDirectory($base_extracted . '/fonts')) {
                                rename($base_extracted . '/fonts', $target_base . '/fonts');
                            }
                            if (File::isDirectory($base_extracted . '/json')) {
                                rename($base_extracted . '/json', $target_base . '/json');
                            }
                            if (File::isDirectory($base_extracted . '/logs')) {
                                rename($base_extracted . '/logs', $target_base . '/logs');
                            }
                            if (File::isDirectory($base_extracted . '/skins')) {
                                rename($base_extracted . '/skins', $target_base . '/skins');
                            }
                            $this->rrmdir($base_extracted);
                            $this->sanitizeExtractedTemplate($target_base);

                            if (StorageSetting::getStorageSetting("storage") == "DigitalOcean") {
                                $fonts_dir = './uploads/template/' . $zip_name . '/fonts/';
                                if (File::isDirectory($fonts_dir)) {
                                    $fonts = File::allFiles($fonts_dir);
                                    foreach ($fonts as $f) {
                                        Storage::disk('spaces')->put('/uploads/template/' . $zip_name . '/fonts/' . $f->getrelativePathname(), file_get_contents($f), 'public');
                                    }
                                }

                                $json_dir = './uploads/template/' . $zip_name . '/json/';
                                if (File::isDirectory($json_dir)) {
                                    $json = File::allFiles($json_dir);
                                    foreach ($json as $j) {
                                        Storage::disk('spaces')->put('/uploads/template/' . $zip_name . '/json/' . $j->getrelativePathname(), file_get_contents($j), 'public');
                                    }
                                }

                                $logs_dir = './uploads/template/' . $zip_name . '/logs/';
                                if (File::isDirectory($logs_dir)) {
                                    $logs = File::allFiles($logs_dir);
                                    Storage::disk('spaces')->makeDirectory('/uploads/template/' . $zip_name . '/logs/');
                                    foreach ($logs as $log) {
                                        Storage::disk('spaces')->put('/uploads/template/' . $zip_name . '/logs/' . $log->getrelativePathname(), file_get_contents($log), 'public');
                                    }
                                }

                                $skins_dir = './uploads/template/' . $zip_name . '/skins/';
                                if (File::isDirectory($skins_dir)) {
                                    $skins = File::allFiles($skins_dir);
                                    foreach ($skins as $s) {
                                        Storage::disk('spaces')->put('/uploads/template/' . $zip_name . '/skins/' . $s->getrelativePathname(), file_get_contents($s), 'public');
                                    }
                                }

                                $this->rrmdir('./uploads/template/' . $zip_name);
                            }
                        }

                        if ($request->file("post_thumb" . $tt) && $request->file('post_thumb' . $tt)->isValid()) {
                            if (StorageSetting::getStorageSetting("storage") == "DigitalOcean") {
                                $image = $request->file('post_thumb' . $tt);
                                $fileName = Str::uuid() . '.' . $image->getClientOriginalExtension();

                                $path = Storage::disk('spaces')->put('uploads/' . $fileName, file_get_contents($image), 'public');

                                $size = getimagesize(Storage::disk('spaces')->url('uploads/' . $fileName));
                                if ($size[0] > $size[1]) {
                                    $type = "landscape";
                                }
                                if ($size[0] < $size[1]) {
                                    $type = "portrait";
                                }
                                if ($size[0] == $size[1]) {
                                    $type = "square";
                                }
                            } else {
                                $file = $request->file('post_thumb' . $tt);
                                $destinationPath = public_path('uploads');
                                $extension = $file->getClientOriginalExtension();
                                $fileName = Str::uuid() . '.' . $extension;
                                $file->move($destinationPath, $fileName);

                                $size = getimagesize(asset('uploads/' . $fileName));
                                if ($size[0] > $size[1]) {
                                    $type = "landscape";
                                }
                                if ($size[0] < $size[1]) {
                                    $type = "portrait";
                                }
                                if ($size[0] == $size[1]) {
                                    $type = "square";
                                }
                            }
                        }

                        // Auto-generate fingerprint from the extracted ZIP
                        $fingerprintService = new TemplateFingerprintService();
                        $fpData = $fingerprintService->extractFromZip(public_path('uploads/template/' . $zip_name));

                        CustomPostFrame::create([
                            "custom_frame_type" => $request->get("custom_frame_type"),
                            "custom_post_id" => $request->get("custom_post_id"),
                            "language_id" => $request->get("language_id" . $tt),
                            "zip_name" => $zip_name,
                            "user_id" => Auth::User()->id,
                            "paid" => 1,
                            "height" => $size[1],
                            "width" => $size[0],
                            "image_type" => $type,
                            "aspect_ratio" => $this->getAspectRatio($size[0], $size[1]),
                            "frame_image" => $fileName,
                            "fingerprint" => $fpData,
                        ]);
                    }
                }

                return redirect()->route("custom-post-frame.index");
            }
        }
    }

    public function custom_post_frame_status(Request $request)
    {
        $category = CustomPostFrame::find($request->get("id"));
        $category->status = ($request->get("checked") == "true") ? 1 : 0;
        $category->save();
    }

    public function custom_post_frame_landing(Request $request)
    {
        $category = CustomPostFrame::find($request->get("id"));
        $category->show_on_landing = ($request->get("checked") == "true") ? 1 : 0;
        $category->save();
    }

    public function custom_post_frame_action(Request $request)
    {
        $ids = explode(",", $request->select_post);
        if ($request->select_post != null) {
            if ($request->action_type == "enable") {
                foreach ($ids as $id) {
                    $category = CustomPostFrame::find($id);
                    $category->status = 1;
                    $category->save();
                }
            }

            if ($request->action_type == "disable") {
                foreach ($ids as $id) {
                    $category = CustomPostFrame::find($id);
                    $category->status = 0;
                    $category->save();
                }
            }

            if ($request->action_type == "delete") {
                foreach ($ids as $id) {
                    CustomPostFrame::find($id)->delete();
                }
            }
        }

        return redirect()->route("custom-post-frame.index");
    }

    public function custom_post_get($id)
    {
        $index['customPost'] = CustomPost::get();
        $index['data'] = CustomPostFrame::where('custom_post_id', $id)->paginate(12);
        $c_name = CustomPost::find($id);
        $index['name'] = $c_name->name;

        return view("custom_post_frame.index", $index);
    }

    public function custom_post_frame_type(Request $request)
    {
        $category = CustomPostFrame::find($request->get("id"));
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
        $index['customPostFrame'] = CustomPostFrame::find($id);
        $index['customPost'] = CustomPost::get();
        $index['language'] = Language::get();
        return view("custom_post_frame.edit", $index);
    }

    public function update(Request $request, $id)
    {
        // dd($request->all());
        if ($request->custom_frame_type == "simple") {
            $validation = Validator::make($request->all(), [
                "custom_post_id" => 'required',
                "language_id" => 'required',
                "frame_image" => "nullable|mimes:jpg,png,jpeg",
            ]);

            if ($validation->fails()) {
                return back()->withErrors($validation)->withInput();
            } else {
                $category = CustomPostFrame::find($request->get("id"));
                $category->custom_frame_type = $request->get("custom_frame_type");
                $category->custom_post_id = $request->get("custom_post_id");
                $category->language_id = $request->get("language_id");
                $category->zip_name = null;
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

                    if (StorageSetting::getStorageSetting("storage") == "DigitalOcean") {
                        $image = $request->file('frame_image');
                        $fileName = Str::uuid() . '.' . $image->getClientOriginalExtension();

                        $path = Storage::disk('spaces')->put('uploads/' . $fileName, file_get_contents($image), 'public');

                        $image = CustomPostFrame::find($request->get("id"));
                        $image->frame_image = $fileName;
                        $image->height = $size[1];
                        $image->width = $size[0];
                        $image->image_type = $type;
                        $image->aspect_ratio = $this->getAspectRatio($size[0], $size[1]);
                        $image->save();
                    } else {
                        $this->upload_image($request->file("frame_image"), "frame_image", $id);

                        $image = CustomPostFrame::find($request->get("id"));
                        $image->height = $size[1];
                        $image->width = $size[0];
                        $image->image_type = $type;
                        $image->aspect_ratio = $this->getAspectRatio($size[0], $size[1]);
                        $image->save();
                    }
                }

                return redirect()->route('custom-post-frame.index');
            }
        }

        if ($request->custom_frame_type == "editable") {
            $validation = Validator::make($request->all(), [
                "custom_post_id" => 'required',
                "language_id" => 'required',
            ]);

            if ($validation->fails()) {
                return back()->withErrors($validation)->withInput();
            } else {
                $post = CustomPostFrame::find($request->get("id"));
                $post->custom_frame_type = $request->get("custom_frame_type");
                $post->custom_post_id = $request->get("custom_post_id");
                $post->language_id = $request->get("language_id");
                $post->save();

                if ($request->file('zip')) {
                    $zip = $request->file('zip');
                    $zip_name = "POST" . date("YmdHis");
                    $zip_original_name = pathinfo($zip->getClientOriginalName(), PATHINFO_FILENAME);
                    $extension = $zip->getClientOriginalExtension();
                    $file_name = $zip_name . "." . $extension;
                    $zip->move('./uploads/template', $file_name);

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

                    $base_extracted = './uploads/template/' . $zip_name . '/' . $zip_original_name;
                    $target_base = './uploads/template/' . $zip_name;

                    if (File::isDirectory($base_extracted . '/fonts')) {
                        rename($base_extracted . '/fonts', $target_base . '/fonts');
                    }
                    if (File::isDirectory($base_extracted . '/json')) {
                        rename($base_extracted . '/json', $target_base . '/json');
                    }
                    if (File::isDirectory($base_extracted . '/logs')) {
                        rename($base_extracted . '/logs', $target_base . '/logs');
                    }
                    if (File::isDirectory($base_extracted . '/skins')) {
                        rename($base_extracted . '/skins', $target_base . '/skins');
                    }
                    $this->rrmdir($base_extracted);
                    $this->sanitizeExtractedTemplate($target_base);

                    $post = CustomPostFrame::find($request->get("id"));
                    $post->zip_name = $zip_name;
                    // Re-generate fingerprint for the updated ZIP
                    $fingerprintService = new TemplateFingerprintService();
                    $post->fingerprint = $fingerprintService->extractFromZip(public_path('uploads/template/' . $zip_name));
                    $post->save();
                }

                if ($request->file("post_thumb") && $request->file('post_thumb')->isValid()) {
                    if (StorageSetting::getStorageSetting("storage") == "DigitalOcean") {
                        $image = $request->file('post_thumb');
                        $fileName = Str::uuid() . '.' . $image->getClientOriginalExtension();

                        $path = Storage::disk('spaces')->put('uploads/' . $fileName, file_get_contents($image), 'public');

                        $size = getimagesize(Storage::disk('spaces')->url('uploads/' . $fileName));
                        if ($size[0] > $size[1]) {
                            $type = "landscape";
                        }
                        if ($size[0] < $size[1]) {
                            $type = "portrait";
                        }
                        if ($size[0] == $size[1]) {
                            $type = "square";
                        }
                    } else {
                        $file = $request->file('post_thumb');
                        $destinationPath = public_path('uploads');
                        $extension = $file->getClientOriginalExtension();
                        $fileName = Str::uuid() . '.' . $extension;
                        $file->move($destinationPath, $fileName);

                        $size = getimagesize(asset('uploads/' . $fileName));
                        if ($size[0] > $size[1]) {
                            $type = "landscape";
                        }
                        if ($size[0] < $size[1]) {
                            $type = "portrait";
                        }
                        if ($size[0] == $size[1]) {
                            $type = "square";
                        }
                    }

                    $post = CustomPostFrame::find($request->get("id"));
                    $post->height = $size[1];
                    $post->width = $size[0];
                    $post->image_type = $type;
                    $post->aspect_ratio = $this->getAspectRatio($size[0], $size[1]);
                    $post->frame_image = $fileName;
                    $post->save();
                }

                return redirect()->route("custom-post-frame.index");
            }
        }
    }

    public function destroy($id)
    {
        CustomPostFrame::find($id)->delete();
        return redirect()->route('custom-post-frame.index');
    }

    private function upload_image($file, $field, $id)
    {
        $destinationPath = public_path('uploads');
        $extension = $file->getClientOriginalExtension();
        $fileName = Str::uuid() . '.' . $extension;
        $file->move($destinationPath, $fileName);

        $image = CustomPostFrame::find($id);
        $image->$field = $fileName;
        $image->save();
    }

    private function sanitizeExtractedTemplate($target_base)
    {
        $json_dir = $target_base . '/json/';
        $skins_dir = $target_base . '/skins/';
        
        if (!\File::isDirectory($json_dir) || !\File::isDirectory($skins_dir)) {
            return;
        }

        $json_files = \File::files($json_dir);
        $skin_folders = \File::directories($skins_dir);
        
        if (count($json_files) == 0 || count($skin_folders) == 0) {
            return;
        }

        $json_file = $json_files[0];
        $skin_folder = $skin_folders[0];

        $json_content = file_get_contents($json_file->getRealPath());
        $layers = json_decode($json_content, true);
        
        if (!is_array($layers) || !isset($layers['layers'])) {
            return;
        }

        // Get template canvas size from JSON info
        $canvas_w = isset($layers['info']['width']) ? (int)$layers['info']['width'] : 1080;
        $canvas_h = isset($layers['info']['height']) ? (int)$layers['info']['height'] : 1080;
        $max_dimension = 1080; // Mobile-friendly maximum

        // Calculate scale factor if canvas is larger than max
        $scale = 1.0;
        if ($canvas_w > $max_dimension || $canvas_h > $max_dimension) {
            $scale = $max_dimension / max($canvas_w, $canvas_h);
            // Update the canvas info
            $layers['info']['width'] = (int)round($canvas_w * $scale);
            $layers['info']['height'] = (int)round($canvas_h * $scale);
        }

        // Build a case-insensitive map of actual files on disk
        $actual_files = [];
        foreach (\File::files($skin_folder) as $file) {
            $actual_files[strtolower($file->getFilename())] = $file->getFilename();
        }

        foreach ($layers['layers'] as &$layer) {
            if (isset($layer['type']) && $layer['type'] === 'image') {
                $name = $layer['name'] ?? '';
                if (!$name) continue;
                
                // Try to find the matching PNG file on disk
                $expectedNames = [
                    strtolower($name . '.png'),
                    strtolower(str_replace(' ', '-', $name) . '.png'),
                    strtolower(str_replace(' ', '_', $name) . '.png'),
                    strtolower(preg_replace('/[^a-zA-Z0-9 \-_]/', '_', $name) . '.png'),
                    strtolower(str_replace(' ', '-', preg_replace('/[^a-zA-Z0-9 \-_]/', '_', $name)) . '.png'),
                ];
                
                $actual_filename = null;
                foreach ($expectedNames as $expected) {
                    if (isset($actual_files[$expected])) {
                        $actual_filename = $actual_files[$expected];
                        break;
                    }
                }
                
                if (!$actual_filename) continue;
                
                // Update the layer "name" to match the actual filename (without .png)
                $file_basename = pathinfo($actual_filename, PATHINFO_FILENAME);
                $layer['name'] = $file_basename;
                
                // Update the JSON src
                $layer['src'] = '../skins/' . basename($skin_folder) . '/' . $actual_filename;
                
                $image_path = $skin_folder . '/' . $actual_filename;
                $size = @getimagesize($image_path);
                if ($size) {
                    $orig_w = $size[0];
                    $orig_h = $size[1];
                    
                    // Resize image if it's larger than max_dimension
                    if ($orig_w > $max_dimension || $orig_h > $max_dimension) {
                        $this->resizePng($image_path, $max_dimension);
                        // Re-read dimensions after resize
                        $size = @getimagesize($image_path);
                        if ($size) {
                            $orig_w = $size[0];
                            $orig_h = $size[1];
                        }
                    }
                    
                    // Scale the coordinates if canvas was scaled down
                    if ($scale < 1.0) {
                        $layer['x'] = max(0, (int)round((isset($layer['x']) ? $layer['x'] : 0) * $scale));
                        $layer['y'] = max(0, (int)round((isset($layer['y']) ? $layer['y'] : 0) * $scale));
                    } else {
                        $layer['x'] = max(0, isset($layer['x']) ? $layer['x'] : 0);
                        $layer['y'] = max(0, isset($layer['y']) ? $layer['y'] : 0);
                    }
                    
                    $layer['w'] = $orig_w;
                    $layer['h'] = $orig_h;
                    $layer['width'] = $orig_w;
                    $layer['height'] = $orig_h;
                }
            } elseif (isset($layer['type']) && $layer['type'] === 'text' && $scale < 1.0) {
                // Scale text layer positions and sizes too
                if (isset($layer['x'])) $layer['x'] = round($layer['x'] * $scale, 2);
                if (isset($layer['y'])) $layer['y'] = round($layer['y'] * $scale, 2);
                if (isset($layer['w'])) $layer['w'] = round($layer['w'] * $scale, 2);
                if (isset($layer['h'])) $layer['h'] = round($layer['h'] * $scale, 2);
                if (isset($layer['width'])) $layer['width'] = round($layer['width'] * $scale, 2);
                if (isset($layer['height'])) $layer['height'] = round($layer['height'] * $scale, 2);
                if (isset($layer['size'])) $layer['size'] = round($layer['size'] * $scale, 2);
                if (isset($layer['lineHeight'])) $layer['lineHeight'] = round($layer['lineHeight'] * $scale, 2);
                
                // Scale shape-related properties in text layers
                if (isset($layer['pathData'])) {
                    foreach ($layer['pathData'] as &$pathGroup) {
                        if (isset($pathGroup['points'])) {
                            foreach ($pathGroup['points'] as &$point) {
                                foreach (['anchor', 'forward', 'backward'] as $ptype) {
                                    if (isset($point[$ptype]['x'])) $point[$ptype]['x'] = round($point[$ptype]['x'] * $scale, 2);
                                    if (isset($point[$ptype]['y'])) $point[$ptype]['y'] = round($point[$ptype]['y'] * $scale, 2);
                                }
                            }
                        }
                    }
                }
                if (isset($layer['radiusX'])) $layer['radiusX'] = round($layer['radiusX'] * $scale, 2);
                if (isset($layer['radiusY'])) $layer['radiusY'] = round($layer['radiusY'] * $scale, 2);
                if (isset($layer['centerX'])) $layer['centerX'] = round($layer['centerX'] * $scale, 2);
                if (isset($layer['centerY'])) $layer['centerY'] = round($layer['centerY'] * $scale, 2);
            }
        }
        
        // 1. Sort layers by z_index ascending (some extractors output descending)
        usort($layers['layers'], function($a, $b) {
            $za = $a['z_index'] ?? 0;
            $zb = $b['z_index'] ?? 0;
            return $za <=> $zb;
        });

        // 2. Re-assign strict ascending z_index and fix base layer (Layer-0)
        $z = 1;
        foreach ($layers['layers'] as $i => &$layer) {
            $layer['z_index'] = $z++;
            
            // Fix the base layer (first layer in rendering order)
            if ($i === 0) {
                $layer['is_background'] = 1; // Force app to recognize as background
                
                // If it lacks a fillColor, it might render as transparent/blank
                if (empty($layer['fillColor'])) {
                    if (!empty($layer['fillGradient']['colors'][0]['color'])) {
                        // Inherit from gradient if available
                        $layer['fillColor'] = $layer['fillGradient']['colors'][0]['color'];
                    } else {
                        // Default to white if nothing is provided
                        $layer['fillColor'] = '#ffffff';
                    }
                }
            }
        }
        
        file_put_contents($json_file->getRealPath(), json_encode($layers, JSON_PRETTY_PRINT));
    }

    /**
     * Resize a PNG image so its largest dimension does not exceed $maxDim.
     * Maintains aspect ratio and preserves transparency.
     */
    private function resizePng($filePath, $maxDim)
    {
        $size = @getimagesize($filePath);
        if (!$size) return;
        
        $origW = $size[0];
        $origH = $size[1];
        
        if ($origW <= $maxDim && $origH <= $maxDim) return;
        
        $ratio = min($maxDim / $origW, $maxDim / $origH);
        $newW = (int)round($origW * $ratio);
        $newH = (int)round($origH * $ratio);
        
        $src = @imagecreatefrompng($filePath);
        if (!$src) return;
        
        $dst = imagecreatetruecolor($newW, $newH);
        // Preserve transparency
        imagealphablending($dst, false);
        imagesavealpha($dst, true);
        $transparent = imagecolorallocatealpha($dst, 0, 0, 0, 127);
        imagefill($dst, 0, 0, $transparent);
        
        imagecopyresampled($dst, $src, 0, 0, 0, 0, $newW, $newH, $origW, $origH);
        
        imagepng($dst, $filePath, 9);
        
        imagedestroy($src);
        imagedestroy($dst);
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
}

