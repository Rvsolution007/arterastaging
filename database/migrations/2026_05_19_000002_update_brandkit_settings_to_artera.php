<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\DB;

class UpdateBrandkitSettingsToArtera extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        // 1. Update app_setting table: 'app_title' from 'Brand Kit' to 'Artera'
        DB::table('app_setting')
            ->where('key_name', 'app_title')
            ->where('key_value', 'Brand Kit')
            ->update(['key_value' => 'Artera']);

        // 2. Define all settings tables to scan and replace
        $settingsTables = [
            'app_setting',
            'other_setting',
            'whatsapp_setting',
            'email_setting',
            'payment_setting',
            'storage_setting',
            'notification_setting',
            'api_setting',
            'ads_setting',
            'ai_setting',
            'app_update_setting',
            'magic_cloner_setting'
        ];

        $searchPatterns = [
            'ArtEra Pixel',
            'Brand Kit',
            'artera_pixel',
            'brand kit',
            'ArtEra Pixel',
            'ARTERA_PIXEL',
            'BRAND KIT'
        ];

        foreach ($settingsTables as $table) {
            if (Schema::hasTable($table)) {
                DB::table($table)
                    ->where(function ($query) use ($searchPatterns) {
                        foreach ($searchPatterns as $pattern) {
                            $query->orWhere('key_value', 'like', '%' . $pattern . '%');
                        }
                    })
                    ->get()
                    ->each(function ($setting) use ($table, $searchPatterns) {
                        $newValue = str_replace(
                            $searchPatterns,
                            'Artera',
                            $setting->key_value
                        );
                        DB::table($table)
                            ->where('id', $setting->id)
                            ->update(['key_value' => $newValue]);
                    });
            }
        }

        // 3. Clear all cached settings
        $settingsModels = [
            \App\Models\AppSetting::class,
            \App\Models\OtherSetting::class,
            \App\Models\WhatsAppSetting::class,
            \App\Models\EmailSetting::class,
            \App\Models\PaymentSetting::class,
            \App\Models\StorageSetting::class,
            \App\Models\NotificationSetting::class,
            \App\Models\ApiSetting::class,
            \App\Models\AdsSetting::class,
            \App\Models\AiSetting::class,
            \App\Models\AppUpdateSetting::class,
            \App\Models\MagicClonerSetting::class
        ];

        foreach ($settingsModels as $model) {
            try {
                if (class_exists($model) && method_exists($model, 'clearCache')) {
                    $model::clearCache();
                }
            } catch (\Exception $e) {
                // Silence if any error
            }
        }
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        // Revert app_title
        DB::table('app_setting')
            ->where('key_name', 'app_title')
            ->where('key_value', 'Artera')
            ->update(['key_value' => 'Brand Kit']);
    }
}
