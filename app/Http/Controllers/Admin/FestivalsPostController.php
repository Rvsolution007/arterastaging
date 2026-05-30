<?php

namespace App\Http\Controllers\Admin;

use Auth;
use App\Models\Language;
use App\Models\Festivals;
use Illuminate\Support\Str;
use Illuminate\Http\Request;
use App\Models\FestivalsPost;
use App\Models\StorageSetting;
use App\Http\Controllers\Controller;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Facades\Validator;

class FestivalsPostController extends Controller
{
    public function __construct()
    {
        $this->middleware('permission:FestivalPost');
    }

    public function index(Request $request)
    {
        $index['festivals'] = Festivals::get();
        $tab = $request->query('tab', 'image');
        $index['tab'] = $tab;

        if ($tab == 'video') {
            $index['data'] = \App\Models\Video::where('type', 'festival')->orderBy('id', 'DESC')->paginate(12);
        } else {
            $index['data'] = FestivalsPost::orderBy('id', 'DESC')->paginate(12);
        }
        return view("festivals_post.index", $index);
    }

    public function create(Request $request)
    {
        $index['festivals'] = Festivals::where('status', 1)->get();
        $index['language'] = Language::where('status', 1)->get();

        if ($request->query('type') == 'video') {
            return view("festivals_post.create_video", $index);
        }

        return view("festivals_post.create", $index);
    }

    public function store(Request $request)
    {
        $validation = Validator::make($request->all(), [
            "festivals_id" => 'required',
            "language_id" => 'required',
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

                    $id = FestivalsPost::create([
                        "festivals_id" => $request->get("festivals_id"),
                        "language_id" => $request->get("language_id"),
                        "user_id" => Auth::User()->id,
                        "paid" => 1,
                        "height" => $size[1],
                        "width" => $size[0],
                        "image_type" => $type,
                        "is_ai" => $request->has("is_ai") ? 1 : 0,
                        "aspect_ratio" => $this->getAspectRatio($size[0], $size[1]),
                    ])->id;

                    if (StorageSetting::getStorageSetting("storage") == "DigitalOcean") {
                        $file = Str::uuid() . '.' . $image->getClientOriginalExtension();

                        $path = Storage::disk('spaces')->put('uploads/' . $file, file_get_contents($image), 'public');

                        $f = FestivalsPost::find($id);
                        $f->frame_image = $file;
                        $f->save();
                    } else {
                        $this->upload_image($image, "frame_image", $id);
                    }
                }
            }

            return redirect()->route("festivals-post.index");
        }
    }

    public function festivals_post_status(Request $request)
    {
        $festivals = FestivalsPost::find($request->get("id"));
        $festivals->status = ($request->get("checked") == "true") ? 1 : 0;
        $festivals->save();
    }

    public function festivals_post_ai(Request $request)
    {
        $festivals = FestivalsPost::find($request->get("id"));
        $festivals->is_ai = ($request->get("checked") == "true") ? 1 : 0;
        $festivals->save();
    }

    public function festivals_post_action(Request $request)
    {
        $ids = explode(",", $request->select_post);
        if ($request->select_post != null) {
            if ($request->action_type == "enable") {
                foreach ($ids as $id) {
                    $category = FestivalsPost::find($id);
                    $category->status = 1;
                    $category->save();
                }
            }

            if ($request->action_type == "disable") {
                foreach ($ids as $id) {
                    $category = FestivalsPost::find($id);
                    $category->status = 0;
                    $category->save();
                }
            }

            if ($request->action_type == "delete") {
                foreach ($ids as $id) {
                    FestivalsPost::find($id)->delete();
                }
            }
        }

        return redirect()->route("festivals-post.index");
    }

    public function festival_filter(Request $request, $id)
    {
        $index['festivals'] = Festivals::get();
        $f_name = Festivals::find($id);
        $index['name'] = $f_name->title;
        $tab = $request->query('tab', 'image');
        $index['tab'] = $tab;

        if ($tab == 'video') {
            $index['data'] = \App\Models\Video::where('type', 'festival')->where('festival_id', $id)->orderBy('id', 'DESC')->paginate(12);
        } else {
            $index['data'] = FestivalsPost::where('festivals_id', $id)->orderBy('id', 'DESC')->paginate(12);
        }
        return view("festivals_post.index", $index);
    }

    public function festivals_post_type(Request $request)
    {
        $festivals = FestivalsPost::find($request->get("id"));
        $festivals->paid = ($request->get("checked") == "true") ? 1 : 0;
        $festivals->save();

        if ($festivals->paid == 1) {
            return 1;
        } else {
            return 0;
        }
    }

    public function edit($id)
    {
        $index['festivalsFrame'] = FestivalsPost::find($id);
        $index['festivals'] = Festivals::get();
        $index['language'] = Language::get();
        return view("festivals_post.edit", $index);
    }

    public function update(Request $request, $id)
    {
        $validation = Validator::make($request->all(), [
            "festivals_id" => 'required',
            "language_id" => 'required',
            "frame_image" => "nullable|mimes:jpg,png,jpeg",
        ]);

        if ($validation->fails()) {
            return back()->withErrors($validation)->withInput();
        } else {
            $festivals = FestivalsPost::find($request->get("id"));
            $festivals->festivals_id = $request->get("festivals_id");
            $festivals->language_id = $request->get("language_id");
            $festivals->is_ai = $request->has("is_ai") ? 1 : 0;
            $festivals->save();

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

                $festival_post = FestivalsPost::find($request->get("id"));
                $festival_post->height = $size[1];
                $festival_post->width = $size[0];
                $festival_post->image_type = $type;
                $festival_post->aspect_ratio = $this->getAspectRatio($size[0], $size[1]);
                $festival_post->save();

                if (StorageSetting::getStorageSetting("storage") == "DigitalOcean") {
                    if ($request->file("frame_image")) {
                        $image = $request->file('frame_image');
                        $file = Str::uuid() . '.' . $image->getClientOriginalExtension();

                        $path = Storage::disk('spaces')->put('uploads/' . $file, file_get_contents($image), 'public');

                        $user = FestivalsPost::find($request->get("id"));
                        $user->frame_image = $file;
                        $user->save();
                    }
                } else {
                    $this->upload_image($request->file("frame_image"), "frame_image", $id);
                }
            }

            return redirect()->route('festivals-post.index');
        }
    }

    public function destroy($id)
    {
        $festivalsFrame = FestivalsPost::find($id);
        if (StorageSetting::getStorageSetting("storage") == "DigitalOcean") {
            Storage::disk('spaces')->delete('uploads/' . $festivalsFrame->frame_image);
        } else {
            if ($festivalsFrame->frame_image && file_exists(public_path('uploads/' . $festivalsFrame->frame_image))) {
                unlink(public_path('uploads/' . $festivalsFrame->frame_image));
            }
        }

        FestivalsPost::find($id)->delete();
        return redirect()->route('festivals-post.index');
    }

    private function upload_image($file, $field, $id)
    {
        $destinationPath = public_path('uploads');
        if (!file_exists($destinationPath)) {
            mkdir($destinationPath, 0777, true);
        }
        $extension = $file->getClientOriginalExtension();
        $fileName = Str::uuid() . '.' . $extension;
        $file->move($destinationPath, $fileName);

        $image = FestivalsPost::find($id);
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
