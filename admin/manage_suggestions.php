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
    $action = $_POST['action'];
    
    if ($action === 'approve') {
      $mysqli->query("UPDATE cms_user_suggestions SET status='approved', reviewed_at=NOW() WHERE id=$id");
    } elseif ($action === 'reject') {
      $mysqli->query("UPDATE cms_user_suggestions SET status='rejected', reviewed_at=NOW() WHERE id=$id");
    } elseif ($action === 'reviewed') {
      $mysqli->query("UPDATE cms_user_suggestions SET status='reviewed', reviewed_at=NOW() WHERE id=$id");
    } elseif ($action === 'delete') {
      $mysqli->query("DELETE FROM cms_user_suggestions WHERE id=$id");
    } elseif ($action === 'update_notes') {
      $notes = trim($_POST['admin_notes'] ?? '');
      $stmt = $mysqli->prepare("UPDATE cms_user_suggestions SET admin_notes=? WHERE id=?");
      $stmt->bind_param('si', $notes, $id);
      $stmt->execute();
      $stmt->close();
    }
  }
  header('Location: ' . base_url('admin/manage_suggestions.php'));
  exit;
}

$suggestions = [];
if ($res = $mysqli->query("SELECT s.*, u.name, u.email, u.picture 
                           FROM cms_user_suggestions s 
                           JOIN cms_oauth_users u ON u.id = s.oauth_user_id 
                           ORDER BY s.created_at DESC LIMIT 200")) {
  while ($row = $res->fetch_assoc()) $suggestions[] = $row;
}

$pageTitle = 'User Suggestions';
include __DIR__ . '/../includes/template_header.php';
include __DIR__ . '/../includes/admin_nav.php';
?>
<div class="flex items-center justify-between">
  <h1 class="text-2xl font-bold">User Suggestions</h1>
</div>

<div class="mt-6 space-y-4">
  <?php foreach ($suggestions as $s): ?>
    <div class="bg-neutral-900 border border-neutral-800 rounded-lg p-4">
      <div class="flex items-start justify-between">
        <div class="flex-1">
          <div class="flex items-center gap-3 text-sm mb-2">
            <?php if (!empty($s['picture'])): ?>
              <img src="<?php echo e($s['picture']); ?>" alt="" class="w-8 h-8 rounded-full">
            <?php endif; ?>
            <span class="font-semibold"><?php echo e($s['name'] ?: 'User'); ?></span>
            <span class="text-neutral-400"><?php echo e($s['email'] ?: ''); ?></span>
            <span class="text-neutral-400">·</span>
            <span class="text-neutral-400"><?php echo e($s['created_at']); ?></span>
            <span class="px-2 py-0.5 rounded text-xs bg-neutral-700 text-neutral-300">
              <?php echo e(ucfirst($s['suggestion_type'])); ?>
            </span>
            <span class="px-2 py-0.5 rounded text-xs <?php echo $s['status']==='approved'?'bg-green-900 text-green-400':($s['status']==='rejected'?'bg-red-900 text-red-400':($s['status']==='reviewed'?'bg-blue-900 text-blue-400':'bg-yellow-900 text-yellow-400')); ?>">
              <?php echo e(ucfirst($s['status'])); ?>
            </span>
          </div>
          <h3 class="text-lg font-semibold"><?php echo e($s['title']); ?></h3>
          <div class="mt-2 text-neutral-300"><?php echo nl2br(e($s['description'])); ?></div>
          
          <?php if (!empty($s['admin_notes'])): ?>
            <div class="mt-3 p-3 bg-neutral-800 rounded text-sm">
              <div class="text-neutral-400 text-xs mb-1">Admin Notes:</div>
              <div><?php echo nl2br(e($s['admin_notes'])); ?></div>
            </div>
          <?php endif; ?>
          
          <details class="mt-3">
            <summary class="text-sm text-sky-400 cursor-pointer hover:underline">Add/Edit Admin Notes</summary>
            <form method="POST" class="mt-2">
              <textarea name="admin_notes" rows="3" class="w-full rounded bg-neutral-950 border border-neutral-800 px-3 py-2 text-sm"><?php echo e($s['admin_notes'] ?? ''); ?></textarea>
              <input type="hidden" name="csrf" value="<?php echo e(csrf_token()); ?>">
              <input type="hidden" name="id" value="<?php echo (int)$s['id']; ?>">
              <input type="hidden" name="action" value="update_notes">
              <button class="mt-2 text-xs bg-neutral-700 hover:bg-neutral-600 text-white px-3 py-1 rounded">Save Notes</button>
            </form>
          </details>
        </div>
        
        <div class="flex flex-col gap-2 ml-4">
          <?php if ($s['status'] !== 'approved'): ?>
            <form method="POST" class="inline">
              <input type="hidden" name="csrf" value="<?php echo e(csrf_token()); ?>">
              <input type="hidden" name="id" value="<?php echo (int)$s['id']; ?>">
              <input type="hidden" name="action" value="approve">
              <button class="text-xs bg-green-600 hover:bg-green-700 text-white px-3 py-1 rounded w-full">Approve</button>
            </form>
          <?php endif; ?>
          
          <?php if ($s['status'] === 'pending'): ?>
            <form method="POST" class="inline">
              <input type="hidden" name="csrf" value="<?php echo e(csrf_token()); ?>">
              <input type="hidden" name="id" value="<?php echo (int)$s['id']; ?>">
              <input type="hidden" name="action" value="reviewed">
              <button class="text-xs bg-blue-600 hover:bg-blue-700 text-white px-3 py-1 rounded w-full">Mark Reviewed</button>
            </form>
          <?php endif; ?>
          
          <form method="POST" class="inline">
            <input type="hidden" name="csrf" value="<?php echo e(csrf_token()); ?>">
            <input type="hidden" name="id" value="<?php echo (int)$s['id']; ?>">
            <input type="hidden" name="action" value="reject">
            <button class="text-xs bg-yellow-600 hover:bg-yellow-700 text-white px-3 py-1 rounded w-full">Reject</button>
          </form>
          
          <form method="POST" class="inline" onsubmit="return confirm('Delete this suggestion?')">
            <input type="hidden" name="csrf" value="<?php echo e(csrf_token()); ?>">
            <input type="hidden" name="id" value="<?php echo (int)$s['id']; ?>">
            <input type="hidden" name="action" value="delete">
            <button class="text-xs bg-red-600 hover:bg-red-700 text-white px-3 py-1 rounded w-full">Delete</button>
          </form>
        </div>
      </div>
    </div>
  <?php endforeach; ?>
  <?php if (empty($suggestions)): ?>
    <div class="text-neutral-400 text-center py-8">No suggestions yet.</div>
  <?php endif; ?>
</div>
<?php include __DIR__ . '/../includes/template_footer.php'; ?>
