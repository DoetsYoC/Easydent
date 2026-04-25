<?php
require_once __DIR__ . '/../config/database.php';
require_once __DIR__ . '/../config/helpers.php';
require_once __DIR__ . '/../config/auth.php';

startSecureSession();

header('Content-Type: application/json; charset=utf-8');
header('X-Content-Type-Options: nosniff');

$method = $_SERVER['REQUEST_METHOD'];
$path   = trim(parse_url($_SERVER['REQUEST_URI'], PHP_URL_PATH), '/');

// Verwijder prefix: easydent/api
$path = preg_replace('#^easydent/api/?#', '', $path);
$segments = explode('/', $path);
$resource = $segments[0] ?? '';
$id       = isset($segments[1]) && is_numeric($segments[1]) ? (int)$segments[1] : null;

// Publieke routes (geen auth)
if ($resource === 'health') {
    jsonResponse(['status' => 'ok', 'time' => date('c')]);
}

// Vanaf hier: auth vereist
requireApiAuth();
$user = currentUser();

// Route dispatcher
try {
    match ($resource) {
        'practices'   => require __DIR__ . '/practices.php',
        'users'       => require __DIR__ . '/users.php',
        'patients'    => require __DIR__ . '/patients.php',
        'appointments'=> require __DIR__ . '/appointments.php',
        'sessions'    => require __DIR__ . '/sessions.php',
        default       => jsonError('Onbekend endpoint', 404),
    };
} catch (Throwable $e) {
    jsonError('Server fout: ' . $e->getMessage(), 500);
}
