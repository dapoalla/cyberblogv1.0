<?php
function base_url(string $path = ''): string {
  $config = require __DIR__ . '/../config.php';
  $base = $config['base_url'] ?? '';
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
