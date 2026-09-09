<?php

session_start();

if (!isset($_SESSION['admin_id'])) {
    header('Location: ../login.php');
    exit;
}

require_once __DIR__ . '/../config/db.php';

$selectedClass = (int) ($_GET['kelas'] ?? 0);
$selectedStatus = $_GET['status'] ?? '';
$selectedGender = $_GET['gender'] ?? '';
$search = trim($_GET['q'] ?? '');
$conditions = [];
$params = [];
$types = '';

if ($selectedClass > 0) {
    $conditions[] = 's.kelas_id = ?';
    $params[] = $selectedClass;
    $types .= 'i';
}
if ($selectedGender === 'L' || $selectedGender === 'P') {
    $conditions[] = 's.jenis_kelamin = ?';
    $params[] = $selectedGender;
    $types .= 's';
}
if ($search !== '') {
    $conditions[] = '(s.nama LIKE ? OR s.nisn LIKE ?)';
    $searchValue = '%' . $search . '%';
    $params[] = $searchValue;
    $params[] = $searchValue;
    $types .= 'ss';
}
if (in_array($selectedStatus, ['Gizi Baik', 'Berisiko (Kurang)', 'Berisiko (Lebih)', 'Masalah Gizi'], true)) {
    $conditions[] = 'latest.status_gizi = ?';
    $params[] = $selectedStatus;
    $types .= 's';
}

$where = $conditions ? 'WHERE ' . implode(' AND ', $conditions) : '';
$query = "
    SELECT s.id, s.nisn, s.nama, s.jenis_kelamin, s.tanggal_lahir, k.nama_kelas,
           latest.berat_badan, latest.tinggi_badan, latest.imt, latest.z_score, latest.status_gizi
    FROM siswa s
    INNER JOIN kelas k ON k.id = s.kelas_id
    LEFT JOIN pengukuran latest ON latest.id = (
        SELECT p.id FROM pengukuran p
        WHERE p.siswa_id = s.id
        ORDER BY p.tanggal_pengukuran DESC, p.id DESC
        LIMIT 1
    )
    $where
    ORDER BY k.id, s.nama
";

$statement = mysqli_prepare($conn, $query);
if ($statement && $params) {
    mysqli_stmt_bind_param($statement, $types, ...$params);
}

$students = [];
if ($statement && mysqli_stmt_execute($statement)) {
    $result = mysqli_stmt_get_result($statement);
    while ($row = mysqli_fetch_assoc($result)) {
        $students[] = $row;
    }
}

$classes = [];
$classResult = mysqli_query($conn, 'SELECT id, nama_kelas FROM kelas ORDER BY id');
if ($classResult) {
    while ($row = mysqli_fetch_assoc($classResult)) {
        $classes[] = $row;
    }
}

$totalRegistered = 0;
$statusCounts = ['Gizi Baik' => 0, 'Berisiko' => 0, 'Masalah Gizi' => 0];
$modalType = $_GET['modal'] ?? '';
$modalStudent = null;
if (in_array($modalType, ['edit', 'hapus'], true) && (int) ($_GET['id'] ?? 0) > 0) {
    $modalId = (int) $_GET['id'];
    $modalStatement = mysqli_prepare($conn, "SELECT s.*, latest.id AS pengukuran_id, latest.tanggal_pengukuran, latest.berat_badan, latest.tinggi_badan, latest.imt, latest.status_gizi FROM siswa s LEFT JOIN pengukuran latest ON latest.id = (SELECT p.id FROM pengukuran p WHERE p.siswa_id = s.id ORDER BY p.tanggal_pengukuran DESC, p.id DESC LIMIT 1) WHERE s.id = ?");
    mysqli_stmt_bind_param($modalStatement, 'i', $modalId);
    mysqli_stmt_execute($modalStatement);
    $modalStudent = mysqli_fetch_assoc(mysqli_stmt_get_result($modalStatement));
}
$allCountResult = mysqli_query($conn, "
    SELECT COUNT(*) AS total,
        SUM(latest.status_gizi = 'Gizi Baik') AS good,
        SUM(latest.status_gizi IN ('Berisiko (Kurang)', 'Berisiko (Lebih)')) AS risk,
        SUM(latest.status_gizi = 'Masalah Gizi') AS problem
    FROM siswa s
    LEFT JOIN pengukuran latest ON latest.id = (
        SELECT p.id FROM pengukuran p WHERE p.siswa_id = s.id ORDER BY p.tanggal_pengukuran DESC, p.id DESC LIMIT 1
    )
");
if ($allCountResult) {
    $counts = mysqli_fetch_assoc($allCountResult);
    $totalRegistered = (int) ($counts['total'] ?? 0);
    $statusCounts['Gizi Baik'] = (int) ($counts['good'] ?? 0);
    $statusCounts['Berisiko'] = (int) ($counts['risk'] ?? 0);
    $statusCounts['Masalah Gizi'] = (int) ($counts['problem'] ?? 0);
}

$adminName = htmlspecialchars($_SESSION['admin_nama'] ?? 'Admin', ENT_QUOTES, 'UTF-8');
function e(string $value): string { return htmlspecialchars($value, ENT_QUOTES, 'UTF-8'); }
function statusClass(?string $status): string {
    if ($status === 'Gizi Baik') return 'status-good';
    if ($status === 'Masalah Gizi') return 'status-problem';
    return 'status-risk';
}
?>
<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Daftar Siswa - SISGIZI</title>
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=DM+Sans:wght@400;500;600;700&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="../assets/css/data-siswa-individual.css">
</head>
<body>
<div class="app">
    <aside class="sidebar">
        <div class="brand"><img src="../assets/image/Logo Sekolah.jpg" alt="Logo SD Patemon 02"><div><strong>SISGIZI</strong><small>SD Patemon 02</small></div></div>
        <nav class="nav">
            <a href="../dashboard.php"><span class="nav-icon">⌂</span><span>Dashboard</span></a>
            <a class="active" href="../DataSiswa.php"><span class="nav-icon">♧</span><span>Data Siswa</span></a>
            <a href="../analisis/index.php"><span class="nav-icon">▥</span><span>Analisis</span></a>
            <a href="../laporan/index.php"><span class="nav-icon">▤</span><span>Laporan</span></a>
            <a href="../pengaturan/index.php"><span class="nav-icon">⚙</span><span>Pengaturan</span></a>
            <a class="sidebar-logout" href="../logout.php"><span class="nav-icon">↪</span><span>Keluar</span></a>
        </nav>
        <div class="version">SISGIZI v1.0</div>
    </aside>
    <div class="main">
        <header class="topbar"><strong>Data Siswa</strong><div class="account"><span class="avatar" aria-hidden="true"></span><span class="account-name"><?= $adminName ?></span></div></header>
        <main class="content">
            <div class="heading">
                <div><h1>Data Siswa &amp; Rekap Gizi</h1><p>Kelola informasi status pertumbuhan dan data antropometri berkala siswa SD Patemon 02.</p></div>
                <div class="view-switch"><a href="../DataSiswa.php">▥ &nbsp; Ringkasan per Kelas</a><a class="active" href="index.php">♧ &nbsp; Daftar Siswa Individual</a></div>
            </div>
            <section class="summary">
                <div class="summary-card"><span class="summary-icon">♧</span><div><label>Total Siswa Terdaftar</label><strong><?= $totalRegistered ?></strong><small>Kelas I - VI</small></div></div>
                <div class="summary-card good-card"><span class="summary-icon">✓</span><div><label>Gizi Baik</label><strong><?= $statusCounts['Gizi Baik'] ?></strong><small>status terbaru</small></div></div>
                <div class="summary-card risk-card"><span class="summary-icon">△</span><div><label>Berisiko</label><strong><?= $statusCounts['Berisiko'] ?></strong><small>perlu dipantau</small></div></div>
                <div class="summary-card problem-card"><span class="summary-icon">!</span><div><label>Masalah Gizi</label><strong><?= $statusCounts['Masalah Gizi'] ?></strong><small>rujukan/PMT</small></div></div>
            </section>
            <form class="filters" method="GET">
                <select name="kelas"><option value="0">Kelas I - VI (Aktif)</option><?php foreach ($classes as $class): ?><option value="<?= (int) $class['id'] ?>" <?= $selectedClass === (int) $class['id'] ? 'selected' : '' ?>>Kelas <?= e($class['nama_kelas']) ?></option><?php endforeach; ?></select>
                <select name="status"><option value="">Semua Status</option><option value="Gizi Baik" <?= $selectedStatus === 'Gizi Baik' ? 'selected' : '' ?>>Gizi Baik</option><option value="Berisiko (Kurang)" <?= $selectedStatus === 'Berisiko (Kurang)' ? 'selected' : '' ?>>Berisiko</option><option value="Masalah Gizi" <?= $selectedStatus === 'Masalah Gizi' ? 'selected' : '' ?>>Masalah Gizi</option></select>
                <select name="gender"><option value="">Semua L/P</option><option value="L" <?= $selectedGender === 'L' ? 'selected' : '' ?>>Laki-laki</option><option value="P" <?= $selectedGender === 'P' ? 'selected' : '' ?>>Perempuan</option></select>
                <input name="q" type="search" value="<?= e($search) ?>" placeholder="⌕  Cari nama siswa atau NISN..."><button type="submit" class="filter-button">Terapkan</button><button type="button" class="export-button" onclick="window.print()">⇩ &nbsp;Export Data</button><a class="add-button" href="tambah.php">＋ &nbsp;Tambah Siswa</a>
            </form>
            <div class="table-card">
                <table>
                    <thead><tr><th>No</th><th>NISN</th><th>Nama Siswa</th><th>L/P</th><th>Usia</th><th>Berat (BB)</th><th>Tinggi (TB)</th><th>IMT / Z-Score</th><th>Status Gizi</th><th>Aksi</th></tr></thead>
                    <tbody>
                    <?php foreach ($students as $number => $student): $age = $student['tanggal_lahir'] ? date_diff(date_create($student['tanggal_lahir']), date_create('today'))->y : '-'; ?>
                        <tr><td><?= $number + 1 ?></td><td class="nisn"><?= e($student['nisn']) ?></td><td class="student-name"><b><?= e($student['nama']) ?></b><small>Kelas <?= e($student['nama_kelas']) ?></small></td><td><?= e($student['jenis_kelamin']) ?></td><td><?= $age ?> th</td><td><?= $student['berat_badan'] !== null ? e((string) $student['berat_badan']) . ' kg' : '-' ?></td><td><?= $student['tinggi_badan'] !== null ? e((string) $student['tinggi_badan']) . ' cm' : '-' ?></td><td><?= $student['imt'] !== null ? e((string) $student['imt']) : '-' ?><small><?= $student['z_score'] !== null ? ' (' . e((string) $student['z_score']) . ' SD)' : '' ?></small></td><td><?php if ($student['status_gizi']): ?><span class="status <?= statusClass($student['status_gizi']) ?>"><?= e($student['status_gizi']) ?></span><?php else: ?><span class="status status-empty">Belum diukur</span><?php endif; ?></td><td class="actions"><a href="index.php?modal=edit&id=<?= (int) $student['id'] ?>">✎</a><a href="index.php?modal=hapus&id=<?= (int) $student['id'] ?>">⊗</a></td></tr>
                    <?php endforeach; ?>
                    <?php if (!$students): ?><tr><td colspan="10" class="empty">Belum ada data siswa yang sesuai.</td></tr><?php endif; ?>
                    </tbody>
                </table>
                <div class="table-footer">Menampilkan <b><?= count($students) ?></b> siswa dari <?= $totalRegistered ?> siswa</div>
            </div>
            <div class="recommendation"><span>◷</span><div><b>Rekomendasi Penanganan Kelas</b><p>Tambahkan data siswa dan pengukuran untuk mendapatkan rekomendasi pendampingan gizi.</p></div><a href="tambah.php">Unduh Rekap Intervensi</a></div>
        </main>
    </div>
</div>
<?php if ($modalStudent): ?>
<div class="inline-modal-overlay"><div class="inline-modal <?= $modalType === 'hapus' ? 'delete-modal' : '' ?>">
    <?php if ($modalType === 'hapus'): ?>
        <div class="inline-modal-head"><span class="modal-danger">!</span><div><h2>Hapus Data Siswa?</h2><p>Data pengukuran siswa juga akan ikut dihapus.</p></div><a href="index.php">×</a></div>
        <div class="delete-content"><p>Anda akan menghapus:</p><strong><?= e($modalStudent['nama']) ?></strong><small>NISN: <?= e($modalStudent['nisn']) ?></small><p class="delete-warning">Tindakan ini tidak dapat dibatalkan.</p></div>
        <form class="inline-modal-foot" method="POST" action="hapus.php"><input type="hidden" name="id" value="<?= (int) $modalStudent['id'] ?>"><a href="index.php">Batal</a><button class="delete-button" type="submit">Hapus Data</button></form>
    <?php else: ?>
        <div class="inline-modal-head"><span class="modal-icon">✎</span><div><h2>Edit Data Siswa</h2><p>Perbarui identitas dan pengukuran terbaru siswa.</p></div><a href="index.php">×</a></div>
        <form method="POST" action="edit.php"><input type="hidden" name="id" value="<?= (int) $modalStudent['id'] ?>"><div class="inline-form-grid"><label>NISN<input name="nisn" value="<?= e($modalStudent['nisn']) ?>" required></label><label>Nama Lengkap<input name="nama" value="<?= e($modalStudent['nama']) ?>" required></label><label>Jenis Kelamin<select name="jenis_kelamin"><option value="L" <?= $modalStudent['jenis_kelamin'] === 'L' ? 'selected' : '' ?>>Laki-laki</option><option value="P" <?= $modalStudent['jenis_kelamin'] === 'P' ? 'selected' : '' ?>>Perempuan</option></select></label><label>Tanggal Lahir<input type="date" name="tanggal_lahir" value="<?= e($modalStudent['tanggal_lahir']) ?>" required></label><label>Kelas<select name="kelas_id" required><?php foreach ($classes as $class): ?><option value="<?= (int) $class['id'] ?>" <?= (int) $modalStudent['kelas_id'] === (int) $class['id'] ? 'selected' : '' ?>>Kelas <?= e($class['nama_kelas']) ?></option><?php endforeach; ?></select></label><label>Tanggal Pengukuran<input type="date" name="tanggal_pengukuran" value="<?= e($modalStudent['tanggal_pengukuran'] ?? date('Y-m-d')) ?>" required></label><label>Berat Badan (kg)<input type="number" step="0.1" name="berat_badan" value="<?= e((string) ($modalStudent['berat_badan'] ?? '')) ?>" required></label><label>Tinggi Badan (cm)<input type="number" step="0.1" name="tinggi_badan" value="<?= e((string) ($modalStudent['tinggi_badan'] ?? '')) ?>" required></label></div><div class="inline-modal-foot"><a href="index.php">Batal</a><button type="submit">✓ Simpan Perubahan</button></div></form>
    <?php endif; ?>
</div></div>
<?php endif; ?>
</body>
</html>
