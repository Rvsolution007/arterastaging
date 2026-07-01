<?php
$psd = json_decode(file_get_contents('C:/xampp/htdocs/Artera/uploads/tmp_inspect/json/Service List_103.json'), true);
echo 'PSD Layers: ' . count($psd['layers']) . PHP_EOL;

// Get the latest web published JSON
$files = glob('C:/xampp/htdocs/Artera/uploads/template/*/json/*.json');
usort($files, function($a, $b) {
    return filemtime($b) - filemtime($a);
});
$webFile = $files[0];
echo "Using Web JSON: $webFile\n";
$web = json_decode(file_get_contents($webFile), true);
echo 'Web Layers: ' . count($web['layers']) . PHP_EOL;

$keys = ['x','y','w','h','type','is_shape','is_background', 'z_index'];

foreach ($psd['layers'] as $i => $l) {
  $wl = $web['layers'][$i] ?? null;
  if (!$wl) continue;
  echo 'Diff for ' . $l['name'] . ': ';
  $diffs = [];
  foreach ($keys as $k) {
    $v1 = isset($l[$k]) ? (is_bool($l[$k]) ? ($l[$k] ? 'true':'false') : $l[$k]) : 'null';
    $v2 = isset($wl[$k]) ? (is_bool($wl[$k]) ? ($wl[$k] ? 'true':'false') : $wl[$k]) : 'null';
    if ($v1 !== $v2) {
      $diffs[] = $k . ' (' . $v1 . ' vs ' . $v2 . ')';
    }
  }
  echo empty($diffs) ? 'None' : implode(', ', $diffs);
  echo PHP_EOL;
}
