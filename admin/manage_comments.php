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
    $currentRole = $_SESSION['admin']['role'] ?? 'editor';
    
    if ($_POST['action'] === 'approve') {
      $mysqli->query("UPDATE cms_comments SET status='approved' WHERE id=$id");
    } elseif ($_POST['action'] === 'spam') {
      $mysqli->query("UPDATE cms_comments SET status='spam' WHERE id=$id");
    } elseif ($_POST['action'] === 'delete') {
      // Only super_editor can delete
      if ($currentRole === 'super_editor') {
        $mysqli->query("DELETE FROM cms_comments WHERE id=$id");
      }
    }
  }
  header('Location: ' . base_url('admin/manage_comments.php'));
  exit;
}

$comments = [];
if ($res = $mysqli->query("SELECT c.id, c.content, c.status, c.created_at, u.name, u.email, u.picture, p.title as post_title, p.slug as post_slug 
                           FROM cms_comments c 
                           JOIN cms_oauth_users u ON u.id = c.oauth_user_id 
                           JOIN cms_posts p ON p.id = c.post_id 
                           ORDER BY c.created_at DESC LIMIT 100")) {
  while ($row = $res->fetch_assoc()) $comments[] = $row;
}

$pageTitle = 'Manage Comments';
$currentRole = $_SESSION['admin']['role'] ?? 'editor';
include __DIR__ . '/../includes/template_header.php';
include __DIR__ . '/../includes/admin_nav.php';
?>
<div class="flex items-center justify-between">
  <h1 class="text-2xl font-bold">Manage Comments</h1>
</div>

<div class="mt-6 space-y-4">
  <?php foreach ($comments as $c): ?>
    <div class="bg-neutral-900 border border-neutral-800 rounded-lg p-4">
      <div class="flex items-start justify-between">
        <div class="flex-1">
          <div class="flex items-center gap-3 text-sm">
            <?php if (!empty($c['picture'])): ?>
              <img src="<?php echo e($c['picture']); ?>" alt="" class="w-8 h-8 rounded-full">
            <?php endif; ?>
            <span class="font-semibold"><?php echo e($c['name'] ?: 'User'); ?></span>
            <span class="text-neutral-400"><?php echo e($c['email'] ?: ''); ?></span>
            <span class="text-neutral-400">·</span>
            <span class="text-neutral-400"><?php echo e($c['created_at']); ?></span>
            <span class="px-2 py-0.5 rounded text-xs <?php echo $c['status']==='approved'?'bg-green-900 text-green-400':($c['status']==='spam'?'bg-red-900 text-red-400':'bg-yellow-900 text-yellow-400'); ?>">
              <?php echo e($c['status']); ?>
            </span>
          </div>
          <div class="mt-2 text-neutral-300"><?php echo nl2br(e($c['content'])); ?></div>
          <div class="mt-2 text-xs text-neutral-400">
            On post: <a href="<?php echo base_url('public/post.php?slug='.e($c['post_slug'])); ?>" class="text-sky-400 hover:underline" target="_blank"><?php echo e($c['post_title']); ?></a>
          </div>
        </div>
        <div class="flex gap-2 ml-4">
          <?php if ($c['status'] !== 'approved'): ?>
            <form method="POST" class="inline">
              <input type="hidden" name="csrf" value="<?php echo e(csrf_token()); ?>">
              <input type="hidden" name="id" value="<?php echo (int)$c['id']; ?>">
              <input type="hidden" name="action" value="approve">
              <button class="text-xs bg-green-600 hover:bg-green-700 text-white px-3 py-1 rounded">Approve</button>
            </form>
          <?php endif; ?>
          <form method="POST" class="inline">
            <input type="hidden" name="csrf" value="<?php echo e(csrf_token()); ?>">
            <input type="hidden" name="id" value="<?php echo (int)$c['id']; ?>">
            <input type="hidden" name="action" value="spam">
            <button class="text-xs bg-yellow-600 hover:bg-yellow-700 text-white px-3 py-1 rounded">Spam</button>
          </form>
          <?php if ($currentRole === 'super_editor'): ?>
            <form method="POST" class="inline" onsubmit="return confirm('Delete this comment?')">
              <input type="hidden" name="csrf" value="<?php echo e(csrf_token()); ?>">
              <input type="hidden" name="id" value="<?php echo (int)$c['id']; ?>">
              <input type="hidden" name="action" value="delete">
              <button class="text-xs bg-red-600 hover:bg-red-700 text-white px-3 py-1 rounded">Delete</button>
            </form>
          <?php endif; ?>
        </div>
      </div>
    </div>
  <?php endforeach; ?>
  <?php if (empty($comments)): ?>
    <div class="text-neutral-400 text-center py-8">No comments yet.</div>
  <?php endif; ?>
</div>
<?php include __DIR__ . '/../includes/template_footer.php'; ?>
