<?php
session_start();
include 'db.php';

if (!isset($_SESSION['role']) || $_SESSION['role'] !== 'admin') {
    header("Location: login.php");
    exit;
}

$tab = $_GET['tab'] ?? 'monthly';
$allowed = ['daily', 'weekly', 'monthly', 'yearly'];
if (!in_array($tab, $allowed)) $tab = 'monthly';

function classifyDevice($text) {
    $t = strtolower($text);
    if (strpos($t, 'laptop') !== false || strpos($t, 'notebook') !== false || strpos($t, 'dell') !== false || strpos($t, 'hp') !== false || strpos($t, 'macbook') !== false || strpos($t, 'inspiron') !== false || strpos($t, 'latitude') !== false || strpos($t, 'pro 14') !== false) {
        return 'laptop';
    }
    if (strpos($t, 'printer') !== false || strpos($t, 'pencetak') !== false || strpos($t, 'pixma') !== false) {
        return 'printer';
    }
    if (strpos($t, 'projector') !== false || strpos($t, 'projektor') !== false || strpos($t, 'epson') !== false) {
        return 'projector';
    }
    return 'other';
}

function countByType($deviceText) {
    $out = ['laptop' => 0, 'printer' => 0, 'projector' => 0, 'other' => 0, 'total' => 0];
    if (empty($deviceText)) return $out;

    $parts = preg_split('/\s*,\s*/', $deviceText);
    foreach ($parts as $p) {
        $p = trim($p);
        if ($p === '') continue;
        $type = classifyDevice($p);
        $out[$type]++;
        $out['total']++;
    }
    if ($out['total'] === 0 && trim($deviceText) !== '') {
        $type = classifyDevice($deviceText);
        $out[$type] = 1;
        $out['total'] = 1;
    }
    return $out;
}

function emptyCounts() {
    return ['laptop' => 0, 'printer' => 0, 'projector' => 0, 'other' => 0, 'total' => 0];
}

$allRows = [];
$res = mysqli_query($conn, "SELECT rent_date, device FROM customers WHERE rent_date IS NOT NULL AND rent_date != ''");
if ($res) {
    while ($row = mysqli_fetch_assoc($res)) {
        $allRows[] = $row;
    }
}

function buildBuckets($allRows, $tab) {
    $buckets = [];
    foreach ($allRows as $row) {
        $ts = strtotime($row['rent_date']);
        if (!$ts) continue;
        $counts = countByType($row['device'] ?? '');

        switch ($tab) {
            case 'daily':
                $key = date('Y-m-d', $ts);
                break;
            case 'weekly':
                $key = date('o', $ts) . '-W' . date('W', $ts);
                break;
            case 'yearly':
                $key = date('Y', $ts);
                break;
            default:
                $key = date('Y-m', $ts);
                break;
        }

        if (!isset($buckets[$key])) {
            $buckets[$key] = emptyCounts();
        }
        foreach (['laptop', 'printer', 'projector', 'other', 'total'] as $k) {
            $buckets[$key][$k] += $counts[$k];
        }
    }
    krsort($buckets);
    $limits = ['daily' => 31, 'weekly' => 20, 'monthly' => 24, 'yearly' => 20];
    return array_slice($buckets, 0, $limits[$tab], true);
}

function formatPeriod($tab, $key) {
    if ($tab === 'weekly' && preg_match('/(\d{4})-W(\d+)/', $key, $wm)) {
        return 'Week ' . (int)$wm[2] . ', ' . $wm[1];
    }
    if ($tab === 'monthly') {
        return date('F Y', strtotime($key . '-01'));
    }
    return $key;
}

$buckets = buildBuckets($allRows, $tab);

$grand = emptyCounts();
foreach ($allRows as $r) {
    $c = countByType($r['device'] ?? '');
    foreach ($grand as $k => $v) {
        $grand[$k] += $c[$k];
    }
}

$printData = [];
foreach (['daily', 'weekly', 'monthly', 'yearly'] as $t) {
    $printData[$t] = buildBuckets($allRows, $t);
}

$titles = [
    'daily'   => 'Daily Device Rentals',
    'weekly'  => 'Weekly Device Rentals',
    'monthly' => 'Monthly Device Rentals',
    'yearly'  => 'Yearly Device Rentals'
];
$labels = [
    'daily'   => 'Date',
    'weekly'  => 'Week',
    'monthly' => 'Month',
    'yearly'  => 'Year'
];
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Reports – DeviceRent</title>
    <link rel="stylesheet" href="style.css">
    <style>
        .report-wrap {
            max-width: 960px;
            margin: 32px auto 60px;
            padding: 0 16px;
        }
        .report-header {
            display: flex;
            justify-content: space-between;
            align-items: flex-start;
            flex-wrap: wrap;
            gap: 12px;
            margin-bottom: 24px;
        }
        .report-header h1 {
            font-size: 1.6rem;
            font-weight: 700;
            margin-bottom: 4px;
        }
        .report-header p {
            color: #64748b;
            font-size: 0.95rem;
        }
        .btn-print-report {
            padding: 10px 20px;
            background: linear-gradient(135deg, #2563eb, #1d4ed8);
            color: #fff;
            border: none;
            border-radius: 10px;
            font-weight: 600;
            font-size: 0.9rem;
            cursor: pointer;
            box-shadow: 0 2px 8px rgba(37,99,235,0.35);
        }
        .btn-print-report:hover {
            background: linear-gradient(135deg, #1d4ed8, #1e40af);
        }

        .stat-cards {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(130px, 1fr));
            gap: 14px;
            margin-bottom: 28px;
        }
        .stat-card {
            background: #fff;
            border-radius: 14px;
            padding: 18px 14px;
            border: 1px solid #e2e8f0;
            text-align: center;
            box-shadow: 0 2px 12px rgba(0,0,0,0.04);
        }
        .stat-card .num { font-size: 1.6rem; font-weight: 800; }
        .stat-card .lbl { font-size: 0.75rem; color: #94a3b8; margin-top: 4px; }
        .stat-card.total .num { color: #2563eb; }
        .stat-card.laptop .num { color: #7c3aed; }
        .stat-card.printer .num { color: #0891b2; }
        .stat-card.projector .num { color: #d97706; }
        .stat-card.other .num { color: #64748b; }

        .tabs {
            display: flex;
            gap: 6px;
            background: #fff;
            padding: 6px;
            border-radius: 12px;
            border: 1px solid #e2e8f0;
            margin-bottom: 20px;
            flex-wrap: wrap;
        }
        .tabs a {
            flex: 1;
            text-align: center;
            padding: 10px 16px;
            border-radius: 8px;
            text-decoration: none;
            font-weight: 600;
            font-size: 0.9rem;
            color: #64748b;
            min-width: 90px;
        }
        .tabs a:hover { background: #f1f5f9; color: #2563eb; }
        .tabs a.active { background: #2563eb; color: #fff; }

        .report-table-card {
            background: #fff;
            border-radius: 14px;
            border: 1px solid #e2e8f0;
            box-shadow: 0 2px 12px rgba(0,0,0,0.04);
            overflow: hidden;
        }
        .report-table-card h2 {
            font-size: 1.05rem;
            font-weight: 600;
            padding: 16px 20px;
            border-bottom: 1px solid #e2e8f0;
        }
        .report-table-card table {
            width: 100%;
            border-collapse: collapse;
        }
        .report-table-card th {
            background: #f8fafc;
            color: #64748b;
            font-size: 0.7rem;
            font-weight: 600;
            text-transform: uppercase;
            letter-spacing: 0.04em;
            padding: 12px 14px;
            text-align: left;
            border-bottom: 1px solid #e2e8f0;
        }
        .report-table-card td {
            padding: 12px 14px;
            border-bottom: 1px solid #f1f5f9;
            font-size: 0.88rem;
        }
        .report-table-card tr:last-child td { border-bottom: none; }
        .report-table-card tr:hover td { background: #f8fafc; }

        .badge {
            display: inline-block;
            padding: 3px 10px;
            border-radius: 20px;
            font-weight: 700;
            font-size: 0.8rem;
        }
        .badge-total { background: #dbeafe; color: #1d4ed8; }
        .badge-laptop { background: #ede9fe; color: #6d28d9; }
        .badge-printer { background: #cffafe; color: #0e7490; }
        .badge-projector { background: #fef3c7; color: #b45309; }
        .badge-other { background: #f1f5f9; color: #475569; }

        .empty-report {
            text-align: center;
            padding: 48px 20px;
            color: #94a3b8;
        }

        .modal-overlay {
            display: none;
            position: fixed;
            inset: 0;
            background: rgba(15,23,42,0.5);
            z-index: 1000;
            align-items: center;
            justify-content: center;
        }
        .modal-overlay.show { display: flex; }
        .modal-box {
            background: #fff;
            border-radius: 16px;
            padding: 28px;
            width: 90%;
            max-width: 400px;
            box-shadow: 0 20px 50px rgba(0,0,0,0.2);
        }
        .modal-box h3 { font-size: 1.15rem; margin-bottom: 6px; }
        .modal-box p { color: #64748b; font-size: 0.9rem; margin-bottom: 18px; }
        .check-list {
            display: flex;
            flex-direction: column;
            gap: 10px;
            margin-bottom: 20px;
        }
        .check-list label {
            display: flex;
            align-items: center;
            gap: 10px;
            padding: 10px 12px;
            border: 1.5px solid #e2e8f0;
            border-radius: 10px;
            cursor: pointer;
            font-weight: 500;
        }
        .check-list label:hover {
            border-color: #93c5fd;
            background: #f0f9ff;
        }
        .check-list input {
            width: 18px;
            height: 18px;
            accent-color: #2563eb;
        }
        .modal-actions {
            display: flex;
            gap: 10px;
            justify-content: flex-end;
        }
        .modal-actions button {
            padding: 10px 18px;
            border-radius: 8px;
            font-weight: 600;
            font-size: 0.9rem;
            cursor: pointer;
            border: none;
        }
        .btn-cancel { background: #f1f5f9; color: #475569; }
        .btn-go-print { background: #2563eb; color: #fff; }

        #print-area { display: none; }

        @media print {
            body * { visibility: hidden; }
            #print-area, #print-area * { visibility: visible; }
            #print-area {
                display: block !important;
                position: absolute;
                left: 0;
                top: 0;
                width: 100%;
                padding: 8mm 10mm;
                box-sizing: border-box;
            }
            .print-header {
                text-align: center;
                margin-bottom: 14px;
            }
            .print-header h1 {
                font-size: 16px;
                margin-bottom: 3px;
            }
            .print-header p {
                font-size: 10px;
                color: #555;
            }
            .print-summary {
                display: flex;
                gap: 8px;
                justify-content: center;
                margin-bottom: 18px;
                flex-wrap: wrap;
            }
            .print-summary .box {
                border: 1px solid #94a3b8;
                border-radius: 6px;
                padding: 8px 14px;
                text-align: center;
                min-width: 70px;
            }
            .print-summary .box .n {
                font-size: 16px;
                font-weight: 800;
            }
            .print-summary .box .l {
                font-size: 8px;
                color: #64748b;
                text-transform: uppercase;
            }
            .print-section {
                margin-bottom: 18px;
                page-break-inside: avoid;
                width: 100%;
            }
            .print-section h2 {
                font-size: 12px;
                border-bottom: 2px solid #1e3a5f;
                padding-bottom: 4px;
                margin-bottom: 6px;
                color: #1e3a5f;
            }
            .print-section table {
                width: 100% !important;
                max-width: 100% !important;
                border-collapse: collapse;
                font-size: 9px;
                table-layout: fixed;
            }
            .print-section th,
            .print-section td {
                border: 1px solid #64748b;
                padding: 5px 4px;
                text-align: center;
                word-wrap: break-word;
                overflow-wrap: break-word;
            }
            .print-section th {
                background: #f1f5f9 !important;
                font-weight: 700;
                -webkit-print-color-adjust: exact;
                print-color-adjust: exact;
            }
            .print-section td:first-child,
            .print-section th:first-child {
                text-align: left;
                width: 28%;
            }
            .print-section .col-total {
                font-weight: 700;
                background: #f8fafc !important;
                -webkit-print-color-adjust: exact;
                print-color-adjust: exact;
            }
            @page {
                size: A4 portrait;
                margin: 10mm;
            }
        }
    </style>
</head>
<body>

<nav class="navbar">
    <a href="index.php" class="brand">📱 DeviceRent</a>
    <div class="nav-links">
        <a href="index.php">Home</a>
        <a href="view_customers.php">Customers</a>
        <a href="add_customer.php">Add Customer</a>
        <a href="manage_devices.php">Devices</a>
        <a href="report.php" class="active">Reports</a>
        <span class="nav-user">Hi, <strong><?php echo htmlspecialchars($_SESSION['username']); ?></strong></span>
        <a href="logout.php">Logout</a>
    </div>
</nav>

<div class="report-wrap">
    <div class="report-header">
        <div>
            <h1>Rental Reports</h1>
            <p>Device counts by type — Laptop, Printer, Projector &amp; more.</p>
        </div>
        <button type="button" class="btn-print-report" onclick="openPrintModal()">🖨 Print Report</button>
    </div>

    <div class="stat-cards">
        <div class="stat-card total">
            <div class="num"><?php echo $grand['total']; ?></div>
            <div class="lbl">Total Devices</div>
        </div>
        <div class="stat-card laptop">
            <div class="num"><?php echo $grand['laptop']; ?></div>
            <div class="lbl">Laptop</div>
        </div>
        <div class="stat-card printer">
            <div class="num"><?php echo $grand['printer']; ?></div>
            <div class="lbl">Printer</div>
        </div>
        <div class="stat-card projector">
            <div class="num"><?php echo $grand['projector']; ?></div>
            <div class="lbl">Projector</div>
        </div>
        <div class="stat-card other">
            <div class="num"><?php echo $grand['other']; ?></div>
            <div class="lbl">Other</div>
        </div>
    </div>

    <div class="tabs">
        <a href="?tab=daily"   class="<?php echo $tab === 'daily'   ? 'active' : ''; ?>">Daily</a>
        <a href="?tab=weekly"  class="<?php echo $tab === 'weekly'  ? 'active' : ''; ?>">Weekly</a>
        <a href="?tab=monthly" class="<?php echo $tab === 'monthly' ? 'active' : ''; ?>">Monthly</a>
        <a href="?tab=yearly"  class="<?php echo $tab === 'yearly'  ? 'active' : ''; ?>">Yearly</a>
    </div>

    <div class="report-table-card">
        <h2><?php echo htmlspecialchars($titles[$tab]); ?></h2>
        <?php if (count($buckets) === 0): ?>
            <div class="empty-report">
                <div style="font-size:2rem;margin-bottom:8px;">📊</div>
                <p>No rental data yet.</p>
            </div>
        <?php else: ?>
            <div style="overflow-x:auto;">
            <table>
                <thead>
                    <tr>
                        <th><?php echo htmlspecialchars($labels[$tab]); ?></th>
                        <th>Laptop</th>
                        <th>Printer</th>
                        <th>Projector</th>
                        <th>Other</th>
                        <th>Total</th>
                    </tr>
                </thead>
                <tbody>
                <?php foreach ($buckets as $key => $b): ?>
                    <tr>
                        <td><strong><?php echo htmlspecialchars(formatPeriod($tab, $key)); ?></strong></td>
                        <td><span class="badge badge-laptop"><?php echo (int)$b['laptop']; ?></span></td>
                        <td><span class="badge badge-printer"><?php echo (int)$b['printer']; ?></span></td>
                        <td><span class="badge badge-projector"><?php echo (int)$b['projector']; ?></span></td>
                        <td><span class="badge badge-other"><?php echo (int)$b['other']; ?></span></td>
                        <td><span class="badge badge-total"><?php echo (int)$b['total']; ?></span></td>
                    </tr>
                <?php endforeach; ?>
                </tbody>
            </table>
            </div>
        <?php endif; ?>
    </div>
</div>

<!-- Print modal -->
<div class="modal-overlay" id="printModal">
    <div class="modal-box">
        <h3>Print Report</h3>
        <p>Choose which periods to include:</p>
        <div class="check-list">
            <label><input type="checkbox" value="daily" checked> Daily</label>
            <label><input type="checkbox" value="weekly" checked> Weekly</label>
            <label><input type="checkbox" value="monthly" checked> Monthly</label>
            <label><input type="checkbox" value="yearly" checked> Yearly</label>
        </div>
        <div class="modal-actions">
            <button type="button" class="btn-cancel" onclick="closePrintModal()">Cancel</button>
            <button type="button" class="btn-go-print" onclick="doPrint()">Print Selected</button>
        </div>
    </div>
</div>

<!-- Print content -->
<div id="print-area">
    <div class="print-header">
        <h1>Device Rental Report</h1>
        <p>Printed: <?php echo date('d/m/Y H:i'); ?></p>
    </div>

    <div class="print-summary">
        <div class="box"><div class="n"><?php echo $grand['total']; ?></div><div class="l">Total</div></div>
        <div class="box"><div class="n"><?php echo $grand['laptop']; ?></div><div class="l">Laptop</div></div>
        <div class="box"><div class="n"><?php echo $grand['printer']; ?></div><div class="l">Printer</div></div>
        <div class="box"><div class="n"><?php echo $grand['projector']; ?></div><div class="l">Projector</div></div>
        <div class="box"><div class="n"><?php echo $grand['other']; ?></div><div class="l">Other</div></div>
    </div>

    <?php foreach (['daily', 'weekly', 'monthly', 'yearly'] as $t):
        $data = $printData[$t];
    ?>
    <div class="print-section" id="print-<?php echo $t; ?>" data-period="<?php echo $t; ?>">
        <h2><?php echo htmlspecialchars($titles[$t]); ?></h2>
        <?php if (count($data) === 0): ?>
            <p style="font-size:11px;color:#888;">No data.</p>
        <?php else: ?>
        <table>
            <thead>
                <tr>
                    <th><?php echo htmlspecialchars($labels[$t]); ?></th>
                    <th>Laptop</th>
                    <th>Printer</th>
                    <th>Projector</th>
                    <th>Other</th>
                    <th>Total</th>
                </tr>
            </thead>
            <tbody>
            <?php foreach ($data as $key => $b): ?>
                <tr>
                    <td><?php echo htmlspecialchars(formatPeriod($t, $key)); ?></td>
                    <td><?php echo (int)$b['laptop']; ?></td>
                    <td><?php echo (int)$b['printer']; ?></td>
                    <td><?php echo (int)$b['projector']; ?></td>
                    <td><?php echo (int)$b['other']; ?></td>
                    <td class="col-total"><?php echo (int)$b['total']; ?></td>
                </tr>
            <?php endforeach; ?>
            </tbody>
        </table>
        <?php endif; ?>
    </div>
    <?php endforeach; ?>
</div>

<script>
function openPrintModal() {
    document.getElementById('printModal').classList.add('show');
}
function closePrintModal() {
    document.getElementById('printModal').classList.remove('show');
}
function doPrint() {
    var selected = [];
    document.querySelectorAll('#printModal input[type=checkbox]').forEach(function(c) {
        if (c.checked) selected.push(c.value);
    });
    if (selected.length === 0) {
        alert('Please select at least one period.');
        return;
    }
    document.querySelectorAll('.print-section').forEach(function(sec) {
        sec.style.display = selected.indexOf(sec.getAttribute('data-period')) !== -1 ? 'block' : 'none';
    });
    closePrintModal();
    window.print();
}
document.getElementById('printModal').addEventListener('click', function(e) {
    if (e.target === this) closePrintModal();
});
</script>

</body>
</html>