<?php
$headers = get_headers('https://google.com', 1);
var_dump($headers['Date']);
