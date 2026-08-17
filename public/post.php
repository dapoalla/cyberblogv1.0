<?php
require __DIR__ . '/../includes/db.php';
require __DIR__ . '/../includes/helpers.php';
$__sess_started = false;
if (session_status() === PHP_SESSION_NONE) {
  session_name('cr_blog2_pub');
  session_start();
  $__sess_started = true;
}
$slug = $_GET['slug'] ?? '';
$stmt=$mysqli->prepare("SELECT p.*,c.name AS category_name,c.slug AS category_slug FROM cms_posts p LEFT JOIN cms_categories c ON c.id=p.category_id WHERE p.slug=? AND p.status='published' LIMIT 1");
$stmt->bind_param('s',$slug);$stmt->execute();$post=$stmt->get_result()->fetch_assoc();$stmt->close();
if(!$post){ http_response_code(404); echo 'Post not found'; exit; }
$pageTitle=$post['meta_title']?:$post['title'];
$metaDescription=$post['meta_description']?:($post['excerpt']?:'');
// views++
$inc=$mysqli->prepare("UPDATE cms_posts SET views=views+1 WHERE id=?"); $inc->bind_param('i',$post['id']); $inc->execute(); $inc->close();
include __DIR__ . '/../includes/template_header.php';
?>
  <h1 class="text-3xl font-bold"><?php echo e($post['title']); ?></h1>
  <?php
    $byline = [];
    if (!empty($post['author_name'])) {
      // Wrap author name for hover popout
      $authorSafe = e($post['author_name']);
      $byline[] = 'By <span class="editor-hover text-sky-400 hover:underline cursor-pointer" data-editor-name="'.htmlspecialchars($post['author_name'], ENT_QUOTES).'">'.$authorSafe.'</span>';
    }
    $pubDt = $post['published_at'] ?: $post['created_at'];
    if (!empty($pubDt)) { $byline[] = date('M j, Y g:ia', strtotime($pubDt)); }
  ?>
  <?php if(!empty($post['category_name']) || !empty($byline)): ?>
    <div class="text-sm text-neutral-400 mt-1">
      <?php if(!empty($post['category_name'])): ?>In <?php echo e($post['category_name']); ?><?php endif; ?>
      <?php if(!empty($post['category_name']) && !empty($byline)): ?> · <?php endif; ?>
      <?php echo implode(' · ', $byline); ?>
    </div>
  <?php endif; ?>
  <?php if(!empty($post['cover_image'])): ?><img class="mt-6 rounded" src="<?php echo e($post['cover_image']); ?>" alt="<?php echo e($post['title']); ?>" loading="lazy"><?php endif; ?>
  <?php if(!empty($post['excerpt'])): ?><p class="text-neutral-300 mt-6"><?php echo e($post['excerpt']); ?></p><?php endif; ?>
  
  <div id="editorPop" class="hidden fixed z-50 w-80 bg-neutral-900 border border-neutral-800 rounded-lg shadow-lg p-4"></div>
  <?php 
    // Split content for mid-content ad injection
    $content = $post['content_html'];
    $adsMidContent = '';
    if ($res = $mysqli->query("SELECT ads_midcontent_code FROM cms_settings WHERE id=1")) {
      $row = $res->fetch_assoc();
      $adsMidContent = $row['ads_midcontent_code'] ?? '';
    }
    
    // Inject ad after 2nd paragraph if available
    if (!empty($adsMidContent)) {
      $paragraphs = preg_split('/(<\/p>)/i', $content, -1, PREG_SPLIT_DELIM_CAPTURE);
      if (count($paragraphs) > 4) {
        array_splice($paragraphs, 4, 0, '<div class="my-8">'.$adsMidContent.'</div>');
        $content = implode('', $paragraphs);
      }
    }
  ?>
  <div class="mt-6 leading-7 prose prose-invert max-w-none"><?php echo $content; ?></div>
  <?php // In-post AdSense code from settings ?>
  <?php
    $adsInPost = '';
    if ($res = $mysqli->query("SELECT ads_inpost_code FROM cms_settings WHERE id=1")) {
      $row = $res->fetch_assoc();
      $adsInPost = $row['ads_inpost_code'] ?? '';
    }
    if (!empty($adsInPost)) { echo '<div class="mt-10">'.$adsInPost.'</div>'; }
  ?>
  
  <?php // Related posts ?>
  <?php
    $relatedPosts = [];
    if (!empty($post['related_post_ids'])) {
      $ids = array_filter(array_map('intval', explode(',', $post['related_post_ids'])));
      if (!empty($ids)) {
        $placeholders = implode(',', array_fill(0, count($ids), '?'));
        $stmt = $mysqli->prepare("SELECT id, title, slug, cover_image FROM cms_posts WHERE id IN ($placeholders) AND status='published' LIMIT 3");
        $stmt->bind_param(str_repeat('i', count($ids)), ...$ids);
        $stmt->execute();
        $result = $stmt->get_result();
        while ($row = $result->fetch_assoc()) {
          $relatedPosts[] = $row;
        }
        $stmt->close();
      }
    }
  ?>
  <?php if (!empty($relatedPosts)): ?>
    <div class="mt-12 pt-8 border-t border-neutral-800">
      <h3 class="text-xl font-semibold mb-4">Related Posts</h3>
      <div class="grid md:grid-cols-3 gap-4">
        <?php foreach ($relatedPosts as $rp): ?>
          <a href="<?php echo base_url('public/post.php?slug='.e($rp['slug'])); ?>" class="bg-neutral-900 border border-neutral-800 rounded-lg overflow-hidden hover:border-neutral-700 transition">
            <?php if (!empty($rp['cover_image'])): ?>
              <img src="<?php echo e($rp['cover_image']); ?>" alt="<?php echo e($rp['title']); ?>" class="w-full aspect-video object-cover" loading="lazy">
            <?php endif; ?>
            <div class="p-4">
              <h4 class="text-sm font-semibold hover:text-sky-400"><?php echo e($rp['title']); ?></h4>
            </div>
          </a>
        <?php endforeach; ?>
      </div>
    </div>
  <?php endif; ?>
</article>
<script>
(function(){
  const pop = document.getElementById('editorPop');
  let hideTimer = null;
  function showPop(x,y,html){
    pop.innerHTML = html;
    pop.style.left = Math.min(window.innerWidth - pop.offsetWidth - 12, x + 12) + 'px';
    pop.style.top = (y + 12) + 'px';
    pop.classList.remove('hidden');
  }
  function hidePop(){ pop.classList.add('hidden'); }
  function template(d){
    const img = d.profile_image ? `<img src="${d.profile_image}" class="w-12 h-12 rounded-full object-cover" alt="">` : `<div class=\"w-12 h-12 rounded-full bg-neutral-800 flex items-center justify-center font-bold\">${(d.display_name||d.username||'?').slice(0,1).toUpperCase()}</div>`;
    const role = d.role ? `<span class=\"ml-2 text-xs px-2 py-0.5 rounded bg-neutral-800 text-neutral-300\">${d.role.replace('_',' ')}</span>` : '';
    const bio = d.bio ? `<p class=\"text-sm text-neutral-300 mt-2 line-clamp-4\">${d.bio}</p>` : '';
    const url = d.profile_url ? d.profile_url : '#';
    return `<div class=\"flex gap-3\">${img}<div class=\"flex-1\"><div class=\"font-semibold\">${d.display_name||d.username||'Editor'}</div>${role}${bio}<div class=\"mt-3\"><a href=\"${url}\" class=\"text-sky-400 hover:underline text-sm\">View profile</a></div></div></div>`;
  }
  function fetchAndShow(name, evt){
    fetch(`<?php echo base_url('public/editor_info.php'); ?>?name=`+encodeURIComponent(name))
      .then(r=>r.json()).then(j=>{
        if(j && j.ok && j.data){ showPop(evt.clientX, evt.clientY, template(j.data)); }
      }).catch(()=>{});
  }
  document.querySelectorAll('.editor-hover').forEach(el=>{
    el.addEventListener('mouseenter', (e)=>{
      clearTimeout(hideTimer);
      const name = el.getAttribute('data-editor-name');
      fetchAndShow(name, e);
    });
    el.addEventListener('mousemove', (e)=>{
      if (!pop.classList.contains('hidden')) {
        pop.style.left = Math.min(window.innerWidth - pop.offsetWidth - 12, e.clientX + 12) + 'px';
        pop.style.top = (e.clientY + 12) + 'px';
      }
    });
    el.addEventListener('mouseleave', ()=>{
      hideTimer = setTimeout(hidePop, 150);
    });
  });
  pop.addEventListener('mouseenter', ()=>{ clearTimeout(hideTimer); });
  pop.addEventListener('mouseleave', ()=>{ hideTimer = setTimeout(hidePop, 150); });
})();
</script>
<?php // Comments section ?>
<?php
  // session already started at top of file
  // Fetch approved comments
  $comments=[];
  $q=$mysqli->prepare("SELECT c.content,c.created_at,u.name,u.picture FROM cms_comments c JOIN cms_oauth_users u ON u.id=c.oauth_user_id WHERE c.post_id=? AND c.status='approved' ORDER BY c.created_at DESC");
  $q->bind_param('i',$post['id']); $q->execute(); $r=$q->get_result(); while($row=$r->fetch_assoc()) $comments[]=$row; $q->close();
?>
<section id="comments" class="mt-12">
  <h2 class="text-2xl font-semibold">Comments</h2>
  <div class="mt-4 space-y-4">
    <?php foreach($comments as $c): ?>
      <div class="bg-neutral-900 border border-neutral-800 rounded p-4">
        <div class="flex items-center gap-3 text-sm text-neutral-400">
          <?php if(!empty($c['picture'])): ?><img src="<?php echo e($c['picture']); ?>" alt="" class="w-6 h-6 rounded-full"><?php endif; ?>
          <span><?php echo e($c['name'] ?: 'User'); ?></span>
          <span>·</span>
          <span><?php echo e($c['created_at']); ?></span>
        </div>
        <div class="mt-2 text-neutral-100 leading-7"><?php echo nl2br(e($c['content'])); ?></div>
      </div>
    <?php endforeach; ?>
    <?php if(!$comments): ?><div class="text-neutral-400 text-sm">No comments yet. Be the first to comment.</div><?php endif; ?>
  </div>
  <div class="mt-6">
    <h3 class="text-lg font-semibold">Add a comment</h3>
    <?php if (empty($_SESSION['pub_user'])): ?>
      <?php $_SESSION['after_login_redirect'] = base_url('public/post.php?slug='.$post['slug'].'#comments'); ?>
      <a class="inline-flex items-center gap-2 mt-3 bg-sky-500 hover:bg-sky-600 text-white px-4 py-2 rounded" href="<?php echo base_url('comments/google_auth.php'); ?>">Sign in with Google to comment</a>
    <?php else: ?>
      <form method="POST" action="<?php echo base_url('comments/add.php'); ?>" class="mt-3">
        <textarea name="content" rows="4" required class="w-full rounded-md bg-neutral-950 border border-neutral-800 px-3 py-2" placeholder="Write your comment..."></textarea>
        <input type="hidden" name="post_id" value="<?php echo (int)$post['id']; ?>">
        <input type="hidden" name="slug" value="<?php echo e($post['slug']); ?>">
        <button class="mt-3 bg-sky-500 hover:bg-sky-600 text-white px-4 py-2 rounded">Post Comment</button>
      </form>
    <?php endif; ?>
  </div>
</section>
<?php include __DIR__ . '/../includes/template_footer.php'; ?>
