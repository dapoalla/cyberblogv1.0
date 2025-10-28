<?php
// One-time script to set admin password, then DELETE this file.
require __DIR__ . '/../includes/db.php';
$pwd = 'Admin4cyberrose*';
$hash = password_hash($pwd, PASSWORD_DEFAULT);
$stmt = $mysqli->prepare("UPDATE cms_admin_users SET password_hash=? WHERE id=1");
$stmt->bind_param('s', $hash);
$stmt->execute();
$ok = $stmt->affected_rows >= 0; // row may already exist
$stmt->close();
header('Content-Type: text/plain');
echo $ok ? "Admin password updated to: $pwd\nPlease DELETE this file: admin/set_admin_password.php" : "Failed to update password";
