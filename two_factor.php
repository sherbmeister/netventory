<?php
require_once __DIR__ . '/config.php';

$pendingId = $_SESSION['pending_2fa_user_id'] ?? '';
$user = $pendingId ? find_user_by_id($pendingId) : null;
if (!$user) {
  header('Location: login.php');
  exit;
}

$error = '';
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
  check_csrf();
  if (verify_totp($user['totp_secret'] ?? '', $_POST['code'] ?? '')) {
    $remember = !empty($_POST['remember_device']) || !empty($_SESSION['pending_2fa_remember']);
    login_user($user, $remember);
    unset($_SESSION['pending_2fa_remember']);
    header('Location: index.php');
    exit;
  }
  $error = 'That 2FA code was not accepted.';
}
?>
<?php require_once __DIR__ . '/header.php'; ?>

<section class="auth-shell">
  <div class="auth-brand">
    <img src="assets/icons/netventory-192.png" alt="" class="auth-logo">
    <div>
      <h1>Two-Factor</h1>
      <p>Enter the current code from your authenticator app.</p>
    </div>
  </div>

  <form method="post" class="card auth-card">
    <input type="hidden" name="csrf" value="<?= h(csrf_token()) ?>">
    <?php if ($error): ?><div class="notice notice-danger"><?= h($error) ?></div><?php endif; ?>

    <label>
      <span>Authenticator code</span>
      <input name="code" inputmode="numeric" pattern="[0-9]{6}" autocomplete="one-time-code" required autofocus>
    </label>

    <label class="check-row">
      <input type="checkbox" name="remember_device" value="1" checked>
      <span>Trust this device for <?= (int) $TRUST_DEVICE_DAYS ?> days</span>
    </label>

    <button class="btn btn-primary auth-submit">Verify</button>
  </form>
</section>

<?php require_once __DIR__ . '/footer.php'; ?>
