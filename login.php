<?php
session_start();
include 'db.php';

if (isset($_SESSION['role']) && $_SESSION['role'] === 'admin') {
    header("Location: index.php");
    exit;
}

$error = '';

if (isset($_POST['login'])) {
    $username = mysqli_real_escape_string($conn, $_POST['username']);
    $password = $_POST['password']; // plain text from form

    $result = mysqli_query($conn, "SELECT * FROM admins WHERE username='$username' LIMIT 1");

    if ($result && mysqli_num_rows($result) === 1) {
        $user = mysqli_fetch_assoc($result);

        // Works with password_hash() from phpMyAdmin
        if (password_verify($password, $user['password'])) {
            $_SESSION['user_id']  = $user['id'];
            $_SESSION['username'] = $user['username'];
            $_SESSION['role']     = $user['role'];
            header("Location: index.php");
            exit;
        }
    }
    $error = 'Invalid username or password.';
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Login – DeviceRent</title>
    <link rel="stylesheet" href="style.css">
</head>
<body>

<nav class="navbar">
    <a href="index.php" class="brand">📱 DeviceRent</a>
    <div class="nav-links">
        <a href="index.php">Home</a>
        <a href="view_customers.php">Customers</a>
        <a href="login.php" class="active">Login</a>
    </div>
</nav>

<div class="page-header" style="text-align:center;">
    <h1>Admin Login</h1>
    <p>Only admin can manage customers and rentals.</p>
</div>

<div class="form-card login-card">
    <?php if ($error): ?>
        <div class="alert alert-error"><?php echo htmlspecialchars($error); ?></div>
    <?php endif; ?>

    <form method="POST">
        <div class="form-group">
            <label for="username">Username</label>
            <input type="text" id="username" name="username" placeholder="admin" required>
        </div>
        <div class="form-group">
            <label for="password">Password</label>
            <input type="password" id="password" name="password" placeholder="••••••••" required>
        </div>
        <button type="submit" name="login" class="btn btn-block">Login</button>
    </form>
</div>

</body>
</html>