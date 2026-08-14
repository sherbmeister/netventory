<?php require_once __DIR__ . '/auth.php'; ?>
<?php require_once __DIR__ . '/header.php'; ?>

<section class="auth-shell">
  <div class="auth-brand">
    <img src="assets/icons/netventory-192.png" alt="" class="auth-logo">
    <div>
      <h1>Netventory</h1>
      <p>Your network inventory, reachable from desktop or phone.</p>
    </div>
  </div>

  <form method="post" class="card auth-card">
    <input type="hidden" name="csrf" value="<?= h(csrf_token()) ?>">

    <?php $flash = flash_message(); ?>
    <?php if ($flash): ?>
      <div class="notice notice-info"><?= h($flash) ?></div>
    <?php endif; ?>

    <?php if (!empty($error)): ?>
      <div class="notice notice-danger"><?= h($error) ?></div>
    <?php endif; ?>

    <label>
      <span>Email</span>
      <input type="email" name="email" autocomplete="email" required>
    </label>

    <label>
      <span>Password</span>
      <input type="password" name="password" autocomplete="current-password" required>
    </label>

    <label class="check-row">
      <input type="checkbox" name="remember_device" value="1" checked>
      <span>Trust this device after 2FA</span>
    </label>

    <button class="btn btn-primary auth-submit">Sign in</button>

    <div class="auth-links">
      <?php if ($REGISTRATION_OPEN): ?>
        <a href="register.php">Create account</a>
      <?php endif; ?>
      <a href="version.php">Version</a>
    </div>
  </form>
</section>

<?php require_once __DIR__ . '/footer.php'; ?>
