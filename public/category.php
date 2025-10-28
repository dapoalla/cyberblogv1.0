<?php
require __DIR__ . '/../includes/db.php';
require __DIR__ . '/../includes/helpers.php';
$slug=$_GET['slug']??'';
$stmt=$mysqli->prepare("SELECT id,name FROM cms_categories WHERE slug=? LIMIT 1");
$stmt->bind_param('s',$slug);$stmt->execute();$cat=$stmt->get_result()->fetch_assoc();$stmt->close();
if(!$cat){ http_response_code(404); echo 'Category not found'; exit; }
$pageTitle='Category: '.$cat['name']; $metaDescription='';
include __DIR__ . '/../includes/template_header.php';
$posts=[]; $q=$mysqli->prepare("SELECT id,title,slug,excerpt,cover_image FROM cms_posts WHERE category_id=? AND status='published' ORDER BY COALESCE(published_at,created_at) DESC");
$q->bind_param('i',$cat['id']);$q->execute();$r=$q->get_result(); while($row=$r->fetch_assoc()) $posts[]=$row; $q->close();
?>
<h1 class="text-2xl font-bold"><?php echo e($cat['name']); ?></h1>
<div class="mt-6 grid gap-6 md:grid-cols-2">
<?php foreach($posts as $p): ?>
  <article class="bg-neutral-900 border border-neutral-800 rounded-lg overflow-hidden">
    <?php if(!empty($p['cover_image'])): ?><a href="<?php echo base_url('public/post.php?slug='.e($p['slug']));?>"><img class="w-full aspect-video object-cover" src="<?php echo e($p['cover_image']); ?>" alt=""></a><?php endif; ?>
    <div class="p-6">
      <h3 class="text-xl font-semibold"><a class="hover:text-sky-400" href="<?php echo base_url('public/post.php?slug='.e($p['slug']));?>"><?php echo e($p['title']); ?></a></h3>
      <?php if(!empty($p['excerpt'])): ?><p class="text-neutral-300 mt-3"><?php echo e($p['excerpt']); ?></p><?php endif; ?>
    </div>
  </article>
<?php endforeach; ?>
</div>
<?php include __DIR__ . '/../includes/template_footer.php'; ?>
