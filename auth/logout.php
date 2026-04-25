<?php
require_once __DIR__ . '/../config/database.php';
require_once __DIR__ . '/../config/helpers.php';
require_once __DIR__ . '/../config/auth.php';

startSecureSession();

if (isLoggedIn()) {
    logoutUser();
}

header('Location: /easydent/auth/login.php');
exit;
