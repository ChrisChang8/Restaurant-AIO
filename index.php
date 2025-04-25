<?php require_once 'includes/header.php'; ?>

<!-- Hero Section -->
<div class="hero-section position-relative bg-primary text-white py-5 mb-5">
    <div class="container">
        <div class="row align-items-center">
            <div class="col-lg-6">
                <h1 class="display-4 fw-bold mb-4">Welcome to Restaurant Manager</h1>
                <p class="lead mb-4">Streamline your restaurant operations with our comprehensive management system.</p>
                <a href="pages/reservations.php" class="btn btn-light btn-lg px-4">Get Started</a>
            </div>
            <div class="col-lg-6 d-none d-lg-block text-center">
                <i class="bi bi-building fs-1 opacity-50"></i>
            </div>
        </div>
    </div>
    <div class="hero-shape position-absolute bottom-0 start-0 w-100 overflow-hidden">
        <svg viewBox="0 0 2880 48" fill="none" xmlns="http://www.w3.org/2000/svg">
            <path d="M0 48h2880V0h-720C1442.5 52 720 0 720 0H0v48z" fill="#ffffff"></path>
        </svg>
    </div>
</div>

<!-- Features Section -->
<div class="container mb-5">
    <div class="row g-4">
        <?php
        $features = [
            [
                'title' => 'Reservations',
                'desc' => 'Manage customer reservations and table assignments efficiently.',
                'icon' => 'calendar-check',
                'link' => 'pages/reservations.php',
                'btn'  => 'Manage Reservations'
            ],
            [
                'title' => 'Menu Management',
                'desc' => 'Update menu items, prices, and track inventory levels.',
                'icon' => 'menu-button-wide',
                'link' => 'pages/menu.php',
                'btn'  => 'View Menu'
            ],
            [
                'title' => 'Order Processing',
                'desc' => 'Create and manage customer orders with ease.',
                'icon' => 'cart-check',
                'link' => 'pages/orders.php',
                'btn'  => 'Process Orders'
            ],
            [
                'title' => 'Employee Management',
                'desc' => 'Manage staff schedules and assignments.',
                'icon' => 'people',
                'link' => 'pages/employees.php',
                'btn'  => 'Manage Employees'
            ],
            [
                'title' => 'Admin Dashboard',
                'desc' => 'Access administrative controls and reports.',
                'icon' => 'speedometer2',
                'link' => 'pages/admin.php',
                'btn'  => 'Admin Panel'
            ]
        ];

        foreach ($features as $f): ?>
            <div class="col-md-4">
                <div class="card h-100 border-0 shadow-sm hover-shadow transition">
                    <div class="card-body text-center p-4">
                        <div class="feature-icon bg-primary bg-opacity-10 text-primary rounded-circle mb-3 mx-auto p-3">
                            <i class="bi bi-<?php echo $f['icon']; ?> fs-4"></i>
                        </div>
                        <h5 class="card-title mb-3"><?php echo $f['title']; ?></h5>
                        <p class="card-text text-muted"><?php echo $f['desc']; ?></p>
                        <a href="<?php echo $f['link']; ?>" class="btn btn-primary mt-3">
                            <i class="bi bi-arrow-right me-2"></i><?php echo $f['btn']; ?>
                        </a>
                    </div>
                </div>
            </div>
        <?php endforeach; ?>
    </div>
</div>

<style>
.hero-section {
    background: linear-gradient(135deg, var(--bs-primary) 0%, #2980b9 100%);
}
.feature-icon {
    width: 64px;
    height: 64px;
    display: flex;
    align-items: center;
    justify-content: center;
}
.hover-shadow {
    transition: all 0.3s ease;
}
.hover-shadow:hover {
    transform: translateY(-5px);
}
.transition {
    transition: all 0.3s ease;
}
</style>

<?php require_once 'includes/footer.php'; ?>
