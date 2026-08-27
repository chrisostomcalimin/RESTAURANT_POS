<?php require_once __DIR__.'/../config/database.php'; require_once __DIR__.'/../config/header.php';
if($_SESSION['user']['role']!=='admin'){http_response_code(403);die('Access denied');}
$msg='';$err='';
if($_SERVER['REQUEST_METHOD']==='POST'){
 $action=$_POST['action']??'';
 try{
  if($action==='add'){
   $u=trim($_POST['username']);$name=trim($_POST['full_name']);$role=$_POST['role'];$pw=$_POST['password'];
   if(!$u||!$name||strlen($pw)<6) throw new Exception('Jaza taarifa zote; password iwe angalau herufi 6.');
   $s=$pdo->prepare('INSERT INTO users(username,password_hash,full_name,role) VALUES(?,?,?,?)');$s->execute([$u,password_hash($pw,PASSWORD_DEFAULT),$name,$role]);$msg='Mtumiaji ameongezwa.';
  } elseif($action==='delete'){
   $id=(int)$_POST['id']; if($id===$_SESSION['user']['id']) throw new Exception('Huwezi kujifuta mwenyewe.');
   $s=$pdo->prepare('DELETE FROM users WHERE id=?');$s->execute([$id]);$msg='Mtumiaji amefutwa.';
  } elseif($action==='reset'){
   $id=(int)$_POST['id'];$pw=$_POST['password']; if(strlen($pw)<6) throw new Exception('Password iwe angalau herufi 6.');
   $s=$pdo->prepare('UPDATE users SET password_hash=? WHERE id=?');$s->execute([password_hash($pw,PASSWORD_DEFAULT),$id]);$msg='Password imebadilishwa.';
  }
 }catch(Throwable $e){$err=$e->getMessage();}
}
$users=$pdo->query('SELECT id,username,full_name,role,created_at FROM users ORDER BY id')->fetchAll();
?><h2>Usimamizi wa Watumiaji</h2><?php if($msg):?><div class="alert success"><?=e($msg)?></div><?php endif;?><?php if($err):?><div class="alert danger"><?=e($err)?></div><?php endif;?><div class="grid"><div class="card"><h3>Ongeza User</h3><form method="post"><input type="hidden" name="action" value="add"><label>Username</label><input name="username" required><label>Jina kamili</label><input name="full_name" required><label>Role</label><select name="role"><option value="cashier">Cashier</option><option value="admin">Admin</option></select><label>Password</label><input type="password" name="password" required minlength="6"><button class="green">+ Ongeza</button></form></div><div class="card"><h3>Watumiaji</h3><table><tr><th>Username</th><th>Jina</th><th>Role</th><th>Action</th></tr><?php foreach($users as $u):?><tr><td><?=e($u['username'])?></td><td><?=e($u['full_name'])?></td><td><?=e($u['role'])?></td><td><form method="post" style="display:inline"><input type="hidden" name="action" value="reset"><input type="hidden" name="id" value="<?=$u['id']?>"><input name="password" placeholder="New password" required minlength="6" style="width:140px;display:inline"><button class="btn gray">Reset</button></form><?php if($u['id']!==$_SESSION['user']['id']):?> <form method="post" style="display:inline" onsubmit="return confirm('Futa user huyu?')"><input type="hidden" name="action" value="delete"><input type="hidden" name="id" value="<?=$u['id']?>"><button class="btn red">Futa</button></form><?php endif;?></td></tr><?php endforeach;?></table></div></div><?php require_once __DIR__.'/../config/footer.php';?>
