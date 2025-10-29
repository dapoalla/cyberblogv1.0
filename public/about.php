<?php
require __DIR__ . '/../includes/helpers.php';
$config = require __DIR__ . '/../config.php';
$version = $config['version'] ?? 'v1.0';
$donations = $config['donations'] ?? [];
$availableDonations = array_filter($donations, function($d){ return !empty($d['address']); });
$pageTitle = 'About';
$metaDescription = 'Integrated Blog + Management System — features and support';
include __DIR__ . '/../includes/template_header.php';
?>

<?php
  // Check About enabled from settings; if disabled, redirect to home
  $cfg = require __DIR__ . '/../config.php';
  $aboutEnabled = 1;
  if (extension_loaded('mysqli') && !empty($cfg['db']['user']) && !empty($cfg['db']['name'])) {
    mysqli_report(MYSQLI_REPORT_OFF);
    $dbh = @mysqli_init();
    if ($dbh) {
      @mysqli_real_connect($dbh, $cfg['db']['host'] ?? 'localhost', $cfg['db']['user'] ?? '', $cfg['db']['pass'] ?? '', $cfg['db']['name'] ?? '', $cfg['db']['port'] ?? 3306);
      if (!$dbh->connect_errno) {
        if ($res = $dbh->query("SELECT about_enabled, site_name, github_url FROM cms_settings WHERE id=1")) {
          $row = $res->fetch_assoc();
          $aboutEnabled = isset($row['about_enabled']) ? (int)$row['about_enabled'] : 1;
          $siteName = $row['site_name'] ?? ($cfg['site_name'] ?? 'CyberBlog');
          $githubUrl = $row['github_url'] ?? '';
        }
      }
    }
  }
  if (empty($aboutEnabled)) { header('Location: '.base_url('public/index.php')); exit; }
?>

<h1 class="text-3xl font-bold">About <?php echo e($siteName ?? 'CyberBlog'); ?></h1>
<div class="mt-6 prose prose-invert max-w-none">
  <p class="text-lg">CyberBlog is an Integrated Blog + Management System designed for clarity, speed, and ease of use. It features a dark, modern UI, a guided setup wizard, and an admin dashboard for managing posts, users, comments, newsletter, and more.</p>

  <h2 class="text-2xl font-semibold mt-8">Version</h2>
  <p>CyberBlog <span class="font-semibold"><?php echo e($version); ?></span>. Visit the repository README for release notes and deployment tips.</p>

  <h2 class="text-2xl font-semibold mt-8">Core Features</h2>
  <div class="grid md:grid-cols-2 gap-6">
    <div>
      <h3 class="text-xl font-semibold">Content Management</h3>
      <ul class="list-disc list-inside space-y-2 text-neutral-300">
        <li>Manage Posts — create, edit, publish, delete</li>
        <li>Categories — organize post categories</li>
        <li>Tags — tag management for SEO and grouping</li>
      </ul>
    </div>
    <div>
      <h3 class="text-xl font-semibold">User Engagement</h3>
      <ul class="list-disc list-inside space-y-2 text-neutral-300">
        <li>Comments — review and moderate feedback</li>
        <li>Newsletter — manage subscribers</li>
        <li>Contact Messages — view contact form submissions</li>
        <li>User Management — admin/editor/viewer roles</li>
        <li>User Suggestions — collect and review ideas</li>
      </ul>
    </div>
    <div>
      <h3 class="text-xl font-semibold">System Tools</h3>
      <ul class="list-disc list-inside space-y-2 text-neutral-300">
        <li>Analytics — extensible performance insights</li>
        <li>Editorial Team — manage authors and editors</li>
        <li>Settings — site preferences and integrations</li>
      </ul>
    </div>
    <div>
      <h3 class="text-xl font-semibold">Setup & Database</h3>
      <ul class="list-disc list-inside space-y-2 text-neutral-300">
        <li>Guided Setup Wizard</li>
        <li>Initialize database (idempotent)</li>
        <li>Wipe and reinitialize `cms_*` tables</li>
        <li>Use existing database (skip schema changes)</li>
      </ul>
    </div>
  </div>

  <div class="mt-8 flex items-center gap-3">
    <button id="bmcBtn" class="bg-amber-600 hover:bg-amber-700 text-white px-4 py-2 rounded">Buy Me a Coffee</button>
    <?php if (!empty($githubUrl)): ?>
      <a href="<?php echo e($githubUrl); ?>" target="_blank" class="bg-neutral-800 hover:bg-neutral-700 text-white px-4 py-2 rounded">GitHub</a>
    <?php endif; ?>
  </div>
  <div id="bmcPanel" class="mt-4 hidden">
    <h2 id="support" class="text-2xl font-semibold">Buy Me a Coffee</h2>
    <p class="mt-2">If you find <?php echo e($siteName ?? 'CyberBlog'); ?> useful, consider buying me a cup of coffee! Your support helps keep this project alive and growing.</p>
  </div>

  <?php if (!empty($availableDonations)): ?>
  <div class="mt-4" id="donationsPanel" style="display:none">
    <div class="flex flex-wrap gap-2" id="donationTabs">
      <?php $firstKey = array_key_first($availableDonations); foreach ($availableDonations as $key => $d): ?>
        <button data-key="<?php echo e($key); ?>" class="text-xs px-3 py-1 rounded border <?php echo $key===$firstKey ? 'bg-neutral-800 border-neutral-700' : 'bg-neutral-900 border-neutral-800'; ?> hover:bg-neutral-700">
          <?php echo e($donations[$key]['label'] ?? $key); ?>
        </button>
      <?php endforeach; ?>
    </div>

    <?php foreach ($availableDonations as $key => $d): $isFirst = ($key === $firstKey); ?>
      <div class="mt-4 grid md:grid-cols-2 gap-6 items-start donation-panel" data-key="<?php echo e($key); ?>" style="<?php echo $isFirst ? '' : 'display:none'; ?>">
        <div class="bg-neutral-900 border border-neutral-800 rounded p-4">
          <div class="text-sm text-neutral-400 mb-2">Scan to Copy Address</div>
          <?php 
            $qr = trim($d['qr'] ?? ''); 
            $addr = trim($d['address'] ?? '');
            if (!$qr && $addr) { 
              $qr = 'https://api.qrserver.com/v1/create-qr-code/?size=224x224&data=' . urlencode($addr);
            }
            // If a local path is provided (e.g., assets/images/...), prefix with base URL
            $qrSrc = $qr;
            if ($qr && !preg_match('~^https?://~i', $qr)) {
              $qrSrc = base_url(ltrim($qr, '/'));
            }
          ?>
          <?php if ($qr): ?>
            <img src="<?php echo e($qrSrc); ?>" alt="Donation QR" class="w-56 h-56 object-contain bg-neutral-800 rounded" onerror="this.style.display='none';document.getElementById('qrFallback_<?php echo e($key); ?>').classList.remove('hidden');" />
          <?php endif; ?>
          <div id="qrFallback_<?php echo e($key); ?>" class="<?php echo $qr ? 'hidden' : ''; ?> text-xs text-neutral-400 mt-2">QR image unavailable. The wallet address is shown below for manual copy.</div>
        </div>
        <div class="bg-neutral-900 border border-neutral-800 rounded p-4">
          <div class="text-sm text-neutral-400">Method:&nbsp; <span class="text-neutral-200"><?php echo e($d['label'] ?? $key); ?></span></div>
          <?php if (!empty($d['networks']) && is_array($d['networks'])): ?>
            <div class="text-xs text-neutral-400 mt-1">Networks: <span class="text-neutral-200"><?php echo e(implode(', ', $d['networks'])); ?></span></div>
          <?php endif; ?>
          <div class="mt-3 text-sm font-semibold">Wallet Address</div>
          <div class="mt-2 flex items-center gap-2">
            <code class="text-xs break-all bg-neutral-800 px-2 py-1 rounded" id="addr_<?php echo e($key); ?>"><?php echo e($d['address']); ?></code>
            <button data-target="addr_<?php echo e($key); ?>" class="copyAddr text-xs bg-neutral-800 hover:bg-neutral-700 px-2 py-1 rounded">📋 Copy</button>
          </div>
          <?php if (!empty($d['warning'])): ?><div class="mt-3 text-amber-400 text-sm">⚠️ <?php echo e($d['warning']); ?> Other assets sent to this address may be lost.</div><?php endif; ?>
        </div>
      </div>
    <?php endforeach; ?>
  </div>
  <?php else: ?>
    <div class="mt-3 text-neutral-400 text-sm">No donation methods configured. Add addresses in <code>config.php</code> under <code>donations</code>.</div>
  <?php endif; ?>

  <h2 class="text-2xl font-semibold mt-8">Contact</h2>
  <p>Have questions or suggestions? <a href="<?php echo base_url('public/contact.php'); ?>" class="text-sky-400 hover:underline">Get in touch</a>.</p>
</div>

<script>
// Tabs + copy handlers
document.addEventListener('DOMContentLoaded', function(){
  var tabs = document.querySelectorAll('#donationTabs button');
  var panels = document.querySelectorAll('.donation-panel');
  tabs.forEach(function(tab){
    tab.addEventListener('click', function(){
      var key = tab.getAttribute('data-key');
      // Toggle tab styles
      tabs.forEach(function(t){ t.classList.remove('bg-neutral-800','border-neutral-700'); t.classList.add('bg-neutral-900','border-neutral-800'); });
      tab.classList.add('bg-neutral-800','border-neutral-700');
      // Show selected panel
      panels.forEach(function(p){ p.style.display = (p.getAttribute('data-key') === key) ? '' : 'none'; });
    });
  });
  document.querySelectorAll('.copyAddr').forEach(function(btn){
    btn.addEventListener('click', function(){
      var targetId = btn.getAttribute('data-target');
      var el = document.getElementById(targetId);
      if (!el) return;
      var addr = el.textContent.trim();
      navigator.clipboard.writeText(addr).then(function(){
        btn.textContent = '✅ Copied';
        setTimeout(function(){ btn.textContent = '📋 Copy'; }, 1500);
      }).catch(function(){
        alert('Copy failed. Please copy the address manually.');
      });
    });
  });
  var bmcBtn = document.getElementById('bmcBtn');
  var bmcPanel = document.getElementById('bmcPanel');
  var donationsPanel = document.getElementById('donationsPanel');
  if (bmcBtn) {
    bmcBtn.addEventListener('click', function(){
      if (bmcPanel) bmcPanel.classList.remove('hidden');
      if (donationsPanel) donationsPanel.style.display = '';
    });
  }
});
</script>

<?php include __DIR__ . '/../includes/template_footer.php'; ?>

