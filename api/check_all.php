<?php
require_once __DIR__ . '/../config.php';
$user = require_account_ready();
header('Content-Type: application/json');
$db = read_inventory(current_data_file());
$out = [];
foreach ($db['items'] as $i) {
$row = ['id'=>$i['id'],'ip'=>$i['ip'],'ports'=>[]];
foreach (($i['ports'] ?? []) as $p) {
$port = (int)$p['port'];
$ok = false; $lat = null; $start = microtime(true);
$fp = @fsockopen($i['ip'], $port, $errno, $errstr, $TCP_TIMEOUT);
if ($fp) { $ok = true; fclose($fp); }
$lat = (int)round((microtime(true)-$start)*1000);
$row['ports'][] = ['port'=>$port,'ok'=>$ok,'latency_ms'=>$lat];
}
$out[] = $row;
}
echo json_encode(['items'=>$out]);