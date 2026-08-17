<?php
require_once __DIR__ . '/../includes/auth.php';
require_admin();
$msg='';
if($_SERVER['REQUEST_METHOD']==='POST' && csrf_check($_POST['csrf']??'')){
  $header = (string)($_POST['ads_header_code']??'');
  $inpost = (string)($_POST['ads_inpost_code']??'');
  $midcontent = (string)($_POST['ads_midcontent_code']??'');
  $siteName = trim((string)($_POST['site_name']??''));
  $siteTagline = trim((string)($_POST['site_tagline']??''));
  $footerText = trim((string)($_POST['footer_text']??''));
  $aboutHtml = (string)($_POST['about_content_html']??'');
  $navCategoryIds = isset($_POST['nav_categories']) ? implode(',', array_filter(array_map('intval', $_POST['nav_categories']))) : '';
  $stmt=$mysqli->prepare("UPDATE cms_settings SET ads_header_code=?, ads_inpost_code=?, ads_midcontent_code=?, site_name=?, site_tagline=?, footer_text=?, about_content_html=?, nav_category_ids=? WHERE id=1");
  if($stmt){$stmt->bind_param('ssssssss',$header,$inpost,$midcontent,$siteName,$siteTagline,$footerText,$aboutHtml,$navCategoryIds);$stmt->execute();$stmt->close();$msg='Settings saved.';}
}
$set=['ads_header_code'=>'','ads_inpost_code'=>'','ads_midcontent_code'=>'','site_name'=>'','site_tagline'=>'','footer_text'=>'','about_content_html'=>'','nav_category_ids'=>''];
if($res=$mysqli->query("SELECT * FROM cms_settings WHERE id=1")){$set=array_merge($set, $res->fetch_assoc()?:[]);}
$selectedNavCats = array_filter(explode(',', (string)$set['nav_category_ids']));
$allCategories=[]; if($res=$mysqli->query("SELECT id,name FROM cms_categories ORDER BY name")){ while($r=$res->fetch_assoc()) $allCategories[]=$r; }
$pageTitle='Settings'; include __DIR__ . '/../includes/template_header.php';
include __DIR__ . '/../includes/admin_nav.php';
?>
<div class="flex items-center justify-between">
  <h1 class="text-2xl font-bold">Settings</h1>
</div>
<?php if($msg):?><div class="mt-3 text-green-400 text-sm"><?php echo e($msg);?></div><?php endif; ?>
<form method="POST" class="mt-6 grid gap-4 max-w-3xl">
  <div class="bg-neutral-900 border border-neutral-800 rounded-lg p-4">
    <h2 class="font-semibold mb-3">Site Identity</h2>
    <div>
      <label class="block text-sm mb-1 font-semibold">Blog Name</label>
      <input name="site_name" value="<?php echo e($set['site_name']); ?>" class="w-full rounded-md bg-neutral-950 border border-neutral-800 px-3 py-2" />
      <div class="text-xs text-neutral-400 mt-1">Shown in the header, browser tab, and RSS feed</div>
    </div>
    <div class="mt-4">
      <label class="block text-sm mb-1 font-semibold">Homepage Tagline</label>
      <textarea name="site_tagline" rows="2" class="w-full rounded-md bg-neutral-950 border border-neutral-800 px-3 py-2"><?php echo e($set['site_tagline']); ?></textarea>
      <div class="text-xs text-neutral-400 mt-1">Short description shown under the heading on the homepage</div>
    </div>
    <div class="mt-4">
      <label class="block text-sm mb-1 font-semibold">Footer Text</label>
      <input name="footer_text" value="<?php echo e($set['footer_text']); ?>" class="w-full rounded-md bg-neutral-950 border border-neutral-800 px-3 py-2" />
      <div class="text-xs text-neutral-400 mt-1">Use <code>{year}</code> to insert the current year, e.g. "© {year} My Blog. All rights reserved."</div>
    </div>
  </div>

  <div class="bg-neutral-900 border border-neutral-800 rounded-lg p-4">
    <h2 class="font-semibold mb-3">About Page Content</h2>
    <textarea name="about_content_html" rows="12" class="w-full rounded-md bg-neutral-950 border border-neutral-800 px-3 py-2 font-mono text-sm"><?php echo e($set['about_content_html']); ?></textarea>
    <div class="text-xs text-neutral-400 mt-1">Raw HTML shown on the About page. Use &lt;h2&gt;, &lt;p&gt;, &lt;ul&gt;&lt;li&gt; etc.</div>
  </div>

  <div class="bg-neutral-900 border border-neutral-800 rounded-lg p-4">
    <h2 class="font-semibold mb-3">"More" Menu</h2>
    <div class="text-xs text-neutral-400 mb-2">Choose which categories appear in the header's "More" dropdown (Contact is always included).</div>
    <?php if (empty($allCategories)): ?>
      <div class="text-sm text-neutral-400">No categories yet - <a href="manage_categories.php" class="text-sky-400 hover:underline">create some</a> first.</div>
    <?php else: ?>
      <div class="grid sm:grid-cols-2 gap-2">
        <?php foreach ($allCategories as $cat): ?>
          <label class="flex items-center gap-2 text-sm">
            <input type="checkbox" name="nav_categories[]" value="<?php echo (int)$cat['id']; ?>" <?php echo in_array((string)$cat['id'], $selectedNavCats) ? 'checked' : ''; ?> />
            <?php echo e($cat['name']); ?>
          </label>
        <?php endforeach; ?>
      </div>
    <?php endif; ?>
  </div>

  <div class="bg-neutral-900 border border-neutral-800 rounded-lg p-4">
    <h2 class="font-semibold mb-3">Ad Codes</h2>
    <div>
      <label class="block text-sm mb-1 font-semibold">Header Ad Code</label>
      <textarea name="ads_header_code" rows="6" class="w-full rounded-md bg-neutral-950 border border-neutral-800 px-3 py-2"><?php echo e($set['ads_header_code']); ?></textarea>
      <div class="text-xs text-neutral-400 mt-1">Injected in the &lt;head&gt; section (e.g., AdSense script)</div>
    </div>
    <div class="mt-4">
      <label class="block text-sm mb-1 font-semibold">Mid-Content Ad Code</label>
      <textarea name="ads_midcontent_code" rows="6" class="w-full rounded-md bg-neutral-950 border border-neutral-800 px-3 py-2"><?php echo e($set['ads_midcontent_code']); ?></textarea>
      <div class="text-xs text-neutral-400 mt-1">Injected in the middle of post content (after 2nd paragraph)</div>
    </div>
    <div class="mt-4">
      <label class="block text-sm mb-1 font-semibold">End of Post Ad Code</label>
      <textarea name="ads_inpost_code" rows="6" class="w-full rounded-md bg-neutral-950 border border-neutral-800 px-3 py-2"><?php echo e($set['ads_inpost_code']); ?></textarea>
      <div class="text-xs text-neutral-400 mt-1">Displayed at the end of post content</div>
    </div>
  </div>

  <input type="hidden" name="csrf" value="<?php echo e(csrf_token()); ?>">
  <button class="bg-sky-500 hover:bg-sky-600 text-white px-4 py-2 rounded w-fit">Save Settings</button>
</form>
<?php include __DIR__ . '/../includes/template_footer.php'; ?>
