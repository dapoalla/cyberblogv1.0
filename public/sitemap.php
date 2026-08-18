<?php
require_once __DIR__ . '/../includes/db.php';
require_once __DIR__ . '/../includes/helpers.php';
header('Content-Type: application/xml; charset=utf-8');
$scheme = (isset($_SERVER['HTTPS']) && $_SERVER['HTTPS'] === 'on') ? 'https://' : 'http://';
$origin = $scheme . ($_SERVER['HTTP_HOST'] ?? '');

echo "<?xml version=\"1.0\" encoding=\"UTF-8\"?>\n";
echo "<urlset xmlns=\"http://www.sitemaps.org/schemas/sitemap/0.9\">\n";

function sitemap_url(string $origin, string $loc, ?string $lastmod = null, string $changefreq = 'weekly', string $priority = '0.5'): void {
  echo "  <url><loc>" . htmlspecialchars($loc, ENT_XML1) . "</loc>";
  if ($lastmod) echo "<lastmod>" . gmdate('c', strtotime($lastmod)) . "</lastmod>";
  echo "<changefreq>$changefreq</changefreq><priority>$priority</priority></url>\n";
}

// Static pages
sitemap_url($origin, $origin . base_url(''), null, 'daily', '1.0');
sitemap_url($origin, $origin . base_url('public/about.php'), null, 'monthly', '0.4');
sitemap_url($origin, $origin . base_url('public/contact.php'), null, 'monthly', '0.3');
sitemap_url($origin, $origin . base_url('public/editorial_team.php'), null, 'monthly', '0.3');

// Categories
if ($res = $mysqli->query("SELECT slug FROM cms_categories ORDER BY name")) {
  while ($row = $res->fetch_assoc()) {
    sitemap_url($origin, $origin . base_url('category/' . $row['slug']), null, 'weekly', '0.6');
  }
}

// Posts - use the clean /post/slug URL (matches the .htaccess rewrite), not the raw ?slug= form
if ($res = $mysqli->query("SELECT slug, COALESCE(updated_at, COALESCE(published_at, created_at)) AS lastmod FROM cms_posts WHERE status='published' AND (published_at IS NULL OR published_at <= NOW())")) {
  while ($row = $res->fetch_assoc()) {
    sitemap_url($origin, $origin . base_url('post/' . $row['slug']), $row['lastmod'], 'monthly', '0.8');
  }
}

echo "</urlset>\n";
