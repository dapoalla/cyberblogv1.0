<?php
require_once __DIR__ . '/../includes/db.php';
header('Content-Type: text/plain; charset=utf-8');

$pubId = '';
if ($res = $mysqli->query("SELECT adsense_publisher_id FROM cms_settings WHERE id=1")) {
  $pubId = trim((string)($res->fetch_assoc()['adsense_publisher_id'] ?? ''));
}
// ads.txt uses the bare "pub-..." form; strip a "ca-" prefix if someone
// pasted the ad-unit "client=ca-pub-..." value instead.
$pubId = preg_replace('/^ca-/', '', $pubId);

if ($pubId === '') {
  http_response_code(404);
  echo "# No AdSense publisher ID configured. Set one in Admin > Settings.\n";
  exit;
}

echo "google.com, $pubId, DIRECT, f08c47fec0942fa0\n";
