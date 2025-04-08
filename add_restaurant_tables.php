<?php
require_once 'config/database.php';
$conn = getDBConnection();

try {
    // Insert tables
    $sql = "INSERT INTO TABLES (table_number, num_seats, seat_is_occupied) VALUES
        (1, 2, FALSE),  -- Table for 2 people
        (2, 2, FALSE),
        (3, 4, FALSE),  -- Table for 4 people
        (4, 4, FALSE),
        (5, 4, FALSE),
        (6, 6, FALSE),  -- Table for 6 people
        (7, 6, FALSE),
        (8, 8, FALSE),  -- Table for 8 people
        (9, 8, FALSE),
        (10, 10, FALSE) -- Table for 10 people";
    
    $conn->exec($sql);
    echo "Tables added successfully!";
} catch(PDOException $e) {
    echo "Error adding tables: " . $e->getMessage();
}
?>
