<?php
$content = file_get_contents('app/Http/Controllers/Api/AuthApi.php');
$content = str_replace("'userType' => \$user->login_type,", "'userType' => \$user->login_type,\n                'isPartner' => (\$user->is_partner == 1) ? true : false,", $content);
file_put_contents('app/Http/Controllers/Api/AuthApi.php', $content);
echo "Updated AuthApi.php\n";
