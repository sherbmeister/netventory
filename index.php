<?php
require_once __DIR__ . '/config.php';
$user = require_account_ready();
$db = read_inventory(current_data_file());
$q = $_GET['q'] ?? '';
$items = search_items($db, $q);
?>
<?php require_once __DIR__ . '/header.php'; ?>

<div class="flex flex-col lg:flex-row lg:items-center gap-3 mb-4">
  <form method="get" class="flex-1">
    <input name="q" value="<?= htmlspecialchars($q) ?>" placeholder="Search by name, IP, MAC, tags, Type, OS..." class="w-full">
  </form>
  <div class="flex items-center gap-3">
    <span class="text-sm" style="color:var(--muted)"><?= count($items) ?> host<?= count($items) === 1 ? '' : 's' ?></span>
    <button id="checkAllBtn" type="button" class="btn btn-primary">Refresh visible</button>
  </div>
</div>

<section class="card p-4 mb-4">
  <div class="flex flex-col md:flex-row md:items-end md:justify-between gap-3 mb-4">
    <div>
      <h1 class="text-xl font-semibold">Network Overview</h1>
      <p class="text-sm" style="color:var(--muted)">Tap a host to open details, checks, edit tools, and notes. Drag tiles to save your own layout.</p>
      <p id="overviewStatusMessage" class="text-sm mt-2" style="color:var(--muted)">Drag and drop tiles to reorder them. Changes save automatically.</p>
    </div>
  </div>

  <?php if (!empty($items)): ?>
    <div class="device-grid" id="deviceGrid" data-csrf="<?= htmlspecialchars(csrf_token()) ?>">
      <?php foreach ($items as $i): ?>
        <?php
          $host_payload = [
            'id' => $i['id'] ?? '',
            'name' => $i['name'] ?? '',
            'ip' => $i['ip'] ?? '',
            'mac' => $i['mac'] ?? '',
            'type' => $i['type'] ?? '',
            'os' => $i['os'] ?? '',
            'icon_url' => $i['icon_url'] ?? '',
            'fallback_icon' => host_icon_glyph($i),
            'tags' => array_values($i['tags'] ?? []),
            'notes' => $i['notes'] ?? '',
            'ports' => array_values($i['ports'] ?? []),
          ];
        ?>
        <button
          type="button"
          class="device-tile"
          data-host-id="<?= htmlspecialchars($i['id']) ?>"
          data-host="<?= htmlspecialchars(json_encode($host_payload, JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE), ENT_QUOTES, 'UTF-8') ?>"
          data-edit-url="edit.php?id=<?= urlencode($i['id']) ?>"
          data-delete-url="delete.php?id=<?= urlencode($i['id']) ?>&csrf=<?= csrf_token() ?>"
          title="<?= htmlspecialchars($i['ip']) ?>"
          aria-label="<?= htmlspecialchars(($i['name'] ?? 'Host') . ' ' . ($i['ip'] ?? '')) ?>"
          draggable="true"
        >
          <span class="device-tile-ip"><?= htmlspecialchars($i['ip']) ?></span>
          <span class="device-tile-name"><?= htmlspecialchars($i['name']) ?></span>
          <?= host_icon_markup($i) ?>
          <span class="status-indicator is-checking" data-tile-status title="Checking status">
            <span class="status-indicator-dot"></span>
          </span>
        </button>
      <?php endforeach; ?>
    </div>
  <?php else: ?>
    <div class="empty-panel">
      <?= trim($q) === '' ? 'No hosts yet. Add one to start building your Netventory.' : 'No hosts match this search.' ?>
    </div>
  <?php endif; ?>
</section>

<section id="hostDetails" class="card p-5">
  <div id="hostDetailsBody" class="empty-panel">
    Tap a host tile to open its ports, MAC address, type, OS, tags, notes, and live status.
  </div>
</section>

<?php require_once __DIR__ . '/footer.php'; ?>
