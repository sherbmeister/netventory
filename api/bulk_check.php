<?php
require_once __DIR__ . '/../config.php';
$user = require_account_ready();
header('Content-Type: application/json');
$ip = $_GET['ip'] ?? '';
$ports = $_GET['ports'] ?? '';
$ports = array_filter(array_map('intval', explode(',', $ports)));
$results = [];
foreach ($ports as $p) {
$ok = false; $lat = null; $start = microtime(true);
$fp = @fsockopen($ip, $p, $errno, $errstr, $TCP_TIMEOUT);
if ($fp) { $ok = true; fclose($fp); }
$lat = (int)round((microtime(true)-$start)*1000);
$results[] = ['port'=>$p,'ok'=>$ok,'latency_ms'=>$lat];
}


echo json_encode(['ip'=>$ip,'results'=>$results]);