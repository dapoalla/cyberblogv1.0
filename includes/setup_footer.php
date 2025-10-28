</main>
<footer class="border-t border-neutral-800 mt-12">
  <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 py-8">
    <div class="grid md:grid-cols-3 gap-8 text-sm">
      <div>
        <h3 class="font-semibold text-neutral-100 mb-3">Cyberrose Blog</h3>
        <p class="text-neutral-400">Setup Wizard to configure your blog quickly and safely.</p>
      </div>
      <div>
        <h3 class="font-semibold text-neutral-100 mb-3">Links</h3>
        <ul class="space-y-2 text-neutral-400">
          <li><a href="<?php echo base_url('public/index.php'); ?>" class="hover:text-sky-400">Blog Home</a></li>
          <li><a href="<?php echo base_url('admin/login.php'); ?>" class="hover:text-sky-400">Admin Login</a></li>
        </ul>
      </div>
      <div>
        <h3 class="font-semibold text-neutral-100 mb-3">Theme</h3>
        <p class="text-neutral-300 mb-3">Use the same cute UI as the blog.</p>
        <button id="themeToggle" class="bg-sky-600 hover:bg-sky-700 text-white px-3 py-1.5 rounded text-sm font-medium">Toggle Theme</button>
      </div>
    </div>
    <div class="mt-8 pt-8 border-t border-neutral-800 text-center text-neutral-400 text-sm">
      © <span id="year"></span> CyberRose Systems. All rights reserved.
    </div>
  </div>
</footer>
<script>document.getElementById('year').textContent=new Date().getFullYear();</script>
<script src="<?php echo base_url('assets/js/main.js'); ?>"></script>
<script>
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
</script>
</body>
</html>