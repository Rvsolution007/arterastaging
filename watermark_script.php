<?php

namespace App\Console;

ini_set('memory_limit', '1024M');

class WatermarkRunner {
    use \App\Traits\WatermarkImageTrait;

    public function run() {
        $countCat = 0;
        $posts = \App\Models\CategoryPost::all();
        foreach($posts as $p) {
            if($p->frame_image && !file_exists(public_path('uploads/watermarked_'.$p->frame_image))) {
                $this->applyWatermark($p->frame_image, 'local');
                $countCat++;
                echo "Cat: $countCat\n";
            }
        }
        
        $countFest = 0;
        $festPosts = \App\Models\FestivalsPost::all();
        foreach($festPosts as $p) {
            if($p->frame_image && !file_exists(public_path('uploads/watermarked_'.$p->frame_image))) {
                $this->applyWatermark($p->frame_image, 'local');
                $countFest++;
                echo "Fest: $countFest\n";
            }
        }

        echo "Generated Category: $countCat, Festival: $countFest\n";
    }
}

(new WatermarkRunner())->run();
