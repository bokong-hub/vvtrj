<?php
// Database settings (XAMPP defaults). The DB and tables are created automatically on first run.
$H='localhost';$U='root';$P='';$DB='vvtrj_bookstore';
$pdo=new PDO("mysql:host=$H;charset=utf8mb4",$U,$P,[PDO::ATTR_ERRMODE=>PDO::ERRMODE_EXCEPTION,PDO::ATTR_DEFAULT_FETCH_MODE=>PDO::FETCH_ASSOC]);
$pdo->exec("CREATE DATABASE IF NOT EXISTS `$DB` CHARACTER SET utf8mb4");
$pdo->exec("USE `$DB`");
// Self-repair: error 1932 = orphaned/corrupt table files left by an interrupted run. Rebuild the database.
try{$pdo->query("SELECT 1 FROM products LIMIT 1");}catch(PDOException $e){
  if(strpos($e->getMessage(),'1932')!==false){$pdo->exec("DROP DATABASE `$DB`");$pdo->exec("CREATE DATABASE `$DB` CHARACTER SET utf8mb4");$pdo->exec("USE `$DB`");}
}
$pdo->exec("CREATE TABLE IF NOT EXISTS users(id INT AUTO_INCREMENT PRIMARY KEY,username VARCHAR(50) UNIQUE,password VARCHAR(255),role VARCHAR(10),name VARCHAR(80),avatar VARCHAR(100) NULL)");
$pdo->exec("CREATE TABLE IF NOT EXISTS genres(id INT AUTO_INCREMENT PRIMARY KEY,name VARCHAR(60) UNIQUE)");
$pdo->exec("CREATE TABLE IF NOT EXISTS products(id INT AUTO_INCREMENT PRIMARY KEY,title VARCHAR(150),author VARCHAR(100),genre_id INT NULL,price DECIMAL(10,2),stock INT DEFAULT 0,image VARCHAR(100) NULL)");
$pdo->exec("CREATE TABLE IF NOT EXISTS orders(id INT AUTO_INCREMENT PRIMARY KEY,user_id INT,total DECIMAL(10,2),payment VARCHAR(30),created_at DATETIME)");
$pdo->exec("CREATE TABLE IF NOT EXISTS order_items(id INT AUTO_INCREMENT PRIMARY KEY,order_id INT,product_id INT,title VARCHAR(150),price DECIMAL(10,2),qty INT)");
if(!$pdo->query("SELECT COUNT(*) FROM users")->fetchColumn()){
  $i=$pdo->prepare("INSERT INTO users(username,password,role,name) VALUES(?,?,?,?)");
  $i->execute(['admin',password_hash('admin',PASSWORD_DEFAULT),'admin','Admin']);
  $i->execute(['user',password_hash('user',PASSWORD_DEFAULT),'user','User']);
  foreach(['Fiction','Romance','Horror','Adventure','Slice of life'] as $g)$pdo->prepare("INSERT INTO genres(name) VALUES(?)")->execute([$g]);
  $b=[['The Silent Library','A. Reyes',1,350],['Midnight Letters','M. Santos',2,299],['Hollow House','R. Cruz',3,320],['Island of Maps','J. Dela Cruz',4,410],['Morning Coffee Diaries','L. Tan',5,275],['City of Glass Hearts','K. Lim',2,310],['The Last Lantern','B. Garcia',1,380],['Whispers in the Attic','S. Ramos',3,299],['Across the Tide','P. Mendoza',4,450],['Small Joys','E. Villanueva',5,250]];
  foreach($b as $x)$pdo->prepare("INSERT INTO products(title,author,genre_id,price,stock) VALUES(?,?,?,?,60)")->execute($x);
  // sample 14-day sales history so the DSS/forecast has data to work with
  for($d=13;$d>=0;$d--){for($k=0,$n=rand(2,4)+(13-$d>>2);$k<$n;$k++){
    $dt=date('Y-m-d',strtotime("-$d day")).' '.sprintf('%02d:%02d:00',rand(9,20),rand(0,59));
    $pdo->prepare("INSERT INTO orders(user_id,total,payment,created_at) VALUES(2,0,?,?)")->execute([['Cash','GCash','Credit Card'][rand(0,2)],$dt]);
    $oid=$pdo->lastInsertId();$tot=0;
    foreach((array)array_rand(range(1,10),rand(1,3)) as $pi){$p=$pdo->query("SELECT * FROM products WHERE id=".($pi+1))->fetch();$q=rand(1,3);$tot+=$q*$p['price'];
      $pdo->prepare("INSERT INTO order_items(order_id,product_id,title,price,qty) VALUES(?,?,?,?,?)")->execute([$oid,$p['id'],$p['title'],$p['price'],$q]);
      $pdo->prepare("UPDATE products SET stock=GREATEST(stock-?,0) WHERE id=?")->execute([$q,$p['id']]);}
    $pdo->prepare("UPDATE orders SET total=? WHERE id=?")->execute([$tot,$oid]);}}
}
