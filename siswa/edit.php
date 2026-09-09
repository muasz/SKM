<?php

session_start();

if (!isset($_SESSION['admin_id'])) {
    header('Location: ../login.php');
    exit;
}

require_once __DIR__ . '/../config/db.php';

$id = (int) ($_GET['id'] ?? $_POST['id'] ?? 0);
$error = '';
$student = null;
$classes = [];
$classResult = mysqli_query($conn, 'SELECT id, nama_kelas FROM kelas ORDER BY id');
if ($classResult) while ($row = mysqli_fetch_assoc($classResult)) $classes[] = $row;

$studentStatement = mysqli_prepare($conn, "SELECT s.*, latest.id AS pengukuran_id, latest.tanggal_pengukuran, latest.berat_badan, latest.tinggi_badan, latest.imt, latest.status_gizi FROM siswa s LEFT JOIN pengukuran latest ON latest.id = (SELECT p.id FROM pengukuran p WHERE p.siswa_id = s.id ORDER BY p.tanggal_pengukuran DESC, p.id DESC LIMIT 1) WHERE s.id = ?");
mysqli_stmt_bind_param($studentStatement, 'i', $id);
mysqli_stmt_execute($studentStatement);
$studentResult = mysqli_stmt_get_result($studentStatement);
$student = mysqli_fetch_assoc($studentResult);

if (!$student) {
    header('Location: index.php');
    exit;
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $nisn = trim($_POST['nisn'] ?? '');
    $nama = trim($_POST['nama'] ?? '');
    $gender = $_POST['jenis_kelamin'] ?? 'L';
    $birthDate = $_POST['tanggal_lahir'] ?? '';
    $classId = (int) ($_POST['kelas_id'] ?? 0);
    $measureDate = $_POST['tanggal_pengukuran'] ?? date('Y-m-d');
    $weight = (float) ($_POST['berat_badan'] ?? 0);
    $height = (float) ($_POST['tinggi_badan'] ?? 0);
    $imt = $height > 0 ? round($weight / (($height / 100) ** 2), 2) : 0;

    if (!$nisn || !$nama || !$birthDate || !$classId || $weight <= 0 || $height <= 0) {
        $error = 'Lengkapi semua data wajib dan pastikan berat serta tinggi lebih dari 0.';
    } elseif ($imt < 14.5) {
        $status = 'Berisiko (Kurang)';
    } elseif ($imt >= 22) {
        $status = 'Berisiko (Lebih)';
    } else {
        $status = 'Gizi Baik';
    }

    if (!$error) {
        $duplicate = mysqli_prepare($conn, 'SELECT id FROM siswa WHERE nisn = ? AND id <> ? LIMIT 1');
        mysqli_stmt_bind_param($duplicate, 'si', $nisn, $id);
        mysqli_stmt_execute($duplicate);
        if (mysqli_stmt_get_result($duplicate)->num_rows > 0) {
            $error = 'NISN sudah digunakan siswa lain.';
        }
    }

    if (!$error) {
        mysqli_begin_transaction($conn);
        try {
            $updateStudent = mysqli_prepare($conn, 'UPDATE siswa SET nisn = ?, nama = ?, jenis_kelamin = ?, tanggal_lahir = ?, kelas_id = ? WHERE id = ?');
            mysqli_stmt_bind_param($updateStudent, 'ssssii', $nisn, $nama, $gender, $birthDate, $classId, $id);
            if (!mysqli_stmt_execute($updateStudent)) throw new RuntimeException(mysqli_stmt_error($updateStudent));

            if ($student['pengukuran_id']) {
                $updateMeasurement = mysqli_prepare($conn, 'UPDATE pengukuran SET tanggal_pengukuran = ?, berat_badan = ?, tinggi_badan = ?, imt = ?, status_gizi = ? WHERE id = ?');
                mysqli_stmt_bind_param($updateMeasurement, 'sdddsi', $measureDate, $weight, $height, $imt, $status, $student['pengukuran_id']);
                if (!mysqli_stmt_execute($updateMeasurement)) throw new RuntimeException(mysqli_stmt_error($updateMeasurement));
            }
            mysqli_commit($conn);
            header('Location: index.php?success=edit');
            exit;
        } catch (Throwable $exception) {
            mysqli_rollback($conn);
            $error = $exception->getMessage();
        }
    }

    $student = array_merge($student, ['nisn' => $nisn, 'nama' => $nama, 'jenis_kelamin' => $gender, 'tanggal_lahir' => $birthDate, 'kelas_id' => $classId, 'tanggal_pengukuran' => $measureDate, 'berat_badan' => $weight, 'tinggi_badan' => $height, 'imt' => $imt]);
}

function e(string $value): string { return htmlspecialchars($value, ENT_QUOTES, 'UTF-8'); }
?>
<!DOCTYPE html>
<html lang="id"><head><meta charset="UTF-8"><meta name="viewport" content="width=device-width, initial-scale=1.0"><title>Edit Siswa - SISGIZI</title><link rel="preconnect" href="https://fonts.googleapis.com"><link rel="preconnect" href="https://fonts.gstatic.com" crossorigin><link href="https://fonts.googleapis.com/css2?family=DM+Sans:wght@400;500;600;700&display=swap" rel="stylesheet"><link rel="stylesheet" href="../assets/css/tambah-siswa.css"></head>
<body><div class="backdrop"><main class="modal"><header class="modal-head"><div class="title-icon">✎</div><div><h1>Edit Data Siswa</h1><p>Perbarui identitas dan pengukuran antropometri siswa.</p></div><a class="close" href="index.php">×</a></header><?php if ($error): ?><div class="error"><?= e($error) ?></div><?php endif; ?><form method="POST"><input type="hidden" name="id" value="<?= $id ?>"><section class="form-section"><h2><b>1.</b> IDENTITAS SISWA</h2><div class="form-grid"><label>NISN <em>*</em><input name="nisn" value="<?= e($student['nisn']) ?>" required></label><label>Nama Lengkap Siswa <em>*</em><input name="nama" value="<?= e($student['nama']) ?>" required></label><label>Jenis Kelamin <em>*</em><span class="gender"><label><input type="radio" name="jenis_kelamin" value="L" <?= $student['jenis_kelamin'] === 'L' ? 'checked' : '' ?>> Laki-laki (L)</label><label><input type="radio" name="jenis_kelamin" value="P" <?= $student['jenis_kelamin'] === 'P' ? 'checked' : '' ?>> Perempuan (P)</label></span></label><label>Tanggal Lahir <em>*</em><input type="date" name="tanggal_lahir" value="<?= e($student['tanggal_lahir']) ?>" required></label><label class="full">Kelas Terdaftar <em>*</em><select name="kelas_id" required><?php foreach ($classes as $class): ?><option value="<?= (int) $class['id'] ?>" <?= (int) $student['kelas_id'] === (int) $class['id'] ? 'selected' : '' ?>>Kelas <?= e($class['nama_kelas']) ?></option><?php endforeach; ?></select></label></div></section><section class="form-section"><div class="section-title"><h2><b>2.</b> PENGUKURAN ANTROPOMETRI</h2><span>Pengukuran terbaru</span></div><div class="form-grid three"><label>Tanggal Pengukuran <em>*</em><input type="date" name="tanggal_pengukuran" value="<?= e($student['tanggal_pengukuran'] ?? date('Y-m-d')) ?>" required></label><label>Berat Badan (BB) <em>*</em><div class="unit-input"><input name="berat_badan" type="number" step="0.1" value="<?= e((string) ($student['berat_badan'] ?? '')) ?>" required><span>kg</span></div></label><label>Tinggi Badan (TB) <em>*</em><div class="unit-input"><input name="tinggi_badan" type="number" step="0.1" value="<?= e((string) ($student['tinggi_badan'] ?? '')) ?>" required><span>cm</span></div></label></div><div class="calculation"><div class="calc-head"><b>▣ &nbsp; Kalkulasi Otomatis Status Gizi</b><span><?= e($student['status_gizi'] ?? 'Gizi Baik') ?></span></div><div class="calc-grid"><div>Indeks Massa Tubuh (IMT)<strong><?= e((string) ($student['imt'] ?? '-')) ?></strong><small>kg/m²</small></div><div>Z-Score IMT/U<strong>0.0 SD</strong><small>Normal</small></div></div></div></section><footer class="modal-foot"><a href="index.php">Batal</a><button type="submit">✓ &nbsp; Simpan Perubahan</button></footer></form></main></div></body></html>
