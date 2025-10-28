<?php
require __DIR__ . '/../includes/db.php';
header('Content-Type: application/xml; charset=utf-8');
echo "<?xml version=\"1.0\" encoding=\"UTF-8\"?>\n";
echo "<urlset xmlns=\"http://www.sitemaps.org/schemas/sitemap/0.9\">\n";
$base = (isset($_SERVER['HTTPS'])&&$_SERVER['HTTPS']==='on'?'https://':'http://').$_SERVER['HTTP_HOST'];
$q = $mysqli->query("SELECT slug, COALESCE(updated_at,COALESCE(published_at,created_at)) AS lastmod FROM cms_posts WHERE status='published'");
while($row=$q->fetch_assoc()){
  $loc = $base.'/public/post.php?slug='.htmlspecialchars($row['slug']);
  $lm = gmdate('c', strtotime($row['lastmod']));
  echo "  <url><loc>{$loc}</loc><lastmod>{$lm}</lastmod></url>\n";
}
echo "</urlset>\n";
