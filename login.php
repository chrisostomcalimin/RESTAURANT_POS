<?php require_once __DIR__.'/../config/database.php'; require_once __DIR__.'/../config/auth.php';
if(!empty($_SESSION['user'])){header('Location: ../dashboard.php');exit;}
$error='';
if($_SERVER['REQUEST_METHOD']==='POST'){
 $st=$pdo->prepare('SELECT * FROM users WHERE username=? LIMIT 1'); $st->execute([trim($_POST['username']??'')]); $u=$st->fetch();
 if($u && password_verify($_POST['password']??'',$u['password_hash'])){ $_SESSION['user']=['id'=>$u['id'],'username'=>$u['username'],'name'=>$u['full_name'],'role'=>$u['role']]; header('Location: ../dashboard.php'); exit; }
 $error='Username au password si sahihi.';
}
?><!doctype html><html lang="sw"><head><meta charset="utf-8"><meta name="viewport" content="width=device-width,initial-scale=1"><title>Restaurant POS - Login</title><link rel="stylesheet" href="../assets/style.css"></head><body class="login"><div class="login-card"><h1 style="text-align: center;">Mikumi VTC Restaurant POS</h1><p></p><?php if($error):?><div class="alert danger"><?=e($error)?></div><?php endif;?><form method="post"><label>Username</label><input name="username" required autofocus><label>Password</label><input type="password" name="password" required><button>Ingia</button></form><small></b></small></div></body></html>
