<?php
session_start();
include 'db.php';

if (!isset($_SESSION['role']) || $_SESSION['role'] !== 'admin') {
    header("Location: login.php");
    exit;
}

$customer = null;
if (isset($_GET['id'])) {
    $id = (int)$_GET['id'];
    $res = mysqli_query($conn, "SELECT * FROM customers WHERE id=$id LIMIT 1");
    if ($res) $customer = mysqli_fetch_assoc($res);
}

$nama     = $customer['fullname'] ?? '';
$nokp     = $customer['ic_number'] ?? '';
$notel    = $customer['phone'] ?? '';
$device   = $customer['device'] ?? '';
$rentDate = $customer['rent_date'] ?? '';
$retDate  = $customer['return_date'] ?? '';

$isLaptop    = (stripos($device, 'laptop') !== false || stripos($device, 'notebook') !== false || stripos($device, 'dell') !== false || stripos($device, 'hp') !== false || stripos($device, 'komputer') !== false);
$isPrinter   = (stripos($device, 'printer') !== false || stripos($device, 'pencetak') !== false);
$isProjector = (stripos($device, 'projector') !== false || stripos($device, 'projektor') !== false || stripos($device, 'epson') !== false);

// Parse devices: "Name [SN], Name2 [SN2]"
$rawList = [];
if (!empty($device)) {
    $parts = preg_split('/\s*,\s*/', $device);
    foreach ($parts as $p) {
        $mName = trim($p);
        $mSerial = '';
        if (preg_match('/^(.*?)\s*\[(.*?)\]\s*$/', $mName, $mm)) {
            $mName = trim($mm[1]);
            $mSerial = trim($mm[2]);
        }
        if ($mName !== '' || $mSerial !== '') {
            $rawList[] = ['model' => $mName, 'serial' => $mSerial];
        }
    }
}

// Group same model → 1 row, serials joined with " / "
$grouped = [];
foreach ($rawList as $item) {
    $key = $item['model'] !== '' ? $item['model'] : '__empty__';
    if (!isset($grouped[$key])) {
        $grouped[$key] = ['model' => $item['model'], 'serials' => []];
    }
    if ($item['serial'] !== '') {
        $grouped[$key]['serials'][] = $item['serial'];
    }
}

$deviceList = [];
foreach ($grouped as $g) {
    $deviceList[] = [
        'model'  => $g['model'],
        'serial' => implode(' / ', $g['serials'])
    ];
}

while (count($deviceList) < 5) {
    $deviceList[] = ['model' => '', 'serial' => ''];
}
$deviceList = array_slice($deviceList, 0, 5);
?>
<!DOCTYPE html>
<html lang="ms">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Borang Pinjaman Peralatan ICT</title>
    <style>
        * { box-sizing: border-box; margin: 0; padding: 0; }
        body {
            font-family: Arial, Helvetica, sans-serif;
            font-size: 11px;
            color: #000;
            background: #e5e7eb;
            padding: 20px;
        }
        .toolbar {
            max-width: 210mm;
            margin: 0 auto 16px;
            display: flex;
            gap: 10px;
            justify-content: flex-end;
        }
        .toolbar button, .toolbar a {
            padding: 10px 20px;
            border: none;
            border-radius: 8px;
            font-size: 14px;
            font-weight: 600;
            cursor: pointer;
            text-decoration: none;
            color: #fff;
        }
        .btn-print { background: #2563eb; }
        .btn-print:hover { background: #1d4ed8; }
        .btn-back { background: #64748b; }
        .page {
            width: 210mm;
            min-height: 297mm;
            margin: 0 auto;
            background: #fff;
            padding: 10mm 12mm;
            box-shadow: 0 4px 20px rgba(0,0,0,0.15);
        }
        .header {
            display: flex;
            align-items: flex-start;
            gap: 12px;
            border-bottom: 2px solid #000;
            padding-bottom: 8px;
            margin-bottom: 8px;
        }
        .header img { width: 70px; height: 70px; object-fit: contain; }
        .header-center { flex: 1; text-align: center; }
        .header-center .agency {
            font-size: 11px;
            font-weight: 700;
            line-height: 1.35;
            text-transform: uppercase;
        }
        .header-center .form-title {
            font-size: 14px;
            font-weight: 800;
            margin-top: 4px;
            text-transform: uppercase;
            letter-spacing: 0.5px;
        }
        .header-right {
            border: 1.5px solid #000;
            padding: 4px 8px;
            font-size: 10px;
            min-width: 110px;
            text-align: center;
        }
        .header-right .code { font-weight: 700; }
        .header-right .bil { margin-top: 4px; }
        .section { border: 1.5px solid #000; margin-bottom: 6px; }
        .section-title {
            background: #1e3a5f;
            color: #fff;
            font-weight: 700;
            font-size: 11px;
            padding: 3px 8px;
            text-transform: uppercase;
        }
        .section-body { padding: 6px 8px; }
        .row {
            display: flex;
            gap: 12px;
            margin-bottom: 5px;
            align-items: baseline;
        }
        .field {
            display: flex;
            align-items: baseline;
            gap: 4px;
            flex: 1;
        }
        .field label { white-space: nowrap; font-weight: 600; }
        .field .line {
            flex: 1;
            border-bottom: 1px solid #000;
            min-height: 16px;
            padding: 0 4px;
            font-size: 11px;
        }
        table.form-table {
            width: 100%;
            border-collapse: collapse;
            font-size: 10px;
        }
        table.form-table th,
        table.form-table td {
            border: 1px solid #000;
            padding: 4px 6px;
            vertical-align: middle;
        }
        table.form-table th {
            background: #f1f5f9;
            font-weight: 700;
            text-align: left;
        }
        .box {
            width: 12px;
            height: 12px;
            border: 1.5px solid #000;
            display: inline-flex;
            align-items: center;
            justify-content: center;
            font-size: 10px;
            font-weight: 700;
            flex-shrink: 0;
            margin-right: 4px;
        }
        .box.checked::after { content: '✓'; }
        .two-col { display: flex; gap: 0; }
        .two-col > div {
            flex: 1;
            border-right: 1.5px solid #000;
            padding: 6px 8px;
        }
        .two-col > div:last-child { border-right: none; }
        .rules { font-size: 10px; line-height: 1.45; margin-bottom: 6px; }
        .rules ol { padding-left: 18px; }
        .sign-row { display: flex; gap: 16px; margin-top: 6px; }
        .sign-box { flex: 1; }
        .sign-box .line {
            border-bottom: 1px solid #000;
            min-height: 18px;
            margin-top: 2px;
            margin-bottom: 4px;
        }
        table.gh-table {
            width: 100%;
            border-collapse: collapse;
            font-size: 10px;
        }
        table.gh-table td {
            border: 1px solid #000;
            padding: 4px 6px;
            vertical-align: middle;
        }
        table.gh-table .gh-head {
            text-align: center;
            font-weight: 700;
            font-size: 10px;
            text-transform: uppercase;
            background: #f8fafc;
            padding: 5px 6px;
        }
        table.gh-table .gh-label { white-space: nowrap; }
        .notes { font-size: 9px; margin-top: 8px; line-height: 1.4; }
        @media print {
            body { background: #fff; padding: 0; }
            .toolbar { display: none !important; }
            .page {
                width: 100%;
                min-height: auto;
                box-shadow: none;
                padding: 8mm 10mm;
                margin: 0;
            }
            @page { size: A4; margin: 8mm; }
        }
    </style>
</head>
<body>

<div class="toolbar">
    <a href="view_customers.php" class="btn-back">← Back</a>
    <button class="btn-print" onclick="window.print()">🖨 Print Form</button>
</div>

<div class="page">

    <div class="header">
        <img src="Logo Maritim Malaysia.png" alt="Maritim Malaysia">
        <div class="header-center">
            <div class="agency">
                CAWANGAN TEKNOLOGI MAKLUMAT DAN KOMUNIKASI (ICT)<br>
                AGENSI PENGUATKUASAAN MARITIM MALAYSIA<br>
                JABATAN PERDANA MENTERI
            </div>
            <div class="form-title">BORANG PINJAMAN PERALATAN ICT</div>
        </div>
        <div class="header-right">
            <div class="code">Borang Pinjaman<br>Peralatan ICT<br>(ICT-B001)</div>
            <div class="bil">Bil : ______ /200</div>
        </div>
    </div>

    <!-- A -->
    <div class="section">
        <div class="section-title">A. PEMINJAM</div>
        <div class="section-body">
            <div class="row">
                <div class="field"><label>Nama :</label><div class="line"><?php echo htmlspecialchars($nama); ?></div></div>
                <div class="field"><label>No KP :</label><div class="line"><?php echo htmlspecialchars($nokp); ?></div></div>
            </div>
            <div class="row">
                <div class="field"><label>Cawangan/Unit :</label><div class="line"></div></div>
                <div class="field"><label>Jawatan :</label><div class="line"></div></div>
            </div>
            <div class="row">
                <div class="field"><label>No. Tel :</label><div class="line"><?php echo htmlspecialchars($notel); ?></div></div>
                <div class="field"><label>Tempoh Pinjaman :</label><div class="line"></div><span>*hari / jam</span></div>
            </div>
            <div class="row">
                <div class="field"><label>Tarikh/Masa Pinjam :</label><div class="line"><?php echo htmlspecialchars($rentDate); ?></div></div>
                <div class="field"><label>Tarikh/Masa Pulang :</label><div class="line"><?php echo htmlspecialchars($retDate); ?></div></div>
            </div>
            <div class="row">
                <div class="field"><label>Tujuan :</label><div class="line"></div></div>
            </div>
        </div>
    </div>

    <!-- B + C separate boxes -->
    <div class="section">
        <div class="two-col">

            <!-- B -->
            <div>
                <div class="section-title" style="margin: -6px -8px 6px;">B. PERALATAN YANG DIPINJAM</div>
                <p style="font-size:9px;margin-bottom:4px;">Sila tandakan ( ✓ ) mana yang berkenaan.</p>
                <table class="form-table">
                    <thead>
                        <tr>
                            <th style="width:55%">Jenis Peralatan</th>
                            <th>Lokasi Penggunaan</th>
                        </tr>
                    </thead>
                    <tbody>
                        <tr>
                            <td><span class="box <?php echo $isLaptop ? 'checked' : ''; ?>"></span> Komputer / Notebook</td>
                            <td></td>
                        </tr>
                        <tr>
                            <td><span class="box <?php echo $isPrinter ? 'checked' : ''; ?>"></span> Pencetak</td>
                            <td></td>
                        </tr>
                        <tr>
                            <td><span class="box"></span> Pengimbas</td>
                            <td></td>
                        </tr>
                        <tr>
                            <td><span class="box <?php echo $isProjector ? 'checked' : ''; ?>"></span> Projektor</td>
                            <td></td>
                        </tr>
                        <tr>
                            <td><span class="box"></span> Lain-lain (nyatakan)</td>
                            <td></td>
                        </tr>
                    </tbody>
                </table>
            </div>

            <!-- C -->
            <div>
                <div class="section-title" style="margin: -6px -8px 6px;">C. UNTUK KEGUNAAN CAWANGAN ICT</div>

                <div style="display:flex; font-weight:700; font-size:10px; margin-bottom:4px; margin-top:2px;">
                    <div style="width:35%; padding:0 4px;">Model</div>
                    <div style="width:65%; padding:0 4px;">No Siri</div>
                </div>

                <?php foreach ($deviceList as $item): ?>
                <div style="display:flex; align-items:flex-end; margin-bottom:7px; min-height:18px;">
                    <div style="width:35%; padding:0 4px;">
                        <div style="border-bottom:1px solid #000; min-height:16px; font-size:10px; padding:0 2px;">
                            <?php echo htmlspecialchars($item['model']); ?>
                        </div>
                    </div>
                    <div style="width:65%; padding:0 4px;">
                        <div style="border-bottom:1px solid #000; min-height:16px; font-size:10px; padding:0 2px;">
                            <?php echo htmlspecialchars($item['serial']); ?>
                        </div>
                    </div>
                </div>
                <?php endforeach; ?>
            </div>

        </div>
    </div>

    <!-- D -->
    <div class="section">
        <div class="section-title">D. PERAKUAN DAN TANGGUNGJAWAB</div>
        <div class="section-body">
            <div class="rules">
                Dengan ini saya mengakui dan mematuhi syarat-syarat berikut :
                <ol type="I">
                    <li>Bertanggungjawab terhadap keselamatan peralatan yang dipinjam.</li>
                    <li>Melaporkan secara bertulis kerosakan/kehilangan peralatan kepada Pengarah Cawangan ICT dengan segera.</li>
                    <li>Memulangkan kembali peralatan seperti yang dipinjam pada tarikh dan masa yang ditetapkan.</li>
                    <li>Pinjaman peralatan adalah untuk tujuan kerja-kerja rasmi APMM sahaja.</li>
                </ol>
            </div>
            <div class="sign-row">
                <div class="sign-box">
                    <label>Tarikh :</label>
                    <div class="line"></div>
                </div>
                <div class="sign-box">
                    <label>Tandatangan :</label>
                    <div class="line"></div>
                </div>
            </div>
        </div>
    </div>

    <!-- E + F -->
    <div class="section">
        <div class="two-col">
            <div>
                <div class="section-title" style="margin:-6px -8px 6px;">E. SOKONGAN KETUA CAWANGAN / UNIT</div>
                <p style="font-size:10px;margin-bottom:6px;">Permohonan anda *disokong / tidak disokong</p>
                <p style="font-size:10px;font-weight:600;margin-bottom:8px;">Persetujuan Ketua Cawangan/Unit</p>
                <div class="sign-row">
                    <div class="sign-box">
                        <label>Tarikh :</label>
                        <div class="line"></div>
                    </div>
                    <div class="sign-box">
                        <label>Tandatangan :</label>
                        <div class="line"></div>
                    </div>
                </div>
            </div>
            <div>
                <div class="section-title" style="margin:-6px -8px 6px;">F. PENGESAHAN PEGAWAI CAWANGAN ICT</div>
                <p style="font-size:10px;font-weight:600;margin-bottom:25px;">Pengesahan Pegawai Cawangan ICT</p>
                <div class="sign-row">
                    <div class="sign-box">
                        <label>Tarikh :</label>
                        <div class="line"></div>
                    </div>
                    <div class="sign-box">
                        <label>Tandatangan :</label>
                        <div class="line"></div>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- G + H -->
    <div class="section" style="padding:0; border:1.5px solid #000;">
        <table style="width:100%; border-collapse:collapse;">
            <tr>
                <td style="width:50%; vertical-align:top; border-right:1.5px solid #000; padding:0;">
                    <div class="section-title">G. PENYERAHAN PERALATAN</div>
                    <table class="gh-table">
                        <tr><td colspan="2" class="gh-head">DISERAHKAN OLEH</td></tr>
                        <tr><td colspan="2"><span class="gh-label">Nama :</span></td></tr>
                        <tr>
                            <td style="width:60%;"><span class="gh-label">Tandatangan :</span></td>
                            <td style="width:40%;"><span class="gh-label">Tarikh :</span></td>
                        </tr>
                        <tr><td colspan="2" class="gh-head">DITERIMA OLEH</td></tr>
                        <tr>
                            <td colspan="2">
                                <span class="gh-label">Nama :</span>
                                <?php echo htmlspecialchars($nama); ?>
                            </td>
                        </tr>
                        <tr>
                            <td><span class="gh-label">Tandatangan :</span></td>
                            <td><span class="gh-label">Tarikh :</span></td>
                        </tr>
                    </table>
                </td>
                <td style="width:50%; vertical-align:top; padding:0;">
                    <div class="section-title">H. PEMULANGAN PERALATAN</div>
                    <table class="gh-table">
                        <tr><td colspan="2" class="gh-head">PENYERAHAN OLEH</td></tr>
                        <tr><td colspan="2"><span class="gh-label">Nama :</span></td></tr>
                        <tr>
                            <td style="width:60%;"><span class="gh-label">Tandatangan :</span></td>
                            <td style="width:40%;"><span class="gh-label">Tarikh :</span></td>
                        </tr>
                        <tr><td colspan="2" class="gh-head">DITERIMA DAN DISEMAK OLEH</td></tr>
                        <tr><td colspan="2"><span class="gh-label">Nama :</span></td></tr>
                        <tr>
                            <td><span class="gh-label">Tandatangan :</span></td>
                            <td><span class="gh-label">Tarikh :</span></td>
                        </tr>
                        <tr>
                            <td colspan="2" style="min-height:28px;">
                                <span class="gh-label">Ulasan :</span>
                            </td>
                        </tr>
                    </table>
                </td>
            </tr>
        </table>
    </div>

    <p style="font-size:9px;margin-top:4px;">* Potong mana yang tidak berkenaan.</p>
    <div class="notes">
        <strong>CATATAN :</strong>
        (i) Tempoh maksimum pinjaman adalah selama seminggu.<br>
        (ii) Dalam tempoh pinjaman, peminjam tidak boleh memberi pinjam kepada sesiapa pun.
    </div>

</div>
</body>
</html>