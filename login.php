<?php
session_start();

if (isset($_SESSION['admin_id'])) {
    header("Location: dashboard.php");
    exit;
}
?>

<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Masuk ke Akun - SISGIZI</title>
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=DM+Sans:wght@400;500;600;700&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="assets/css/login.css">
</head>
<body>
<main class="page">
    <section class="login-card">
        <div class="welcome-panel">
            <div class="brand">
                <img class="crest" src="assets/image/Logo Sekolah.jpg" alt="Logo SD Patemon 02">
                <div>
                    <h1>SISGIZI</h1>
                    <p>Sistem Informasi Status Gizi Siswa</p>
                    <small>SD Patemon 02</small>
                </div>
            </div>
            <p class="slogan">Data yang sehat,<strong>untuk generasi yang kuat</strong></p>
            <div class="scene" aria-hidden="true">
                <div class="sun"></div><div class="cloud one"></div><div class="cloud two"></div>
                <div class="hill"></div>
                <div class="children"><img src="assets/image/anak kecil.png" alt="Ilustrasi anak sekolah"></div>
            </div>
        </div>
        <div class="form-panel">
            <div class="form-content">
                <h2>Masuk ke Akun</h2>
                <p class="intro">Silakan login untuk mengakses dashboard<br>analisis status gizi siswa.</p>
                <?php if (isset($_GET['error'])): ?>
                    <div class="error">Username atau password salah.</div>
                <?php endif; ?>
                <form action="proses/proses_login.php" method="POST">
                    <div class="field"><span aria-hidden="true">♙</span><input type="text" name="username" placeholder="Username" autocomplete="username" required></div>
                    <div class="field"><span aria-hidden="true">▣</span><input type="password" name="password" placeholder="Password" autocomplete="current-password" required></div>
                    <button type="submit">Login</button>
                </form>
                <a class="forgot" href="#">Lupa password?</a>
                <div class="security"><b>♧</b> Sistem Pemantauan Terpadu Patemon 02</div>
            </div>
        </div>
    </section>
    <p class="copyright">© 2026 SISGIZI SD Patemon 02. Hak Cipta Dilindungi Undang-Undang.</p>
</main>
</body>
</html>