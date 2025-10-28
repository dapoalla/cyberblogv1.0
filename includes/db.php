<?php
$config = require __DIR__ . '/../config.php';
$cfg = $config['db'] ?? [];
mysqli_report(MYSQLI_REPORT_OFF);
$mysqli = mysqli_init();
if (!$mysqli) {
  http_response_code(200);
  ?><!doctype html><html lang="en"><head><meta charset="utf-8" /><meta name="viewport" content="width=device-width, initial-scale=1" /><title>PHP mysqli extension missing</title><style>html,body{background:#0a0a0a;color:#e5e5e5;font-family:ui-sans-serif,system-ui,-apple-system,Segoe UI,Roboto,Helvetica,Arial;margin:0;padding:20px}</style></head><body><div style="max-width:680px;margin:0 auto;border:1px solid #262626;background:#111827;border-radius:8px;padding:20px"><h1 style="font-size:20px;margin:0 0 10px">PHP mysqli extension missing</h1><p style="color:#d4d4d4">This server does not have the <code>mysqli</code> extension enabled. Please enable it in your PHP configuration or install it via your hosting control panel.</p><p style="margin-top:10px"><a href="../setup/index.php" style="color:#38bdf8">Return to setup</a></p></div></body></html><?php
  exit;
}
@mysqli_real_connect($mysqli, $cfg['host'] ?? 'localhost', $cfg['user'] ?? '', $cfg['pass'] ?? '', $cfg['name'] ?? '', $cfg['port'] ?? 3306, null, 0);
if ($mysqli->connect_errno) {
  http_response_code(200);
  ?><!doctype html>
  <html lang="en">
  <head>
    <meta charset="utf-8" />
    <meta name="viewport" content="width=device-width, initial-scale=1" />
    <title>Database connection failed</title>
    <link rel="preconnect" href="https://cdn.tailwindcss.com">
    <script src="https://cdn.tailwindcss.com?plugins=typography,forms"></script>
    <style>html,body{background:#0a0a0a;color:#e5e5e5;font-family:ui-sans-serif,system-ui,-apple-system,Segoe UI,Roboto,Helvetica,Arial;margin:0;padding:0}</style>
  </head>
  <body>
    <div class="max-w-xl mx-auto px-4 py-10">
      <div class="bg-neutral-900 border border-neutral-800 rounded p-6">
        <h1 class="text-xl font-bold">Database connection failed</h1>
        <p class="text-neutral-300 mt-2">Please check your settings in <code>config.php</code>:</p>
        <ul class="mt-3 text-sm text-neutral-400 list-disc list-inside">
          <li>Host: <?php echo htmlspecialchars($cfg['host'] ?? 'localhost'); ?></li>
          <li>Name: <?php echo htmlspecialchars($cfg['name'] ?? ''); ?></li>
          <li>User: <?php echo htmlspecialchars($cfg['user'] ?? ''); ?></li>
          <li>Port: <?php echo (int)($cfg['port'] ?? 3306); ?></li>
        </ul>
        <?php if (!empty($cfg['user']) && empty($cfg['pass'])): ?>
          <p class="mt-3 text-amber-400">Note: DB user is set but password is empty.</p>
        <?php endif; ?>
        <p class="mt-4"><a href="../setup/index.php" class="text-sky-400">Return to setup</a> or edit <code>config.php</code>.</p>
      </div>
    </div>
  </body>
  </html><?php
  exit;
}
$mysqli->set_charset('utf8mb4');
