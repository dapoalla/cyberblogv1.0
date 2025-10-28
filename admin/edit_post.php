<?php
require __DIR__ . '/../includes/db.php';
require __DIR__ . '/../includes/helpers.php';
require __DIR__ . '/../includes/csrf.php';
function require_admin(){ if(empty($_SESSION['admin'])){ header('Location: '.base_url('admin/login.php')); exit; } }
require_admin();

$id = isset($_GET['id']) ? (int)$_GET['id'] : 0;
$post = ['title'=>'','slug'=>'','excerpt'=>'','content_html'=>'','cover_image'=>'','category_id'=>null,'status'=>'draft','published_at'=>'','meta_title'=>'','meta_description'=>'','og_image'=>'','related_post_ids'=>'','author_name'=>''];

// Load categories for select
$cats=[]; if ($res=$mysqli->query("SELECT id,name FROM cms_categories ORDER BY name")) { while ($r=$res->fetch_assoc()) $cats[]=$r; }

// Load editorial team for author selection
$teamEditors=[]; if ($res=$mysqli->query("SELECT id, username, COALESCE(display_name, username) AS name FROM cms_admin_users WHERE role IN ('super_editor','editor') ORDER BY role, username")) { while ($r=$res->fetch_assoc()) $teamEditors[]=$r; }

// Load existing post
if ($id) {
  $stmt = $mysqli->prepare("SELECT * FROM cms_posts WHERE id=? LIMIT 1");
  $stmt->bind_param('i',$id);
  $stmt->execute();
  $row = $stmt->get_result()->fetch_assoc();
  $stmt->close();
  if ($row) $post = $row; else { http_response_code(404); echo 'Post not found'; exit; }
}

// Internal linking helper: recent posts list
$linkPosts=[]; if ($res=$mysqli->query("SELECT id,title,slug FROM cms_posts ORDER BY id DESC LIMIT 50")) { while($r=$res->fetch_assoc()) $linkPosts[]=$r; }

// All posts for related posts selection
$allPosts=[]; if ($res=$mysqli->query("SELECT id,title FROM cms_posts WHERE status='published' ORDER BY title LIMIT 200")) { while($r=$res->fetch_assoc()) $allPosts[]=$r; }

$msg='';
if ($_SERVER['REQUEST_METHOD']==='POST' && csrf_check($_POST['csrf']??'')) {
  $post['title'] = trim($_POST['title'] ?? '');
  $post['slug'] = trim($_POST['slug'] ?? '') ?: slugify($post['title']);
  $post['excerpt'] = trim($_POST['excerpt'] ?? '');
  $post['content_html'] = (string)($_POST['content_html'] ?? '');
  $post['cover_image'] = trim($_POST['cover_image'] ?? '');
  $post['category_id'] = !empty($_POST['category_id']) ? (int)$_POST['category_id'] : null;
  $post['status'] = in_array(($_POST['status'] ?? 'draft'), ['draft','published','scheduled'], true) ? $_POST['status'] : 'draft';
  $post['published_at'] = trim($_POST['published_at'] ?? '');
  if (strpos($post['published_at'],'T')!==false) $post['published_at']=str_replace('T',' ',$post['published_at']);
  
  // Auto-set published_at to NOW if changing to published and no date set
  if ($post['status'] === 'published' && empty($post['published_at'])) {
    $post['published_at'] = date('Y-m-d H:i:s');
  }
  $post['meta_title'] = trim($_POST['meta_title'] ?? '');
  $post['meta_description'] = trim($_POST['meta_description'] ?? '');
  $post['og_image'] = trim($_POST['og_image'] ?? '');
  $post['related_post_ids'] = isset($_POST['related_posts']) ? implode(',', array_filter(array_map('intval', $_POST['related_posts']))) : '';
  // Determine author name based on selection
  $authorOption = $_POST['author_option'] ?? 'none';
  $post['author_name'] = '';
  if ($authorOption === 'current') {
    $aid = (int)($_SESSION['admin']['id'] ?? 0);
    if ($aid) {
      $st=$mysqli->prepare("SELECT COALESCE(display_name, username) AS name FROM cms_admin_users WHERE id=? LIMIT 1");
      $st->bind_param('i',$aid); $st->execute(); $row=$st->get_result()->fetch_assoc(); $st->close();
      $post['author_name'] = $row['name'] ?? '';
    }
  } elseif ($authorOption === 'team') {
    $sel = (int)($_POST['author_team_id'] ?? 0);
    if ($sel) {
      $st=$mysqli->prepare("SELECT COALESCE(display_name, username) AS name FROM cms_admin_users WHERE id=? LIMIT 1");
      $st->bind_param('i',$sel); $st->execute(); $row=$st->get_result()->fetch_assoc(); $st->close();
      $post['author_name'] = $row['name'] ?? '';
    }
  } elseif ($authorOption === 'custom') {
    $post['author_name'] = trim($_POST['author_custom'] ?? '');
  } // 'none' leaves blank

  // Cover upload
  if (!empty($_FILES['cover_upload']['name']) && is_uploaded_file($_FILES['cover_upload']['tmp_name'])) {
    $dir = __DIR__ . '/../uploads'; if (!is_dir($dir)) { @mkdir($dir, 0775, true); }
    $ext = strtolower(pathinfo($_FILES['cover_upload']['name'], PATHINFO_EXTENSION));
    if (in_array($ext,['jpg','jpeg','png','webp','gif'])) {
      $fname = 'cover_'.time().'_'.mt_rand(1000,9999).'.'.$ext;
      $dest = $dir.'/'.$fname;
      if (move_uploaded_file($_FILES['cover_upload']['tmp_name'],$dest)) {
        $post['cover_image'] = base_url('uploads/'.$fname);
      }
    }
  }

  if ($id) {
    $stmt = $mysqli->prepare("UPDATE cms_posts SET title=?, slug=?, excerpt=?, content_html=?, cover_image=?, category_id=?, status=?, published_at=IF(?='', NULL, ?), meta_title=?, meta_description=?, og_image=?, related_post_ids=?, author_name=?, updated_at=NOW() WHERE id=?");
    // Types: s,s,s,s,s, i, s, s, s, s, s, s, s, s, i  => 'sssssissssssssi'
    $stmt->bind_param('sssssissssssssi', $post['title'],$post['slug'],$post['excerpt'],$post['content_html'],$post['cover_image'],$post['category_id'],$post['status'],$post['published_at'],$post['published_at'],$post['meta_title'],$post['meta_description'],$post['og_image'],$post['related_post_ids'],$post['author_name'],$id);
    $stmt->execute(); $stmt->close();
    $msg='Post updated.';
  } else {
    $stmt = $mysqli->prepare("INSERT INTO cms_posts (title, slug, excerpt, content_html, cover_image, category_id, status, published_at, created_at, meta_title, meta_description, og_image, related_post_ids, author_name) VALUES (?,?,?,?,?,?,?,?,NOW(),?,?,?,?,?)");
    $stmt->bind_param('sssssisssssss', $post['title'],$post['slug'],$post['excerpt'],$post['content_html'],$post['cover_image'],$post['category_id'],$post['status'],$post['published_at'],$post['meta_title'],$post['meta_description'],$post['og_image'],$post['related_post_ids'],$post['author_name']);
    $stmt->execute(); $id = $stmt->insert_id; $stmt->close();
    header('Location: '.base_url('admin/edit_post.php?id='.$id.'&saved=1'));
    exit;
  }
}

$pageTitle = $id? 'Edit Post' : 'New Post';
include __DIR__ . '/../includes/template_header.php';
include __DIR__ . '/../includes/admin_nav.php';
?>
<div class="flex items-center justify-between">
  <h1 class="text-2xl font-bold"><?php echo $id? 'Edit Post':'New Post'; ?></h1>
  <a class="text-sky-400 hover:underline" href="manage_posts.php">Back to Posts</a>
</div>
<?php if ($msg || isset($_GET['saved'])): ?><div class="mt-3 text-green-400 text-sm"><?php echo e($msg?:'Post saved.'); ?></div><?php endif; ?>
<form method="POST" enctype="multipart/form-data" class="mt-6 grid gap-4">
  <div>
    <label class="block text-sm mb-1 font-semibold">Title *</label>
    <input name="title" value="<?php echo e($post['title']); ?>" required class="w-full rounded-md bg-neutral-950 border border-neutral-800 px-3 py-2" />
    <div class="text-xs text-neutral-400 mt-1">The main title of your post (required)</div>
  </div>
  <div>
    <label class="block text-sm mb-1 font-semibold">Slug</label>
    <input name="slug" value="<?php echo e($post['slug']); ?>" class="w-full rounded-md bg-neutral-950 border border-neutral-800 px-3 py-2" />
    <div class="text-xs text-neutral-400 mt-1">URL-friendly version of the title (auto-generated if empty)</div>
  </div>
  <div>
    <label class="block text-sm mb-1 font-semibold">Excerpt</label>
    <textarea name="excerpt" rows="3" class="w-full rounded-md bg-neutral-950 border border-neutral-800 px-3 py-2"><?php echo e($post['excerpt']); ?></textarea>
    <div class="text-xs text-neutral-400 mt-1">Short summary shown in post listings and search results</div>
  </div>
  <div>
    <label class="block text-sm mb-1 font-semibold">Content *</label>
    <textarea id="editor" name="content_html" rows="16" class="w-full rounded-md bg-neutral-950 border border-neutral-800 px-3 py-2"><?php echo e($post['content_html']); ?></textarea>
    <div class="text-xs text-neutral-400 mt-1">Main article content with rich text formatting</div>
  </div>
  <div class="grid md:grid-cols-2 gap-4">
    <div>
      <label class="block text-sm mb-1 font-semibold">Cover Image URL</label>
      <input name="cover_image" value="<?php echo e($post['cover_image']); ?>" class="w-full rounded-md bg-neutral-950 border border-neutral-800 px-3 py-2" />
      <div class="text-xs text-neutral-400 mt-1">Featured image URL or upload below (jpg, png, webp, gif)</div>
      <input type="file" name="cover_upload" accept="image/*" class="mt-2 text-sm" />
    </div>
    <div>
      <label class="block text-sm mb-1 font-semibold">Category</label>
      <select name="category_id" class="w-full rounded-md bg-neutral-950 border border-neutral-800 px-3 py-2">
        <option value="">-- None --</option>
        <?php foreach($cats as $c): ?>
          <option value="<?php echo (int)$c['id']; ?>" <?php echo ((int)($post['category_id']??0)===(int)$c['id'])?'selected':''; ?>><?php echo e($c['name']); ?></option>
        <?php endforeach; ?>
      </select>
      <div class="text-xs text-neutral-400 mt-1">Organize posts by topic or theme</div>
    </div>
  </div>
  <div class="grid md:grid-cols-3 gap-4">
    <div>
      <label class="block text-sm mb-1 font-semibold">Status</label>
      <select name="status" class="w-full rounded-md bg-neutral-950 border border-neutral-800 px-3 py-2">
        <option value="draft" <?php echo $post['status']==='draft'?'selected':''; ?>>draft</option>
        <option value="published" <?php echo $post['status']==='published'?'selected':''; ?>>published</option>
        <option value="scheduled" <?php echo $post['status']==='scheduled'?'selected':''; ?>>scheduled</option>
      </select>
      <div class="text-xs text-neutral-400 mt-1">Draft, published, or scheduled for future</div>
    </div>
    <div>
      <label class="block text-sm mb-1 font-semibold">Publish At</label>
      <?php $dtVal = $post['published_at'] ? str_replace(' ','T', substr($post['published_at'],0,16)) : ''; ?>
      <input type="datetime-local" name="published_at" value="<?php echo e($dtVal); ?>" class="w-full rounded-md bg-neutral-950 border border-neutral-800 px-3 py-2" />
      <div class="text-xs text-neutral-400 mt-1">Schedule publication date/time (optional)</div>
    </div>
  </div>
  <div class="mt-2 bg-neutral-900 border border-neutral-800 rounded p-4">
    <label class="block text-sm mb-2 font-semibold">Author</label>
    <?php $hasAuthor = !empty($post['author_name']); ?>
    <div class="space-y-2 text-sm">
      <label class="flex items-center gap-2"><input type="radio" name="author_option" value="none" <?php echo !$hasAuthor? 'checked' : ''; ?> /> <span>No author</span></label>
      <label class="flex items-center gap-2"><input type="radio" name="author_option" value="current" /> <span>Use current editor</span></label>
      <div class="flex items-center gap-2">
        <label class="flex items-center gap-2"><input type="radio" name="author_option" value="team" /> <span>Select from team</span></label>
        <select name="author_team_id" class="rounded-md bg-neutral-950 border border-neutral-800 px-3 py-2">
          <option value="">-- Choose editor --</option>
          <?php foreach($teamEditors as $ed): ?>
            <option value="<?php echo (int)$ed['id']; ?>"><?php echo e($ed['name']); ?></option>
          <?php endforeach; ?>
        </select>
      </div>
      <div class="flex items-center gap-2">
        <label class="flex items-center gap-2"><input type="radio" name="author_option" value="custom" <?php echo $hasAuthor? 'checked' : ''; ?> /> <span>Custom name</span></label>
        <input type="text" name="author_custom" value="<?php echo e($post['author_name']); ?>" class="flex-1 rounded-md bg-neutral-950 border border-neutral-800 px-3 py-2" placeholder="e.g., CyberRose Editorial" />
      </div>
    </div>
  </div>
  <div class="grid md:grid-cols-3 gap-4">
    <div>
      <label class="block text-sm mb-1 font-semibold">Meta Title</label>
      <input name="meta_title" value="<?php echo e($post['meta_title']); ?>" class="w-full rounded-md bg-neutral-950 border border-neutral-800 px-3 py-2" />
      <div class="text-xs text-neutral-400 mt-1">SEO title for search engines (defaults to post title)</div>
    </div>
    <div>
      <label class="block text-sm mb-1 font-semibold">Meta Description</label>
      <input name="meta_description" value="<?php echo e($post['meta_description']); ?>" class="w-full rounded-md bg-neutral-950 border border-neutral-800 px-3 py-2" />
      <div class="text-xs text-neutral-400 mt-1">SEO description for search results (defaults to excerpt)</div>
    </div>
    <div>
      <label class="block text-sm mb-1 font-semibold">OG Image URL</label>
      <input name="og_image" value="<?php echo e($post['og_image']); ?>" class="w-full rounded-md bg-neutral-950 border border-neutral-800 px-3 py-2" />
      <div class="text-xs text-neutral-400 mt-1">Social media share image (defaults to cover image)</div>
    </div>
  </div>
  <div>
    <label class="block text-sm mb-1 font-semibold">Related Posts</label>
    <select multiple name="related_posts[]" size="5" class="w-full rounded-md bg-neutral-950 border border-neutral-800 px-3 py-2">
      <?php 
        $selectedIds = !empty($post['related_post_ids']) ? explode(',', $post['related_post_ids']) : [];
        foreach($allPosts as $ap): 
          if ($ap['id'] != $id): // Don't show current post
      ?>
        <option value="<?php echo (int)$ap['id']; ?>" <?php echo in_array((string)$ap['id'], $selectedIds) ? 'selected' : ''; ?>><?php echo e($ap['title']); ?></option>
      <?php endif; endforeach; ?>
    </select>
    <div class="text-xs text-neutral-400 mt-1">Select up to 3 related posts to display at the end of this article (hold Ctrl/Cmd to select multiple)</div>
  </div>
  <div>
    <label class="block text-sm mb-1 font-semibold">Internal Linking Helper</label>
    <select id="linkHelper" class="w-full rounded-md bg-neutral-950 border border-neutral-800 px-3 py-2">
      <option value="">-- Select a post to copy its URL --</option>
      <?php foreach($linkPosts as $lp): ?>
        <option value="<?php echo e(base_url('public/post.php?slug='.$lp['slug'])); ?>"><?php echo e($lp['title']); ?></option>
      <?php endforeach; ?>
    </select>
    <div class="text-xs text-neutral-400 mt-1">Quick tool to copy post URLs for internal linking in your content</div>
  </div>
  <input type="hidden" name="csrf" value="<?php echo e(csrf_token()); ?>" />
  <button class="mt-2 bg-sky-500 hover:bg-sky-600 text-white px-4 py-2 rounded"><?php echo $id? 'Update Post':'Create Post'; ?></button>
</form>
<script src="https://cdn.tiny.cloud/1/n5zu2xqrrgef0qnchz89hmacpyww0dsa0hwrdtwmueqfqx90/tinymce/6/tinymce.min.js" referrerpolicy="origin"></script>
<script>
  tinymce.init({
    selector:'#editor',
    plugins:'link image media lists table code',
    menubar:false,
    toolbar:'undo redo | styles | bold italic underline | alignleft aligncenter alignright | bullist numlist | link image media | table | code',
    height: 500,
    convert_urls:false,
  });
  const helper=document.getElementById('linkHelper');
  helper && helper.addEventListener('change',()=>{ if(helper.value){ navigator.clipboard.writeText(helper.value); alert('Copied to clipboard: '+helper.value); helper.selectedIndex=0; } });
</script>
<?php include __DIR__ . '/../includes/template_footer.php'; ?>
