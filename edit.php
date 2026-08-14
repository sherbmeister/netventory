<?php
require_once __DIR__ . '/config.php';
$user = require_account_ready();

$db = read_inventory(current_data_file());
$id = $_GET['id'] ?? '';
$item = find_item($db, $id);
if (!$item) { http_response_code(404); exit('Not found'); }

$defaults_types = ['Server','Router','Switch','NAS','VM','AP','Printer'];
$defaults_oses  = ['Linux','Windows','ESXi','BSD','macOS','Other'];
$saved = $db['options'] ?? ['types'=>$defaults_types, 'oses'=>$defaults_oses];
$types = array_values(array_filter($saved['types'] ?? $defaults_types, 'strlen'));
$oses  = array_values(array_filter($saved['oses']  ?? $defaults_oses,  'strlen'));

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
  check_csrf();
  $updated = [
    'id'      => $item['id'],
    'name'    => trim($_POST['name'] ?? ''),
    'ip'      => trim($_POST['ip'] ?? ''),
    'ports'   => normalize_ports($_POST['ports'] ?? ''),
    'mac'     => normalize_mac($_POST['mac'] ?? ''),
    'type'    => trim($_POST['type'] ?? ''),
    'os'      => trim($_POST['os'] ?? ''),
    'icon_url'=> normalize_icon_url($_POST['icon_url'] ?? ''),
    'tags'    => array_values(array_filter(array_map('trim', explode(',', $_POST['tags'] ?? '')))),
    'notes'   => trim($_POST['notes'] ?? ''),
    'sort_order' => is_numeric($item['sort_order'] ?? null) ? (int) $item['sort_order'] : next_sort_order($db),
    'created' => $item['created'] ?? time(),
    'updated' => time(),
  ];
  update_item($db, $updated); write_inventory(current_data_file(), $db); header('Location: index.php'); exit;
}

$ports_str = implode(',', array_map(fn($p)=>$p['port'].($p['label']?':'.$p['label']:''), $item['ports'] ?? []));
$tags_str  = implode(',', $item['tags'] ?? []);
?>
<?php require_once __DIR__ . '/header.php'; ?>

<h1 class="text-2xl font-semibold mb-4">Edit Host</h1>

<form method="post" class="grid gap-4 card p-6">
  <input type="hidden" name="csrf" value="<?= csrf_token() ?>">
  <input type="hidden" name="id" value="<?= htmlspecialchars($item['id']) ?>">

  <div>
    <label class="block text-sm font-medium" style="color:var(--muted)">Name</label>
    <input name="name" class="w-full" required value="<?= htmlspecialchars($item['name'] ?? '') ?>">
  </div>

  <div>
    <label class="block text-sm font-medium" style="color:var(--muted)">IP / Hostname</label>
    <input name="ip" class="w-full" required value="<?= htmlspecialchars($item['ip'] ?? '') ?>">
  </div>

  <div>
    <label class="block text-sm font-medium" style="color:var(--muted)">Ports (comma-separated; allow labels like 22:ssh,80:http)</label>
    <input name="ports" class="w-full" value="<?= htmlspecialchars($ports_str) ?>">
  </div>

  <div>
    <label class="block text-sm font-medium" style="color:var(--muted)">Custom Icon URL</label>
    <input name="icon_url" class="w-full" placeholder="https://example.com/icon.png or /assets/icons/router.svg" value="<?= htmlspecialchars($item['icon_url'] ?? '') ?>">
    <p class="text-sm mt-2" style="color:var(--muted)">Optional. Supports `http`, `https`, root-relative paths, and `data:image/...` URLs.</p>
  </div>

  <div class="grid grid-cols-1 md:grid-cols-3 gap-4">
    <div>
      <label class="block text-sm font-medium" style="color:var(--muted)">MAC Address</label>
      <input name="mac" class="w-full" placeholder="AA:BB:CC:DD:EE:FF" value="<?= htmlspecialchars($item['mac'] ?? '') ?>">
    </div>
    <div>
      <label class="block text-sm font-medium" style="color:var(--muted)">Type</label>
      <select name="type" class="w-full">
        <option value="">—</option>
        <?php foreach ($types as $t): ?>
          <option value="<?= htmlspecialchars($t) ?>" <?= ($t === ($item['type'] ?? '')) ? 'selected' : '' ?>>
            <?= htmlspecialchars($t) ?>
          </option>
        <?php endforeach; ?>
      </select>
    </div>
    <div>
      <label class="block text-sm font-medium" style="color:var(--muted)">OS</label>
      <select name="os" class="w-full">
        <option value="">—</option>
        <?php foreach ($oses as $o): ?>
          <option value="<?= htmlspecialchars($o) ?>" <?= ($o === ($item['os'] ?? '')) ? 'selected' : '' ?>>
            <?= htmlspecialchars($o) ?>
          </option>
        <?php endforeach; ?>
      </select>
    </div>
  </div>

  <div>
    <label class="block text-sm font-medium" style="color:var(--muted)">Tags (comma-separated)</label>
    <input name="tags" class="w-full" value="<?= htmlspecialchars($tags_str) ?>">
  </div>

  <div>
    <label class="block text-sm font-medium" style="color:var(--muted)">Notes</label>
    <textarea name="notes" class="w-full" rows="4"><?= htmlspecialchars($item['notes'] ?? '') ?></textarea>
  </div>

  <div class="flex items-center gap-4">
    <button class="btn btn-primary">Save</button>
    <a href="index.php" class="text-sm hover:underline" style="color:var(--muted)">Cancel</a>
  </div>
</form>

<?php require_once __DIR__ . '/footer.php'; ?>
