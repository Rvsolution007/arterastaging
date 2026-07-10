<?php $ch = curl_init('https://stagingartera.arterapixel.com/api/getAppAbout'); curl_setopt($ch, CURLOPT_RETURNTRANSFER, true); curl_setopt($ch, CURLOPT_POST, true); echo curl_exec($ch);
