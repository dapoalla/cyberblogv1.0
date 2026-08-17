<?php
  $config = require __DIR__ . '/../config.php';
  require_once __DIR__ . '/db.php';
  $siteSettings = ['site_name'=>'','site_tagline'=>'','footer_text'=>'','about_content_html'=>'','nav_category_ids'=>'','ads_header_code'=>''];
  if ($res = $mysqli->query("SELECT * FROM cms_settings WHERE id=1")) {
    $siteSettings = array_merge($siteSettings, $res->fetch_assoc() ?: []);
  }
  $siteDisplayName = !empty($siteSettings['site_name']) ? $siteSettings['site_name'] : $config['site_name'];
  $navCategories = [];
  $navCategoryIds = array_filter(array_map('intval', explode(',', (string)$siteSettings['nav_category_ids'])));
  if (!empty($navCategoryIds)) {
    $placeholders = implode(',', array_fill(0, count($navCategoryIds), '?'));
    $navStmt = $mysqli->prepare("SELECT name, slug FROM cms_categories WHERE id IN ($placeholders) ORDER BY name");
    $navStmt->bind_param(str_repeat('i', count($navCategoryIds)), ...$navCategoryIds);
    $navStmt->execute();
    $navRes = $navStmt->get_result();
    while ($row = $navRes->fetch_assoc()) $navCategories[] = $row;
    $navStmt->close();
  }
?>
<!doctype html>
<html lang="en">
<head>
  <meta charset="utf-8" />
  <meta name="viewport" content="width=device-width, initial-scale=1" />
  <title><?php echo isset($pageTitle)? e($pageTitle).' | ' : ''; ?><?php echo e($siteDisplayName); ?></title>
  <meta name="description" content="<?php echo isset($metaDescription)? e($metaDescription) : e($siteDisplayName); ?>" />
  <?php
    $cssRelPath = 'assets/css/tailwind.min.css';
    $cssAbsPath = __DIR__ . '/../' . $cssRelPath;
    $cssVer = file_exists($cssAbsPath) ? filemtime($cssAbsPath) : 1;
  ?>
  <link rel="preload" as="font" type="font/woff2" href="<?php echo base_url('assets/fonts/poppins-400.woff2'); ?>" crossorigin>
  <link rel="stylesheet" href="<?php echo base_url($cssRelPath); ?>?v=<?php echo (int)$cssVer; ?>" />
  <script>
    // Dark mode toggle - apply immediately to prevent flash
    (function() {
      if (localStorage.theme === 'grey') {
        document.documentElement.classList.add('grey-mode');
      }
    })();
  </script>
  <?php if (!empty($siteSettings['ads_header_code'])) { echo $siteSettings['ads_header_code']; } ?>
</head>
<body class="bg-neutral-950 text-neutral-100">
<header class="sticky top-0 z-50 bg-neutral-950/80 backdrop-blur border-b border-neutral-800">
  <div class="max-w-screen-2xl mx-auto px-4 sm:px-6 lg:px-8 py-3 flex items-center justify-between">
    <a href="<?php echo base_url('public/index.php'); ?>" class="flex items-center gap-3">
      <span class="text-xl font-extrabold tracking-tight"><?php echo e($siteDisplayName); ?></span>
    </a>
    <nav class="hidden md:flex items-center gap-6 text-sm">
      <a href="<?php echo base_url('public/index.php'); ?>" class="hover:text-sky-400">Home</a>
      <a href="<?php echo base_url('public/about.php'); ?>" class="hover:text-sky-400">About</a>
      <a href="<?php echo base_url('public/contact.php'); ?>" class="hover:text-sky-400">Contact</a>
      <a href="<?php echo base_url('public/search.php'); ?>" class="hover:text-sky-400">Search</a>
      <button id="themeToggle" class="hover:text-sky-400" title="Toggle theme">🌓</button>
      <div class="relative">
        <button id="moreBtn" class="hover:text-sky-400" aria-haspopup="true" aria-expanded="false">More ▾</button>
        <div id="moreMenu" class="hidden absolute right-0 mt-2 w-56 bg-neutral-900 border border-neutral-800 rounded shadow-lg">
          <?php foreach ($navCategories as $navCat): ?>
            <a href="<?php echo base_url('public/category.php?slug='.e($navCat['slug'])); ?>" class="block px-4 py-2 hover:bg-neutral-800"><?php echo e($navCat['name']); ?></a>
          <?php endforeach; ?>
          <a href="<?php echo base_url('public/contact.php'); ?>" class="block px-4 py-2 hover:bg-neutral-800">Contact</a>
        </div>
      </div>
    </nav>
  </div>
</header>
<main class="max-w-screen-2xl mx-auto px-4 sm:px-6 lg:px-8 py-8">
