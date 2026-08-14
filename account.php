<?php
require_once __DIR__ . '/config.php';
$user = require_account_ready();

$message = '';
$error = '';
if (empty($_SESSION['totp_setup_secret'])) {
  $_SESSION['totp_setup_secret'] = generate_totp_secret();
}
$setupSecret = $_SESSION['totp_setup_secret'];

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
  check_csrf();
  $action = $_POST['action'] ?? '';

  if ($action === 'enable_2fa') {
    if (verify_totp($setupSecret, $_POST['code'] ?? '')) {
      $user['totp_secret'] = $setupSecret;
      $user['totp_enabled'] = true;
      update_user_record($user);
      unset($_SESSION['totp_setup_secret']);
      $_SESSION['flash'] = '2FA enabled.';
      header('Location: account.php');
      exit;
    }
    $error = 'The setup code was not accepted.';
  }

  if ($action === 'disable_2fa') {
    if (password_verify($_POST['password'] ?? '', $user['password_hash'] ?? '')) {
      $user['totp_enabled'] = false;
      $user['totp_secret'] = '';
      $user['trusted_devices'] = [];
      update_user_record($user);
      $_SESSION['flash'] = '2FA disabled.';
      header('Location: account.php');
      exit;
    }
    $error = 'Password was not accepted.';
  }

  if ($action === 'clear_trusted') {
    $user['trusted_devices'] = [];
    update_user_record($user);
    setcookie(trusted_cookie_name(), '', ['expires' => time() - 3600, 'path' => '/']);
    $_SESSION['flash'] = 'Trusted devices cleared.';
    header('Location: account.php');
    exit;
  }
}

$user = current_user();
$qrUri = totp_uri($user, $setupSecret);
$qrData = qr_png_data_uri($qrUri);
$message = flash_message();
?>
<?php require_once __DIR__ . '/header.php'; ?>

<div class="page-head">
  <div>
    <h1>Account</h1>
    <p><?= h($user['email']) ?></p>
  </div>
</div>

<?php if ($message): ?><div class="notice notice-info mb-4"><?= h($message) ?></div><?php endif; ?>
<?php if ($error): ?><div class="notice notice-danger mb-4"><?= h($error) ?></div><?php endif; ?>

<div class="settings-grid">
  <section class="card settings-card">
    <h2>Two-Factor Authentication</h2>
    <?php if (!empty($user['totp_enabled'])): ?>
      <p class="muted">2FA is enabled. Trusted devices skip codes for <?= (int) $TRUST_DEVICE_DAYS ?> days.</p>
      <form method="post" class="stack">
        <input type="hidden" name="csrf" value="<?= h(csrf_token()) ?>">
        <input type="hidden" name="action" value="disable_2fa">
        <label>
          <span>Password</span>
          <input type="password" name="password" autocomplete="current-password" required>
        </label>
        <button class="btn btn-ghost danger-text">Disable 2FA</button>
      </form>
    <?php else: ?>
      <p class="muted">Scan the QR code in your authenticator app, then enter the 6-digit code.</p>
      <div class="qr-panel">
        <?php if ($qrData): ?>
          <img src="<?= h($qrData) ?>" alt="2FA QR code">
        <?php endif; ?>
        <code><?= h($setupSecret) ?></code>
      </div>
      <form method="post" class="stack">
        <input type="hidden" name="csrf" value="<?= h(csrf_token()) ?>">
        <input type="hidden" name="action" value="enable_2fa">
        <label>
          <span>Authenticator code</span>
          <input name="code" inputmode="numeric" pattern="[0-9]{6}" autocomplete="one-time-code" required>
        </label>
        <button class="btn btn-primary">Enable 2FA</button>
      </form>
    <?php endif; ?>
  </section>

  <section class="card settings-card">
    <h2>Trusted Devices</h2>
    <p class="muted">A trusted device can skip 2FA until its trust window expires.</p>
    <p><?= count(array_filter($user['trusted_devices'] ?? [], fn($d) => ($d['expires'] ?? 0) > time())) ?> active trusted device(s).</p>
    <form method="post">
      <input type="hidden" name="csrf" value="<?= h(csrf_token()) ?>">
      <input type="hidden" name="action" value="clear_trusted">
      <button class="btn btn-ghost">Clear trusted devices</button>
    </form>
  </section>
</div>

<?php require_once __DIR__ . '/footer.php'; ?>
