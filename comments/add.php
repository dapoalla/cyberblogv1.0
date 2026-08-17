<?php
require __DIR__ . '/../includes/db.php';
require __DIR__ . '/../includes/helpers.php';
session_name('cr_blog2_pub'); if(session_status()===PHP_SESSION_NONE) session_start();
if($_SERVER['REQUEST_METHOD']!=='POST'){ http_response_code(405); echo 'Method not allowed'; exit; }
$pid=(int)($_POST['post_id']??0); $content=trim($_POST['content']??''); $slug=$_POST['slug']??'';
if($pid<=0 || $content===''){ http_response_code(400); echo 'Missing fields'; exit; }
if(empty($_SESSION['pub_user'])){ $_SESSION['after_login_redirect']=base_url('public/post.php?slug='.$slug); header('Location: '.base_url('comments/google_auth.php')); exit; }
$uid=(int)$_SESSION['pub_user']['id'];
$stmt=$mysqli->prepare("INSERT INTO cms_comments (post_id,oauth_user_id,content) VALUES (?,?,?)"); $stmt->bind_param('iis',$pid,$uid,$content); $stmt->execute(); $stmt->close();
header('Location: '.base_url('public/post.php?slug='.$slug).'#comments'); exit;
