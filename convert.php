<?php
$content = file_get_contents('arterastaging.sql');
$content = mb_convert_encoding($content, 'UTF-8', 'UTF-16');
file_put_contents('arterastaging.sql', $content);
echo "Converted successfully.\n";
