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

// Handle form submissions
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    try {
        if ($_POST['action'] === 'add_employee') {
            $stmt = $conn->prepare("
                INSERT INTO EMPLOYEES (first_name, last_name, role, phone, email)
                VALUES (:first_name, :last_name, :role, :phone, :email)
            ");
            
            $stmt->execute([
                ':first_name' => $_POST['first_name'],
                ':last_name' => $_POST['last_name'],
                ':role' => $_POST['role'],
                ':phone' => $_POST['phone'],
                ':email' => $_POST['email']
            ]);
            
            $_SESSION['success'] = "Employee added successfully!";
            header('Location: ' . $_SERVER['PHP_SELF']);
            exit();
            
        } elseif ($_POST['action'] === 'add_schedule') {
            $stmt = $conn->prepare("
                INSERT INTO SCHEDULES (employee_id, shift_date, start_time, end_time)
                VALUES (:employee_id, :shift_date, :start_time, :end_time)
            ");
            
            $stmt->execute([
                ':employee_id' => $_POST['employee_id'],
                ':shift_date' => $_POST['shift_date'],
                ':start_time' => $_POST['start_time'],
                ':end_time' => $_POST['end_time']
            ]);
            
            $_SESSION['success'] = "Shift scheduled successfully!";
            header('Location: ' . $_SERVER['PHP_SELF']);
            exit();
            
        } elseif ($_POST['action'] === 'delete_schedule') {
            $stmt = $conn->prepare("DELETE FROM SCHEDULES WHERE id = :id");
            $stmt->execute([':id' => $_POST['schedule_id']]);
            
            $_SESSION['success'] = "Shift deleted successfully!";
            header('Location: ' . $_SERVER['PHP_SELF']);
            exit();
        } elseif ($_POST['action'] === 'reschedule') {
            $stmt = $conn->prepare("
                UPDATE SCHEDULES 
                SET shift_date = :shift_date,
                    start_time = :start_time,
                    end_time = :end_time
                WHERE id = :schedule_id
            ");
            
            $stmt->execute([
                ':schedule_id' => $_POST['schedule_id'],
                ':shift_date' => $_POST['shift_date'],
                ':start_time' => $_POST['start_time'],
                ':end_time' => $_POST['end_time']
            ]);
            
            $_SESSION['success'] = "Shift rescheduled successfully!";
            header('Location: ' . $_SERVER['PHP_SELF']);
            exit();
        }
        
    } catch(PDOException $e) {
        $_SESSION['error'] = "Error: " . $e->getMessage();
        header('Location: ' . $_SERVER['PHP_SELF']);
        exit();
    }
}

// Get all employees
$employees = $conn->query("
    SELECT * FROM EMPLOYEES 
    ORDER BY first_name, last_name
")->fetchAll();

// Get upcoming schedules for the next 7 days
$schedules = $conn->query("
    SELECT 
        S.id,
        S.shift_date,
        S.start_time,
        S.end_time,
        E.first_name,
        E.last_name,
        E.role
    FROM SCHEDULES S
    JOIN EMPLOYEES E ON S.employee_id = E.id
    WHERE S.shift_date BETWEEN CURDATE() AND DATE_ADD(CURDATE(), INTERVAL 7 DAY)
    ORDER BY S.shift_date, S.start_time
")->fetchAll();

require_once '../includes/header.php';
?>

<div class="container">
    <h2 class="mb-4">Employee Management</h2>
    
    <?php if (isset($success)): ?>
        <div class="alert alert-success"><?php echo $success; ?></div>
    <?php endif; ?>
    
    <?php if (isset($error)): ?>
        <div class="alert alert-danger"><?php echo $error; ?></div>
    <?php endif; ?>
    
    <div class="row">
        <!-- Add Employee Form -->
        <div class="col-md-4">
            <div class="card">
                <div class="card-header">
                    <h3 class="card-title">Add New Employee</h3>
                </div>
                <div class="card-body">
                    <form method="POST" onsubmit="return validateForm('employeeForm')" id="employeeForm">
                        <input type="hidden" name="action" value="add_employee">
                        
                        <div class="mb-3">
                            <label class="form-label">First Name</label>
                            <input type="text" name="first_name" class="form-control" required>
                        </div>
                        
                        <div class="mb-3">
                            <label class="form-label">Last Name</label>
                            <input type="text" name="last_name" class="form-control" required>
                        </div>
                        
                        <div class="mb-3">
                            <label class="form-label">Role</label>
                            <select name="role" class="form-control" required>
                                <option value="">Select role</option>
                                <option value="Manager">Manager</option>
                                <option value="Chef">Chef</option>
                                <option value="Waiter">Waiter</option>
                                <option value="Cashier">Cashier</option>
                            </select>
                        </div>
                        
                        <div class="mb-3">
                            <label class="form-label">Phone</label>
                            <input type="tel" name="phone" class="form-control" required>
                        </div>
                        
                        <div class="mb-3">
                            <label class="form-label">Email</label>
                            <input type="email" name="email" class="form-control" required>
                        </div>
                        
                        <button type="submit" class="btn btn-primary">Add Employee</button>
                    </form>
                </div>
            </div>
        </div>
        
        <!-- Schedule Shift Form -->
        <div class="col-md-4">
            <div class="card">
                <div class="card-header">
                    <h3 class="card-title">Schedule Shift</h3>
                </div>
                <div class="card-body">
                    <form method="POST" onsubmit="return validateForm('scheduleForm')" id="scheduleForm">
                        <input type="hidden" name="action" value="add_schedule">
                        
                        <div class="mb-3">
                            <label class="form-label">Employee</label>
                            <select name="employee_id" class="form-control" required>
                                <option value="">Select employee</option>
                                <?php foreach ($employees as $employee): ?>
                                    <option value="<?php echo $employee['id']; ?>">
                                        <?php echo htmlspecialchars($employee['first_name'] . ' ' . $employee['last_name'] . 
                                              ' (' . $employee['role'] . ')'); ?>
                                    </option>
                                <?php endforeach; ?>
                            </select>
                        </div>
                        
                        <div class="mb-3">
                            <label class="form-label">Date</label>
                            <input type="date" 
                                   name="shift_date" 
                                   class="form-control" 
                                   min="<?php echo date('Y-m-d'); ?>"
                                   required>
                        </div>
                        
                        <div class="mb-3">
                            <label class="form-label">Start Time</label>
                            <input type="time" name="start_time" class="form-control" required>
                        </div>
                        
                        <div class="mb-3">
                            <label class="form-label">End Time</label>
                            <input type="time" name="end_time" class="form-control" required>
                        </div>
                        
                        <button type="submit" class="btn btn-primary">Schedule Shift</button>
                    </form>
                </div>
            </div>
        </div>
        
        <!-- Weekly Schedule -->
        <div class="col-md-4">
            <div class="card">
                <div class="card-header">
                    <h3 class="card-title">Upcoming Shifts (Next 7 Days)</h3>
                </div>
                <div class="card-body">
                    <?php
                    $currentDate = null;
                    foreach ($schedules as $schedule):
                        $scheduleDate = date('Y-m-d', strtotime($schedule['shift_date']));
                        if ($currentDate !== $scheduleDate):
                            if ($currentDate !== null) echo "</div>";
                            $currentDate = $scheduleDate;
                    ?>
                        <div class="mb-3">
                            <h5><?php echo date('l, F j', strtotime($schedule['shift_date'])); ?></h5>
                    <?php endif; ?>
                                                        <div class="d-flex justify-content-between align-items-center border-bottom py-2">
                                <div>
                                    <strong><?php echo htmlspecialchars($schedule['first_name'] . ' ' . $schedule['last_name']); ?></strong>
                                    <br>
                                    <small class="text-muted">
                                        <?php echo $schedule['role']; ?> •
                                        <?php echo date('g:i A', strtotime($schedule['start_time'])); ?> - 
                                        <?php echo date('g:i A', strtotime($schedule['end_time'])); ?>
                                    </small>
                                </div>
                                <div>
                                    <button class="btn btn-sm btn-primary me-1" 
                                            onclick="openRescheduleModal('<?php echo $schedule['id']; ?>', 
                                                '<?php echo $schedule['shift_date']; ?>', 
                                                '<?php echo $schedule['start_time']; ?>', 
                                                '<?php echo $schedule['end_time']; ?>')">
                                        <i class="bi bi-calendar"></i>
                                    </button>
                                    <form method="POST" class="d-inline" onsubmit="return confirm('Are you sure you want to delete this shift?');">
                                        <input type="hidden" name="action" value="delete_schedule">
                                        <input type="hidden" name="schedule_id" value="<?php echo $schedule['id']; ?>">
                                        <button type="submit" class="btn btn-sm btn-danger">
                                            <i class="bi bi-trash"></i>
                                        </button>
                                    </form>
                                </div>
                            </div>
                    <?php endforeach; ?>
                    <?php if (empty($schedules)): ?>
                        <p class="text-muted">No upcoming shifts scheduled.</p>
                    <?php endif; ?>
                </div>
            </div>
        </div>
    </div>
    
    <!-- Employee List -->
    <div class="row mt-4">
        <div class="col-12">
            <div class="card">
                <div class="card-header">
                    <h3 class="card-title">All Employees</h3>
                </div>
                <div class="card-body">
                    <div class="table-responsive">
                        <table class="table table-striped">
                            <thead>
                                <tr>
                                    <th>Name</th>
                                    <th>Role</th>
                                    <th>Phone</th>
                                    <th>Email</th>
                                    <th>Actions</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php foreach ($employees as $employee): ?>
                                    <tr>
                                        <td><?php echo htmlspecialchars($employee['first_name'] . ' ' . $employee['last_name']); ?></td>
                                        <td><?php echo htmlspecialchars($employee['role']); ?></td>
                                        <td><?php echo htmlspecialchars($employee['phone']); ?></td>
                                        <td><?php echo htmlspecialchars($employee['email']); ?></td>
                                        <td>
                                            <button class="btn btn-sm btn-primary" 
                                                    onclick="document.querySelector('select[name=employee_id]').value='<?php echo $employee['id']; ?>';document.querySelector('#scheduleForm').scrollIntoView({behavior: 'smooth'});">
                                                Schedule Shift
                                            </button>
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
</div>

<!-- Reschedule Modal -->
<div class="modal fade" id="rescheduleModal" tabindex="-1">
    <div class="modal-dialog">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title">Reschedule Shift</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
            </div>
            <div class="modal-body">
                <form method="POST" id="rescheduleForm" onsubmit="return validateForm('rescheduleForm')">
                    <input type="hidden" name="action" value="reschedule">
                    <input type="hidden" name="schedule_id" id="reschedule_id">
                    
                    <div class="mb-3">
                        <label class="form-label">Date</label>
                        <input type="date" 
                               name="shift_date" 
                               id="reschedule_date"
                               class="form-control" 
                               min="<?php echo date('Y-m-d'); ?>"
                               required>
                    </div>
                    
                    <div class="mb-3">
                        <label class="form-label">Start Time</label>
                        <input type="time" name="start_time" id="reschedule_start" class="form-control" required>
                    </div>
                    
                    <div class="mb-3">
                        <label class="form-label">End Time</label>
                        <input type="time" name="end_time" id="reschedule_end" class="form-control" required>
                    </div>
                    
                    <button type="submit" class="btn btn-primary">Save Changes</button>
                </form>
            </div>
        </div>
    </div>
</div>

<script>
// Add form validation
document.addEventListener('DOMContentLoaded', function() {
    // Validate schedule times for both forms
    ['scheduleForm', 'rescheduleForm'].forEach(formId => {
        document.getElementById(formId).addEventListener('submit', function(e) {
            const startTime = this.querySelector('input[name="start_time"]').value;
            const endTime = this.querySelector('input[name="end_time"]').value;
            
            if (startTime >= endTime) {
                e.preventDefault();
                alert('End time must be after start time');
            }
        });
    });
});

// Function to open reschedule modal
function openRescheduleModal(id, date, start, end) {
    document.getElementById('reschedule_id').value = id;
    document.getElementById('reschedule_date').value = date;
    document.getElementById('reschedule_start').value = start;
    document.getElementById('reschedule_end').value = end;
    
    new bootstrap.Modal(document.getElementById('rescheduleModal')).show();
}
</script>

<?php require_once '../includes/footer.php'; ?>
