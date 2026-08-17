<?php
require __DIR__ . '/../includes/db.php';
require __DIR__ . '/../includes/helpers.php';

header('Content-Type: application/json');

$name = trim($_GET['name'] ?? '');
if ($name === '') {
  echo json_encode(['ok' => false, 'error' => 'missing name']);
  exit;
}

$info = null;
// Try match by display_name first, then username
$stmt = $mysqli->prepare("SELECT id, username, COALESCE(display_name, username) AS display_name, bio, profile_image, role, created_at FROM cms_admin_users WHERE display_name=? OR username=? LIMIT 1");
$stmt->bind_param('ss', $name, $name);
$stmt->execute();
$res = $stmt->get_result();
if ($row = $res->fetch_assoc()) {
  $info = [
    'id' => (int)$row['id'],
    'username' => $row['username'],
    'display_name' => $row['display_name'],
    'bio' => $row['bio'],
    'profile_image' => $row['profile_image'],
    'role' => $row['role'],
    'joined' => $row['created_at'],
    'profile_url' => base_url('public/editor.php?u=' . urlencode($row['username']))
  ];
}
$stmt->close();

echo json_encode(['ok' => (bool)$info, 'data' => $info]);
