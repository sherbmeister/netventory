<?php
require_once __DIR__ . '/../config.php';
$user = require_account_ready();
header('Content-Type: application/json');
$ip = $_GET['ip'] ?? '';
$port = (int)($_GET['port'] ?? 0);
if (!$ip || $port <= 0) { http_response_code(400); echo json_encode(['ok'=>false,'error'=>'bad params']); exit; }
$ok = false; $lat = null; $start = microtime(true);
$fp = @fsockopen($ip, $port, $errno, $errstr, $TCP_TIMEOUT);
if ($fp) { $ok = true; fclose($fp); }
$lat = (int)round((microtime(true)-$start)*1000);


echo json_encode(['ok'=>$ok,'ip'=>$ip,'port'=>$port,'latency_ms'=>$lat]);