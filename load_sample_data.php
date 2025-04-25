<?php
require_once 'config/database.php';

// Function to ensure data directory exists
function ensureDataDirectoryExists() {
    $dataDir = __DIR__ . '/data';
    if (!file_exists($dataDir)) {
        if (mkdir($dataDir, 0755, true)) {
            echo "Created data directory successfully.\n";
        } else {
            die("Failed to create data directory.\n");
        }
    }
}

// Check and create data directory if needed
ensureDataDirectoryExists();

// Path to the data file
$dataFile = __DIR__ . '/data/tables.dat';

// Check if data file exists
if (!file_exists($dataFile)) {
    die("Error: Data file not found at: $dataFile\n");
}

try {
    // Read and parse the JSON data
    $jsonData = file_get_contents($dataFile);
    $data = json_decode($jsonData, true);

    if (json_last_error() !== JSON_ERROR_NONE) {
        throw new Exception("Error parsing JSON data: " . json_last_error_msg());
    }

    // Get database connection
    $conn = getDBConnection();

    // Begin transaction
    $conn->beginTransaction();

    // Clear existing data (in reverse order of dependencies)
    $conn->exec("DELETE FROM PAYMENT");
    $conn->exec("DELETE FROM ORDER_MENU_ITEM");
    $conn->exec("DELETE FROM FOOD_ORDER");
    $conn->exec("DELETE FROM MENU_ITEM");
    $conn->exec("DELETE FROM CUSTOMERS");
    echo "Cleared existing data successfully.\n";

    // Load customers
    if (isset($data['customers'])) {
        foreach ($data['customers'] as $customer) {
            $stmt = $conn->prepare("INSERT INTO CUSTOMERS (id, first_name, last_name, phone) 
                                  VALUES (?, ?, ?, ?)");
            $stmt->execute([
                $customer['id'],
                $customer['first_name'],
                $customer['last_name'],
                $customer['phone']
            ]);
        }
        echo "Loaded customers data successfully.\n";
    }

    // Load employees
    if (isset($data['employees'])) {
        foreach ($data['employees'] as $employee) {
            $stmt = $conn->prepare("INSERT INTO EMPLOYEES (id, first_name, last_name, role, phone, email) 
                                  VALUES (?, ?, ?, ?, ?, ?)");
            $stmt->execute([
                $employee['id'],
                $employee['first_name'],
                $employee['last_name'],
                $employee['role'],
                $employee['phone'],
                $employee['email']
            ]);
        }
        echo "Loaded employees data successfully.\n";
    }

    // Load menu items
    if (isset($data['menu_items'])) {
        foreach ($data['menu_items'] as $item) {
            $stmt = $conn->prepare("INSERT INTO MENU_ITEM (id, item_name, price, stock_availability) 
                                  VALUES (?, ?, ?, ?)");
            $stmt->execute([
                $item['id'],
                $item['item_name'],
                $item['price'],
                $item['stock_availability']
            ]);
        }
        echo "Loaded menu items data successfully.\n";
    }

    // Load orders
    if (isset($data['orders'])) {
        foreach ($data['orders'] as $order) {
            $stmt = $conn->prepare("INSERT INTO FOOD_ORDER (id, customer_id, order_status_id, table_id, order_date, total_price) 
                                  VALUES (?, ?, ?, ?, ?, ?)");
            $stmt->execute([
                $order['id'],
                $order['customer_id'],
                $order['order_status_id'],
                $order['table_id'],
                $order['order_date'],
                $order['total_price']
            ]);

            // Insert order items
            foreach ($order['items'] as $item) {
                $stmt = $conn->prepare("INSERT INTO ORDER_MENU_ITEM (order_id, menu_item_id, qty_ordered) 
                                      VALUES (?, ?, ?)");
                $stmt->execute([
                    $order['id'], 
                    $item['menu_item_id'],
                    $item['qty_ordered']
                ]);
            }
        }
        echo "Loaded orders data successfully.\n";
    }

    // Load payments
    if (isset($data['payments'])) {
        foreach ($data['payments'] as $payment) {
            $stmt = $conn->prepare("INSERT INTO PAYMENT (id, order_id, payment_method, amount_paid, payment_date, payment_status) 
                                  VALUES (?, ?, ?, ?, ?, ?)");
            $stmt->execute([
                $payment['id'],
                $payment['order_id'],
                $payment['payment_method'],
                $payment['amount_paid'],
                $payment['payment_date'],
                $payment['payment_status']
            ]);
        }
        echo "Loaded payments data successfully.\n";
    }

    // Commit transaction
    $conn->commit();
    echo "\nAll sample data loaded successfully!\n";

} catch (Exception $e) {
    if (isset($conn)) {
        $conn->rollback();
    }
    die("Error: " . $e->getMessage() . "\n");
} finally {
    if (isset($conn)) {
        $conn = null; // Close PDO connection
    }
}
