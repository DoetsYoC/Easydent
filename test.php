<?php
require_once 'config/database.php';

try {
    $db = getDB();
    echo "<h1 style='color:green'>✅ Database verbinding werkt!</h1>";
    echo "<p>Verbonden met: " . DB_NAME . "</p>";
    echo "<p>Host: " . DB_HOST . "</p>";
} catch (Exception $e) {
    echo "<h1 style='color:red'>❌ Verbinding mislukt</h1>";
    echo "<p>" . $e->getMessage() . "</p>";
}