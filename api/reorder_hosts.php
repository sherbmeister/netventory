<?php
require_once __DIR__ . '/../config.php';
$user = require_account_ready();
header('Content-Type: application/json');

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
  http_response_code(405);
  echo json_encode(['ok' => false, 'error' => 'method not allowed']);
  exit;
}

check_csrf();

$orderedIds = $_POST['ordered_ids'] ?? [];
if (!is_array($orderedIds) || !$orderedIds) {
  http_response_code(400);
  echo json_encode(['ok' => false, 'error' => 'missing ordered ids']);
  exit;
}

$db = read_inventory(current_data_file());
if (!reorder_items($db, $orderedIds)) {
  http_response_code(400);
  echo json_encode(['ok' => false, 'error' => 'invalid host order']);
  exit;
}

write_inventory(current_data_file(), $db);
echo json_encode(['ok' => true]);
