<?php
require __DIR__ . '/../includes/db.php';
require __DIR__ . '/../includes/helpers.php';
require __DIR__ . '/../includes/csrf.php';
function require_admin(){ if(empty($_SESSION['admin'])){ header('Location: '.base_url('admin/login.php')); exit; } }
require_admin();

$users = [];
if ($res = $mysqli->query("SELECT * FROM cms_oauth_users ORDER BY last_login_at DESC LIMIT 200")) {
  while ($row = $res->fetch_assoc()) $users[] = $row;
}

$pageTitle = 'User Management';
include __DIR__ . '/../includes/template_header.php';
include __DIR__ . '/../includes/admin_nav.php';
?>
<div class="flex items-center justify-between">
  <h1 class="text-2xl font-bold">User Management</h1>
</div>

<div class="mt-6 bg-neutral-900 border border-neutral-800 rounded-lg overflow-hidden">
  <table class="w-full">
    <thead class="bg-neutral-800">
      <tr>
        <th class="px-4 py-3 text-left text-sm font-semibold">User</th>
        <th class="px-4 py-3 text-left text-sm font-semibold">Email</th>
        <th class="px-4 py-3 text-left text-sm font-semibold">Provider</th>
        <th class="px-4 py-3 text-left text-sm font-semibold">Joined</th>
        <th class="px-4 py-3 text-left text-sm font-semibold">Last Login</th>
      </tr>
    </thead>
    <tbody class="divide-y divide-neutral-800">
      <?php foreach ($users as $u): ?>
        <tr>
          <td class="px-4 py-3 text-sm">
            <div class="flex items-center gap-2">
              <?php if (!empty($u['picture'])): ?>
                <img src="<?php echo e($u['picture']); ?>" alt="" class="w-8 h-8 rounded-full">
              <?php endif; ?>
              <span><?php echo e($u['name'] ?: 'User'); ?></span>
            </div>
          </td>
          <td class="px-4 py-3 text-sm text-neutral-400"><?php echo e($u['email'] ?: '-'); ?></td>
          <td class="px-4 py-3 text-sm">
            <span class="px-2 py-0.5 rounded text-xs bg-neutral-700 text-neutral-300">
              <?php echo e($u['provider']); ?>
            </span>
          </td>
          <td class="px-4 py-3 text-sm text-neutral-400"><?php echo e($u['created_at']); ?></td>
          <td class="px-4 py-3 text-sm text-neutral-400"><?php echo e($u['last_login_at'] ?: 'Never'); ?></td>
        </tr>
      <?php endforeach; ?>
    </tbody>
  </table>
  <?php if (empty($users)): ?>
    <div class="text-neutral-400 text-center py-8">No users yet.</div>
  <?php endif; ?>
</div>

<div class="mt-6 bg-neutral-900 border border-neutral-800 rounded-lg p-4">
  <h3 class="font-semibold mb-2">User Statistics</h3>
  <div class="grid md:grid-cols-3 gap-4 mt-4">
    <div class="bg-neutral-800 rounded p-3">
      <div class="text-neutral-400 text-sm">Total Users</div>
      <div class="text-2xl font-bold"><?php echo count($users); ?></div>
    </div>
    <div class="bg-neutral-800 rounded p-3">
      <div class="text-neutral-400 text-sm">Google Users</div>
      <div class="text-2xl font-bold"><?php echo count(array_filter($users, fn($u) => $u['provider'] === 'google')); ?></div>
    </div>
    <div class="bg-neutral-800 rounded p-3">
      <div class="text-neutral-400 text-sm">Active This Month</div>
      <div class="text-2xl font-bold"><?php echo count(array_filter($users, fn($u) => strtotime($u['last_login_at'] ?? '1970-01-01') > strtotime('-30 days'))); ?></div>
    </div>
  </div>
</div>
<?php include __DIR__ . '/../includes/template_footer.php'; ?>
