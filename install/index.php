<?php
// CyberBlog v2.0 first-run setup wizard.
// Reached automatically by includes/db.php whenever the DB is unreachable
// or the core schema isn't installed yet.

session_name('cyberblog_install');
if (session_status() === PHP_SESSION_NONE) session_start();

$rootDir = dirname(__DIR__);
$configLocalPath = $rootDir . '/config.local.php';

function e($s) { return htmlspecialchars((string)$s, ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8'); }

function try_connect(array $cfg) {
  $mysqli = mysqli_init();
  mysqli_report(MYSQLI_REPORT_OFF);
  $ok = @mysqli_real_connect($mysqli, $cfg['host'], $cfg['user'], $cfg['pass'], $cfg['name'], (int)($cfg['port'] ?: 3306), null, 0);
  if (!$ok) return [null, mysqli_connect_error()];
  $mysqli->set_charset('utf8mb4');
  return [$mysqli, null];
}

function schema_installed(mysqli $mysqli): bool {
  $res = @$mysqli->query("SHOW TABLES LIKE 'cms_admin_users'");
  return $res && $res->num_rows > 0;
}

function has_admin_user(mysqli $mysqli): bool {
  $res = @$mysqli->query("SELECT COUNT(*) c FROM cms_admin_users");
  if (!$res) return false;
  return (int)$res->fetch_assoc()['c'] > 0;
}

// --- Guard: refuse to run against an already-installed site ---
if (file_exists($configLocalPath)) {
  $existing = require $configLocalPath;
  $dbCfg = $existing['db'] ?? [];
  if (!empty($dbCfg['name'])) {
    [$probe, $err] = try_connect($dbCfg);
    if ($probe && schema_installed($probe) && has_admin_user($probe)) {
      http_response_code(403);
      ?>
      <!doctype html><html><head><meta charset="utf-8"><title>Already installed</title>
      <style>body{background:#0a0a0a;color:#e5e5e5;font-family:system-ui,sans-serif;display:flex;min-height:100vh;align-items:center;justify-content:center;margin:0}
      .box{max-width:32rem;padding:2rem;text-align:center}
      a{color:#38bdf8}</style></head><body>
      <div class="box">
        <h1>CyberBlog is already installed</h1>
        <p>The setup wizard has already run on this database. If you need to reinstall, remove <code>config.local.php</code> first (or point at a fresh database).</p>
        <p><a href="../admin/login.php">Go to admin login</a> &middot; <a href="../public/index.php">Go to the blog</a></p>
      </div>
      </body></html>
      <?php
      exit;
    }
  }
}

$csrf = $_SESSION['install_csrf'] ?? ($_SESSION['install_csrf'] = bin2hex(random_bytes(16)));
$step = $_POST['step'] ?? $_GET['step'] ?? 'welcome';
$error = '';

function check_csrf(string $t): bool {
  return !empty($_SESSION['install_csrf']) && hash_equals($_SESSION['install_csrf'], $t);
}

if ($step === 'database' && $_SERVER['REQUEST_METHOD'] === 'POST') {
  if (!check_csrf($_POST['csrf'] ?? '')) {
    $error = 'Security token expired, please try again.';
    $step = 'database';
  } else {
    $dbCfg = [
      'host' => trim($_POST['host'] ?? 'localhost'),
      'name' => trim($_POST['name'] ?? ''),
      'user' => trim($_POST['user'] ?? ''),
      'pass' => (string)($_POST['pass'] ?? ''),
      'port' => (int)($_POST['port'] ?? 3306) ?: 3306,
    ];
    if ($dbCfg['name'] === '' || $dbCfg['user'] === '') {
      $error = 'Database name and username are required.';
      $step = 'database';
    } else {
      [$mysqli, $connErr] = try_connect($dbCfg);
      if (!$mysqli) {
        $error = 'Could not connect: ' . e($connErr);
        $step = 'database';
      } else {
        // Run schema
        $sql = file_get_contents(__DIR__ . '/schema.sql');
        $sql = preg_replace('/^--.*$/m', '', $sql); // strip full-line comments before splitting
        $statements = array_filter(array_map('trim', explode(';', $sql)));
        $schemaError = '';
        foreach ($statements as $stmt) {
          if ($stmt === '') continue;
          if (!$mysqli->query($stmt)) {
            $schemaError = $mysqli->error;
            break;
          }
        }
        if ($schemaError !== '') {
          $error = 'Schema install failed: ' . e($schemaError);
          $step = 'database';
        } else {
          // Write config.local.php
          $existingLocal = file_exists($configLocalPath) ? (require $configLocalPath) : [];
          $newLocal = array_replace_recursive($existingLocal, ['db' => $dbCfg]);
          $php = "<?php\n// Real credentials for this environment. NEVER commit this file.\nreturn " . var_export($newLocal, true) . ";\n";
          if (@file_put_contents($configLocalPath, $php) === false) {
            $error = 'Database connected and schema installed, but config.local.php could not be written. Check folder permissions for: ' . e($rootDir);
            $step = 'database';
          } else {
            $_SESSION['install_db_ready'] = true;
            $step = 'admin';
          }
        }
      }
    }
  }
}

if ($step === 'admin' && $_SERVER['REQUEST_METHOD'] === 'POST') {
  if (!check_csrf($_POST['csrf'] ?? '')) {
    $error = 'Security token expired, please try again.';
  } elseif (empty($_SESSION['install_db_ready']) || !file_exists($configLocalPath)) {
    $error = 'Database step not completed yet.';
    $step = 'database';
  } else {
    $username = trim($_POST['username'] ?? '');
    $password = (string)($_POST['password'] ?? '');
    $displayName = trim($_POST['display_name'] ?? '');
    $siteName = trim($_POST['site_name'] ?? '') ?: 'My Blog';
    $siteTagline = trim($_POST['site_tagline'] ?? '');
    if ($username === '' || strlen($password) < 8) {
      $error = 'Username is required and password must be at least 8 characters.';
    } else {
      $cfg = require $rootDir . '/config.php';
      [$mysqli, $connErr] = try_connect($cfg['db']);
      if (!$mysqli) {
        $error = 'Could not reconnect to the database: ' . e($connErr);
      } else {
        $hash = password_hash($password, PASSWORD_DEFAULT);
        $roleName = 'super_editor';
        $stmt = $mysqli->prepare("INSERT INTO cms_admin_users (username, password_hash, role, display_name) VALUES (?,?,?,?)");
        $stmt->bind_param('ssss', $username, $hash, $roleName, $displayName);
        if ($stmt->execute()) {
          $stmt->close();

          require_once $rootDir . '/includes/migrate.php';
          cyberblog_migrate($mysqli);
          if (empty($siteTagline)) {
            $siteTagline = 'Welcome to ' . $siteName . ' - fresh posts, guides and reviews.';
          }
          $footerText = '© {year} ' . $siteName . '. All rights reserved.';
          $aboutHtml = '<p class="text-lg">Welcome to ' . htmlspecialchars($siteName, ENT_QUOTES) . '.</p>'
            . '<h2 class="text-2xl font-semibold mt-8">Our Mission</h2><p>Tell your readers what this blog is about.</p>'
            . '<h2 class="text-2xl font-semibold mt-8">Editorial Standards</h2><p>Describe how content on this blog is researched and written.</p>'
            . '<h2 class="text-2xl font-semibold mt-8">Topics We Cover</h2><ul class="list-disc list-inside space-y-2 text-neutral-300"><li>Add a topic</li><li>Add another topic</li></ul>';
          $upd = $mysqli->prepare("UPDATE cms_settings SET site_name=?, site_tagline=?, footer_text=?, about_content_html=? WHERE id=1");
          $upd->bind_param('ssss', $siteName, $siteTagline, $footerText, $aboutHtml);
          $upd->execute();
          $upd->close();

          unset($_SESSION['install_db_ready'], $_SESSION['install_csrf']);
          header('Location: ' . '../admin/login.php?installed=1');
          exit;
        } else {
          $error = 'Could not create admin user: ' . e($stmt->error);
        }
      }
    }
  }
}

$dbCfgPrefill = ['host' => 'localhost', 'name' => '', 'user' => '', 'port' => 3306];
?>
<!doctype html>
<html lang="en">
<head>
<meta charset="utf-8">
<meta name="viewport" content="width=device-width, initial-scale=1">
<title>CyberBlog v2.0 Setup</title>
<style>
  :root{color-scheme:dark;}
  *{box-sizing:border-box}
  body{background:#0a0a0a;color:#e5e5e5;font-family:system-ui,-apple-system,Segoe UI,Roboto,sans-serif;margin:0;padding:2.5rem 1rem}
  .wrap{max-width:38rem;margin:0 auto}
  h1{font-size:1.75rem;font-weight:800;margin-bottom:.25rem}
  .sub{color:#a3a3a3;margin-bottom:2rem}
  .card{background:#171717;border:1px solid #262626;border-radius:.75rem;padding:1.75rem}
  .steps{display:flex;gap:.5rem;margin-bottom:2rem;font-size:.8rem;color:#737373}
  .steps span.active{color:#38bdf8;font-weight:600}
  label{display:block;font-size:.85rem;font-weight:600;margin:.9rem 0 .3rem}
  input{width:100%;background:#0a0a0a;border:1px solid #262626;border-radius:.4rem;padding:.55rem .7rem;color:#e5e5e5;font-size:.9rem}
  input:focus{outline:2px solid #38bdf8}
  button{margin-top:1.5rem;background:#0ea5e9;color:#fff;border:0;border-radius:.4rem;padding:.65rem 1.4rem;font-size:.9rem;font-weight:600;cursor:pointer}
  button:hover{background:#0284c7}
  .error{background:rgba(190,18,60,.15);border:1px solid #9f1239;color:#fda4af;padding:.75rem 1rem;border-radius:.5rem;margin-bottom:1.25rem;font-size:.9rem}
  .ok{color:#4ade80}
  ul.checks{list-style:none;padding:0;margin:0 0 1.5rem}
  ul.checks li{padding:.35rem 0;font-size:.9rem;display:flex;gap:.5rem;align-items:center}
  .hint{color:#737373;font-size:.8rem;margin-top:.3rem}
  a{color:#38bdf8}
</style>
</head>
<body>
<div class="wrap">
  <h1>CyberBlog v2.0</h1>
  <p class="sub">First-run setup</p>

  <?php if ($error): ?><div class="error"><?php echo $error; ?></div><?php endif; ?>

  <div class="card">
    <?php if ($step === 'welcome'): ?>
      <div class="steps"><span class="active">1. Requirements</span><span>2. Database</span><span>3. Admin account</span></div>
      <?php
        $checks = [
          'PHP 7.4+' => version_compare(PHP_VERSION, '7.4.0', '>='),
          'mysqli extension' => extension_loaded('mysqli'),
          'Project folder is writable (to save config.local.php)' => is_writable($rootDir),
        ];
        $allOk = !in_array(false, $checks, true);
      ?>
      <ul class="checks">
        <?php foreach ($checks as $label => $ok): ?>
          <li><span class="<?php echo $ok?'ok':'error'; ?>"><?php echo $ok?'✓':'✗'; ?></span> <?php echo e($label); ?></li>
        <?php endforeach; ?>
      </ul>
      <?php if ($allOk): ?>
        <a href="?step=database"><button type="button" onclick="window.location='?step=database'">Continue</button></a>
      <?php else: ?>
        <p class="error">Please fix the failing requirement(s) above, then reload this page.</p>
      <?php endif; ?>

    <?php elseif ($step === 'database'): ?>
      <div class="steps"><span>1. Requirements</span><span class="active">2. Database</span><span>3. Admin account</span></div>
      <p class="hint">Enter the MySQL database CyberBlog should use. Tables will be created automatically if they don't already exist.</p>
      <form method="POST" action="?step=database" autocomplete="off">
        <input type="hidden" name="step" value="database">
        <input type="hidden" name="csrf" value="<?php echo e($csrf); ?>">
        <label>Database host</label>
        <input name="host" autocomplete="off" value="<?php echo e($_POST['host'] ?? $dbCfgPrefill['host']); ?>" required>
        <label>Database name</label>
        <input name="name" autocomplete="off" value="<?php echo e($_POST['name'] ?? ''); ?>" required>
        <label>Database username</label>
        <input name="user" autocomplete="off" value="<?php echo e($_POST['user'] ?? ''); ?>" required>
        <label>Database password</label>
        <input type="password" name="pass" autocomplete="new-password" value="">
        <label>Port</label>
        <input name="port" autocomplete="off" value="<?php echo e($_POST['port'] ?? $dbCfgPrefill['port']); ?>">
        <button type="submit">Test connection &amp; install schema</button>
      </form>

    <?php elseif ($step === 'admin'): ?>
      <div class="steps"><span>1. Requirements</span><span>2. Database</span><span class="active">3. Admin account</span></div>
      <p class="hint">Database connected and schema installed. Now create your first admin (Super Editor) account.</p>
      <form method="POST" action="?step=admin" autocomplete="off">
        <input type="hidden" name="step" value="admin">
        <input type="hidden" name="csrf" value="<?php echo e($csrf); ?>">
        <label>Blog name</label>
        <input name="site_name" autocomplete="off" placeholder="e.g. My Blog" required>
        <label>Tagline <span style="color:#737373;font-weight:400">(optional, shown on the homepage)</span></label>
        <input name="site_tagline" autocomplete="off" placeholder="A short description of your blog">
        <label>Username</label>
        <input name="username" autocomplete="off" required>
        <label>Display name</label>
        <input name="display_name" autocomplete="off" placeholder="Optional, shown publicly">
        <label>Password (min 8 characters)</label>
        <input type="password" name="password" autocomplete="new-password" minlength="8" required>
        <button type="submit">Create admin &amp; finish</button>
      </form>
    <?php endif; ?>
  </div>
</div>
</body>
</html>
