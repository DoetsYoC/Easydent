<?php
require_once __DIR__ . '/config/database.php';
require_once __DIR__ . '/config/helpers.php';

echo "LANG_DIR: " . LANG_DIR . "<br>";

$data = @include LANG_DIR . 'de.php';
echo "include via LANG_DIR: " . (is_array($data) ? 'OK (' . count($data) . ' sleutels)' : 'MISLUKT') . "<br>";

// Simuleer sessie voor vertaling
session_start();
$_SESSION['lang'] = 'de';

echo "login_btn: " . __('login_btn') . "<br>";
echo "app_name: " . __('app_name') . "<br>";
