<?php
session_start();
include 'db.php';

if (!isset($_SESSION['role']) || $_SESSION['role'] !== 'admin') {
    header("Location: login.php");
    exit;
}

$available = mysqli_query($conn,
    "SELECT * FROM devices WHERE status='Available' OR status='available' ORDER BY category, device_name");

$error = '';

if (isset($_POST['save'])) {
    $name      = mysqli_real_escape_string($conn, $_POST['fullname']);
    $ic        = mysqli_real_escape_string($conn, $_POST['ic']);
    $phone     = mysqli_real_escape_string($conn, $_POST['phone']);
    $email     = mysqli_real_escape_string($conn, $_POST['email']);
    $device_id = (int)$_POST['device_id'];
    $rent      = mysqli_real_escape_string($conn, $_POST['rent']);
    $return    = mysqli_real_escape_string($conn, $_POST['return']);

    $dev = mysqli_fetch_assoc(mysqli_query($conn,
        "SELECT * FROM devices WHERE device_id=$device_id AND (status='Available' OR status='available')"));

    if (!$dev) {
        $error = 'Selected device is not available.';
    } else {
        $device_label = $dev['device_name'] . ' [' . ($dev['serial_number'] ?? '') . ']';

        mysqli_query($conn, "INSERT INTO customers
            (fullname, ic_number, phone, email, device, rent_date, return_date)
            VALUES
            ('$name', '$ic', '$phone', '$email', '$device_label', '$rent', '$return')");

        mysqli_query($conn, "UPDATE devices SET status='Unavailable' WHERE device_id=$device_id");

        header("Location: view_customers.php");
        exit;
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Add Customer – DeviceRent</title>
    <link rel="stylesheet" href="style.css">
</head>
<body>

<nav class="navbar">
    <a href="index.php" class="brand">📱 DeviceRent</a>
    <div class="nav-links">
        <a href="index.php">Home</a>
        <a href="view_customers.php">Customers</a>
        <a href="add_customer.php" class="active">Add Customer</a>
        <a href="manage_devices.php">Devices</a>
        <span class="nav-user">Hi, <strong><?php echo htmlspecialchars($_SESSION['username']); ?></strong></span>
        <a href="logout.php">Logout</a>
    </div>
</nav>

<div class="page-header">
    <h1>Add Customer / Rental</h1>
    <p>Choose an available device, then fill customer details.</p>
</div>

<div class="form-card">
    <?php if ($error): ?>
        <div class="alert alert-error"><?php echo htmlspecialchars($error); ?></div>
    <?php endif; ?>

    <form method="POST">
        <div class="form-group">
            <label>Full Name</label>
            <input type="text" name="fullname" placeholder="e.g. Ali Ahmad" required>
        </div>

        <div class="form-row">
            <div class="form-group">
                <label>IC Number</label>
                <input type="text" name="ic" placeholder="e.g. 010101-10-1234" required>
            </div>
            <div class="form-group">
                <label>Phone</label>
                <input type="tel" name="phone" placeholder="e.g. 0123456789">
            </div>
        </div>

        <div class="form-group">
            <label>Email</label>
            <input type="email" name="email" placeholder="e.g. ali@gmail.com">
        </div>

        <div class="form-group">
            <label>Select Available Device</label>
            <select name="device_id" required
                style="width:100%;padding:11px 14px;border:1.5px solid #e2e8f0;border-radius:8px;font-size:0.95rem;background:#f8fafc;">
                <option value="">-- Choose device --</option>
                <?php if ($available && mysqli_num_rows($available) > 0): ?>
                    <?php while ($d = mysqli_fetch_assoc($available)): ?>
                        <option value="<?php echo $d['device_id']; ?>">
                            <?php
                            echo htmlspecialchars($d['category'] . ' – ' . $d['device_name']);
                            if (!empty($d['serial_number'])) {
                                echo ' (S/N: ' . htmlspecialchars($d['serial_number']) . ')';
                            }
                            ?>
                        </option>
                    <?php endwhile; ?>
                <?php else: ?>
                    <option value="" disabled>No available devices</option>
                <?php endif; ?>
            </select>
        </div>

        <div class="form-row">
            <div class="form-group">
                <label>Rent Date</label>
                <input type="date" name="rent">
            </div>
            <div class="form-group">
                <label>Return Date</label>
                <input type="date" name="return">
            </div>
        </div>

        <div class="form-actions">
            <a href="index.php" class="btn btn-secondary">Cancel</a>
            <button type="submit" name="save" class="btn">Save Customer</button>
        </div>
    </form>
</div>

</body>
</html>