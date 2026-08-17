</main>
<footer class="border-t border-neutral-800 mt-12">
  <div class="max-w-screen-2xl mx-auto px-4 sm:px-6 lg:px-8 py-8">
    <div class="grid md:grid-cols-3 gap-8 text-sm">
      <div>
        <h3 class="font-semibold text-neutral-100 mb-3"><?php echo e($siteDisplayName); ?></h3>
        <p class="text-neutral-400"><?php echo e($siteSettings['site_tagline'] ?: 'Welcome to ' . $siteDisplayName . '.'); ?></p>
      </div>
      <div>
        <h3 class="font-semibold text-neutral-100 mb-3">Quick Links</h3>
        <ul class="space-y-2 text-neutral-400">
          <li><a href="<?php echo base_url('public/about.php'); ?>" class="hover:text-sky-400">About</a></li>
          <li><a href="<?php echo base_url('public/contact.php'); ?>" class="hover:text-sky-400">Contact</a></li>
          <li><a href="<?php echo base_url('public/sitemap_html.php'); ?>" class="hover:text-sky-400">Sitemap</a></li>
          <li><a href="<?php echo base_url('public/suggest_topic.php'); ?>" class="hover:text-sky-400">Suggest Topic</a></li>
          <li><a href="<?php echo base_url('admin/login.php'); ?>" class="hover:text-sky-400">Admin Login</a></li>
        </ul>
      </div>
      <div>
        <h3 class="font-semibold text-neutral-100 mb-3">Newsletter</h3>
        <p class="text-neutral-300 mb-3">Subscribe for updates</p>
        <form method="POST" action="<?php echo base_url('public/newsletter_subscribe.php'); ?>" class="flex gap-2">
          <input type="email" name="email" placeholder="Your email" required class="flex-1 rounded bg-neutral-900 border border-neutral-800 px-3 py-1.5 text-sm" />
          <button type="submit" class="bg-sky-600 hover:bg-sky-700 text-white px-3 py-1.5 rounded text-sm font-medium">Subscribe</button>
        </form>
      </div>
    </div>
    <div class="mt-8 pt-8 border-t border-neutral-800 text-center text-neutral-400 text-sm">
      <?php
        $footerTemplate = $siteSettings['footer_text'] ?: ('© {year} ' . $siteDisplayName . '. All rights reserved.');
        echo e(str_replace('{year}', date('Y'), $footerTemplate));
      ?>
    </div>
  </div>
</footer>
<script src="<?php echo base_url('assets/js/main.js'); ?>"></script>
<script>
// Theme toggle
const themeToggle = document.getElementById('themeToggle');
if (themeToggle) {
  themeToggle.addEventListener('click', () => {
    if (document.documentElement.classList.contains('grey-mode')) {
      document.documentElement.classList.remove('grey-mode');
      localStorage.theme = 'dark';
    } else {
      document.documentElement.classList.add('grey-mode');
      localStorage.theme = 'grey';
    }
  });
}

// More menu toggle (click to open, click outside to close)
(function(){
  const btn = document.getElementById('moreBtn');
  const menu = document.getElementById('moreMenu');
  if (!btn || !menu) return;
  function closeMenu(){ menu.classList.add('hidden'); btn.setAttribute('aria-expanded','false'); }
  function openMenu(){ menu.classList.remove('hidden'); btn.setAttribute('aria-expanded','true'); }
  btn.addEventListener('click', (e)=>{
    e.stopPropagation();
    if (menu.classList.contains('hidden')) openMenu(); else closeMenu();
  });
  menu.addEventListener('click', (e)=>{ e.stopPropagation(); });
  document.addEventListener('click', closeMenu);
})();
</script>
</body>
</html>
