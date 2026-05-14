<?php

namespace App\Models;

use Illuminate\Support\Arr;
use Illuminate\Support\Facades\File;

class Setting
{
    /**
     * Get setting value.
     */
    public static function getValue($group, $key, $default = null, $companyId = null)
    {
        // Special case for AI config looking for Vertex Config
        if ($group === 'ai_bot' && $key === 'vertex_config') {
            $vertexConfigJson = AiSetting::get('vertex_config');
            if ($vertexConfigJson) {
                $decoded = json_decode($vertexConfigJson, true);
                if (is_array($decoded)) return $decoded;
            }
            return $default;
        }

        $data = self::getStorageData();
        $fullKey = $companyId ? "{$companyId}.{$group}.{$key}" : "global.{$group}.{$key}";

        return Arr::get($data, $fullKey, $default);
    }

    /**
     * Set setting value.
     */
    public static function setValue($group, $key, $value, $companyId = null)
    {
        if ($group === 'ai_bot' && $key === 'vertex_config') {
            $record = AiSetting::firstOrNew(['key_name' => 'vertex_config']);
            $record->key_value = is_array($value) ? json_encode($value) : $value;
            $record->save();
            return true;
        }

        $data = self::getStorageData();
        $fullKey = $companyId ? "{$companyId}.{$group}.{$key}" : "global.{$group}.{$key}";
        
        Arr::set($data, $fullKey, $value);
        self::saveStorageData($data);
        return true;
    }

    /**
     * Get global setting value.
     */
    public static function getGlobalValue($group, $key, $default = null)
    {
        return self::getValue($group, $key, $default, null);
    }

    private static function getStorageData()
    {
        $path = storage_path('app/settings_wizard.json');
        if (File::exists($path)) {
            $content = File::get($path);
            return json_decode($content, true) ?: [];
        }
        return [];
    }

    private static function saveStorageData($data)
    {
        $path = storage_path('app/settings_wizard.json');
        File::put($path, json_encode($data, JSON_PRETTY_PRINT));
    }
}
