<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class GoldenRender extends Model
{
    protected $fillable = [
        'frame_id',
        'zip_name',
        'render_version',
        'web_computed',
        'native_computed',
        'web_thumbnail_path',
        'native_snapshot_path',
        'source',
    ];

    protected $casts = [
        'web_computed' => 'array',
        'native_computed' => 'array',
        'render_version' => 'integer',
    ];

    /**
     * Get the PosterMaker frame this golden render belongs to.
     */
    public function frame()
    {
        return $this->belongsTo(PosterMaker::class, 'frame_id');
    }

    /**
     * Get or create a golden render for a specific frame + version.
     */
    public static function capture(int $frameId, string $zipName, int $version, array $data): self
    {
        return self::updateOrCreate(
            ['frame_id' => $frameId, 'render_version' => $version],
            array_merge(['zip_name' => $zipName], $data)
        );
    }
}
