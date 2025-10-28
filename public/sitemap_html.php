<?php
require __DIR__ . '/../includes/db.php';
require __DIR__ . '/../includes/helpers.php';
$pageTitle = 'Sitemap';
$metaDescription = 'Browse all posts on Cyberrose Blog';
include __DIR__ . '/../includes/template_header.php';

// Get all categories with post counts
$categories = [];
if ($res = $mysqli->query("SELECT c.id, c.name, c.slug, COUNT(p.id) as post_count 
                           FROM cms_categories c 
                           LEFT JOIN cms_posts p ON p.category_id = c.id AND p.status='published'
                           GROUP BY c.id, c.name, c.slug 
                           ORDER BY c.name")) {
  while ($row = $res->fetch_assoc()) $categories[] = $row;
}

// Get all published posts grouped by category
$posts_by_category = [];
if ($res = $mysqli->query("SELECT p.id, p.title, p.slug, c.id as cat_id, c.name as cat_name 
                           FROM cms_posts p 
                           LEFT JOIN cms_categories c ON c.id = p.category_id 
                           WHERE p.status='published' AND (p.published_at IS NULL OR p.published_at <= NOW())
                           ORDER BY c.name, p.title")) {
  while ($row = $res->fetch_assoc()) {
    $cat_id = $row['cat_id'] ?? 0;
    if (!isset($posts_by_category[$cat_id])) {
      $posts_by_category[$cat_id] = [];
    }
    $posts_by_category[$cat_id][] = $row;
  }
}
?>
<h1 class="text-3xl font-bold">Sitemap</h1>
<p class="text-neutral-300 mt-2">Browse all content on Cyberrose Blog</p>

<div class="mt-8 grid gap-8">
  <section>
    <h2 class="text-2xl font-semibold mb-4">Main Pages</h2>
    <ul class="space-y-2 text-neutral-300">
      <li><a href="<?php echo base_url('public/index.php'); ?>" class="text-sky-400 hover:underline">Home</a></li>
      <li><a href="<?php echo base_url('public/about.php'); ?>" class="text-sky-400 hover:underline">About Us</a></li>
      <li><a href="<?php echo base_url('public/contact.php'); ?>" class="text-sky-400 hover:underline">Contact</a></li>
      <li><a href="<?php echo base_url('public/search.php'); ?>" class="text-sky-400 hover:underline">Search</a></li>
    </ul>
  </section>

  <?php foreach ($categories as $cat): ?>
    <?php if (isset($posts_by_category[$cat['id']]) && !empty($posts_by_category[$cat['id']])): ?>
      <section>
        <h2 class="text-2xl font-semibold mb-4"><?php echo e($cat['name']); ?> (<?php echo $cat['post_count']; ?>)</h2>
        <ul class="space-y-2 text-neutral-300 grid md:grid-cols-2 gap-x-8">
          <?php foreach ($posts_by_category[$cat['id']] as $post): ?>
            <li><a href="<?php echo base_url('public/post.php?slug='.e($post['slug'])); ?>" class="text-sky-400 hover:underline"><?php echo e($post['title']); ?></a></li>
          <?php endforeach; ?>
        </ul>
      </section>
    <?php endif; ?>
  <?php endforeach; ?>

  <?php if (isset($posts_by_category[0]) && !empty($posts_by_category[0])): ?>
    <section>
      <h2 class="text-2xl font-semibold mb-4">Uncategorized</h2>
      <ul class="space-y-2 text-neutral-300 grid md:grid-cols-2 gap-x-8">
        <?php foreach ($posts_by_category[0] as $post): ?>
          <li><a href="<?php echo base_url('public/post.php?slug='.e($post['slug'])); ?>" class="text-sky-400 hover:underline"><?php echo e($post['title']); ?></a></li>
        <?php endforeach; ?>
      </ul>
    </section>
  <?php endif; ?>
</div>
<?php include __DIR__ . '/../includes/template_footer.php'; ?>
