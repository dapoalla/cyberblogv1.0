<?php
require __DIR__ . '/../includes/db.php';
require __DIR__ . '/../includes/helpers.php';
require __DIR__ . '/../includes/csrf.php';

$msg = '';
if ($_SERVER['REQUEST_METHOD'] === 'POST' && csrf_check($_POST['csrf'] ?? '')) {
  $u = trim($_POST['username'] ?? '');
  $p = (string)($_POST['password'] ?? '');
  $stmt = $mysqli->prepare("SELECT id, username, password_hash, role FROM cms_admin_users WHERE username=? LIMIT 1");
  $stmt->bind_param('s', $u);
  $stmt->execute();
  $row = $stmt->get_result()->fetch_assoc();
  $stmt->close();
  if ($row && password_verify($p, $row['password_hash'])) {
    $_SESSION['admin'] = ['id'=>$row['id'], 'username'=>$row['username'], 'role'=>$row['role'] ?? 'editor'];
    header('Location: ' . base_url('admin/index.php'));
    exit;
  } else {
    $msg = 'Invalid credentials';
  }
}

$pageTitle = 'Admin Login'; $metaDescription='';
include __DIR__ . '/../includes/template_header.php';
?>
<h1 class="text-2xl font-bold">Admin Login</h1>
<?php if ($msg): ?><div class="mt-3 text-rose-400 text-sm"><?php echo e($msg); ?></div><?php endif; ?>
<form method="POST" class="mt-6 max-w-sm grid gap-4">
  <div>
    <label class="block text-sm mb-1">Username</label>
    <input name="username" required class="w-full rounded-md bg-neutral-950 border border-neutral-800 px-3 py-2" />
  </div>
  <div>
    <label class="block text-sm mb-1">Password</label>
    <input type="password" name="password" required class="w-full rounded-md bg-neutral-950 border border-neutral-800 px-3 py-2" />
  </div>
  <input type="hidden" name="csrf" value="<?php echo e(csrf_token()); ?>" />
  <button class="bg-sky-500 hover:bg-sky-600 text-white px-4 py-2 rounded">Login</button>
</form>
<?php include __DIR__ . '/../includes/template_footer.php'; ?>
