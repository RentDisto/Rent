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
    <style>
        body {
            background: linear-gradient(160deg, #eef2ff 0%, #f0f9ff 40%, #f8fafc 100%);
            min-height: 100vh;
        }
        .hero { padding: 70px 20px 30px; text-align: center; }
        .hero-badge {
            display: inline-block;
            background: #e0e7ff;
            color: #4338ca;
            font-size: 0.8rem;
            font-weight: 600;
            padding: 6px 14px;
            border-radius: 20px;
            margin-bottom: 18px;
        }
        .hero h1 {
            font-size: 2.6rem;
            font-weight: 800;
            color: #0f172a;
            letter-spacing: -0.03em;
            margin-bottom: 12px;
        }
        .hero p {
            color: #64748b;
            font-size: 1.1rem;
            max-width: 460px;
            margin: 0 auto 48px;
        }
        .card-grid {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(240px, 1fr));
            gap: 24px;
            max-width: 900px;
            margin: 0 auto;
            padding: 0 20px 80px;
        }
        .action-card {
            background: #fff;
            border-radius: 20px;
            padding: 36px 28px 32px;
            text-align: center;
            text-decoration: none;
            color: inherit;
            border: 1px solid rgba(226, 232, 240, 0.8);
            box-shadow: 0 4px 24px rgba(15, 23, 42, 0.04);
            transition: transform 0.25s ease, box-shadow 0.25s ease;
        }
        .action-card:hover {
            transform: translateY(-6px);
            box-shadow: 0 16px 40px rgba(15, 23, 42, 0.1);
        }
        .action-card .icon {
            width: 64px;
            height: 64px;
            border-radius: 18px;
            display: flex;
            align-items: center;
            justify-content: center;
            font-size: 1.7rem;
            margin: 0 auto 20px;
        }
        .icon-blue   { background: linear-gradient(135deg, #dbeafe, #eff6ff); }
        .icon-purple { background: linear-gradient(135deg, #ede9fe, #f5f3ff); }
        .icon-green  { background: linear-gradient(135deg, #d1fae5, #ecfdf5); }
        .icon-indigo { background: linear-gradient(135deg, #e0e7ff, #eef2ff); }
        .action-card h3 {
            font-size: 1.15rem;
            font-weight: 700;
            color: #0f172a;
            margin-bottom: 8px;
        }
        .action-card p {
            font-size: 0.9rem;
            color: #94a3b8;
            margin: 0;
        }
        .navbar {
            background: rgba(255,255,255,0.85);
            backdrop-filter: blur(12px);
        }
        @media (max-width: 640px) {
            .hero h1 { font-size: 1.9rem; }
            .card-grid { grid-template-columns: 1fr; max-width: 360px; }
        }
    </style>
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
            <a href="report.php">Reports</a>
            <span class="nav-user">Hi, <strong><?php echo htmlspecialchars($_SESSION['username']); ?></strong></span>
            <a href="logout.php">Logout</a>
        <?php else: ?>
            <a href="login.php">Login</a>
        <?php endif; ?>
    </div>
</nav>

<section class="hero">
    <div class="hero-badge">Device Rental System</div>
    <h1>Device Rent Management</h1>
    <p>
        <?php if ($isAdmin): ?>
            Welcome back, admin. Manage customers, devices and rentals from one place.
        <?php else: ?>
            Browse rentals easily. Login as admin to manage records.
        <?php endif; ?>
    </p>
</section>

<div class="card-grid">
    <a href="view_customers.php" class="action-card">
        <div class="icon icon-blue">📋</div>
        <h3>View Customers</h3>
        <p>Browse all registered customers and their rental status</p>
    </a>

    <?php if ($isAdmin): ?>
        <a href="add_customer.php" class="action-card">
            <div class="icon icon-purple">➕</div>
            <h3>Add Customer</h3>
            <p>Register a new customer and assign an available device</p>
        </a>
        <a href="manage_devices.php" class="action-card">
            <div class="icon icon-green">💻</div>
            <h3>Manage Devices</h3>
            <p>Add devices with serial numbers and control availability</p>
        </a>
        <a href="report.php" class="action-card">
            <div class="icon icon-indigo">📊</div>
            <h3>Reports</h3>
            <p>View daily, weekly, monthly and yearly rental counts</p>
        </a>
    <?php else: ?>
        <a href="login.php" class="action-card">
            <div class="icon icon-indigo">🔐</div>
            <h3>Admin Login</h3>
            <p>Login to manage customers, devices and rentals</p>
        </a>
    <?php endif; ?>
</div>

</body>
</html>