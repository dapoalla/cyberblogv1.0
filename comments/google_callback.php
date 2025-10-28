<?php
require __DIR__ . '/../includes/db.php';
require __DIR__ . '/../includes/helpers.php';
session_name('cr_blog2_pub'); if(session_status()===PHP_SESSION_NONE) session_start();
// Prefer config.php oauth
$app = require __DIR__ . '/../config.php';
$oauth = $app['oauth'] ?? [];
$client_id = $oauth['client_id'] ?? '';
$client_secret = $oauth['client_secret'] ?? '';
$token_uri = $oauth['token_uri'] ?? 'https://oauth2.googleapis.com/token';
if (empty($client_id) || empty($client_secret)) {
  // Fallback to client_secret.json
  $paths=[__DIR__.'/../client_secret.json', __DIR__.'/../../blog/client_secret_183753845233-ifg8fji2i03nmsh0873arfda749kn11s.apps.googleusercontent.com.json'];
  $cfg=[]; foreach($paths as $p){ if(file_exists($p)){ $cfg=json_decode(file_get_contents($p),true); break; } }
  $client=$cfg['web']??[]; $client_id=$client['client_id']??$client_id; $client_secret=$client['client_secret']??$client_secret; $token_uri=$client['token_uri']??$token_uri;
}
if(empty($_GET['state']) || !hash_equals($_SESSION['g_state']??'', (string)$_GET['state'])){ http_response_code(400); echo 'Invalid state'; exit; }
unset($_SESSION['g_state']);
$code=$_GET['code']??''; if(!$code){ http_response_code(400); echo 'Missing code'; exit; }
$redirect = $oauth['redirect_uri'] ?? ((isset($_SERVER['HTTPS'])&&$_SERVER['HTTPS']==='on')?'https://':'http://').$_SERVER['HTTP_HOST'].base_url('comments/google_callback.php');
$post=http_build_query(['code'=>$code,'client_id'=>$client_id,'client_secret'=>$client_secret,'redirect_uri'=>$redirect,'grant_type'=>'authorization_code']);
$ch=curl_init($token_uri); curl_setopt($ch,CURLOPT_POST,1); curl_setopt($ch,CURLOPT_POSTFIELDS,$post); curl_setopt($ch,CURLOPT_RETURNTRANSFER,1); $res=curl_exec($ch); curl_close($ch);
$data=json_decode($res,true); $id_token=$data['id_token']??''; if(!$id_token){ http_response_code(500); echo 'No id_token'; exit; }
$parts=explode('.',$id_token); $payload=json_decode(base64_decode(strtr($parts[1]??'','-_','+/')),true);
$sub=$payload['sub']??''; $email=$payload['email']??null; $name=$payload['name']??null; $picture=$payload['picture']??null; if(!$sub){ http_response_code(500); echo 'Invalid token'; exit; }
$stmt=$mysqli->prepare("SELECT id FROM cms_oauth_users WHERE provider='google' AND provider_id=? LIMIT 1"); $stmt->bind_param('s',$sub); $stmt->execute(); $row=$stmt->get_result()->fetch_assoc(); $stmt->close();
if($row){ $uid=(int)$row['id']; $stmt=$mysqli->prepare("UPDATE cms_oauth_users SET email=?, name=?, picture=?, last_login_at=NOW() WHERE id=?"); $stmt->bind_param('sssi',$email,$name,$picture,$uid); $stmt->execute(); $stmt->close(); }
else { $stmt=$mysqli->prepare("INSERT INTO cms_oauth_users (provider,provider_id,email,name,picture,last_login_at) VALUES ('google',?,?,?,?,NOW())"); $stmt->bind_param('ssss',$sub,$email,$name,$picture); $stmt->execute(); $uid=$stmt->insert_id; $stmt->close(); }
$_SESSION['pub_user']=['id'=>$uid,'provider'=>'google','email'=>$email,'name'=>$name,'picture'=>$picture];
$to=$_SESSION['after_login_redirect']??base_url('public/index.php'); unset($_SESSION['after_login_redirect']); header('Location: '.$to); exit;
