<?php
session_start();
require_once '../config/database.php';
$conn = getDBConnection();

// Get current date and time
$currentDate = date('Y-m-d');
$currentTime = date('H:i:s');

// Get today's reservations
$reservations = $conn->query("
    SELECT 
        R.id,
        R.num_guests,
        R.reservation_date,
        T.table_number,
        CONCAT(C.first_name, ' ', C.last_name) as customer_name,
        C.phone
    FROM RESERVATIONS R
    JOIN CUSTOMERS C ON R.customer_id = C.id
    JOIN TABLES T ON R.table_id = T.id
    WHERE DATE(R.reservation_date) = CURRENT_DATE()
    ORDER BY R.reservation_date
")->fetchAll();

// Get active orders
$activeOrders = $conn->query("
    SELECT 
        FO.id,
        FO.order_date,
        T.table_number,
        OS.status_value,
        COUNT(OMI.id) as item_count,
        COALESCE(P.payment_status, 'Pending') as payment_status
    FROM FOOD_ORDER FO
    JOIN ORDER_STATUS OS ON FO.order_status_id = OS.id
    JOIN TABLES T ON FO.table_id = T.id
    LEFT JOIN ORDER_MENU_ITEM OMI ON FO.id = OMI.order_id
    LEFT JOIN PAYMENT P ON FO.id = P.order_id
    WHERE OS.status_value NOT IN ('Delivered', 'Cancelled')
    GROUP BY FO.id
    ORDER BY FO.order_date DESC
    LIMIT 5
")->fetchAll();

// Get today's employee schedule
$todaySchedule = $conn->query("
    SELECT 
        E.first_name,
        E.last_name,
        E.role,
        S.start_time,
        S.end_time
    FROM SCHEDULES S
    JOIN EMPLOYEES E ON S.employee_id = E.id
    WHERE S.shift_date = CURRENT_DATE()
    ORDER BY S.start_time
")->fetchAll();

// Get table status
$tables = $conn->query("
    SELECT 
        id,
        table_number,
        num_seats,
        seat_is_occupied
    FROM TABLES
    ORDER BY table_number
")->fetchAll();

require_once '../includes/header.php';
?>

<div class="container">
    <h2 class="mb-4">Restaurant Dashboard</h2>
    
    <div class="row">
        <!-- Today's Reservations -->
        <div class="col-md-6 mb-4">
            <div class="card h-100">
                <div class="card-header bg-primary text-white">
                    <h3 class="card-title h5 mb-0">Today's Reservations</h3>
                </div>
                <div class="card-body">
                    <?php if (!empty($reservations)): ?>
                        <div class="table-responsive">
                            <table class="table table-sm">
                                <thead>
                                    <tr>
                                        <th>Time</th>
                                        <th>Customer</th>
                                        <th>Table</th>
                                        <th>Guests</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <?php foreach ($reservations as $reservation): ?>
                                        <tr>
                                            <td><?php echo date('g:i A', strtotime($reservation['reservation_date'])); ?></td>
                                            <td>
                                                <?php echo htmlspecialchars($reservation['customer_name']); ?>
                                                <br>
                                                <small class="text-muted"><?php echo htmlspecialchars($reservation['phone']); ?></small>
                                            </td>
                                            <td><?php echo $reservation['table_number']; ?></td>
                                            <td><?php echo $reservation['num_guests']; ?></td>
                                        </tr>
                                    <?php endforeach; ?>
                                </tbody>
                            </table>
                        </div>
                    <?php else: ?>
                        <p class="text-muted">No reservations for today.</p>
                    <?php endif; ?>
                </div>
            </div>
        </div>
        
        <!-- Active Orders -->
        <div class="col-md-6 mb-4">
            <div class="card h-100">
                <div class="card-header bg-info text-white">
                    <h3 class="card-title h5 mb-0">Active Orders</h3>
                </div>
                <div class="card-body">
                    <?php if (!empty($activeOrders)): ?>
                        <div class="table-responsive">
                            <table class="table table-sm">
                                <thead>
                                    <tr>
                                        <th>Table</th>
                                        <th>Items</th>
                                        <th>Status</th>
                                        <th>Time</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <?php foreach ($activeOrders as $order): ?>
                                        <tr>
                                            <td><?php echo $order['table_number']; ?></td>
                                            <td><?php echo $order['item_count']; ?></td>
                                            <td>
                                                <span class="badge bg-<?php 
                                                    echo match($order['status_value']) {
                                                        'Pending' => 'warning',
                                                        'Preparing' => 'info',
                                                        'Ready' => 'success',
                                                        default => 'secondary'
                                                    };
                                                ?>">
                                                    <?php echo $order['status_value']; ?>
                                                </span>
                                            </td>
                                            <td><?php echo date('g:i A', strtotime($order['order_date'])); ?></td>
                                        </tr>
                                    <?php endforeach; ?>
                                </tbody>
                            </table>
                        </div>
                    <?php else: ?>
                        <p class="text-muted">No active orders.</p>
                    <?php endif; ?>
                </div>
            </div>
        </div>
    </div>
    
    <div class="row">
        <!-- Today's Staff -->
        <div class="col-md-6 mb-4">
            <div class="card h-100">
                <div class="card-header bg-success text-white">
                    <h3 class="card-title h5 mb-0">Today's Staff</h3>
                </div>
                <div class="card-body">
                    <?php if (!empty($todaySchedule)): ?>
                        <div class="table-responsive">
                            <table class="table table-sm">
                                <thead>
                                    <tr>
                                        <th>Name</th>
                                        <th>Role</th>
                                        <th>Hours</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <?php foreach ($todaySchedule as $schedule): ?>
                                        <tr>
                                            <td><?php echo htmlspecialchars($schedule['first_name'] . ' ' . $schedule['last_name']); ?></td>
                                            <td><?php echo htmlspecialchars($schedule['role']); ?></td>
                                            <td>
                                                <?php 
                                                    echo date('g:i A', strtotime($schedule['start_time'])) . ' - ' . 
                                                         date('g:i A', strtotime($schedule['end_time'])); 
                                                ?>
                                            </td>
                                        </tr>
                                    <?php endforeach; ?>
                                </tbody>
                            </table>
                        </div>
                    <?php else: ?>
                        <p class="text-muted">No staff scheduled for today.</p>
                    <?php endif; ?>
                </div>
            </div>
        </div>
        
        <!-- Table Status -->
        <div class="col-md-6 mb-4">
            <div class="card h-100">
                <div class="card-header bg-warning">
                    <h3 class="card-title h5 mb-0">Table Status</h3>
                </div>
                <div class="card-body">
                    <div class="row row-cols-2 row-cols-sm-3 row-cols-md-4 g-3">
                        <?php foreach ($tables as $table): ?>
                            <div class="col">
                                <div class="card <?php echo $table['seat_is_occupied'] ? 'bg-danger text-white' : 'bg-success text-white'; ?>">
                                    <div class="card-body p-2 text-center">
                                        <h5 class="card-title mb-0">Table <?php echo $table['table_number']; ?></h5>
                                        <small><?php echo $table['num_seats']; ?> seats</small>
                                        <br>
                                        <span class="badge bg-light text-dark">
                                            <?php echo $table['seat_is_occupied'] ? 'Occupied' : 'Available'; ?>
                                        </span>
                                    </div>
                                </div>
                            </div>
                        <?php endforeach; ?>
                    </div>
                </div>
            </div>
        </div>
    </div>
    
    <!-- Quick Actions -->
    <div class="row mb-4">
        <div class="col-12">
            <div class="card">
                <div class="card-header">
                    <h3 class="card-title h5 mb-0">Quick Actions</h3>
                </div>
                <div class="card-body">
                    <div class="d-flex gap-2 flex-wrap">
                        <a href="reservations.php" class="btn btn-primary">
                            <i class="bi bi-calendar-plus"></i> New Reservation
                        </a>
                        <a href="orders.php" class="btn btn-info text-white">
                            <i class="bi bi-cart-plus"></i> New Order
                        </a>
                        <a href="menu.php" class="btn btn-success">
                            <i class="bi bi-menu-button"></i> Update Menu
                        </a>
                        <a href="employees.php" class="btn btn-warning">
                            <i class="bi bi-people"></i> Manage Staff
                        </a>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

<script>
// Auto-refresh the page every 5 minutes
setTimeout(function() {
    window.location.reload();
}, 300000);
</script>

<?php require_once '../includes/footer.php'; ?>
