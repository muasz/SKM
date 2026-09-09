<?php

session_start();

if (!isset($_SESSION['admin_id'])) {
    header('Location: ../login.php');
    exit;
}

require_once __DIR__ . '/../config/db.php';
$id = (int) ($_GET['id'] ?? $_POST['id'] ?? 0);
$studentStatement = mysqli_prepare($conn, 'SELECT nama, nisn FROM siswa WHERE id = ?');
mysqli_stmt_bind_param($studentStatement, 'i', $id);
mysqli_stmt_execute($studentStatement);
$student = mysqli_fetch_assoc(mysqli_stmt_get_result($studentStatement));
if (!$student) {
    header('Location: index.php');
    exit;
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $deleteStatement = mysqli_prepare($conn, 'DELETE FROM siswa WHERE id = ?');
    mysqli_stmt_bind_param($deleteStatement, 'i', $id);
    mysqli_stmt_execute($deleteStatement);
    header('Location: index.php?success=hapus');
    exit;
}

function e(string $value): string { return htmlspecialchars($value, ENT_QUOTES, 'UTF-8'); }
?>
<!DOCTYPE html>
<html lang="id"><head><meta charset="UTF-8"><meta name="viewport" content="width=device-width, initial-scale=1.0"><title>Hapus Siswa - SISGIZI</title><link rel="stylesheet" href="../assets/css/tambah-siswa.css"></head><body><div class="backdrop"><main class="modal confirm-modal"><header class="modal-head"><div class="title-icon danger">!</div><div><h1>Hapus Data Siswa?</h1><p>Data pengukuran siswa juga akan ikut dihapus.</p></div><a class="close" href="index.php">×</a></header><div class="confirm-body"><p>Anda akan menghapus:</p><strong><?= e($student['nama']) ?></strong><small>NISN: <?= e($student['nisn']) ?></small><p class="warning">Tindakan ini tidak dapat dibatalkan.</p></div><form method="POST" class="modal-foot"><input type="hidden" name="id" value="<?= $id ?>"><a href="index.php">Batal</a><button class="delete-button" type="submit">Hapus Data</button></form></main></div></body></html>
