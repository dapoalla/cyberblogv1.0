<?php
function base_url(string $path = ''): string {
  $config = require __DIR__ . '/../config.php';
  // Prefer configured base_url if provided (expects a path like "/blog" or "")
  $base = trim((string)($config['base_url'] ?? ''));

  if ($base === '') {
    // Auto-detect subfolder from the current request path, so links stay within the project root
    $script = str_replace('\\', '/', $_SERVER['SCRIPT_NAME'] ?? ''); // e.g., /A03.Blog/admin/index.php
    $parts = array_values(array_filter(explode('/', $script), 'strlen'));
    $rootParts = [];
    foreach ($parts as $idx => $seg) {
      // Stop at known top-level app directories; everything before is the project folder path
      if (in_array($seg, ['admin','public','comments','setup'])) {
        $rootParts = array_slice($parts, 0, $idx);
        break;
      }
    }
    // If we didn't hit a known folder, fall back to dirname (handles direct root deployments)
    if (empty($rootParts)) {
      $dir = trim(str_replace('\\', '/', dirname($script)), '/');
      // dirname("/index.php") => "/"; normalize to empty
      $base = ($dir === '/' ? '' : $dir);
    } else {
      $base = implode('/', $rootParts);
    }
  } else {
    // Normalize configured base path
    $base = trim($base, '/');
  }

  $path = ltrim($path, '/');
  // Compose absolute path on host, ensuring trailing slash after base when non-empty
  return ($base !== '' ? '/' . $base . '/' : '/') . $path;
}
function e($s) { return htmlspecialchars((string)$s, ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8'); }
function slugify(string $t): string {
  $t = strtolower($t);
  $t = preg_replace('~[^a-z0-9_\-]+~','-', $t);
  $t = trim($t, '-');
  return $t ?: uniqid('post-');
}
