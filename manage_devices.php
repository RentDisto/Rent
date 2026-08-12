<?php
session_start();
include 'db.php';

if (!isset($_SESSION['role']) || $_SESSION['role'] !== 'admin') {
    header("Location: login.php");
    exit;
}

// ADD device
if (isset($_POST['add_device'])) {
    $name   = mysqli_real_escape_string($conn, $_POST['device_name']);
    $serial = mysqli_real_escape_string($conn, $_POST['serial_number']);
    $type   = mysqli_real_escape_string($conn, $_POST['category']);
    $qty    = (int)$_POST['quantity'];
    $status = mysqli_real_escape_string($conn, $_POST['status']);

    mysqli_query($conn, "INSERT INTO devices (device_name, serial_number, category, quantity, status)
        VALUES ('$name', '$serial', '$type', $qty, '$status')");
    header("Location: manage_devices.php");
    exit;
}

// TOGGLE Available <-> Unavailable
if (isset($_GET['toggle'])) {
    $id = (int)$_GET['toggle'];
    $row = mysqli_fetch_assoc(mysqli_query($conn, "SELECT status FROM devices WHERE device_id=$id"));
    if ($row) {
        $new = (strtolower($row['status']) === 'available') ? 'Unavailable' : 'Available';
        mysqli_query($conn, "UPDATE devices SET status='$new' WHERE device_id=$id");
    }
    header("Location: manage_devices.php");
    exit;
}

// DELETE (remove rentals first)
if (isset($_GET['delete'])) {
    $id = (int)$_GET['delete'];
    mysqli_query($conn, "DELETE FROM rentals WHERE device_id=$id");
    mysqli_query($conn, "DELETE FROM devices WHERE device_id=$id");
    header("Location: manage_devices.php");
    exit;
}

$devices = mysqli_query($conn, "SELECT * FROM devices ORDER BY category, device_name");
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Manage Devices – DeviceRent</title>
    <link rel="stylesheet" href="style.css">
    <style>
        .toggle-btn {
            display: inline-flex;
            align-items: center;
            gap: 6px;
            padding: 6px 12px;
            border-radius: 20px;
            font-size: 0.8rem;
            font-weight: 600;
            text-decoration: none;
            border: none;
            cursor: pointer;
        }
        .toggle-on {
            background: #dcfce7;
            color: #15803d;
        }
        .toggle-on:hover {
            background: #bbf7d0;
        }
        .toggle-off {
            background: #fee2e2;
            color: #b91c1c;
        }
        .toggle-off:hover {
            background: #fecaca;
        }
        .dot {
            width: 8px;
            height: 8px;
            border-radius: 50%;
            display: inline-block;
        }
        .dot-on { background: #22c55e; }
        .dot-off { background: #ef4444; }
    </style>
</head>
<body>

<nav class="navbar">
    <a href="index.php" class="brand">📱 DeviceRent</a>
    <div class="nav-links">
        <a href="index.php">Home</a>
        <a href="view_customers.php">Customers</a>
        <a href="add_customer.php">Add Customer</a>
        <a href="manage_devices.php" class="active">Devices</a>
        <span class="nav-user">Hi, <strong><?php echo htmlspecialchars($_SESSION['username']); ?></strong></span>
        <a href="logout.php">Logout</a>
    </div>
</nav>

<div class="page-header">
    <h1>Manage Devices</h1>
    <p>Add devices with serial numbers. Types: Projector, Printer, Laptop.</p>
</div>

<div class="form-card">
    <h3 style="margin-bottom:16px;">Add New Device</h3>
    <form method="POST">
        <div class="form-group">
            <label>Device Name</label>
            <input type="text" name="device_name" placeholder="e.g. Dell Latitude 5420" required>
        </div>

        <div class="form-row">
            <div class="form-group">
                <label>Serial Number</label>
                <input type="text" name="serial_number" placeholder="e.g. LPT-001" required>
            </div>
            <div class="form-group">
                <label>Type</label>
                <select name="category" required
                    style="width:100%;padding:11px 14px;border:1.5px solid #e2e8f0;border-radius:8px;font-size:0.95rem;background:#f8fafc;">
                    <option value="Laptop">Laptop</option>
                    <option value="Printer">Printer</option>
                    <option value="Projector">Projector</option>
                </select>
            </div>
        </div>

        <div class="form-row">
            <div class="form-group">
                <label>Quantity</label>
                <input type="number" name="quantity" value="1" min="1" required>
            </div>
            <div class="form-group">
                <label>Status</label>
                <select name="status"
                    style="width:100%;padding:11px 14px;border:1.5px solid #e2e8f0;border-radius:8px;font-size:0.95rem;background:#f8fafc;">
                    <option value="Available">Available</option>
                    <option value="Unavailable">Unavailable</option>
                </select>
            </div>
        </div>

        <button type="submit" name="add_device" class="btn btn-block">Add Device</button>
    </form>
</div>

<div class="table-wrapper">
    <div class="table-card">
        <div class="table-toolbar">
            <h2>All Devices</h2>
        </div>

        <?php if (!$devices || mysqli_num_rows($devices) === 0): ?>
            <div class="empty-state">
                <div class="icon">📦</div>
                <p>No devices yet.</p>
            </div>
        <?php else: ?>
        <div style="overflow-x:auto;">
            <table>
                <thead>
                    <tr>
                        <th>ID</th>
                        <th>Name</th>
                        <th>Serial No.</th>
                        <th>Type</th>
                        <th>Qty</th>
                        <th>Status</th>
                        <th>Actions</th>
                    </tr>
                </thead>
                <tbody>
                <?php while ($d = mysqli_fetch_assoc($devices)):
                    $isAvailable = strtolower($d['status']) === 'available';
                ?>
                    <tr>
                        <td><span class="badge">#<?php echo $d['device_id']; ?></span></td>
                        <td><strong><?php echo htmlspecialchars($d['device_name']); ?></strong></td>
                        <td><?php echo htmlspecialchars($d['serial_number'] ?? '—'); ?></td>
                        <td><?php echo htmlspecialchars($d['category']); ?></td>
                        <td><?php echo (int)$d['quantity']; ?></td>
                        <td>
                            <?php if ($isAvailable): ?>
                                <a href="?toggle=<?php echo $d['device_id']; ?>" class="toggle-btn toggle-on"
                                   title="Click to turn OFF">
                                    <span class="dot dot-on"></span> Available
                                </a>
                            <?php else: ?>
                                <a href="?toggle=<?php echo $d['device_id']; ?>" class="toggle-btn toggle-off"
                                   title="Click to turn ON">
                                    <span class="dot dot-off"></span> Unavailable
                                </a>
                            <?php endif; ?>
                        </td>
                        <td>
                            <a href="?delete=<?php echo $d['device_id']; ?>"
                               class="btn" style="padding:6px 12px;font-size:0.8rem;background:#ef4444;"
                               onclick="return confirm('Delete this device and its rental history?')">Delete</a>
                        </td>
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