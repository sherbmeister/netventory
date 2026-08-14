<?php
require_once __DIR__ . '/config.php';
$user = require_account_ready();
$db = read_inventory(current_data_file());
$fmt = $_GET['fmt'] ?? 'csv';
if ($fmt === 'json') {
  header('Content-Type: application/json');
  echo json_encode($db, JSON_PRETTY_PRINT|JSON_UNESCAPED_SLASHES);
  exit;
}
header('Content-Type: text/csv');
header('Content-Disposition: attachment; filename="netventory.csv"');
$out = fopen('php://output', 'w');
fputcsv($out, ['name','ip','ports','mac','type','os','tags','notes']);
foreach ($db['items'] as $i) {
  $ports = implode(',', array_map(fn($p)=>$p['port'].($p['label']?':'.$p['label']:''), $i['ports'] ?? []));
  fputcsv($out, [
    $i['name'] ?? '',
    $i['ip'] ?? '',
    $ports,
    $i['mac'] ?? '',
    $i['type'] ?? '',
    $i['os'] ?? '',
    implode(',', $i['tags'] ?? []),
    $i['notes'] ?? ''
  ]);
}
fclose($out);
