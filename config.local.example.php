<?php
// Copy this file to config.local.php and fill in your real values.
// config.local.php is git-ignored and never committed.
return [
  'site_name' => 'CyberBlog v2.0',
  'db' => [
    'host' => 'localhost',
    'name' => 'your_db_name',
    'user' => 'your_db_user',
    'pass' => 'your_db_password',
    'port' => 3306,
  ],
  'oauth' => [
    'client_id' => '',
    'client_secret' => '',
    'redirect_uri' => 'https://yourdomain.com/comments/google_callback.php',
  ],
  'tinymce' => [
    'api_key' => 'no-api-key',
  ],
];
