<?php
// config/config.php

// 1) Oturumu başlat
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

// 2) Public sayfalar
$publicPages   = ['login.php', 'signup.php'];
$currentScript = basename($_SERVER['SCRIPT_NAME']);

// 3) Giriş yapılmamışsa ve o anki sayfa public değilse yönlendir
if (empty($_SESSION['admin_logged_in']) && ! in_array($currentScript, $publicPages)) {
    header('Location: login.php');
    exit;
}

// 4) Veritabanı ayarları
define('DB_HOST', 'localhost');
define('DB_NAME', 'e_ticaret');
define('DB_USER', 'root');
define('DB_PASS', '');

// 5) PDO ile bağlantı
try {
    $pdo = new PDO(
        "mysql:host=" . DB_HOST . ";dbname=" . DB_NAME . ";charset=utf8mb4",
        DB_USER,
        DB_PASS,
        [PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION]
    );
} catch (PDOException $e) {
    die("Veritabanı bağlantı hatası: " . $e->getMessage());
}