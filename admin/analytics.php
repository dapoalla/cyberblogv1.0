<?php
require __DIR__ . '/../includes/db.php';
require __DIR__ . '/../includes/helpers.php';
require __DIR__ . '/../includes/csrf.php';
function require_admin(){ if(empty($_SESSION['admin'])){ header('Location: '.base_url('admin/login.php')); exit; } }
require_admin();

$pageTitle = 'Analytics';
$metaDescription = '';

// Fetch posts ordered by views desc
$posts = [];
if ($res = $mysqli->query("SELECT id, title, slug, views, COALESCE(published_at, created_at) AS dt FROM cms_posts ORDER BY views DESC, dt DESC LIMIT 200")) {
  while ($row = $res->fetch_assoc()) $posts[] = $row;
}

include __DIR__ . '/../includes/template_header.php';
include __DIR__ . '/../includes/admin_nav.php';
?>
<h1 class="text-2xl font-bold">Analytics</h1>
<p class="text-neutral-400 text-sm mt-1">Most read posts by total views</p>

<div class="mt-6 bg-neutral-900 border border-neutral-800 rounded-lg overflow-auto">
  <table class="min-w-full text-sm">
    <thead class="bg-neutral-950">
      <tr class="text-left">
        <th class="px-4 py-3 border-b border-neutral-800">#</th>
        <th class="px-4 py-3 border-b border-neutral-800">Title</th>
        <th class="px-4 py-3 border-b border-neutral-800">Views</th>
        <th class="px-4 py-3 border-b border-neutral-800">Published</th>
        <th class="px-4 py-3 border-b border-neutral-800">Actions</th>
      </tr>
    </thead>
    <tbody>
      <?php $i=1; foreach ($posts as $p): ?>
      <tr class="hover:bg-neutral-800/50">
        <td class="px-4 py-3 border-b border-neutral-800 text-neutral-400"><?php echo $i++; ?></td>
        <td class="px-4 py-3 border-b border-neutral-800">
          <div class="font-medium"><?php echo e($p['title']); ?></div>
          <div class="text-xs text-neutral-500">Slug: <?php echo e($p['slug']); ?> · ID: <?php echo (int)$p['id']; ?></div>
        </td>
        <td class="px-4 py-3 border-b border-neutral-800 font-semibold"><?php echo (int)$p['views']; ?></td>
        <td class="px-4 py-3 border-b border-neutral-800 text-neutral-400"><?php echo e($p['dt']); ?></td>
        <td class="px-4 py-3 border-b border-neutral-800 text-sm">
          <a href="<?php echo base_url('admin/edit_post.php?id='.(int)$p['id']); ?>" class="text-sky-400 hover:underline">Edit</a>
          <span class="mx-2 text-neutral-600">|</span>
          <a href="<?php echo base_url('public/post.php?slug='.e($p['slug'])); ?>" class="text-neutral-300 hover:underline" target="_blank">View</a>
        </td>
      </tr>
      <?php endforeach; ?>
      <?php if (!$posts): ?>
      <tr><td class="px-4 py-6 text-neutral-400" colspan="5">No posts found.</td></tr>
      <?php endif; ?>
    </tbody>
  </table>
</div>

<?php include __DIR__ . '/../includes/template_footer.php'; ?>
