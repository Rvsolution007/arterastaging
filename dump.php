<?php
$zips = glob('public/uploads/custom_frames_zips/*.zip');
if (count($zips) > 0) {
    $zip = new ZipArchive();
    if ($zip->open($zips[0]) === true) {
        for ($i = 0; $i < $zip->numFiles; $i++) {
            $name = $zip->getNameIndex($i);
            if (substr($name, -5) === '.json') {
                echo $zip->getFromIndex($i);
                break;
            }
        }
    }
} else { echo 'No zip found'; }
