<?php
// Oturum kontrolü ve config
require_once __DIR__ . '/../../config/config.php';
if (!isset($_SESSION['admin_logged_in'])) {
    header('Location: login.php');
    exit;
}
?><!DOCTYPE html>
<html lang="tr">
<head>
  <meta charset="utf-8">
  <title>Admin • Panel</title>
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <!-- CSS -->
  <link href="./css/bootstrap.min.css" rel="stylesheet">
  <link href="./css/style.css" rel="stylesheet">
  <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/5.10.0/css/all.min.css" rel="stylesheet">
  <link href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.4.1/font/bootstrap-icons.css" rel="stylesheet">
  <!-- Diğer library CSS’leri -->
  <link href="./lib/owlcarousel/assets/owl.carousel.min.css" rel="stylesheet">
  <link href="./lib/tempusdominus/css/tempusdominus-bootstrap-4.min.css" rel="stylesheet">
</head>
<body>
<div class="container-xxl position-relative bg-white d-flex p-0">
