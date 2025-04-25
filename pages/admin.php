<?php
session_start();
require_once '../config/database.php';
$conn = getDBConnection();

// Handle form submissions
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    try {
        if ($_POST['action'] === 'add_table') {
            $stmt = $conn->prepare("
                INSERT INTO TABLES (table_number, num_seats, seat_is_occupied)
                VALUES (:table_number, :num_seats, FALSE)
            ");
            $stmt->execute([
                ':table_number' => $_POST['table_number'],
                ':num_seats' => $_POST['num_seats']
            ]);
            $_SESSION['success'] = "Table added successfully!";
        } elseif ($_POST['action'] === 'delete_table') {
            $stmt = $conn->prepare("
                SELECT COUNT(*) as count 
                FROM FOOD_ORDER 
                WHERE table_id = :table_id AND 
                      order_status_id IN (SELECT id FROM ORDER_STATUS WHERE status_value NOT IN ('Delivered', 'Cancelled'))
            ");
            $stmt->execute([':table_id' => $_POST['table_id']]);
            $inUse = $stmt->fetch()['count'] > 0;
            if ($inUse) {
                $_SESSION['error'] = "Cannot delete table - it has active orders!";
            } else {
                $stmt = $conn->prepare("DELETE FROM TABLES WHERE id = :id");
                $stmt->execute([':id' => $_POST['table_id']]);
                $_SESSION['success'] = "Table deleted successfully!";
            }
        } elseif ($_POST['action'] === 'toggle_table_status') {
            $stmt = $conn->prepare("
                UPDATE TABLES 
                SET seat_is_occupied = NOT seat_is_occupied
                WHERE id = :table_id
            ");
            $stmt->execute([':table_id' => $_POST['table_id']]);
            $_SESSION['success'] = "Table status updated!";
        } elseif ($_POST['action'] === 'add_menu_item') {
            $stmt = $conn->prepare("
                INSERT INTO MENU_ITEM (item_name, price, stock_availability)
                VALUES (:item_name, :price, TRUE)
            ");
            $stmt->execute([
                ':item_name' => $_POST['item_name'],
                ':price' => $_POST['price']
            ]);
            $_SESSION['success'] = "Menu item added successfully!";
        } elseif ($_POST['action'] === 'delete_menu_item') {
            $stmt = $conn->prepare("
                SELECT COUNT(*) as count 
                FROM ORDER_MENU_ITEM OMI
                JOIN FOOD_ORDER FO ON OMI.order_id = FO.id
                WHERE OMI.menu_item_id = :menu_item_id AND 
                      FO.order_status_id IN (SELECT id FROM ORDER_STATUS WHERE status_value NOT IN ('Delivered', 'Cancelled'))
            ");
            $stmt->execute([':menu_item_id' => $_POST['menu_item_id']]);
            $inUse = $stmt->fetch()['count'] > 0;
            if ($inUse) {
                $_SESSION['error'] = "Cannot delete menu item - it is in active orders!";
            } else {
                $stmt = $conn->prepare("DELETE FROM MENU_ITEM WHERE id = :id");
                $stmt->execute([':id' => $_POST['menu_item_id']]);
                $_SESSION['success'] = "Menu item deleted successfully!";
            }
        } elseif ($_POST['action'] === 'clear_completed') {
            $days = intval($_POST['days']);
            $stmt = $conn->prepare("
                DELETE FROM FOOD_ORDER 
                WHERE order_status_id IN (
                    SELECT id FROM ORDER_STATUS 
                    WHERE status_value IN ('Delivered', 'Cancelled')
                )
                AND order_date < DATE_SUB(NOW(), INTERVAL :days DAY)
            ");
            $stmt->execute([':days' => $days]);
            $_SESSION['success'] = "Old completed orders cleared successfully!";
        } elseif ($_POST['action'] === 'backup_db') {
            $tables = ['CUSTOMERS', 'TABLES', 'RESERVATIONS', 'ORDER_STATUS', 'FOOD_ORDER', 
                      'MENU_ITEM', 'ORDER_MENU_ITEM', 'PAYMENT', 'EMPLOYEES', 'SCHEDULES'];
            $backup = [];
            foreach ($tables as $table) {
                $stmt = $conn->query("SELECT * FROM $table");
                $backup[$table] = $stmt->fetchAll(PDO::FETCH_ASSOC);
            }
            $backupFile = '../backups/backup_' . date('Y-m-d_H-i-s') . '.json';
            if (!file_exists('../backups')) {
                mkdir('../backups', 0777, true);
            }
            file_put_contents($backupFile, json_encode($backup, JSON_PRETTY_PRINT));
            $_SESSION['success'] = "Database backup created successfully!";
        }
    } catch(PDOException $e) {
        $_SESSION['error'] = "Error: " . $e->getMessage();
    }
    header('Location: ' . $_SERVER['PHP_SELF']);
    exit();
}

// Fetch current database information
$tables = $conn->query("
    SELECT T.*, 
           (SELECT COUNT(*) FROM FOOD_ORDER FO WHERE FO.table_id = T.id AND 
            FO.order_status_id IN (SELECT id FROM ORDER_STATUS WHERE status_value NOT IN ('Delivered', 'Cancelled'))) as active_orders
    FROM TABLES T 
    ORDER BY table_number
")->fetchAll();

$menuItems = $conn->query("
    SELECT M.*,
           (SELECT COUNT(*) FROM ORDER_MENU_ITEM OMI 
            JOIN FOOD_ORDER FO ON OMI.order_id = FO.id 
            WHERE OMI.menu_item_id = M.id AND 
            FO.order_status_id IN (SELECT id FROM ORDER_STATUS WHERE status_value NOT IN ('Delivered', 'Cancelled'))) as active_orders
    FROM MENU_ITEM M 
    ORDER BY item_name
")->fetchAll();

$stats = [
    'total_orders' => $conn->query("SELECT COUNT(*) FROM FOOD_ORDER")->fetchColumn(),
    'active_orders' => $conn->query("
        SELECT COUNT(*) FROM FOOD_ORDER 
        WHERE order_status_id IN (SELECT id FROM ORDER_STATUS WHERE status_value NOT IN ('Delivered', 'Cancelled'))
    ")->fetchColumn(),
    'total_revenue' => $conn->query("
        SELECT COALESCE(SUM(amount_paid), 0) FROM PAYMENT 
        WHERE payment_status = 'Completed'
    ")->fetchColumn(),
    'total_employees' => $conn->query("SELECT COUNT(*) FROM EMPLOYEES")->fetchColumn()
];

require_once '../includes/header.php';
?>

<div class="container">
    <h2 class="mb-4">Admin Dashboard</h2>
    
    <?php if (isset($_SESSION['success'])): ?>
        <div class="alert alert-success"><?php echo $_SESSION['success']; unset($_SESSION['success']); ?></div>
    <?php endif; ?>
    
    <?php if (isset($_SESSION['error'])): ?>
        <div class="alert alert-danger"><?php echo $_SESSION['error']; unset($_SESSION['error']); ?></div>
    <?php endif; ?>
    
    <!-- System Overview -->
    <div class="row mb-4">
        <div class="col-md-3">
            <div class="card bg-primary text-white">
                <div class="card-body">
                    <h5 class="card-title">Total Orders</h5>
                    <h2><?php echo $stats['total_orders']; ?></h2>
                </div>
            </div>
        </div>
        <div class="col-md-3">
            <div class="card bg-warning">
                <div class="card-body">
                    <h5 class="card-title">Active Orders</h5>
                    <h2><?php echo $stats['active_orders']; ?></h2>
                </div>
            </div>
        </div>
        <div class="col-md-3">
            <div class="card bg-success text-white">
                <div class="card-body">
                    <h5 class="card-title">Total Revenue</h5>
                    <h2>$<?php echo number_format($stats['total_revenue'], 2); ?></h2>
                </div>
            </div>
        </div>
        <div class="col-md-3">
            <div class="card bg-info text-white">
                <div class="card-body">
                    <h5 class="card-title">Total Staff</h5>
                    <h2><?php echo $stats['total_employees']; ?></h2>
                </div>
            </div>
        </div>
    </div>
    
    <div class="row">
        <!-- Table Management -->
        <div class="col-md-6 mb-4">
            <div class="card">
                <div class="card-header">
                    <h3 class="card-title h5 mb-0">Table Management</h3>
                </div>
                <div class="card-body">
                    <form method="POST" class="mb-4" onsubmit="return validateForm('addTableForm')" id="addTableForm">
                        <input type="hidden" name="action" value="add_table">
                        <div class="row g-2">
                            <div class="col">
                                <input type="number" name="table_number" class="form-control" placeholder="Table Number" required min="1">
                            </div>
                            <div class="col">
                                <input type="number" name="num_seats" class="form-control" placeholder="Number of Seats" required min="1">
                            </div>
                            <div class="col-auto">
                                <button type="submit" class="btn btn-primary">Add Table</button>
                            </div>
                        </div>
                    </form>
                    
                    <div class="table-responsive">
                        <table class="table">
                            <thead>
                                <tr>
                                    <th>Table</th>
                                    <th>Seats</th>
                                    <th>Status</th>
                                    <th>Actions</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php foreach ($tables as $table): ?>
                                    <tr>
                                        <td><?php echo $table['table_number']; ?></td>
                                        <td><?php echo $table['num_seats']; ?></td>
                                        <td>
                                            <?php if ($table['seat_is_occupied']): ?>
                                                <span class="badge bg-danger">Occupied</span>
                                            <?php else: ?>
                                                <span class="badge bg-success">Available</span>
                                            <?php endif; ?>
                                            <?php if ($table['active_orders'] > 0): ?>
                                                <span class="badge bg-warning"><?php echo $table['active_orders']; ?> active orders</span>
                                            <?php endif; ?>
                                        </td>
                                        <td>
                                            <!-- Toggle Seat Status Button -->
                                            <form method="POST" class="d-inline">
                                                <input type="hidden" name="action" value="toggle_table_status">
                                                <input type="hidden" name="table_id" value="<?php echo $table['id']; ?>">
                                                <button type="submit" class="btn btn-sm <?php echo $table['seat_is_occupied'] ? 'btn-success' : 'btn-warning'; ?>">
                                                    <?php echo $table['seat_is_occupied'] ? 'Mark Available' : 'Mark Occupied'; ?>
                                                </button>
                                            </form>

                                            <!-- Delete Table Button -->
                                            <form method="POST" class="d-inline" onsubmit="return confirm('Are you sure you want to delete this table?');">
                                                <input type="hidden" name="action" value="delete_table">
                                                <input type="hidden" name="table_id" value="<?php echo $table['id']; ?>">
                                                <button type="submit" class="btn btn-sm btn-danger" <?php echo $table['active_orders'] > 0 ? 'disabled' : ''; ?>>
                                                    <i class="bi bi-trash"></i>
                                                </button>
                                            </form>
                                        </td>
                                    </tr>
                                <?php endforeach; ?>
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>
        </div>
        
        <!-- Menu Management -->
        <div class="col-md-6 mb-4">
            <div class="card">
                <div class="card-header">
                    <h3 class="card-title h5 mb-0">Menu Management</h3>
                </div>
                <div class="card-body">
                    <form method="POST" class="mb-4" onsubmit="return validateForm('addMenuItemForm')" id="addMenuItemForm">
                        <input type="hidden" name="action" value="add_menu_item">
                        <div class="row g-2">
                            <div class="col">
                                <input type="text" name="item_name" class="form-control" placeholder="Item Name" required>
                            </div>
                            <div class="col">
                                <input type="number" name="price" class="form-control" placeholder="Price" required min="0" step="0.01">
                            </div>
                            <div class="col-auto">
                                <button type="submit" class="btn btn-primary">Add Item</button>
                            </div>
                        </div>
                    </form>
                    
                    <div class="table-responsive">
                        <table class="table">
                            <thead>
                                <tr>
                                    <th>Item</th>
                                    <th>Price</th>
                                    <th>Status</th>
                                    <th>Actions</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php foreach ($menuItems as $item): ?>
                                    <tr>
                                        <td><?php echo htmlspecialchars($item['item_name']); ?></td>
                                        <td>$<?php echo number_format($item['price'], 2); ?></td>
                                        <td>
                                            <?php if ($item['stock_availability']): ?>
                                                <span class="badge bg-success">In Stock</span>
                                            <?php else: ?>
                                                <span class="badge bg-danger">Out of Stock</span>
                                            <?php endif; ?>
                                            <?php if ($item['active_orders'] > 0): ?>
                                                <span class="badge bg-warning"><?php echo $item['active_orders']; ?> active orders</span>
                                            <?php endif; ?>
                                        </td>
                                        <td>
                                            <form method="POST" class="d-inline" onsubmit="return confirm('Are you sure you want to delete this menu item?');">
                                                <input type="hidden" name="action" value="delete_menu_item">
                                                <input type="hidden" name="menu_item_id" value="<?php echo $item['id']; ?>">
                                                <button type="submit" class="btn btn-sm btn-danger" <?php echo $item['active_orders'] > 0 ? 'disabled' : ''; ?>>
                                                    <i class="bi bi-trash"></i>
                                                </button>
                                            </form>
                                        </td>
                                    </tr>
                                <?php endforeach; ?>
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>
        </div>
    </div>
    
    <!-- System Maintenance -->
    <div class="row">
        <div class="col-md-6 mb-4">
            <div class="card">
                <div class="card-header">
                    <h3 class="card-title h5 mb-0">Database Maintenance</h3>
                </div>
                <div class="card-body">
                    <form method="POST" class="mb-3" onsubmit="return confirm('Are you sure you want to clear old orders?');">
                        <input type="hidden" name="action" value="clear_completed">
                        <div class="input-group">
                            <input type="number" name="days" class="form-control" placeholder="Days to keep" required min="1" value="30">
                            <button type="submit" class="btn btn-warning">Clear Old Orders</button>
                        </div>
                        <small class="text-muted">This will permanently delete completed and cancelled orders older than specified days.</small>
                    </form>
                    
                    <form method="POST">
                        <input type="hidden" name="action" value="backup_db">
                        <button type="submit" class="btn btn-info">Create Database Backup</button>
                    </form>
                </div>
            </div>
        </div>
        
        <div class="col-md-6 mb-4">
            <div class="card">
                <div class="card-header">
                    <h3 class="card-title h5 mb-0">System Information</h3>
                </div>
                <div class="card-body">
                    <ul class="list-group">
                        <li class="list-group-item d-flex justify-content-between align-items-center">
                            PHP Version
                            <span class="badge bg-primary"><?php echo phpversion(); ?></span>
                        </li>
                        <li class="list-group-item d-flex justify-content-between align-items-center">
                            MySQL Version
                            <span class="badge bg-primary"><?php echo $conn->getAttribute(PDO::ATTR_SERVER_VERSION); ?></span>
                        </li>
                        <li class="list-group-item d-flex justify-content-between align-items-center">
                            Server Time
                            <span class="badge bg-primary"><?php echo date('Y-m-d H:i:s'); ?></span>
                        </li>
                        <li class="list-group-item d-flex justify-content-between align-items-center">
                            Database Size
                            <span class="badge bg-primary">
                                <?php 
                                    $size = $conn->query("
                                        SELECT SUM(data_length + index_length) as size 
                                        FROM information_schema.TABLES 
                                        WHERE table_schema = 'restaurant_db'
                                    ")->fetchColumn();
                                    echo number_format($size / 1024 / 1024, 2) . ' MB';
                                ?>
                            </span>
                        </li>
                    </ul>
                </div>
            </div>
        </div>
    </div>
</div>

<?php require_once '../includes/footer.php'; ?>
