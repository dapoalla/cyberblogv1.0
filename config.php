<?php
// config.php - Core configuration (safe to commit; contains no secrets).
// Real credentials live in config.local.php, which is git-ignored.
// Copy config.local.example.php to config.local.php and fill in your values,
// or just let install/index.php write it for you on first run.
$defaults = [
  'site_name' => 'CyberBlog v2.0',
  'base_url' => '', // since subdomain points here
  'db' => [
    'host' => 'localhost',
    'name' => '',
    'user' => '',
    'pass' => '',
    'port' => 3306,
  ],
  'security' => [
    'session_name' => 'cyberblog_sess',
    'csrf_key' => 'cyberblog_csrf',
  ],
  'oauth' => [
    'client_id' => '',
    'client_secret' => '',
    'auth_uri' => 'https://accounts.google.com/o/oauth2/auth',
    'token_uri' => 'https://oauth2.googleapis.com/token',
    'redirect_uri' => '',
  ],
  'tinymce' => [
    'api_key' => 'no-api-key',
  ],
];

$localFile = __DIR__ . '/config.local.php';
if (file_exists($localFile)) {
  $overrides = require $localFile;
  return array_replace_recursive($defaults, is_array($overrides) ? $overrides : []);
}

return $defaults;
