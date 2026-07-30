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
        <a href="add_customer.php">Add Customer</a>
        <a href="view_customers.php">Customers</a>
    </div>
</nav>

<section class="hero">
    <h1>Device Rent Management</h1>
    <p>Easily manage customers, track rentals, and keep your inventory organized in one place.</p>
</section>

<div class="card-grid">
    <a href="add_customer.php" class="action-card">
        <div class="icon">➕</div>
        <h3>Add Customer</h3>
        <p>Register a new customer and create a rental record</p>
    </a>
    <a href="view_customers.php" class="action-card">
        <div class="icon">📋</div>
        <h3>View Customers</h3>
        <p>Browse all registered customers and their rentals</p>
    </a>
</div>

</body>
</html>