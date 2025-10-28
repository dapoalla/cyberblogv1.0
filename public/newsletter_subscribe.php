<?php
require __DIR__ . '/../includes/db.php';
require __DIR__ . '/../includes/helpers.php';

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
  http_response_code(405);
  echo 'Method not allowed';
  exit;
}

$email = trim($_POST['email'] ?? '');
$name = trim($_POST['name'] ?? '');

if (empty($email) || !filter_var($email, FILTER_VALIDATE_EMAIL)) {
  http_response_code(400);
  echo 'Invalid email address';
  exit;
}

// Check if already subscribed
$stmt = $mysqli->prepare("SELECT id, status FROM cms_newsletter WHERE email = ? LIMIT 1");
$stmt->bind_param('s', $email);
$stmt->execute();
$result = $stmt->get_result();
$existing = $result->fetch_assoc();
$stmt->close();

if ($existing) {
  if ($existing['status'] === 'active') {
    header('Location: ' . base_url('public/index.php?newsletter=already'));
    exit;
  } else {
    // Reactivate
    $stmt = $mysqli->prepare("UPDATE cms_newsletter SET status='active', name=?, subscribed_at=NOW() WHERE id=?");
    $stmt->bind_param('si', $name, $existing['id']);
    $stmt->execute();
    $stmt->close();
  }
} else {
  // New subscription
  $stmt = $mysqli->prepare("INSERT INTO cms_newsletter (email, name) VALUES (?, ?)");
  $stmt->bind_param('ss', $email, $name);
  $stmt->execute();
  $stmt->close();
}

header('Location: ' . base_url('public/index.php?newsletter=success'));
exit;
