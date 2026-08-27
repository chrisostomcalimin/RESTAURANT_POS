<?php
require_once __DIR__.'/../config/database.php';
require_once __DIR__.'/../config/header.php';
$products=$pdo->query('SELECT id,name,category,price,stock_qty FROM products WHERE active=1 ORDER BY category,name')->fetchAll();
$tables=$pdo->query("SELECT id,table_no,seats FROM restaurant_tables WHERE status='AVAILABLE' ORDER BY id")->fetchAll();
if($_SERVER['REQUEST_METHOD']==='POST'){
 $customer=trim($_POST['customer_name']??''); $tableId=(int)($_POST['table_id']??0);
 $types=$_POST['item_type']??[]; $ids=$_POST['product_id']??[]; $names=$_POST['manual_name']??[]; $cats=$_POST['manual_category']??[]; $prices=$_POST['manual_price']??[]; $qtys=$_POST['qty']??[];
 $valid=[]; $total=0; $error='';
 $n=max(count($types),count($ids),count($qtys));
 for($i=0;$i<$n;$i++){
   $q=(float)($qtys[$i]??0); if($q<=0) continue;
   $type=$types[$i]??'product';
   if($type==='manual'){
      $name=trim($names[$i]??''); $cat=trim($cats[$i]??'Manual'); $price=(float)($prices[$i]??0);
      if($name===''){ $error='Andika jina la bidhaa ya manual.'; break; }
      if($price<=0){ $error='Bei ya bidhaa ya manual lazima iwe zaidi ya 0: '.$name; break; }
      $lt=$q*$price; $total+=$lt; $valid[]=['id'=>null,'name'=>$name,'category'=>$cat?:'Manual','price'=>$price,'qty'=>$q,'line'=>$lt,'manual'=>true];
   } else {
      $pid=(int)($ids[$i]??0); if(!$pid) continue;
      $st=$pdo->prepare('SELECT * FROM products WHERE id=? AND active=1'); $st->execute([$pid]); $p=$st->fetch();
      if(!$p) continue;
      if((float)$p['stock_qty']<$q){$error='Stock haitoshi kwa: '.$p['name'].' (imebaki '.$p['stock_qty'].')';break;}
      $lt=$q*(float)$p['price']; $total+=$lt; $valid[]=['id'=>$p['id'],'name'=>$p['name'],'category'=>$p['category'],'price'=>(float)$p['price'],'qty'=>$q,'line'=>$lt,'manual'=>false];
   }
 }
 if(!$valid&&!$error) $error='Ongeza angalau bidhaa moja.';
 if($valid&&!$error){
  try{
   $pdo->beginTransaction();
   $billNo='B'.date('YmdHis').rand(10,99);
   $st=$pdo->prepare('INSERT INTO bills(bill_no,cashier_id,customer_name,total,table_id) VALUES(?,?,?,?,?)');
   $st->execute([$billNo,$_SESSION['user']['id'],$customer?:null,$total,$tableId?:null]); $bid=$pdo->lastInsertId();
   $it=$pdo->prepare('INSERT INTO bill_items(bill_id,product_id,product_name,quantity,unit_price,line_total) VALUES(?,?,?,?,?,?)');
   $stock=$pdo->prepare('UPDATE products SET stock_qty=stock_qty-? WHERE id=?');
   $mov=$pdo->prepare("INSERT INTO stock_movements(product_id,movement_type,quantity,note,user_id) VALUES(?,'OUT',?,?,?)");
   foreach($valid as $v){
     $it->execute([$bid,$v['id'],$v['name'],$v['qty'],$v['price'],$v['line']]);
     if(!$v['manual']){ $stock->execute([$v['qty'],$v['id']]); $mov->execute([$v['id'],$v['qty'],'Sale Bill '.$billNo,$_SESSION['user']['id']]); }
   }
   if($tableId) $pdo->prepare("UPDATE restaurant_tables SET status='OCCUPIED' WHERE id=?")->execute([$tableId]);
   $pdo->commit(); header('Location: ../prints/bill.php?id='.$bid); exit;
  }catch(Throwable $e){if($pdo->inTransaction())$pdo->rollBack();$error='Imeshindikana kuhifadhi bill: '.$e->getMessage();}
 }
}
?>
<h2>Tengeneza Bill Mpya</h2>
<?php if(!empty($error)):?><div class="alert danger"><?=e($error)?></div><?php endif;?>
<form method="post" id="billForm">
<div class="card"><div class="grid">
<div><label>Jina la Mteja (hiari)</label><input name="customer_name" placeholder="Walk-in customer"></div>
<div><label>Table</label><select name="table_id"><option value="">Take Away / Counter</option><?php foreach($tables as $t):?><option value="<?=$t['id']?>"><?=e($t['table_no'])?> (<?=e($t['seats'])?> seats)</option><?php endforeach;?></select></div>
</div>
<table id="items"><tr><th>Aina</th><th>Bidhaa</th><th>Idadi</th><th>Bei</th><th>Jumla</th><th></th></tr>
<tr class="item"><td><select name="item_type[]" class="type" onchange="toggleRow(this);calc()"><option value="product">Bidhaa Iliyopo</option><option value="manual">Manual</option></select></td>
<td class="productCell"><select name="product_id[]" class="prod" onchange="calc()"><option value="">-- Chagua --</option><?php foreach($products as $p):?><option value="<?=$p['id']?>" data-price="<?=$p['price']?>" data-stock="<?=$p['stock_qty']?>"><?=e($p['category'].' - '.$p['name'])?> (Stock: <?=e($p['stock_qty'])?>)</option><?php endforeach;?></select></td>
<td class="manualCell" style="display:none"><input name="manual_name[]" placeholder="Jina la bidhaa"><input name="manual_category[]" placeholder="Aina/Category" value="Manual"><input name="manual_price[]" type="number" min="0" step="0.01" placeholder="Bei"></td>
<td><input type="number" name="qty[]" min="0.01" step="0.01" value="1" class="qty" onchange="calc()"></td><td class="price">0</td><td class="line">0</td><td><button type="button" class="btn red" onclick="this.closest('tr').remove();calc()">X</button></td></tr></table>
<div class="actions"><button type="button" class="btn gray" onclick="addRow()">+ Ongeza Bidhaa</button></div>
<div class="total">Jumla: TSh <span id="total">0</span></div><br><button class="green">Hifadhi Bill na Print</button></div></form>
<script>
function toggleRow(sel){const r=sel.closest('tr');const manual=sel.value==='manual';r.querySelector('.productCell').style.display=manual?'none':'';r.querySelector('.manualCell').style.display=manual?'':'none';}
function addRow(){let r=document.querySelector('.item').cloneNode(true);r.querySelector('.type').value='product';r.querySelector('.prod').value='';r.querySelectorAll('input').forEach((x,i)=>{if(x.classList.contains('qty'))x.value=1;else if(x.name.startsWith('manual_name'))x.value='';else if(x.name.startsWith('manual_category'))x.value='Manual';else if(x.name.startsWith('manual_price'))x.value='';});r.querySelector('.manualCell').style.display='none';r.querySelector('.productCell').style.display='';r.querySelector('.price').textContent='0';r.querySelector('.line').textContent='0';document.querySelector('#items').appendChild(r)}
function calc(){let t=0;document.querySelectorAll('.item').forEach(r=>{let type=r.querySelector('.type').value,q=parseFloat(r.querySelector('.qty').value)||0,p=0;if(type==='manual')p=parseFloat(r.querySelector('input[name="manual_price[]"]').value)||0;else{let s=r.querySelector('.prod');p=parseFloat(s.options[s.selectedIndex]?.dataset.price||0)}let l=p*q;r.querySelector('.price').textContent=p.toLocaleString();r.querySelector('.line').textContent=l.toLocaleString();t+=l});document.querySelector('#total').textContent=t.toLocaleString()}
document.addEventListener('input',e=>{if(e.target.matches('input[name="manual_price[]"]'))calc()});
</script>
<?php require_once __DIR__.'/../config/footer.php'; ?>
