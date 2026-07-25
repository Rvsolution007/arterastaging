<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class AppInstallEvent extends Model
{
    protected $fillable = [
        'device_hash',
        'user_id',
        'event_type',
        'event_key',
        'platform',
        'app_version',
        'device_model',
        'os_version',
        'occurred_at',
    ];

    protected $casts = [
        'occurred_at' => 'datetime',
    ];

    /**
     * Store only a keyed hash of the mobile identifier, never the identifier itself.
     */
    public static function hashDeviceId(string $deviceId): string
    {
        return hash_hmac('sha256', $deviceId, (string) config('app.key'));
    }

    public static function recordInstall(string $deviceId, string $installId, array $attributes = []): self
    {
        $deviceHash = static::hashDeviceId($deviceId);
        $eventKey = hash('sha256', $deviceHash . '|install|' . $installId);

        $event = static::firstOrCreate(
            ['event_key' => $eventKey],
            array_merge($attributes, [
                'device_hash' => $deviceHash,
                'event_type' => 'install',
                'event_key' => $eventKey,
                'occurred_at' => now(),
            ])
        );

        if (!empty($attributes['user_id']) && empty($event->user_id)) {
            $event->update(['user_id' => $attributes['user_id']]);
        }

        return $event;
    }

    public static function recordUninstall(string $deviceId, ?int $userId = null): self
    {
        $deviceHash = static::hashDeviceId($deviceId);
        $eventKey = hash('sha256', $deviceHash . '|uninstall');

        return static::firstOrCreate(
            ['event_key' => $eventKey],
            [
                'device_hash' => $deviceHash,
                'user_id' => $userId,
                'event_type' => 'uninstall',
                'event_key' => $eventKey,
                'platform' => 'android',
                'occurred_at' => now(),
            ]
        );
    }
}
