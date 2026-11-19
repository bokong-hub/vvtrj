<?php require 'inc.php';need('admin');
$p=$_GET['p']??'sales';$L=['profile'=>'Profile','products'=>'Products','inventory'=>'Inventory','sales'=>'Sales','transactions'=>'Transactions','logout'=>'Log out'];
if($_SERVER['REQUEST_METHOD']=='POST'&&$p!='profile'){$a=$_POST['a']??'';
 if($a=='genre'&&trim($_POST['name']))$pdo->prepare("INSERT IGNORE INTO genres(name) VALUES(?)")->execute([trim($_POST['name'])]);
 if($a=='save'){$id=(int)$_POST['id'];$img=up('image');$d=[$_POST['title'],$_POST['author'],$_POST['genre_id']?:null,$_POST['price'],(int)$_POST['stock']];
  if($id){$pdo->prepare("UPDATE products SET title=?,author=?,genre_id=?,price=?,stock=? WHERE id=?")->execute([...$d,$id]);if($img)$pdo->prepare("UPDATE products SET image=? WHERE id=?")->execute([$img,$id]);}
  else $pdo->prepare("INSERT INTO products(title,author,genre_id,price,stock,image) VALUES(?,?,?,?,?,?)")->execute([...$d,$img]);}
 if($a=='del')$pdo->prepare("DELETE FROM products WHERE id=?")->execute([(int)$_POST['id']]);
 if($a=='stock')$pdo->prepare("UPDATE products SET stock=? WHERE id=?")->execute([max(0,(int)$_POST['stock']),(int)$_POST['id']]);
 header("Location: ?p=$p");exit;}
top('Admin',$L,$p);
if($p=='profile')profile_page('admin');
elseif($p=='transactions')tx_list(true);
elseif($p=='view')receipt((int)$_GET['id'],true);
elseif($p=='products'){$e=['id'=>0,'title'=>'','author'=>'','genre_id'=>'','price'=>'','stock'=>0];
 if(!empty($_GET['edit'])){$s=$pdo->prepare("SELECT * FROM products WHERE id=?");$s->execute([$_GET['edit']]);$e=$s->fetch()?:$e;}
 $gs=$pdo->query("SELECT * FROM genres ORDER BY name")->fetchAll();
 echo '<h2>Products</h2><div class="two"><div class="card"><table><tr><th></th><th>Title</th><th>Genre</th><th>Price</th><th>Stock</th><th></th></tr>';
 foreach($pdo->query("SELECT p.*,g.name g FROM products p LEFT JOIN genres g ON g.id=p.genre_id ORDER BY p.id DESC") as $r)
  echo '<tr><td>'.($r['image']?'<img src="uploads/'.h($r['image']).'" width="40" height="40" style="object-fit:cover">':'📕').'</td><td>'.h($r['title']).'<br><small>'.h($r['author']).'</small></td><td>'.h($r['g']).'</td><td>'.money($r['price']).'</td><td>'.$r['stock'].'</td><td><a class="btn sm" href="?p=products&edit='.$r['id'].'">Edit</a> <form method="post" style="display:inline" onsubmit="return confirm(\'Delete this product?\')"><input type="hidden" name="a" value="del"><input type="hidden" name="id" value="'.$r['id'].'"><button class="btn sm red">Delete</button></form></td></tr>';
 echo '</table></div><div><div class="card"><h3>'.($e['id']?'Edit':'Add New').' Product</h3><form method="post" enctype="multipart/form-data"><input type="hidden" name="a" value="save"><input type="hidden" name="id" value="'.$e['id'].'"><label>Title</label><input name="title" value="'.h($e['title']).'" required><label>Author</label><input name="author" value="'.h($e['author']).'"><label>Genre</label><select name="genre_id">';
 foreach($gs as $g)echo '<option value="'.$g['id'].'"'.($g['id']==$e['genre_id']?' selected':'').'>'.h($g['name']).'</option>';
 echo '</select><label>Price (₱)</label><input type="number" step="0.01" min="0" name="price" value="'.h($e['price']).'" required><label>Stock</label><input type="number" min="0" name="stock" value="'.h($e['stock']).'" required><label>Picture</label><input type="file" name="image" accept="image/*"><button class="btn">Save</button> '.($e['id']?'<a class="btn alt" href="?p=products">Cancel</a>':'').'</form></div>
 <div class="card"><h3>Add Genre</h3><form method="post"><input type="hidden" name="a" value="genre"><input name="name" placeholder="e.g. Mystery" required><button class="btn">Add Genre</button></form><small>'.implode(', ',array_map(fn($g)=>h($g['name']),$gs)).'</small></div></div></div>';}
elseif($p=='inventory'){$low=$pdo->query("SELECT * FROM products WHERE stock<=10 ORDER BY stock")->fetchAll();
 echo '<h2>Inventory</h2><div class="card"><h3 class="low">⚠ Low Stock Items (10 or fewer)</h3>';
 if(!$low)echo '<p>All items are well stocked.</p>';
 foreach($low as $r)echo '<p><span class="low">'.h($r['title']).'</span> — only '.$r['stock'].' left</p>';
 echo '</div><div class="card"><table><tr><th>Product</th><th>Current Stock</th><th>Set Stock</th></tr>';
 foreach($pdo->query("SELECT * FROM products ORDER BY title") as $r)echo '<tr><td>'.h($r['title']).'</td><td class="'.($r['stock']<=10?'low':'').'">'.$r['stock'].'</td><td><form method="post" style="display:flex;gap:6px"><input type="hidden" name="a" value="stock"><input type="hidden" name="id" value="'.$r['id'].'"><input type="number" min="0" name="stock" value="'.$r['stock'].'" style="width:100px;margin:0"><button class="btn sm">Update</button></form></td></tr>';
 echo '</table></div>';}
else{ // SALES + DSS + PREDICTIVE
 $tot=$pdo->query("SELECT COUNT(*) c,COALESCE(SUM(total),0) t FROM orders")->fetch();
 $today=$pdo->query("SELECT COALESCE(SUM(total),0) FROM orders WHERE DATE(created_at)=CURDATE()")->fetchColumn();
 $lowc=$pdo->query("SELECT COUNT(*) FROM products WHERE stock<=10")->fetchColumn();
 $rows=$pdo->query("SELECT DATE(created_at) d,SUM(total) t FROM orders WHERE created_at>=CURDATE()-INTERVAL 13 DAY GROUP BY d")->fetchAll(PDO::FETCH_KEY_PAIR);
 $lab=[];$act=[];for($i=13;$i>=0;$i--){$d=date('Y-m-d',strtotime("-$i day"));$lab[]=date('M d',strtotime($d));$act[]=(float)($rows[$d]??0);}
 $n=14;$sx=array_sum(range(0,13));$sy=array_sum($act);$sxy=0;$sxx=0;foreach($act as $x=>$y){$sxy+=$x*$y;$sxx+=$x*$x;}
 $m=($n*$sxy-$sx*$sy)/($n*$sxx-$sx*$sx);$b=($sy-$m*$sx)/$n;$fl=[];$fc=[];$fsum=0;
 for($i=14;$i<=20;$i++){$v=max(0,round($b+$m*$i,2));$fc[]=$v;$fsum+=$v;$lab[]=date('M d',strtotime('+'.($i-13).' day'));}
 $A=array_merge($act,array_fill(0,7,null));$F=array_merge(array_fill(0,13,null),[end($act)],$fc);
 $top=$pdo->query("SELECT oi.title,SUM(oi.qty) q FROM order_items oi GROUP BY oi.title ORDER BY q DESC LIMIT 5")->fetchAll();
 $gen=$pdo->query("SELECT COALESCE(g.name,'Other') g,SUM(oi.qty*oi.price) t FROM order_items oi LEFT JOIN products p ON p.id=oi.product_id LEFT JOIN genres g ON g.id=p.genre_id GROUP BY g.name ORDER BY t DESC")->fetchAll();
 $vel=$pdo->query("SELECT oi.product_id,SUM(oi.qty)/14 v FROM order_items oi JOIN orders o ON o.id=oi.order_id WHERE o.created_at>=CURDATE()-INTERVAL 13 DAY GROUP BY oi.product_id")->fetchAll(PDO::FETCH_KEY_PAIR);
 $dss=[];foreach($pdo->query("SELECT * FROM products") as $r){$v=$vel[$r['id']]??0;$days=$v>0?$r['stock']/$v:999;if($days<10)$dss[]=[$r['title'],$r['stock'],$v,$days,max(1,ceil($v*14-$r['stock']))];}
 usort($dss,fn($a,$b)=>$a[3]<=>$b[3]);
 $trend=$m>0.5?'📈 Sales are trending <b>UP</b>':($m<-0.5?'📉 Sales are trending <b>DOWN</b>':'➖ Sales are <b>stable</b>');
 echo '<h2>Sales Dashboard</h2><div class="grid"><div class="stat">Total Orders<b>'.$tot['c'].'</b></div><div class="stat">Total Sales<b>'.money($tot['t']).'</b></div><div class="stat">Today\'s Sales<b>'.money($today).'</b></div><div class="stat">Predicted Next 7 Days<b>'.money($fsum).'</b></div><div class="stat">Low-Stock Items<b>'.$lowc.'</b></div></div>
 <div class="card"><h3>Sales Forecast (14-day history + 7-day linear regression forecast)</h3><canvas id="c1" height="90"></canvas></div>
 <div class="two" style="grid-template-columns:1fr 1fr"><div class="card"><h3>Top Selling Books</h3><canvas id="c2"></canvas></div><div class="card"><h3>Revenue by Genre</h3><canvas id="c3"></canvas></div></div>
 <div class="card"><h3>🧠 Decision Support System</h3><p>'.$trend.' (forecast slope '.round($m,2).' ₱/day). Expected revenue next 7 days: <b>'.money($fsum).'</b>.</p>'.($top?'<p>🏆 Best seller: <b>'.h($top[0]['title']).'</b> ('.$top[0]['q'].' sold).'.($gen?' Strongest genre: <b>'.h($gen[0]['g']).'</b>.':'').'</p>':'').'<h4>Restock Recommendations (stock lasts under 10 days)</h4>';
 if(!$dss)echo '<p>No urgent restocking needed.</p>';else{echo '<table><tr><th>Product</th><th>Stock</th><th>Avg sold/day</th><th>Days left</th><th>Suggested order (14-day cover)</th></tr>';foreach($dss as $r)echo '<tr><td>'.h($r[0]).'</td><td>'.$r[1].'</td><td>'.round($r[2],2).'</td><td class="low">'.round($r[3],1).'</td><td><b>'.$r[4].'</b></td></tr>';echo '</table>';}
 echo '</div><script>const lb='.json_encode($lab).';
 new Chart(c1,{type:"line",data:{labels:lb,datasets:[{label:"Actual Sales (₱)",data:'.json_encode($A).',borderColor:"#14532d",backgroundColor:"#14532d33",fill:true,tension:.3},{label:"Forecast (₱)",data:'.json_encode($F).',borderColor:"#e08a00",borderDash:[6,4],tension:.3}]}});
 new Chart(c2,{type:"bar",data:{labels:'.json_encode(array_column($top,'title')).',datasets:[{label:"Units sold",data:'.json_encode(array_map('intval',array_column($top,'q'))).',backgroundColor:"#1f7a46"}]},options:{indexAxis:"y"}});
 new Chart(c3,{type:"doughnut",data:{labels:'.json_encode(array_column($gen,'g')).',datasets:[{data:'.json_encode(array_map('floatval',array_column($gen,'t'))).',backgroundColor:["#14532d","#2e8b57","#6fbf8b","#a8d5b5","#e08a00","#8d6e63","#607d8b"]}]}});</script>';}
bottom();
