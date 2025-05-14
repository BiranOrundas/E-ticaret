<?php
// admin/index.php

require_once __DIR__ . '/../config/config.php';

// If not logged in, send to login
if (empty($_SESSION['admin_logged_in'])) {
    header('Location: login.php');
    exit;
}

// If you’re already logged in, send to dashboard
header('Location: dashboard.php');
exit;
