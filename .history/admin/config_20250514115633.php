<?php
// admin/config.php

// 1) Ortak ayarları al
require_once __DIR__ . '/../config/config.php';

// 2) Public admin sayfaları
$public = ['login.php','signup.php'];
if (empty($_SESSION['admin_logged_in'])
 && ! in_array(basename($_SERVER['SCRIPT_NAME']), $public)
) {
    header('Location: login.php');
    exit;
}