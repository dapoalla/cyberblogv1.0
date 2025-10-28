<?php
$config = require __DIR__ . '/../config.php';
$cfg = $config['db'] ?? [];
$mysqli = mysqli_init();
@mysqli_real_connect($mysqli, $cfg['host'] ?? 'localhost', $cfg['user'] ?? '', $cfg['pass'] ?? '', $cfg['name'] ?? '', $cfg['port'] ?? 3306, null, 0);
if ($mysqli->connect_errno) {
  http_response_code(500);
  echo 'Database connection failed.';
  exit;
}
$mysqli->set_charset('utf8mb4');
