<?php

session_start();

if (!isset($_SESSION['admin_id'])) {
    header('Location: ../login.php');
    exit;
}

require_once __DIR__ . '/../config/db.php';

$overall = ['total' => 0, 'good' => 0, 'risk' => 0, 'problem' => 0];
$overallResult = mysqli_query($conn, "
    SELECT COUNT(*) AS total,
        SUM(status_gizi = 'Gizi Baik') AS good,
        SUM(status_gizi IN ('Berisiko (Kurang)', 'Berisiko (Lebih)')) AS risk,
        SUM(status_gizi = 'Masalah Gizi') AS problem
    FROM pengukuran
");
if ($overallResult) {
    $row = mysqli_fetch_assoc($overallResult);
    $overall['total'] = (int) ($row['total'] ?? 0);
    $overall['good'] = (int) ($row['good'] ?? 0);
    $overall['risk'] = (int) ($row['risk'] ?? 0);
    $overall['problem'] = (int) ($row['problem'] ?? 0);
}

$classes = [];
$classResult = mysqli_query($conn, "
    SELECT k.nama_kelas,
        COUNT(p.id) AS total,
        SUM(p.status_gizi = 'Gizi Baik') AS good,
        SUM(p.status_gizi IN ('Berisiko (Kurang)', 'Berisiko (Lebih)')) AS risk,
        SUM(p.status_gizi = 'Masalah Gizi') AS problem
    FROM kelas k
    LEFT JOIN siswa s ON s.kelas_id = k.id
    LEFT JOIN pengukuran p ON p.siswa_id = s.id
    GROUP BY k.id, k.nama_kelas
    ORDER BY k.id
");
if ($classResult) {
    while ($row = mysqli_fetch_assoc($classResult)) {
        $classes[] = [
            'name' => $row['nama_kelas'],
            'total' => (int) $row['total'],
            'good' => (int) $row['good'],
            'risk' => (int) $row['risk'],
            'problem' => (int) $row['problem'],
        ];
    }
}

function percent(int $value, int $total): float
{
    return $total > 0 ? round(($value / $total) * 100, 1) : 0;
}
function e(string $value): string { return htmlspecialchars($value, ENT_QUOTES, 'UTF-8'); }

$goodPercent = percent($overall['good'], $overall['total']);
$riskPercent = percent($overall['risk'], $overall['total']);
$problemPercent = percent($overall['problem'], $overall['total']);
$tab = $_GET['tab'] ?? 'distribution';
$totalGoodByClass = 0;
$highestRiskClass = '-';
$highestRiskPercent = 0;
foreach ($classes as &$class) {
    $class['good_percent'] = percent($class['good'], $class['total']);
    $class['risk_percent'] = percent($class['risk'], $class['total']);
    $class['problem_percent'] = percent($class['problem'], $class['total']);
    $totalGoodByClass += $class['good'];
    if ($class['risk_percent'] + $class['problem_percent'] > $highestRiskPercent) {
        $highestRiskPercent = $class['risk_percent'] + $class['problem_percent'];
        $highestRiskClass = $class['name'];
    }
}
unset($class);
$adminName = htmlspecialchars($_SESSION['admin_nama'] ?? 'Admin', ENT_QUOTES, 'UTF-8');
?>
<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Analisis Status Gizi - SISGIZI</title>
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=DM+Sans:wght@400;500;600;700&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="../assets/css/analisis.css">
</head>
<body>
<div class="app">
    <aside class="sidebar">
        <div class="brand"><img src="../assets/image/Logo Sekolah.jpg" alt="Logo SD Patemon 02"><div><strong>SISGIZI</strong><small>SD Patemon 02</small></div></div>
        <nav class="nav">
            <a href="../dashboard.php"><span class="nav-icon">⌂</span><span>Dashboard</span></a>
            <a href="../DataSiswa.php"><span class="nav-icon">♧</span><span>Data Siswa</span></a>
            <a class="active" href="index.php"><span class="nav-icon">▥</span><span>Analisis</span></a>
            <a href="../laporan/index.php"><span class="nav-icon">▤</span><span>Laporan</span></a>
            <a href="../pengaturan/index.php"><span class="nav-icon">⚙</span><span>Pengaturan</span></a>
            <a class="sidebar-logout" href="../logout.php"><span class="nav-icon">↪</span><span>Keluar</span></a>
        </nav>
        <div class="version">SISGIZI v1.0</div>
    </aside>
    <div class="main">
        <header class="topbar"><div class="crumb"><span>SISGIZI</span><b>/</b> Analisis Status Gizi</div><div class="account"><span class="avatar" aria-hidden="true"></span><span class="account-name"><?= $adminName ?></span></div></header>
        <main class="content">
            <h1>Analisis Status Gizi</h1>
            <p class="intro">Analisis dilakukan berdasarkan data agregat, meliputi distribusi, perbandingan antar kelas, dan tren status gizi siswa.</p>
            <nav class="tabs"><a class="<?= $tab === 'distribution' ? 'active' : '' ?>" href="index.php?tab=distribution">Distribusi Status Gizi</a><a class="<?= $tab === 'comparison' ? 'active' : '' ?>" href="index.php?tab=comparison">Perbandingan Antar Kelas</a><a class="<?= $tab === 'trend' ? 'active' : '' ?>" href="index.php?tab=trend">Tren</a></nav>
            <?php if ($tab === 'comparison'): ?>
            <section class="comparison-page">
                <div class="analysis-filters"><label>⚱ Filter Metrik<select><option>Status Gizi Lengkap (IMT/U &amp; TB/U)</option><option>Persentase Gizi Baik</option></select></label><label>Tahun Ajaran<select><option>2025/2026 (Semester II)</option></select></label><span>Total Siswa Terdata <b><?= $overall['total'] ?> Siswa (Kelas I - VI)</b></span></div>
                <article class="panel class-chart"><div class="panel-heading"><div><h2>Distribusi Proporsi Status Gizi Antar Kelas (Kelas I - VI)</h2><p>Visualisasi 100% stacked bar komparatif persentase Gizi Baik, Berisiko, dan Masalah Gizi.</p></div><div class="chart-key"><span><i class="green"></i>Gizi Baik</span><span><i class="orange"></i>Berisiko</span><span><i class="red"></i>Masalah Gizi</span></div></div><div class="comparison-bars"><?php foreach ($classes as $class): ?><div class="comparison-bar-group"><div class="comparison-bar"><span class="bar-problem" style="height:<?= $class['problem_percent'] ?>%"> <?php if ($class['problem_percent'] >= 10): ?><?= $class['problem_percent'] ?>%<?php endif; ?></span><span class="bar-risk" style="height:<?= $class['risk_percent'] ?>%"> <?php if ($class['risk_percent'] >= 10): ?><?= $class['risk_percent'] ?>%<?php endif; ?></span><span class="bar-good" style="height:<?= $class['good_percent'] ?>%"> <?php if ($class['good_percent'] >= 10): ?><?= $class['good_percent'] ?>%<?php endif; ?></span></div><b>Kelas <?= e($class['name']) ?></b><small><?= $class['total'] ?> siswa</small></div><?php endforeach; ?></div><p class="chart-note">* Tinggi batang menunjukkan proporsi status gizi. Kelas dengan warna merah/oranye lebih tinggi memerlukan perhatian lebih lanjut.</p></article>
                <article class="panel comparison-table"><div class="panel-heading"><div><h2>Tabel Komparasi Distribusi Status Gizi</h2><p>Rincian jumlah dan persentase status gizi per kelas.</p></div><button class="outline-button">⇩ &nbsp; Unduh Rekap (CSV)</button></div><div class="table-scroll"><table><thead><tr><th>Kelas</th><th>Total Siswa</th><th>Gizi Baik</th><th>Berisiko</th><th>Masalah Gizi</th><th>Status Mayoritas</th><th>Prioritas Intervensi</th></tr></thead><tbody><?php foreach ($classes as $class): $priority = $class['problem_percent'] >= 10 ? 'Tinggi' : ($class['risk_percent'] >= 10 ? 'Sedang' : 'Rendah'); ?><tr><td><i class="class-dot"></i>Kelas <?= e($class['name']) ?></td><td><?= $class['total'] ?></td><td class="good-text"><?= $class['good'] ?><small>(<?= $class['good_percent'] ?>%)</small></td><td class="risk-text"><?= $class['risk'] ?><small>(<?= $class['risk_percent'] ?>%)</small></td><td class="problem-text"><?= $class['problem'] ?><small>(<?= $class['problem_percent'] ?>%)</small></td><td><span class="status-good-pill">Gizi Baik (<?= $class['good_percent'] ?>%)</span></td><td><span class="priority <?= strtolower($priority) ?>"><?= $priority === 'Rendah' ? 'Rendah (Pemantauan Rutin)' : $priority . ' (Perlu Perhatian)' ?></span></td></tr><?php endforeach; ?></tbody></table></div></article>
                <div class="insight-grid"><section class="finding"><span class="finding-icon">⌁</span><div><h2>Temuan Komparatif Antar Kelas</h2><p>Kelas <?= e($highestRiskClass) ?> memiliki proporsi siswa berisiko/masalah gizi tertinggi (<?= $highestRiskPercent ?>%).</p><p>Distribusi gizi baik antar kelas perlu dipantau untuk menjaga kondisi siswa.</p></div></section><section class="recommendation"><span>◷</span><div><h2>Rekomendasi Program (UKS &amp; Wali Kelas)</h2><p>Prioritaskan konseling gizi di kelas dengan proporsi risiko tertinggi dan lakukan pemantauan berkala.</p><p>Koordinasikan tindak lanjut dengan Puskesmas Patemon untuk siswa yang membutuhkan perhatian khusus.</p></div></section></div>
            </section>
            <?php elseif ($tab === 'trend'): ?>
            <section class="finding trend-panel" id="trend"><span class="finding-icon">⌁</span><div><h2>Tren Status Gizi</h2><p>Tren akan tersedia setelah terdapat beberapa periode pengukuran.</p></div></section>
            <?php else: ?>
            <section class="charts">
                <article class="panel distribution"><h2>Distribusi Status Gizi Keseluruhan</h2><div class="pie-row"><div class="pie" style="--good: <?= $goodPercent ?>%; --risk: <?= $goodPercent + $riskPercent ?>%;"><div>Total<strong><?= $overall['total'] ?></strong>siswa</div></div><div class="legend"><div><i class="green"></i>Gizi baik <b><?= $goodPercent ?>%</b> (<?= $overall['good'] ?>)</div><div><i class="orange"></i>Berisiko atau masalah gizi <b><?= $riskPercent ?>%</b> (<?= $overall['risk'] ?>)</div><div><i class="red"></i>Masalah gizi <b><?= $problemPercent ?>%</b> (<?= $overall['problem'] ?>)</div></div></div><small class="caption">Total sampel: <?= $overall['total'] ?> siswa SD Patemon 02</small></article>
                <article class="panel comparison" id="comparison"><h2>Perbandingan Status Gizi per Kelas</h2><div class="bars"><?php foreach ($classes as $class): $good = percent($class['good'], $class['total']); $risk = percent($class['risk'], $class['total']); $problem = percent($class['problem'], $class['total']); ?><div class="bar-group"><div class="bar" title="<?= e($class['name']) ?>"><span class="bar-good" style="height:<?= $good ?>%"></span><span class="bar-risk" style="height:<?= $risk ?>%"></span><span class="bar-problem" style="height:<?= $problem ?>%"></span></div><small>Kelas <?= e($class['name']) ?></small></div><?php endforeach; ?></div><div class="bar-legend"><span><i class="green"></i> Gizi baik</span><span><i class="orange"></i> Berisiko</span><span><i class="red"></i> Masalah gizi</span></div></article>
            </section>
            <section class="finding" id="trend"><span class="finding-icon">⌁</span><div><h2>Temuan Utama</h2><?php if ($overall['total'] > 0): ?><p>Data menunjukkan <?= $goodPercent ?>% hasil pengukuran berada pada kategori gizi baik.</p><p>Perlu perhatian lebih lanjut pada <?= $overall['risk'] + $overall['problem'] ?> siswa dengan status berisiko atau masalah gizi.</p><?php else: ?><p>Belum ada data pengukuran untuk menghasilkan temuan analisis.</p><p>Tambahkan data siswa dan pengukuran terlebih dahulu.</p><?php endif; ?></div></section>
            <?php endif; ?>
        </main>
    </div>
</div>
</body>
</html>
