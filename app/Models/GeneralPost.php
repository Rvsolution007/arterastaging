<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class GeneralPost extends Model
{
    use HasFactory;
    protected $table = "general_posts";

    protected $fillable = [
        'business_category_id',
        'business_sub_category_id',
        'product_id',
        'user_id',
        'frame_image',
        'status',
        "paid",
        "height",
        "width",
        "image_type",
        "aspect_ratio",
        "zip_file_id",
        "zip_name",
        "ai_content_subject",
        "ai_generated_content",
        "task_name",
        "process_status",
        "failure_reason",
        "post_purpose_id"
    ];

    public function zip_file()
    {
        return $this->belongsTo("App\Models\ZipFileManager", "zip_file_id", "id");
    }

    public function user()
    {
        return $this->hasOne("App\Models\User", "id", "user_id");
    }

    public function business_category()
    {
        return $this->hasOne("App\Models\BusinessCategory", "id", "business_category_id");
    }

    public function business_sub_category()
    {
        return $this->hasOne("App\Models\BusinessSubCategory", "id", "business_sub_category_id");
    }

    public function product()
    {
        return $this->hasOne("App\Models\Product", "id", "product_id");
    }

    public function post_purpose()
    {
        return $this->belongsTo("App\Models\PostPurpose", "post_purpose_id", "id");
    }

    /**
     * Resolves the source template configuration (JSON) for this AI post.
     */
    public function getTemplateConfig()
    {
        if (!$this->zip_name || !$this->frame_image) {
            return null;
        }

        $zipBaseName = pathinfo($this->zip_name, PATHINFO_FILENAME);
        $templateBaseDir = public_path('uploads/template/' . $this->zip_name);
        
        if (!is_dir($templateBaseDir)) {
             $templateBaseDir = public_path('uploads/template/' . $zipBaseName);
        }

        if (!is_dir($templateBaseDir)) {
            return null;
        }

        // Find the extraction root (usually FrameYYYYMMDD...)
        $subDirs = glob($templateBaseDir . '/*', GLOB_ONLYDIR);
        $extractRoot = null;
        foreach ($subDirs as $dir) {
            if (is_dir($dir . '/json') && is_dir($dir . '/skins')) {
                $extractRoot = $dir;
                break;
            }
        }

        if (!$extractRoot) {
            return null;
        }

        $frameImagePath = public_path('uploads/' . $this->frame_image);
        if (!file_exists($frameImagePath)) {
            return null;
        }

        $targetSize = filesize($frameImagePath);
        $matchingDesign = null;

        // Search in skins folders for a match
        $skinFolders = glob($extractRoot . '/skins/*', GLOB_ONLYDIR);
        foreach ($skinFolders as $skinFolder) {
            $designName = basename($skinFolder);
            $img1 = $skinFolder . '/image-1.png';
            $framePng = $skinFolder . '/frame.png';

            // Check if either matches the frame_image size
            if ((file_exists($img1) && filesize($img1) == $targetSize) || 
                (file_exists($framePng) && filesize($framePng) == $targetSize)) {
                $matchingDesign = $designName;
                break;
            }
        }

        if (!$matchingDesign) {
            // Fallback: Use the first design found if only one exists
            if (count($skinFolders) == 1) {
                $matchingDesign = basename($skinFolders[0]);
            } else {
                return null;
            }
        }

        $jsonPath = $extractRoot . '/json/' . $matchingDesign . '.json';
        if (!file_exists($jsonPath)) {
            return null;
        }

        $config = json_decode(file_get_contents($jsonPath), true);
        
        // Construct relative public paths for frontend
        $relativeRoot = str_replace(public_path(), '', $extractRoot);
        $relativeRoot = str_replace('\\', '/', $relativeRoot);
        
        return [
            'design_name' => $matchingDesign,
            'config' => $config,
            'asset_root' => asset($relativeRoot),
            'skins_dir' => asset($relativeRoot . '/skins/' . $matchingDesign),
            'fonts_dir' => asset($relativeRoot . '/fonts'),
            'ai_data' => json_decode($this->ai_generated_content, true)
        ];
    }
}
