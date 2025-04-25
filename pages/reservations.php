<?php
session_start();
require_once '../config/database.php';
$conn = getDBConnection();

// Handle delete reservation
if (isset($_POST['delete_reservation'])) {
    try {
        $stmt = $conn->prepare("DELETE FROM RESERVATIONS WHERE id = ?");
        $stmt->execute([$_POST['reservation_id']]);
        $_SESSION['success'] = "Reservation deleted successfully!";
    } catch(PDOException $e) {
        $_SESSION['error'] = "Error deleting reservation: " . $e->getMessage();
    }
    header('Location: ' . $_SERVER['PHP_SELF']);
    exit();
}

// Handle edit reservation
if (isset($_POST['edit_reservation'])) {
    try {
        // Check for time overlap (2-hour margin)
        $stmt = $conn->prepare("
            SELECT COUNT(*) FROM RESERVATIONS 
            WHERE table_id = :table_id 
            AND id != :reservation_id
            AND reservation_date BETWEEN 
                DATE_SUB(:reservation_date, INTERVAL 2 HOUR) AND
                DATE_ADD(:reservation_date, INTERVAL 2 HOUR)
        ");
        
        $stmt->execute([
            ':table_id' => $_POST['table_id'],
            ':reservation_date' => $_POST['reservation_date'],
            ':reservation_id' => $_POST['reservation_id']
        ]);
        
        if ($stmt->fetchColumn() > 0) {
            throw new Exception("This time slot conflicts with another reservation (±2 hours)");
        }

        // Update the reservation
        $stmt = $conn->prepare("
            UPDATE RESERVATIONS 
            SET num_guests = :num_guests,
                reservation_date = :reservation_date,
                table_id = :table_id
            WHERE id = :id
        ");
        
        $stmt->execute([
            ':num_guests' => $_POST['num_guests'],
            ':reservation_date' => $_POST['reservation_date'],
            ':table_id' => $_POST['table_id'],
            ':id' => $_POST['reservation_id']
        ]);
        
        $_SESSION['success'] = "Reservation updated successfully!";
    } catch(Exception $e) {
        $_SESSION['error'] = $e->getMessage();
    }
    header('Location: ' . $_SERVER['PHP_SELF']);
    exit();
}

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
if ($_SERVER['REQUEST_METHOD'] === 'POST' && !isset($_POST['delete_reservation']) && !isset($_POST['edit_reservation'])) {
    try {
        // Check for time overlap (2-hour margin)
        $stmt = $conn->prepare("
            SELECT COUNT(*) FROM RESERVATIONS 
            WHERE table_id = :table_id 
            AND reservation_date BETWEEN 
                DATE_SUB(:reservation_date, INTERVAL 2 HOUR) AND
                DATE_ADD(:reservation_date, INTERVAL 2 HOUR)
        ");
        
        $stmt->execute([
            ':table_id' => $_POST['table_id'],
            ':reservation_date' => $_POST['reservation_date']
        ]);
        
        if ($stmt->fetchColumn() > 0) {
            throw new Exception("This time slot conflicts with another reservation (±2 hours)");
        }

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
                            <th>Actions</th>
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
                                <td>
                                    <button type="button" class="btn btn-sm btn-primary" data-bs-toggle="modal" data-bs-target="#editModal<?php echo $reservation['id']; ?>">
                                        Edit
                                    </button>
                                    <form method="POST" style="display: inline;" onsubmit="return confirm('Are you sure you want to delete this reservation?');">
                                        <input type="hidden" name="reservation_id" value="<?php echo $reservation['id']; ?>">
                                        <button type="submit" name="delete_reservation" class="btn btn-sm btn-danger">Delete</button>
                                    </form>

                                    <!-- Edit Modal -->
                                    <div class="modal fade" id="editModal<?php echo $reservation['id']; ?>" tabindex="-1">
                                        <div class="modal-dialog">
                                            <div class="modal-content">
                                                <div class="modal-header">
                                                    <h5 class="modal-title">Edit Reservation</h5>
                                                    <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                                                </div>
                                                <form method="POST">
                                                    <div class="modal-body">
                                                        <input type="hidden" name="reservation_id" value="<?php echo $reservation['id']; ?>">
                                                        
                                                        <div class="mb-3">
                                                            <label class="form-label">Number of Guests</label>
                                                            <input type="number" name="num_guests" class="form-control" required min="1" value="<?php echo $reservation['num_guests']; ?>">
                                                        </div>
                                                        
                                                        <div class="mb-3">
                                                            <label class="form-label">Date & Time</label>
                                                            <input type="datetime-local" name="reservation_date" class="form-control" required value="<?php echo date('Y-m-d\TH:i', strtotime($reservation['reservation_date'])); ?>">
                                                        </div>
                                                        
                                                        <div class="mb-3">
                                                            <label class="form-label">Table</label>
                                                            <select name="table_id" class="form-control" required>
                                                                <?php foreach ($tables as $table): ?>
                                                                    <option value="<?php echo $table['id']; ?>" <?php echo ($table['id'] == $reservation['table_id']) ? 'selected' : ''; ?>>
                                                                        Table <?php echo $table['table_number']; ?> (<?php echo $table['num_seats']; ?> seats)
                                                                    </option>
                                                                <?php endforeach; ?>
                                                            </select>
                                                        </div>
                                                    </div>
                                                    <div class="modal-footer">
                                                        <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Close</button>
                                                        <button type="submit" name="edit_reservation" class="btn btn-primary">Save changes</button>
                                                    </div>
                                                </form>
                                            </div>
                                        </div>
                                    </div>
                                </td>
                            </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            </div>
        </div>
    </div>
</div>

<?php require_once '../includes/footer.php'; ?>
