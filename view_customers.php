<?php
session_start();
include 'db.php';
$isAdmin = isset($_SESSION['role']) && $_SESSION['role'] === 'admin';
$today = date('Y-m-d');

// Admin permanently removes selected customers and releases assigned devices.
if ($isAdmin && isset($_POST['delete_customers'])) {
    $customerIds = array_values(array_filter(array_map('intval', $_POST['customer_ids'] ?? [])));

    if ($customerIds) {
        mysqli_begin_transaction($conn);
        $selectStmt = mysqli_prepare($conn, "SELECT device FROM customers WHERE id = ? LIMIT 1");
        $deviceStmt = mysqli_prepare($conn, "UPDATE devices SET status = 'Available' WHERE serial_number = ?");
        $deleteStmt = mysqli_prepare($conn, "DELETE FROM customers WHERE id = ?");
        $success = true;

        foreach ($customerIds as $id) {
            mysqli_stmt_bind_param($selectStmt, 'i', $id);
            $success = mysqli_stmt_execute($selectStmt) && $success;
            $customer = mysqli_fetch_assoc(mysqli_stmt_get_result($selectStmt));

            if ($customer && preg_match_all('/\[(.*?)\]/', $customer['device'] ?? '', $matches)) {
                foreach ($matches[1] as $serialNumber) {
                    $serialNumber = trim($serialNumber);
                    if ($serialNumber !== '') {
                        mysqli_stmt_bind_param($deviceStmt, 's', $serialNumber);
                        $success = mysqli_stmt_execute($deviceStmt) && $success;
                    }
                }
            }

            mysqli_stmt_bind_param($deleteStmt, 'i', $id);
            $success = mysqli_stmt_execute($deleteStmt) && $success;
        }

        mysqli_stmt_close($selectStmt);
        mysqli_stmt_close($deviceStmt);
        mysqli_stmt_close($deleteStmt);

        if ($success) {
            mysqli_commit($conn);
        } else {
            mysqli_rollback($conn);
        }
    }

    header("Location: view_customers.php");
    exit;
}

// Admin marks rental as returned – free ALL devices in that rental
if ($isAdmin && isset($_GET['return_id'])) {
    $id = (int)$_GET['return_id'];
    $row = mysqli_fetch_assoc(mysqli_query($conn, "SELECT * FROM customers WHERE id=$id"));

    if ($row) {
        mysqli_query($conn, "UPDATE customers SET returned=1 WHERE id=$id");

        $deviceText = $row['device'] ?? '';
        // Free every serial found: Name [SN1], Name2 [SN2]
        if (preg_match_all('/\[(.*?)\]/', $deviceText, $matches)) {
            foreach ($matches[1] as $sn) {
                $serial = mysqli_real_escape_string($conn, trim($sn));
                if ($serial !== '') {
                    mysqli_query($conn, "UPDATE devices SET status='Available' WHERE serial_number='$serial'");
                }
            }
        }
    }

    header("Location: view_customers.php");
    exit;
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Customers – DeviceRent</title>
    <link rel="stylesheet" href="style.css">
    <style>
        .table-wrapper {
            max-width: 98%;
            margin: 24px auto 60px;
            padding: 0 12px;
        }
        .table-card { overflow-x: auto; }
        table {
            width: 100%;
            min-width: 0;
            table-layout: auto;
        }
        th, td {
            padding: 10px 12px;
            font-size: 0.85rem;
            white-space: nowrap;
        }
        th { font-size: 0.7rem; }
        td:nth-child(6) {
            white-space: normal;
            max-width: 160px;
            word-break: break-word;
        }

        tr.overdue td { background: #fef2f2 !important; }
        tr.overdue:hover td { background: #fee2e2 !important; }
        tr.active-rental td { background: #f0fdf4 !important; }
        tr.active-rental:hover td { background: #dcfce7 !important; }
        tr.returned-row td { background: #f8fafc !important; color: #94a3b8; }

        .status-badge {
            display: inline-flex;
            align-items: center;
            gap: 5px;
            padding: 4px 10px;
            border-radius: 20px;
            font-size: 0.72rem;
            font-weight: 600;
            white-space: nowrap;
        }
        .status-overdue  { background: #fee2e2; color: #b91c1c; }
        .status-active   { background: #dcfce7; color: #15803d; }
        .status-soon     { background: #fef9c3; color: #a16207; }
        .status-returned { background: #e2e8f0; color: #64748b; }

        .status-dot {
            width: 6px;
            height: 6px;
            border-radius: 50%;
            display: inline-block;
        }
        .dot-red    { background: #ef4444; }
        .dot-green  { background: #22c55e; }
        .dot-yellow { background: #eab308; }
        .dot-gray   { background: #94a3b8; }

        .btn-return {
            display: inline-flex;
            align-items: center;
            gap: 5px;
            padding: 6px 12px;
            border-radius: 8px;
            font-size: 0.75rem;
            font-weight: 600;
            text-decoration: none;
            background: linear-gradient(135deg, #16a34a, #15803d);
            color: #fff;
            box-shadow: 0 2px 6px rgba(22, 163, 74, 0.35);
            transition: transform 0.15s, box-shadow 0.15s;
            white-space: nowrap;
        }
        .btn-return:hover {
            background: linear-gradient(135deg, #15803d, #166534);
            box-shadow: 0 4px 12px rgba(22, 163, 74, 0.4);
            transform: translateY(-1px);
            color: #fff;
        }

        .btn-print {
            display: inline-flex;
            align-items: center;
            gap: 5px;
            padding: 6px 12px;
            border-radius: 8px;
            font-size: 0.75rem;
            font-weight: 600;
            text-decoration: none;
            background: linear-gradient(135deg, #2563eb, #1d4ed8);
            color: #fff;
            box-shadow: 0 2px 6px rgba(37, 99, 235, 0.35);
            transition: transform 0.15s, box-shadow 0.15s;
            white-space: nowrap;
            margin-left: 4px;
        }
        .btn-print:hover {
            background: linear-gradient(135deg, #1d4ed8, #1e40af);
            box-shadow: 0 4px 12px rgba(37, 99, 235, 0.4);
            transform: translateY(-1px);
            color: #fff;
        }

        .selection-cell { width: 48px; text-align: center; }
        .customer-checkbox, #select-all {
            width: 17px;
            height: 17px;
            accent-color: #2563eb;
            cursor: pointer;
        }
        .selection-count {
            color: #64748b;
            font-size: 0.82rem;
            margin-right: auto;
            padding-left: 16px;
        }
        .btn-delete {
            display: inline-flex;
            align-items: center;
            gap: 7px;
            padding: 9px 14px;
            border: none;
            border-radius: 8px;
            font: inherit;
            font-size: 0.82rem;
            font-weight: 600;
            background: #ef4444;
            color: #fff;
            cursor: pointer;
        }
        .btn-delete:hover:not(:disabled) { background: #dc2626; }
        .btn-delete:disabled { background: #cbd5e1; cursor: not-allowed; }
        .customer-row-selected td { background: #eff6ff !important; }
        .modal-backdrop {
            position: fixed;
            inset: 0;
            display: flex;
            align-items: center;
            justify-content: center;
            padding: 20px;
            background: rgba(15, 23, 42, 0.55);
            z-index: 200;
        }
        .modal-backdrop[hidden] { display: none; }
        .confirm-modal {
            width: min(100%, 430px);
            padding: 28px;
            border-radius: 14px;
            background: #fff;
            box-shadow: 0 20px 50px rgba(15, 23, 42, 0.25);
        }
        .confirm-icon {
            display: grid;
            width: 44px;
            height: 44px;
            place-items: center;
            margin-bottom: 16px;
            border-radius: 50%;
            background: #fee2e2;
            color: #dc2626;
            font-size: 1.35rem;
        }
        .confirm-modal h3 { margin-bottom: 8px; font-size: 1.15rem; }
        .confirm-modal p { color: #64748b; font-size: 0.9rem; }
        .modal-actions { display: flex; justify-content: flex-end; gap: 10px; margin-top: 24px; }
        .modal-actions button { border: 0; border-radius: 8px; padding: 10px 15px; font: inherit; font-weight: 600; cursor: pointer; }
        .modal-cancel { background: #f1f5f9; color: #334155; }
        .modal-confirm { background: #ef4444; color: #fff; }

        .returned-label {
            font-size: 0.8rem;
            font-weight: 500;
            color: #94a3b8;
        }

        .action-btns {
            display: flex;
            align-items: center;
            gap: 4px;
            flex-wrap: wrap;
        }
    </style>
</head>
<body>

<nav class="navbar">
    <a href="index.php" class="brand">📱 DeviceRent</a>
    <div class="nav-links">
        <a href="index.php">Home</a>
        <a href="view_customers.php" class="active">Customers</a>
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

<div class="table-wrapper">
    <div class="table-card">
        <div class="table-toolbar">
            <h2>Customer List</h2>
            <?php if ($isAdmin): ?>
                <span class="selection-count" id="selection-count">0 selected</span>
                <button type="submit" form="bulk-delete-form" name="delete_customers" class="btn-delete" id="delete-selected" disabled>
                    🗑 Delete selected
                </button>
            <?php endif; ?>
            <?php if ($isAdmin): ?>
                <a href="add_customer.php" class="btn">+ Add Customer</a>
            <?php endif; ?>
        </div>

        <?php
        $result = mysqli_query($conn, "SELECT * FROM customers ORDER BY id DESC");
        $count  = $result ? mysqli_num_rows($result) : 0;

        if ($count === 0):
        ?>
            <div class="empty-state">
                <div class="icon">📭</div>
                <p>No customers yet.</p>
            </div>
        <?php else: ?>
        <form method="POST" id="bulk-delete-form" onsubmit="return openDeleteConfirmation(event);">
        <input type="hidden" name="delete_customers" value="1">
        <div>
            <table>
                <thead>
                    <tr>
                        <?php if ($isAdmin): ?><th class="selection-cell"><input type="checkbox" id="select-all" title="Select all customers"></th><?php endif; ?>
                        <th>ID</th>
                        <th>Name</th>
                        <th>IC</th>
                        <th>Phone</th>
                        <th>Email</th>
                        <th>Device</th>
                        <th>Rent Date</th>
                        <th>Return Date</th>
                        <th>Status</th>
                        <?php if ($isAdmin): ?><th>Action</th><?php endif; ?>
                    </tr>
                </thead>
                <tbody>
                <?php while ($row = mysqli_fetch_assoc($result)):
                    $returnDate = $row['return_date'] ?? '';
                    $isReturned = !empty($row['returned']);
                    $rowClass = '';
                    $statusHtml = '';

                    if ($isReturned) {
                        $rowClass = 'returned-row';
                        $statusHtml = '<span class="status-badge status-returned"><span class="status-dot dot-gray"></span>Returned</span>';
                    } elseif (!empty($returnDate) && $returnDate < $today) {
                        $rowClass = 'overdue';
                        $statusHtml = '<span class="status-badge status-overdue"><span class="status-dot dot-red"></span>Not Returned</span>';
                    } elseif (!empty($returnDate) && $returnDate === $today) {
                        $rowClass = 'active-rental';
                        $statusHtml = '<span class="status-badge status-soon"><span class="status-dot dot-yellow"></span>Due Today</span>';
                    } else {
                        $rowClass = 'active-rental';
                        $statusHtml = '<span class="status-badge status-active"><span class="status-dot dot-green"></span>On Rent</span>';
                    }
                ?>
                    <tr class="<?php echo $rowClass; ?>">
                        <?php if ($isAdmin): ?><td class="selection-cell"><input type="checkbox" class="customer-checkbox" name="customer_ids[]" value="<?php echo (int)$row['id']; ?>"></td><?php endif; ?>
                        <td><span class="badge">#<?php echo htmlspecialchars($row['id']); ?></span></td>
                        <td><strong><?php echo htmlspecialchars($row['fullname'] ?? ''); ?></strong></td>
                        <td><?php echo htmlspecialchars($row['ic_number'] ?? ''); ?></td>
                        <td><?php echo htmlspecialchars($row['phone'] ?? '—'); ?></td>
                        <td><?php echo htmlspecialchars($row['email'] ?? '—'); ?></td>
                        <td><?php echo htmlspecialchars($row['device'] ?? '—'); ?></td>
                        <td><?php echo htmlspecialchars($row['rent_date'] ?? '—'); ?></td>
                        <td><?php echo htmlspecialchars($row['return_date'] ?? '—'); ?></td>
                        <td><?php echo $statusHtml; ?></td>
                        <?php if ($isAdmin): ?>
                        <td>
                            <div class="action-btns">
                                <?php if (!$isReturned): ?>
                                    <a href="?return_id=<?php echo $row['id']; ?>"
                                       class="btn-return"
                                       onclick="return confirm('Confirm: mark this device as returned?')">
                                        ✓ Mark Returned
                                    </a>
                                <?php else: ?>
                                    <span class="returned-label">✓ Done</span>
                                <?php endif; ?>

                                <a href="print_form.php?id=<?php echo $row['id']; ?>"
                                   class="btn-print"
                                   target="_blank">
                                    🖨 Print
                                </a>

                            </div>
                        </td>
                        <?php endif; ?>
                    </tr>
                <?php endwhile; ?>
                </tbody>
            </table>
        </div>
        </form>
        <?php endif; ?>
    </div>
</div>

<?php if ($isAdmin && $count > 0): ?>
<div class="modal-backdrop" id="delete-modal" hidden>
    <div class="confirm-modal" role="dialog" aria-modal="true" aria-labelledby="delete-modal-title">
        <div class="confirm-icon">!</div>
        <h3 id="delete-modal-title">Delete selected customers?</h3>
        <p id="delete-modal-message">The selected customer records and their assigned devices will be removed.</p>
        <div class="modal-actions">
            <button type="button" class="modal-cancel" id="cancel-delete">Cancel</button>
            <button type="button" class="modal-confirm" id="confirm-delete">Continue</button>
        </div>
    </div>
</div>
<?php endif; ?>

<script>
const checkboxes = document.querySelectorAll('.customer-checkbox');
const selectAll = document.getElementById('select-all');
const deleteButton = document.getElementById('delete-selected');
const selectionCount = document.getElementById('selection-count');
const deleteModal = document.getElementById('delete-modal');
const confirmDelete = document.getElementById('confirm-delete');
const cancelDelete = document.getElementById('cancel-delete');
let deleteForm;

function updateSelection() {
    const selected = [...checkboxes].filter((checkbox) => checkbox.checked);
    const count = selected.length;
    if (selectionCount) selectionCount.textContent = count + (count === 1 ? ' selected' : ' selected');
    if (deleteButton) deleteButton.disabled = count === 0;
    if (selectAll) {
        selectAll.checked = count > 0 && count === checkboxes.length;
        selectAll.indeterminate = count > 0 && count < checkboxes.length;
    }
    selected.forEach((checkbox) => checkbox.closest('tr').classList.add('customer-row-selected'));
    [...checkboxes].filter((checkbox) => !checkbox.checked)
        .forEach((checkbox) => checkbox.closest('tr').classList.remove('customer-row-selected'));
}

checkboxes.forEach((checkbox) => checkbox.addEventListener('change', updateSelection));
if (selectAll) {
    selectAll.addEventListener('change', () => {
        checkboxes.forEach((checkbox) => checkbox.checked = selectAll.checked);
        updateSelection();
    });
}

function openDeleteConfirmation(event) {
    event.preventDefault();
    deleteForm = event.target;
    const count = [...checkboxes].filter((checkbox) => checkbox.checked).length;
    if (!count || !deleteModal) return false;
    document.getElementById('delete-modal-title').textContent = 'Delete ' + count + (count === 1 ? ' customer?' : ' customers?');
    document.getElementById('delete-modal-message').textContent = 'This removes the selected records and releases their assigned devices. This cannot be undone.';
    confirmDelete.textContent = 'Continue';
    deleteModal.hidden = false;
    return false;
}

cancelDelete?.addEventListener('click', () => deleteModal.hidden = true);
confirmDelete?.addEventListener('click', () => {
    if (confirmDelete.textContent === 'Continue') {
        document.getElementById('delete-modal-title').textContent = 'Are you absolutely sure?';
        document.getElementById('delete-modal-message').textContent = 'Click Delete permanently to remove these customers forever.';
        confirmDelete.textContent = 'Delete permanently';
    } else {
        deleteForm.submit();
    }
});
deleteModal?.addEventListener('click', (event) => {
    if (event.target === deleteModal) deleteModal.hidden = true;
});
updateSelection();
</script>

</body>
</html>