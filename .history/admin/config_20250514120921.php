<?php
// admin/config.php

// önce ortak config’i al (DB + session_start)
require_once __DIR__ . '/../config/config.php';

// public erişime açık admin sayfaları
$publicPages = ['login.php','signup.php'];
// şu anki script adı
$current     = basename($_SERVER['PHP_SELF']);

// eğer admin oturumu yoksa ve current page publicPages içinde değilse, login’e gönder
if (empty($_SESSION['admin_logged_in'])
    && ! in_array($current, $publicPages)
) {
    header('Location: login.php');
    exit;
}
