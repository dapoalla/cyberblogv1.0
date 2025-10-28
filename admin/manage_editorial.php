<?php
require __DIR__ . '/../includes/db.php';
require __DIR__ . '/../includes/helpers.php';
require __DIR__ . '/../includes/csrf.php';
function require_admin(){ if(empty($_SESSION['admin'])){ header('Location: '.base_url('admin/login.php')); exit; } }
require_admin();

// Only super_editor can manage editorial team
$currentRole = $_SESSION['admin']['role'] ?? 'editor';
if ($currentRole !== 'super_editor') {
  http_response_code(403);
  echo 'Access denied. Only Super Editors can manage the editorial team.';
  exit;
}

$msg = '';
$error = '';

// Handle create/update
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action']) && $_POST['action'] === 'save') {
  if (csrf_check($_POST['csrf'] ?? '')) {
    $id = (int)($_POST['id'] ?? 0);
    $username = trim($_POST['username'] ?? '');
    $display_name = trim($_POST['display_name'] ?? '');
    $bio = trim($_POST['bio'] ?? '');
    $profile_image = trim($_POST['profile_image'] ?? '');
    $role = in_array($_POST['role'] ?? '', ['super_editor', 'editor', 'viewer']) ? $_POST['role'] : 'editor';
    $password = trim($_POST['password'] ?? '');
    
    if (empty($username)) {
      $error = 'Username is required';
    } else {
      // Check for duplicate username
      $checkStmt = $mysqli->prepare("SELECT id FROM cms_admin_users WHERE username=? AND id!=? LIMIT 1");
      $checkStmt->bind_param('si', $username, $id);
      $checkStmt->execute();
      $exists = $checkStmt->get_result()->fetch_assoc();
      $checkStmt->close();
      
      if ($exists) {
        $error = "Username '$username' already exists";
      } else {
        // Handle profile image upload if provided
        if (!empty($_FILES['profile_upload']['name']) && is_uploaded_file($_FILES['profile_upload']['tmp_name'])) {
          $dir = __DIR__ . '/../uploads'; if (!is_dir($dir)) { @mkdir($dir, 0775, true); }
          $ext = strtolower(pathinfo($_FILES['profile_upload']['name'], PATHINFO_EXTENSION));
          if (in_array($ext, ['jpg','jpeg','png','webp','gif'])) {
            $fname = 'editor_'.time().'_'.mt_rand(1000,9999).'.'.$ext;
            $dest = $dir . '/' . $fname;
            if (move_uploaded_file($_FILES['profile_upload']['tmp_name'], $dest)) {
              $profile_image = base_url('uploads/'.$fname);
            }
          }
        }

        if ($id) {
          // Update existing
          if (!empty($password)) {
            $hash = password_hash($password, PASSWORD_DEFAULT);
            $stmt = $mysqli->prepare("UPDATE cms_admin_users SET username=?, password_hash=?, role=?, display_name=?, bio=?, profile_image=? WHERE id=?");
            $stmt->bind_param('ssssssi', $username, $hash, $role, $display_name, $bio, $profile_image, $id);
          } else {
            $stmt = $mysqli->prepare("UPDATE cms_admin_users SET username=?, role=?, display_name=?, bio=?, profile_image=? WHERE id=?");
            $stmt->bind_param('sssssi', $username, $role, $display_name, $bio, $profile_image, $id);
          }
          $stmt->execute();
          $stmt->close();
          $msg = 'User updated successfully';
        } else {
          // Create new
          if (empty($password)) {
            $error = 'Password is required for new users';
          } else {
            $hash = password_hash($password, PASSWORD_DEFAULT);
            $stmt = $mysqli->prepare("INSERT INTO cms_admin_users (username, password_hash, role, display_name, bio, profile_image) VALUES (?, ?, ?, ?, ?, ?)");
            $stmt->bind_param('ssssss', $username, $hash, $role, $display_name, $bio, $profile_image);
            $stmt->execute();
            $stmt->close();
            $msg = 'User created successfully';
          }
        }
      }
    }
  }
}

// Handle delete
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action']) && $_POST['action'] === 'delete') {
  if (csrf_check($_POST['csrf'] ?? '')) {
    $id = (int)($_POST['id'] ?? 0);
    if ($id !== 1 && $id !== (int)($_SESSION['admin']['id'] ?? 0)) {
      $mysqli->query("DELETE FROM cms_admin_users WHERE id=$id");
      $msg = 'User deleted';
    } else {
      $error = 'Cannot delete primary admin or yourself';
    }
  }
}

// Get all editorial users
$users = [];
if ($res = $mysqli->query("SELECT * FROM cms_admin_users ORDER BY role, username")) {
  while ($row = $res->fetch_assoc()) $users[] = $row;
}

$pageTitle = 'Editorial Team Management';
include __DIR__ . '/../includes/template_header.php';
include __DIR__ . '/../includes/admin_nav.php';
?>
<h1 class="text-2xl font-bold">Editorial Team Management</h1>
<p class="text-neutral-400 text-sm mt-1">Manage editors and their access levels</p>

<?php if ($msg): ?>
  <div class="mt-4 bg-green-900/30 border border-green-700 text-green-400 px-4 py-3 rounded"><?php echo e($msg); ?></div>
<?php endif; ?>
<?php if ($error): ?>
  <div class="mt-4 bg-red-900/30 border border-red-700 text-red-400 px-4 py-3 rounded"><?php echo e($error); ?></div>
<?php endif; ?>

<div class="mt-6 bg-neutral-900 border border-neutral-800 rounded-lg p-6">
  <h2 class="text-lg font-semibold mb-4">Add New Editor</h2>
  <form method="POST" enctype="multipart/form-data" class="grid md:grid-cols-2 gap-4" id="editorForm">
    <input type="hidden" name="id" value="" />
    <div>
      <label class="block text-sm mb-1 font-semibold">Username *</label>
      <input type="text" name="username" required class="w-full rounded-md bg-neutral-950 border border-neutral-800 px-3 py-2" />
    </div>
    <div>
      <label class="block text-sm mb-1 font-semibold">Password *</label>
      <input type="password" name="password" required class="w-full rounded-md bg-neutral-950 border border-neutral-800 px-3 py-2" />
    </div>
    <div>
      <label class="block text-sm mb-1 font-semibold">Display Name</label>
      <input type="text" name="display_name" class="w-full rounded-md bg-neutral-950 border border-neutral-800 px-3 py-2" />
      <div class="text-xs text-neutral-400 mt-1">Public-facing name</div>
    </div>
    <div>
      <label class="block text-sm mb-1 font-semibold">Role *</label>
      <select name="role" required class="w-full rounded-md bg-neutral-950 border border-neutral-800 px-3 py-2">
        <option value="viewer">Viewer (Read-only)</option>
        <option value="editor" selected>Editor (Post, approve comments)</option>
        <option value="super_editor">Super Editor (Full access)</option>
      </select>
      <div class="text-xs text-neutral-400 mt-1">Access level</div>
    </div>
    <div class="md:col-span-2">
      <label class="block text-sm mb-1 font-semibold">Bio</label>
      <textarea name="bio" rows="3" class="w-full rounded-md bg-neutral-950 border border-neutral-800 px-3 py-2"></textarea>
      <div class="text-xs text-neutral-400 mt-1">Brief description for editorial team page</div>
    </div>
    <div class="md:col-span-2">
      <label class="block text-sm mb-1 font-semibold">Profile Image URL</label>
      <input type="url" name="profile_image" class="w-full rounded-md bg-neutral-950 border border-neutral-800 px-3 py-2" />
      <input type="file" name="profile_upload" accept="image/*" class="mt-2 text-sm" />
    </div>
    <input type="hidden" name="csrf" value="<?php echo e(csrf_token()); ?>">
    <input type="hidden" name="action" value="save">
    <div class="md:col-span-2">
      <button type="submit" class="bg-sky-500 hover:bg-sky-600 text-white px-6 py-2 rounded" id="editorSubmit">Add Editor</button>
    </div>
  </form>
</div>

<div class="mt-8">
  <h2 class="text-xl font-semibold mb-4">Editorial Team</h2>
  <div class="grid gap-4">
    <?php foreach ($users as $user): ?>
      <div class="bg-neutral-900 border border-neutral-800 rounded-lg p-4">
        <div class="flex items-start justify-between">
          <div class="flex gap-4 flex-1">
            <?php if (!empty($user['profile_image'])): ?>
              <img src="<?php echo e($user['profile_image']); ?>" alt="" class="w-16 h-16 rounded-full object-cover">
            <?php else: ?>
              <div class="w-16 h-16 rounded-full bg-neutral-800 flex items-center justify-center text-2xl font-bold">
                <?php echo strtoupper(substr($user['username'], 0, 1)); ?>
              </div>
            <?php endif; ?>
            <div class="flex-1">
              <div class="flex items-center gap-3">
                <h3 class="font-semibold"><?php echo e($user['display_name'] ?: $user['username']); ?></h3>
                <span class="px-2 py-0.5 rounded text-xs <?php echo $user['role']==='super_editor'?'bg-purple-900 text-purple-400':($user['role']==='editor'?'bg-blue-900 text-blue-400':'bg-neutral-700 text-neutral-400'); ?>">
                  <?php echo e(ucfirst(str_replace('_', ' ', $user['role']))); ?>
                </span>
              </div>
              <div class="text-sm text-neutral-400 mt-1">@<?php echo e($user['username']); ?></div>
              <?php if (!empty($user['bio'])): ?>
                <p class="text-sm text-neutral-300 mt-2"><?php echo e($user['bio']); ?></p>
              <?php endif; ?>
              <div class="text-xs text-neutral-500 mt-2">Joined: <?php echo e($user['created_at']); ?></div>
            </div>
          </div>
          <div class="flex gap-2 ml-4">
            <button onclick="editUser(<?php echo (int)$user['id']; ?>)" class="text-xs bg-neutral-700 hover:bg-neutral-600 text-white px-3 py-1 rounded">Edit</button>
            <?php if ($user['id'] !== 1 && $user['id'] !== (int)($_SESSION['admin']['id'] ?? 0)): ?>
              <form method="POST" class="inline" onsubmit="return confirm('Delete this user?')">
                <input type="hidden" name="csrf" value="<?php echo e(csrf_token()); ?>">
                <input type="hidden" name="id" value="<?php echo (int)$user['id']; ?>">
                <input type="hidden" name="action" value="delete">
                <button class="text-xs bg-red-600 hover:bg-red-700 text-white px-3 py-1 rounded">Delete</button>
              </form>
            <?php endif; ?>
          </div>
        </div>
      </div>
    <?php endforeach; ?>
  </div>
</div>

<div class="mt-8 bg-neutral-900 border border-neutral-800 rounded-lg p-6">
  <h3 class="font-semibold mb-3">Role Permissions</h3>
  <div class="space-y-3 text-sm">
    <div>
      <span class="px-2 py-0.5 rounded text-xs bg-purple-900 text-purple-400 font-semibold">Super Editor</span>
      <span class="text-neutral-300 ml-2">Full access - Create, edit, delete posts and comments, manage users</span>
    </div>
    <div>
      <span class="px-2 py-0.5 rounded text-xs bg-blue-900 text-blue-400 font-semibold">Editor</span>
      <span class="text-neutral-300 ml-2">Can create, edit, hide posts, approve/spam comments (cannot delete)</span>
    </div>
    <div>
      <span class="px-2 py-0.5 rounded text-xs bg-neutral-700 text-neutral-400 font-semibold">Viewer</span>
      <span class="text-neutral-300 ml-2">Read-only access to admin panel</span>
    </div>
  </div>
</div>

<script>
// Preload user data into JS map for quick editing
window.__editorUsers = {};
<?php foreach ($users as $u): ?>
window.__editorUsers[<?php echo (int)$u['id']; ?>] = {
  id: <?php echo (int)$u['id']; ?>,
  username: <?php echo json_encode($u['username']); ?>,
  display_name: <?php echo json_encode($u['display_name']); ?>,
  role: <?php echo json_encode($u['role']); ?>,
  bio: <?php echo json_encode($u['bio']); ?>,
  profile_image: <?php echo json_encode($u['profile_image']); ?>
};
<?php endforeach; ?>

function editUser(id) {
  const data = window.__editorUsers[id];
  if (!data) return;
  const f = document.getElementById('editorForm');
  f.querySelector('[name="id"]').value = data.id;
  f.querySelector('[name="username"]').value = data.username || '';
  f.querySelector('[name="password"]').value = '';
  f.querySelector('[name="display_name"]').value = data.display_name || '';
  f.querySelector('[name="role"]').value = data.role || 'editor';
  f.querySelector('[name="bio"]').value = data.bio || '';
  f.querySelector('[name="profile_image"]').value = data.profile_image || '';
  const btn = document.getElementById('editorSubmit');
  btn.textContent = 'Update Editor';
  window.scrollTo({top: 0, behavior: 'smooth'});
}
</script>
<?php include __DIR__ . '/../includes/template_footer.php'; ?>
