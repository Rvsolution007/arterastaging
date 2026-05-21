<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use App\Models\News;
use App\Models\Story;
use App\Models\Video;
use App\Models\Category;
use App\Models\Festivals;
use App\Models\ApiSetting;
use App\Models\StorageSetting;
use App\Models\Subscription;
use App\Models\CustomPost;
use App\Models\FestivalsPost;
use App\Models\CategoryPost;
use Illuminate\Support\Facades\Storage;

class ContentApiController extends Controller
{
    public function getNews()
    {
        $news = News::orderBy(ApiSetting::getApiSetting("news_order_type"),ApiSetting::getApiSetting("news_order_by"))->get();
    
        if(!$news->isEmpty())
        {
            $data = [];
            foreach ($news as $n) {
                $data[] = array(
                    "id" => $n->id,
                    "title" => $n->title,
                    "description" => $n->description,
                    "image" => ($n->image)?((StorageSetting::getStorageSetting('storage') == 'DigitalOcean')?Storage::disk('spaces')->url('uploads/'.$n->image):asset('uploads/'.$n->image)):"",
                    "date" => date('d M, y',strtotime($n->date))
                );
            }
            return $data;
        }
        else
        {
            return array();
        }
    }

    public function getCategory()
    {
        $category = Category::where('status',1)->orderBy(ApiSetting::getApiSetting("category_order_type"),ApiSetting::getApiSetting("category_order_by"))->get();

        if(!$category->isEmpty())
        {
            $data = [];
            $isDigitalOcean = StorageSetting::getStorageSetting('storage') == 'DigitalOcean';
            $categoryIds = $category->pluck('id')->toArray();
            $videoCategoryIds = Video::where("type","category")->whereIn("category_id", $categoryIds)->distinct()->pluck('category_id')->toArray();

            foreach ($category as $cat) {
                $data[] = array(
                    "categoryId" => $cat->id,
                    "categoryName" => $cat->name,
                    "categoryIcon" => $isDigitalOcean?Storage::disk('spaces')->url('uploads/'.$cat->icon):asset('uploads/'.$cat->icon),
                    "video" => in_array($cat->id, $videoCategoryIds),
                );
            }
            return $data;
        }
        else
        {
            return response()->json([
                'status' => "Error",
                'message' => "No Data Found",
            ], 404);
        }
    }

    public function getFestival(Request $request)
    {
        $query = Festivals::where('status',1)
                    ->where('activation_date',"<=",date("Y-m-d",strtotime('today')));
        
        if ($request->has('date') && !empty($request->date)) {
            $query->whereDate('festivals_date', date('Y-m-d', strtotime($request->date)));
        } else {
            $query->whereDate('festivals_date', ">=", date("Y-m-d",strtotime('today')));
        }

        $festival = $query->orderBy(ApiSetting::getApiSetting("festival_order_type"),ApiSetting::getApiSetting("festival_order_by"))->get();
       
        if(!$festival->isEmpty())
        {
            $isDigitalOcean = StorageSetting::getStorageSetting('storage') == 'DigitalOcean';
            $festivalIds = $festival->pluck('id')->toArray();
            $videoFestivalIds = Video::where("type","festival")->whereIn("festival_id", $festivalIds)->distinct()->pluck('festival_id')->toArray();

            $data = [];
            foreach ($festival as $f) {
                $data[] = array(
                    "festivalId" => $f->id,
                    "festivalTitle" => $f->title,
                    "festivalImage" => ($f->image)?($isDigitalOcean?Storage::disk('spaces')->url('uploads/'.$f->image):asset('uploads/'.$f->image)):"",
                    "festivalDate" => date_format(date_create(implode("", preg_split("/[-\s:,]/",$f->festivals_date))),"d M, y"),
                    "activationDate" => date_format(date_create(implode("", preg_split("/[-\s:,]/",$f->activation_date))),"d M, y"),
                    "isActive" => ($f->activation_date <= date("Y-m-d",strtotime('today')))?true:false,
                    "video" => in_array($f->id, $videoFestivalIds),
                );
            }
            return $data;
        }
        else
        {
            return response()->json([
                'status' => "Error",
                'message' => "No Data Found",
            ], 404);
        }
    }

    public function getStory()
    {
        $story = Story::where('status',1)->orderBy("id","desc")->get();
        
        if(!$story->isEmpty())
        {
            $data = [];
            $isDigitalOcean = StorageSetting::getStorageSetting('storage') == 'DigitalOcean';

            $festivalIds = $story->pluck('festival_id')->filter()->unique()->toArray();
            $categoryIds = $story->pluck('category_id')->filter()->unique()->toArray();
            $subIds = $story->pluck('subscription_id')->filter()->unique()->toArray();
            $customIds = $story->pluck('custom_category_id')->filter()->unique()->toArray();

            $festivals = Festivals::whereIn('id', $festivalIds)->get()->keyBy('id');
            $categories = Category::whereIn('id', $categoryIds)->get()->keyBy('id');
            $subscriptions = Subscription::whereIn('id', $subIds)->get()->keyBy('id');
            $customs = CustomPost::whereIn('id', $customIds)->get()->keyBy('id');

            foreach ($story as $s) {
                $festival = $festivals->get($s->festival_id);
                $category = $categories->get($s->category_id);
                $subscription = $subscriptions->get($s->subscription_id);
                $custom = $customs->get($s->custom_category_id);

                $id = "";
                $name = "";

                if(!empty($festival)) { $id = $festival->id; $name = $festival->title; }
                if(!empty($custom)) { $id = $custom->id; $name = $custom->name; }
                if(!empty($category)) { $id = $category->id; $name = $category->name; }
                if(!empty($subscription)) { $id = $subscription->id; $name = $subscription->plan_name; }

                $data[] = array(
                    "storyId" => $s->id,
                    "storyType" => $s->story_type,
                    "image" => $isDigitalOcean?Storage::disk('spaces')->url('uploads/'.$s->image):asset('uploads/'.$s->image),
                    "id" => $id,
                    "name" => ($name)?$name: $s->external_link_title,
                    "externalLink"=> $s->external_link,
                );
            }
            return $data;
        }
        else
        {
            return response()->json([
                'status' => "Error",
                'message' => "No Data Found",
            ], 404);
        }
    }
}
