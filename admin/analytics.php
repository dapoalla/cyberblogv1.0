<?php
require_once __DIR__ . '/../includes/auth.php';
require_admin();

$pageTitle = 'Analytics';
$metaDescription = '';

$days = isset($_GET['days']) && in_array((int)$_GET['days'], [7, 30, 90], true) ? (int)$_GET['days'] : 30;

// --- Summary ---
$summary = ['views' => 0, 'visitors' => 0, 'searches' => 0];
if ($res = $mysqli->query("SELECT COUNT(*) views, COUNT(DISTINCT visitor_id) visitors FROM cms_analytics_events WHERE event_type='pageview' AND device<>'bot' AND created_at >= NOW() - INTERVAL $days DAY")) {
  $row = $res->fetch_assoc();
  $summary['views'] = (int)$row['views'];
  $summary['visitors'] = (int)$row['visitors'];
}
if ($res = $mysqli->query("SELECT COUNT(*) c FROM cms_analytics_events WHERE event_type='search' AND created_at >= NOW() - INTERVAL $days DAY")) {
  $summary['searches'] = (int)$res->fetch_assoc()['c'];
}

// --- Daily page views (filled for every day in range, even zero days) ---
$dailyRaw = [];
if ($res = $mysqli->query("SELECT DATE(created_at) d, COUNT(*) c FROM cms_analytics_events WHERE event_type='pageview' AND device<>'bot' AND created_at >= NOW() - INTERVAL $days DAY GROUP BY DATE(created_at)")) {
  while ($row = $res->fetch_assoc()) $dailyRaw[$row['d']] = (int)$row['c'];
}
$chartData = [];
for ($i = $days - 1; $i >= 0; $i--) {
  $d = date('Y-m-d', strtotime("-$i days"));
  $chartData[$d] = $dailyRaw[$d] ?? 0;
}
$maxDaily = max(1, max($chartData));

// --- Popular articles (from real event data, not just the running counter) ---
$popular = [];
if ($res = $mysqli->query("SELECT e.post_id, p.title, p.slug, COUNT(*) views, COUNT(DISTINCT e.visitor_id) uniques
  FROM cms_analytics_events e JOIN cms_posts p ON p.id = e.post_id
  WHERE e.event_type='pageview' AND e.device<>'bot' AND e.post_id IS NOT NULL AND e.created_at >= NOW() - INTERVAL $days DAY
  GROUP BY e.post_id, p.title, p.slug ORDER BY views DESC LIMIT 10")) {
  while ($row = $res->fetch_assoc()) $popular[] = $row;
}

// --- Referrers ---
$referrers = [];
if ($res = $mysqli->query("SELECT referrer_domain, COUNT(*) c FROM cms_analytics_events WHERE event_type='pageview' AND device<>'bot' AND referrer_domain IS NOT NULL AND created_at >= NOW() - INTERVAL $days DAY GROUP BY referrer_domain ORDER BY c DESC LIMIT 10")) {
  while ($row = $res->fetch_assoc()) $referrers[] = $row;
}
$directCount = 0;
if ($res = $mysqli->query("SELECT COUNT(*) c FROM cms_analytics_events WHERE event_type='pageview' AND device<>'bot' AND referrer_domain IS NULL AND created_at >= NOW() - INTERVAL $days DAY")) {
  $directCount = (int)$res->fetch_assoc()['c'];
}

// --- Device breakdown ---
$devices = ['desktop' => 0, 'mobile' => 0, 'tablet' => 0, 'bot' => 0, 'unknown' => 0];
if ($res = $mysqli->query("SELECT device, COUNT(*) c FROM cms_analytics_events WHERE event_type='pageview' AND created_at >= NOW() - INTERVAL $days DAY GROUP BY device")) {
  while ($row = $res->fetch_assoc()) $devices[$row['device']] = (int)$row['c'];
}
$deviceTotal = max(1, array_sum($devices));

// --- Country breakdown ---
$countries = [];
if ($res = $mysqli->query("SELECT country, COUNT(*) c FROM cms_analytics_events WHERE event_type='pageview' AND device<>'bot' AND country IS NOT NULL AND created_at >= NOW() - INTERVAL $days DAY GROUP BY country ORDER BY c DESC LIMIT 10")) {
  while ($row = $res->fetch_assoc()) $countries[] = $row;
}

// --- Top search queries ---
$searchQueries = [];
if ($res = $mysqli->query("SELECT query, COUNT(*) c FROM cms_analytics_events WHERE event_type='search' AND created_at >= NOW() - INTERVAL $days DAY GROUP BY query ORDER BY c DESC LIMIT 10")) {
  while ($row = $res->fetch_assoc()) $searchQueries[] = $row;
}

// --- Exit rate per page ---
// A "session" is a run of pageviews from the same visitor with no gap over
// 30 minutes. Exit rate = the share of a page's views that were the last
// pageview in their session.
$exitRates = [];
$exitSql = "
  WITH events_ordered AS (
    SELECT id, visitor_id, path, created_at,
      TIMESTAMPDIFF(SECOND, LAG(created_at) OVER (PARTITION BY visitor_id ORDER BY created_at), created_at) AS gap_seconds
    FROM cms_analytics_events
    WHERE event_type='pageview' AND device<>'bot' AND created_at >= NOW() - INTERVAL $days DAY
  ),
  sess AS (
    SELECT *, SUM(CASE WHEN gap_seconds IS NULL OR gap_seconds > 1800 THEN 1 ELSE 0 END) OVER (PARTITION BY visitor_id ORDER BY created_at) AS session_num
    FROM events_ordered
  ),
  session_last AS (
    SELECT visitor_id, session_num, MAX(created_at) AS last_at FROM sess GROUP BY visitor_id, session_num
  ),
  agg AS (
    SELECT s.path AS path, COUNT(*) AS views, SUM(CASE WHEN s.created_at = sl.last_at THEN 1 ELSE 0 END) AS exits
    FROM sess s JOIN session_last sl ON sl.visitor_id = s.visitor_id AND sl.session_num = s.session_num
    GROUP BY s.path
  )
  SELECT path, views, exits FROM agg WHERE views >= 3 ORDER BY (exits / views) DESC LIMIT 10
";
if ($res = $mysqli->query($exitSql)) {
  while ($row = $res->fetch_assoc()) $exitRates[] = $row;
}

// Legacy running counter, kept as a fallback view of all-time totals
$posts = [];
if ($res = $mysqli->query("SELECT id, title, slug, views, COALESCE(published_at, created_at) AS dt FROM cms_posts ORDER BY views DESC, dt DESC LIMIT 10")) {
  while ($row = $res->fetch_assoc()) $posts[] = $row;
}

include __DIR__ . '/../includes/template_header.php';
include __DIR__ . '/../includes/admin_nav.php';

$deviceLabels = ['desktop' => 'Desktop', 'mobile' => 'Mobile', 'tablet' => 'Tablet', 'bot' => 'Bot/Crawler', 'unknown' => 'Unknown'];
$deviceColors = ['desktop' => 'bg-sky-500', 'mobile' => 'bg-emerald-500', 'tablet' => 'bg-amber-500', 'bot' => 'bg-neutral-600', 'unknown' => 'bg-neutral-700'];
?>
<div class="flex items-center justify-between flex-wrap gap-3">
  <div>
    <h1 class="text-2xl font-bold">Analytics</h1>
    <p class="text-neutral-400 text-sm mt-1">First-party, self-hosted - no third-party script, no cost to page load</p>
  </div>
  <div class="flex gap-2 text-sm">
    <?php foreach ([7, 30, 90] as $d): ?>
      <a href="?days=<?php echo $d; ?>" class="px-3 py-1.5 rounded <?php echo $days === $d ? 'bg-sky-500 text-white' : 'bg-neutral-800 text-neutral-300 hover:bg-neutral-700'; ?>"><?php echo $d; ?>d</a>
    <?php endforeach; ?>
  </div>
</div>

<div class="grid gap-4 md:grid-cols-3 mt-6">
  <div class="bg-neutral-900 border border-neutral-800 rounded p-4">
    <div class="text-neutral-400 text-sm">Page Views</div>
    <div class="text-3xl font-bold mt-1"><?php echo number_format($summary['views']); ?></div>
  </div>
  <div class="bg-neutral-900 border border-neutral-800 rounded p-4">
    <div class="text-neutral-400 text-sm">Unique Visitors</div>
    <div class="text-3xl font-bold mt-1"><?php echo number_format($summary['visitors']); ?></div>
  </div>
  <div class="bg-neutral-900 border border-neutral-800 rounded p-4">
    <div class="text-neutral-400 text-sm">Search Queries</div>
    <div class="text-3xl font-bold mt-1"><?php echo number_format($summary['searches']); ?></div>
  </div>
</div>

<div class="mt-6 bg-neutral-900 border border-neutral-800 rounded-lg p-6">
  <h2 class="font-semibold mb-4">Page Views Over Time</h2>
  <?php if ($summary['views'] === 0): ?>
    <p class="text-neutral-400 text-sm">No traffic recorded yet in this window.</p>
  <?php else: ?>
    <div class="flex items-end gap-1" style="height:120px">
      <?php foreach ($chartData as $date => $count): ?>
        <div class="flex-1 bg-sky-500 hover:bg-sky-400 rounded-t" style="height:<?php echo max(2, round($count / $maxDaily * 100)); ?>%" title="<?php echo e($date . ': ' . $count . ' views'); ?>"></div>
      <?php endforeach; ?>
    </div>
    <div class="flex justify-between text-xs text-neutral-500 mt-2">
      <span><?php echo e(array_key_first($chartData)); ?></span>
      <span><?php echo e(array_key_last($chartData)); ?></span>
    </div>
  <?php endif; ?>
</div>

<div class="grid gap-6 lg:grid-cols-2 mt-6">
  <div class="bg-neutral-900 border border-neutral-800 rounded-lg p-6">
    <h2 class="font-semibold mb-4">Popular Articles</h2>
    <?php if (empty($popular)): ?>
      <p class="text-neutral-400 text-sm">No article views recorded yet in this window.</p>
    <?php else: ?>
      <table class="w-full text-sm">
        <thead class="text-neutral-400 text-left"><tr><th class="pb-2">Title</th><th class="pb-2 text-right">Views</th><th class="pb-2 text-right">Uniques</th></tr></thead>
        <tbody>
          <?php foreach ($popular as $p): ?>
            <tr class="border-t border-neutral-800">
              <td class="py-2"><a href="edit_post.php?id=<?php echo (int)$p['post_id']; ?>" class="hover:text-sky-400"><?php echo e($p['title']); ?></a></td>
              <td class="py-2 text-right font-semibold"><?php echo (int)$p['views']; ?></td>
              <td class="py-2 text-right text-neutral-400"><?php echo (int)$p['uniques']; ?></td>
            </tr>
          <?php endforeach; ?>
        </tbody>
      </table>
    <?php endif; ?>
  </div>

  <div class="bg-neutral-900 border border-neutral-800 rounded-lg p-6">
    <h2 class="font-semibold mb-4">Top Referrers</h2>
    <?php if (empty($referrers) && $directCount === 0): ?>
      <p class="text-neutral-400 text-sm">No referrer data recorded yet in this window.</p>
    <?php else: ?>
      <table class="w-full text-sm">
        <tbody>
          <?php if ($directCount > 0): ?>
            <tr class="border-t border-neutral-800"><td class="py-2">Direct / none</td><td class="py-2 text-right font-semibold"><?php echo number_format($directCount); ?></td></tr>
          <?php endif; ?>
          <?php foreach ($referrers as $r): ?>
            <tr class="border-t border-neutral-800"><td class="py-2"><?php echo e($r['referrer_domain']); ?></td><td class="py-2 text-right font-semibold"><?php echo number_format($r['c']); ?></td></tr>
          <?php endforeach; ?>
        </tbody>
      </table>
    <?php endif; ?>
  </div>

  <div class="bg-neutral-900 border border-neutral-800 rounded-lg p-6">
    <h2 class="font-semibold mb-4">Devices</h2>
    <div class="space-y-2">
      <?php foreach ($devices as $key => $count): if ($count === 0) continue; ?>
        <div>
          <div class="flex justify-between text-sm mb-1"><span><?php echo e($deviceLabels[$key] ?? $key); ?></span><span class="text-neutral-400"><?php echo number_format($count); ?> (<?php echo round($count / $deviceTotal * 100); ?>%)</span></div>
          <div class="h-2 rounded bg-neutral-800 overflow-hidden"><div class="h-full <?php echo $deviceColors[$key] ?? 'bg-neutral-600'; ?>" style="width:<?php echo round($count / $deviceTotal * 100); ?>%"></div></div>
        </div>
      <?php endforeach; ?>
    </div>
  </div>

  <div class="bg-neutral-900 border border-neutral-800 rounded-lg p-6">
    <h2 class="font-semibold mb-4">Countries</h2>
    <?php if (empty($countries)): ?>
      <p class="text-neutral-400 text-sm">No country data available. This is populated automatically if the host sits behind Cloudflare or a similar edge proxy - no external lookups are made, to avoid adding latency.</p>
    <?php else: ?>
      <table class="w-full text-sm">
        <tbody>
          <?php foreach ($countries as $c): ?>
            <tr class="border-t border-neutral-800"><td class="py-2"><?php echo e($c['country']); ?></td><td class="py-2 text-right font-semibold"><?php echo number_format($c['c']); ?></td></tr>
          <?php endforeach; ?>
        </tbody>
      </table>
    <?php endif; ?>
  </div>

  <div class="bg-neutral-900 border border-neutral-800 rounded-lg p-6">
    <h2 class="font-semibold mb-4">Top Search Queries</h2>
    <?php if (empty($searchQueries)): ?>
      <p class="text-neutral-400 text-sm">No searches recorded yet in this window.</p>
    <?php else: ?>
      <table class="w-full text-sm">
        <tbody>
          <?php foreach ($searchQueries as $s): ?>
            <tr class="border-t border-neutral-800"><td class="py-2"><?php echo e($s['query']); ?></td><td class="py-2 text-right font-semibold"><?php echo number_format($s['c']); ?></td></tr>
          <?php endforeach; ?>
        </tbody>
      </table>
    <?php endif; ?>
  </div>

  <div class="bg-neutral-900 border border-neutral-800 rounded-lg p-6">
    <h2 class="font-semibold mb-4">Highest Exit Rate</h2>
    <p class="text-xs text-neutral-500 mb-3">Pages people leave from most often (last page in a session before a 30+ minute gap). Only pages with 3+ views in this window are shown.</p>
    <?php if (empty($exitRates)): ?>
      <p class="text-neutral-400 text-sm">Not enough session data yet in this window.</p>
    <?php else: ?>
      <table class="w-full text-sm">
        <thead class="text-neutral-400 text-left"><tr><th class="pb-2">Path</th><th class="pb-2 text-right">Exit Rate</th></tr></thead>
        <tbody>
          <?php foreach ($exitRates as $e): ?>
            <tr class="border-t border-neutral-800">
              <td class="py-2 font-mono text-xs"><?php echo e($e['path']); ?></td>
              <td class="py-2 text-right font-semibold"><?php echo round($e['exits'] / max(1, $e['views']) * 100); ?>%</td>
            </tr>
          <?php endforeach; ?>
        </tbody>
      </table>
    <?php endif; ?>
  </div>
</div>

<div class="mt-6 bg-neutral-900 border border-neutral-800 rounded-lg overflow-auto">
  <h2 class="font-semibold p-6 pb-0">All-Time Most Viewed (legacy counter)</h2>
  <table class="min-w-full text-sm mt-4">
    <thead class="bg-neutral-950">
      <tr class="text-left">
        <th class="px-4 py-3 border-b border-neutral-800">#</th>
        <th class="px-4 py-3 border-b border-neutral-800">Title</th>
        <th class="px-4 py-3 border-b border-neutral-800">Views</th>
        <th class="px-4 py-3 border-b border-neutral-800">Published</th>
        <th class="px-4 py-3 border-b border-neutral-800">Actions</th>
      </tr>
    </thead>
    <tbody>
      <?php $i=1; foreach ($posts as $p): ?>
      <tr class="hover:bg-neutral-800/50">
        <td class="px-4 py-3 border-b border-neutral-800 text-neutral-400"><?php echo $i++; ?></td>
        <td class="px-4 py-3 border-b border-neutral-800">
          <div class="font-medium"><?php echo e($p['title']); ?></div>
          <div class="text-xs text-neutral-500">Slug: <?php echo e($p['slug']); ?> · ID: <?php echo (int)$p['id']; ?></div>
        </td>
        <td class="px-4 py-3 border-b border-neutral-800 font-semibold"><?php echo (int)$p['views']; ?></td>
        <td class="px-4 py-3 border-b border-neutral-800 text-neutral-400"><?php echo e($p['dt']); ?></td>
        <td class="px-4 py-3 border-b border-neutral-800 text-sm">
          <a href="<?php echo base_url('admin/edit_post.php?id='.(int)$p['id']); ?>" class="text-sky-400 hover:underline">Edit</a>
          <span class="mx-2 text-neutral-600">|</span>
          <a href="<?php echo base_url('post/'.e($p['slug'])); ?>" class="text-neutral-300 hover:underline" target="_blank">View</a>
        </td>
      </tr>
      <?php endforeach; ?>
      <?php if (!$posts): ?>
      <tr><td class="px-4 py-6 text-neutral-400" colspan="5">No posts found.</td></tr>
      <?php endif; ?>
    </tbody>
  </table>
</div>

<?php include __DIR__ . '/../includes/template_footer.php'; ?>
