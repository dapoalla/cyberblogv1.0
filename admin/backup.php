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
$uploadsDir = __DIR__ . '/../uploads';

function cyberblog_build_sql_dump(mysqli $mysqli, array $tables): string {
  $out = "-- CyberBlog v2.0 backup - generated " . date('c') . "\n";
  $out .= "SET FOREIGN_KEY_CHECKS=0;\n\n";

  // Restoring onto an already-installed instance means every table already
  // exists with live FK constraints. Dropping and recreating one table at a
  // time fails with errno 150 as soon as a still-existing table elsewhere
  // still references the one being recreated - e.g. recreating
  // cms_categories while cms_posts (not yet dropped) still holds a FK to
  // it. Dropping everything first, then creating everything, then
  // inserting, avoids that entirely.
  foreach ($tables as $t) { $out .= "DROP TABLE IF EXISTS `$t`;\n"; }
  $out .= "\n";
  foreach ($tables as $t) {
    $createRow = $mysqli->query("SHOW CREATE TABLE `$t`")->fetch_assoc();
    $out .= $createRow['Create Table'] . ";\n\n";
  }
  foreach ($tables as $t) {
    $dataRes = $mysqli->query("SELECT * FROM `$t`");
    while ($row = $dataRes->fetch_assoc()) {
      $cols = array_keys($row);
      $vals = array_map(function ($v) use ($mysqli) {
        return $v === null ? 'NULL' : "'" . $mysqli->real_escape_string($v) . "'";
      }, $row);
      $out .= "INSERT INTO `$t` (`" . implode('`,`', $cols) . "`) VALUES (" . implode(',', $vals) . ");\n";
    }
  }
  $out .= "\nSET FOREIGN_KEY_CHECKS=1;\n";
  return $out;
}

function cyberblog_run_sql_restore(mysqli $mysqli, string $sql): array {
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
  } else {
    $mysqli->rollback();
  }
  return [$ok, $errMsg];
}

// --- Download backup (.zip: database.sql + uploads/) ---
if (isset($_GET['action']) && $_GET['action'] === 'download' && isset($_GET['csrf']) && csrf_check($_GET['csrf'])) {
  $tmpZip = tempnam(sys_get_temp_dir(), 'cbbackup');
  $zip = new ZipArchive();
  $zip->open($tmpZip, ZipArchive::OVERWRITE);
  $zip->addFromString('database.sql', cyberblog_build_sql_dump($mysqli, $tables));
  if (is_dir($uploadsDir)) {
    foreach (scandir($uploadsDir) as $f) {
      if ($f === '.' || $f === '..') continue;
      $full = $uploadsDir . '/' . $f;
      if (is_file($full)) $zip->addFile($full, 'uploads/' . $f);
    }
  }
  $zip->close();

  header('Content-Type: application/zip');
  header('Content-Disposition: attachment; filename="cyberblog_backup_' . date('Y-m-d_His') . '.zip"');
  header('Content-Length: ' . filesize($tmpZip));
  readfile($tmpZip);
  unlink($tmpZip);
  exit;
}

$msg = '';
$error = '';

// A POST whose body exceeds post_max_size makes PHP silently discard both
// $_POST and $_FILES entirely - no error, no CSRF field, nothing - which
// otherwise shows up as a baffling "please choose a file" even though one
// was picked. Detect that specific case before anything CSRF-gated runs,
// since the CSRF field itself is one of the things that gets dropped.
if ($_SERVER['REQUEST_METHOD'] === 'POST' && empty($_POST) && empty($_FILES) && (int)($_SERVER['CONTENT_LENGTH'] ?? 0) > 0) {
  $sentMb = round(((int)$_SERVER['CONTENT_LENGTH']) / 1048576, 1);
  $postMax = ini_get('post_max_size') ?: '?';
  $uploadMax = ini_get('upload_max_filesize') ?: '?';
  $error = "The uploaded file (~{$sentMb}MB) is larger than this server currently allows "
    . "(post_max_size={$postMax}, upload_max_filesize={$uploadMax}). Ask your host to raise both, "
    . "or - if you can edit PHP settings yourself (e.g. cPanel's MultiPHP INI Editor) - set them to at least 64M each, then try again.";
}

// --- Restore backup (.zip with photos, or legacy .sql-only) ---
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['restore']) && csrf_check($_POST['csrf'] ?? '')) {
  $uploadErr = $_FILES['restore_file']['error'] ?? UPLOAD_ERR_NO_FILE;
  if ($uploadErr === UPLOAD_ERR_INI_SIZE || $uploadErr === UPLOAD_ERR_FORM_SIZE) {
    $error = 'That file is larger than this server\'s upload_max_filesize/post_max_size allows (currently upload_max_filesize=' . (ini_get('upload_max_filesize') ?: '?') . '). Ask your host to raise it, or use cPanel\'s MultiPHP INI Editor if you manage PHP settings yourself.';
  } elseif (empty($_FILES['restore_file']['name']) || !is_uploaded_file($_FILES['restore_file']['tmp_name'])) {
    $error = 'Please choose a backup file to restore.';
  } else {
    $tmpPath = $_FILES['restore_file']['tmp_name'];
    $origName = $_FILES['restore_file']['name'];
    $isZip = strtolower(pathinfo($origName, PATHINFO_EXTENSION)) === 'zip';
    $sql = null;
    $photosRestored = 0;

    if ($isZip) {
      $zip = new ZipArchive();
      if ($zip->open($tmpPath) !== true) {
        $error = 'Could not open the uploaded .zip file.';
      } elseif (($sql = $zip->getFromName('database.sql')) === false) {
        $error = 'The .zip file does not contain a database.sql - is this a CyberBlog backup?';
      } else {
        if (!is_dir($uploadsDir)) @mkdir($uploadsDir, 0775, true);
        $uploadsWritable = is_dir($uploadsDir) && is_writable($uploadsDir);
        $photosExpected = 0;
        for ($i = 0; $i < $zip->numFiles; $i++) {
          $entry = $zip->getNameIndex($i);
          if (strpos($entry, 'uploads/') !== 0) continue;
          $baseName = basename($entry);
          // Guard against zip-slip: only allow a plain filename, no traversal.
          if ($baseName === '' || $baseName === '.' || $baseName === '..' || strpos($entry, '..') !== false) continue;
          $photosExpected++;
          if (!$uploadsWritable) continue;
          $data = $zip->getFromIndex($i);
          if ($data === false) continue;
          if (@file_put_contents($uploadsDir . '/' . $baseName, $data) !== false) $photosRestored++;
        }
        $zip->close();
        if ($photosExpected > 0 && $photosRestored < $photosExpected) {
          $photosWarning = "Warning: only $photosRestored of $photosExpected photo(s) from the backup could be written to $uploadsDir - "
            . ($uploadsWritable ? 'some files failed individually (check disk space/permissions).' : 'that folder is not writable by the web server. Fix its permissions (commonly chmod 775) and restore again to get the photos.');
        }
      }
    } else {
      $sql = file_get_contents($tmpPath);
    }

    if ($sql === null) {
      // $error already set above for the zip-open/missing-sql cases
    } elseif ($sql === false || trim($sql) === '') {
      $error = 'The uploaded backup has no database content.';
    } else {
      [$ok, $errMsg] = cyberblog_run_sql_restore($mysqli, $sql);
      if ($ok) {
        $msg = 'Restore completed successfully.' . ($photosRestored > 0 ? " Restored $photosRestored photo(s)." : '');
        if (!empty($photosWarning)) { $error = $photosWarning; }
      } else {
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
  <p class="text-sm text-neutral-400 mb-4">Exports every table (posts, categories, users, comments, settings, etc.) plus everything in <code>/uploads</code> as a single <code>.zip</code> file. Works on any host - no shell access required.</p>
  <a href="?action=download&csrf=<?php echo urlencode(csrf_token()); ?>" class="inline-block bg-sky-500 hover:bg-sky-600 text-white px-4 py-2 rounded">Download .zip backup</a>
</div>

<div class="mt-6 bg-neutral-900 border border-neutral-800 rounded-lg p-6">
  <h2 class="text-lg font-semibold mb-2">Restore Backup</h2>
  <p class="text-sm text-neutral-400 mb-4">Restoring <strong>replaces all current data</strong> - every table listed in the backup is dropped and recreated, and any photos in the backup are copied into <code>/uploads</code> (existing files with the same name are overwritten). This cannot be undone. Only restore a file you trust. Accepts a <code>.zip</code> backup from this tool, or a legacy <code>.sql</code>-only backup.</p>
  <form method="POST" enctype="multipart/form-data" onsubmit="return confirm('This will permanently overwrite all current posts, users, comments and settings with the contents of the uploaded file. Continue?');">
    <input type="file" name="restore_file" accept=".zip,.sql" required class="text-sm">
    <input type="hidden" name="csrf" value="<?php echo e(csrf_token()); ?>">
    <div>
      <button type="submit" name="restore" value="1" class="mt-4 bg-rose-600 hover:bg-rose-700 text-white px-4 py-2 rounded">Restore from file</button>
    </div>
  </form>
</div>
<?php include __DIR__ . '/../includes/template_footer.php'; ?>
