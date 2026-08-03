<?php
session_start();
$isAdmin = isset($_SESSION['role']) && $_SESSION['role'] === 'admin';
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Device Rent System</title>
    <link rel="stylesheet" href="style.css">
</head>
<body>

<nav class="navbar">
    <a href="index.php" class="brand">📱 DeviceRent</a>
    <div class="nav-links">
        <a href="index.php" class="active">Home</a>
        <a href="view_customers.php">Customers</a>

        <?php if ($isAdmin): ?>
            <a href="add_customer.php">Add Customer</a>
            <a href="manage_devices.php">Devices</a>
            <span class="nav-user">Hi, <strong><?php echo htmlspecialchars($_SESSION['username']); ?></strong></span>
            <a href="logout.php">Logout</a>
        <?php else: ?>
            <a href="login.php">Login</a>
        <?php endif; ?>
    </div>
</nav>

<section class="hero">
    <h1>Device Rent Management</h1>
    <p>
        <?php if ($isAdmin): ?>
            Welcome back, admin. Manage customers, devices and rentals from here.
        <?php else: ?>
            Browse available rentals. Login as admin to manage records.
        <?php endif; ?>
    </p>
</section>

<div class="card-grid">
    <a href="view_customers.php" class="action-card">
        <div class="icon">📋</div>
        <h3>View Customers</h3>
        <p>Browse all registered customers and their rentals</p>
    </a>

    <?php if ($isAdmin): ?>
        <a href="add_customer.php" class="action-card">
            <div class="icon">➕</div>
            <h3>Add Customer</h3>
            <p>Register a new customer and create a rental record</p>
        </a>
        <a href="manage_devices.php" class="action-card">
            <div class="icon">💻</div>
            <h3>Manage Devices</h3>
            <p>Add devices with serial numbers and set availability</p>
        </a>
    <?php else: ?>
        <a href="login.php" class="action-card">
            <div class="icon">🔐</div>
            <h3>Admin Login</h3>
            <p>Login to manage customers and devices</p>
        </a>
    <?php endif; ?>
</div>

</body>
</html>