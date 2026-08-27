<?php
session_start();
function require_login(){ if(empty($_SESSION['user'])){ header('Location: /restaurant_pos/auth/login.php'); exit; } }
function e($v){ return htmlspecialchars((string)$v,ENT_QUOTES,'UTF-8'); }
?>
