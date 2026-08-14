<?php
require_once __DIR__ . '/config.php';

if (!$REGISTRATION_OPEN) {
  http_response_code(403);
  exit('Registration is closed.');
}

$error = '';
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
  check_csrf();
  [$user, $error] = register_user($_POST['email'] ?? '', $_POST['password'] ?? '', $_POST['display_name'] ?? '');
  if ($user && !$error) {
    $_SESSION['flash'] = $REQUIRE_EMAIL_CONFIRMATION
      ? 'Account created. Check your email for the confirmation link.'
      : 'Account created. You can sign in now.';
    header('Location: login.php');
    exit;
  }
}
?>
<?php require_once __DIR__ . '/header.php'; ?>

<section class="auth-shell">
  <div class="auth-brand">
    <img src="assets/icons/netventory-192.png" alt="" class="auth-logo">
    <div>
      <h1>Create Account</h1>
      <p>Each account gets a private Netventory.</p>
    </div>
  </div>

  <form method="post" class="card auth-card">
    <input type="hidden" name="csrf" value="<?= h(csrf_token()) ?>">

    <?php if ($error): ?>
      <div class="notice notice-danger"><?= h($error) ?></div>
    <?php endif; ?>

    <label>
      <span>Name</span>
      <input name="display_name" autocomplete="name" required>
    </label>

    <label>
      <span>Email</span>
      <input type="email" name="email" autocomplete="email" required>
    </label>

    <label>
      <span>Password</span>
      <input type="password" name="password" autocomplete="new-password" minlength="10" required>
    </label>

    <button class="btn btn-primary auth-submit">Create account</button>

    <div class="auth-links">
      <a href="login.php">Back to sign in</a>
    </div>
  </form>
</section>

<?php require_once __DIR__ . '/footer.php'; ?>
