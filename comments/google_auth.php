<?php
require_once __DIR__ . '/../includes/helpers.php';
start_public_session();

// Capture the return URL from referer if not already set
if (empty($_SESSION['after_login_redirect']) && !empty($_SERVER['HTTP_REFERER'])) {
  $_SESSION['after_login_redirect'] = $_SERVER['HTTP_REFERER'];
}

// Load client from config first
$app = require __DIR__ . '/../config.php';
$oauth = $app['oauth'] ?? [];
$client_id = $oauth['client_id'] ?? '';
$auth_uri = $oauth['auth_uri'] ?? 'https://accounts.google.com/o/oauth2/auth';
if (empty($client_id)) { http_response_code(500); echo 'Google sign-in is not configured yet. An admin can set this up at Admin -> Settings -> Google Sign-In.'; exit; }
$_SESSION['g_state']=bin2hex(random_bytes(16));
// Always match the host the visitor is actually on (not a hardcoded
// config value) - the session cookie that holds g_state is scoped to
// whatever host set it, so if www and non-www are both registered with
// Google but redirect_uri is pinned to one of them, a visitor arriving on
// the other one loses their session on the way back and gets "Invalid
// state". Register both variants with Google if you serve both.
$redirect = ((isset($_SERVER['HTTPS'])&&$_SERVER['HTTPS']==='on')?'https://':'http://').$_SERVER['HTTP_HOST'].base_url('comments/google_callback.php');
$params=[ 'client_id'=>$client_id, 'redirect_uri'=>$redirect, 'response_type'=>'code', 'scope'=>'openid email profile', 'state'=>$_SESSION['g_state'], 'prompt'=>'select_account' ];
header('Location: '.$auth_uri.'?'.http_build_query($params));
exit;
