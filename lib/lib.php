<?php
function h($value) {
  return htmlspecialchars((string) $value, ENT_QUOTES, 'UTF-8');
}

function read_db($file) {
  if (!file_exists($file)) { return ['items' => [], 'updated' => time()]; }
  $raw = file_get_contents($file);
  $data = json_decode($raw, true);
  if (!$data) $data = ['items' => [], 'updated' => time()];
  return $data;
}

function write_db($file, $data) {
  $data['updated'] = time();
  if (!is_dir(dirname($file))) { mkdir(dirname($file), 0755, true); }
  $tmp = $file . '.tmp';
  file_put_contents($tmp, json_encode($data, JSON_PRETTY_PRINT|JSON_UNESCAPED_SLASHES));
  rename($tmp, $file);
}

function default_inventory() {
  return [
    'items' => [],
    'options' => [
      'types' => ['Server','Router','Switch','NAS','VM','AP','Printer'],
      'oses' => ['Linux','Windows','ESXi','BSD','macOS','Other'],
    ],
    'updated' => time(),
  ];
}

function read_inventory($file) {
  if (!file_exists($file)) return default_inventory();
  $db = read_db($file);
  $defaults = default_inventory();
  if (!isset($db['items']) || !is_array($db['items'])) $db['items'] = [];
  if (!isset($db['options']) || !is_array($db['options'])) $db['options'] = $defaults['options'];
  return $db;
}

function write_inventory($file, $data) {
  write_db($file, $data);
}

function user_inventory_file($userId) {
  global $DATA_DIR;
  $safe = preg_replace('/[^a-zA-Z0-9_-]/', '', (string) $userId);
  return rtrim($DATA_DIR, '/') . '/inventories/' . $safe . '.json';
}

function ensure_user_inventory($userId) {
  global $LEGACY_DATA_FILE, $BOOTSTRAP_LEGACY_TO_FIRST_USER;
  $file = user_inventory_file($userId);
  if (file_exists($file)) return $file;

  $db = default_inventory();
  if ($BOOTSTRAP_LEGACY_TO_FIRST_USER && legacy_inventory_unclaimed() && file_exists($LEGACY_DATA_FILE)) {
    $legacy = read_inventory($LEGACY_DATA_FILE);
    $db['items'] = $legacy['items'] ?? [];
    $db['options'] = $legacy['options'] ?? $db['options'];
    mark_legacy_inventory_claimed($userId);
  }

  write_inventory($file, $db);
  return $file;
}

function legacy_inventory_unclaimed() {
  global $DATA_DIR;
  return !file_exists(rtrim($DATA_DIR, '/') . '/.legacy_claimed');
}

function mark_legacy_inventory_claimed($userId) {
  global $DATA_DIR;
  @file_put_contents(rtrim($DATA_DIR, '/') . '/.legacy_claimed', (string) $userId . "\n", LOCK_EX);
}

function read_options($file) {
  if (!file_exists($file)) {
    return [
      'types'=>['Server','VM','Router','Switch','AP','NAS','PC','Printer'],
      'oses' =>['Linux','Windows','BSD','ESXi','TrueNAS','RouterOS','OPNsense']
    ];
  }
  $raw = file_get_contents($file);
  $data = json_decode($raw, true);
  if (!$data) $data = ['types'=>[], 'oses'=>[]];
  return $data;
}

function write_options($file, $opts) {
  if (!is_dir(dirname($file))) { mkdir(dirname($file), 0755, true); }
  $tmp = $file . '.tmp';
  file_put_contents($tmp, json_encode($opts, JSON_PRETTY_PRINT|JSON_UNESCAPED_SLASHES));
  rename($tmp, $file);
}

function create_id() { return bin2hex(random_bytes(8)); }
function sanitize_text($s) { return trim(filter_var($s, FILTER_SANITIZE_SPECIAL_CHARS)); }

function normalize_ports($str) {
  // Accept formats like: 22,80,443 or 22:ssh,80:http
  $ports = [];
  foreach (preg_split('/\s*,\s*/', trim($str)) as $p) {
    if ($p === '') continue;
    if (strpos($p, ':') !== false) {
      list($num, $label) = array_map('trim', explode(':', $p, 2));
      $num = (int)$num; if ($num <= 0) continue;
      $ports[] = ['port'=>$num,'label'=>$label];
    } else {
      $num = (int)$p; if ($num <= 0) continue;
      $ports[] = ['port'=>$num,'label'=>''];
    }
  }
  return $ports;
}

function normalize_mac($m) {
  $m = strtoupper(preg_replace('/[^0-9A-F]/i','',$m));
  if (strlen($m) === 12) return implode(':', str_split($m,2));
  return $m;
}

function normalize_icon_url($url) {
  $url = trim((string) $url);
  if ($url === '') return '';

  if (preg_match('#^https?://#i', $url)) {
    return filter_var($url, FILTER_VALIDATE_URL) ? $url : '';
  }

  if (strpos($url, '/') === 0) {
    return preg_match('#^/[^\s]+$#', $url) ? $url : '';
  }

  if (stripos($url, 'data:image/') === 0) {
    return $url;
  }

  return '';
}

function item_from_request() {
  return [
    'id'     => $_POST['id'] ?? create_id(),
    'name'   => sanitize_text($_POST['name'] ?? ''),
    'ip'     => trim($_POST['ip'] ?? ''),
    'ports'  => normalize_ports($_POST['ports'] ?? ''),
    'mac'    => normalize_mac($_POST['mac'] ?? ''),    // NEW
    'type'   => sanitize_text($_POST['type'] ?? ''),   // NEW
    'os'     => sanitize_text($_POST['os'] ?? ''),     // NEW
    'icon_url' => normalize_icon_url($_POST['icon_url'] ?? ''),
    'tags'   => array_values(array_filter(array_map('trim', explode(',', $_POST['tags'] ?? '')))),
    'notes'  => sanitize_text($_POST['notes'] ?? ''),
    'created'=> time(),
    'updated'=> time(),
  ];
}

function update_item(&$db, $item) {
  foreach ($db['items'] as &$it) {
    if ($it['id'] === $item['id']) { $item['created'] = $it['created']; $it = $item; return true; }
  }
  $db['items'][] = $item; return false;
}

function delete_item(&$db, $id) {
  $db['items'] = array_values(array_filter($db['items'], fn($i) => $i['id'] !== $id));
}

function find_item($db, $id) {
  foreach ($db['items'] as $it) if ($it['id'] === $id) return $it; return null;
}

function sort_items($items) {
  $indexed = [];
  foreach (array_values($items ?? []) as $index => $item) {
    $indexed[] = ['index' => $index, 'item' => $item];
  }

  usort($indexed, function($left, $right) {
    $leftOrder = $left['item']['sort_order'] ?? null;
    $rightOrder = $right['item']['sort_order'] ?? null;

    $leftHasOrder = is_numeric($leftOrder);
    $rightHasOrder = is_numeric($rightOrder);

    if ($leftHasOrder && $rightHasOrder) {
      $cmp = (int) $leftOrder <=> (int) $rightOrder;
      if ($cmp !== 0) return $cmp;
    } elseif ($leftHasOrder !== $rightHasOrder) {
      return $leftHasOrder ? -1 : 1;
    }

    return $left['index'] <=> $right['index'];
  });

  return array_map(fn($entry) => $entry['item'], $indexed);
}

function next_sort_order($db) {
  $max = -1;
  foreach (($db['items'] ?? []) as $item) {
    if (isset($item['sort_order']) && is_numeric($item['sort_order'])) {
      $max = max($max, (int) $item['sort_order']);
    }
  }

  if ($max >= 0) return $max + 1;
  return count($db['items'] ?? []);
}

function assign_sort_orders(&$items) {
  foreach (array_values($items) as $index => $item) {
    $items[$index]['sort_order'] = $index;
  }
  $items = array_values($items);
}

function reorder_items(&$db, $ordered_ids) {
  $ordered_ids = array_values(array_filter(array_map('strval', $ordered_ids), 'strlen'));
  if (!$ordered_ids) return false;

  $items = sort_items($db['items'] ?? []);
  $itemById = [];
  foreach ($items as $item) {
    if (!isset($item['id'])) continue;
    $itemById[(string) $item['id']] = $item;
  }

  $ordered_ids = array_values(array_unique($ordered_ids));
  foreach ($ordered_ids as $id) {
    if (!isset($itemById[$id])) return false;
  }

  $subsetLookup = array_fill_keys($ordered_ids, true);
  $replacementItems = array_map(fn($id) => $itemById[$id], $ordered_ids);
  $replacementIndex = 0;
  $reordered = [];

  foreach ($items as $item) {
    $id = (string) ($item['id'] ?? '');
    if (isset($subsetLookup[$id])) {
      $reordered[] = $replacementItems[$replacementIndex++] ?? $item;
      continue;
    }
    $reordered[] = $item;
  }

  assign_sort_orders($reordered);
  $db['items'] = $reordered;
  return true;
}

function search_items($db, $q) {
  $items = sort_items($db['items'] ?? []);
  $q = mb_strtolower(trim($q)); if ($q==='') return $items;
  return array_values(array_filter($items, function($i) use ($q) {
    $hay = mb_strtolower(
      ($i['name']??'').' '.($i['ip']??'').' '.implode(' ', $i['tags']??[]).' '.
      ($i['type']??'').' '.($i['os']??'').' '.($i['mac']??'').' '.($i['notes']??'')
    );
    return strpos($hay, $q) !== false;
  }));
}

/* ---------- Badge helpers (icons) ---------- */
function type_icon($type) {
  $key = mb_strtolower(trim((string)$type));
  $map = [
    'server'  => '🖥️',
    'vm'      => '🧊',
    'router'  => '🌐',
    'switch'  => '🔀',
    'ap'      => '📶',
    'nas'     => '🗄️',
    'pc'      => '💻',
    'printer' => '🖨️',
    'docker'  => '🐳',
    'lxc'     => '📦',
    'ipmi'    => '🛠️',
    'dns'     => '🧭',
  ];
  return $map[$key] ?? '🧩';
}

function os_icon($os) {
  $key = mb_strtolower(trim((string)$os));
  $map = [
    'linux'    => '🐧',
    'windows'  => '🪟',
    'bsd'      => '🐡',
    'esxi'     => '🧊',
    'truenas'  => '🧩',
    'routeros' => '🛣️',
    'opnsense' => '🛡️',
    'proxmox'  => '🧱',
    'debian'   => '🌀',
    'ubuntu'   => '🟠',
    'redhat'   => '🎩',
    'other'    => '💽',
  ];
  return $map[$key] ?? '💽';
}

function host_icon_glyph($item) {
  $os = trim((string) ($item['os'] ?? ''));
  if ($os !== '') return os_icon($os);

  $type = trim((string) ($item['type'] ?? ''));
  if ($type !== '') return type_icon($type);

  return '🧩';
}

function host_icon_markup($item, $baseClass = 'device-tile-icon') {
  $iconUrl = normalize_icon_url($item['icon_url'] ?? '');
  $fallback = htmlspecialchars(host_icon_glyph($item), ENT_QUOTES, 'UTF-8');

  if ($iconUrl !== '') {
    $escapedUrl = htmlspecialchars($iconUrl, ENT_QUOTES, 'UTF-8');
    return
      '<span class="' . htmlspecialchars($baseClass, ENT_QUOTES, 'UTF-8') . ' has-image" aria-hidden="true">' .
        '<img class="' . htmlspecialchars($baseClass, ENT_QUOTES, 'UTF-8') . '-image" src="' . $escapedUrl . '" alt="" loading="lazy" referrerpolicy="no-referrer" onerror="this.closest(\'span\').classList.remove(\'has-image\'); this.remove();">' .
        '<span class="' . htmlspecialchars($baseClass, ENT_QUOTES, 'UTF-8') . '-fallback">' . $fallback . '</span>' .
      '</span>';
  }

  return '<span class="' . htmlspecialchars($baseClass, ENT_QUOTES, 'UTF-8') . '" aria-hidden="true"><span class="' . htmlspecialchars($baseClass, ENT_QUOTES, 'UTF-8') . '-fallback">' . $fallback . '</span></span>';
}

function type_badge($type) {
  $icon = type_icon($type);
  return '<span class="pill">'.$icon.' '.htmlspecialchars($type).'</span>';
}
function os_badge($os) {
  $icon = os_icon($os);
  return '<span class="pill">'.$icon.' '.htmlspecialchars($os).'</span>';
}

function read_users() {
  global $USERS_FILE;
  if (!file_exists($USERS_FILE)) return ['users' => [], 'updated' => time()];
  $raw = file_get_contents($USERS_FILE);
  $data = json_decode($raw, true);
  if (!is_array($data)) $data = ['users' => [], 'updated' => time()];
  if (!isset($data['users']) || !is_array($data['users'])) $data['users'] = [];
  return $data;
}

function write_users($data) {
  global $USERS_FILE;
  write_db($USERS_FILE, $data);
}

function normalize_email($email) {
  return mb_strtolower(trim((string) $email));
}

function find_user_by_email($email) {
  $email = normalize_email($email);
  foreach (read_users()['users'] as $user) {
    if (normalize_email($user['email'] ?? '') === $email) return $user;
  }
  return null;
}

function find_user_by_id($id) {
  foreach (read_users()['users'] as $user) {
    if (($user['id'] ?? '') === $id) return $user;
  }
  return null;
}

function update_user_record($user) {
  $users = read_users();
  $found = false;
  foreach ($users['users'] as $index => $existing) {
    if (($existing['id'] ?? '') === ($user['id'] ?? '')) {
      $user['updated'] = time();
      $users['users'][$index] = $user;
      $found = true;
      break;
    }
  }
  if (!$found) {
    $user['created'] = $user['created'] ?? time();
    $user['updated'] = time();
    $users['users'][] = $user;
  }
  write_users($users);
  return $user;
}

function create_confirmation_token($userId) {
  $token = bin2hex(random_bytes(32));
  $user = find_user_by_id($userId);
  if (!$user) return null;
  $user['email_verify_token_hash'] = hash('sha256', $token);
  $user['email_verify_expires'] = time() + 86400;
  update_user_record($user);
  return $token;
}

function send_confirmation_email($user, $token) {
  global $MAIL_FROM, $SITE_NAME;
  $link = app_url('confirm.php?token=' . urlencode($token));
  $subject = $SITE_NAME . ' email confirmation';
  $body = "Welcome to {$SITE_NAME}.\n\nConfirm your email address here:\n{$link}\n\nThis link expires in 24 hours.";
  $headers = "From: {$MAIL_FROM}\r\nContent-Type: text/plain; charset=UTF-8";
  return @mail($user['email'], $subject, $body, $headers);
}

function register_user($email, $password, $displayName) {
  global $REQUIRE_EMAIL_CONFIRMATION;
  $email = normalize_email($email);
  if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
    return [null, 'Enter a valid email address.'];
  }
  if (strlen((string) $password) < 10) {
    return [null, 'Use at least 10 characters for the password.'];
  }
  if (find_user_by_email($email)) {
    return [null, 'That email already has an account.'];
  }

  $user = [
    'id' => create_id(),
    'email' => $email,
    'display_name' => trim((string) $displayName) ?: $email,
    'password_hash' => password_hash($password, PASSWORD_DEFAULT),
    'email_verified' => !$REQUIRE_EMAIL_CONFIRMATION,
    'totp_enabled' => false,
    'totp_secret' => '',
    'trusted_devices' => [],
    'created' => time(),
    'updated' => time(),
  ];
  $user = update_user_record($user);
  ensure_user_inventory($user['id']);

  if ($REQUIRE_EMAIL_CONFIRMATION) {
    $token = create_confirmation_token($user['id']);
    if ($token) send_confirmation_email($user, $token);
  }
  return [$user, null];
}

function confirm_email_token($token) {
  $hash = hash('sha256', (string) $token);
  $users = read_users();
  foreach ($users['users'] as $index => $user) {
    if (($user['email_verify_token_hash'] ?? '') !== $hash) continue;
    if (($user['email_verify_expires'] ?? 0) < time()) return [false, 'This confirmation link has expired.'];
    $users['users'][$index]['email_verified'] = true;
    unset($users['users'][$index]['email_verify_token_hash'], $users['users'][$index]['email_verify_expires']);
    write_users($users);
    ensure_user_inventory($user['id']);
    return [true, 'Email confirmed. You can sign in now.'];
  }
  return [false, 'This confirmation link is not valid.'];
}

function base32_encode_secret($bytes) {
  $alphabet = 'ABCDEFGHIJKLMNOPQRSTUVWXYZ234567';
  $bits = '';
  for ($i = 0; $i < strlen($bytes); $i++) {
    $bits .= str_pad(decbin(ord($bytes[$i])), 8, '0', STR_PAD_LEFT);
  }
  $out = '';
  foreach (str_split($bits, 5) as $chunk) {
    if (strlen($chunk) < 5) $chunk = str_pad($chunk, 5, '0', STR_PAD_RIGHT);
    $out .= $alphabet[bindec($chunk)];
  }
  return $out;
}

function base32_decode_secret($secret) {
  $alphabet = 'ABCDEFGHIJKLMNOPQRSTUVWXYZ234567';
  $secret = strtoupper(preg_replace('/[^A-Z2-7]/i', '', (string) $secret));
  $bits = '';
  for ($i = 0; $i < strlen($secret); $i++) {
    $pos = strpos($alphabet, $secret[$i]);
    if ($pos === false) continue;
    $bits .= str_pad(decbin($pos), 5, '0', STR_PAD_LEFT);
  }
  $bytes = '';
  foreach (str_split($bits, 8) as $chunk) {
    if (strlen($chunk) === 8) $bytes .= chr(bindec($chunk));
  }
  return $bytes;
}

function generate_totp_secret() {
  return base32_encode_secret(random_bytes(20));
}

function totp_code($secret, $timeSlice = null) {
  $timeSlice = $timeSlice ?? floor(time() / 30);
  $key = base32_decode_secret($secret);
  $counter = pack('N*', 0) . pack('N*', $timeSlice);
  $hash = hash_hmac('sha1', $counter, $key, true);
  $offset = ord(substr($hash, -1)) & 0x0F;
  $part = substr($hash, $offset, 4);
  $value = unpack('N', $part)[1] & 0x7FFFFFFF;
  return str_pad((string) ($value % 1000000), 6, '0', STR_PAD_LEFT);
}

function verify_totp($secret, $code) {
  $code = preg_replace('/\D/', '', (string) $code);
  if (strlen($code) !== 6) return false;
  $slice = floor(time() / 30);
  for ($i = -1; $i <= 1; $i++) {
    if (hash_equals(totp_code($secret, $slice + $i), $code)) return true;
  }
  return false;
}

function totp_uri($user, $secret) {
  global $SITE_NAME;
  $label = rawurlencode($SITE_NAME . ':' . ($user['email'] ?? 'account'));
  $issuer = rawurlencode($SITE_NAME);
  return "otpauth://totp/{$label}?secret={$secret}&issuer={$issuer}&algorithm=SHA1&digits=6&period=30";
}

function qr_png_data_uri($text) {
  $lib = __DIR__ . '/../vendor/phpqrcode/qrlib.php';
  if (!is_file($lib)) return '';
  require_once $lib;
  ob_start();
  QRcode::png($text, null, QR_ECLEVEL_M, 5, 2);
  $png = ob_get_clean();
  return 'data:image/png;base64,' . base64_encode($png);
}

function trusted_cookie_name() {
  return 'netventory_trust';
}

function trust_current_device($user) {
  global $TRUST_DEVICE_DAYS;
  $token = bin2hex(random_bytes(32));
  $hash = hash('sha256', $token);
  $expires = time() + ($TRUST_DEVICE_DAYS * 86400);
  $devices = array_values(array_filter($user['trusted_devices'] ?? [], fn($d) => ($d['expires'] ?? 0) > time()));
  $devices[] = [
    'hash' => $hash,
    'expires' => $expires,
    'created' => time(),
    'ua' => substr($_SERVER['HTTP_USER_AGENT'] ?? '', 0, 180),
  ];
  $user['trusted_devices'] = array_slice($devices, -10);
  update_user_record($user);
  setcookie(trusted_cookie_name(), $user['id'] . ':' . $token, [
    'expires' => $expires,
    'path' => '/',
    'secure' => (!empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off') || (($_SERVER['HTTP_X_FORWARDED_PROTO'] ?? '') === 'https'),
    'httponly' => true,
    'samesite' => 'Lax',
  ]);
}

function trusted_device_ok($user) {
  $cookie = $_COOKIE[trusted_cookie_name()] ?? '';
  if (!$cookie || strpos($cookie, ':') === false) return false;
  [$userId, $token] = explode(':', $cookie, 2);
  if (!hash_equals($user['id'] ?? '', $userId)) return false;
  $hash = hash('sha256', $token);
  foreach (($user['trusted_devices'] ?? []) as $device) {
    if (($device['expires'] ?? 0) < time()) continue;
    if (hash_equals($device['hash'] ?? '', $hash)) return true;
  }
  return false;
}

function two_factor_session_ok($user) {
  if (empty($user['totp_enabled'])) return true;
  if (!empty($_SESSION['2fa_verified_at']) && (time() - (int) $_SESSION['2fa_verified_at']) < 43200) return true;
  return trusted_device_ok($user);
}
