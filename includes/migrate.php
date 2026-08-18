<?php
// Lightweight self-healing migration: adds columns that newer app versions
// need to cms_settings if an existing install doesn't have them yet.
// Safe to run on every request - checks column existence first.
function cyberblog_migrate(mysqli $mysqli): void {
  $existing = [];
  if ($res = $mysqli->query("SHOW COLUMNS FROM cms_settings")) {
    while ($row = $res->fetch_assoc()) $existing[$row['Field']] = true;
  }
  $wanted = [
    'site_name' => "ALTER TABLE cms_settings ADD COLUMN site_name VARCHAR(255) NULL",
    'site_tagline' => "ALTER TABLE cms_settings ADD COLUMN site_tagline TEXT NULL",
    'footer_text' => "ALTER TABLE cms_settings ADD COLUMN footer_text VARCHAR(500) NULL",
    'about_content_html' => "ALTER TABLE cms_settings ADD COLUMN about_content_html LONGTEXT NULL",
    'nav_category_ids' => "ALTER TABLE cms_settings ADD COLUMN nav_category_ids VARCHAR(255) NULL",
    'onboarding_dismissed' => "ALTER TABLE cms_settings ADD COLUMN onboarding_dismissed TINYINT(1) NOT NULL DEFAULT 0",
  ];
  foreach ($wanted as $col => $ddl) {
    if (!isset($existing[$col])) {
      $mysqli->query($ddl);
    }
  }

  $tableCheck = $mysqli->query("SHOW TABLES LIKE 'cms_analytics_events'");
  if (!$tableCheck || $tableCheck->num_rows === 0) {
    $mysqli->query("CREATE TABLE cms_analytics_events (
      id BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
      event_type ENUM('pageview','search') NOT NULL DEFAULT 'pageview',
      path VARCHAR(500) NOT NULL,
      post_id INT UNSIGNED DEFAULT NULL,
      visitor_id CHAR(32) NOT NULL,
      referrer_domain VARCHAR(255) DEFAULT NULL,
      device ENUM('desktop','mobile','tablet','bot','unknown') NOT NULL DEFAULT 'unknown',
      country VARCHAR(2) DEFAULT NULL,
      query VARCHAR(255) DEFAULT NULL,
      created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
      PRIMARY KEY (id),
      KEY idx_created_at (created_at),
      KEY idx_visitor_time (visitor_id, created_at),
      KEY idx_post (post_id),
      KEY idx_path (path(191)),
      KEY idx_event_type (event_type)
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4");
  }
}
