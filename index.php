<?php require 'inc.php';
if(!empty($_SESSION['u'])){header('Location: '.($_SESSION['u']['role']=='admin'?'admin.php':'user.php'));exit;}
$err='';$mode=$_POST['mode']??'';
if($_SERVER['REQUEST_METHOD']=='POST'){$un=trim($_POST['username']??'');$pw=$_POST['password']??'';
 if($mode=='register'){
  if(strlen($un)<3||strlen($pw)<3)$err='Username and password need at least 3 characters.';
  else{$s=$pdo->prepare("SELECT 1 FROM users WHERE username=?");$s->execute([$un]);
   if($s->fetch())$err='Username already taken.';
   else{$pdo->prepare("INSERT INTO users(username,password,role,name) VALUES(?,?,'user',?)")->execute([$un,password_hash($pw,PASSWORD_DEFAULT),trim($_POST['name'])?:$un]);$err='ok:Account created! Please log in as User.';$mode='user';}}
 }else{$s=$pdo->prepare("SELECT * FROM users WHERE username=? AND role=?");$s->execute([$un,$mode]);$u=$s->fetch();
  if($u&&password_verify($pw,$u['password'])){$_SESSION['u']=['id'=>$u['id'],'role'=>$u['role']];header('Location: '.($mode=='admin'?'admin.php':'user.php'));exit;}
  $err='Invalid username or password.';}}
?><!doctype html><html><head><meta charset="utf-8"><meta name="viewport" content="width=device-width,initial-scale=1"><title>VVTRJ Bookstore</title><link rel="stylesheet" href="style.css"></head><body>
<div class="login"><div class="card lb"><svg width="90" height="90" viewBox="0 0 40 40"><circle cx="20" cy="20" r="20" fill="#14532d"/><path d="M10 12h8c1.5 0 2 .7 2 2v14c0-1-1-2-2-2h-8zM30 12h-8c-1.5 0-2 .7-2 2v14c0-1 1-2 2-2h8z" fill="#fff"/></svg>
<h2>VVTRJ Bookstore</h2><p><b>Log in as:</b></p>
<button class="btn" onclick="sh('user')">User</button><button class="btn" onclick="sh('admin')">Admin</button><button class="btn alt" onclick="sh('register')">Create Account (new user)</button>
<?php if($err)echo '<p class="'.(strpos($err,'ok:')===0?'ok':'err').'">'.h(preg_replace('/^ok:/','',$err)).'</p>'?>
<form method="post" id="f" style="display:<?=$mode?'block':'none'?>;text-align:left"><input type="hidden" name="mode" id="mode" value="<?=h($mode)?>"><h3 id="ft" style="color:#14532d"></h3>
<div id="nm" style="display:none"><label>Full name</label><input name="name"></div><label>Username</label><input name="username" required><label>Password</label><input type="password" name="password" required><button class="btn">Continue</button></form></div></div>
<script>function sh(m){f.style.display='block';mode.value=m;nm.style.display=m=='register'?'block':'none';ft.textContent=m=='register'?'Create Account':'Log in as '+m.charAt(0).toUpperCase()+m.slice(1)}<?php if($mode)echo "sh('".h($mode)."');"?></script></body></html>
