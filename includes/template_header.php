<?php $config = require __DIR__ . '/../config.php'; ?>
<!doctype html>
<html lang="en">
<head>
  <meta charset="utf-8" />
  <meta name="viewport" content="width=device-width, initial-scale=1" />
  <?php
    // Prefer site name from DB settings if available
    $dbSiteName = '';
    $aboutEnabled = 1;
    if (extension_loaded('mysqli') && !empty($config['db']['user']) && !empty($config['db']['name'])) {
      mysqli_report(MYSQLI_REPORT_OFF);
      $dbh = @mysqli_init();
      if ($dbh) {
        @mysqli_real_connect($dbh, $config['db']['host'] ?? 'localhost', $config['db']['user'] ?? '', $config['db']['pass'] ?? '', $config['db']['name'] ?? '', $config['db']['port'] ?? 3306);
        if (!$dbh->connect_errno) {
          $dbh->set_charset('utf8mb4');
          if ($res = $dbh->query("SELECT site_name, logo_url, about_enabled FROM cms_settings WHERE id=1")) {
            $row = $res->fetch_assoc();
            $dbSiteName = $row['site_name'] ?? '';
            $adsHeader = '';
            $aboutEnabled = isset($row['about_enabled']) ? (int)$row['about_enabled'] : 1;
          }
        }
      }
    }
    $siteName = !empty($dbSiteName) ? $dbSiteName : ($config['site_name'] ?? 'CyberBlog');
  ?>
  <title><?php echo isset($pageTitle)? e($pageTitle).' | ' : ''; ?><?php echo e($siteName); ?></title>
  <meta name="description" content="<?php echo isset($metaDescription)? e($metaDescription) : e($siteName); ?>" />
  <link rel="preconnect" href="https://cdn.tailwindcss.com">
  <link rel="preconnect" href="https://fonts.googleapis.com">
  <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
  <link href="https://fonts.googleapis.com/css2?family=Poppins:wght@300;400;500;600;700&display=swap" rel="stylesheet">
  <script src="https://cdn.tailwindcss.com?plugins=typography,forms"></script>
  <link rel="stylesheet" href="<?php echo base_url('assets/css/styles.css'); ?>" />
  <style>html,body{font-family:'Poppins',ui-sans-serif,system-ui,-apple-system,Segoe UI,Roboto,Helvetica,Arial}</style>
  <script>
    // Dark mode toggle - apply immediately to prevent flash
    (function() {
      if (localStorage.theme === 'grey') {
        document.documentElement.classList.add('grey-mode');
      }
    })();
  </script>
  <?php // Inject AdSense header code if configured (non-fatal, optional) ?>
  <?php
    $adsHeader = '';
    if (extension_loaded('mysqli') && !empty($config['db']['user']) && !empty($config['db']['name'])) {
      mysqli_report(MYSQLI_REPORT_OFF);
      $dbh = @mysqli_init();
      if ($dbh) {
        @mysqli_real_connect($dbh, $config['db']['host'] ?? 'localhost', $config['db']['user'] ?? '', $config['db']['pass'] ?? '', $config['db']['name'] ?? '', $config['db']['port'] ?? 3306);
        if (!$dbh->connect_errno) {
          $dbh->set_charset('utf8mb4');
          if ($res = $dbh->query("SELECT ads_header_code FROM cms_settings WHERE id=1")) {
            $row = $res->fetch_assoc();
            $adsHeader = $row['ads_header_code'] ?? '';
          }
        }
      }
    }
    if (!empty($adsHeader)) { echo $adsHeader; }
  ?>
</head>
<body class="bg-neutral-950 text-neutral-100">
<header class="sticky top-0 z-50 bg-neutral-950/80 backdrop-blur border-b border-neutral-800">
  <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 py-3 flex items-center justify-between">
    <?php
      $logo = $config['logo_url'] ?? '';
      if (empty($logo) && extension_loaded('mysqli') && !empty($config['db']['user']) && !empty($config['db']['name'])) {
        mysqli_report(MYSQLI_REPORT_OFF);
        $dbh = @mysqli_init();
        if ($dbh) {
          @mysqli_real_connect($dbh, $config['db']['host'] ?? 'localhost', $config['db']['user'] ?? '', $config['db']['pass'] ?? '', $config['db']['name'] ?? '', $config['db']['port'] ?? 3306);
          if (!$dbh->connect_errno) {
            if ($res = $dbh->query("SELECT logo_url FROM cms_settings WHERE id=1")) {
              $row = $res->fetch_assoc();
              $dbLogo = $row['logo_url'] ?? '';
              if (!empty($dbLogo)) { $logo = $dbLogo; }
            }
          }
        }
      }
    ?>
    <a href="<?php echo base_url('public/index.php'); ?>" class="flex items-center gap-3">
      <?php if (!empty($logo)): ?>
        <img src="<?php echo e($logo); ?>" alt="<?php echo e($siteName ?? 'Blog'); ?>" class="h-8 w-auto">
      <?php endif; ?>
      <span class="text-xl font-extrabold tracking-tight"><?php echo e($siteName ?? 'Blog'); ?></span>
    </a>
    <nav class="hidden md:flex items-center gap-6 text-sm">
      <a href="<?php echo base_url('public/index.php'); ?>" class="hover:text-sky-400">Home</a>
      <?php if (!empty($aboutEnabled)): ?><a href="<?php echo base_url('public/about.php'); ?>" class="hover:text-sky-400">About</a><?php endif; ?>
      <a href="<?php echo base_url('public/contact.php'); ?>" class="hover:text-sky-400">Contact</a>
      <a href="<?php echo base_url('public/search.php'); ?>" class="hover:text-sky-400">Search</a>
      <button id="themeToggle" class="hover:text-sky-400" title="Toggle theme">🌓</button>
      <div class="relative">
        <?php
          // Build More menu from categories; hide if none
          $cats = [];
          if (extension_loaded('mysqli') && !empty($config['db']['user']) && !empty($config['db']['name'])) {
            mysqli_report(MYSQLI_REPORT_OFF);
            $dbh = @mysqli_init();
            if ($dbh) {
              @mysqli_real_connect($dbh, $config['db']['host'] ?? 'localhost', $config['db']['user'] ?? '', $config['db']['pass'] ?? '', $config['db']['name'] ?? '', $config['db']['port'] ?? 3306);
              if (!$dbh->connect_errno) {
                $dbh->set_charset('utf8mb4');
                if ($res = $dbh->query("SELECT name, slug FROM cms_categories ORDER BY name ASC LIMIT 20")) {
                  while ($row = $res->fetch_assoc()) { $cats[] = $row; }
                }
              }
            }
          }
        ?>
        <?php if (!empty($cats)): ?>
          <button id="moreBtn" class="hover:text-sky-400" aria-haspopup="true" aria-expanded="false">More ▾</button>
          <div id="moreMenu" class="hidden absolute right-0 mt-2 w-56 bg-neutral-900 border border-neutral-800 rounded shadow-lg">
            <?php foreach ($cats as $c): ?>
              <a href="<?php echo base_url('public/category.php?slug='.e($c['slug'])); ?>" class="block px-4 py-2 hover:bg-neutral-800"><?php echo e($c['name']); ?></a>
            <?php endforeach; ?>
          </div>
        <?php endif; ?>
      </div>
    </nav>
  </div>
</header>
<main class="max-w-5xl mx-auto px-4 sm:px-6 lg:px-8 py-8">
