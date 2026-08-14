<?php
require_once __DIR__ . '/config.php';
logout_user();
session_regenerate_id(true);
header('Location: login.php');
exit;
