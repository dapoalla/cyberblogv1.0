<?php
$config = require __DIR__ . '/../config.php';
// Harden admin session cookie
session_name(($config['security']['session_name'] ?? 'cr_blog2_sess').'_adm');
if (session_status() === PHP_SESSION_NONE) {
  $isHttps = (!empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off')
    || (($_SERVER['SERVER_PORT'] ?? '') == 443)
    || (($_SERVER['HTTP_X_FORWARDED_PROTO'] ?? '') === 'https');
  // Persistent, not session-only: a lifetime of 0 gets cleared by some
  // mobile browsers far more eagerly than "closing the browser" implies,
  // which is what was behind needing to log back in constantly.
  $adminSessionDays = 30;
  $cookieParams = [
    'lifetime' => 60 * 60 * 24 * $adminSessionDays,
    'path' => '/',
    'domain' => '', // default current host
    'secure' => $isHttps,
    'httponly' => true,
    'samesite' => 'Lax'
  ];
  if (PHP_VERSION_ID >= 70300) {
    session_set_cookie_params($cookieParams);
  } else {
    // Fallback for older PHP versions (without samesite)
    session_set_cookie_params($cookieParams['lifetime'], '/'.($isHttps?'; Secure; HttpOnly':''));
  }
  ini_set('session.gc_maxlifetime', (string)$cookieParams['lifetime']);
  session_start();
}
function csrf_token(): string {
  $k = 'csrf_key';
  if (empty($_SESSION[$k])) $_SESSION[$k] = bin2hex(random_bytes(16));
  return $_SESSION[$k];
}
function csrf_check($t): bool { return !empty($_SESSION['csrf_key']) && hash_equals($_SESSION['csrf_key'], (string)$t); }
