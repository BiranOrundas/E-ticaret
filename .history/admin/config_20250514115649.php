<?php
// config/config.php

// 1) Oturumu başlat
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

// 2) Public sayfalar
$publicPages   = ['login.php', 'signup.php'];
$currentScript = basename($_SERVER['SCRIPT_NAME']);

