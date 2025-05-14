<?php
// admin/config.php

// önce ortak ayarları al
require_once __DIR__ . '/../config/config.php';

// hangi admin sayfaları giriş olmadan da gezilebilir?
$public = ['login.php','signup.php'];
$current = basename($_SERVER['SCRIPT_NAME']);

// eğer oturum yoksa ve açık sayfa public değilse
if (empty($_SESSION['admin_logged_in'])
    && ! in_array($current, $public)
) {
    header('Location: login.php');
    exit;
}
