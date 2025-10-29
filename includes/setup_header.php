<?php $config = file_exists(__DIR__ . '/../config.php') ? require __DIR__ . '/../config.php' : ['site_name'=>'CyberBlog','base_url'=>'']; ?>
<!doctype html>
<html lang="en">
<head>
  <meta charset="utf-8" />
  <meta name="viewport" content="width=device-width, initial-scale=1" />
  <title><?php echo isset($pageTitle)? e($pageTitle).' | ' : ''; ?><?php echo e($config['site_name'] ?? 'CyberBlog'); ?></title>
  <meta name="description" content="<?php echo isset($metaDescription)? e($metaDescription) : 'CyberBlog Setup'; ?>" />
  <link rel="preconnect" href="https://cdn.tailwindcss.com">
  <link rel="preconnect" href="https://fonts.googleapis.com">
  <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
  <link href="https://fonts.googleapis.com/css2?family=Poppins:wght@300;400;500;600;700&display=swap" rel="stylesheet">
  <script src="https://cdn.tailwindcss.com?plugins=typography,forms"></script>
  <link rel="stylesheet" href="../assets/css/styles.css" />
  <style>html,body{font-family:'Poppins',ui-sans-serif,system-ui,-apple-system,Segoe UI,Roboto,Helvetica,Arial}</style>
  <script>
    (function() {
      if (localStorage.theme === 'grey') {
        document.documentElement.classList.add('grey-mode');
      }
    })();
  </script>
</head>
<body class="bg-neutral-950 text-neutral-100">
<header class="sticky top-0 z-50 bg-neutral-950/80 backdrop-blur border-b border-neutral-800">
  <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 py-3 flex items-center justify-between">
    <?php $logo = $config['logo_url'] ?? ''; ?>
    <a href="<?php echo htmlspecialchars($_SERVER['SCRIPT_NAME']); ?>" class="flex items-center gap-3">
      <?php if (!empty($logo)): ?>
        <img src="<?php echo e($logo); ?>" alt="<?php echo e($config['site_name'] ?? 'Blog'); ?>" class="h-8 w-auto">
      <?php endif; ?>
      <span class="text-xl font-extrabold tracking-tight">Setup Wizard</span>
    </a>
    <nav class="hidden md:flex items-center gap-6 text-sm">
      <a href="../public/index.php" class="hover:text-sky-400">Blog Home</a>
      <button id="themeToggle" class="hover:text-sky-400" title="Toggle theme">🌓</button>
    </nav>
  </div>
</header>
<main class="max-w-5xl mx-auto px-4 sm:px-6 lg:px-8 py-8">