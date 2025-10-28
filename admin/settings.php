<?php
require __DIR__ . '/../includes/db.php';
require __DIR__ . '/../includes/helpers.php';
require __DIR__ . '/../includes/csrf.php';
function require_admin(){ if(empty($_SESSION['admin'])){ header('Location: '.base_url('admin/login.php')); exit; } }
require_admin();
$msg='';
// Ensure optional setting column exists
$col = $mysqli->query("SHOW COLUMNS FROM cms_settings LIKE 'logo_url'");
if (!$col || $col->num_rows === 0) {
  @$mysqli->query("ALTER TABLE cms_settings ADD COLUMN logo_url VARCHAR(512) NULL");
}
if($_SERVER['REQUEST_METHOD']==='POST' && csrf_check($_POST['csrf']??'')){
  $header = (string)($_POST['ads_header_code']??'');
  $inpost = (string)($_POST['ads_inpost_code']??'');
  $midcontent = (string)($_POST['ads_midcontent_code']??'');
  $logo_url = trim((string)($_POST['logo_url']??''));
  $stmt=$mysqli->prepare("UPDATE cms_settings SET ads_header_code=?, ads_inpost_code=?, ads_midcontent_code=?, logo_url=? WHERE id=1");
  if($stmt){$stmt->bind_param('ssss',$header,$inpost,$midcontent,$logo_url);$stmt->execute();$stmt->close();$msg='Settings saved.';}
}
$set=['ads_header_code'=>'','ads_inpost_code'=>'','ads_midcontent_code'=>'','logo_url'=>''];
if($res=$mysqli->query("SELECT * FROM cms_settings WHERE id=1")){$set=$res->fetch_assoc()?:$set;}
$pageTitle='Settings'; include __DIR__ . '/../includes/template_header.php';
include __DIR__ . '/../includes/admin_nav.php';
?>
<div class="flex items-center justify-between">
  <h1 class="text-2xl font-bold">Settings</h1>
</div>
<?php if($msg):?><div class="mt-3 text-green-400 text-sm"><?php echo e($msg);?></div><?php endif; ?>
<form method="POST" class="mt-6 grid gap-4">
  <div>
    <label class="block text-sm mb-1 font-semibold">Header Ad Code</label>
    <textarea name="ads_header_code" rows="6" class="w-full rounded-md bg-neutral-950 border border-neutral-800 px-3 py-2"><?php echo e($set['ads_header_code']); ?></textarea>
    <div class="text-xs text-neutral-400 mt-1">Injected in the &lt;head&gt; section (e.g., AdSense script)</div>
  </div>
  <div>
    <label class="block text-sm mb-1 font-semibold">Mid-Content Ad Code</label>
    <textarea name="ads_midcontent_code" rows="6" class="w-full rounded-md bg-neutral-950 border border-neutral-800 px-3 py-2"><?php echo e($set['ads_midcontent_code']); ?></textarea>
    <div class="text-xs text-neutral-400 mt-1">Injected in the middle of post content (after 2nd paragraph)</div>
  </div>
  <div>
    <label class="block text-sm mb-1 font-semibold">End of Post Ad Code</label>
    <textarea name="ads_inpost_code" rows="6" class="w-full rounded-md bg-neutral-950 border border-neutral-800 px-3 py-2"><?php echo e($set['ads_inpost_code']); ?></textarea>
    <div class="text-xs text-neutral-400 mt-1">Displayed at the end of post content</div>
  </div>
  <div>
    <label class="block text-sm mb-1 font-semibold">Logo URL (optional)</label>
    <input type="url" name="logo_url" class="w-full rounded-md bg-neutral-950 border border-neutral-800 px-3 py-2" value="<?php echo e($set['logo_url']); ?>" placeholder="https://example.com/logo.png">
    <div class="text-xs text-neutral-400 mt-1">If set, the header shows this image before the site name.</div>
  </div>
  <input type="hidden" name="csrf" value="<?php echo e(csrf_token()); ?>">
  <button class="bg-sky-500 hover:bg-sky-600 text-white px-4 py-2 rounded">Save Settings</button>
</form>
<?php include __DIR__ . '/../includes/template_footer.php'; ?>
