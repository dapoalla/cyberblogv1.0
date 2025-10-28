<?php
require __DIR__ . '/../includes/db.php';
require __DIR__ . '/../includes/helpers.php';
require __DIR__ . '/../includes/csrf.php';
function require_admin(){ if(empty($_SESSION['admin'])){ header('Location: '.base_url('admin/login.php')); exit; } }
require_admin();

$msg='';
if ($_SERVER['REQUEST_METHOD']==='POST' && csrf_check($_POST['csrf']??'')) {
  $name = trim($_POST['name']??'');
  $slug = trim($_POST['slug']??'') ?: slugify($name);
  $id = (int)($_POST['id']??0);
  if ($id) {
    $stmt=$mysqli->prepare("UPDATE cms_tags SET name=?, slug=? WHERE id=?");
    if($stmt){$stmt->bind_param('ssi',$name,$slug,$id);$stmt->execute();$stmt->close();$msg='Tag updated.';}
  } else {
    $stmt=$mysqli->prepare("INSERT INTO cms_tags (name,slug) VALUES (?,?)");
    if($stmt){$stmt->bind_param('ss',$name,$slug);$stmt->execute();$stmt->close();$msg='Tag created.';}
  }
}
if ($_SERVER['REQUEST_METHOD']==='POST' && isset($_POST['delete']) && csrf_check($_POST['csrf']??'')){
  $id=(int)($_POST['id']??0);
  $stmt=$mysqli->prepare("DELETE FROM cms_tags WHERE id=? LIMIT 1");
  if($stmt){$stmt->bind_param('i',$id);$stmt->execute();$stmt->close();}
}
$rows=[]; if($res=$mysqli->query("SELECT id,name,slug FROM cms_tags ORDER BY name")){ while($r=$res->fetch_assoc()) $rows[]=$r; }
$pageTitle='Tags'; $metaDescription='';
include __DIR__ . '/../includes/template_header.php';
include __DIR__ . '/../includes/admin_nav.php';
?>
<div class="flex items-center justify-between"><h1 class="text-2xl font-bold">Tags</h1></div>
<?php if($msg):?><div class="mt-3 text-green-400 text-sm"><?php echo e($msg);?></div><?php endif;?>
<form method="POST" class="mt-4 grid md:grid-cols-3 gap-3 items-end">
  <div>
    <label class="block text-sm mb-1">Name</label>
    <input name="name" class="w-full rounded-md bg-neutral-950 border border-neutral-800 px-3 py-2" required />
  </div>
  <div>
    <label class="block text-sm mb-1">Slug</label>
    <input name="slug" class="w-full rounded-md bg-neutral-950 border border-neutral-800 px-3 py-2" />
  </div>
  <div>
    <input type="hidden" name="csrf" value="<?php echo e(csrf_token()); ?>" />
    <button class="bg-sky-500 hover:bg-sky-600 text-white px-4 py-2 rounded">Add Tag</button>
  </div>
</form>
<table class="mt-6 w-full text-sm">
  <thead class="text-neutral-400"><tr><th class="text-left py-2">Name</th><th class="text-left py-2">Slug</th><th class="py-2">Actions</th></tr></thead>
  <tbody>
    <?php foreach($rows as $r): ?>
    <tr class="border-t border-neutral-800">
      <td class="py-2"><?php echo e($r['name']); ?></td>
      <td class="py-2"><?php echo e($r['slug']); ?></td>
      <td class="py-2 text-right">
        <form method="POST" class="inline" onsubmit="return confirm('Delete this tag?');">
          <input type="hidden" name="id" value="<?php echo (int)$r['id'];?>" />
          <input type="hidden" name="csrf" value="<?php echo e(csrf_token()); ?>" />
          <button name="delete" class="text-rose-400 hover:underline">Delete</button>
        </form>
      </td>
    </tr>
    <?php endforeach; ?>
  </tbody>
</table>
<?php include __DIR__ . '/../includes/template_footer.php'; ?>
