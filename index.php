<?php require_once 'includes/header.php'; ?>

<div class="jumbotron text-center bg-light py-5">
    <h1 class="display-4">Welcome to Restaurant Manager</h1>
    <p class="lead">Streamline your restaurant operations with our comprehensive management system.</p>
</div>

<div class="row mt-5">
    <div class="col-md-4">
        <div class="card">
            <div class="card-body">
                <h5 class="card-title">Reservations</h5>
                <p class="card-text">Manage customer reservations and table assignments efficiently.</p>
                <a href="/restaurant/pages/reservations.php" class="btn btn-primary">Manage Reservations</a>
            </div>
        </div>
    </div>
    <div class="col-md-4">
        <div class="card">
            <div class="card-body">
                <h5 class="card-title">Menu Management</h5>
                <p class="card-text">Update menu items, prices, and track inventory levels.</p>
                <a href="/restaurant/pages/menu.php" class="btn btn-primary">View Menu</a>
            </div>
        </div>
    </div>
    <div class="col-md-4">
        <div class="card">
            <div class="card-body">
                <h5 class="card-title">Order Processing</h5>
                <p class="card-text">Create and manage customer orders with ease.</p>
                <a href="/restaurant/pages/orders.php" class="btn btn-primary">Process Orders</a>
            </div>
        </div>
    </div>
</div>

<div class="row mt-4">
    <div class="col-md-6">
        <div class="card">
            <div class="card-body">
                <h5 class="card-title">Employee Management</h5>
                <p class="card-text">Manage staff schedules and assignments.</p>
                <a href="/restaurant/pages/employees.php" class="btn btn-primary">Manage Employees</a>
            </div>
        </div>
    </div>
    <div class="col-md-6">
        <div class="card">
            <div class="card-body">
                <h5 class="card-title">Admin Dashboard</h5>
                <p class="card-text">Access administrative controls and reports.</p>
                <a href="/restaurant/pages/admin.php" class="btn btn-primary">Admin Panel</a>
            </div>
        </div>
    </div>
</div>

<?php require_once 'includes/footer.php'; ?>
