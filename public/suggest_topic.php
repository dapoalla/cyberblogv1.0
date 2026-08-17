<?php
require __DIR__ . '/../includes/db.php';
require __DIR__ . '/../includes/helpers.php';
session_name('cr_blog2_pub'); if(session_status()===PHP_SESSION_NONE) session_start();

$pageTitle = 'Suggest a Topic';
$metaDescription = 'Suggest topics you\'d like us to cover';

$success = false;
$error = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
  if (empty($_SESSION['pub_user'])) {
    $_SESSION['after_login_redirect'] = base_url('public/suggest_topic.php');
    header('Location: ' . base_url('comments/google_auth.php'));
    exit;
  }
  
  $type = in_array($_POST['type'] ?? '', ['topic', 'feature', 'feedback']) ? $_POST['type'] : 'topic';
  $title = trim($_POST['title'] ?? '');
  $description = trim($_POST['description'] ?? '');
  
  if (!empty($title) && !empty($description)) {
    $uid = (int)$_SESSION['pub_user']['id'];
    $stmt = $mysqli->prepare("INSERT INTO cms_user_suggestions (oauth_user_id, suggestion_type, title, description) VALUES (?, ?, ?, ?)");
    $stmt->bind_param('isss', $uid, $type, $title, $description);
    $stmt->execute();
    $stmt->close();
    $success = true;
  } else {
    $error = 'Please fill in all fields';
  }
}

include __DIR__ . '/../includes/template_header.php';
?>
<h1 class="text-3xl font-bold">Suggest a Topic</h1>
<p class="text-neutral-300 mt-2">Have an idea for content you'd like to see? Let us know!</p>

<?php if (!empty($_SESSION['pub_user'])): ?>
  <?php if ($success): ?>
    <div class="mt-6 bg-green-900/30 border border-green-700 text-green-400 px-4 py-3 rounded">
      Thank you for your suggestion! We'll review it and consider it for future content.
    </div>
  <?php endif; ?>
  
  <?php if ($error): ?>
    <div class="mt-6 bg-red-900/30 border border-red-700 text-red-400 px-4 py-3 rounded">
      <?php echo e($error); ?>
    </div>
  <?php endif; ?>
  
  <form method="POST" class="mt-8 max-w-2xl grid gap-4">
    <div>
      <label class="block text-sm mb-1 font-semibold">Type</label>
      <select name="type" required class="w-full rounded-md bg-neutral-950 border border-neutral-800 px-4 py-2">
        <option value="topic">Topic Suggestion</option>
        <option value="feature">Feature Request</option>
        <option value="feedback">General Feedback</option>
      </select>
      <div class="text-xs text-neutral-400 mt-1">What kind of suggestion is this?</div>
    </div>
    
    <div>
      <label class="block text-sm mb-1 font-semibold">Title *</label>
      <input type="text" name="title" required class="w-full rounded-md bg-neutral-950 border border-neutral-800 px-4 py-2" placeholder="Brief title for your suggestion" />
    </div>
    
    <div>
      <label class="block text-sm mb-1 font-semibold">Description *</label>
      <textarea name="description" rows="6" required class="w-full rounded-md bg-neutral-950 border border-neutral-800 px-4 py-2" placeholder="Tell us more about your idea..."></textarea>
      <div class="text-xs text-neutral-400 mt-1">Provide as much detail as possible</div>
    </div>
    
    <button type="submit" class="bg-sky-500 hover:bg-sky-600 text-white px-6 py-2 rounded w-fit">Submit Suggestion</button>
  </form>
  
  <div class="mt-8 p-4 bg-neutral-900 border border-neutral-800 rounded">
    <h3 class="font-semibold mb-2">Signed in as:</h3>
    <div class="flex items-center gap-3">
      <?php if (!empty($_SESSION['pub_user']['picture'])): ?>
        <img src="<?php echo e($_SESSION['pub_user']['picture']); ?>" alt="" class="w-10 h-10 rounded-full">
      <?php endif; ?>
      <div>
        <div class="font-medium"><?php echo e($_SESSION['pub_user']['name'] ?? 'User'); ?></div>
        <div class="text-sm text-neutral-400"><?php echo e($_SESSION['pub_user']['email'] ?? ''); ?></div>
      </div>
    </div>
  </div>
<?php else: ?>
  <div class="mt-8 max-w-2xl">
    <div class="bg-neutral-900 border border-neutral-800 rounded-lg p-6 text-center">
      <p class="text-neutral-300 mb-4">Please sign in with Google to submit suggestions</p>
      <a href="<?php echo base_url('comments/google_auth.php'); ?>" class="inline-flex items-center gap-2 bg-sky-500 hover:bg-sky-600 text-white px-6 py-3 rounded">
        Sign in with Google
      </a>
    </div>
  </div>
<?php endif; ?>

<?php include __DIR__ . '/../includes/template_footer.php'; ?>
