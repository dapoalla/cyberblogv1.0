<?php
require __DIR__ . '/../includes/db.php';
require __DIR__ . '/../includes/helpers.php';
$pageTitle = 'Editorial Team';
$metaDescription = 'Meet the editorial team behind Cyberrose Blog';

// Get all editors with public profiles
$team = [];
if ($res = $mysqli->query("SELECT display_name, username, bio, profile_image, role 
                           FROM cms_admin_users 
                           WHERE role IN ('super_editor', 'editor') 
                           ORDER BY FIELD(role, 'super_editor', 'editor'), username")) {
  while ($row = $res->fetch_assoc()) {
    if (!empty($row['display_name']) || !empty($row['bio'])) {
      $team[] = $row;
    }
  }
}

include __DIR__ . '/../includes/template_header.php';
?>
<h1 class="text-3xl font-bold">Editorial Team</h1>
<p class="text-neutral-300 mt-2">Meet the experts behind Cyberrose Blog</p>

<div class="mt-8 grid md:grid-cols-2 gap-6">
  <?php foreach ($team as $member): ?>
    <div class="bg-neutral-900 border border-neutral-800 rounded-lg p-6">
      <div class="flex gap-4">
        <?php if (!empty($member['profile_image'])): ?>
          <img src="<?php echo e($member['profile_image']); ?>" alt="<?php echo e($member['display_name'] ?: $member['username']); ?>" class="w-20 h-20 rounded-full object-cover">
        <?php else: ?>
          <div class="w-20 h-20 rounded-full bg-neutral-800 flex items-center justify-center text-3xl font-bold">
            <?php echo strtoupper(substr($member['display_name'] ?: $member['username'], 0, 1)); ?>
          </div>
        <?php endif; ?>
        <div class="flex-1">
          <h3 class="text-xl font-semibold"><?php echo e($member['display_name'] ?: $member['username']); ?></h3>
          <div class="text-sm text-neutral-400 mt-1">
            <?php echo $member['role'] === 'super_editor' ? 'Chief Editor' : 'Editor'; ?>
          </div>
          <?php if (!empty($member['bio'])): ?>
            <p class="text-neutral-300 mt-3 text-sm"><?php echo nl2br(e($member['bio'])); ?></p>
          <?php endif; ?>
        </div>
      </div>
    </div>
  <?php endforeach; ?>
</div>

<?php if (empty($team)): ?>
  <div class="mt-8 text-center text-neutral-400">
    <p>Our editorial team information will be available soon.</p>
  </div>
<?php endif; ?>

<div class="mt-12 bg-neutral-900 border border-neutral-800 rounded-lg p-6">
  <h2 class="text-xl font-semibold mb-3">Join Our Team</h2>
  <p class="text-neutral-300">Interested in contributing to Cyberrose Blog? We're always looking for passionate writers and experts in cybersecurity, networking, and technology.</p>
  <a href="<?php echo base_url('public/contact.php'); ?>" class="inline-block mt-4 bg-sky-500 hover:bg-sky-600 text-white px-6 py-2 rounded">Get in Touch</a>
</div>
<?php include __DIR__ . '/../includes/template_footer.php'; ?>
