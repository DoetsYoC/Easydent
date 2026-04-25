<?php
// Taalwisselaar — stelt sessietaal in en stuurt terug
require_once __DIR__ . '/config/database.php';
require_once __DIR__ . '/config/helpers.php';
require_once __DIR__ . '/config/auth.php';

startSecureSession();

$allowed = ['de', 'nl', 'en'];
$lang    = $_GET['lang'] ?? 'de';
if (!in_array($lang, $allowed, true)) $lang = 'de';

$_SESSION['lang'] = $lang;

$return = $_GET['return'] ?? '/easydent/index.php';
// Voorkom open redirects — alleen eigen paden toegestaan
if (!str_starts_with($return, '/easydent/')) {
    $return = '/easydent/index.php';
}

header('Location: ' . $return);
exit;
