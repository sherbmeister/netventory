<?php
require_once __DIR__ . '/../config.php';
$user = require_account_ready();
header('Content-Type: application/json');
$db = read_inventory(current_data_file());
$counts = ['total'=>count($db['items']),'by_tag'=>[],'by_type'=>[],'by_os'=>[]];
foreach ($db['items'] as $i) {
  foreach (($i['tags'] ?? []) as $t) { $counts['by_tag'][$t] = ($counts['by_tag'][$t] ?? 0)+1; }
  $ty = $i['type'] ?? '';
  $os = $i['os'] ?? '';
  if ($ty) $counts['by_type'][$ty] = ($counts['by_type'][$ty] ?? 0)+1;
  if ($os) $counts['by_os'][$os] = ($counts['by_os'][$os] ?? 0)+1;
}
echo json_encode(['updated'=>$db['updated'] ?? time(),'counts'=>$counts]);
