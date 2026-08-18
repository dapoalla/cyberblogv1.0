<?php
require_once __DIR__ . '/../includes/helpers.php';
header('Content-Type: text/plain; charset=utf-8');
$scheme = (isset($_SERVER['HTTPS']) && $_SERVER['HTTPS'] === 'on') ? 'https://' : 'http://';
$host = $_SERVER['HTTP_HOST'] ?? '';
$sitemapUrl = $scheme . $host . base_url('sitemap.xml');
$lines = [
  'User-agent: *',
  'Disallow: ' . base_url('admin/'),
  'Disallow: ' . base_url('install/'),
  'Allow: /',
  '',
  'Sitemap: ' . $sitemapUrl,
];
echo implode("\n", $lines) . "\n";
