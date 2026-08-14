<?php
require_once __DIR__ . '/config.php';

$error = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
  check_csrf();
  $email = normalize_email($_POST['email'] ?? '');
  $password = $_POST['password'] ?? '';
  $user = find_user_by_email($email);

  if (!$user || !password_verify($password, $user['password_hash'] ?? '')) {
    $error = 'Invalid email or password.';
  } elseif ($REQUIRE_EMAIL_CONFIRMATION && empty($user['email_verified'])) {
    $error = 'Confirm your email address before signing in.';
  } elseif (!empty($user['totp_enabled']) && !trusted_device_ok($user)) {
    $_SESSION['pending_2fa_user_id'] = $user['id'];
    $_SESSION['pending_2fa_remember'] = !empty($_POST['remember_device']);
    header('Location: two_factor.php');
    exit;
  } else {
    login_user($user, !empty($_POST['remember_device']) && !empty($user['totp_enabled']));
    ensure_user_inventory($user['id']);
    header('Location: index.php');
    exit;
  }
}
