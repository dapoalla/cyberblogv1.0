<?php
require __DIR__ . '/../includes/db.php';
require __DIR__ . '/../includes/helpers.php';
$pageTitle = 'Home';
$metaDescription = 'Latest posts from Cyberrose Blog';
include __DIR__ . '/../includes/template_header.php';

// Pagination
$page = isset($_GET['page']) ? max(1, (int)$_GET['page']) : 1;
$perPage = 12;
$offset = ($page - 1) * $perPage;

// Get total count
$totalCount = 0;
if ($res = $mysqli->query("SELECT COUNT(*) as cnt FROM cms_posts WHERE status='published' AND (published_at IS NULL OR published_at<=NOW())")) {
  $totalCount = (int)$res->fetch_assoc()['cnt'];
}

// All posts sorted by views
$posts = [];
if ($res = $mysqli->query("SELECT p.id,p.title,p.slug,p.excerpt,p.cover_image,p.views,c.name AS category FROM cms_posts p LEFT JOIN cms_categories c ON c.id=p.category_id WHERE p.status='published' AND (p.published_at IS NULL OR p.published_at<=NOW()) ORDER BY p.views DESC, COALESCE(p.published_at,p.created_at) DESC LIMIT $perPage OFFSET $offset")) { 
  while($row=$res->fetch_assoc()) $posts[]=$row; 
}

// Recent posts for sidebar (top 6)
$recentSidebar = [];
if ($res = $mysqli->query("SELECT p.id,p.title,p.slug,p.cover_image,p.views,COALESCE(p.published_at,p.created_at) AS dt FROM cms_posts p WHERE p.status='published' AND (p.published_at IS NULL OR p.published_at<=NOW()) ORDER BY dt DESC LIMIT 6")) { 
  while($row=$res->fetch_assoc()) $recentSidebar[]=$row; 
}

// Popular posts for sidebar (top 6 by views)
$popularSidebar = [];
if ($res = $mysqli->query("SELECT p.id,p.title,p.slug,p.cover_image,p.views FROM cms_posts p WHERE p.status='published' AND (p.published_at IS NULL OR p.published_at<=NOW()) ORDER BY p.views DESC LIMIT 6")) { 
  while($row=$res->fetch_assoc()) $popularSidebar[]=$row; 
}

// Categories for sidebar
$categories = [];
if ($res = $mysqli->query("SELECT c.id, c.name, c.slug, COUNT(p.id) as post_count FROM cms_categories c LEFT JOIN cms_posts p ON p.category_id = c.id AND p.status='published' GROUP BY c.id, c.name, c.slug HAVING post_count > 0 ORDER BY c.name LIMIT 10")) {
  while($row=$res->fetch_assoc()) $categories[]=$row;
}

$hasMore = ($offset + $perPage) < $totalCount;
?>
<div class="flex flex-col lg:flex-row gap-8">
  <!-- Main Content -->
  <div class="flex-1">
    <h1 class="text-3xl font-bold">Cyberrose Blog</h1>
    <p class="text-neutral-300 mt-2">We have a wealth of proven expertise in security integration, networking, automation, and cybersecurity. At CyberRose, we deliver real human reviews and genuine blogs — authentic human insights</p>
    
    <?php if (isset($_GET['newsletter'])): ?>
      <?php if ($_GET['newsletter'] === 'success'): ?>
        <div class="mt-4 bg-green-900/30 border border-green-700 text-green-400 px-4 py-3 rounded">
          Thank you for subscribing to our newsletter!
        </div>
      <?php elseif ($_GET['newsletter'] === 'already'): ?>
        <div class="mt-4 bg-yellow-900/30 border border-yellow-700 text-yellow-400 px-4 py-3 rounded">
          You're already subscribed to our newsletter.
        </div>
      <?php endif; ?>
    <?php endif; ?>

    <section class="mt-8">
      <h2 class="text-2xl font-semibold">All Posts</h2>
      <div class="mt-4 grid gap-6 md:grid-cols-2">
        <?php foreach ($posts as $p): ?>
          <article class="bg-neutral-900 border border-neutral-800 rounded-lg overflow-hidden hover:border-neutral-700 transition">
            <?php if (!empty($p['cover_image'])): ?>
              <a href="<?php echo base_url('public/post.php?slug='.e($p['slug'])); ?>"><img src="<?php echo e($p['cover_image']); ?>" alt="<?php echo e($p['title']); ?>" class="w-full aspect-video object-cover" loading="lazy"></a>
            <?php endif; ?>
            <div class="p-6">
              <h3 class="text-xl font-semibold"><a class="hover:text-sky-400" href="<?php echo base_url('public/post.php?slug='.e($p['slug'])); ?>"><?php echo e($p['title']); ?></a></h3>
              <?php if (!empty($p['category'])): ?><div class="text-xs text-neutral-400 mt-1"><?php echo e($p['category']); ?> · <?php echo (int)($p['views'] ?? 0); ?> views</div><?php endif; ?>
              <?php if (!empty($p['excerpt'])): ?><p class="text-neutral-300 mt-3"><?php echo e($p['excerpt']); ?></p><?php endif; ?>
              <a class="inline-block mt-4 text-sky-400 hover:underline" href="<?php echo base_url('public/post.php?slug='.e($p['slug'])); ?>">Read more</a>
            </div>
          </article>
        <?php endforeach; ?>
      </div>
      
      <?php if ($hasMore): ?>
        <div class="mt-8 text-center">
          <a href="?page=<?php echo $page + 1; ?>" class="inline-block bg-sky-500 hover:bg-sky-600 text-white px-6 py-3 rounded">Load More Posts</a>
        </div>
      <?php endif; ?>
      
      <?php if ($page > 1): ?>
        <div class="mt-4 text-center">
          <a href="?page=<?php echo $page - 1; ?>" class="text-sky-400 hover:underline">← Previous Page</a>
        </div>
      <?php endif; ?>
    </section>
  </div>

  <!-- Sidebar -->
  <aside class="lg:w-80 space-y-6">
    <!-- Recent Posts -->
    <div class="bg-neutral-900 border border-neutral-800 rounded-lg p-6">
      <h3 class="text-lg font-semibold mb-4">Recent Posts</h3>
      <ul class="space-y-2">
        <?php foreach ($recentSidebar as $p): ?>
          <li>
            <a href="<?php echo base_url('public/post.php?slug='.e($p['slug'])); ?>" class="text-sm hover:text-sky-400 line-clamp-2 block">
              <?php echo e($p['title']); ?>
            </a>
            <div class="text-xs text-neutral-400 mt-0.5"><?php echo (int)($p['views'] ?? 0); ?> views</div>
          </li>
        <?php endforeach; ?>
      </ul>
    </div>

    <!-- Popular Posts -->
    <div class="bg-neutral-900 border border-neutral-800 rounded-lg p-6">
      <h3 class="text-lg font-semibold mb-4">Popular Posts</h3>
      <ul class="space-y-2">
        <?php foreach ($popularSidebar as $p): ?>
          <li>
            <a href="<?php echo base_url('public/post.php?slug='.e($p['slug'])); ?>" class="text-sm hover:text-sky-400 line-clamp-2 block">
              <?php echo e($p['title']); ?>
            </a>
            <div class="text-xs text-neutral-400 mt-0.5"><?php echo (int)($p['views'] ?? 0); ?> views</div>
          </li>
        <?php endforeach; ?>
      </ul>
    </div>

    <!-- Categories -->
    <?php if (!empty($categories)): ?>
      <div class="bg-neutral-900 border border-neutral-800 rounded-lg p-6">
        <h3 class="text-lg font-semibold mb-4">Categories</h3>
        <div class="space-y-2">
          <?php foreach ($categories as $cat): ?>
            <a href="<?php echo base_url('public/category.php?slug='.e($cat['slug'])); ?>" class="flex items-center justify-between text-sm hover:text-sky-400">
              <span><?php echo e($cat['name']); ?></span>
              <span class="text-neutral-400"><?php echo (int)$cat['post_count']; ?></span>
            </a>
          <?php endforeach; ?>
        </div>
      </div>
    <?php endif; ?>
  </aside>
</div>
<?php include __DIR__ . '/../includes/template_footer.php'; ?>
