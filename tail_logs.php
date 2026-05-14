<?php
$content = file_get_contents('storage/logs/laravel.log');
$content = mb_convert_encoding($content, 'UTF-8', 'auto');
$lines = explode("\n", $content);
echo implode("\n", array_slice($lines, -100));
