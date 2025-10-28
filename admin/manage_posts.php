<?php
require __DIR__ . '/../includes/db.php';
require __DIR__ . '/../includes/helpers.php';
require __DIR__ . '/../includes/csrf.php';
function require_admin(){ if(empty($_SESSION['admin'])){ header('Location: '.base_url('admin/login.php')); exit; } }
require_admin();

// Delete (only super_editor)
if ($_SERVER['REQUEST_METHOD']==='POST' && isset($_POST['delete']) && csrf_check($_POST['csrf']??'')){
  $currentRole = $_SESSION['admin']['role'] ?? 'editor';
  if ($currentRole === 'super_editor') {
    $id=(int)($_POST['id']??0);
    $stmt=$mysqli->prepare("DELETE FROM cms_posts WHERE id=? LIMIT 1");
    if($stmt){$stmt->bind_param('i',$id);$stmt->execute();$stmt->close();}
  }
}

// List
$rows=[];
$q="SELECT p.id,p.title,p.slug,p.status,COALESCE(p.published_at,p.created_at) dt,c.name cat FROM cms_posts p LEFT JOIN cms_categories c ON c.id=p.category_id ORDER BY dt DESC";
if($res=$mysqli->query($q)){ while($r=$res->fetch_assoc()) $rows[]=$r; }

$pageTitle='Manage Posts'; $metaDescription='';
$currentRole = $_SESSION['admin']['role'] ?? 'editor';
include __DIR__ . '/../includes/template_header.php';
include __DIR__ . '/../includes/admin_nav.php';
?>
<div class="flex items-center justify-between">
  <h1 class="text-2xl font-bold">Posts</h1>
  <a class="bg-sky-500 hover:bg-sky-600 text-white px-4 py-2 rounded" href="edit_post.php">New Post</a>
</div>
<table class="mt-6 w-full text-sm">
  <thead class="text-neutral-400"><tr><th class="text-left py-2">Title</th><th class="text-left py-2">Category</th><th class="text-left py-2">Status</th><th class="text-left py-2">Date</th><th class="py-2">Actions</th></tr></thead>
  <tbody>
    <?php foreach($rows as $r): ?>
    <tr class="border-t border-neutral-800">
      <td class="py-2"><?php echo e($r['title']); ?></td>
      <td class="py-2"><?php echo e($r['cat']??''); ?></td>
      <td class="py-2"><?php echo e($r['status']); ?></td>
      <td class="py-2"><?php echo e($r['dt']); ?></td>
      <td class="py-2 text-right">
        <a class="text-sky-400 hover:underline mr-3" href="edit_post.php?id=<?php echo (int)$r['id']; ?>">Edit</a>
        <?php if ($currentRole === 'super_editor'): ?>
          <form method="POST" class="inline" onsubmit="return confirm('Delete this post?');">
            <input type="hidden" name="id" value="<?php echo (int)$r['id']; ?>" />
            <input type="hidden" name="csrf" value="<?php echo e(csrf_token()); ?>" />
            <button name="delete" class="text-rose-400 hover:underline">Delete</button>
          </form>
        <?php endif; ?>
      </td>
    </tr>
    <?php endforeach; ?>
  </tbody>
</table>
<?php include __DIR__ . '/../includes/template_footer.php'; ?>
