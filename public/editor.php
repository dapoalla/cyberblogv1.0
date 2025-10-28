<?php
require __DIR__ . '/../includes/db.php';
require __DIR__ . '/../includes/helpers.php';

$u = trim($_GET['u'] ?? '');
if ($u === '') { http_response_code(400); echo 'Missing editor username'; exit; }

// Fetch editor by username
$stmt = $mysqli->prepare("SELECT id, username, COALESCE(display_name, username) AS display_name, bio, profile_image, role, created_at FROM cms_admin_users WHERE username=? LIMIT 1");
$stmt->bind_param('s', $u);
$stmt->execute();
$editor = $stmt->get_result()->fetch_assoc();
$stmt->close();
if (!$editor) { http_response_code(404); echo 'Editor not found'; exit; }

$pageTitle = $editor['display_name'] . ' - Editor Profile';
$metaDescription = substr(trim($editor['bio'] ?? ''), 0, 150);

// Determine the author label used in posts
$authorLabel = $editor['display_name'] ?: $editor['username'];

// Fetch posts that list this editor as author
$posts = [];
$st = $mysqli->prepare("SELECT id, title, slug, cover_image, excerpt, views, COALESCE(published_at, created_at) AS dt FROM cms_posts WHERE status='published' AND (published_at IS NULL OR published_at <= NOW()) AND author_name = ? ORDER BY dt DESC LIMIT 20");
$st->bind_param('s', $authorLabel);
$st->execute();
$r = $st->get_result();
while ($row = $r->fetch_assoc()) $posts[] = $row;
$st->close();

include __DIR__ . '/../includes/template_header.php';
?>
<h1 class="text-3xl font-bold">Editor</h1>
<div class="mt-4 flex items-start gap-4">
  <?php if (!empty($editor['profile_image'])): ?>
    <img src="<?php echo e($editor['profile_image']); ?>" alt="<?php echo e($editor['display_name']); ?>" class="w-20 h-20 rounded-full object-cover" />
  <?php else: ?>
    <div class="w-20 h-20 rounded-full bg-neutral-800 flex items-center justify-center text-2xl font-bold">
      <?php echo strtoupper(substr($editor['display_name'] ?: $editor['username'], 0, 1)); ?>
    </div>
  <?php endif; ?>
  <div class="flex-1">
    <div class="flex items-center gap-2">
      <h2 class="text-2xl font-semibold"><?php echo e($editor['display_name']); ?></h2>
      <?php if (!empty($editor['role'])): ?>
        <span class="px-2 py-0.5 rounded text-xs bg-neutral-800 text-neutral-300"><?php echo e(ucfirst(str_replace('_', ' ', $editor['role']))); ?></span>
      <?php endif; ?>
    </div>
    <?php if (!empty($editor['bio'])): ?><p class="text-neutral-300 mt-2"><?php echo e($editor['bio']); ?></p><?php endif; ?>
    <div class="text-xs text-neutral-500 mt-2">Joined: <?php echo e($editor['created_at']); ?></div>
  </div>
</div>

<?php if (!empty($posts)): ?>
  <div class="mt-10">
    <h3 class="text-xl font-semibold">Posts by <?php echo e($editor['display_name']); ?></h3>
    <div class="mt-4 grid gap-6 md:grid-cols-2">
      <?php foreach ($posts as $p): ?>
        <article class="bg-neutral-900 border border-neutral-800 rounded-lg overflow-hidden">
          <?php if (!empty($p['cover_image'])): ?>
            <a href="<?php echo base_url('public/post.php?slug='.e($p['slug'])); ?>">
              <img class="w-full aspect-video object-cover" src="<?php echo e($p['cover_image']); ?>" alt="<?php echo e($p['title']); ?>" loading="lazy" />
            </a>
          <?php endif; ?>
          <div class="p-6">
            <h4 class="text-lg font-semibold"><a class="hover:text-sky-400" href="<?php echo base_url('public/post.php?slug='.e($p['slug'])); ?>"><?php echo e($p['title']); ?></a></h4>
            <div class="text-xs text-neutral-400 mt-1"><?php echo (int)($p['views'] ?? 0); ?> views</div>
            <?php if (!empty($p['excerpt'])): ?><p class="text-neutral-300 mt-3"><?php echo e($p['excerpt']); ?></p><?php endif; ?>
          </div>
        </article>
      <?php endforeach; ?>
    </div>
  </div>
<?php else: ?>
  <p class="mt-8 text-neutral-400">No posts found for this editor yet.</p>
<?php endif; ?>

<?php include __DIR__ . '/../includes/template_footer.php'; ?>
