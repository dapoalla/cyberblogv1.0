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
    // Canonical URL: pages can set $canonicalUrl before including this file
    // (e.g. post.php uses the clean /post/slug form); otherwise derive it
    // from the current request so every page still gets one.
    $scheme = (isset($_SERVER['HTTPS']) && $_SERVER['HTTPS'] === 'on') ? 'https://' : 'http://';
    $currentOrigin = $scheme . ($_SERVER['HTTP_HOST'] ?? '');
    if (empty($canonicalUrl)) {
      $canonicalUrl = $currentOrigin . strtok($_SERVER['REQUEST_URI'] ?? '', '?');
    }
    $ogTitleText = isset($pageTitle) ? $pageTitle : $siteDisplayName;
    $ogDescText = isset($metaDescription) ? $metaDescription : $siteDisplayName;
    $ogType = $ogType ?? 'website';
    $ogImage = !empty($ogImage) ? $ogImage : '';
    if ($ogImage && strpos($ogImage, '://') === false) {
      $ogImage = $currentOrigin . (($ogImage[0] === '/') ? $ogImage : '/' . $ogImage);
    }
  ?>
  <link rel="canonical" href="<?php echo e($canonicalUrl); ?>" />
  <meta property="og:type" content="<?php echo e($ogType); ?>" />
  <meta property="og:site_name" content="<?php echo e($siteDisplayName); ?>" />
  <meta property="og:title" content="<?php echo e($ogTitleText); ?>" />
  <meta property="og:description" content="<?php echo e($ogDescText); ?>" />
  <meta property="og:url" content="<?php echo e($canonicalUrl); ?>" />
  <?php if ($ogImage): ?><meta property="og:image" content="<?php echo e($ogImage); ?>" /><?php endif; ?>
  <meta name="twitter:card" content="<?php echo $ogImage ? 'summary_large_image' : 'summary'; ?>" />
  <meta name="twitter:title" content="<?php echo e($ogTitleText); ?>" />
  <meta name="twitter:description" content="<?php echo e($ogDescText); ?>" />
  <?php if ($ogImage): ?><meta name="twitter:image" content="<?php echo e($ogImage); ?>" /><?php endif; ?>
  <script type="application/ld+json"><?php echo json_encode([
    '@context' => 'https://schema.org',
    '@type' => 'WebSite',
    'name' => $siteDisplayName,
    'url' => $currentOrigin . base_url(''),
  ], JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE); ?></script>
  <?php if (!empty($articleSchema)): ?>
    <?php
      if ($ogImage) $articleSchema['image'] = [$ogImage];
      $articleSchema['publisher'] = ['@type' => 'Organization', 'name' => $siteDisplayName];
    ?>
    <script type="application/ld+json"><?php echo json_encode($articleSchema, JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE); ?></script>
  <?php endif; ?>
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
