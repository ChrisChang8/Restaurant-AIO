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

// Get order details
if (isset($_GET['order_id'])) {
    try {
        $stmt = $conn->prepare("
            SELECT 
                FO.id,
                FO.order_date,
                FO.total_price,
                C.first_name,
                C.last_name,
                T.table_number,
                OS.status_value,
                P.payment_status,
                P.payment_method,
                P.amount_paid
            FROM FOOD_ORDER FO
            JOIN CUSTOMERS C ON FO.customer_id = C.id
            JOIN TABLES T ON FO.table_id = T.id
            JOIN ORDER_STATUS OS ON FO.order_status_id = OS.id
            LEFT JOIN PAYMENT P ON FO.id = P.order_id
            WHERE FO.id = :order_id
        ");
        
        $stmt->execute([':order_id' => $_GET['order_id']]);
        $order = $stmt->fetch();
        
        // Get order items
        $stmt = $conn->prepare("
            SELECT 
                MI.item_name,
                MI.price,
                OMI.qty_ordered,
                (MI.price * OMI.qty_ordered) as subtotal
            FROM ORDER_MENU_ITEM OMI
            JOIN MENU_ITEM MI ON OMI.menu_item_id = MI.id
            WHERE OMI.order_id = :order_id
        ");
        
        $stmt->execute([':order_id' => $_GET['order_id']]);
        $items = $stmt->fetchAll();
        
    } catch(PDOException $e) {
        $_SESSION['error'] = "Error retrieving order details: " . $e->getMessage();
        header('Location: orders.php');
        exit();
    }
}

// Handle payment processing
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    try {
        $stmt = $conn->prepare("
            UPDATE PAYMENT
            SET payment_method = :method,
                amount_paid = :amount,
                payment_status = 'Completed',
                payment_date = NOW()
            WHERE order_id = :order_id
        ");
        
        $stmt->execute([
            ':method' => $_POST['payment_method'],
            ':amount' => $_POST['amount_paid'],
            ':order_id' => $_POST['order_id']
        ]);
        
        // If payment is completed, free up the table
        if ($_POST['payment_method'] !== 'Pending') {
            $stmt = $conn->prepare("
                UPDATE TABLES T
                JOIN FOOD_ORDER FO ON T.id = FO.table_id
                SET T.seat_is_occupied = FALSE
                WHERE FO.id = :order_id
            ");
            
            $stmt->execute([':order_id' => $_POST['order_id']]);
        }
        
        $_SESSION['success'] = "Payment processed successfully!";
        header('Location: orders.php');
        exit();
        
    } catch(PDOException $e) {
        $_SESSION['error'] = "Error processing payment: " . $e->getMessage();
        header('Location: ' . $_SERVER['PHP_SELF'] . '?order_id=' . $_POST['order_id']);
        exit();
    }
}

require_once '../includes/header.php';
?>

<div class="container">
    <h2 class="mb-4">Process Payment</h2>
    
    <?php if (isset($success)): ?>
        <div class="alert alert-success"><?php echo $success; ?></div>
    <?php endif; ?>
    
    <?php if (isset($error)): ?>
        <div class="alert alert-danger"><?php echo $error; ?></div>
    <?php endif; ?>
    
    <?php if (isset($order)): ?>
        <div class="row">
            <div class="col-md-6">
                <div class="card">
                    <div class="card-header">
                        <h3 class="card-title">Order Details</h3>
                    </div>
                    <div class="card-body">
                        <p><strong>Order #:</strong> <?php echo $order['id']; ?></p>
                        <p><strong>Customer:</strong> <?php echo htmlspecialchars($order['first_name'] . ' ' . $order['last_name']); ?></p>
                        <p><strong>Table:</strong> <?php echo htmlspecialchars($order['table_number']); ?></p>
                        <p><strong>Status:</strong> <?php echo htmlspecialchars($order['status_value']); ?></p>
                        <p><strong>Date:</strong> <?php echo $order['order_date']; ?></p>
                        
                        <h4 class="mt-4">Order Items</h4>
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
                                <?php foreach ($items as $item): ?>
                                    <tr>
                                        <td><?php echo htmlspecialchars($item['item_name']); ?></td>
                                        <td>$<?php echo number_format($item['price'], 2); ?></td>
                                        <td><?php echo $item['qty_ordered']; ?></td>
                                        <td>$<?php echo number_format($item['subtotal'], 2); ?></td>
                                    </tr>
                                <?php endforeach; ?>
                            </tbody>
                            <tfoot>
                                <tr>
                                    <th colspan="3">Total:</th>
                                    <th>$<?php echo number_format($order['total_price'], 2); ?></th>
                                </tr>
                            </tfoot>
                        </table>
                    </div>
                </div>
            </div>
            
            <div class="col-md-6">
                <div class="card">
                    <div class="card-header">
                        <h3 class="card-title">Process Payment</h3>
                    </div>
                    <div class="card-body">
                        <?php if ($order['status_value'] === 'Cancelled'): ?>
                            <div class="alert alert-secondary">
                                <h4>Order Cancelled</h4>
                                <p>This order has been cancelled and cannot be processed for payment.</p>
                            </div>
                            <a href="orders.php" class="btn btn-primary">Back to Orders</a>
                        <?php elseif ($order['payment_status'] === 'Completed'): ?>
                            <div class="alert alert-success">
                                <h4>Payment Completed</h4>
                                <p><strong>Method:</strong> <?php echo htmlspecialchars($order['payment_method']); ?></p>
                                <p><strong>Amount Paid:</strong> $<?php echo number_format($order['amount_paid'], 2); ?></p>
                            </div>
                            <a href="orders.php" class="btn btn-primary">Back to Orders</a>
                        <?php else: ?>
                            <form method="POST" onsubmit="return validateForm('paymentForm')" id="paymentForm">
                                <input type="hidden" name="order_id" value="<?php echo $order['id']; ?>">
                                
                                <div class="mb-3">
                                    <label class="form-label">Payment Method</label>
                                    <select name="payment_method" class="form-control" required>
                                        <option value="">Select payment method</option>
                                        <option value="Cash">Cash</option>
                                        <option value="Credit Card">Credit Card</option>
                                        <option value="Debit Card">Debit Card</option>
                                    </select>
                                </div>
                                
                                <div class="mb-3">
                                    <label class="form-label">Amount</label>
                                    <input type="number" 
                                           name="amount_paid" 
                                           class="form-control" 
                                           step="0.01" 
                                           value="<?php echo $order['total_price']; ?>"
                                           required>
                                </div>
                                
                                <button type="submit" class="btn btn-primary">Process Payment</button>
                                <a href="orders.php" class="btn btn-secondary">Back to Orders</a>
                            </form>
                        <?php endif; ?>
                    </div>
                </div>
            </div>
        </div>
    <?php else: ?>
        <div class="alert alert-danger">Order not found.</div>
        <a href="orders.php" class="btn btn-primary">Back to Orders</a>
    <?php endif; ?>
</div>

<?php require_once '../includes/footer.php'; ?>
