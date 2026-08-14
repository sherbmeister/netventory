<?php
$APP_VERSION = '1.0.0';
$APP_RELEASE_DATE = '2026-08-14';
$SITE_NAME = 'Netventory';
$BASE_URL = rtrim(getenv('NETVENTORY_BASE_URL') ?: 'https://netventory.quantumnet.space', '/');
$DATA_DIR = getenv('NETVENTORY_DATA_DIR') ?: (__DIR__ . '/data');
$USERS_FILE = $DATA_DIR . '/users.json';
$LEGACY_DATA_FILE = $DATA_DIR . '/iplist.json';
$OPTIONS_FILE = $DATA_DIR . '/options.json';
$SESSION_NAME = 'netventory_sess';
$CSRF_KEY = 'netventory_csrf';
$TCP_TIMEOUT = (int) (getenv('NETVENTORY_TCP_TIMEOUT') ?: 1);
$ALLOW_PING = filter_var(getenv('NETVENTORY_ALLOW_PING') ?: 'true', FILTER_VALIDATE_BOOLEAN);
$MAIL_FROM = getenv('NETVENTORY_MAIL_FROM') ?: 'Netventory <no-reply@netventory.quantumnet.space>';
$REGISTRATION_OPEN = filter_var(getenv('NETVENTORY_REGISTRATION_OPEN') ?: 'true', FILTER_VALIDATE_BOOLEAN);
$TRUST_DEVICE_DAYS = (int) (getenv('NETVENTORY_TRUST_DEVICE_DAYS') ?: 21);
$REQUIRE_EMAIL_CONFIRMATION = filter_var(getenv('NETVENTORY_REQUIRE_EMAIL_CONFIRMATION') ?: 'true', FILTER_VALIDATE_BOOLEAN);
$BOOTSTRAP_LEGACY_TO_FIRST_USER = filter_var(getenv('NETVENTORY_BOOTSTRAP_LEGACY_TO_FIRST_USER') ?: 'true', FILTER_VALIDATE_BOOLEAN);

require_once __DIR__ . '/lib/lib.php';

if (!is_dir($DATA_DIR)) {
  @mkdir($DATA_DIR, 0750, true);
}

session_name($SESSION_NAME);
$__sess_dir = __DIR__ . '/_sessions';
if (!is_dir($__sess_dir)) { @mkdir($__sess_dir, 0700, true); }
session_save_path($__sess_dir);

$isHttps = (!empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off') || (($_SERVER['HTTP_X_FORWARDED_PROTO'] ?? '') === 'https');
session_start([
  'cookie_httponly' => true,
  'cookie_samesite' => 'Lax',
  'cookie_secure' => $isHttps,
]);

if (empty($_SESSION[$CSRF_KEY])) {
  $_SESSION[$CSRF_KEY] = bin2hex(random_bytes(32));
}

function csrf_token() {
  global $CSRF_KEY;
  return $_SESSION[$CSRF_KEY] ?? '';
}

function check_csrf() {
  global $CSRF_KEY;
  if ($_SERVER['REQUEST_METHOD'] !== 'POST') return;
  $tok = $_POST['csrf'] ?? '';
  $sess = $_SESSION[$CSRF_KEY] ?? '';
  if (!$tok || !$sess || !hash_equals($sess, $tok)) {
    http_response_code(403);
    exit('Invalid CSRF token');
  }
}

function app_url($path = '') {
  global $BASE_URL;
  return $BASE_URL . '/' . ltrim((string) $path, '/');
}

function is_logged_in() {
  return !empty($_SESSION['user_id']);
}

function current_user_id() {
  return $_SESSION['user_id'] ?? null;
}

function current_user() {
  $id = current_user_id();
  if (!$id) return null;
  return find_user_by_id($id);
}

function require_login() {
  if (!is_logged_in()) {
    header('Location: login.php');
    exit;
  }
}

function require_email_verified($user) {
  global $REQUIRE_EMAIL_CONFIRMATION;
  if ($REQUIRE_EMAIL_CONFIRMATION && empty($user['email_verified'])) {
    $_SESSION['flash'] = 'Please confirm your email address before continuing.';
    header('Location: login.php');
    exit;
  }
}

function require_account_ready() {
  require_login();
  $user = current_user();
  if (!$user) {
    logout_user();
    header('Location: login.php');
    exit;
  }
  require_email_verified($user);
  ensure_user_inventory($user['id']);
  if (!empty($user['totp_enabled']) && !two_factor_session_ok($user)) {
    $_SESSION['pending_2fa_user_id'] = $user['id'];
    unset($_SESSION['user_id']);
    header('Location: two_factor.php');
    exit;
  }
  return $user;
}

function login_user($user, $remember_device = false) {
  session_regenerate_id(true);
  $_SESSION['user_id'] = $user['id'];
  unset($_SESSION['pending_2fa_user_id']);
  $_SESSION['2fa_verified_at'] = time();
  if ($remember_device) trust_current_device($user);
}

function logout_user() {
  unset($_SESSION['user_id'], $_SESSION['pending_2fa_user_id'], $_SESSION['2fa_verified_at']);
}

function current_data_file() {
  $userId = current_user_id();
  if (!$userId) return null;
  return user_inventory_file($userId);
}

function flash_message() {
  $msg = $_SESSION['flash'] ?? '';
  unset($_SESSION['flash']);
  return $msg;
}
