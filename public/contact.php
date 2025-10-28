<?php
require __DIR__ . '/../includes/db.php';
require __DIR__ . '/../includes/helpers.php';
$pageTitle = 'Contact Us';
$metaDescription = 'Get in touch with Cyberrose Blog';

$success = false;
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
  $name = trim($_POST['name'] ?? '');
  $email = trim($_POST['email'] ?? '');
  $subject = trim($_POST['subject'] ?? '');
  $message = trim($_POST['message'] ?? '');
  
  if (!empty($name) && !empty($email) && !empty($message) && filter_var($email, FILTER_VALIDATE_EMAIL)) {
    $stmt = $mysqli->prepare("INSERT INTO cms_contacts (name, email, subject, message) VALUES (?, ?, ?, ?)");
    $stmt->bind_param('ssss', $name, $email, $subject, $message);
    $stmt->execute();
    $stmt->close();
    
    // Also forward to Formspree (best-effort; ignore errors)
    $fsEndpoint = 'https://formspree.io/f/mkgqzlvg';
    $payload = http_build_query([
      'name' => $name,
      'email' => $email,
      'subject' => $subject,
      'message' => $message,
      '_subject' => $subject ?: ('New message from ' . $name),
      '_replyto' => $email,
    ]);
    if (function_exists('curl_init')) {
      $ch = curl_init($fsEndpoint);
      curl_setopt($ch, CURLOPT_POST, true);
      curl_setopt($ch, CURLOPT_POSTFIELDS, $payload);
      curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
      curl_setopt($ch, CURLOPT_TIMEOUT, 5);
      @curl_exec($ch);
      @curl_close($ch);
    } else {
      $opts = [
        'http' => [
          'method' => 'POST',
          'header' => "Content-type: application/x-www-form-urlencoded\r\n",
          'content' => $payload,
          'timeout' => 5,
        ]
      ];
      @file_get_contents($fsEndpoint, false, stream_context_create($opts));
    }
    
    $success = true;
  }
}

include __DIR__ . '/../includes/template_header.php';
?>
<h1 class="text-3xl font-bold">Contact Us</h1>
<p class="text-neutral-300 mt-2">Have a question or feedback? We'd love to hear from you.</p>

<?php if ($success): ?>
  <div class="mt-6 bg-green-900/30 border border-green-700 text-green-400 px-4 py-3 rounded">
    Thank you for your message! We'll get back to you soon.
  </div>
<?php endif; ?>

<form method="POST" class="mt-8 max-w-2xl grid gap-4">
  <div>
    <label class="block text-sm mb-1">Name *</label>
    <input type="text" name="name" required class="w-full rounded-md bg-neutral-950 border border-neutral-800 px-4 py-2" />
  </div>
  <div>
    <label class="block text-sm mb-1">Email *</label>
    <input type="email" name="email" required class="w-full rounded-md bg-neutral-950 border border-neutral-800 px-4 py-2" />
  </div>
  <div>
    <label class="block text-sm mb-1">Subject</label>
    <input type="text" name="subject" class="w-full rounded-md bg-neutral-950 border border-neutral-800 px-4 py-2" />
  </div>
  <div>
    <label class="block text-sm mb-1">Message *</label>
    <textarea name="message" rows="6" required class="w-full rounded-md bg-neutral-950 border border-neutral-800 px-4 py-2"></textarea>
  </div>
  <button type="submit" class="bg-sky-500 hover:bg-sky-600 text-white px-6 py-2 rounded w-fit">Send Message</button>
</form>
<?php include __DIR__ . '/../includes/template_footer.php'; ?>
