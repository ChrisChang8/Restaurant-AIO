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
        if (isset($_POST['action'])) {
            if ($_POST['action'] === 'add') {
                $stmt = $conn->prepare("
                    INSERT INTO MENU_ITEM (item_name, price, stock_availability)
                    VALUES (:item_name, :price, :stock_availability)
                ");
                
                $stmt->execute([
                    ':item_name' => $_POST['item_name'],
                    ':price' => $_POST['price'],
                    ':stock_availability' => isset($_POST['stock_availability']) ? 1 : 0
                ]);
                
                $_SESSION['success'] = "Menu item added successfully!";
                header('Location: ' . $_SERVER['PHP_SELF']);
                exit();
            } elseif ($_POST['action'] === 'update') {
                $stmt = $conn->prepare("
                    UPDATE MENU_ITEM 
                    SET stock_availability = :stock_availability
                    WHERE id = :id
                ");
                
                $stmt->execute([
                    ':id' => $_POST['item_id'],
                    ':stock_availability' => isset($_POST['stock_availability']) ? 1 : 0
                ]);
                
                $_SESSION['success'] = "Menu item updated successfully!";
                header('Location: ' . $_SERVER['PHP_SELF']);
                exit();
            } elseif ($_POST['action'] === 'delete') {
                $stmt = $conn->prepare("DELETE FROM MENU_ITEM WHERE id = :id");
                $stmt->execute([':id' => $_POST['item_id']]);
                $_SESSION['success'] = "Menu item deleted successfully!";
                header('Location: ' . $_SERVER['PHP_SELF']);
                exit();
            }
        }
    } catch(PDOException $e) {
        $_SESSION['error'] = "Error managing menu item: " . $e->getMessage();
        header('Location: ' . $_SERVER['PHP_SELF']);
        exit();
    }
}

// Get all menu items
$menu_items = $conn->query("SELECT * FROM MENU_ITEM ORDER BY item_name")->fetchAll();

require_once '../includes/header.php';
?>

<div class="container">
    <h2 class="mb-4">Menu Management</h2>
    
    <?php if (isset($success)): ?>
        <div class="alert alert-success"><?php echo $success; ?></div>
    <?php endif; ?>
    
    <?php if (isset($error)): ?>
        <div class="alert alert-danger"><?php echo $error; ?></div>
    <?php endif; ?>

    <div class="row">
        <!-- Add New Menu Item Form -->
        <div class="col-md-4">
            <div class="card">
                <div class="card-header">
                    <h3 class="card-title">Add New Menu Item</h3>
                </div>
                <div class="card-body">
                    <form method="POST" onsubmit="return validateForm('addMenuForm')" id="addMenuForm">
                        <input type="hidden" name="action" value="add">
                        
                        <div class="mb-3">
                            <label class="form-label">Item Name</label>
                            <input type="text" name="item_name" class="form-control" required>
                        </div>
                        
                        <div class="mb-3">
                            <label class="form-label">Price ($)</label>
                            <input type="number" name="price" class="form-control" step="0.01" min="0" required>
                        </div>
                        
                        <div class="mb-3 form-check">
                            <input type="checkbox" name="stock_availability" class="form-check-input" id="stockCheck" checked>
                            <label class="form-check-label" for="stockCheck">In Stock</label>
                        </div>
                        
                        <button type="submit" class="btn btn-primary">Add Item</button>
                    </form>
                </div>
            </div>
        </div>

        <!-- Menu Items List -->
        <div class="col-md-8">
            <div class="table-responsive">
                <table class="table table-striped">
                    <thead>
                        <tr>
                            <th>Item Name</th>
                            <th>Price</th>
                            <th>Status</th>
                            <th>Actions</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($menu_items as $item): ?>
                            <tr>
                                <td><?php echo htmlspecialchars($item['item_name']); ?></td>
                                <td>$<?php echo number_format($item['price'], 2); ?></td>
                                <td>
                                    <form method="POST" class="d-inline">
                                        <input type="hidden" name="action" value="update">
                                        <input type="hidden" name="item_id" value="<?php echo $item['id']; ?>">
                                        <div class="form-check form-switch">
                                            <input type="checkbox" name="stock_availability" 
                                                class="form-check-input stock-toggle" 
                                                <?php echo $item['stock_availability'] ? 'checked' : ''; ?>
                                                onchange="this.form.submit()"
                                                id="stock_<?php echo $item['id']; ?>">
                                            <label class="form-check-label" for="stock_<?php echo $item['id']; ?>">
                                                <?php echo $item['stock_availability'] ? 'In Stock' : 'Out of Stock'; ?>
                                            </label>
                                        </div>
                                    </form>
                                </td>
                                <td>
                                    <form method="POST" class="d-inline" onsubmit="return confirm('Are you sure you want to delete this item?')">
                                        <input type="hidden" name="action" value="delete">
                                        <input type="hidden" name="item_id" value="<?php echo $item['id']; ?>">
                                        <button type="submit" class="btn btn-danger btn-sm">Delete</button>
                                    </form>
                                </td>
                            </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            </div>
        </div>
    </div>

    <!-- Menu Display Section -->
    <div class="row mt-5">
        <div class="col-12">
            <h3>Menu Display</h3>
            <p class="text-muted">This is how customers will see the menu:</p>
        </div>
        <?php foreach ($menu_items as $item): ?>
            <?php if ($item['stock_availability']): ?>
                <div class="col-md-4 mb-4">
                    <div class="card menu-item">
                        <div class="card-body">
                            <h5 class="card-title"><?php echo htmlspecialchars($item['item_name']); ?></h5>
                            <p class="card-text">
                                <strong class="text-primary">$<?php echo number_format($item['price'], 2); ?></strong>
                            </p>
                            <span class="badge bg-success">Available</span>
                        </div>
                    </div>
                </div>
            <?php endif; ?>
        <?php endforeach; ?>
    </div>
</div>

<script>
// Add event listeners for stock toggles
document.addEventListener('DOMContentLoaded', function() {
    const stockToggles = document.querySelectorAll('.stock-toggle');
    stockToggles.forEach(toggle => {
        toggle.addEventListener('change', function() {
            const label = this.nextElementSibling;
            label.textContent = this.checked ? 'In Stock' : 'Out of Stock';
        });
    });
});
</script>

<?php require_once '../includes/footer.php'; ?>
