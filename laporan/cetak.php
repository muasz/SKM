<?php

session_start();

if (!isset($_SESSION['admin_id'])) {
    header('Location: ../login.php');
    exit;
}

require_once __DIR__ . '/../config/db.php';

$type = $_GET['jenis'] ?? 'analisis';
$startDate = $_GET['mulai'] ?? date('Y-m-01');
$endDate = $_GET['selesai'] ?? date('Y-m-d');
$rows = [];

if ($type === 'kelas') {
    $result = mysqli_query($conn, "SELECT k.nama_kelas, COUNT(p.id) AS total, SUM(p.status_gizi = 'Gizi Baik') AS good, SUM(p.status_gizi IN ('Berisiko (Kurang)', 'Berisiko (Lebih)')) AS risk, SUM(p.status_gizi = 'Masalah Gizi') AS problem FROM kelas k LEFT JOIN siswa s ON s.kelas_id = k.id LEFT JOIN pengukuran p ON p.siswa_id = s.id AND p.tanggal_pengukuran BETWEEN '" . mysqli_real_escape_string($conn, $startDate) . "' AND '" . mysqli_real_escape_string($conn, $endDate) . "' GROUP BY k.id, k.nama_kelas ORDER BY k.id");
} else {
    $result = mysqli_query($conn, "SELECT status_gizi, COUNT(*) AS total FROM pengukuran WHERE tanggal_pengukuran BETWEEN '" . mysqli_real_escape_string($conn, $startDate) . "' AND '" . mysqli_real_escape_string($conn, $endDate) . "' GROUP BY status_gizi ORDER BY status_gizi");
}
if ($result) {
    while ($row = mysqli_fetch_assoc($result)) $rows[] = $row;
}
function e(string $value): string { return htmlspecialchars($value, ENT_QUOTES, 'UTF-8'); }
function dateId(string $value): string { return date('d-m-Y', strtotime($value)); }
?>
<!DOCTYPE html>
<html lang="id"><head><meta charset="UTF-8"><title>Laporan SISGIZI</title><style>body{font-family:Arial,sans-serif;color:#24334a;margin:40px}h1{color:#078760}p{color:#71839d}table{width:100%;border-collapse:collapse;margin-top:25px}th,td{padding:12px;border:1px solid #dce5ec;text-align:left}th{background:#eff8f4}.print{padding:10px 16px;color:#fff;background:#078760;border:0;border-radius:5px;cursor:pointer}@media print{.print{display:none}}</style></head>
<body><button class="print" onclick="window.print()">Cetak / Simpan sebagai PDF</button><h1><?= $type === 'kelas' ? 'Laporan Status Gizi per Kelas' : 'Laporan Analisis Status Gizi Siswa' ?></h1><p>Periode: <?= e(dateId($startDate)) ?> sampai <?= e(dateId($endDate)) ?></p><table><thead><?php if ($type === 'kelas'): ?><tr><th>Kelas</th><th>Total Pengukuran</th><th>Gizi Baik</th><th>Berisiko</th><th>Masalah Gizi</th></tr><?php else: ?><tr><th>Status Gizi</th><th>Total</th></tr><?php endif; ?></thead><tbody><?php foreach ($rows as $row): ?><?php if ($type === 'kelas'): ?><tr><td><?= e($row['nama_kelas']) ?></td><td><?= (int) $row['total'] ?></td><td><?= (int) $row['good'] ?></td><td><?= (int) $row['risk'] ?></td><td><?= (int) $row['problem'] ?></td></tr><?php else: ?><tr><td><?= e($row['status_gizi']) ?></td><td><?= (int) $row['total'] ?></td></tr><?php endif; ?><?php endforeach; ?><?php if (!$rows): ?><tr><td colspan="5">Belum ada data pada periode ini.</td></tr><?php endif; ?></tbody></table></body></html>
