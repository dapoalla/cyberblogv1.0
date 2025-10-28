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
