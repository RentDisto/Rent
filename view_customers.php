<?php
include 'db.php';
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Customers – DeviceRent</title>
    <link rel="stylesheet" href="style.css">
</head>
<body>

<nav class="navbar">
    <a href="index.php" class="brand">📱 DeviceRent</a>
    <div class="nav-links">
        <a href="index.php">Home</a>
        <a href="add_customer.php">Add Customer</a>
        <a href="view_customers.php" class="active">Customers</a>
    </div>
</nav>

<div class="table-wrapper">
    <div class="table-card">
        <div class="table-toolbar">
            <h2>Customer List</h2>
            <a href="add_customer.php" class="btn">+ Add Customer</a>
        </div>

        <?php
        $result = mysqli_query($conn, "SELECT * FROM customers ORDER BY id DESC");
        $count  = $result ? mysqli_num_rows($result) : 0;

        if ($count === 0):
        ?>
            <div class="empty-state">
                <div class="icon">📭</div>
                <p>No customers yet. Add your first one!</p>
                <br>
                <a href="add_customer.php" class="btn">Add Customer</a>
            </div>
        <?php else: ?>
        <div style="overflow-x: auto;">
            <table>
                <thead>
                    <tr>
                        <th>ID</th>
                        <th>Name</th>
                        <th>IC</th>
                        <th>Phone</th>
                        <th>Email</th>
                        <th>Device</th>
                        <th>Rent Date</th>
                        <th>Return Date</th>
                        <th>Price</th>
                    </tr>
                </thead>
                <tbody>
                <?php while ($row = mysqli_fetch_assoc($result)): ?>
                    <tr>
                        <td><span class="badge">#<?php echo htmlspecialchars($row['id']); ?></span></td>
                        <td><strong><?php echo htmlspecialchars($row['fullname'] ?? ''); ?></strong></td>
                        <td><?php echo htmlspecialchars($row['ic_number'] ?? ''); ?></td>
                        <td><?php echo htmlspecialchars($row['phone'] ?? '—'); ?></td>
                        <td><?php echo htmlspecialchars($row['email'] ?? '—'); ?></td>
                        <td><?php echo htmlspecialchars($row['device'] ?? '—'); ?></td>
                        <td><?php echo htmlspecialchars($row['rent_date'] ?? '—'); ?></td>
                        <td><?php echo htmlspecialchars($row['return_date'] ?? '—'); ?></td>
                        <td class="price">RM <?php echo number_format((float)($row['price'] ?? 0), 2); ?></td>
                    </tr>
                <?php endwhile; ?>
                </tbody>
            </table>
        </div>
        <?php endif; ?>
    </div>
</div>

</body>
</html>