<?php
require_once __DIR__ . '/config.php';
$user = require_account_ready();
if (!isset($_GET['csrf']) || !hash_equals(csrf_token(), $_GET['csrf'])) { http_response_code(403); exit('Bad CSRF'); }
$id = $_GET['id'] ?? '';
$db = read_inventory(current_data_file());
$exists = find_item($db, $id);
if ($exists) { delete_item($db, $id); write_inventory(current_data_file(), $db); }
header('Location: index.php');