<?php
// config/config.php

// 1) Oturumu başlat (bir kez)
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

// 2) Veritabanı ayarları
define('DB_HOST','localhost');
define('DB_NAME','e_ticaret');
define('DB_USER','root');
define('DB_PASS','');

try {
    $pdo = new PDO(
      "mysql:host=".DB_HOST.";dbname=".DB_NAME.";charset=utf8mb4",
      DB_USER, DB_PASS,
      [PDO::ATTR_ERRMODE=>PDO::ERRMODE_EXCEPTION]
    );
} catch (PDOException $e) {
    die("Veritabanı bağlantı hatası: ".$e->getMessage());
}
