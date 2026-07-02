<?php

namespace App\Http\Controllers\Admin;

use Auth;
use App\Models\Category;
use App\Models\Language;
use Illuminate\Support\Str;
use App\Traits\WatermarkImageTrait;
use Illuminate\Http\Request;
use App\Models\CategoryPost;
use App\Models\StorageSetting;
use App\Http\Controllers\Controller;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Facades\Validator;

class CategoryPostController extends Controller
{
    use WatermarkImageTrait;

    public function __construct()
    {
        $this->middleware('permission:CategoryPost');
    }

    public function index(Request $request)
    {
        $index['category'] = Category::get();
        $tab = $request->query('tab', 'image');
        $index['tab'] = $tab;

        if ($tab == 'video') {
            $index['data'] = \App\Models\Video::where('type', 'category')->orderBy('id', 'DESC')->paginate(12);
        } else {
            $index['data'] = CategoryPost::orderBy('id', 'DESC')->paginate(12);
        }
        return view("category_post.index", $index);
    }

    public function create(Request $request)
    {
        $index['category'] = Category::where('status', 1)->get();
        $index['language'] = Language::where('status', 1)->get();

        if ($request->query('type') == 'video') {
            return view("category_post.create_video", $index);
        }

        return view("category_post.create", $index);
    }

    public function store(Request $request)
    {
        $validation = Validator::make($request->all(), [
            "category_id" => 'required',
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

                    $id = CategoryPost::create([
                        "category_id" => $request->get("category_id"),
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

                        $f = CategoryPost::find($id);
                        $f->frame_image = $file;
                        $f->save();

                        $this->applyWatermark($file, 'DigitalOcean');
                    } else {
                        $this->upload_image($image, "frame_image", $id);
                    }
                }
            }

            return redirect()->route("category-post.index");
        }
    }

    public function category_post_status(Request $request)
    {
        $category = CategoryPost::find($request->get("id"));
        $category->status = ($request->get("checked") == "true") ? 1 : 0;
        $category->save();
    }

    public function category_post_landing(Request $request)
    {
        $category = CategoryPost::find($request->get("id"));
        $category->show_on_landing = ($request->get("checked") == "true") ? 1 : 0;
        $category->save();
    }

    public function category_post_ai(Request $request)
    {
        $category = CategoryPost::find($request->get("id"));
        $category->is_ai = ($request->get("checked") == "true") ? 1 : 0;
        $category->save();
    }

    public function category_post_action(Request $request)
    {
        $ids = explode(",", $request->select_post);
        if ($request->select_post != null) {
            if ($request->action_type == "enable") {
                foreach ($ids as $id) {
                    $category = CategoryPost::find($id);
                    $category->status = 1;
                    $category->save();
                }
            }

            if ($request->action_type == "disable") {
                foreach ($ids as $id) {
                    $category = CategoryPost::find($id);
                    $category->status = 0;
                    $category->save();
                }
            }

            if ($request->action_type == "delete") {
                foreach ($ids as $id) {
                    CategoryPost::find($id)->delete();
                }
            }
        }

        return redirect()->route("category-post.index");
    }

    public function get_category_post(Request $request)
    {
        $category = CategoryPost::where('category_id', $request->id)->get();

        $html = array();
        foreach ($category as $frame) {
            $status = "";
            $paid = "";
            if ($frame->status == 1) {
                $status = "checked";
            }
            if ($frame->paid == 1) {
                $paid = "checked";
            }
            $html[] = '<div class="col-lg-3 col-sm-6 col-xs-12">
                <div class="block_wallpaper add_wall_category" style="box-shadow:0px 3px 8px rgba(0, 0, 0, 0.3)">
                
                    <div class="wall_image_title">
                        <h2 style="font-size: 20px">'
                . $frame->category->name . '              
                        </h2>
                        <ul>
                        <li><a href="' . url("admin/category-post/" . $frame->id . "/edit") . '" data-toggle="tooltip" data-tooltip="Edit"><i class="fa fa-edit"></i></a></li>
                        <li><a href="#" data-id="' . $frame->id . '" class="btn_delete_a" data-toggle="modal" data-target="#myModal"><i class="fa fa-trash"></i></a></li>
                        <li>
                            <label class="cl-switch cl-switch-red">
                            <input type="checkbox" class="frame-switch" data-id="' . $frame->id . '" value="1" ' . $status . '>
                            <span class="switcher"></span>
                            </label>
                        </li>
                        <li>
                            <div class="form-check form-check-inline">
                            <div class="ui-switcher" aria-checked="true"></div>
                            <input class="form-check-input checkbox2" type="checkbox" data-id="' . $frame->id . '" value="1" ' . $paid . ' style="display: none;">
                            </div>
                        </li>
                        </ul>
                        <form action="' . url("admin/category-post/" . $frame->id) . '" method="POST" class="form-horizontal" id="form_' . $frame->id . '">
                        <input type="hidden" name="_token" value="' . csrf_token() . '" />
                        <input type="hidden" name="_method" value="DELETE">
                        <input type="hidden" name="id" value="' . $frame->id . '">
                        </form>
                    </div>
                    <span>
                        <img src="' . asset("uploads/" . $frame->frame_image) . '"/>
                    </span>
                </div>
                </div>';
        }

        return $html;
    }

    public function category_get(Request $request, $id)
    {
        $index['category'] = Category::get();
        $c_name = Category::find($id);
        $index['name'] = $c_name->name;
        $tab = $request->query('tab', 'image');
        $index['tab'] = $tab;

        if ($tab == 'video') {
            $index['data'] = \App\Models\Video::where('type', 'category')->where('category_id', $id)->orderBy('id', 'DESC')->paginate(12);
        } else {
            $index['data'] = CategoryPost::where('category_id', $id)->orderBy('id', 'DESC')->paginate(12);
        }

        return view("category_post.index", $index);
    }

    public function category_post_type(Request $request)
    {
        $category = CategoryPost::find($request->get("id"));
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
        $index['categoryPost'] = CategoryPost::find($id);
        $index['category'] = Category::get();
        $index['language'] = Language::get();
        return view("category_post.edit", $index);
    }

    public function update(Request $request, $id)
    {
        $validation = Validator::make($request->all(), [
            "category_id" => 'required',
            "language_id" => 'required',
            "frame_image" => "nullable|mimes:jpg,png,jpeg",
        ]);

        if ($validation->fails()) {
            return back()->withErrors($validation)->withInput();
        } else {
            $category = CategoryPost::find($request->get("id"));
            $category->category_id = $request->get("category_id");
            $category->language_id = $request->get("language_id");
            $category->is_ai = $request->has("is_ai") ? 1 : 0;
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

                $category_post = CategoryPost::find($request->get("id"));
                $category_post->height = $size[1];
                $category_post->width = $size[0];
                $category_post->image_type = $type;
                $category_post->aspect_ratio = $this->getAspectRatio($size[0], $size[1]);
                $category_post->save();

                if (StorageSetting::getStorageSetting("storage") == "DigitalOcean") {
                    if ($request->file("frame_image")) {
                        $image = $request->file('frame_image');
                        $file = Str::uuid() . '.' . $image->getClientOriginalExtension();

                        $path = Storage::disk('spaces')->put('uploads/' . $file, file_get_contents($image), 'public');

                        $user = CategoryPost::find($request->get("id"));
                        $user->frame_image = $file;
                        $user->save();

                        $this->applyWatermark($file, 'DigitalOcean');
                    }
                } else {
                    $this->upload_image($request->file("frame_image"), "frame_image", $id);
                }
            }

            return redirect()->route('category-post.index');
        }
    }

    public function destroy($id)
    {
        $categoryPost = CategoryPost::find($id);
        if (StorageSetting::getStorageSetting("storage") == "DigitalOcean") {
            Storage::disk('spaces')->delete('uploads/' . $categoryPost->frame_image);
        } else {
            unlink(public_path('uploads/') . $categoryPost->frame_image);
        }

        CategoryPost::find($id)->delete();
        return redirect()->route('category-post.index');
    }

    private function upload_image($file, $field, $id)
    {
        $destinationPath = public_path('uploads');
        $extension = $file->getClientOriginalExtension();
        $fileName = Str::uuid() . '.' . $extension;
        $file->move($destinationPath, $fileName);

        $image = CategoryPost::find($id);
        $image->$field = $fileName;
        $image->save();

        if ($field === 'frame_image') {
            $this->applyWatermark($fileName, 'local');
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
