<?php
require_once __DIR__ . '/../includes/auth.php';
require_admin();

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['dismiss_onboarding']) && csrf_check($_POST['csrf'] ?? '')) {
  $mysqli->query("UPDATE cms_settings SET onboarding_dismissed=1 WHERE id=1");
  header('Location: ' . base_url('admin/index.php'));
  exit;
}
$onboardingDismissed = true;
if ($res = $mysqli->query("SELECT onboarding_dismissed FROM cms_settings WHERE id=1")) {
  $row = $res->fetch_assoc();
  $onboardingDismissed = !empty($row['onboarding_dismissed']);
}

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
    <?php if (($_SESSION['admin']['role'] ?? '') === 'super_editor'): ?>
      <a class="bg-neutral-800 hover:bg-neutral-700 text-white px-4 py-2 rounded" href="backup.php">Backup &amp; Restore</a>
    <?php endif; ?>
    <a class="bg-rose-600 hover:bg-rose-700 text-white px-4 py-2 rounded" href="logout.php">Logout</a>
  </div>
</div>

<?php if (!$onboardingDismissed): ?>
<div id="onboardingModal" class="fixed inset-0 z-[100] flex items-center justify-center bg-black/70 p-4">
  <div class="bg-neutral-900 border border-neutral-800 rounded-lg max-w-md w-full p-6 relative">
    <div class="flex justify-center gap-2 mb-4">
      <span class="onb-dot w-2 h-2 rounded-full bg-sky-500"></span>
      <span class="onb-dot w-2 h-2 rounded-full bg-neutral-700"></span>
      <span class="onb-dot w-2 h-2 rounded-full bg-neutral-700"></span>
    </div>
    <div class="onb-slide">
      <h2 class="text-xl font-bold text-center">Welcome to CyberBlog!</h2>
      <p class="text-neutral-300 text-sm mt-3 text-center">Let's get your blog ready to go - it only takes a minute. Everything here can be changed anytime later from Settings, so feel free to skip.</p>
    </div>
    <div class="onb-slide hidden">
      <h2 class="text-xl font-bold text-center">Add Some Categories</h2>
      <p class="text-neutral-300 text-sm mt-3 text-center">Categories organize your posts and power the header's "More" menu. Create a few that fit your blog - e.g. Reviews, Tutorials, News.</p>
      <div class="text-center mt-4"><a href="manage_categories.php" class="text-sky-400 hover:underline text-sm">Go to Manage Categories &rarr;</a></div>
    </div>
    <div class="onb-slide hidden">
      <h2 class="text-xl font-bold text-center">Make It Yours</h2>
      <p class="text-neutral-300 text-sm mt-3 text-center">Set your blog's name, tagline, About page, footer text, and which categories show in the "More" menu - all from one place.</p>
      <div class="text-center mt-4"><a href="settings.php" class="text-sky-400 hover:underline text-sm">Go to Settings &rarr;</a></div>
    </div>
    <div class="flex items-center justify-between mt-6">
      <button type="button" id="onbSkip" class="text-neutral-400 hover:text-neutral-200 text-sm">Skip</button>
      <div class="flex gap-2">
        <button type="button" id="onbBack" class="hidden bg-neutral-800 hover:bg-neutral-700 text-white px-4 py-1.5 rounded text-sm">Back</button>
        <button type="button" id="onbNext" class="bg-sky-500 hover:bg-sky-600 text-white px-4 py-1.5 rounded text-sm">Next</button>
      </div>
    </div>
  </div>
</div>
<form id="onbDismissForm" method="POST" class="hidden">
  <input type="hidden" name="dismiss_onboarding" value="1">
  <input type="hidden" name="csrf" value="<?php echo e(csrf_token()); ?>">
</form>
<script>
(function(){
  const slides = document.querySelectorAll('.onb-slide');
  const dots = document.querySelectorAll('.onb-dot');
  const backBtn = document.getElementById('onbBack');
  const nextBtn = document.getElementById('onbNext');
  let idx = 0;
  function show(i){
    slides.forEach((s,j)=>s.classList.toggle('hidden', j!==i));
    dots.forEach((d,j)=>{
      d.classList.toggle('bg-sky-500', j===i);
      d.classList.toggle('bg-neutral-700', j!==i);
    });
    backBtn.classList.toggle('hidden', i===0);
    nextBtn.textContent = i===slides.length-1 ? "Got it, let's go" : 'Next';
  }
  nextBtn.addEventListener('click', ()=>{
    if (idx < slides.length-1) { idx++; show(idx); } else { document.getElementById('onbDismissForm').submit(); }
  });
  backBtn.addEventListener('click', ()=>{ if (idx>0){ idx--; show(idx); } });
  document.getElementById('onbSkip').addEventListener('click', ()=>{ document.getElementById('onbDismissForm').submit(); });
})();
</script>
<?php endif; ?>
<?php include __DIR__ . '/../includes/template_footer.php'; ?>
