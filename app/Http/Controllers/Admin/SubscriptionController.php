<?php

namespace App\Http\Controllers\Admin;

use Auth;
use App\Models\User;
use App\Models\Story;
use App\Models\Business;
use App\Models\CustomFrame;
use App\Models\Transaction;
use App\Models\AiImageModel;
use Illuminate\Support\Str;
use App\Models\Subscription;
use Illuminate\Http\Request;
use App\Models\EarningHistory;
use App\Models\StorageSetting;
use App\Models\WithdrawRequest;
use App\Models\ReferralRegister;
use App\Http\Controllers\Controller;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Facades\Validator;
use Illuminate\Validation\ValidationException;

class SubscriptionController extends Controller
{
    public function __construct()
    {
        $this->middleware('permission:SubscriptionPlan');
    }

    public function index()
    {
        $index['data'] = Subscription::get();
        return view("subscription.index", $index);
    }

    public function create()
    {
        return view("subscription.create", [
            'aiImageModels' => $this->aiImageModels(),
        ]);
    }

    public function show($id)
    {
        $index['data'] = Subscription::find($id);
        return view('subscription.show',$index);
    }

    public function subscription_status(Request $request)
    {
        $subscription = Subscription::find($request->get("id"));
        $subscription->status = ($request->get("checked")=="true")?1:0;
        $subscription->save();
    }

    public function store(Request $request)
    {
        $validation = Validator::make($request->all(), [
            'plan_name' => 'required',
            'monthly_price' => 'required|numeric',
            "monthly_discount_price" => 'required|numeric',
            'yearly_price' => 'required|numeric',
            "yearly_discount_price" => 'required|numeric',
            'business_limit' => 'required',
            'custom_post_edit_limit' => 'required|numeric',
            'festival_post_limit' => 'required|numeric',
            'category_post_limit' => 'required|numeric'
        ]);

        if ($validation->fails()) {
            return back()->withErrors($validation)->withInput();
        } else {

            DB::transaction(function () use ($request) {
                $subscription = Subscription::create([
                    "plan_name" => $request->get("plan_name"),
                    "monthly_price" => $request->get("monthly_price"),
                    "monthly_discount_price" => $request->get("monthly_discount_price"),
                    "yearly_price" => $request->get("yearly_price"),
                    "yearly_discount_price" => $request->get("yearly_discount_price"),
                    "plan_price" => $request->get("yearly_price"),
                    "discount_price" => $request->get("yearly_discount_price"),
                    "duration" => 1,
                    "duration_type" => "Year",
                    "plan_detail" => serialize($request->get('detail')),
                    "business_limit" =>  $request->get("business_limit"),
                    "custom_post_edit_limit" => $request->get("custom_post_edit_limit", 0),
                    "festival_post_limit" => $request->get("festival_post_limit", 0),
                    "category_post_limit" => $request->get("category_post_limit", 0),
                    "photoroom_bg_limit" => $request->get("photoroom_bg_limit", 0),
                    "ai_image_limit" => $request->get("ai_image_limit", 0),
                    "custom_post_ad_reward_limit" => $request->get("custom_post_ad_reward_limit", 5),
                    "festival_post_ad_reward_limit" => $request->get("festival_post_ad_reward_limit", 5),
                    "category_ad_reward_limit" => $request->get("category_ad_reward_limit", 5),
                    "custom_post_ad_reward" => $request->get("custom_post_ad_reward_limit", 5) > 0 ? 1 : 0,
                    "festival_post_ad_reward" => $request->get("festival_post_ad_reward_limit", 5) > 0 ? 1 : 0,
                    "category_ad_reward" => $request->get("category_ad_reward_limit", 5) > 0 ? 1 : 0,
                    "google_product_enable" => ($request->get("google_product_enable"))?1:0,
                    "google_product_id" =>  $request->get("google_product_id"),
                ]);

                $this->syncAiImageAccesses($subscription, $request);
            });
           
            return redirect()->route("subscription-plan.index");
        }
    }

    public function edit($id)
    {
        $subscription = Subscription::with('aiImageAccesses')->find($id);
        if (!$subscription) {
            return redirect()->route('subscription-plan.index')->withErrors('Subscription plan not found.');
        }
        $index['subscription'] = $subscription;
        $plan_detail = $subscription->plan_detail;
        $index['plan_detail'] = $plan_detail ? @unserialize($plan_detail) : [];
        if ($index['plan_detail'] === false && $plan_detail !== 'b:0;') {
            $index['plan_detail'] = [];
        }
        $index['aiImageModels'] = $this->aiImageModels();
        return view("subscription.edit", $index);
    }

    public function update(Request $request, $id)
    {
        //dd($request->all());
        $validation = Validator::make($request->all(), [
            'plan_name' => 'required',
            'monthly_price' => 'required|numeric',
            "monthly_discount_price" => 'required|numeric',
            'yearly_price' => 'required|numeric',
            "yearly_discount_price" => 'required|numeric',
        ]);

        if ($validation->fails()) {
            return back()->withErrors($validation)->withInput();
        } else {
            $subscription = Subscription::findOrFail($id);

            DB::transaction(function () use ($request, $subscription) {
                $subscription->plan_name = $request->plan_name;
                $subscription->monthly_price = $request->monthly_price;
                $subscription->monthly_discount_price = $request->monthly_discount_price;
                $subscription->yearly_price = $request->yearly_price;
                $subscription->yearly_discount_price = $request->yearly_discount_price;
                $subscription->plan_price = $request->yearly_price;
                $subscription->discount_price = $request->yearly_discount_price;
                $subscription->duration = 1;
                $subscription->duration_type = "Year";
                $subscription->plan_detail = serialize($request->detail);
                $subscription->business_limit = $request->business_limit;
                $subscription->custom_post_edit_limit = $request->get("custom_post_edit_limit", 0);
                $subscription->festival_post_limit = $request->get("festival_post_limit", 0);
                $subscription->category_post_limit = $request->get("category_post_limit", 0);
                $subscription->photoroom_bg_limit = $request->get("photoroom_bg_limit", 0);
                $subscription->ai_image_limit = $request->get("ai_image_limit", 0);
                $subscription->custom_post_ad_reward_limit = $request->get("custom_post_ad_reward_limit", 5);
                $subscription->festival_post_ad_reward_limit = $request->get("festival_post_ad_reward_limit", 5);
                $subscription->category_ad_reward_limit = $request->get("category_ad_reward_limit", 5);
                $subscription->custom_post_ad_reward = $request->get("custom_post_ad_reward_limit", 5) > 0 ? 1 : 0;
                $subscription->festival_post_ad_reward = $request->get("festival_post_ad_reward_limit", 5) > 0 ? 1 : 0;
                $subscription->category_ad_reward = $request->get("category_ad_reward_limit", 5) > 0 ? 1 : 0;
                $subscription->google_product_enable = ($request->google_product_enable)?1:0;
                $subscription->google_product_id = $request->google_product_id;
                $subscription->save();

                $this->syncAiImageAccesses($subscription, $request);
            });

            return redirect()->route('subscription-plan.index');
        }
    }

    public function destroy($id)
    {
        $story = Story::where("subscription_id",$id)->get();
        if(StorageSetting::getStorageSetting("storage") == "DigitalOcean")
        {
            foreach($story as $s)
            {
                Storage::disk('spaces')->delete('uploads/'.$s->image);
            }
        }
        else
        {
            foreach($story as $s)
            {
                if ($s->image && file_exists(public_path('uploads/').$s->image)) {
                    @unlink(public_path('uploads/').$s->image);
                }
            }
        }

        Subscription::find($id)->delete();
        Transaction::where("subscription_id",$id)->delete();
        Story::where("subscription_id",$id)->delete();
        $user = User::where("subscription_id",$id)->get();
        foreach($user as $u)
        {
            if($u->user_type != "Super Admin")
            {
                $business = Business::where("user_id",$u->id)->get();
                $customFrame = CustomFrame::where("user_id",$u->id)->get();
                if(StorageSetting::getStorageSetting("storage") == "DigitalOcean")
                {
                    foreach($business as $b)
                    {
                        Storage::disk('spaces')->delete('uploads/'.$b->logo);
                    }
                    foreach($customFrame as $frame)
                    {
                        Storage::disk('spaces')->delete('uploads/'.$frame->frame_image);
                    }
                }
                else
                {
                    foreach($business as $b)
                    {
                        if ($b->logo && file_exists(public_path('uploads/').$b->logo)) {
                            @unlink(public_path('uploads/').$b->logo);
                        }
                    }
                    foreach($customFrame as $frame)
                    {
                        if ($frame->frame_image && file_exists(public_path('uploads/').$frame->frame_image)) {
                            @unlink(public_path('uploads/').$frame->frame_image);
                        }
                    }
                }
                User::find($u->id)->delete();
                Business::where("user_id",$u->id)->delete();
                Transaction::where("user_id",$u->id)->delete();
                WithdrawRequest::where("user_id",$u->id)->delete();
                CustomFrame::where("user_id",$u->id)->delete();
                ReferralRegister::where("user_id",$u->id)->delete();
                EarningHistory::where("user_id",$u->id)->delete();
            }
        }

        return redirect()->route('subscription-plan.index');
    }

    private function upload_image($file,$field,$id)
    {
        $destinationPath = public_path('uploads');
        $extension = $file->getClientOriginalExtension();
        $fileName = Str::uuid() . '.' . $extension;
        $file->move($destinationPath, $fileName);
        
        $image = Subscription::find($id);
        $image->$field = $fileName;
        $image->save();
    }

    private function aiImageModels()
    {
        return AiImageModel::orderByDesc('is_recommended')
            ->orderBy('sort_order')
            ->orderBy('id')
            ->get();
    }

    private function syncAiImageAccesses(Subscription $subscription, Request $request): void
    {
        $submittedAccesses = $request->input('ai_accesses', []);
        $submittedAccesses = is_array($submittedAccesses) ? $submittedAccesses : [];
        $modelIds = array_values(array_unique(array_map('intval', array_keys($submittedAccesses))));
        $models = AiImageModel::whereIn('id', $modelIds)->get()->keyBy('id');

        if (count($models) !== count($modelIds)) {
            throw ValidationException::withMessages([
                'ai_accesses' => 'One or more selected AI image models no longer exist.',
            ]);
        }

        $enabledModelIds = [];
        $sortOrder = 0;

        foreach ($submittedAccesses as $modelId => $access) {
            if (!is_array($access) || empty($access['enabled'])) {
                continue;
            }

            $model = $models->get((int) $modelId);
            if (!$model) {
                continue;
            }

            $qualities = array_values(array_unique(array_filter((array) ($access['qualities'] ?? []), 'is_string')));
            $supportedQualities = array_values((array) $model->quality_options);

            if (empty($qualities) || array_diff($qualities, $supportedQualities)) {
                throw ValidationException::withMessages([
                    'ai_accesses.' . $modelId . '.qualities' => 'Select at least one quality supported by ' . $model->display_name . '.',
                ]);
            }

            $sizeKeys = array_values(array_unique(array_filter((array) ($access['size_keys'] ?? []), 'is_string')));
            $supportedSizeKeys = collect((array) $model->size_options)
                ->pluck('key')
                ->filter()
                ->values()
                ->all();

            if (empty($sizeKeys) || array_diff($sizeKeys, $supportedSizeKeys)) {
                throw ValidationException::withMessages([
                    'ai_accesses.' . $modelId . '.size_keys' => 'Select at least one size supported by ' . $model->display_name . '.',
                ]);
            }

            $maxReferenceImages = (int) ($access['max_reference_images'] ?? 0);
            if ($maxReferenceImages < 0 || $maxReferenceImages > (int) $model->max_reference_images) {
                throw ValidationException::withMessages([
                    'ai_accesses.' . $modelId . '.max_reference_images' => 'Reference image limit must be between 0 and ' . $model->max_reference_images . ' for ' . $model->display_name . '.',
                ]);
            }

            if (!$model->supports_reference_images) {
                $maxReferenceImages = 0;
            }

            $subscription->aiImageAccesses()->updateOrCreate(
                ['ai_image_model_id' => $model->id],
                [
                    'allowed_qualities' => $qualities,
                    'allowed_size_keys' => $sizeKeys,
                    'max_reference_images' => $maxReferenceImages,
                    'allow_refinement' => !empty($access['allow_refinement']) && $model->supports_edits,
                    'status' => true,
                    'sort_order' => $sortOrder++,
                ]
            );

            $enabledModelIds[] = $model->id;
        }

        $accesses = $subscription->aiImageAccesses();
        if (empty($enabledModelIds)) {
            $accesses->delete();
        } else {
            $accesses->whereNotIn('ai_image_model_id', $enabledModelIds)->delete();
        }
    }
}
