<?php

session_start();

if (!isset($_SESSION['admin_id'])) {
    header('Location: ../login.php');
    exit;
}

require_once __DIR__ . '/../config/db.php';

$classes = [];
$classResult = mysqli_query($conn, 'SELECT id, nama_kelas FROM kelas ORDER BY id');
if ($classResult) {
    while ($row = mysqli_fetch_assoc($classResult)) $classes[] = $row;
}

$latestPeriod = null;
$periodResult = mysqli_query($conn, 'SELECT id, nama_periode, tanggal_selesai FROM periode_pengukuran ORDER BY tanggal_mulai DESC, id DESC LIMIT 1');
if ($periodResult) $latestPeriod = mysqli_fetch_assoc($periodResult);

$error = '';
$old = [
    'nisn' => '', 'nama' => '', 'jenis_kelamin' => 'L', 'tanggal_lahir' => '',
    'kelas_id' => '', 'tanggal_pengukuran' => $latestPeriod['tanggal_selesai'] ?? date('Y-m-d'), 'berat_badan' => '', 'tinggi_badan' => '',
];

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    foreach ($old as $field => $value) $old[$field] = trim($_POST[$field] ?? $value);
    $weight = (float) $old['berat_badan'];
    $height = (float) $old['tinggi_badan'];
    $imt = $height > 0 ? round($weight / (($height / 100) ** 2), 2) : 0;

    if (!$latestPeriod) {
        $error = 'Belum ada periode pengukuran. Buat periode terlebih dahulu.';
    } elseif (!$old['nisn'] || !$old['nama'] || !$old['tanggal_lahir'] || !$old['kelas_id'] || $weight <= 0 || $height <= 0) {
        $error = 'Lengkapi semua data wajib dan pastikan berat serta tinggi lebih dari 0.';
    } elseif ($imt < 14.5) {
        $status = 'Berisiko (Kurang)';
    } elseif ($imt >= 22) {
        $status = 'Berisiko (Lebih)';
    } else {
        $status = 'Gizi Baik';
    }

    if (!$error) {
        mysqli_begin_transaction($conn);
        try {
            $studentStatement = mysqli_prepare($conn, 'INSERT INTO siswa (nisn, nama, jenis_kelamin, tanggal_lahir, kelas_id) VALUES (?, ?, ?, ?, ?)');
            $classId = (int) $old['kelas_id'];
            mysqli_stmt_bind_param($studentStatement, 'ssssi', $old['nisn'], $old['nama'], $old['jenis_kelamin'], $old['tanggal_lahir'], $classId);
            if (!mysqli_stmt_execute($studentStatement)) throw new RuntimeException(mysqli_stmt_errno($studentStatement) === 1062 ? 'NISN sudah terdaftar.' : mysqli_stmt_error($studentStatement));
            $studentId = mysqli_insert_id($conn);

            $measurementStatement = mysqli_prepare($conn, 'INSERT INTO pengukuran (siswa_id, periode_id, tanggal_pengukuran, berat_badan, tinggi_badan, imt, z_score, status_gizi, catatan) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?)');
            $periodId = (int) $latestPeriod['id'];
            $zScore = 0.00;
            $note = 'Data ditambahkan melalui formulir siswa baru.';
            mysqli_stmt_bind_param($measurementStatement, 'iisddddss', $studentId, $periodId, $old['tanggal_pengukuran'], $weight, $height, $imt, $zScore, $status, $note);
            if (!mysqli_stmt_execute($measurementStatement)) throw new RuntimeException(mysqli_stmt_error($measurementStatement));

            mysqli_commit($conn);
            header('Location: index.php?success=1');
            exit;
        } catch (Throwable $exception) {
            mysqli_rollback($conn);
            $error = $exception->getMessage();
        }
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
    <title>Tambah Siswa - SISGIZI</title>
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=DM+Sans:wght@400;500;600;700&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="../assets/css/tambah-siswa.css">
</head>
<body>
<div class="backdrop"><iframe class="page-preview" src="index.php" title="Halaman Data Siswa"></iframe><main class="modal">
    <header class="modal-head"><div class="title-icon">♧</div><div><h1>Tambah Data Siswa Baru</h1><p>Masukkan data identitas siswa dan pengukuran antropometri berkala.</p></div><a class="close" href="index.php" aria-label="Tutup">×</a></header>
    <?php if ($error): ?><div class="error"><?= e($error) ?></div><?php endif; ?>
    <form method="POST" id="studentForm">
        <section class="form-section"><h2><b>1.</b> IDENTITAS SISWA</h2><div class="form-grid">
            <label>NISN <em>*</em><input name="nisn" value="<?= e($old['nisn']) ?>" required></label>
            <label>Nama Lengkap Siswa <em>*</em><input name="nama" value="<?= e($old['nama']) ?>" required></label>
            <label>Jenis Kelamin <em>*</em><span class="gender"><label><input type="radio" name="jenis_kelamin" value="L" <?= $old['jenis_kelamin'] === 'L' ? 'checked' : '' ?>> Laki-laki (L)</label><label><input type="radio" name="jenis_kelamin" value="P" <?= $old['jenis_kelamin'] === 'P' ? 'checked' : '' ?>> Perempuan (P)</label></span></label>
            <label>Tanggal Lahir <em>*</em><input type="date" name="tanggal_lahir" value="<?= e($old['tanggal_lahir']) ?>" required></label>
            <label class="full">Kelas Terdaftar <em>*</em><select name="kelas_id" required><option value="">Pilih kelas</option><?php foreach ($classes as $class): ?><option value="<?= (int) $class['id'] ?>" <?= (string) $old['kelas_id'] === (string) $class['id'] ? 'selected' : '' ?>>Kelas <?= e($class['nama_kelas']) ?></option><?php endforeach; ?></select></label>
        </div></section>
        <section class="form-section"><div class="section-title"><h2><b>2.</b> PENGUKURAN ANTROPOMETRI</h2><span><?= $latestPeriod ? e($latestPeriod['nama_periode']) : 'Periode belum tersedia' ?></span></div><div class="form-grid three">
            <label>Tanggal Pengukuran <em>*</em><input type="date" name="tanggal_pengukuran" value="<?= e($old['tanggal_pengukuran']) ?>" required></label>
            <label>Berat Badan (BB) <em>*</em><div class="unit-input"><input id="weight" name="berat_badan" type="number" step="0.1" min="0" value="<?= e($old['berat_badan']) ?>" required><span>kg</span></div></label>
            <label>Tinggi Badan (TB) <em>*</em><div class="unit-input"><input id="height" name="tinggi_badan" type="number" step="0.1" min="0" value="<?= e($old['tinggi_badan']) ?>" required><span>cm</span></div></label>
        </div><div class="calculation"><div class="calc-head"><b>▣ &nbsp; Kalkulasi Otomatis Status Gizi</b><span id="statusBadge">Gizi Baik (Normal)</span></div><div class="calc-grid"><div>Indeks Massa Tubuh (IMT)<strong id="imtValue">-</strong><small>kg/m²</small></div><div>Z-Score IMT/U<strong id="zValue">0.0 SD</strong><small>Normal</small></div></div><p>Kategori dihitung otomatis berdasarkan data berat dan tinggi badan.</p></div></section>
        <footer class="modal-foot"><a href="index.php">Batal</a><button type="submit">✓ &nbsp; Simpan Data Siswa</button></footer>
    </form>
</main></div>
<script>
    const weight = document.getElementById('weight');
    const height = document.getElementById('height');
    const imtValue = document.getElementById('imtValue');
    const statusBadge = document.getElementById('statusBadge');
    function calculate() { const w = parseFloat(weight.value); const h = parseFloat(height.value) / 100; if (!w || !h) { imtValue.textContent = '-'; return; } const imt = w / (h * h); imtValue.textContent = imt.toFixed(1); if (imt < 14.5) { statusBadge.textContent = 'Berisiko (Kurang)'; } else if (imt >= 22) { statusBadge.textContent = 'Berisiko (Lebih)'; } else { statusBadge.textContent = 'Gizi Baik (Normal)'; } }
    weight.addEventListener('input', calculate); height.addEventListener('input', calculate); calculate();
</script>
</body>
</html>
