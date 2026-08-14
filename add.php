<?php
require_once __DIR__ . '/config.php';
$user = require_account_ready();

$db = read_inventory(current_data_file());

// pull options (with sensible defaults if missing)
$defaults_types = ['Server','Router','Switch','NAS','VM','AP','Printer'];
$defaults_oses  = ['Linux','Windows','ESXi','BSD','macOS','Other'];
$opts = $db['options'] ?? ['types'=>$defaults_types, 'oses'=>$defaults_oses];
$types = array_values(array_filter($opts['types'] ?? $defaults_types, 'strlen'));
$oses  = array_values(array_filter($opts['oses']  ?? $defaults_oses,  'strlen'));

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
  check_csrf();
  $item = [
    'id'      => create_id(),
    'name'    => trim($_POST['name'] ?? ''),
    'ip'      => trim($_POST['ip'] ?? ''),
    'ports'   => normalize_ports($_POST['ports'] ?? ''),
    'mac'     => normalize_mac($_POST['mac'] ?? ''),
    'type'    => trim($_POST['type'] ?? ''),
    'os'      => trim($_POST['os'] ?? ''),
    'icon_url'=> normalize_icon_url($_POST['icon_url'] ?? ''),
    'tags'    => array_values(array_filter(array_map('trim', explode(',', $_POST['tags'] ?? '')))),
    'notes'   => trim($_POST['notes'] ?? ''),
    'sort_order' => next_sort_order($db),
    'created' => time(), 'updated' => time()
  ];
  if ($item['name'] && $item['ip']) {
    $db['items'][] = $item; write_inventory(current_data_file(), $db); header('Location: index.php'); exit;
  }
}
?>
<?php require_once __DIR__ . '/header.php'; ?>

<h1 class="text-2xl font-semibold mb-4">Add Host</h1>

<form method="post" class="grid gap-4 card p-6">
  <input type="hidden" name="csrf" value="<?= csrf_token() ?>">

  <div>
    <label class="block text-sm font-medium" style="color:var(--muted)">Name</label>
    <input name="name" class="w-full" required>
  </div>

  <div>
    <label class="block text-sm font-medium" style="color:var(--muted)">IP / Hostname</label>
    <input name="ip" class="w-full" required placeholder="10.0.0.10 or host.local">
  </div>

  <div>
    <label class="block text-sm font-medium" style="color:var(--muted)">Ports (comma-separated; allow labels like 22:ssh,80:http)</label>
    <input name="ports" class="w-full" placeholder="22:ssh,80:http,443:https">
  </div>

  <div>
    <label class="block text-sm font-medium" style="color:var(--muted)">Custom Icon URL</label>
    <input name="icon_url" class="w-full" placeholder="https://example.com/icon.png or /assets/icons/router.svg">
    <p class="text-sm mt-2" style="color:var(--muted)">Optional. Supports `http`, `https`, root-relative paths, and `data:image/...` URLs.</p>
  </div>

  <div class="grid grid-cols-1 md:grid-cols-3 gap-4">
    <div>
      <label class="block text-sm font-medium" style="color:var(--muted)">MAC Address</label>
      <input name="mac" class="w-full" placeholder="AA:BB:CC:DD:EE:FF">
    </div>
    <div>
      <label class="block text-sm font-medium" style="color:var(--muted)">Type</label>
      <select name="type" class="w-full">
        <option value="">—</option>
        <?php foreach ($types as $t): ?>
          <option value="<?= htmlspecialchars($t) ?>"><?= htmlspecialchars($t) ?></option>
        <?php endforeach; ?>
      </select>
    </div>
    <div>
      <label class="block text-sm font-medium" style="color:var(--muted)">OS</label>
      <select name="os" class="w-full">
        <option value="">—</option>
        <?php foreach ($oses as $o): ?>
          <option value="<?= htmlspecialchars($o) ?>"><?= htmlspecialchars($o) ?></option>
        <?php endforeach; ?>
      </select>
    </div>
  </div>

  <div>
    <label class="block text-sm font-medium" style="color:var(--muted)">Tags (comma-separated)</label>
    <input name="tags" class="w-full" placeholder="nas,prod,vm">
  </div>

  <div>
    <label class="block text-sm font-medium" style="color:var(--muted)">Notes</label>
    <textarea name="notes" class="w-full" rows="4" placeholder="Optional notes"></textarea>
  </div>

  <div class="flex items-center gap-4">
    <button class="btn btn-primary">Save</button>
    <a href="options.php" class="text-sm hover:underline" style="color:var(--muted)">Manage Type/OS options</a>
  </div>
</form>

<?php require_once __DIR__ . '/footer.php'; ?>
