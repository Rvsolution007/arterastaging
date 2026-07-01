<?php

namespace App\Http\Controllers\Admin;

use Auth;
use App\Models\Sticker;
use App\Models\Language;
use Illuminate\Support\Str;
use Illuminate\Http\Request;
use App\Models\StorageSetting;
use App\Models\StickerCategory;
use App\Http\Controllers\Controller;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Facades\Validator;

class StickerController extends Controller
{
    public function __construct()
    {
        $this->middleware('permission:Sticker');
    }

    public function index()
    {
        $index['category'] = StickerCategory::get();
        $index['data'] = Sticker::orderBy('id', 'DESC')->paginate(12);
        return view("sticker.index", $index);
    }

    public function create()
    {
        $index['category'] = StickerCategory::where('status',1)->get();
        return view("sticker.create", $index);
    }

    public function store(Request $request)
    {
        //dd($request->all());
        $validation = Validator::make($request->all(), [
            "sticker_category_id" => 'required',
        ]);

        if ($validation->fails()) {
            return back()->withErrors($validation)->withInput();
        } else {
            if ($request->has('cropped_images')) 
            {
                $croppedImages = $request->get('cropped_images');
                foreach($croppedImages as $base64Image) 
                {
                    $id = Sticker::create([
                        "sticker_category_id" => $request->get("sticker_category_id"),
                        "keywords" => $request->get("keywords"),
                    ])->id;

                    $image_parts = explode(";base64,", $base64Image);
                    $image_base64 = base64_decode($image_parts[1]);
                    
                    // Convert to WebP
                    $gdImage = imagecreatefromstring($image_base64);
                    imagepalettetotruecolor($gdImage);
                    imagealphablending($gdImage, true);
                    imagesavealpha($gdImage, true);
                    
                    ob_start();
                    imagewebp($gdImage, null, 80);
                    $webp_content = ob_get_clean();
                    imagedestroy($gdImage);

                    $fileName = Str::uuid() . '.webp';

                    if(StorageSetting::getStorageSetting("storage") == "DigitalOcean")
                    {
                        Storage::disk('spaces')->put('uploads/'.$fileName, $webp_content, 'public');
                        
                        $sticker = Sticker::find($id);
                        $sticker->image = $fileName;
                        $sticker->save();
                    }
                    else
                    {
                        $destinationPath = public_path('uploads/' . $fileName);
                        file_put_contents($destinationPath, $webp_content);
                        
                        $sticker = Sticker::find($id);
                        $sticker->image = $fileName;
                        $sticker->save();
                    }
                }
            }
            elseif ($request->file("image")) 
            {
                $removedImages = json_decode($request->get("deleted_file_ids"), true);
                $images = $request->file('image');
                foreach($images as $image) 
                {
                    if($removedImages != null)
                    {
                        if (in_array($image->getClientOriginalName(), $removedImages)) {
                            continue;
                        }
                    }
                    
                    $id = Sticker::create([
                        "sticker_category_id" => $request->get("sticker_category_id"),
                        "keywords" => $request->get("keywords"),
                    ])->id;

                    if(StorageSetting::getStorageSetting("storage") == "DigitalOcean")
                    {
                        $file = Str::uuid() . '.webp';
                
                        $gdImage = imagecreatefromstring(file_get_contents($image->getRealPath()));
                        imagepalettetotruecolor($gdImage);
                        imagealphablending($gdImage, true);
                        imagesavealpha($gdImage, true);
                        
                        ob_start();
                        imagewebp($gdImage, null, 80);
                        $webp_content = ob_get_clean();
                        imagedestroy($gdImage);

                        $path = Storage::disk('spaces')->put('uploads/'.$file, $webp_content,'public');
                        
                        $sticker = Sticker::find($id);
                        $sticker->image = $file;
                        $sticker->save();
                    }
                    else
                    {
                        $this->upload_image($image,"image", $id);
                    }
                }
            }

            return redirect()->route("sticker.index");
        }
    }

    public function sticker_status(Request $request)
    {
        $category = Sticker::find($request->get("id"));
        $category->status = ($request->get("checked")=="true")?1:0;
        $category->save();
    }

    public function sticker_action(Request $request)
    {
        $ids = explode(",",$request->select_post);
        if($request->select_post != null)
        {
            if($request->action_type == "enable")
            {
                foreach($ids as $id){
                    $category = Sticker::find($id);
                    $category->status = 1;
                    $category->save();
                }
            }
    
            if($request->action_type == "disable")
            {
                foreach($ids as $id){
                    $category = Sticker::find($id);
                    $category->status = 0;
                    $category->save();
                }
            }
    
            if($request->action_type == "delete")
            {
                foreach($ids as $id){
                    Sticker::find($id)->delete();
                }
            }
        }

        return redirect()->route("sticker.index");
    }

    public function sticker_category_get($id)
    {
        $index['category'] = StickerCategory::get();
        $index['data'] = Sticker::where('sticker_category_id',$id)->paginate(12);
        $c_name=StickerCategory::find($id);
        $index['name'] = $c_name->name;

        return view("sticker.index", $index);
    }

    public function edit($id)
    {
        $index['sticker'] = Sticker::find($id);
        $index['category'] = StickerCategory::get();
        return view("sticker.edit", $index);
    }

    public function update(Request $request, $id)
    {
        $validation = Validator::make($request->all(), [
            "sticker_category_id" => 'required',
            "image" => "nullable|mimes:jpg,png,jpeg",
        ]);

        if ($validation->fails()) {
            return back()->withErrors($validation)->withInput();
        } else {
            $category = Sticker::find($request->get("id"));
            $category->sticker_category_id = $request->get("sticker_category_id");
            $category->keywords = $request->get("keywords");
            $category->save();

            if(StorageSetting::getStorageSetting("storage") == "DigitalOcean")
            {
                if ($request->file("image") && $request->file('image')->isValid()) {
                    $image = $request->file('image');
                    $file = Str::uuid() . '.webp';
            
                    $gdImage = imagecreatefromstring(file_get_contents($image->getRealPath()));
                    imagepalettetotruecolor($gdImage);
                    imagealphablending($gdImage, true);
                    imagesavealpha($gdImage, true);
                    
                    ob_start();
                    imagewebp($gdImage, null, 80);
                    $webp_content = ob_get_clean();
                    imagedestroy($gdImage);

                    $path = Storage::disk('spaces')->put('uploads/'.$file, $webp_content,'public');
                    
                    $sticker = Sticker::find($request->get("id"));
                    $sticker->image = $file;
                    $sticker->save();
                }
            }
            else
            {
                if ($request->file("image") && $request->file('image')->isValid()) {
                    $this->upload_image($request->file("image"),"image", $id);
                }
            }

            return redirect()->route('sticker.index');
        }
    }

    public function destroy($id)
    {
        $sticker = Sticker::find($id);
        if(StorageSetting::getStorageSetting("storage") == "DigitalOcean")
        {
            Storage::disk('spaces')->delete('uploads/'.$sticker->image);
        }
        else
        {
            unlink(public_path('uploads/').$sticker->image);
        }

        Sticker::find($id)->delete();
        return redirect()->route('sticker.index');
    }

    private function upload_image($file,$field,$id)
    {
        $destinationPath = public_path('uploads');
        $fileName = Str::uuid() . '.webp';
        
        $gdImage = imagecreatefromstring(file_get_contents($file->getRealPath()));
        imagepalettetotruecolor($gdImage);
        imagealphablending($gdImage, true);
        imagesavealpha($gdImage, true);
        
        imagewebp($gdImage, $destinationPath . '/' . $fileName, 80);
        imagedestroy($gdImage);
        
        $image = Sticker::find($id);
        $image->$field = $fileName;
        $image->save();
    }

    public function generateAi(Request $request)
    {
        $request->validate([
            'category_name' => 'required|string|max:255'
        ]);

        $categoryName = $request->category_name;
        
        try {
            $aiService = new \App\Services\VertexAIService(Auth::id() ?? 1);
            $prompt = "You are an AI that provides emojis for a category. I will give you a category name. You must return exactly 10 standard emoji unicode hex codes (without 'U+', e.g., '1f436') that best represent this category. Output ONLY a comma-separated list of hex codes. No explanation, no extra text.";
            
            $aiResponse = $aiService->generateContent($prompt, [
                ['role' => 'user', 'text' => "Category: " . $categoryName]
            ]);
            
            $hexCodes = array_map('trim', explode(',', $aiResponse['text']));
            $hexCodes = array_filter($hexCodes, function($h) {
                return !empty($h) && preg_match('/^[0-9a-fA-F-]+$/', $h);
            });
            
            if (empty($hexCodes)) {
                return response()->json(['success' => false, 'message' => 'AI failed to generate valid emojis.']);
            }
            
            // Get or create category
            $category = StickerCategory::firstOrCreate(
                ['name' => $categoryName],
                ['status' => 1]
            );
            
            $added = 0;
            foreach ($hexCodes as $code) {
                $url = "https://raw.githubusercontent.com/twitter/twemoji/master/assets/72x72/" . strtolower($code) . ".png";
                $imgData = @file_get_contents($url);
                
                if ($imgData) {
                    $fileName = Str::uuid() . '.png';
                    
                    // Save to public/uploads
                    $publicPath = public_path('uploads/' . $fileName);
                    file_put_contents($publicPath, $imgData);
                    
                    // Save to root uploads to ensure compatibility with XAMPP setup
                    $rootPath = base_path('uploads/' . $fileName);
                    if (!is_dir(base_path('uploads'))) {
                        mkdir(base_path('uploads'), 0755, true);
                    }
                    file_put_contents($rootPath, $imgData);
                    
                    Sticker::create([
                        'sticker_category_id' => $category->id,
                        'image' => $fileName,
                        'status' => 1,
                    ]);
                    $added++;
                }
            }
            
            return response()->json(['success' => true, 'message' => "Successfully generated and added $added stickers for '$categoryName'."]);
            
        } catch (\Exception $e) {
            return response()->json(['success' => false, 'message' => $e->getMessage()]);
        }
    }
}
