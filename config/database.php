<?php
define('DB_HOST', 'localhost');
define('DB_USER', 'root');     // Change these credentials
define('DB_PASS', '');         // in production
define('DB_NAME', 'restaurant_db');

// Create connection
function getDBConnection() {
    try {
        $conn = new PDO(
            "mysql:host=" . DB_HOST . ";dbname=" . DB_NAME,
            DB_USER,
            DB_PASS
        );
        $conn->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
        return $conn;
    } catch(PDOException $e) {
        die("Connection failed: " . $e->getMessage());
    }
}
?>
