<?php
require_once __DIR__ . '/../includes/auth.php';
require_admin();

$msg = '';
$error = '';
if ($_SERVER['REQUEST_METHOD'] === 'POST' && csrf_check($_POST['csrf'] ?? '')) {
  $currentPassword = (string)($_POST['current_password'] ?? '');
  $newPassword = (string)($_POST['new_password'] ?? '');
  $confirmPassword = (string)($_POST['confirm_password'] ?? '');
  $adminId = (int)($_SESSION['admin']['id'] ?? 0);

  $stmt = $mysqli->prepare("SELECT password_hash FROM cms_admin_users WHERE id=? LIMIT 1");
  $stmt->bind_param('i', $adminId);
  $stmt->execute();
  $row = $stmt->get_result()->fetch_assoc();
  $stmt->close();

  if (!$row || !password_verify($currentPassword, $row['password_hash'])) {
    $error = 'Current password is incorrect.';
  } elseif (strlen($newPassword) < 8) {
    $error = 'New password must be at least 8 characters.';
  } elseif ($newPassword !== $confirmPassword) {
    $error = 'New password and confirmation do not match.';
  } else {
    $hash = password_hash($newPassword, PASSWORD_DEFAULT);
    $stmt = $mysqli->prepare("UPDATE cms_admin_users SET password_hash=? WHERE id=?");
    $stmt->bind_param('si', $hash, $adminId);
    $stmt->execute();
    $stmt->close();
    $msg = 'Password updated successfully.';
  }
}

$pageTitle = 'Change Password';
include __DIR__ . '/../includes/template_header.php';
include __DIR__ . '/../includes/admin_nav.php';
?>
<h1 class="text-2xl font-bold">Change Password</h1>
<p class="text-neutral-400 text-sm mt-1">Update your own admin login password.</p>
<?php if($msg):?><div class="mt-4 bg-green-900/30 border border-green-700 text-green-400 px-4 py-3 rounded text-sm"><?php echo e($msg);?></div><?php endif; ?>
<?php if($error):?><div class="mt-4 bg-red-900/30 border border-red-700 text-red-400 px-4 py-3 rounded text-sm"><?php echo e($error);?></div><?php endif; ?>
<form method="POST" class="mt-6 max-w-sm grid gap-4">
  <div>
    <label class="block text-sm mb-1 font-semibold">Current Password</label>
    <input type="password" name="current_password" required autocomplete="current-password" class="w-full rounded-md bg-neutral-950 border border-neutral-800 px-3 py-2" />
  </div>
  <div>
    <label class="block text-sm mb-1 font-semibold">New Password</label>
    <input type="password" name="new_password" required minlength="8" autocomplete="new-password" class="w-full rounded-md bg-neutral-950 border border-neutral-800 px-3 py-2" />
    <div class="text-xs text-neutral-400 mt-1">At least 8 characters</div>
  </div>
  <div>
    <label class="block text-sm mb-1 font-semibold">Confirm New Password</label>
    <input type="password" name="confirm_password" required minlength="8" autocomplete="new-password" class="w-full rounded-md bg-neutral-950 border border-neutral-800 px-3 py-2" />
  </div>
  <input type="hidden" name="csrf" value="<?php echo e(csrf_token()); ?>" />
  <button class="bg-sky-500 hover:bg-sky-600 text-white px-4 py-2 rounded w-fit">Update Password</button>
</form>
<?php include __DIR__ . '/../includes/template_footer.php'; ?>
