<?php
// Auto-detects the URL subfolder the app is deployed into (e.g. "/blog2") by
// comparing the project's filesystem path against the server's document root.
// Lets base_url() work out of the box whether the app sits at the domain root
// or in an arbitrary subfolder, without needing config.local.php to exist yet -
// important for the very first request of a fresh install.
function detect_base_url(): string {
  static $cached = null;
  if ($cached !== null) return $cached;
  $projectRoot = rtrim(str_replace('\\', '/', dirname(__DIR__)), '/');
  $docRoot = rtrim(str_replace('\\', '/', $_SERVER['DOCUMENT_ROOT'] ?? ''), '/');
  $base = '';
  if ($docRoot !== '' && strpos($projectRoot, $docRoot) === 0) {
    $base = substr($projectRoot, strlen($docRoot));
  }
  return $cached = $base;
}
function base_url(string $path = ''): string {
  $config = require __DIR__ . '/../config.php';
  $base = $config['base_url'] ?? '';
  if ($base === '') $base = detect_base_url();
  if ($base && $base[0] !== '/') $base = '/'.$base;
  $path = ltrim($path, '/');
  return ($base ? $base.'/' : '/').$path;
}
function e($s) { return htmlspecialchars((string)$s, ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8'); }
function slugify(string $t): string {
  $t = strtolower($t);
  $t = preg_replace('~[^a-z0-9_\-]+~','-', $t);
  $t = trim($t, '-');
  return $t ?: uniqid('post-');
}
// Renders a visible breadcrumb trail plus the matching BreadcrumbList JSON-LD.
// $items is an ordered list of ['label' => string, 'url' => string|null] -
// the last item is treated as the current page (rendered without a link).
function render_breadcrumbs(array $items): void {
  if (empty($items)) return;
  echo '<nav aria-label="Breadcrumb" class="text-sm text-neutral-400 mb-4 flex flex-wrap items-center gap-1">';
  $count = count($items);
  foreach ($items as $i => $item) {
    if ($i > 0) echo '<span class="text-neutral-600" aria-hidden="true">/</span>';
    if (!empty($item['url']) && $i < $count - 1) {
      echo '<a href="' . e($item['url']) . '" class="hover:text-sky-400">' . e($item['label']) . '</a>';
    } else {
      echo '<span class="text-neutral-300"' . ($i === $count - 1 ? ' aria-current="page"' : '') . '>' . e($item['label']) . '</span>';
    }
  }
  echo '</nav>';

  $ld = ['@context' => 'https://schema.org', '@type' => 'BreadcrumbList', 'itemListElement' => []];
  foreach ($items as $i => $item) {
    $entry = ['@type' => 'ListItem', 'position' => $i + 1, 'name' => $item['label']];
    if (!empty($item['url'])) $entry['item'] = $item['url'];
    $ld['itemListElement'][] = $entry;
  }
  echo '<script type="application/ld+json">' . json_encode($ld, JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE) . '</script>';
}

function start_public_session(): void {
  if (session_status() !== PHP_SESSION_NONE) return;
  session_name('cr_blog2_pub');
  $isHttps = (!empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off')
    || (($_SERVER['SERVER_PORT'] ?? '') == 443)
    || (($_SERVER['HTTP_X_FORWARDED_PROTO'] ?? '') === 'https');
  $cookieParams = [
    'lifetime' => 0,
    'path' => '/',
    'domain' => '',
    'secure' => $isHttps,
    'httponly' => true,
    'samesite' => 'Lax',
  ];
  if (PHP_VERSION_ID >= 70300) {
    session_set_cookie_params($cookieParams);
  } else {
    session_set_cookie_params(0, '/' . ($isHttps ? '; Secure; HttpOnly' : ''));
  }
  session_start();
}
