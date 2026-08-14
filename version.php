<?php
require_once __DIR__ . '/config.php';
$user = is_logged_in() ? current_user() : null;
$dataWritable = is_writable($DATA_DIR);
$inventoryFile = $user ? user_inventory_file($user['id']) : null;
?>
<?php require_once __DIR__ . '/header.php'; ?>

<div class="page-head">
  <div>
    <h1>Version</h1>
    <p>Runtime and app status.</p>
  </div>
</div>

<section class="card settings-card">
  <div class="version-grid">
    <div><span>App</span><strong><?= h($SITE_NAME) ?></strong></div>
    <div><span>Version</span><strong><?= h($APP_VERSION) ?></strong></div>
    <div><span>Release</span><strong><?= h($APP_RELEASE_DATE) ?></strong></div>
    <div><span>PHP</span><strong><?= h(PHP_VERSION) ?></strong></div>
    <div><span>Data directory</span><strong><?= $dataWritable ? 'Writable' : 'Not writable' ?></strong></div>
    <div><span>Email confirmation</span><strong><?= $REQUIRE_EMAIL_CONFIRMATION ? 'Enabled' : 'Disabled' ?></strong></div>
    <div><span>Registrations</span><strong><?= $REGISTRATION_OPEN ? 'Open' : 'Closed' ?></strong></div>
    <div><span>Trusted device window</span><strong><?= (int) $TRUST_DEVICE_DAYS ?> days</strong></div>
    <?php if ($inventoryFile): ?>
      <div><span>Your inventory file</span><strong><?= h(basename($inventoryFile)) ?></strong></div>
    <?php endif; ?>
  </div>
</section>

<?php require_once __DIR__ . '/footer.php'; ?>
