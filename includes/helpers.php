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
