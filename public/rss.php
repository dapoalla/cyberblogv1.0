<?php
require __DIR__ . '/../includes/db.php';
require __DIR__ . '/../includes/helpers.php';

header('Content-Type: application/rss+xml; charset=utf-8');

$config = require __DIR__ . '/../config.php';
$siteName = $config['site_name'] ?? 'Cyberrose Blog';
$siteUrl = (isset($_SERVER['HTTPS']) && $_SERVER['HTTPS'] === 'on' ? 'https://' : 'http://') . $_SERVER['HTTP_HOST'];
$blogUrl = $siteUrl . base_url('public/index.php');

// Get latest 20 published posts
$posts = [];
if ($res = $mysqli->query("SELECT p.id, p.title, p.slug, p.excerpt, COALESCE(p.published_at, p.created_at) AS pub_date, c.name AS category 
                           FROM cms_posts p 
                           LEFT JOIN cms_categories c ON c.id = p.category_id 
                           WHERE p.status='published' AND (p.published_at IS NULL OR p.published_at <= NOW()) 
                           ORDER BY pub_date DESC LIMIT 20")) {
  while ($row = $res->fetch_assoc()) $posts[] = $row;
}

echo '<?xml version="1.0" encoding="UTF-8"?>' . "\n";
?>
<rss version="2.0" xmlns:atom="http://www.w3.org/2005/Atom">
  <channel>
    <title><?php echo htmlspecialchars($siteName); ?></title>
    <link><?php echo htmlspecialchars($blogUrl); ?></link>
    <description>More than 12 years of proven expertise in security integration, networking, automation, and cybersecurity. At CyberRose, we deliver real human reviews and genuine blogs — authentic human insights</description>
    <language>en-us</language>
    <lastBuildDate><?php echo date('r'); ?></lastBuildDate>
    <atom:link href="<?php echo htmlspecialchars($siteUrl . base_url('public/rss.php')); ?>" rel="self" type="application/rss+xml" />
    
    <?php foreach ($posts as $post): ?>
    <item>
      <title><?php echo htmlspecialchars($post['title']); ?></title>
      <link><?php echo htmlspecialchars($siteUrl . base_url('public/post.php?slug=' . $post['slug'])); ?></link>
      <guid isPermaLink="true"><?php echo htmlspecialchars($siteUrl . base_url('public/post.php?slug=' . $post['slug'])); ?></guid>
      <pubDate><?php echo date('r', strtotime($post['pub_date'])); ?></pubDate>
      <?php if (!empty($post['category'])): ?>
      <category><?php echo htmlspecialchars($post['category']); ?></category>
      <?php endif; ?>
      <?php if (!empty($post['excerpt'])): ?>
      <description><?php echo htmlspecialchars($post['excerpt']); ?></description>
      <?php endif; ?>
    </item>
    <?php endforeach; ?>
  </channel>
</rss>
