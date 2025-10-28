<?php
require __DIR__ . '/../includes/db.php';
require __DIR__ . '/../includes/helpers.php';
require __DIR__ . '/../includes/csrf.php';
function require_admin(){ if(empty($_SESSION['admin'])){ header('Location: '.base_url('admin/login.php')); exit; } }
require_admin();
$pageTitle='Dashboard'; $metaDescription='';
include __DIR__ . '/../includes/template_header.php';
$counts=['posts'=>0,'published'=>0,'categories'=>0,'tags'=>0,'comments'=>0,'newsletter'=>0,'contacts'=>0,'users'=>0,'suggestions'=>0];
if ($res=$mysqli->query("SELECT COUNT(*) c FROM cms_posts")) $counts['posts']=(int)$res->fetch_assoc()['c'];
if ($res=$mysqli->query("SELECT COUNT(*) c FROM cms_posts WHERE status='published'")) $counts['published']=(int)$res->fetch_assoc()['c'];
if ($res=$mysqli->query("SELECT COUNT(*) c FROM cms_categories")) $counts['categories']=(int)$res->fetch_assoc()['c'];
if ($res=$mysqli->query("SELECT COUNT(*) c FROM cms_tags")) $counts['tags']=(int)$res->fetch_assoc()['c'];
if ($res=$mysqli->query("SELECT COUNT(*) c FROM cms_comments")) $counts['comments']=(int)$res->fetch_assoc()['c'];
if ($res=$mysqli->query("SELECT COUNT(*) c FROM cms_newsletter WHERE status='active'")) $counts['newsletter']=(int)$res->fetch_assoc()['c'];
if ($res=$mysqli->query("SELECT COUNT(*) c FROM cms_contacts WHERE status='new'")) $counts['contacts']=(int)$res->fetch_assoc()['c'];
if ($res=$mysqli->query("SELECT COUNT(*) c FROM cms_oauth_users")) $counts['users']=(int)$res->fetch_assoc()['c'];
if ($res=$mysqli->query("SELECT COUNT(*) c FROM cms_user_suggestions WHERE status='pending'")) $counts['suggestions']=(int)$res->fetch_assoc()['c'];
?>
<h1 class="text-3xl font-bold">Admin Dashboard</h1>
<div class="grid gap-4 md:grid-cols-3 lg:grid-cols-5 mt-6">
  <div class="bg-neutral-900 border border-neutral-800 rounded p-4"><div class="text-neutral-400 text-sm">Posts</div><div class="text-2xl font-bold"><?php echo $counts['posts']; ?></div></div>
  <div class="bg-neutral-900 border border-neutral-800 rounded p-4"><div class="text-neutral-400 text-sm">Published</div><div class="text-2xl font-bold"><?php echo $counts['published']; ?></div></div>
  <div class="bg-neutral-900 border border-neutral-800 rounded p-4"><div class="text-neutral-400 text-sm">Comments</div><div class="text-2xl font-bold"><?php echo $counts['comments']; ?></div></div>
  <div class="bg-neutral-900 border border-neutral-800 rounded p-4"><div class="text-neutral-400 text-sm">Subscribers</div><div class="text-2xl font-bold"><?php echo $counts['newsletter']; ?></div></div>
  <div class="bg-neutral-900 border border-neutral-800 rounded p-4"><div class="text-neutral-400 text-sm">New Contacts</div><div class="text-2xl font-bold"><?php echo $counts['contacts']; ?></div></div>
  <div class="bg-neutral-900 border border-neutral-800 rounded p-4"><div class="text-neutral-400 text-sm">Users</div><div class="text-2xl font-bold"><?php echo $counts['users']; ?></div></div>
  <div class="bg-neutral-900 border border-neutral-800 rounded p-4"><div class="text-neutral-400 text-sm">Suggestions</div><div class="text-2xl font-bold"><?php echo $counts['suggestions']; ?></div></div>
  <div class="bg-neutral-900 border border-neutral-800 rounded p-4"><div class="text-neutral-400 text-sm">Categories</div><div class="text-2xl font-bold"><?php echo $counts['categories']; ?></div></div>
  <div class="bg-neutral-900 border border-neutral-800 rounded p-4"><div class="text-neutral-400 text-sm">Tags</div><div class="text-2xl font-bold"><?php echo $counts['tags']; ?></div></div>
</div>

<div class="mt-8">
  <h2 class="text-xl font-semibold mb-4">Content Management</h2>
  <div class="flex flex-wrap gap-3">
    <a class="bg-sky-500 hover:bg-sky-600 text-white px-4 py-2 rounded" href="manage_posts.php">Manage Posts</a>
    <a class="bg-neutral-800 hover:bg-neutral-700 text-white px-4 py-2 rounded" href="manage_categories.php">Categories</a>
    <a class="bg-neutral-800 hover:bg-neutral-700 text-white px-4 py-2 rounded" href="manage_tags.php">Tags</a>
  </div>
</div>

<div class="mt-8">
  <h2 class="text-xl font-semibold mb-4">User Engagement</h2>
  <div class="flex flex-wrap gap-3">
    <a class="bg-neutral-800 hover:bg-neutral-700 text-white px-4 py-2 rounded" href="manage_comments.php">Comments</a>
    <a class="bg-neutral-800 hover:bg-neutral-700 text-white px-4 py-2 rounded" href="manage_newsletter.php">Newsletter</a>
    <a class="bg-neutral-800 hover:bg-neutral-700 text-white px-4 py-2 rounded" href="manage_contacts.php">Contact Messages</a>
    <a class="bg-neutral-800 hover:bg-neutral-700 text-white px-4 py-2 rounded" href="manage_users.php">User Management</a>
    <a class="bg-neutral-800 hover:bg-neutral-700 text-white px-4 py-2 rounded" href="manage_suggestions.php">User Suggestions</a>
  </div>
</div>

<div class="mt-8">
  <h2 class="text-xl font-semibold mb-4">System</h2>
  <div class="flex flex-wrap gap-3">
    <a class="bg-neutral-800 hover:bg-neutral-700 text-white px-4 py-2 rounded" href="analytics.php">Analytics</a>
    <a class="bg-neutral-800 hover:bg-neutral-700 text-white px-4 py-2 rounded" href="manage_editorial.php">Editorial Team</a>
    <a class="bg-neutral-800 hover:bg-neutral-700 text-white px-4 py-2 rounded" href="settings.php">Settings</a>
    <a class="bg-neutral-800 hover:bg-neutral-700 text-white px-4 py-2 rounded" href="<?php echo base_url('public/about.php#support'); ?>">Support</a>
    <a class="bg-rose-600 hover:bg-rose-700 text-white px-4 py-2 rounded" href="logout.php">Logout</a>
  </div>
</div>
<?php include __DIR__ . '/../includes/template_footer.php'; ?>
