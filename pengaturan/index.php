<?php

session_start();

if (!isset($_SESSION['admin_id'])) {
    header('Location: ../login.php');
    exit;
}

require_once __DIR__ . '/../config/db.php';

$tab = $_GET['tab'] ?? 'sekolah';
$periods = [];
$periodResult = mysqli_query($conn, 'SELECT id, nama_periode, tanggal_mulai, tanggal_selesai FROM periode_pengukuran ORDER BY tanggal_mulai DESC');
if ($periodResult) while ($row = mysqli_fetch_assoc($periodResult)) $periods[] = $row;

$adminName = htmlspecialchars($_SESSION['admin_nama'] ?? 'Admin', ENT_QUOTES, 'UTF-8');
$adminUsername = htmlspecialchars($_SESSION['admin_username'] ?? 'admin', ENT_QUOTES, 'UTF-8');
function e(string $value): string { return htmlspecialchars($value, ENT_QUOTES, 'UTF-8'); }
function dateId(string $date): string { return date('d M Y', strtotime($date)); }
?>
<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Pengaturan - SISGIZI</title>
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=DM+Sans:wght@400;500;600;700&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="../assets/css/pengaturan.css">
</head>
<body>
<div class="app">
    <aside class="sidebar">
        <div class="brand"><img src="../assets/image/Logo Sekolah.jpg" alt="Logo SD Patemon 02"><div><strong>SISGIZI</strong><small>SD Patemon 02</small></div></div>
        <nav class="nav">
            <a href="../dashboard.php"><span class="nav-icon">⌂</span><span>Dashboard</span></a>
            <a href="../DataSiswa.php"><span class="nav-icon">♧</span><span>Data Siswa</span></a>
            <a href="../analisis/index.php"><span class="nav-icon">▥</span><span>Analisis</span></a>
            <a href="../laporan/index.php"><span class="nav-icon">▤</span><span>Laporan</span></a>
            <a class="active" href="index.php"><span class="nav-icon">⚙</span><span>Pengaturan</span></a>
            <a class="sidebar-logout" href="../logout.php"><span class="nav-icon">↪</span><span>Keluar</span></a>
        </nav>
        <div class="version">SISGIZI v1.0</div>
    </aside>
    <div class="main">
        <header class="topbar"><strong>Pengaturan</strong><div class="account"><span class="avatar" aria-hidden="true"></span><span class="account-name"><?= $adminName ?></span></div></header>
        <main class="content">
            <h1>Pengaturan</h1>
            <p class="intro">Kelola data sekolah, periode pengukuran, dan akun pengguna.</p>
            <nav class="tabs"><a class="<?= $tab === 'sekolah' ? 'active' : '' ?>" href="?tab=sekolah">Data Sekolah</a><a class="<?= $tab === 'periode' ? 'active' : '' ?>" href="?tab=periode">Periode Pengukuran</a><a class="<?= $tab === 'akun' ? 'active' : '' ?>" href="?tab=akun">Akun Pengguna</a></nav>
            <?php if ($tab === 'periode'): ?>
                <section class="card"><div class="card-head"><h2>Periode Pengukuran</h2><button class="outline-button" type="button">＋ Tambah Periode</button></div><?php if ($periods): ?><div class="period-list"><?php foreach ($periods as $period): ?><div class="period-row"><div class="period-icon">□</div><div><b><?= e($period['nama_periode']) ?></b><small><?= e(dateId($period['tanggal_mulai'])) ?> - <?= e(dateId($period['tanggal_selesai'])) ?></small></div><span class="period-status">Aktif</span></div><?php endforeach; ?></div><?php else: ?><p class="empty">Belum ada periode pengukuran.</p><?php endif; ?></section>
            <?php elseif ($tab === 'akun'): ?>
                <section class="card account-card"><div class="card-head"><h2>Informasi Akun Pengguna</h2><button class="outline-button" type="button">✎ Edit</button></div><div class="account-detail"><div class="large-avatar"></div><div><b><?= $adminName ?></b><small>Username: <?= $adminUsername ?></small><small>Administrator SISGIZI</small></div></div></section>
            <?php else: ?>
                <section class="card school-card"><div class="card-head"><h2>Informasi Sekolah</h2><button class="outline-button" type="button">✎ Edit</button></div><div class="school-details"><span>Nama Sekolah</span><b>SD Patemon 02</b><span>Alamat</span><b>Jl. Patemon No. 2, Semarang</b><span>Tingkat</span><b>Sekolah Dasar</b><span>Tahun Ajaran</span><b>2025/2026</b><span>Logo Sekolah</span><div class="logo-setting"><img src="../assets/image/Logo Sekolah.jpg" alt="Logo Sekolah"><button class="outline-button" type="button">⇧ &nbsp; Ubah Logo</button></div></div></section>
            <?php endif; ?>
        </main>
    </div>
</div>
</body>
</html>
