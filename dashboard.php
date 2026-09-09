<?php

session_start();

if (!isset($_SESSION['admin_id'])) {
    header("Location: login.php");
    exit;
}
require_once __DIR__ . '/config/db.php';

function countRows(mysqli $conn, string $query): int
{
    $result = mysqli_query($conn, $query);
    if (!$result) {
        return 0;
    }

    $row = mysqli_fetch_assoc($result);
    return (int) ($row['total'] ?? 0);
}

$totalSiswa = countRows($conn, 'SELECT COUNT(*) AS total FROM siswa');
$lakiLaki = countRows($conn, "SELECT COUNT(*) AS total FROM siswa WHERE jenis_kelamin = 'L'");
$perempuan = countRows($conn, "SELECT COUNT(*) AS total FROM siswa WHERE jenis_kelamin = 'P'");
$giziBaik = countRows($conn, "SELECT COUNT(*) AS total FROM pengukuran WHERE status_gizi = 'Gizi Baik'");
$berisiko = countRows($conn, "SELECT COUNT(*) AS total FROM pengukuran WHERE status_gizi IN ('Berisiko (Kurang)', 'Berisiko (Lebih)')");
$masalahGizi = countRows($conn, "SELECT COUNT(*) AS total FROM pengukuran WHERE status_gizi = 'Masalah Gizi'");

$latestDate = '-';
$latestResult = mysqli_query($conn, 'SELECT MAX(tanggal_pengukuran) AS tanggal FROM pengukuran');
if ($latestResult) {
    $latestRow = mysqli_fetch_assoc($latestResult);
    if (!empty($latestRow['tanggal'])) {
        $latestDate = date('d F Y', strtotime($latestRow['tanggal']));
    }
}

$measuredTotal = $giziBaik + $berisiko + $masalahGizi;
$goodPercent = $measuredTotal > 0 ? round(($giziBaik / $measuredTotal) * 100, 1) : 0;
$riskPercent = $measuredTotal > 0 ? round(($berisiko / $measuredTotal) * 100, 1) : 0;
$problemPercent = $measuredTotal > 0 ? round(($masalahGizi / $measuredTotal) * 100, 1) : 0;
$riskEndPercent = $goodPercent + $riskPercent;
$malePercent = $totalSiswa > 0 ? round(($lakiLaki / $totalSiswa) * 100, 1) : 0;
$femalePercent = $totalSiswa > 0 ? round(($perempuan / $totalSiswa) * 100, 1) : 0;

$adminName = htmlspecialchars($_SESSION['admin_nama'] ?? 'Admin', ENT_QUOTES, 'UTF-8');
?>
<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Dashboard - SISGIZI</title>
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=DM+Sans:wght@400;500;600;700&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="assets/css/dashboard.css">
</head>
<body>
<div class="app">
    <aside class="sidebar">
        <div class="brand"><img src="assets/image/Logo Sekolah.jpg" alt="Logo SD Patemon 02"><div><strong>SISGIZI</strong><small>SD Patemon 02</small></div></div>
        <nav class="nav">
            <a class="active" href="dashboard.php"><span class="nav-icon">⌂</span><span>Dashboard</span></a>
            <a href="siswa/index.php"><span class="nav-icon">♧</span><span>Data Siswa</span></a>
            <a href="analisis/index.php"><span class="nav-icon">▥</span><span>Analisis</span></a>
            <a href="laporan/index.php"><span class="nav-icon">▤</span><span>Laporan</span></a>
            <a href="pengaturan/index.php"><span class="nav-icon">⚙</span><span>Pengaturan</span></a>
            <a class="sidebar-logout" href="logout.php"><span class="nav-icon">↪</span><span>Keluar</span></a>
        </nav>
        <div class="version">SISGIZI v1.0</div>
    </aside>
    <div class="main">
        <header class="topbar"><strong>Dashboard</strong><div class="account"><span class="avatar" aria-hidden="true"></span><span class="account-name"><?= $adminName ?></span></div></header>
        <main class="content">
            <div class="heading"><h1>Halo, <?= $adminName ?></h1><p>Selamat datang di Sistem Informasi Analisis Status Gizi Siswa SD Patemon 02.</p></div>
            <section class="stats">
                <div class="stat"><span class="stat-icon">♧</span><div><label>Total Siswa Diperiksa</label><b><?= $totalSiswa ?></b><em>siswa</em></div></div>
                <div class="stat"><span class="stat-icon">♙</span><div><label>Laki-laki</label><b><?= $lakiLaki ?></b><em>(<?= $malePercent ?>%)</em></div></div>
                <div class="stat"><span class="stat-icon">♙</span><div><label>Perempuan</label><b><?= $perempuan ?></b><em>(<?= $femalePercent ?>%)</em></div></div>
                <div class="stat"><span class="stat-icon">◷</span><div><label>Rentang Usia</label><b>6 - 12</b><em>tahun</em></div></div>
            </section>
            <div class="notice"><span class="notice-icon">✓</span><span><b>Status gizi siswa SD Patemon 02 secara umum</b> menunjukkan kondisi yang baik. Namun, masih terdapat beberapa siswa yang memerlukan perhatian lebih lanjut.</span></div>
            <section class="lower">
                <div class="panel"><h2>Distribusi Status Gizi</h2><div class="chart-wrap"><div class="donut" style="--good-percent: <?= $goodPercent ?>%; --risk-end-percent: <?= $riskEndPercent ?>%;"><span>Total<strong><?= $measuredTotal ?></strong>siswa</span></div><div class="legend"><div class="legend-row"><span class="legend-name"><i class="dot"></i>Gizi baik</span><span><b><?= $goodPercent ?>%</b> (<?= $giziBaik ?>)</span></div><div class="legend-row"><span class="legend-name"><i class="dot orange"></i>Berisiko atau kurang</span><span><b><?= $riskPercent ?>%</b> (<?= $berisiko ?>)</span></div><div class="legend-row"><span class="legend-name"><i class="dot red"></i>Masalah gizi</span><span><b><?= $problemPercent ?>%</b> (<?= $masalahGizi ?>)</span></div></div></div></div>
                <div class="updated"><div class="updated-top"><span class="calendar">□</span><div><label>Data diperbarui</label><b><?= htmlspecialchars($latestDate, ENT_QUOTES, 'UTF-8') ?></b></div></div><a class="detail" href="analisis/index.php">Lihat Detail &nbsp;→</a></div>
            </section>
        </main>
    </div>
</div>
</body>
</html>