<?php
require __DIR__ . '/../includes/db.php';
require __DIR__ . '/../includes/helpers.php';
$pageTitle = 'Search';
$metaDescription = 'Search blog posts';

$query = trim($_GET['q'] ?? '');
$category = isset($_GET['category']) ? (int)$_GET['category'] : 0;

// Get all categories for filter
$categories = [];
if ($res = $mysqli->query("SELECT id, name FROM cms_categories ORDER BY name")) {
  while ($row = $res->fetch_assoc()) $categories[] = $row;
}

$posts = [];
if (!empty($query) || $category > 0) {
  $sql = "SELECT p.id, p.title, p.slug, p.excerpt, p.cover_image, p.views, c.name AS category 
          FROM cms_posts p 
          LEFT JOIN cms_categories c ON c.id = p.category_id 
          WHERE p.status='published' AND (p.published_at IS NULL OR p.published_at <= NOW())";
  
  $params = [];
  $types = '';
  
  if (!empty($query)) {
    $sql .= " AND (p.title LIKE ? OR p.excerpt LIKE ? OR p.content_html LIKE ?)";
    $searchTerm = '%' . $query . '%';
    $params[] = $searchTerm;
    $params[] = $searchTerm;
    $params[] = $searchTerm;
    $types .= 'sss';
  }
  
  if ($category > 0) {
    $sql .= " AND p.category_id = ?";
    $params[] = $category;
    $types .= 'i';
  }
  
  $sql .= " ORDER BY p.views DESC, COALESCE(p.published_at, p.created_at) DESC LIMIT 50";
  
  $stmt = $mysqli->prepare($sql);
  if (!empty($params)) {
    $stmt->bind_param($types, ...$params);
  }
  $stmt->execute();
  $result = $stmt->get_result();
  while ($row = $result->fetch_assoc()) {
    $posts[] = $row;
  }
  $stmt->close();
}

include __DIR__ . '/../includes/template_header.php';
?>
<h1 class="text-3xl font-bold">Search</h1>
<form method="GET" class="mt-6 flex flex-col md:flex-row gap-3">
  <input type="text" name="q" value="<?php echo e($query); ?>" placeholder="Search posts..." class="flex-1 rounded-md bg-neutral-950 border border-neutral-800 px-4 py-2" />
  <select name="category" class="rounded-md bg-neutral-950 border border-neutral-800 px-4 py-2">
    <option value="">All Categories</option>
    <?php foreach ($categories as $cat): ?>
      <option value="<?php echo (int)$cat['id']; ?>" <?php echo $category === (int)$cat['id'] ? 'selected' : ''; ?>><?php echo e($cat['name']); ?></option>
    <?php endforeach; ?>
  </select>
  <button type="submit" class="bg-sky-500 hover:bg-sky-600 text-white px-6 py-2 rounded">Search</button>
</form>

<?php if (!empty($query) || $category > 0): ?>
  <div class="mt-8">
    <h2 class="text-xl font-semibold">Results (<?php echo count($posts); ?>)</h2>
    <?php if (empty($posts)): ?>
      <p class="text-neutral-400 mt-4">No posts found matching your search.</p>
    <?php else: ?>
      <div class="mt-4 grid gap-6 md:grid-cols-2">
        <?php foreach ($posts as $p): ?>
          <article class="bg-neutral-900 border border-neutral-800 rounded-lg overflow-hidden">
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
    <?php endif; ?>
  </div>
<?php endif; ?>
<?php include __DIR__ . '/../includes/template_footer.php'; ?>
