<?php
$html = file_get_contents('http://localhost/Artera/edit/festival/7');
file_put_contents('C:\Users\Admin\.gemini\antigravity\brain\7896d00a-ea8f-4446-8920-23a40aa3f611\scratch\page_source.html', $html);
// We know it redirected to login.
// I need to fetch it WITH A LOGGED IN SESSION!
