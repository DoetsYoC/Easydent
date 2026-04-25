<?php
define('DB_HOST', 'localhost');
define('DB_NAME', 'europeaneu10990_Easydent');
define('DB_USER', 'easydent_user');
define('DB_PASS', 'Easydent2024!');
define('DB_CHARSET', 'utf8mb4');

function getDB() {
    static $pdo = null;
    if ($pdo === null) {
        try {
            $dsn = "mysql:host=" . DB_HOST . ";dbname=" . DB_NAME . ";charset=" . DB_CHARSET;
            $pdo = new PDO($dsn, DB_USER, DB_PASS, [
                PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
                PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
            ]);
        } catch (PDOException $e) {
            die(json_encode(['error' => 'Database connection failed']));
        }
    }
    return $pdo;
}