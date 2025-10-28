<?php
require __DIR__ . '/../includes/db.php';
require __DIR__ . '/../includes/helpers.php';
require __DIR__ . '/../includes/csrf.php';
function require_admin(){ if(empty($_SESSION['admin'])){ header('Location: '.base_url('admin/login.php')); exit; } }
require_admin();

// Handle unsubscribe
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action']) && $_POST['action'] === 'unsubscribe') {
  if (csrf_check($_POST['csrf'] ?? '')) {
    $id = (int)($_POST['id'] ?? 0);
    $mysqli->query("UPDATE cms_newsletter SET status='unsubscribed' WHERE id=$id");
  }
  header('Location: ' . base_url('admin/manage_newsletter.php'));
  exit;
}

$subscribers = [];
if ($res = $mysqli->query("SELECT * FROM cms_newsletter ORDER BY subscribed_at DESC LIMIT 500")) {
  while ($row = $res->fetch_assoc()) $subscribers[] = $row;
}

$pageTitle = 'Newsletter Subscribers';
include __DIR__ . '/../includes/template_header.php';
include __DIR__ . '/../includes/admin_nav.php';
?>
<div class="flex items-center justify-between">
  <h1 class="text-2xl font-bold">Newsletter Subscribers</h1>
</div>

<div class="mt-6 bg-neutral-900 border border-neutral-800 rounded-lg overflow-hidden">
  <table class="w-full">
    <thead class="bg-neutral-800">
      <tr>
        <th class="px-4 py-3 text-left text-sm font-semibold">Email</th>
        <th class="px-4 py-3 text-left text-sm font-semibold">Name</th>
        <th class="px-4 py-3 text-left text-sm font-semibold">Subscribed</th>
        <th class="px-4 py-3 text-left text-sm font-semibold">Status</th>
        <th class="px-4 py-3 text-left text-sm font-semibold">Actions</th>
      </tr>
    </thead>
    <tbody class="divide-y divide-neutral-800">
      <?php foreach ($subscribers as $sub): ?>
        <tr>
          <td class="px-4 py-3 text-sm"><?php echo e($sub['email']); ?></td>
          <td class="px-4 py-3 text-sm text-neutral-400"><?php echo e($sub['name'] ?: '-'); ?></td>
          <td class="px-4 py-3 text-sm text-neutral-400"><?php echo e($sub['subscribed_at']); ?></td>
          <td class="px-4 py-3 text-sm">
            <span class="px-2 py-0.5 rounded text-xs <?php echo $sub['status']==='active'?'bg-green-900 text-green-400':'bg-neutral-700 text-neutral-400'; ?>">
              <?php echo e($sub['status']); ?>
            </span>
          </td>
          <td class="px-4 py-3 text-sm">
            <?php if ($sub['status'] === 'active'): ?>
              <form method="POST" class="inline" onsubmit="return confirm('Unsubscribe this user?')">
                <input type="hidden" name="csrf" value="<?php echo e(csrf_token()); ?>">
                <input type="hidden" name="id" value="<?php echo (int)$sub['id']; ?>">
                <input type="hidden" name="action" value="unsubscribe">
                <button class="text-xs text-red-400 hover:underline">Unsubscribe</button>
              </form>
            <?php endif; ?>
          </td>
        </tr>
      <?php endforeach; ?>
    </tbody>
  </table>
  <?php if (empty($subscribers)): ?>
    <div class="text-neutral-400 text-center py-8">No subscribers yet.</div>
  <?php endif; ?>
</div>

<div class="mt-6 bg-neutral-900 border border-neutral-800 rounded-lg p-4">
  <h3 class="font-semibold mb-2">Export Subscribers</h3>
  <p class="text-sm text-neutral-400 mb-3">Download active subscribers as CSV for email campaigns</p>
  <a href="?export=csv" class="inline-block bg-sky-500 hover:bg-sky-600 text-white px-4 py-2 rounded text-sm">Export as CSV</a>
</div>

<?php
// Handle CSV export
if (isset($_GET['export']) && $_GET['export'] === 'csv') {
  header('Content-Type: text/csv');
  header('Content-Disposition: attachment; filename="newsletter_subscribers_'.date('Y-m-d').'.csv"');
  $output = fopen('php://output', 'w');
  fputcsv($output, ['Email', 'Name', 'Subscribed At', 'Status']);
  foreach ($subscribers as $sub) {
    if ($sub['status'] === 'active') {
      fputcsv($output, [$sub['email'], $sub['name'], $sub['subscribed_at'], $sub['status']]);
    }
  }
  fclose($output);
  exit;
}
?>
<?php include __DIR__ . '/../includes/template_footer.php'; ?>
