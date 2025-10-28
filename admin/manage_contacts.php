<?php
require __DIR__ . '/../includes/db.php';
require __DIR__ . '/../includes/helpers.php';
require __DIR__ . '/../includes/csrf.php';
function require_admin(){ if(empty($_SESSION['admin'])){ header('Location: '.base_url('admin/login.php')); exit; } }
require_admin();

// Handle status update
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action'])) {
  if (csrf_check($_POST['csrf'] ?? '')) {
    $id = (int)($_POST['id'] ?? 0);
    if ($_POST['action'] === 'read') {
      $mysqli->query("UPDATE cms_contacts SET status='read' WHERE id=$id");
    } elseif ($_POST['action'] === 'archive') {
      $mysqli->query("UPDATE cms_contacts SET status='archived' WHERE id=$id");
    } elseif ($_POST['action'] === 'delete') {
      $mysqli->query("DELETE FROM cms_contacts WHERE id=$id");
    }
  }
  header('Location: ' . base_url('admin/manage_contacts.php'));
  exit;
}

$contacts = [];
if ($res = $mysqli->query("SELECT * FROM cms_contacts ORDER BY created_at DESC LIMIT 200")) {
  while ($row = $res->fetch_assoc()) $contacts[] = $row;
}

$pageTitle = 'Contact Messages';
include __DIR__ . '/../includes/template_header.php';
include __DIR__ . '/../includes/admin_nav.php';
?>
<div class="flex items-center justify-between">
  <h1 class="text-2xl font-bold">Contact Messages</h1>
</div>

<div class="mt-6 space-y-4">
  <?php foreach ($contacts as $c): ?>
    <div class="bg-neutral-900 border border-neutral-800 rounded-lg p-4">
      <div class="flex items-start justify-between">
        <div class="flex-1">
          <div class="flex items-center gap-3 text-sm">
            <span class="font-semibold"><?php echo e($c['name']); ?></span>
            <span class="text-neutral-400"><?php echo e($c['email']); ?></span>
            <span class="text-neutral-400">·</span>
            <span class="text-neutral-400"><?php echo e($c['created_at']); ?></span>
            <span class="px-2 py-0.5 rounded text-xs <?php echo $c['status']==='new'?'bg-blue-900 text-blue-400':($c['status']==='read'?'bg-green-900 text-green-400':'bg-neutral-700 text-neutral-400'); ?>">
              <?php echo e($c['status']); ?>
            </span>
          </div>
          <?php if (!empty($c['subject'])): ?>
            <div class="mt-2 font-semibold"><?php echo e($c['subject']); ?></div>
          <?php endif; ?>
          <div class="mt-2 text-neutral-300"><?php echo nl2br(e($c['message'])); ?></div>
        </div>
        <div class="flex gap-2 ml-4">
          <?php if ($c['status'] === 'new'): ?>
            <form method="POST" class="inline">
              <input type="hidden" name="csrf" value="<?php echo e(csrf_token()); ?>">
              <input type="hidden" name="id" value="<?php echo (int)$c['id']; ?>">
              <input type="hidden" name="action" value="read">
              <button class="text-xs bg-green-600 hover:bg-green-700 text-white px-3 py-1 rounded">Mark Read</button>
            </form>
          <?php endif; ?>
          <form method="POST" class="inline">
            <input type="hidden" name="csrf" value="<?php echo e(csrf_token()); ?>">
            <input type="hidden" name="id" value="<?php echo (int)$c['id']; ?>">
            <input type="hidden" name="action" value="archive">
            <button class="text-xs bg-neutral-600 hover:bg-neutral-700 text-white px-3 py-1 rounded">Archive</button>
          </form>
          <form method="POST" class="inline" onsubmit="return confirm('Delete this message?')">
            <input type="hidden" name="csrf" value="<?php echo e(csrf_token()); ?>">
            <input type="hidden" name="id" value="<?php echo (int)$c['id']; ?>">
            <input type="hidden" name="action" value="delete">
            <button class="text-xs bg-red-600 hover:bg-red-700 text-white px-3 py-1 rounded">Delete</button>
          </form>
        </div>
      </div>
    </div>
  <?php endforeach; ?>
  <?php if (empty($contacts)): ?>
    <div class="text-neutral-400 text-center py-8">No contact messages yet.</div>
  <?php endif; ?>
</div>
<?php include __DIR__ . '/../includes/template_footer.php'; ?>
