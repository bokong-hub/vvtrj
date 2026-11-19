<?php
session_start(); date_default_timezone_set('Asia/Manila'); require __DIR__.'/config.php';
function h($s){return htmlspecialchars((string)$s,ENT_QUOTES);}
function money($n){return '₱'.number_format((float)$n,2);}
function need($r){if(empty($_SESSION['u'])||$_SESSION['u']['role']!==$r){header('Location: index.php');exit;}}
function me(){global $pdo;$s=$pdo->prepare("SELECT * FROM users WHERE id=?");$s->execute([$_SESSION['u']['id']]);return $s->fetch();}
function up($f){if(empty($_FILES[$f]['name'])||$_FILES[$f]['error'])return null;$e=strtolower(pathinfo($_FILES[$f]['name'],PATHINFO_EXTENSION));if(!in_array($e,['jpg','jpeg','png','gif','webp']))return null;@mkdir(__DIR__.'/uploads');$n=uniqid().'.'.$e;move_uploaded_file($_FILES[$f]['tmp_name'],__DIR__."/uploads/$n");return $n;}
function avatar($u){return $u['avatar']?'<img class="av" src="uploads/'.h($u['avatar']).'">':'<span class="av ph">'.h(strtoupper(substr($u['name'],0,1))).'</span>';}
function top($title,$links,$active){$u=me();$logo='<svg width="38" height="38" viewBox="0 0 40 40"><circle cx="20" cy="20" r="20" fill="#14532d"/><path d="M10 12h8c1.5 0 2 .7 2 2v14c0-1-1-2-2-2h-8zM30 12h-8c-1.5 0-2 .7-2 2v14c0-1 1-2 2-2h8z" fill="#fff"/></svg>';
echo '<!doctype html><html><head><meta charset="utf-8"><meta name="viewport" content="width=device-width,initial-scale=1"><title>'.h($title).' - VVTRJ Bookstore</title><link rel="stylesheet" href="style.css"><script src="https://cdn.jsdelivr.net/npm/chart.js@4.4.1/dist/chart.umd.min.js"></script><script src="https://cdn.jsdelivr.net/npm/html2canvas@1.4.1/dist/html2canvas.min.js"></script></head><body>
<header><button id="m" onclick="document.body.classList.toggle(\'nav\')">&#9776;</button>'.$logo.'<b>VVTRJ Bookstore</b><span class="sp"></span><span class="who">'.h($u['name']).'</span><a href="?p=profile">'.avatar($u).'</a></header><nav>';
foreach($links as $k=>$v)echo '<a class="'.($k==$active?'on':'').'" href="'.($k=='logout'?'logout.php':'?p='.$k).'">'.$v.'</a>';
echo '</nav><main>';}
function bottom(){echo '</main><div id="clock"></div><script>function t(){const d=new Date();document.getElementById("clock").innerHTML="<b>"+d.toLocaleTimeString()+"</b><br>"+d.toLocaleDateString("en-US",{weekday:"long",year:"numeric",month:"long",day:"numeric"})}t();setInterval(t,1000)</script></body></html>';}
function profile_page($role){global $pdo;$u=me();$msg='';
 if($_SERVER['REQUEST_METHOD']=='POST'){$a=up('avatar')?:$u['avatar'];$n=trim($_POST['name'])?:$u['name'];$pdo->prepare("UPDATE users SET name=?,avatar=? WHERE id=?")->execute([$n,$a,$u['id']]);header('Location: ?p=profile&ok=1');exit;}
 $u=me();echo '<h2>Hi, '.h($u['name']).'!</h2>'.(isset($_GET['ok'])?'<p class="ok">Profile updated.</p>':'').'<div class="card prof">'.avatar($u).'<form method="post" enctype="multipart/form-data"><label>Name</label><input name="name" value="'.h($u['name']).'" required><label>Profile picture</label><input type="file" name="avatar" accept="image/*"><button class="btn">Save Profile</button></form></div>';}
function receipt($id,$admin){global $pdo;
 $s=$pdo->prepare("SELECT o.*,u.name cname FROM orders o LEFT JOIN users u ON u.id=o.user_id WHERE o.id=?");$s->execute([$id]);$o=$s->fetch();
 if(!$o||(!$admin&&$o['user_id']!=$_SESSION['u']['id'])){echo '<p>Transaction not found.</p>';return;}
 $it=$pdo->prepare("SELECT * FROM order_items WHERE order_id=?");$it->execute([$id]);
 echo '<div class="rc" id="rc"><h3>VVTRJ Bookstore</h3><p class="c">Official Receipt</p><p>Receipt #: '.str_pad($o['id'],6,'0',STR_PAD_LEFT).'<br>Date: '.date('M d, Y',strtotime($o['created_at'])).'<br>Time: '.date('h:i A',strtotime($o['created_at'])).'<br>Customer ID: '.$o['user_id'].' ('.h($o['cname']).')<br>Payment: '.h($o['payment']).'</p><table><tr><th>Item</th><th>Qty</th><th>Price</th><th>Amt</th></tr>';
 foreach($it as $r)echo '<tr><td>'.h($r['title']).'</td><td>'.$r['qty'].'</td><td>'.money($r['price']).'</td><td>'.money($r['price']*$r['qty']).'</td></tr>';
 echo '</table><h3 class="r">TOTAL: '.money($o['total']).'</h3><p class="c">Thank you for shopping!</p></div>
 <p class="noprint"><button class="btn" onclick="window.print()">Print Receipt</button> <button class="btn" onclick="html2canvas(document.getElementById(\'rc\'),{scale:2}).then(c=>{const a=document.createElement(\'a\');a.download=\'receipt-'.$o['id'].'.png\';a.href=c.toDataURL();a.click()})">Save as Image</button> <a class="btn alt" href="?p=transactions">Back</a></p>';}
function tx_list($admin){global $pdo;
 $q=$admin?$pdo->query("SELECT id,created_at FROM orders ORDER BY id DESC"):$pdo->prepare("SELECT id,created_at FROM orders WHERE user_id=? ORDER BY id DESC");
 if(!$admin)$q->execute([$_SESSION['u']['id']]);
 echo '<h2>'.($admin?'Transactions':'Transaction History').'</h2><div class="card"><table><tr><th>Date</th><th>Time</th><th></th></tr>';
 foreach($q as $r)echo '<tr><td>'.date('M d, Y',strtotime($r['created_at'])).'</td><td>'.date('h:i A',strtotime($r['created_at'])).'</td><td><a class="btn sm" href="?p=view&id='.$r['id'].'">View</a></td></tr>';
 echo '</table></div>';}
