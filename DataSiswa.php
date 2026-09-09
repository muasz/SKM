<?php

session_start();

if (!isset($_SESSION['admin_id'])) {
    header('Location: login.php');
    exit;
}

require_once __DIR__ . '/config/db.php';

$rows = [];
$query = "
    SELECT k.id, k.nama_kelas,
        COUNT(DISTINCT s.id) AS total_siswa,
        COUNT(DISTINCT CASE WHEN p.status_gizi = 'Gizi Baik' THEN s.id END) AS gizi_baik,
        COUNT(DISTINCT CASE WHEN p.status_gizi IN ('Berisiko (Kurang)', 'Berisiko (Lebih)') THEN s.id END) AS berisiko,
        COUNT(DISTINCT CASE WHEN p.status_gizi = 'Masalah Gizi' THEN s.id END) AS masalah_gizi
    FROM kelas k
    LEFT JOIN siswa s ON s.kelas_id = k.id
    LEFT JOIN pengukuran p ON p.siswa_id = s.id
    GROUP BY k.id, k.nama_kelas
    ORDER BY k.id
";
$result = mysqli_query($conn, $query);
if ($result) {
    while ($row = mysqli_fetch_assoc($result)) {
        $rows[] = $row;
    }
}

$adminName = htmlspecialchars($_SESSION['admin_nama'] ?? 'Admin', ENT_QUOTES, 'UTF-8');
function e(string $value): string { return htmlspecialchars($value, ENT_QUOTES, 'UTF-8'); }
?>
<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Ringkasan Data Siswa - SISGIZI</title>
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=DM+Sans:wght@400;500;600;700&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="assets/css/data-siswa.css">
</head>
<body>
<div class="app">
    <aside class="sidebar">
        <div class="brand"><img src="assets/image/Logo Sekolah.jpg" alt="Logo SD Patemon 02"><div><strong>SISGIZI</strong><small>SD Patemon 02</small></div></div>
        <nav class="nav">
            <a href="dashboard.php"><span class="nav-icon">⌂</span><span>Dashboard</span></a>
            <a class="active" href="DataSiswa.php"><span class="nav-icon">♧</span><span>Data Siswa</span></a>
            <a href="analisis/index.php"><span class="nav-icon">▥</span><span>Analisis</span></a>
            <a href="laporan/index.php"><span class="nav-icon">▤</span><span>Laporan</span></a>
            <a href="pengaturan/index.php"><span class="nav-icon">⚙</span><span>Pengaturan</span></a>
            <a class="sidebar-logout" href="logout.php"><span class="nav-icon">↪</span><span>Keluar</span></a>
        </nav>
        <div class="version">SISGIZI v1.0</div>
    </aside>
    <div class="main">
        <header class="topbar"><strong>Data Siswa</strong><div class="account"><span class="avatar" aria-hidden="true"></span><span class="account-name"><?= $adminName ?></span></div></header>
        <main class="content">
            <div class="heading"><div><h1>Data Siswa &amp; Rekap Gizi</h1><p>Menampilkan jumlah dan persentase status gizi siswa berdasarkan kelas.</p></div><div class="view-switch"><a class="active" href="DataSiswa.php">✓ &nbsp; Ringkasan per Kelas</a><a href="siswa/index.php">♧ &nbsp; Daftar Siswa Individual <span class="new">Baru</span></a></div></div>
            <div class="toolbar"><select class="select" id="classFilter"><option value="">Semua Kelas</option><?php foreach ($rows as $row): ?><option value="<?= e($row['nama_kelas']) ?>">Kelas <?= e($row['nama_kelas']) ?></option><?php endforeach; ?></select><input class="search" id="classSearch" type="search" placeholder="⌕  Cari kelas..."></div>
            <div class="table-card"><table id="classTable"><thead><tr><th>Kelas</th><th>Total Siswa</th><th>Gizi Baik</th><th>Berisiko</th><th>Masalah Gizi</th><th>Persentase Gizi Baik</th><th>Aksi</th></tr></thead><tbody><?php foreach ($rows as $row): $total = (int) $row['total_siswa']; $good = (int) $row['gizi_baik']; $percentage = $total > 0 ? round(($good / $total) * 100, 1) : 0; ?><tr><td><?= e($row['nama_kelas']) ?></td><td><?= $total ?></td><td><?= $good ?></td><td><?= (int) $row['berisiko'] ?></td><td><?= (int) $row['masalah_gizi'] ?></td><td class="good"><?= str_replace('.', ',', (string) $percentage) ?>%</td><td><a class="action" href="siswa/index.php?kelas=<?= (int) $row['id'] ?>">Lihat Siswa&nbsp; →</a></td></tr><?php endforeach; ?><?php if (!$rows): ?><tr><td colspan="7" class="empty">Data kelas belum tersedia.</td></tr><?php endif; ?></tbody></table></div>
            <p class="note">Catatan: Persentase gizi baik dihitung dari jumlah siswa dengan kategori gizi baik dibagi total siswa per kelas.</p>
        </main>
    </div>
</div>
<script>
    const filter = () => { const query = document.getElementById('classSearch').value.toLowerCase(); const selected = document.getElementById('classFilter').value.toLowerCase(); document.querySelectorAll('#classTable tbody tr').forEach(row => { const name = row.cells[0]?.textContent.toLowerCase() || ''; row.style.display = name.includes(query) && (!selected || name === selected) ? '' : 'none'; }); };
    document.getElementById('classSearch').addEventListener('input', filter);
    document.getElementById('classFilter').addEventListener('change', filter);
</script>
</body>
</html>
