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
}
