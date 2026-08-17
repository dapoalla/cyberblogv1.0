<?php
require_once __DIR__ . '/../includes/auth.php';
require_admin();

$currentRole = $_SESSION['admin']['role'] ?? 'editor';
if ($currentRole !== 'super_editor') {
  http_response_code(403);
  echo 'Access denied. Only Super Editors can manage backups.';
  exit;
}

// Tables in FK-safe creation order (parents before children)
$tables = [
  'cms_categories', 'cms_tags', 'cms_admin_users', 'cms_oauth_users',
  'cms_posts', 'cms_settings', 'cms_comments', 'cms_newsletter',
  'cms_contacts', 'cms_user_suggestions',
];

// --- Download backup ---
if (isset($_GET['action']) && $_GET['action'] === 'download' && isset($_GET['csrf']) && csrf_check($_GET['csrf'])) {
  header('Content-Type: application/sql; charset=utf-8');
  header('Content-Disposition: attachment; filename="cyberblog_backup_' . date('Y-m-d_His') . '.sql"');
  echo "-- CyberBlog v2.0 backup - generated " . date('c') . "\n";
  echo "SET FOREIGN_KEY_CHECKS=0;\n\n";
  foreach ($tables as $t) {
    $createRow = $mysqli->query("SHOW CREATE TABLE `$t`")->fetch_assoc();
    echo "DROP TABLE IF EXISTS `$t`;\n";
    echo $createRow['Create Table'] . ";\n\n";

    $dataRes = $mysqli->query("SELECT * FROM `$t`");
    while ($row = $dataRes->fetch_assoc()) {
      $cols = array_keys($row);
      $vals = array_map(function ($v) use ($mysqli) {
        return $v === null ? 'NULL' : "'" . $mysqli->real_escape_string($v) . "'";
      }, $row);
      echo "INSERT INTO `$t` (`" . implode('`,`', $cols) . "`) VALUES (" . implode(',', $vals) . ");\n";
    }
    echo "\n";
  }
  echo "SET FOREIGN_KEY_CHECKS=1;\n";
  exit;
}

$msg = '';
$error = '';

// --- Restore backup ---
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['restore']) && csrf_check($_POST['csrf'] ?? '')) {
  if (empty($_FILES['restore_file']['name']) || !is_uploaded_file($_FILES['restore_file']['tmp_name'])) {
    $error = 'Please choose a .sql backup file to restore.';
  } else {
    $sql = file_get_contents($_FILES['restore_file']['tmp_name']);
    if ($sql === false || trim($sql) === '') {
      $error = 'The uploaded file is empty or could not be read.';
    } else {
      $mysqli->begin_transaction();
      $ok = true;
      $errMsg = '';
      if ($mysqli->multi_query($sql)) {
        do {
          if ($res = $mysqli->store_result()) $res->free();
          if ($mysqli->errno) { $ok = false; $errMsg = $mysqli->error; break; }
        } while ($mysqli->more_results() && $mysqli->next_result());
      } else {
        $ok = false;
        $errMsg = $mysqli->error;
      }
      if ($ok) {
        $mysqli->commit();
        $msg = 'Restore completed successfully.';
      } else {
        $mysqli->rollback();
        // Note: DROP/CREATE TABLE statements auto-commit in MySQL and cannot be
        // rolled back even inside a transaction - only the row data is protected.
        $error = 'Restore failed: ' . e($errMsg) . '. Tables dropped before the failure may need to be recreated from a known-good backup.';
      }
    }
  }
}

$pageTitle = 'Backup & Restore';
include __DIR__ . '/../includes/template_header.php';
include __DIR__ . '/../includes/admin_nav.php';
?>
<div class="flex items-center justify-between">
  <h1 class="text-2xl font-bold">Backup &amp; Restore</h1>
</div>

<?php if ($msg): ?><div class="mt-4 bg-green-900/30 border border-green-700 text-green-400 px-4 py-3 rounded"><?php echo e($msg); ?></div><?php endif; ?>
<?php if ($error): ?><div class="mt-4 bg-red-900/30 border border-red-700 text-red-400 px-4 py-3 rounded"><?php echo $error; ?></div><?php endif; ?>

<div class="mt-6 bg-neutral-900 border border-neutral-800 rounded-lg p-6">
  <h2 class="text-lg font-semibold mb-2">Download Backup</h2>
  <p class="text-sm text-neutral-400 mb-4">Exports every table (posts, categories, users, comments, settings, etc.) as a single self-contained <code>.sql</code> file. Works on any host - no shell access required.</p>
  <a href="?action=download&csrf=<?php echo urlencode(csrf_token()); ?>" class="inline-block bg-sky-500 hover:bg-sky-600 text-white px-4 py-2 rounded">Download .sql backup</a>
</div>

<div class="mt-6 bg-neutral-900 border border-neutral-800 rounded-lg p-6">
  <h2 class="text-lg font-semibold mb-2">Restore Backup</h2>
  <p class="text-sm text-neutral-400 mb-4">Restoring <strong>replaces all current data</strong> - every table listed in the backup file is dropped and recreated. This cannot be undone. Only restore a file you trust.</p>
  <form method="POST" enctype="multipart/form-data" onsubmit="return confirm('This will permanently overwrite all current posts, users, comments and settings with the contents of the uploaded file. Continue?');">
    <input type="file" name="restore_file" accept=".sql" required class="text-sm">
    <input type="hidden" name="csrf" value="<?php echo e(csrf_token()); ?>">
    <div>
      <button type="submit" name="restore" value="1" class="mt-4 bg-rose-600 hover:bg-rose-700 text-white px-4 py-2 rounded">Restore from file</button>
    </div>
  </form>
</div>
<?php include __DIR__ . '/../includes/template_footer.php'; ?>
