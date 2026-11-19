<?php require 'inc.php';need('user');
$p=$_GET['p']??'products';$L=['profile'=>'Profile','products'=>'Products','transactions'=>'Transaction History','logout'=>'Log out'];
$C=&$_SESSION['cart'];$C=$C??[];$err='';
if($_SERVER['REQUEST_METHOD']=='POST'&&$p=='products'){$a=$_POST['a']??'';$id=(int)($_POST['id']??0);
 if($a=='add'){$s=$pdo->prepare("SELECT stock FROM products WHERE id=?");$s->execute([$id]);$st=(int)$s->fetchColumn();if(($C[$id]??0)<$st)$C[$id]=($C[$id]??0)+1;}
 if($a=='sub'){if(isset($C[$id])&&--$C[$id]<=0)unset($C[$id]);}
 if($a=='clear')$C=[];
 if($a=='pay'&&$C){$pm=$_POST['pay'];
  try{$pdo->beginTransaction();$tot=0;$lines=[];
   foreach($C as $pid=>$q){$s=$pdo->prepare("SELECT * FROM products WHERE id=? FOR UPDATE");$s->execute([$pid]);$r=$s->fetch();
    if(!$r||$r['stock']<$q)throw new Exception('Not enough stock for '.($r['title']??'item'));
    $pdo->prepare("UPDATE products SET stock=stock-? WHERE id=?")->execute([$q,$pid]);$tot+=$q*$r['price'];$lines[]=[$pid,$r['title'],$r['price'],$q];}
   $pdo->prepare("INSERT INTO orders(user_id,total,payment,created_at) VALUES(?,?,?,NOW())")->execute([$_SESSION['u']['id'],$tot,$pm]);$oid=$pdo->lastInsertId();
   foreach($lines as $l)$pdo->prepare("INSERT INTO order_items(order_id,product_id,title,price,qty) VALUES(?,?,?,?,?)")->execute([$oid,...$l]);
   $pdo->commit();$C=[];header("Location: ?p=view&id=$oid");exit;
  }catch(Exception $e){$pdo->rollBack();$err=$e->getMessage();}}
 if(!$err){header('Location: ?p=products'.(isset($_GET['g'])?'&g='.(int)$_GET['g']:''));exit;}}
top('Shop',$L,$p==='view'?'transactions':$p);
if($p=='profile')profile_page('user');
elseif($p=='transactions')tx_list(false);
elseif($p=='view')receipt((int)$_GET['id'],false);
else{$g=(int)($_GET['g']??0);$q=trim($_GET['q']??'');
 $sql="SELECT p.*,g.name gn FROM products p LEFT JOIN genres g ON g.id=p.genre_id WHERE (?=0 OR p.genre_id=?) AND (p.title LIKE ? OR p.author LIKE ?) ORDER BY p.title";$s=$pdo->prepare($sql);$s->execute([$g,$g,"%$q%","%$q%"]);
 echo '<h2>Products</h2>'.($err?'<p class="err">'.h($err).'</p>':'').'<form method="get" style="display:flex;gap:8px"><input type="hidden" name="p" value="products"><input name="q" value="'.h($q).'" placeholder="Search title or author"><select name="g" style="max-width:200px"><option value="0">All genres</option>';
 foreach($pdo->query("SELECT * FROM genres ORDER BY name") as $r)echo '<option value="'.$r['id'].'"'.($g==$r['id']?' selected':'').'>'.h($r['name']).'</option>';
 echo '</select><button class="btn">Filter</button></form><div class="two"><div class="pg">';
 foreach($s as $r){$n=$C[$r['id']]??0;
  echo '<div class="pc">'.($r['image']?'<img src="uploads/'.h($r['image']).'">':'<div class="im">📕</div>').'<div class="b"><b>'.h($r['title']).'</b><br><small>'.h($r['author']).' • '.h($r['gn']).'</small><br><b style="color:#14532d">'.money($r['price']).'</b> <small class="'.($r['stock']<=10?'low':'').'">'.($r['stock']>0?$r['stock'].' in stock':'Out of stock').'</small><div class="qty" style="margin-top:8px">';
  foreach([['sub','−'],['add','+']] as $x){if($x[0]=='sub')echo '<form method="post"><input type="hidden" name="a" value="sub"><input type="hidden" name="id" value="'.$r['id'].'"><button class="btn sm">−</button></form><b>'.$n.'</b>';else echo '<form method="post"><input type="hidden" name="a" value="add"><input type="hidden" name="id" value="'.$r['id'].'"><button class="btn sm"'.($r['stock']<=$n?' disabled':'').'>+</button></form>';}
  echo '</div></div></div>';}
 echo '</div><div class="card" style="position:sticky;top:75px;align-self:start"><h3>🛒 Your Order</h3>';
 if(!$C)echo '<p>Cart is empty.</p>';else{$t=0;echo '<table>';foreach($C as $pid=>$qty){$r=$pdo->query("SELECT title,price FROM products WHERE id=".(int)$pid)->fetch();$t+=$r['price']*$qty;echo '<tr><td>'.h($r['title']).'<br><small>'.$qty.' × '.money($r['price']).'</small></td><td>'.money($r['price']*$qty).'</td></tr>';}
  echo '</table><h3>Total: '.money($t).'</h3><form method="post"><input type="hidden" name="a" value="pay"><label>Payment Method</label><select name="pay"><option>Cash</option><option>GCash</option><option>Maya</option><option>Credit Card</option><option>Debit Card</option></select><button class="btn" style="width:100%">Confirm Payment</button></form><form method="post"><input type="hidden" name="a" value="clear"><button class="btn alt" style="width:100%;margin-top:6px">Clear Order</button></form>';}
 echo '</div></div>';}
bottom();
