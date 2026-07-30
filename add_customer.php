<?php
include 'db.php';

if (isset($_POST['save'])) {
    $name   = $_POST['fullname'];
    $ic     = $_POST['ic'];
    $phone  = $_POST['phone'];
    $email  = $_POST['email'];
    $device = $_POST['device'];
    $rent   = $_POST['rent'];
    $return = $_POST['return'];
    $price  = $_POST['price'];

    mysqli_query($conn, "INSERT INTO customers
        (fullname, ic_number, phone, email, device, rent_date, return_date, price)
        VALUES
        ('$name', '$ic', '$phone', '$email', '$device', '$rent', '$return', '$price')");

    header("Location: view_customers.php");
    exit;
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
        <a href="add_customer.php" class="active">Add Customer</a>
        <a href="view_customers.php">Customers</a>
    </div>
</nav>

<div class="page-header">
    <h1>Add Customer</h1>
    <p>Fill in the details below to register a new rental.</p>
</div>

<div class="form-card">
    <form method="POST">
        <div class="form-group">
            <label for="fullname">Full Name</label>
            <input type="text" id="fullname" name="fullname" placeholder="e.g. Ali Ahmad" required>
        </div>

        <div class="form-row">
            <div class="form-group">
                <label for="ic">IC Number</label>
                <input type="text" id="ic" name="ic" placeholder="e.g. 010101-10-1234" required>
            </div>
            <div class="form-group">
                <label for="phone">Phone</label>
                <input type="tel" id="phone" name="phone" placeholder="e.g. 0123456789">
            </div>
        </div>

        <div class="form-group">
            <label for="email">Email</label>
            <input type="email" id="email" name="email" placeholder="e.g. ali@gmail.com">
        </div>

        <div class="form-group">
            <label for="device">Device Name</label>
            <input type="text" id="device" name="device" placeholder="e.g. Dell Latitude 5420">
        </div>

        <div class="form-row">
            <div class="form-group">
                <label for="rent">Rent Date</label>
                <input type="date" id="rent" name="rent">
            </div>
            <div class="form-group">
                <label for="return">Return Date</label>
                <input type="date" id="return" name="return">
            </div>
        </div>

        <div class="form-group">
            <label for="price">Rental Price (RM)</label>
            <input type="number" step="0.01" id="price" name="price" placeholder="0.00">
        </div>

        <div class="form-actions">
            <a href="index.php" class="btn btn-secondary">Cancel</a>
            <button type="submit" name="save" class="btn">Save Customer</button>
        </div>
    </form>
</div>

</body>
</html>