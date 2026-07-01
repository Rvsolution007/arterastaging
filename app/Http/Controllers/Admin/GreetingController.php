<?php

namespace App\Http\Controllers\Admin;

use Auth;
use App\Models\Language;
use App\Models\GreetingCategory;
use Illuminate\Support\Str;
use Illuminate\Http\Request;
use App\Models\StorageSetting;
use App\Models\Greeting;
use App\Http\Controllers\Controller;
use Illuminate\Support\Facades\File;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Facades\Validator;

class GreetingController extends Controller
{
    public function __construct()
    {
        // $this->middleware('permission:Greeting'); // Optional
    }

    public function index()
    {
        $index['greetingCategory'] = GreetingCategory::get();
        $index['data'] = Greeting::orderBy('id', 'DESC')->paginate(12);
        return view("greeting.index", $index);
    }

    public function create()
    {
        $index['greetingCategory'] = GreetingCategory::where('status', 1)->get();
        $index['language'] = Language::where('status', 1)->get();
        return view("greeting.create", $index);
    }

    public function store(Request $request)
    {
        if ($request->greeting_type == "simple") {
            $validation = Validator::make($request->all(), [
                "greeting_category_id" => 'required',
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

                        $id = Greeting::create([
                            "greeting_type" => $request->get("greeting_type"),
                            "greeting_category_id" => $request->get("greeting_category_id"),
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
                            $post = Greeting::find($id);
                            $post->frame_image = $file;
                            $post->save();
                        } else {
                            $this->upload_image($image, "frame_image", $id);
                        }
                    }
                }
                return redirect()->route("greeting.index");
            }
        }
        if ($request->greeting_type == "editable") {
            $validation = Validator::make($request->all(), [
                "greeting_category_id" => 'required',
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
                        $destinationPath = base_path('uploads');
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

                Greeting::create([
                    "greeting_type" => $request->get("greeting_type"),
                    "greeting_category_id" => $request->get("greeting_category_id"),
                    "language_id" => $request->get("language_id"),
                    "zip_name" => $zip_name,
                    "user_id" => Auth::User()->id,
                    "paid" => 1,
                    "height" => $size[1],
                    "width" => $size[0],
                    "image_type" => $type,
                    "aspect_ratio" => $this->getAspectRatio($size[0], $size[1]),
                    "frame_image" => $fileName,
                ]);

                return redirect()->route("greeting.index");
            }
        }
    }

    public function greeting_status(Request $request)
    {
        $category = Greeting::find($request->get("id"));
        $category->status = ($request->get("checked") == "true") ? 1 : 0;
        $category->save();
    }

    public function greeting_action(Request $request)
    {
        $ids = explode(",", $request->select_post);
        if ($request->select_post != null) {
            if ($request->action_type == "enable") {
                foreach ($ids as $id) {
                    $category = Greeting::find($id);
                    $category->status = 1;
                    $category->save();
                }
            }

            if ($request->action_type == "disable") {
                foreach ($ids as $id) {
                    $category = Greeting::find($id);
                    $category->status = 0;
                    $category->save();
                }
            }

            if ($request->action_type == "delete") {
                foreach ($ids as $id) {
                    Greeting::find($id)->delete();
                }
            }
        }

        return redirect()->route("greeting.index");
    }

    public function greeting_get($id)
    {
        $index['greetingCategory'] = GreetingCategory::get();
        $index['data'] = Greeting::where('greeting_category_id', $id)->paginate(12);
        $c_name = GreetingCategory::find($id);
        $index['name'] = $c_name->name;

        return view("greeting.index", $index);
    }

    public function greeting_type(Request $request)
    {
        $category = Greeting::find($request->get("id"));
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
        $index['greeting'] = Greeting::find($id);
        $index['greetingCategory'] = GreetingCategory::get();
        $index['language'] = Language::get();
        return view("greeting.edit", $index);
    }

    public function update(Request $request, $id)
    {
        if ($request->greeting_type == "simple") {
            $validation = Validator::make($request->all(), [
                "greeting_category_id" => 'required',
                "language_id" => 'required',
                "frame_image" => "nullable|mimes:jpg,png,jpeg",
            ]);

            if ($validation->fails()) {
                return back()->withErrors($validation)->withInput();
            } else {
                $category = Greeting::find($request->get("id"));
                $category->greeting_type = $request->get("greeting_type");
                $category->greeting_category_id = $request->get("greeting_category_id");
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

                        $image = Greeting::find($request->get("id"));
                        $image->frame_image = $fileName;
                        $image->height = $size[1];
                        $image->width = $size[0];
                        $image->image_type = $type;
                        $image->aspect_ratio = $this->getAspectRatio($size[0], $size[1]);
                        $image->save();
                    } else {
                        $this->upload_image($request->file("frame_image"), "frame_image", $id);

                        $image = Greeting::find($request->get("id"));
                        $image->height = $size[1];
                        $image->width = $size[0];
                        $image->image_type = $type;
                        $image->aspect_ratio = $this->getAspectRatio($size[0], $size[1]);
                        $image->save();
                    }
                }

                return redirect()->route('greeting.index');
            }
        }

        if ($request->greeting_type == "editable") {
            $validation = Validator::make($request->all(), [
                "greeting_category_id" => 'required',
                "language_id" => 'required',
            ]);

            if ($validation->fails()) {
                return back()->withErrors($validation)->withInput();
            } else {
                $post = Greeting::find($request->get("id"));
                $post->greeting_type = $request->get("greeting_type");
                $post->greeting_category_id = $request->get("greeting_category_id");
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

                    $post = Greeting::find($request->get("id"));
                    $post->zip_name = $zip_name;
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
                        $destinationPath = base_path('uploads');
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

                    $post = Greeting::find($request->get("id"));
                    $post->height = $size[1];
                    $post->width = $size[0];
                    $post->image_type = $type;
                    $post->aspect_ratio = $this->getAspectRatio($size[0], $size[1]);
                    $post->frame_image = $fileName;
                    $post->save();
                }

                return redirect()->route("greeting.index");
            }
        }
    }

    public function destroy($id)
    {
        Greeting::find($id)->delete();
        return redirect()->route('greeting.index');
    }

    private function upload_image($file, $field, $id)
    {
        $destinationPath = base_path('uploads');
        $extension = $file->getClientOriginalExtension();
        $fileName = Str::uuid() . '.' . $extension;
        $file->move($destinationPath, $fileName);

        $image = Greeting::find($id);
        $image->$field = $fileName;
        $image->save();
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
