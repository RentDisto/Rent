<?php
session_start();
include 'db.php';

if (!isset($_SESSION['role']) || $_SESSION['role'] !== 'admin') {
    header("Location: login.php");
    exit;
}

$available = mysqli_query($conn, "
    SELECT * FROM devices
    WHERE LOWER(status) = 'available'
    ORDER BY FIELD(category, 'Laptop', 'Printer', 'Projector'), device_name
");

$error = '';

if (isset($_POST['save'])) {
    $name   = mysqli_real_escape_string($conn, $_POST['fullname']);
    $ic     = mysqli_real_escape_string($conn, $_POST['ic']);
    $phone  = mysqli_real_escape_string($conn, $_POST['phone']);
    $email  = mysqli_real_escape_string($conn, $_POST['email']);
    $rent   = mysqli_real_escape_string($conn, $_POST['rent']);
    $return = mysqli_real_escape_string($conn, $_POST['return']);

    $device_ids = isset($_POST['device_ids']) ? $_POST['device_ids'] : [];

    if (empty($device_ids)) {
        $error = 'Please select at least one device.';
    } else {
        $labels = [];
        $ok = true;

        foreach ($device_ids as $did) {
            $did = (int)$did;
            $dev = mysqli_fetch_assoc(mysqli_query($conn, "
                SELECT * FROM devices
                WHERE device_id=$did AND LOWER(status)='available'
            "));
            if (!$dev) {
                $ok = false;
                $error = 'One or more selected devices are no longer available.';
                break;
            }
            $labels[] = $dev['device_name'] . ' [' . ($dev['serial_number'] ?? '') . ']';
        }

        if ($ok) {
            $device_label = mysqli_real_escape_string($conn, implode(', ', $labels));

            mysqli_query($conn, "INSERT INTO customers
                (fullname, ic_number, phone, email, device, rent_date, return_date)
                VALUES
                ('$name', '$ic', '$phone', '$email', '$device_label', '$rent', '$return')");

            foreach ($device_ids as $did) {
                $did = (int)$did;
                mysqli_query($conn, "UPDATE devices SET status='Unavailable' WHERE device_id=$did");
            }

            header("Location: view_customers.php");
            exit;
        }
    }
}

$grouped = ['Laptop' => [], 'Printer' => [], 'Projector' => []];
if ($available) {
    while ($d = mysqli_fetch_assoc($available)) {
        $cat = $d['category'];
        if (!isset($grouped[$cat])) $grouped[$cat] = [];
        $grouped[$cat][] = $d;
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
    <style>
        .device-box {
            max-height: 260px;
            overflow-y: auto;
            border: 1.5px solid #e2e8f0;
            border-radius: 8px;
            background: #f8fafc;
            padding: 10px 12px;
        }
        .device-group-title {
            font-size: 0.8rem;
            font-weight: 700;
            color: #2563eb;
            margin: 10px 0 6px;
            text-transform: uppercase;
            letter-spacing: 0.03em;
        }
        .device-group-title:first-child { margin-top: 0; }
        .device-item {
            display: flex;
            align-items: center;
            gap: 10px;
            padding: 8px 10px;
            border-radius: 8px;
            cursor: pointer;
            transition: background 0.15s;
        }
        .device-item:hover { background: #eff6ff; }
        .device-item input {
            width: 16px;
            height: 16px;
            accent-color: #2563eb;
            cursor: pointer;
        }
        .device-item span {
            font-size: 0.9rem;
            color: #1e293b;
        }
        .device-item .sn {
            color: #94a3b8;
            font-size: 0.8rem;
        }
    </style>
</head>
<body>

<nav class="navbar">
    <a href="index.php" class="brand">📱 DeviceRent</a>
    <div class="nav-links">
        <a href="index.php">Home</a>
        <a href="view_customers.php">Customers</a>
        <a href="add_customer.php" class="active">Add Customer</a>
        <a href="manage_devices.php">Devices</a>
        <a href="report.php">Reports</a>
        <span class="nav-user">Hi, <strong><?php echo htmlspecialchars($_SESSION['username']); ?></strong></span>
        <a href="logout.php">Logout</a>
    </div>
</nav>

<div class="page-header">
    <h1>Add Customer / Rental</h1>
    <p>Select one or more available devices, then fill customer details.</p>
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
            <label>Select Available Device(s) — can choose more than one</label>
            <div class="device-box">
                <?php
                $hasAny = false;
                foreach ($grouped as $type => $list):
                    if (count($list) === 0) continue;
                    $hasAny = true;
                ?>
                    <div class="device-group-title"><?php echo htmlspecialchars($type); ?></div>
                    <?php foreach ($list as $d): ?>
                        <label class="device-item">
                            <input type="checkbox" name="device_ids[]" value="<?php echo $d['device_id']; ?>">
                            <span>
                                <?php echo htmlspecialchars($d['device_name']); ?>
                                <?php if (!empty($d['serial_number'])): ?>
                                    <span class="sn">(S/N: <?php echo htmlspecialchars($d['serial_number']); ?>)</span>
                                <?php endif; ?>
                            </span>
                        </label>
                    <?php endforeach; ?>
                <?php endforeach; ?>

                <?php if (!$hasAny): ?>
                    <p style="color:#94a3b8;padding:12px 0;text-align:center;">No available devices</p>
                <?php endif; ?>
            </div>
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