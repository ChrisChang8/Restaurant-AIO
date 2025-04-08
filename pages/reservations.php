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
        
        $stmt = $conn->prepare("
            INSERT INTO RESERVATIONS (customer_id, num_guests, reservation_date, table_id)
            VALUES (:customer_id, :num_guests, :reservation_date, :table_id)
        ");
        
        $stmt->execute([
            ':customer_id' => $customerId,
            ':num_guests' => $_POST['num_guests'],
            ':reservation_date' => $_POST['reservation_date'],
            ':table_id' => $_POST['table_id']
        ]);
        
        $_SESSION['success'] = "Reservation created successfully!";
        header('Location: ' . $_SERVER['PHP_SELF']);
        exit();
    } catch(PDOException $e) {
        $_SESSION['error'] = "Error creating reservation: " . $e->getMessage();
        header('Location: ' . $_SERVER['PHP_SELF']);
        exit();
    }
}

// Get available tables
$tables = $conn->query("SELECT * FROM TABLES WHERE seat_is_occupied = FALSE")->fetchAll();

// Get existing reservations
$reservations = $conn->query("
    SELECT R.*, C.first_name, C.last_name, C.phone, T.table_number
    FROM RESERVATIONS R
    JOIN CUSTOMERS C ON R.customer_id = C.id
    JOIN TABLES T ON R.table_id = T.id
    ORDER BY R.reservation_date DESC
")->fetchAll();

require_once '../includes/header.php';
?>

<div class="container">
    <h2 class="mb-4">Make a Reservation</h2>
    
    <?php if (isset($success)): ?>
        <div class="alert alert-success"><?php echo $success; ?></div>
    <?php endif; ?>
    
    <?php if (isset($error)): ?>
        <div class="alert alert-danger"><?php echo $error; ?></div>
    <?php endif; ?>

    <div class="row">
        <div class="col-md-6">
            <form method="POST" class="reservation-form" onsubmit="return validateForm('reservationForm')" id="reservationForm">
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
                    <label class="form-label">Number of Guests</label>
                    <input type="number" name="num_guests" class="form-control" required min="1">
                </div>
                
                <div class="mb-3">
                    <label class="form-label">Date & Time</label>
                    <input type="datetime-local" name="reservation_date" class="form-control" required>
                </div>
                
                <div class="mb-3">
                    <label class="form-label">Table</label>
                    <select name="table_id" class="form-control" required id="tableSelect">
                        <option value="">Select a table</option>
                        <?php foreach ($tables as $table): ?>
                            <option value="<?php echo $table['id']; ?>" data-seats="<?php echo $table['num_seats']; ?>">
                                Table <?php echo $table['table_number']; ?> (<?php echo $table['num_seats']; ?> seats)
                            </option>
                        <?php endforeach; ?>
                    </select>
                    <small class="form-text text-muted">Only tables with sufficient seats for your party will be available</small>
                </div>
                
                <button type="submit" class="btn btn-primary">Make Reservation</button>
            </form>
        </div>
        
        <div class="col-md-6">
            <h3>Current Reservations</h3>
            <div class="table-responsive">
                <table class="table table-striped">
                    <thead>
                        <tr>
                            <th>Name</th>
                            <th>Table</th>
                            <th>Guests</th>
                            <th>Date & Time</th>
                            <th>Phone</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($reservations as $reservation): ?>
                            <tr>
                                <td><?php echo htmlspecialchars($reservation['first_name'] . ' ' . $reservation['last_name']); ?></td>
                                <td><?php echo htmlspecialchars($reservation['table_number']); ?></td>
                                <td><?php echo htmlspecialchars($reservation['num_guests']); ?></td>
                                <td><?php echo htmlspecialchars(date('M j, Y g:i A', strtotime($reservation['reservation_date']))); ?></td>
                                <td><?php echo htmlspecialchars($reservation['phone']); ?></td>
                            </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            </div>
        </div>
    </div>
</div>

<?php require_once '../includes/footer.php'; ?>
