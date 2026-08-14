<?php
require_once __DIR__ . '/config.php';
$user = require_account_ready();

$msg = '';
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
  check_csrf();
  if (!empty($_FILES['csv']['tmp_name'])) {
    $db = read_inventory(current_data_file());
    $fh = fopen($_FILES['csv']['tmp_name'], 'r');
    // Expect header: name,ip,ports,mac,type,os,tags,notes  (older exports without mac/type/os still work)
    $header = fgetcsv($fh);
    while (($row = fgetcsv($fh)) !== false) {
      $map = array_combine($header, $row);
      $item = [
        'id'      => create_id(),
        'name'    => sanitize_text($map['name'] ?? ''),
        'ip'      => trim($map['ip'] ?? ''),
        'ports'   => normalize_ports($map['ports'] ?? ''),
        'mac'     => sanitize_text($map['mac'] ?? ($map['MAC'] ?? '')),
        'type'    => sanitize_text($map['type'] ?? ''),
        'os'      => sanitize_text($map['os'] ?? ''),
        'tags'    => array_values(array_filter(array_map('trim', explode(',', $map['tags'] ?? '')))),
        'notes'   => sanitize_text($map['notes'] ?? ''),
        'created' => time(),
        'updated' => time(),
      ];
      if ($item['name'] && $item['ip']) $db['items'][] = $item;
    }
    fclose($fh);
    write_inventory(current_data_file(), $db);
    $msg = 'Import complete';
  } else {
    $msg = 'No file selected';
  }
}
?>
<?php require_once __DIR__ . '/header.php'; ?>

<h1 class="text-2xl font-semibold mb-4">Import CSV</h1>

<div class="card p-6 grid gap-4">
  <?php if ($msg): ?>
    <div class="p-3 rounded"
         style="background:<?= $msg === 'Import complete' ? 'var(--success-bg)' : 'var(--warn-bg)' ?>;
                color:<?= $msg === 'Import complete' ? 'var(--success-tx)' : 'var(--warn-tx)' ?>;
                border:1px solid <?= $msg === 'Import complete' ? 'var(--success-br)' : 'var(--warn-br)' ?>;">
      <?= htmlspecialchars($msg) ?>
    </div>
  <?php endif; ?>

  <p class="text-sm" style="color:var(--muted)">
    CSV header should be:
    <code>name,ip,ports,mac,type,os,tags,notes</code><br>
    (Older exports without <code>mac,type,os</code> also import.)
  </p>

  <form method="post" enctype="multipart/form-data" class="flex flex-col md:flex-row md:items-center gap-3">
    <input type="hidden" name="csrf" value="<?= csrf_token() ?>">
    <input type="file" name="csv" accept=".csv,text/csv" required class="w-full md:w-auto">
    <button class="btn btn-primary">Upload</button>
  </form>
</div>

<?php require_once __DIR__ . '/footer.php'; ?>
