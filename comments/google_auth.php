<?php
require __DIR__ . '/../includes/helpers.php';
session_name('cr_blog2_pub'); if(session_status()===PHP_SESSION_NONE) session_start();

// Capture the return URL from referer if not already set
if (empty($_SESSION['after_login_redirect']) && !empty($_SERVER['HTTP_REFERER'])) {
  $_SESSION['after_login_redirect'] = $_SERVER['HTTP_REFERER'];
}

// Load client from config first
$app = require __DIR__ . '/../config.php';
$oauth = $app['oauth'] ?? [];
$client_id = $oauth['client_id'] ?? '';
$auth_uri = $oauth['auth_uri'] ?? 'https://accounts.google.com/o/oauth2/auth';
if (empty($client_id)) { http_response_code(500); echo 'Google OAuth client_id not configured. Please set in config.php (oauth.client_id).'; exit; }
$_SESSION['g_state']=bin2hex(random_bytes(16));
$redirect = $oauth['redirect_uri'] ?? ((isset($_SERVER['HTTPS'])&&$_SERVER['HTTPS']==='on')?'https://':'http://').$_SERVER['HTTP_HOST'].base_url('comments/google_callback.php');
$params=[ 'client_id'=>$client_id, 'redirect_uri'=>$redirect, 'response_type'=>'code', 'scope'=>'openid email profile', 'state'=>$_SESSION['g_state'], 'prompt'=>'select_account' ];
header('Location: '.$auth_uri.'?'.http_build_query($params));
exit;
