<?php
require __DIR__ . '/../includes/helpers.php';
require __DIR__ . '/../includes/csrf.php';
$_SESSION = [];
session_destroy();
header('Location: ' . base_url('admin/login.php'));
exit;
