<?php
require_once 'config/database.php';
$conn = getDBConnection();

try {
    // Temporarily disable foreign key checks
    $conn->exec("SET FOREIGN_KEY_CHECKS=0");

    // Drop existing tables if they exist
    $conn->exec("DROP TABLE IF EXISTS SCHEDULES");
    $conn->exec("DROP TABLE IF EXISTS EMPLOYEES");
    
    // Create EMPLOYEES table
    $conn->exec("
        CREATE TABLE IF NOT EXISTS EMPLOYEES (
            id INT PRIMARY KEY AUTO_INCREMENT,
            first_name VARCHAR(50) NOT NULL,
            last_name VARCHAR(50) NOT NULL,
            role VARCHAR(20) NOT NULL,
            phone VARCHAR(20) NOT NULL,
            email VARCHAR(100) NOT NULL
        )
    ");
    
    // Create SCHEDULES table
    $conn->exec("
        CREATE TABLE IF NOT EXISTS SCHEDULES (
            id INT PRIMARY KEY AUTO_INCREMENT,
            employee_id INT NOT NULL,
            shift_date DATE NOT NULL,
            start_time TIME NOT NULL,
            end_time TIME NOT NULL,
            FOREIGN KEY (employee_id) REFERENCES EMPLOYEES(id)
        )
    ");

    // Re-enable foreign key checks
    $conn->exec("SET FOREIGN_KEY_CHECKS=1");
    
    echo "Employee and Schedule tables created successfully!";
} catch(PDOException $e) {
    // Make sure to re-enable foreign key checks even if there's an error
    $conn->exec("SET FOREIGN_KEY_CHECKS=1");
    echo "Error creating tables: " . $e->getMessage();
}
?>
