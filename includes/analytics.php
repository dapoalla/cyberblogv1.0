<?php
// First-party, self-hosted analytics - no external script, no client-side
// beacon. The tracking write is deferred until after the response has
// already been sent to the browser (fastcgi_finish_request when available),
// so it costs nothing on page load time.

function cb_get_visitor_id(): string {
  if (!empty($_COOKIE['cb_vid']) && preg_match('/^[a-f0-9]{32}$/', $_COOKIE['cb_vid'])) {
    return $_COOKIE['cb_vid'];
  }
  $vid = bin2hex(random_bytes(16));
  $isHttps = (!empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off')
    || (($_SERVER['SERVER_PORT'] ?? '') == 443)
    || (($_SERVER['HTTP_X_FORWARDED_PROTO'] ?? '') === 'https');
  $params = ['expires' => time() + 31536000, 'path' => '/', 'domain' => '', 'secure' => $isHttps, 'httponly' => true, 'samesite' => 'Lax'];
  if (PHP_VERSION_ID >= 70300) {
    setcookie('cb_vid', $vid, $params);
  } else {
    setcookie('cb_vid', $vid, $params['expires'], '/');
  }
  $_COOKIE['cb_vid'] = $vid;
  return $vid;
}

function cb_detect_device(string $ua): string {
  if ($ua === '') return 'unknown';
  if (preg_match('/bot|crawl|spider|slurp|bingpreview|facebookexternalhit|whatsapp|telegrambot/i', $ua)) return 'bot';
  if (preg_match('/iPad|Android(?!.*Mobile)|Tablet/i', $ua)) return 'tablet';
  if (preg_match('/Mobi|Android|iPhone|iPod/i', $ua)) return 'mobile';
  return 'desktop';
}

function cb_detect_referrer_domain(): ?string {
  $ref = $_SERVER['HTTP_REFERER'] ?? '';
  if ($ref === '') return null;
  $refHost = parse_url($ref, PHP_URL_HOST);
  $ownHost = $_SERVER['HTTP_HOST'] ?? '';
  if (!$refHost || strcasecmp($refHost, $ownHost) === 0) return null;
  return substr($refHost, 0, 255);
}

function cb_detect_country(): ?string {
  // Zero-cost signal: most real hosting either sits behind Cloudflare
  // (CF-IPCountry) or a similar edge proxy. No external API call, no
  // bundled GeoIP database - if the header isn't present, we simply don't
  // report a country rather than adding latency to look one up.
  $cc = $_SERVER['HTTP_CF_IPCOUNTRY'] ?? $_SERVER['HTTP_X_COUNTRY_CODE'] ?? null;
  if ($cc && $cc !== 'XX' && strlen($cc) === 2) return strtoupper($cc);
  return null;
}

function cb_track_pageview(mysqli $mysqli, ?int $postId = null): void {
  $visitorId = cb_get_visitor_id();
  $path = substr((string)($_SERVER['REQUEST_URI'] ?? ''), 0, 500);
  $device = cb_detect_device($_SERVER['HTTP_USER_AGENT'] ?? '');
  $referrer = cb_detect_referrer_domain();
  $country = cb_detect_country();
  register_shutdown_function(function () use ($mysqli, $visitorId, $path, $postId, $device, $referrer, $country) {
    if (function_exists('fastcgi_finish_request')) { @fastcgi_finish_request(); }
    try {
      $stmt = $mysqli->prepare("INSERT INTO cms_analytics_events (event_type, path, post_id, visitor_id, referrer_domain, device, country) VALUES ('pageview', ?, ?, ?, ?, ?, ?)");
      if (!$stmt) return;
      $stmt->bind_param('sissss', $path, $postId, $visitorId, $referrer, $device, $country);
      $stmt->execute();
      $stmt->close();
    } catch (Throwable $e) {
      // Analytics must never break the page - swallow and move on.
    }
  });
}

function cb_track_search(mysqli $mysqli, string $query): void {
  $query = trim(substr($query, 0, 255));
  if ($query === '') return;
  $visitorId = cb_get_visitor_id();
  $path = substr((string)($_SERVER['REQUEST_URI'] ?? ''), 0, 500);
  $device = cb_detect_device($_SERVER['HTTP_USER_AGENT'] ?? '');
  register_shutdown_function(function () use ($mysqli, $visitorId, $path, $query, $device) {
    if (function_exists('fastcgi_finish_request')) { @fastcgi_finish_request(); }
    try {
      $stmt = $mysqli->prepare("INSERT INTO cms_analytics_events (event_type, path, visitor_id, device, query) VALUES ('search', ?, ?, ?, ?)");
      if (!$stmt) return;
      $stmt->bind_param('ssss', $path, $visitorId, $device, $query);
      $stmt->execute();
      $stmt->close();
    } catch (Throwable $e) {
      // Analytics must never break the page - swallow and move on.
    }
  });
}
