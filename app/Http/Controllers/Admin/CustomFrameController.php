<?php

namespace App\Http\Controllers\Admin;

use Auth;
use App\Models\Category;
use App\Models\Language;
use App\Models\CustomFrame;
use Illuminate\Support\Str;
use Illuminate\Http\Request;
use App\Models\StorageSetting;
use App\Http\Controllers\Controller;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Facades\Validator;

class CustomFrameController extends Controller
{
    public function __construct()
    {
        $this->middleware('permission:Users');
    }

    public function store(Request $request)
    {
        $validation = Validator::make($request->all(), [
            "user_id" => 'required',
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

                    $id = CustomFrame::create([
                        "user_id" => $request->user_id,
                        "height" => $size[1],
                        "width" => $size[0],
                        "image_type" => $type,
                        "aspect_ratio" => $this->getAspectRatio($size[0], $size[1]),
                    ])->id;

                    if (StorageSetting::getStorageSetting("storage") == "DigitalOcean") {
                        if ($request->file("frame_image")) {
                            $file = Str::uuid() . '.' . $image->getClientOriginalExtension();

                            $path = Storage::disk('spaces')->put('uploads/' . $file, file_get_contents($image), 'public');

                            $c = CustomFrame::find($id);
                            $c->frame_image = $file;
                            $c->save();
                        }
                    } else {
                        $this->upload_image($image, "frame_image", $id);
                    }
                }
            }

            return back();
        }
    }

    public function custom_frame_status(Request $request)
    {
        $custom = CustomFrame::find($request->get("id"));
        $custom->status = ($request->get("checked") == "true") ? 1 : 0;
        $custom->save();
    }

    public function edit($id)
    {
        $index['customFrame'] = CustomFrame::find($id);
        $index['category'] = Category::get();
        $index['language'] = Language::get();
        return view("category_post.edit", $index);
    }

    public function update(Request $request, $id)
    {
        $validation = Validator::make($request->all(), [
            "frame_image" => "nullable|mimes:jpg,png,jpeg",
        ]);

        if ($validation->fails()) {
            return back()->withErrors($validation)->withInput();
        } else {
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

                $frame = CustomFrame::find($id);
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

                        $c = CustomFrame::find($id);
                        $c->frame_image = $file;
                        $c->save();
                    }
                } else {
                    $this->upload_image($request->file("frame_image"), "frame_image", $id);
                }
            }

            return back();
        }
    }

    public function destroy($id)
    {
        $customFrame = CustomFrame::find($id);
        if (StorageSetting::getStorageSetting("storage") == "DigitalOcean") {
            Storage::disk('spaces')->delete('uploads/' . $customFrame->frame_image);
        } else {
            unlink('./uploads/' . $customFrame->frame_image);
        }

        CustomFrame::find($id)->delete();
        return back();
    }

    private function upload_image($file, $field, $id)
    {
        $destinationPath = './uploads';
        $extension = $file->getClientOriginalExtension();
        $fileName = Str::uuid() . '.' . $extension;
        $file->move($destinationPath, $fileName);

        $image = CustomFrame::find($id);
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
}
