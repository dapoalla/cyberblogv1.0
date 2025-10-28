<?php
require __DIR__ . '/db.php';
require __DIR__ . '/helpers.php';
require __DIR__ . '/csrf.php';
function require_admin(){ if(empty($_SESSION['admin'])){ header('Location: '.base_url('admin/login.php')); exit; } }
