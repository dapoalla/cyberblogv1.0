<?php
require_once __DIR__ . '/../includes/db.php';
require_once __DIR__ . '/../includes/helpers.php';
require_once __DIR__ . '/../includes/analytics.php';
cb_track_pageview($mysqli);
$pageTitle = 'About Us';
include __DIR__ . '/../includes/template_header.php';
?>
<div class="max-w-3xl mx-auto">
<h1 class="text-3xl font-bold">About <?php echo e($siteDisplayName); ?></h1>
<div class="mt-6 prose prose-invert max-w-none">
  <?php if (!empty($siteSettings['about_content_html'])): ?>
    <?php echo $siteSettings['about_content_html']; ?>
  <?php else: ?>
    <p class="text-lg">Welcome to <?php echo e($siteDisplayName); ?>.</p>
    <p class="text-neutral-400 text-sm">(The site owner hasn't customized this page yet - it can be edited from Settings.)</p>
  <?php endif; ?>
  <h2 class="text-2xl font-semibold mt-8">Contact Us</h2>
  <p>Have questions or suggestions? <a href="<?php echo base_url('public/contact.php'); ?>" class="text-sky-400 hover:underline">Get in touch with us</a>.</p>
</div>
</div>
<?php include __DIR__ . '/../includes/template_footer.php'; ?>
