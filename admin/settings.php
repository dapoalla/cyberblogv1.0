<?php
require __DIR__ . '/../includes/auth.php';
require_admin();
$msg='';
if($_SERVER['REQUEST_METHOD']==='POST' && csrf_check($_POST['csrf']??'')){
  $header = (string)($_POST['ads_header_code']??'');
  $inpost = (string)($_POST['ads_inpost_code']??'');
  $midcontent = (string)($_POST['ads_midcontent_code']??'');
  $stmt=$mysqli->prepare("UPDATE cms_settings SET ads_header_code=?, ads_inpost_code=?, ads_midcontent_code=? WHERE id=1");
  if($stmt){$stmt->bind_param('sss',$header,$inpost,$midcontent);$stmt->execute();$stmt->close();$msg='Settings saved.';}
}
$set=['ads_header_code'=>'','ads_inpost_code'=>'','ads_midcontent_code'=>''];
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
  <input type="hidden" name="csrf" value="<?php echo e(csrf_token()); ?>">
  <button class="bg-sky-500 hover:bg-sky-600 text-white px-4 py-2 rounded">Save Settings</button>
</form>
<?php include __DIR__ . '/../includes/template_footer.php'; ?>
