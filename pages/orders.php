<?php
session_start();
require_once '../config/database.php';
$conn = getDBConnection();

// Display messages from session
if (isset($_SESSION['success'])) {
    $success = $_SESSION['success'];
    unset($_SESSION['success']);
}
if (isset($_SESSION['error'])) {
    $error = $_SESSION['error'];
    unset($_SESSION['error']);
}

// Handle form submission
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    try {
        if ($_POST['action'] === 'create_order') {
            // Start transaction
            $conn->beginTransaction();
            
            // Create or get customer
            $stmt = $conn->prepare("
                INSERT INTO CUSTOMERS (first_name, last_name, phone)
                VALUES (:first_name, :last_name, :phone)
            ");
            
            $stmt->execute([
                ':first_name' => $_POST['first_name'],
                ':last_name' => $_POST['last_name'],
                ':phone' => $_POST['phone']
            ]);
            
            $customerId = $conn->lastInsertId();
            
            // Create food order
            $stmt = $conn->prepare("
                INSERT INTO FOOD_ORDER (customer_id, order_status_id, table_id, order_date, total_price)
                VALUES (:customer_id, :status_id, :table_id, NOW(), :total_price)
            ");
            
            $stmt->execute([
                ':customer_id' => $customerId,
                ':status_id' => 1, // Pending status
                ':table_id' => $_POST['table_id'],
                ':total_price' => $_POST['total_price']
            ]);
            
            $orderId = $conn->lastInsertId();
            
            // Add order items
            foreach ($_POST['items'] as $itemId => $quantity) {
                if ($quantity > 0) {
                    $stmt = $conn->prepare("
                        INSERT INTO ORDER_MENU_ITEM (order_id, menu_item_id, qty_ordered)
                        VALUES (:order_id, :menu_item_id, :quantity)
                    ");
                    
                    $stmt->execute([
                        ':order_id' => $orderId,
                        ':menu_item_id' => $itemId,
                        ':quantity' => $quantity
                    ]);
                }
            }
            
            // Update table status
            $stmt = $conn->prepare("
                UPDATE TABLES SET seat_is_occupied = TRUE
                WHERE id = :table_id
            ");
            
            $stmt->execute([':table_id' => $_POST['table_id']]);
            
            $conn->commit();
            $_SESSION['success'] = "Order created successfully!";
            header('Location: ' . $_SERVER['PHP_SELF']);
            exit();
        } elseif ($_POST['action'] === 'update_status') {
            $stmt = $conn->prepare("
                UPDATE FOOD_ORDER 
                SET order_status_id = :status_id
                WHERE id = :order_id
            ");
            
            $stmt->execute([
                ':order_id' => $_POST['order_id'],
                ':status_id' => $_POST['status_id']
            ]);
            
            // If order is delivered, create or update payment record
            if ($_POST['status_id'] == 4) { // Delivered status
                $stmt = $conn->prepare("
                    INSERT INTO PAYMENT (order_id, payment_method, amount_paid, payment_date, payment_status)
                    SELECT id, 'Pending', total_price, NOW(), 'Pending'
                    FROM FOOD_ORDER WHERE id = :order_id
                    ON DUPLICATE KEY UPDATE
                    payment_method = VALUES(payment_method),
                    amount_paid = VALUES(amount_paid),
                    payment_date = VALUES(payment_date),
                    payment_status = VALUES(payment_status)
                ");
                $stmt->execute([':order_id' => $_POST['order_id']]);
            
            // If order is cancelled, automatically mark payment as cancelled
            if ($_POST['status_id'] == 5) { // Cancelled status
                // Insert or update payment record as cancelled
                $stmt = $conn->prepare("
                    INSERT INTO PAYMENT (order_id, payment_method, amount_paid, payment_date, payment_status)
                    VALUES (:order_id, 'Cancelled', 0, NOW(), 'Cancelled')
                    ON DUPLICATE KEY UPDATE
                    payment_method = 'Cancelled',
                    amount_paid = 0,
                    payment_status = 'Cancelled'
                ");
                $stmt->execute([':order_id' => $_POST['order_id']]);
                
                // Free up the table
                $stmt = $conn->prepare("
                    UPDATE TABLES T
                    JOIN FOOD_ORDER FO ON T.id = FO.table_id
                    SET T.seat_is_occupied = FALSE
                    WHERE FO.id = :order_id
                ");
                $stmt->execute([':order_id' => $_POST['order_id']]);
            }
            }
            
            $_SESSION['success'] = "Order status updated successfully!";
            header('Location: ' . $_SERVER['PHP_SELF']);
            exit();
        }
    } catch(PDOException $e) {
        if (isset($conn)) {
            $conn->rollBack();
        }
        $_SESSION['error'] = "Error processing order: " . $e->getMessage();
        header('Location: ' . $_SERVER['PHP_SELF']);
        exit();
    }
}

// Get available tables
$tables = $conn->query("SELECT * FROM TABLES WHERE seat_is_occupied = FALSE")->fetchAll();

// Get menu items
$menu_items = $conn->query("SELECT * FROM MENU_ITEM WHERE stock_availability = TRUE ORDER BY item_name")->fetchAll();

// Get order statuses
$statuses = $conn->query("SELECT * FROM ORDER_STATUS")->fetchAll();

// Get active orders (not delivered or cancelled)
$orders = $conn->query("
    SELECT 
        FO.id,
        FO.order_date,
        FO.total_price,
        C.first_name,
        C.last_name,
        T.table_number,
        OS.status_value,
        OS.id as status_id
    FROM FOOD_ORDER FO
    JOIN CUSTOMERS C ON FO.customer_id = C.id
    JOIN TABLES T ON FO.table_id = T.id
    JOIN ORDER_STATUS OS ON FO.order_status_id = OS.id
    WHERE OS.status_value NOT IN ('Delivered', 'Cancelled')
    ORDER BY FO.order_date DESC
")->fetchAll();

// Get fulfilled orders with payment status
$fulfilled_orders = $conn->query("
    SELECT 
        FO.id,
        FO.order_date,
        FO.total_price,
        C.first_name,
        C.last_name,
        T.table_number,
        OS.status_value as order_status,
        OS.id as status_id,
        CASE 
            WHEN OS.status_value = 'Cancelled' THEN 'Cancelled'
            ELSE MAX(COALESCE(P.payment_status, 'Pending'))
        END as payment_status,
        CASE
            WHEN OS.status_value = 'Cancelled' THEN 'Cancelled'
            ELSE MAX(COALESCE(P.payment_method, 'Not Paid'))
        END as payment_method,
        MAX(P.payment_date) as payment_date,
        MAX(P.amount_paid) as amount_paid
    FROM FOOD_ORDER FO
    JOIN CUSTOMERS C ON FO.customer_id = C.id
    JOIN TABLES T ON FO.table_id = T.id
    JOIN ORDER_STATUS OS ON FO.order_status_id = OS.id
    LEFT JOIN PAYMENT P ON FO.id = P.order_id
    WHERE OS.status_value IN ('Delivered', 'Cancelled')
    GROUP BY FO.id, FO.order_date, FO.total_price, C.first_name, C.last_name, T.table_number, OS.status_value, OS.id
    ORDER BY FO.order_date DESC
")->fetchAll();

require_once '../includes/header.php';
?>

<div class="container">
    <h2 class="mb-4">Order Management</h2>
    
    <?php if (isset($success)): ?>
        <div class="alert alert-success"><?php echo $success; ?></div>
    <?php endif; ?>
    
    <?php if (isset($error)): ?>
        <div class="alert alert-danger"><?php echo $error; ?></div>
    <?php endif; ?>

    <div class="row">
        <!-- New Order Form -->
        <div class="col-md-6">
            <div class="card">
                <div class="card-header">
                    <h3 class="card-title">Create New Order</h3>
                </div>
                <div class="card-body">
                    <form method="POST" onsubmit="return validateForm('orderForm')" id="orderForm">
                        <input type="hidden" name="action" value="create_order">
                        <input type="hidden" name="total_price" id="totalPrice" value="0">
                        
                        <div class="mb-3">
                            <label class="form-label">First Name</label>
                            <input type="text" name="first_name" class="form-control" required>
                        </div>
                        
                        <div class="mb-3">
                            <label class="form-label">Last Name</label>
                            <input type="text" name="last_name" class="form-control" required>
                        </div>
                        
                        <div class="mb-3">
                            <label class="form-label">Phone</label>
                            <input type="tel" name="phone" class="form-control" required>
                        </div>
                        
                        <div class="mb-3">
                            <label class="form-label">Table</label>
                            <select name="table_id" class="form-control" required>
                                <option value="">Select a table</option>
                                <?php foreach ($tables as $table): ?>
                                    <option value="<?php echo $table['id']; ?>">
                                        Table <?php echo $table['table_number']; ?> (<?php echo $table['num_seats']; ?> seats)
                                    </option>
                                <?php endforeach; ?>
                            </select>
                        </div>
                        
                        <div class="mb-3">
                            <label class="form-label">Menu Items</label>
                            <div class="table-responsive">
                                <table class="table">
                                    <thead>
                                        <tr>
                                            <th>Item</th>
                                            <th>Price</th>
                                            <th>Quantity</th>
                                            <th>Subtotal</th>
                                        </tr>
                                    </thead>
                                    <tbody>
                                        <?php foreach ($menu_items as $item): ?>
                                            <tr>
                                                <td><?php echo htmlspecialchars($item['item_name']); ?></td>
                                                <td>$<?php echo number_format($item['price'], 2); ?></td>
                                                <td>
                                                    <input type="number" 
                                                           name="items[<?php echo $item['id']; ?>]" 
                                                           value="0" 
                                                           min="0" 
                                                           class="form-control quantity-input"
                                                           data-price="<?php echo $item['price']; ?>">
                                                </td>
                                                <td class="subtotal">$0.00</td>
                                            </tr>
                                        <?php endforeach; ?>
                                    </tbody>
                                    <tfoot>
                                        <tr>
                                            <th colspan="3">Total:</th>
                                            <th id="orderTotal">$0.00</th>
                                        </tr>
                                    </tfoot>
                                </table>
                            </div>
                        </div>
                        
                        <button type="submit" class="btn btn-primary">Create Order</button>
                    </form>
                </div>
            </div>
        </div>

        <!-- Active Orders -->
        <div class="col-md-6">
            <h3>Active Orders</h3>
            <div class="table-responsive">
                <table class="table table-striped">
                    <thead>
                        <tr>
                            <th>Order #</th>
                            <th>Customer</th>
                            <th>Table</th>
                            <th>Total</th>
                            <th>Status</th>
                            <th>Actions</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($orders as $order): ?>
                            <tr>
                                <td><?php echo $order['id']; ?></td>
                                <td><?php echo htmlspecialchars($order['first_name'] . ' ' . $order['last_name']); ?></td>
                                <td><?php echo htmlspecialchars($order['table_number']); ?></td>
                                <td>$<?php echo number_format($order['total_price'], 2); ?></td>
                                <td>
                                    <form method="POST" class="d-inline">
                                        <input type="hidden" name="action" value="update_status">
                                        <input type="hidden" name="order_id" value="<?php echo $order['id']; ?>">
                                        <select name="status_id" class="form-control status-select" 
                                                onchange="this.form.submit()">
                                            <?php foreach ($statuses as $status): ?>
                                                <option value="<?php echo $status['id']; ?>"
                                                    <?php echo $status['id'] == $order['status_id'] ? 'selected' : ''; ?>>
                                                    <?php echo htmlspecialchars($status['status_value']); ?>
                                                </option>
                                            <?php endforeach; ?>
                                        </select>
                                    </form>
                                </td>
                                <td>
                                    <a href="/restaurant/pages/payment.php?order_id=<?php echo $order['id']; ?>" 
                                       class="btn btn-success btn-sm">
                                        Payment
                                    </a>
                                </td>
                            </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            </div>
        </div>
    </div>

    <!-- Fulfilled Orders with Payment Status -->
    <div class="row mt-4">
        <div class="col-12">
            <h3>Orders History</h3>
            <div class="table-responsive">
                <table class="table table-striped">
                    <thead>
                        <tr>
                            <th>Order #</th>
                            <th>Customer</th>
                            <th>Table</th>
                            <th>Total</th>
                            <th>Order Status</th>
                            <th>Payment Status</th>
                            <th>Payment Details</th>
                            <th>Order Date</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($fulfilled_orders as $order): ?>
                            <tr>
                                <td><?php echo $order['id']; ?></td>
                                <td><?php echo htmlspecialchars($order['first_name'] . ' ' . $order['last_name']); ?></td>
                                <td><?php echo htmlspecialchars($order['table_number']); ?></td>
                                <td>$<?php echo number_format($order['total_price'], 2); ?></td>
                                <td>
                                    <span class="badge bg-<?php echo $order['order_status'] === 'Delivered' ? 'success' : 'secondary'; ?>">
                                        <?php echo htmlspecialchars($order['order_status']); ?>
                                    </span>
                                </td>
                                <td>
                                    <span class="badge bg-<?php 
                                        if ($order['payment_status'] === 'Completed') echo 'success';
                                        elseif ($order['payment_status'] === 'Cancelled') echo 'secondary';
                                        else echo 'warning';
                                    ?>">
                                        <?php echo htmlspecialchars($order['payment_status']); ?>
                                    </span>
                                </td>
                                <td>
                                    <?php if ($order['order_status'] === 'Cancelled'): ?>
                                        <span class="badge bg-secondary">Cancelled</span>
                                    <?php elseif ($order['payment_status'] === 'Completed'): ?>
                                        <?php echo htmlspecialchars($order['payment_method']); ?><br>
                                        $<?php echo number_format($order['amount_paid'], 2); ?><br>
                                        <small class="text-muted"><?php echo date('Y-m-d H:i', strtotime($order['payment_date'])); ?></small>
                                    <?php else: ?>
                                        <a href="payment.php?order_id=<?php echo $order['id']; ?>" class="btn btn-sm btn-primary">Process Payment</a>
                                    <?php endif; ?>
                                </td>
                                <td><?php echo date('Y-m-d H:i', strtotime($order['order_date'])); ?></td>
                            </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            </div>
        </div>
    </div>
</div>

<script>
// Calculate subtotals and total
function calculateTotals() {
    let total = 0;
    document.querySelectorAll('.quantity-input').forEach(input => {
        const price = parseFloat(input.dataset.price);
        const quantity = parseInt(input.value) || 0;
        const subtotal = price * quantity;
        
        // Update subtotal display
        input.closest('tr').querySelector('.subtotal').textContent = 
            '$' + subtotal.toFixed(2);
        
        total += subtotal;
    });
    
    // Update total display and hidden input
    document.getElementById('orderTotal').textContent = '$' + total.toFixed(2);
    document.getElementById('totalPrice').value = total;
}

// Add event listeners
document.addEventListener('DOMContentLoaded', function() {
    document.querySelectorAll('.quantity-input').forEach(input => {
        input.addEventListener('change', calculateTotals);
    });
    
    // Initialize totals
    calculateTotals();
});
</script>

<?php require_once '../includes/footer.php'; ?>
