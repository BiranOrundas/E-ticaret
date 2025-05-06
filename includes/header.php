<?php
// includes/header.php
require_once __DIR__ . '/../config/config.php';

// Eğer user oturumu yoksa login sayfasına gönder
// (login.php ve logout.php dışında)
$currentScript = basename($_SERVER['SCRIPT_NAME']);
if (!isset($_SESSION['admin_logged_in']) 
    && !in_array($currentScript, ['login.php', 'logout.php'])) {
    header('Location: login.php');
    exit;
}
?>
<!DOCTYPE html>
<html lang="tr">
<head>
  <meta charset="UTF-8">
  <title>Admin Panel</title>
  <link rel="stylesheet" href="../public/css/admin.css">
</head>
<body>
  <nav>
    <ul>
      <li><a href="dashboard.php">Dashboard</a></li>
      <li><a href="categories.php">Kategoriler</a></li>
      <li><a href="products.php">Ürünler</a></li>
      <li><a href="orders.php">Siparişler</a></li>
      <li><a href="logout.php">Çıkış Yap</a></li>
    </ul>
  </nav>
  <main>
