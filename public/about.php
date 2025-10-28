<?php
require __DIR__ . '/../includes/db.php';
require __DIR__ . '/../includes/helpers.php';
$pageTitle = 'About Us';
$metaDescription = 'Learn more about Cyberrose Blog and our editorial team';
include __DIR__ . '/../includes/template_header.php';
?>
<h1 class="text-3xl font-bold">About Cyberrose Blog</h1>
<div class="mt-6 prose prose-invert max-w-none">
  <p class="text-lg">Welcome to Cyberrose Blog, your trusted source for insights on security integration, networking, automation, and cybersecurity.</p>
  
  <h2 class="text-2xl font-semibold mt-8">Our Mission</h2>
  <p>We are dedicated to providing high-quality, actionable content that helps professionals and enthusiasts stay ahead in the rapidly evolving world of technology and cybersecurity.</p>
  
  <h2 class="text-2xl font-semibold mt-8">Editorial Standards</h2>
  <p>Our content is carefully researched, fact-checked, and written by experienced professionals in the field. We maintain strict editorial standards to ensure accuracy and relevance.</p>
  
  
  <h2 class="text-2xl font-semibold mt-8">Topics We Cover</h2>
  <ul class="list-disc list-inside space-y-2 text-neutral-300">
    <li>Cybersecurity best practices and threat analysis</li>
    <li>Network infrastructure and security</li>
    <li>Automation and DevOps</li>
    <li>Security integration solutions</li>
    <li>Product reviews and recommendations</li>
    <li>Industry news and trends</li>
  </ul>
  
  <h2 class="text-2xl font-semibold mt-8">Contact Us</h2>
  <p>Have questions or suggestions? <a href="<?php echo base_url('public/contact.php'); ?>" class="text-sky-400 hover:underline">Get in touch with us</a>.</p>
  
  <h2 class="text-2xl font-semibold mt-8">Stay Updated</h2>
  <p>Subscribe to our newsletter to receive the latest articles and updates directly in your inbox.</p>
</div>
<?php include __DIR__ . '/../includes/template_footer.php'; ?>

