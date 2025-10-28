<?php
require __DIR__ . '/../includes/db.php';
require __DIR__ . '/../includes/helpers.php';
require __DIR__ . '/../includes/csrf.php';
function require_admin(){ if(empty($_SESSION['admin'])){ header('Location: '.base_url('admin/login.php')); exit; } }
require_admin();

// Create/Update
$msg='';
$error='';
if ($_SERVER['REQUEST_METHOD']==='POST' && csrf_check($_POST['csrf']??'')) {
  $name = trim($_POST['name']??'');
  $slug = trim($_POST['slug']??'') ?: slugify($name);
  $id = (int)($_POST['id']??0);
  
  // Check for duplicate slug
  $checkStmt = $mysqli->prepare("SELECT id FROM cms_categories WHERE slug=? AND id!=? LIMIT 1");
  $checkStmt->bind_param('si', $slug, $id);
  $checkStmt->execute();
  $exists = $checkStmt->get_result()->fetch_assoc();
  $checkStmt->close();
  
  if ($exists) {
    $error = "A category with slug '$slug' already exists. Please use a different name or slug.";
  } else {
    if ($id) {
      $stmt=$mysqli->prepare("UPDATE cms_categories SET name=?, slug=? WHERE id=?");
      if($stmt){$stmt->bind_param('ssi',$name,$slug,$id);$stmt->execute();$stmt->close();$msg='Category updated.';}
    } else {
      $stmt=$mysqli->prepare("INSERT INTO cms_categories (name,slug) VALUES (?,?)");
      if($stmt){$stmt->bind_param('ss',$name,$slug);$stmt->execute();$stmt->close();$msg='Category created.';}
    }
  }
}
// Delete
if ($_SERVER['REQUEST_METHOD']==='POST' && isset($_POST['delete']) && csrf_check($_POST['csrf']??'')){
  $id=(int)($_POST['id']??0);
  $stmt=$mysqli->prepare("DELETE FROM cms_categories WHERE id=? LIMIT 1");
  if($stmt){$stmt->bind_param('i',$id);$stmt->execute();$stmt->close();}
}

$rows=[]; if($res=$mysqli->query("SELECT id,name,slug FROM cms_categories ORDER BY name")){ while($r=$res->fetch_assoc()) $rows[]=$r; }
$pageTitle='Categories'; $metaDescription='';
include __DIR__ . '/../includes/template_header.php';
include __DIR__ . '/../includes/admin_nav.php';
?>
<div class="flex items-center justify-between"><h1 class="text-2xl font-bold">Categories</h1></div>
<?php if($msg):?><div class="mt-3 text-green-400 text-sm"><?php echo e($msg);?></div><?php endif;?>
<?php if($error):?><div class="mt-3 text-red-400 text-sm"><?php echo e($error);?></div><?php endif;?>
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
    <button class="bg-sky-500 hover:bg-sky-600 text-white px-4 py-2 rounded">Add Category</button>
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
        <form method="POST" class="inline" onsubmit="return confirm('Delete this category?');">
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
