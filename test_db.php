<?php
require_once 'config/database.php';

try {
    $conn = getDBConnection();
    echo "Database connection successful!";
    
    // Test query to check if tables exist
    $tables = $conn->query("SHOW TABLES")->fetchAll(PDO::FETCH_COLUMN);
    echo "\n\nAvailable tables:\n";
    foreach ($tables as $table) {
        echo "- $table\n";
    }
} catch(PDOException $e) {
    echo "Connection failed: " . $e->getMessage();
}
?>
