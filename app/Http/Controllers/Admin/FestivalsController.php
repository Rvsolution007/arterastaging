<?php

namespace App\Http\Controllers\Admin;

use App\Models\Story;
use App\Models\Video;
use App\Models\Festivals;
use App\Models\FeaturePost;
use Illuminate\Support\Str;
use Illuminate\Http\Request;
use App\Models\FestivalsPost;
use App\Models\StorageSetting;
use App\Http\Controllers\Controller;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Facades\Validator;

class FestivalsController extends Controller
{
    public function __construct()
    {
        $this->middleware('permission:Festival');
    }

    public function index()
    {
        $index['data'] = Festivals::orderBy('id', 'DESC')->paginate(12);
        $index['festival'] = Festivals::get();
        return view("festivals.index", $index);
    }

    public function create()
    {
        return view("festivals.create");
    }

    public function store(Request $request)
    {
        $validation = Validator::make($request->all(), [
            'title' => 'required',
            "image" => "nullable|mimes:jpg,png,jpeg",
            "festivals_date" => 'required',
            "activation_date" => 'required',
        ]);

        if ($validation->fails()) {
            return back()->withErrors($validation)->withInput();
        } else {
            $id = Festivals::create([
                "title" => $request->get("title"),
                "festivals_date" => date_format(date_create(implode("", preg_split("/[-\s:,]/", $request->get("festivals_date")))), "Y-m-d"),
                "activation_date" => date_format(date_create(implode("", preg_split("/[-\s:,]/", $request->get("activation_date")))), "Y-m-d"),
            ])->id;

            if (StorageSetting::getStorageSetting("storage") == "DigitalOcean") {
                if ($request->file("image") && $request->file('image')->isValid()) {
                    $image = $request->file('image');
                    $file = Str::uuid() . '.' . $image->getClientOriginalExtension();

                    $path = Storage::disk('spaces')->put('uploads/' . $file, file_get_contents($image), 'public');

                    $f = Festivals::find($id);
                    $f->image = $file;
                    $f->save();
                }
            } else {
                if ($request->file("image") && $request->file('image')->isValid()) {
                    $this->upload_image($request->file("image"), "image", $id);
                }
            }

            return redirect()->route("festivals.index");
        }
    }

    public function festivals_status(Request $request)
    {
        $festivals = Festivals::find($request->get("id"));
        $festivals->status = ($request->get("checked") == "true") ? 1 : 0;
        $festivals->save();
    }

    public function festivals_feature_status(Request $request)
    {
        $post = FeaturePost::where("festival_id", $request->get("id"))->get();
        if ($post->isEmpty()) {
            FeaturePost::create([
                "festival_id" => $request->get("id"),
                "type" => "festival",
            ]);
            return 1;
        } else {
            FeaturePost::where("festival_id", $request->get("id"))->delete();
            return 0;
        }
    }

    public function edit($id)
    {
        $festivals = Festivals::find($id);
        return view("festivals.edit", compact("festivals"));
    }

    public function update(Request $request, $id)
    {
        $validation = Validator::make($request->all(), [
            "title" => 'required',
            "image" => "nullable|mimes:jpg,png,jpeg",
            "festivals_date" => 'required',
            "activation_date" => 'required',
        ]);

        if ($validation->fails()) {
            return back()->withErrors($validation)->withInput();
        } else {
            $festivals = Festivals::find($request->get("id"));
            $festivals->title = $request->get("title");
            $festivals->festivals_date = date_format(date_create(implode("", preg_split("/[-\s:,]/", $request->get("festivals_date")))), "Y-m-d");
            $festivals->activation_date = date_format(date_create(implode("", preg_split("/[-\s:,]/", $request->get("activation_date")))), "Y-m-d");
            $festivals->save();

            if (StorageSetting::getStorageSetting("storage") == "DigitalOcean") {
                if ($request->file("image") && $request->file('image')->isValid()) {
                    $image = $request->file('image');
                    $file = Str::uuid() . '.' . $image->getClientOriginalExtension();

                    $path = Storage::disk('spaces')->put('uploads/' . $file, file_get_contents($image), 'public');

                    $f = Festivals::find($request->get("id"));
                    $f->image = $file;
                    $f->save();
                }
            } else {
                if ($request->file("image") && $request->file('image')->isValid()) {
                    $this->upload_image($request->file("image"), "image", $id);
                }
            }

            return redirect()->route('festivals.index');
        }
    }

    public function festivals_search(Request $request)
    {
        $index['data'] = Festivals::where("title", 'LIKE', '%' . $request->search . '%')->paginate(12);
        $index['festival'] = Festivals::get();
        $index['name'] = $request->search;
        return view("festivals.index", $index);
    }

    public function festivals_action(Request $request)
    {
        $ids = explode(",", $request->select_post);
        if ($request->select_post != null) {
            if ($request->action_type == "enable") {
                foreach ($ids as $id) {
                    $item = Festivals::find($id);
                    if ($item) {
                        $item->status = 1;
                        $item->save();
                    }
                }
            }

            if ($request->action_type == "disable") {
                foreach ($ids as $id) {
                    $item = Festivals::find($id);
                    if ($item) {
                        $item->status = 0;
                        $item->save();
                    }
                }
            }

            if ($request->action_type == "delete") {
                foreach ($ids as $id) {
                    $this->destroy($id);
                }
            }
        }

        return redirect()->route("festivals.index");
    }

    public function destroy($id)
    {
        $festivals = Festivals::find($id);
        $festivalsFrame = FestivalsPost::where('festivals_id', $id)->get();
        $story = Story::where("festival_id", $id)->get();
        $video = Video::where("festival_id", $id)->get();

        if (StorageSetting::getStorageSetting("storage") == "DigitalOcean") {
            Storage::disk('spaces')->delete('uploads/' . $festivals->image);
            foreach ($festivalsFrame as $frame) {
                Storage::disk('spaces')->delete('uploads/' . $frame->frame_image);
            }
            foreach ($story as $s) {
                Storage::disk('spaces')->delete('uploads/' . $s->image);
            }
            foreach ($video as $v) {
                Storage::disk('spaces')->delete('uploads/video/' . $v->video);
            }
        } else {
            if ($festivals->image && file_exists('./uploads/' . $festivals->image)) {
                unlink('./uploads/' . $festivals->image);
            }
            foreach ($festivalsFrame as $frame) {
                if ($frame->frame_image && file_exists('./uploads/' . $frame->frame_image)) {
                    unlink('./uploads/' . $frame->frame_image);
                }
            }
            foreach ($story as $s) {
                if ($s->image && file_exists('./uploads/' . $s->image)) {
                    unlink('./uploads/' . $s->image);
                }
            }
            foreach ($video as $v) {
                if ($v->video && file_exists('./uploads/video/' . $v->video)) {
                    unlink('./uploads/video/' . $v->video);
                }
            }
        }

        Festivals::find($id)->delete();
        FestivalsPost::where('festivals_id', $id)->delete();
        FeaturePost::where('festival_id', $id)->delete();
        Story::where("festival_id", $id)->delete();
        Video::where("festival_id", $id)->delete();

        return redirect()->route('festivals.index');
    }

    private function upload_image($file, $field, $id)
    {
        $destinationPath = './uploads';
        $extension = $file->getClientOriginalExtension();
        $fileName = Str::uuid() . '.' . $extension;
        $file->move($destinationPath, $fileName);

        $image = Festivals::find($id);
        $image->$field = $fileName;
        $image->save();
    }
}
