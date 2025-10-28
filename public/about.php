<?php
require __DIR__ . '/../includes/helpers.php';
$config = require __DIR__ . '/../config.php';
$version = $config['version'] ?? 'v1.0';
$donations = $config['donations'] ?? [];
$availableDonations = array_filter($donations, function($d){ return !empty($d['address']); });
$pageTitle = 'About CyberBlog';
$metaDescription = 'CyberBlog — Integrated Blog + Management System, features, version, and support';
include __DIR__ . '/../includes/template_header.php';
?>

<h1 class="text-3xl font-bold">About CyberBlog</h1>
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

  <h2 id="support" class="text-2xl font-semibold mt-8">Buy Me a Coffee</h2>
  <p>If you find CyberBlog useful, consider buying me a cup of coffee! Your support helps keep this project alive and growing.</p>

  <?php if (!empty($availableDonations)): ?>
  <div class="mt-4">
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
          ?>
          <?php if ($qr): ?>
            <img src="<?php echo e($qr); ?>" alt="Donation QR" class="w-56 h-56 object-contain bg-neutral-800 rounded" onerror="this.style.display='none';document.getElementById('qrFallback_<?php echo e($key); ?>').classList.remove('hidden');" />
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
});
</script>

<?php include __DIR__ . '/../includes/template_footer.php'; ?>

