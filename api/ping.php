<?php
require_once __DIR__ . '/../config.php';
$user = require_account_ready();
header('Content-Type: application/json');

$ip    = $_GET['ip'] ?? '';
$debug = isset($_GET['debug']);

if (!$ip) {
  http_response_code(400);
  echo json_encode(['ok'=>false,'error'=>'no ip']);
  exit;
}
if (!$ALLOW_PING) {
  echo json_encode(['ok'=>false,'error'=>'icmp disabled in config','method'=>'icmp']);
  exit;
}

/* ---- helpers ---- */
function disabled_list() {
  $s = (string) ini_get('disable_functions');
  $s = strtolower(trim($s));
  if ($s === '') return [];
  $arr = array_map('trim', explode(',', $s));
  return array_values(array_filter($arr, fn($x) => $x !== ''));
}
function fn_disabled($fn) {
  return in_array(strtolower($fn), disabled_list(), true) || !function_exists($fn);
}
function find_ping_path() {
  // common linux paths
  foreach (['/bin/ping','/usr/bin/ping','/sbin/ping','/usr/sbin/ping'] as $p) {
    if (is_file($p) && is_executable($p)) return $p;
  }
  // which/where if allowed
  if (!fn_disabled('shell_exec')) {
    $cmd = stripos(PHP_OS_FAMILY,'Windows')!==false ? 'where ping 2>NUL' : 'command -v ping 2>/dev/null';
    $which = @shell_exec($cmd);
    if ($which) {
      $cand = trim(preg_split('/\r?\n/', $which)[0]);
      if ($cand && is_file($cand) && is_executable($cand)) return $cand;
    }
  }
  // windows fallback
  if (stripos(PHP_OS_FAMILY,'Windows')!==false) {
    $win = getenv('WINDIR') ?: 'C:\\Windows';
    $cand = $win.'\\System32\\PING.EXE';
    if (is_file($cand)) return $cand;
  }
  return null;
}

/* ---- locate ping ---- */
$ping = find_ping_path();
$diags = [
  'php_os'            => PHP_OS_FAMILY,
  'disable_functions' => disabled_list(),
  'ping_path'         => $ping,
];

if (!$ping) {
  $resp = ['ok'=>false,'error'=>'ping binary not found','method'=>'icmp'];
  if ($debug) $resp['debug'] = $diags;
  echo json_encode($resp);
  exit;
}
if (fn_disabled('proc_open') && fn_disabled('exec') && fn_disabled('shell_exec') && fn_disabled('system') && fn_disabled('passthru')) {
  $resp = ['ok'=>false,'error'=>'all exec functions disabled (proc_open/exec/shell_exec/system/passthru)','method'=>'icmp'];
  if ($debug) $resp['debug'] = $diags;
  echo json_encode($resp);
  exit;
}

/* ---- build command ---- */
if (stripos(PHP_OS_FAMILY, 'Windows') !== false) {
  // -n 1 one echo, -w 1000 timeout ms
  $cmd = escapeshellcmd($ping).' -n 1 -w 1000 '.escapeshellarg($ip);
} else {
  // -n no DNS, -c 1 one echo, -W 1 timeout s (GNU/BusyBox)
  $cmd = escapeshellcmd($ping).' -n -c 1 -W 1 '.escapeshellarg($ip);
}

/* ---- run command ---- */
$stdout = ''; $stderr = ''; $code = 127;
$t0 = microtime(true);

if (!fn_disabled('proc_open')) {
  $spec = [1=>['pipe','w'], 2=>['pipe','w']];
  $proc = @proc_open($cmd, $spec, $pipes);
  if (is_resource($proc)) {
    $stdout = stream_get_contents($pipes[1]); fclose($pipes[1]);
    $stderr = stream_get_contents($pipes[2]); fclose($pipes[2]);
    $code = proc_close($proc);
  }
} elseif (!fn_disabled('exec')) {
  $out = []; @exec($cmd.' 2>&1', $out, $code); $stdout = implode("\n", $out);
} elseif (!fn_disabled('shell_exec')) {
  $stdout = @shell_exec($cmd.' 2>&1'); $code = ($stdout !== null && $stdout !== '') ? 0 : 1;
} elseif (!fn_disabled('system')) {
  ob_start(); @system($cmd.' 2>&1', $code); $stdout = ob_get_clean();
} elseif (!fn_disabled('passthru')) {
  ob_start(); @passthru($cmd.' 2>&1', $code); $stdout = ob_get_clean();
}

$elapsed = (int) round((microtime(true) - $t0) * 1000);

/* ---- parse RTT (fallback to measured) ---- */
$rtt = null;
$text = trim($stdout."\n".$stderr);
if (preg_match('/time[=<]([0-9.]+)\s*ms/i', $text, $m)) {
  $rtt = (int) round((float) $m[1]);
} else {
  $rtt = $elapsed;
}

/* ---- response ---- */
$resp = [
  'ok'     => ($code === 0),
  'rtt_ms' => $rtt,
  'method' => 'icmp',
  'code'   => $code,
];
if ($debug) $resp['debug'] = $diags + ['cmd'=>$cmd, 'stdout'=>$stdout, 'stderr'=>$stderr];

echo json_encode($resp);
