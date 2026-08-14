<?php
require_once __DIR__ . '/config.php';
$user = require_account_ready();

$db = read_inventory(current_data_file());
$defaults_types = ['Server','Router','Switch','NAS','VM','AP','Printer'];
$defaults_oses  = ['Linux','Windows','ESXi','BSD','macOS','Other'];

$opts = $db['options'] ?? ['types'=>$defaults_types, 'oses'=>$defaults_oses];
$types = array_values(array_filter($opts['types'] ?? $defaults_types, 'strlen'));
$oses  = array_values(array_filter($opts['oses']  ?? $defaults_oses,  'strlen'));

$msg = '';
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
  check_csrf();

  $raw_types = $_POST['types'] ?? '';
  $raw_oses  = $_POST['oses']  ?? '';

  // Accept comma or newline separated entries
  $parse = function($s){
    $s = str_replace("\r", "\n", $s);
    $parts = preg_split('/[,\n]+/', $s);
    $clean = [];
    foreach ($parts as $p) {
      $p = trim($p);
      if ($p !== '' && !in_array($p, $clean, true)) $clean[] = $p;
    }
    return $clean;
  };

  $types = $parse($raw_types);
  $oses  = $parse($raw_oses);

  // Persist in db
  $db['options'] = ['types'=>$types, 'oses'=>$oses];
  write_inventory(current_data_file(), $db);
  $msg = 'Saved';
}
?>
<?php require_once __DIR__ . '/header.php'; ?>

<h1 class="text-2xl font-semibold mb-4">Options</h1>

<form method="post" class="card p-6 grid gap-6">
  <input type="hidden" name="csrf" value="<?= csrf_token() ?>">

  <?php if ($msg): ?>
    <div class="p-3 rounded"
         style="background:var(--success-bg); color:var(--success-tx); border:1px solid var(--success-br);">
      <?= htmlspecialchars($msg) ?>
    </div>
  <?php endif; ?>

  <p class="text-sm" style="color:var(--muted)">
    Set the selectable lists for <strong>Type</strong> and <strong>OS</strong>.
    Enter one value per line (commas are also accepted). The order you enter is preserved.
  </p>

  <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
    <div>
      <label class="block text-sm font-medium mb-2" style="color:var(--muted)">Types</label>
      <textarea name="types" rows="12" class="w-full" placeholder="One per line"><?= htmlspecialchars(implode("\n", $types)) ?></textarea>
    </div>
    <div>
      <label class="block text-sm font-medium mb-2" style="color:var(--muted)">Operating Systems</label>
      <textarea name="oses" rows="12" class="w-full" placeholder="One per line"><?= htmlspecialchars(implode("\n", $oses)) ?></textarea>
    </div>
  </div>

  <div class="flex items-center gap-3">
    <button class="btn btn-primary">Save</button>
    <a href="add.php" class="text-sm hover:underline" style="color:var(--muted)">Go to Add Host</a>
  </div>
</form>

<?php require_once __DIR__ . '/footer.php'; ?>
