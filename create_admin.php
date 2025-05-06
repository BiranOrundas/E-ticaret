<?php
require_once __DIR__ . '/config/config.php';

$email = 'biranorundas17@gmail.com';
$pass  = 'biranadana01';  // burada istediğiniz düz metni belirleyin
$hash  = password_hash($pass, PASSWORD_DEFAULT);

$stmt = $pdo->prepare("
  INSERT INTO users (name, email, password_hash, role)
  VALUES (?, ?, ?, 'admin')
");
$stmt->execute(['Root User', $email, $hash]);

echo "Admin kullanıcı eklendi: $email / $pass";
