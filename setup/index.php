<?php
require __DIR__ . '/../includes/helpers.php';

// Simple step router
$step = isset($_GET['step']) ? (int)$_GET['step'] : 1;
$pageTitle = 'Setup Wizard';
$metaDescription = 'Install and configure Cyberrose Blog';
include __DIR__ . '/../includes/setup_header.php';

function env_check(): array {
  $errors = [];
  $warnings = [];
  if (version_compare(PHP_VERSION, '7.4.0', '<')) $errors[] = 'PHP 7.4+ required.';
  foreach (['mysqli','json','mbstring','openssl'] as $ext) {
    if (!extension_loaded($ext)) $errors[] = "Missing PHP extension: $ext";
  }
  $configPath = __DIR__ . '/../config.php';
  if (file_exists($configPath) && !is_writable($configPath)) $warnings[] = 'config.php exists and is not writable; installer may fail to update it.';
  if (!file_exists($configPath)) {
    // Verify directory is writable
    if (!is_writable(dirname($configPath))) $errors[] = 'Project directory not writable to create config.php.';
  }
  return [$errors,$warnings];
}

function try_db_connect($host,$user,$pass,$name,$port): array {
  $mysqli = mysqli_init();
  @mysqli_real_connect($mysqli,$host,$user,$pass,$name,(int)$port,null,0);
  if ($mysqli->connect_errno) return [false, 'Connect error: '.$mysqli->connect_error];
  $mysqli->set_charset('utf8mb4');
  return [true,$mysqli];
}

function write_config($site_name,$base_url,$db,$oauth): bool {
  $sec_session = 'cr_blog2_sess';
  $csrf_key = bin2hex(random_bytes(16));
  $tpl = <<<'PHP'
<?php
return [
  'site_name' => '__SITE_NAME__',
  'base_url' => '__BASE_URL__',
  'db' => [
    'host' => '__DB_HOST__',
    'name' => '__DB_NAME__',
    'user' => '__DB_USER__',
    'pass' => '__DB_PASS__',
    'port' => __DB_PORT__,
  ],
  'security' => [
    'session_name' => '__SESSION_NAME__',
    'csrf_key' => '__CSRF_KEY__',
  ],
  'oauth' => [
    'client_id' => '__OAUTH_CLIENT_ID__',
    'client_secret' => '__OAUTH_CLIENT_SECRET__',
    'auth_uri' => 'https://accounts.google.com/o/oauth2/auth',
    'token_uri' => 'https://oauth2.googleapis.com/token',
    'redirect_uri' => '__OAUTH_REDIRECT__',
  ],
];
PHP;
  $repl = [
    '__SITE_NAME__' => addslashes($site_name),
    '__BASE_URL__' => addslashes($base_url),
    '__DB_HOST__' => addslashes($db['host']),
    '__DB_NAME__' => addslashes($db['name']),
    '__DB_USER__' => addslashes($db['user']),
    '__DB_PASS__' => addslashes($db['pass']),
    '__DB_PORT__' => (int)$db['port'],
    '__SESSION_NAME__' => addslashes($sec_session),
    '__CSRF_KEY__' => addslashes($csrf_key),
    '__OAUTH_CLIENT_ID__' => addslashes($oauth['client_id'] ?? ''),
    '__OAUTH_CLIENT_SECRET__' => addslashes($oauth['client_secret'] ?? ''),
    '__OAUTH_REDIRECT__' => addslashes($oauth['redirect_uri'] ?? ''),
  ];
  $out = $tpl;
  foreach ($repl as $k=>$v) {
    $out = str_replace($k, (string)$v, $out);
  }
  return (bool)file_put_contents(__DIR__.'/../config.php', $out);
}

?>
<div class="space-y-6">
  <div class="bg-neutral-900 border border-neutral-800 rounded p-6">
    <h1 class="text-2xl font-bold">Setup Wizard</h1>
    <p class="text-neutral-300 mt-2">Follow the steps to configure your blog.</p>
  </div>

  <?php if ($step === 1): ?>
    <?php list($errs,$warns) = env_check(); ?>
    <div class="bg-neutral-900 border border-neutral-800 rounded p-6">
      <h2 class="text-xl font-semibold">Environment Checks</h2>
      <ul class="mt-3 space-y-2">
        <li>PHP Version: <span class="text-neutral-300"><?php echo e(PHP_VERSION); ?></span></li>
        <li>Extensions: mysqli, json, mbstring, openssl</li>
        <li>Config writable: <?php echo is_writable(__DIR__.'/../') ? '<span class="text-green-400">yes</span>' : '<span class="text-rose-400">no</span>'; ?></li>
      </ul>
      <?php if (!empty($errs)): ?>
        <div class="mt-4 bg-rose-900/30 border border-rose-700 text-rose-400 px-4 py-3 rounded">
          <div class="font-semibold">Errors</div>
          <ul class="mt-2 list-disc list-inside">
            <?php foreach ($errs as $e): ?><li><?php echo e($e); ?></li><?php endforeach; ?>
          </ul>
        </div>
      <?php endif; ?>
      <?php if (!empty($warns)): ?>
        <div class="mt-4 bg-amber-900/30 border border-amber-700 text-amber-400 px-4 py-3 rounded">
          <div class="font-semibold">Warnings</div>
          <ul class="mt-2 list-disc list-inside">
            <?php foreach ($warns as $w): ?><li><?php echo e($w); ?></li><?php endforeach; ?>
          </ul>
        </div>
      <?php endif; ?>
      <div class="mt-6">
        <a href="<?php echo base_url('setup/index.php?step=2'); ?>" class="bg-sky-500 hover:bg-sky-600 text-white px-6 py-2 rounded">Continue</a>
      </div>
    </div>
  <?php elseif ($step === 2): ?>
    <?php
      $error = '';
      $ok = false;
      if ($_SERVER['REQUEST_METHOD'] === 'POST') {
        $site_name = trim($_POST['site_name'] ?? 'Cyberrose Blog');
        $base_url = trim($_POST['base_url'] ?? '');
        $db_host = trim($_POST['db_host'] ?? 'localhost');
        $db_name = trim($_POST['db_name'] ?? 'blog');
        $db_user = trim($_POST['db_user'] ?? 'root');
        $db_pass = (string)($_POST['db_pass'] ?? '');
        $db_port = (int)($_POST['db_port'] ?? 3306);
        list($connOk,$conn) = try_db_connect($db_host,$db_user,$db_pass,$db_name,$db_port);
        if (!$connOk) { $error = $conn; }
        else {
          $ok = true;
          $oauth = [
            'client_id' => trim($_POST['oauth_client_id'] ?? ''),
            'client_secret' => trim($_POST['oauth_client_secret'] ?? ''),
            'redirect_uri' => trim($_POST['oauth_redirect'] ?? ''),
          ];
          $saved = write_config($site_name,$base_url,[
            'host'=>$db_host,'name'=>$db_name,'user'=>$db_user,'pass'=>$db_pass,'port'=>$db_port
          ],$oauth);
          if (!$saved) { $error = 'Failed to write config.php'; $ok=false; }
        }
        if ($ok) {
          header('Location: '.base_url('setup/index.php?step=3'));
          exit;
        }
      }
    ?>
    <form method="POST" class="bg-neutral-900 border border-neutral-800 rounded p-6 grid gap-4">
      <h2 class="text-xl font-semibold">Site & Database</h2>
      <?php if (!empty($error)): ?><div class="bg-rose-900/30 border border-rose-700 text-rose-400 px-4 py-3 rounded"><?php echo e($error); ?></div><?php endif; ?>
      <div>
        <label class="block text-sm mb-1">Site Name</label>
        <input name="site_name" value="Cyberrose Blog" class="w-full rounded-md bg-neutral-950 border border-neutral-800 px-3 py-2" />
      </div>
      <div>
        <label class="block text-sm mb-1">Base URL (optional)</label>
        <input name="base_url" placeholder="e.g., /blog" class="w-full rounded-md bg-neutral-950 border border-neutral-800 px-3 py-2" />
      </div>
      <div class="grid md:grid-cols-2 gap-4">
        <div>
          <label class="block text-sm mb-1">DB Host</label>
          <input name="db_host" value="localhost" class="w-full rounded-md bg-neutral-950 border border-neutral-800 px-3 py-2" />
        </div>
        <div>
          <label class="block text-sm mb-1">DB Name</label>
          <input name="db_name" value="cyberblog" class="w-full rounded-md bg-neutral-950 border border-neutral-800 px-3 py-2" />
        </div>
        <div>
          <label class="block text-sm mb-1">DB User</label>
          <input name="db_user" value="root" class="w-full rounded-md bg-neutral-950 border border-neutral-800 px-3 py-2" />
        </div>
        <div>
          <label class="block text-sm mb-1">DB Password</label>
          <input type="password" name="db_pass" class="w-full rounded-md bg-neutral-950 border border-neutral-800 px-3 py-2" />
        </div>
        <div>
          <label class="block text-sm mb-1">DB Port</label>
          <input name="db_port" value="3306" class="w-full rounded-md bg-neutral-950 border border-neutral-800 px-3 py-2" />
        </div>
      </div>
      <h3 class="text-lg font-semibold mt-4">Google OAuth (optional)</h3>
      <div class="grid md:grid-cols-3 gap-4">
        <div>
          <label class="block text-sm mb-1">Client ID</label>
          <input name="oauth_client_id" class="w-full rounded-md bg-neutral-950 border border-neutral-800 px-3 py-2" />
        </div>
        <div>
          <label class="block text-sm mb-1">Client Secret</label>
          <input name="oauth_client_secret" class="w-full rounded-md bg-neutral-950 border border-neutral-800 px-3 py-2" />
        </div>
        <div>
          <label class="block text-sm mb-1">Redirect URI</label>
          <input name="oauth_redirect" placeholder="https://yourdomain/comments/google_callback.php" class="w-full rounded-md bg-neutral-950 border border-neutral-800 px-3 py-2" />
        </div>
      </div>
      <div class="mt-4"><button class="bg-sky-500 hover:bg-sky-600 text-white px-6 py-2 rounded">Save & Continue</button></div>
    </form>
  <?php elseif ($step === 3): ?>
    <?php
      $cfg = require __DIR__ . '/../config.php';
      list($ok,$conn) = try_db_connect($cfg['db']['host'],$cfg['db']['user'],$cfg['db']['pass'],$cfg['db']['name'],$cfg['db']['port']);
      $error = '';
      $done = false;
      if (!$ok) { $error = $conn; }
      else {
        $sql = file_get_contents(__DIR__.'/schema.sql');
        if ($sql === false) $error = 'Missing schema.sql';
        else {
          $conn->multi_query($sql);
          while ($conn->more_results() && $conn->next_result()) { /* flush results */ }
          $done = true;
        }
      }
      if ($done) {
        echo '<div class="bg-green-900/30 border border-green-700 text-green-400 px-4 py-3 rounded">Database initialized successfully.</div>';
      } else {
        echo '<div class="bg-rose-900/30 border border-rose-700 text-rose-400 px-4 py-3 rounded">'.e($error).'</div>';
      }
    ?>
    <div class="mt-6">
      <a href="<?php echo base_url('setup/index.php?step=4'); ?>" class="bg-sky-500 hover:bg-sky-600 text-white px-6 py-2 rounded">Continue</a>
    </div>
  <?php elseif ($step === 4): ?>
    <?php
      $msg = '';
      $error = '';
      if ($_SERVER['REQUEST_METHOD'] === 'POST') {
        $u = trim($_POST['username'] ?? 'admin');
        $p = (string)($_POST['password'] ?? '');
        $role = 'super_editor';
        if (!$u || !$p) { $error = 'Username and password required.'; }
        else {
          $cfg = require __DIR__ . '/../config.php';
          list($ok,$conn) = try_db_connect($cfg['db']['host'],$cfg['db']['user'],$cfg['db']['pass'],$cfg['db']['name'],$cfg['db']['port']);
          if (!$ok) { $error = is_string($conn)?$conn:'DB error'; }
          else {
            $hash = password_hash($p, PASSWORD_DEFAULT);
            $stmt = $conn->prepare("INSERT INTO cms_admin_users (username, password_hash, role) VALUES (?,?,?)");
            $stmt->bind_param('sss',$u,$hash,$role);
            if ($stmt->execute()) { $msg = 'Admin user created.'; }
            else { $error = 'Failed to create admin user (maybe exists).'; }
            $stmt->close();
          }
        }
      }
    ?>
    <form method="POST" class="bg-neutral-900 border border-neutral-800 rounded p-6 grid gap-4 max-w-md">
      <h2 class="text-xl font-semibold">Create Admin User</h2>
      <?php if (!empty($msg)): ?><div class="bg-green-900/30 border border-green-700 text-green-400 px-4 py-3 rounded"><?php echo e($msg); ?></div><?php endif; ?>
      <?php if (!empty($error)): ?><div class="bg-rose-900/30 border border-rose-700 text-rose-400 px-4 py-3 rounded"><?php echo e($error); ?></div><?php endif; ?>
      <div>
        <label class="block text-sm mb-1">Username</label>
        <input name="username" value="admin" class="w-full rounded-md bg-neutral-950 border border-neutral-800 px-3 py-2" />
      </div>
      <div>
        <label class="block text-sm mb-1">Password</label>
        <input type="password" name="password" class="w-full rounded-md bg-neutral-950 border border-neutral-800 px-3 py-2" />
      </div>
      <div class="mt-2"><button class="bg-sky-500 hover:bg-sky-600 text-white px-6 py-2 rounded">Create Admin</button></div>
    </form>
    <div class="mt-6">
      <a href="<?php echo base_url('setup/index.php?step=5'); ?>" class="bg-sky-500 hover:bg-sky-600 text-white px-6 py-2 rounded">Finish</a>
    </div>
  <?php elseif ($step === 5): ?>
    <div class="bg-neutral-900 border border-neutral-800 rounded p-6">
      <h2 class="text-xl font-semibold">All Set!</h2>
      <p class="text-neutral-300 mt-2">Your blog is configured. You can now log in to the admin dashboard or visit the homepage.</p>
      <div class="mt-4 flex gap-3">
        <a class="bg-neutral-800 hover:bg-neutral-700 text-white px-4 py-2 rounded" href="<?php echo base_url('admin/login.php'); ?>">Admin Login</a>
        <a class="bg-sky-500 hover:bg-sky-600 text-white px-4 py-2 rounded" href="<?php echo base_url('public/index.php'); ?>">Go to Blog</a>
      </div>
    </div>
  <?php endif; ?>
</div>

<?php include __DIR__ . '/../includes/setup_footer.php'; ?>