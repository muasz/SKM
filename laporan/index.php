<?php

session_start();

if (!isset($_SESSION['admin_id'])) {
    header('Location: ../login.php');
    exit;
}

require_once __DIR__ . '/../config/db.php';

$startDate = $_GET['mulai'] ?? date('Y-m-01');
$endDate = $_GET['selesai'] ?? date('Y-m-d');
$periods = [];
$periodResult = mysqli_query($conn, 'SELECT id, nama_periode, tanggal_mulai, tanggal_selesai FROM periode_pengukuran ORDER BY tanggal_mulai DESC');
if ($periodResult) {
    while ($row = mysqli_fetch_assoc($periodResult)) {
        $periods[] = $row;
    }
}

$measurementCount = 0;
$countStatement = mysqli_prepare($conn, 'SELECT COUNT(*) AS total FROM pengukuran WHERE tanggal_pengukuran BETWEEN ? AND ?');
if ($countStatement) {
    mysqli_stmt_bind_param($countStatement, 'ss', $startDate, $endDate);
    mysqli_stmt_execute($countStatement);
    $countResult = mysqli_stmt_get_result($countStatement);
    $countRow = mysqli_fetch_assoc($countResult);
    $measurementCount = (int) ($countRow['total'] ?? 0);
}

$adminName = htmlspecialchars($_SESSION['admin_nama'] ?? 'Admin', ENT_QUOTES, 'UTF-8');
function e(string $value): string { return htmlspecialchars($value, ENT_QUOTES, 'UTF-8'); }
function displayDate(string $date): string { return date('d M Y', strtotime($date)); }
?>
<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Laporan - SISGIZI</title>
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=DM+Sans:wght@400;500;600;700&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="../assets/css/laporan.css">
</head>
<body>
<div class="app">
    <aside class="sidebar">
        <div class="brand"><img src="../assets/image/Logo Sekolah.jpg" alt="Logo SD Patemon 02"><div><strong>SISGIZI</strong><small>SD Patemon 02</small></div></div>
        <nav class="nav">
            <a href="../dashboard.php"><span class="nav-icon">⌂</span><span>Dashboard</span></a>
            <a href="../DataSiswa.php"><span class="nav-icon">♧</span><span>Data Siswa</span></a>
            <a href="../analisis/index.php"><span class="nav-icon">▥</span><span>Analisis</span></a>
            <a class="active" href="index.php"><span class="nav-icon">▤</span><span>Laporan</span></a>
            <a href="../pengaturan/index.php"><span class="nav-icon">⚙</span><span>Pengaturan</span></a>
            <a class="sidebar-logout" href="../logout.php"><span class="nav-icon">↪</span><span>Keluar</span></a>
        </nav>
        <div class="version">SISGIZI v1.0</div>
    </aside>
    <div class="main">
        <header class="topbar"><strong>Laporan</strong><div class="account"><span class="avatar" aria-hidden="true"></span><span class="account-name"><?= $adminName ?></span></div></header>
        <main class="content">
            <h1>Laporan</h1>
            <p class="intro">Unduh laporan hasil analisis status gizi siswa dalam format PDF.</p>
            <section class="period-card"><div><h2>Pilih Periode Laporan</h2><form method="GET" class="period-form"><input type="date" name="mulai" value="<?= e($startDate) ?>"><span>→</span><input type="date" name="selesai" value="<?= e($endDate) ?>"><button type="submit">Generate Laporan</button></form></div></section>
            <section class="reports"><h2>Laporan Tersedia</h2>
                <article class="report-row"><span class="report-icon">▤</span><div class="report-info"><b>Laporan Analisis Status Gizi Siswa</b><small>Ringkasan hasil analisis, grafik, dan rekomendasi</small></div><div class="report-action"><a href="cetak.php?jenis=analisis&mulai=<?= e($startDate) ?>&selesai=<?= e($endDate) ?>" target="_blank">Download PDF</a><small><?= e(displayDate($endDate)) ?></small></div></article>
                <article class="report-row"><span class="report-icon">▤</span><div class="report-info"><b>Laporan per Kelas</b><small>Distribusi status gizi berdasarkan kelas</small></div><div class="report-action"><a href="cetak.php?jenis=kelas&mulai=<?= e($startDate) ?>&selesai=<?= e($endDate) ?>" target="_blank">Download PDF</a><small><?= e(displayDate($endDate)) ?></small></div></article>
            </section>
            <p class="result-note"><?= $measurementCount ?> data pengukuran tersedia pada periode <?= e(displayDate($startDate)) ?> - <?= e(displayDate($endDate)) ?>.</p>
        </main>
    </div>
</div>
</body>
</html>
