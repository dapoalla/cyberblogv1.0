<?php
require_once __DIR__ . '/helpers.php';
$config = require __DIR__ . '/../config.php';
$cfg = $config['db'];

$mysqli = null;
if (!empty($cfg['name']) && !empty($cfg['user'])) {
  $mysqli = mysqli_init();
  $ok = @mysqli_real_connect($mysqli, $cfg['host'], $cfg['user'], $cfg['pass'], $cfg['name'], (int)($cfg['port'] ?? 3306), null, 0);
  if (!$ok) $mysqli = null;
}

$schemaReady = false;
if ($mysqli) {
  $mysqli->set_charset('utf8mb4');
  $check = @$mysqli->query("SHOW TABLES LIKE 'cms_admin_users'");
  $schemaReady = $check && $check->num_rows > 0;
}

if (!$mysqli || !$schemaReady) {
  header('Location: ' . base_url('install/index.php'));
  exit;
}

require_once __DIR__ . '/migrate.php';
cyberblog_migrate($mysqli);
