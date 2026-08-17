<?php
// Admin navigation helper - include at top of admin pages
if (empty($_SESSION['admin'])) {
  header('Location: ' . base_url('admin/login.php'));
  exit;
}
?>
<div class="bg-neutral-900 border-b border-neutral-800 mb-6 -mx-4 sm:-mx-6 lg:-mx-8 px-4 sm:px-6 lg:px-8 py-3">
  <div class="flex items-center justify-between">
    <div class="flex items-center gap-3">
      <a href="<?php echo base_url('admin/index.php'); ?>" class="inline-flex items-center gap-2 text-sm text-neutral-300 hover:text-sky-400">
        <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
          <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 12l2-2m0 0l7-7 7 7M5 10v10a1 1 0 001 1h3m10-11l2 2m-2-2v10a1 1 0 01-1 1h-3m-6 0a1 1 0 001-1v-4a1 1 0 011-1h2a1 1 0 011 1v4a1 1 0 001 1m-6 0h6"></path>
        </svg>
        Dashboard
      </a>
      <span class="text-neutral-600">|</span>
      <a href="<?php echo base_url('public/index.php'); ?>" class="inline-flex items-center gap-2 text-sm text-neutral-300 hover:text-sky-400" target="_blank">
        <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
          <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M21 12a9 9 0 01-9 9m9-9a9 9 0 00-9-9m9 9H3m9 9a9 9 0 01-9-9m9 9c1.657 0 3-4.03 3-9s-1.343-9-3-9m0 18c-1.657 0-3-4.03-3-9s1.343-9 3-9m-9 9a9 9 0 019-9"></path>
        </svg>
        View Blog
      </a>
    </div>
    <div class="text-sm text-neutral-400">
      <?php echo e($_SESSION['admin']['username'] ?? 'Admin'); ?>
      <?php if (!empty($_SESSION['admin']['role'])): ?>
        <span class="ml-2 px-2 py-0.5 rounded text-xs bg-neutral-800 text-neutral-300">
          <?php echo e(ucfirst(str_replace('_', ' ', $_SESSION['admin']['role']))); ?>
        </span>
      <?php endif; ?>
    </div>
  </div>
</div>
