<?php
require_once __DIR__ . '/config.php';

[$ok, $message] = confirm_email_token($_GET['token'] ?? '');
$_SESSION['flash'] = $message;
header('Location: login.php');
exit;
