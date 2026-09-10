<?php
session_start();

if (isset($_SESSION['admin_id'])) {
	header('Location: dashboard.php');
	exit;
}

$totalSiswa = 0;
$totalKelas = 0;
$totalPengukuran = 0;
$dbFile = __DIR__ . '/config/db.php';
if (is_file($dbFile)) {
	require $dbFile;
	if (isset($conn) && $conn instanceof mysqli) {
		$totalSiswa = (int) ($conn->query('SELECT COUNT(*) AS total FROM siswa')->fetch_assoc()['total'] ?? 0);
		$totalKelas = (int) ($conn->query('SELECT COUNT(*) AS total FROM kelas')->fetch_assoc()['total'] ?? 0);
		$totalPengukuran = (int) ($conn->query('SELECT COUNT(*) AS total FROM pengukuran')->fetch_assoc()['total'] ?? 0);
	}
}

$displaySiswa = $totalSiswa > 0 ? number_format($totalSiswa, 0, ',', '.') : '-';
$displayKelas = $totalKelas > 0 ? number_format($totalKelas, 0, ',', '.') : '-';
$displayPengukuran = $totalPengukuran > 0 ? number_format($totalPengukuran, 0, ',', '.') : '-';
$chartMax = max($totalSiswa, $totalKelas, $totalPengukuran, 1);
$chartSiswa = 18 + round(($totalSiswa / $chartMax) * 64);
$chartKelas = 18 + round(($totalKelas / $chartMax) * 64);
$chartPengukuran = 18 + round(($totalPengukuran / $chartMax) * 64);
$maleStudents = 0;
$femaleStudents = 0;
$goodResults = 0;
$riskResults = 0;
$problemResults = 0;
$classStats = [];
if (isset($conn) && $conn instanceof mysqli) {
	$maleStudents = (int) ($conn->query("SELECT COUNT(*) AS total FROM siswa WHERE jenis_kelamin = 'L'")->fetch_assoc()['total'] ?? 0);
	$femaleStudents = (int) ($conn->query("SELECT COUNT(*) AS total FROM siswa WHERE jenis_kelamin = 'P'")->fetch_assoc()['total'] ?? 0);
	$goodResults = (int) ($conn->query("SELECT COUNT(*) AS total FROM pengukuran WHERE status_gizi = 'Gizi Baik'")->fetch_assoc()['total'] ?? 0);
	$riskResults = (int) ($conn->query("SELECT COUNT(*) AS total FROM pengukuran WHERE status_gizi IN ('Berisiko (Kurang)', 'Berisiko (Lebih)')")->fetch_assoc()['total'] ?? 0);
	$problemResults = (int) ($conn->query("SELECT COUNT(*) AS total FROM pengukuran WHERE status_gizi = 'Masalah Gizi'")->fetch_assoc()['total'] ?? 0);
	$classResult = $conn->query("SELECT k.nama_kelas, COUNT(DISTINCT s.id) AS total_siswa, COUNT(p.id) AS total_pengukuran, SUM(p.status_gizi = 'Gizi Baik') AS baik, SUM(p.status_gizi IN ('Berisiko (Kurang)', 'Berisiko (Lebih)')) AS berisiko, SUM(p.status_gizi = 'Masalah Gizi') AS masalah FROM kelas k LEFT JOIN siswa s ON s.kelas_id = k.id LEFT JOIN pengukuran p ON p.siswa_id = s.id GROUP BY k.id, k.nama_kelas ORDER BY k.id");
	while ($row = $classResult->fetch_assoc()) {
		$classStats[] = $row;
	}
}
$resultTotal = max($goodResults + $riskResults + $problemResults, 1);
$goodPercent = round(($goodResults / $resultTotal) * 100, 1);
$riskPercent = round(($riskResults / $resultTotal) * 100, 1);
$problemPercent = round(($problemResults / $resultTotal) * 100, 1);
$genderTotal = max($maleStudents + $femaleStudents, 1);
$malePercent = round(($maleStudents / $genderTotal) * 100, 1);
$femalePercent = round(($femaleStudents / $genderTotal) * 100, 1);
?>
<!DOCTYPE html>
<html lang="id">
<head>
	<meta charset="UTF-8">
	<meta name="viewport" content="width=device-width, initial-scale=1.0">
	<title>SISGIZI | Pemantauan Status Gizi SD Patemon 02</title>
	<meta name="description" content="Informasi publik program pemantauan status gizi siswa SD Patemon 02.">
	<link rel="preconnect" href="https://fonts.googleapis.com">
	<link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
	<link href="https://fonts.googleapis.com/css2?family=DM+Sans:wght@400;500;600;700&family=Manrope:wght@600;700;800&display=swap" rel="stylesheet">
	<link rel="stylesheet" href="assets/css/public.css">
</head>
<body>
<header class="site-header">
	<a class="brand" href="index.php">
		<img src="assets/image/Logo Sekolah.jpg" alt="Logo SD Patemon 02">
		<span><strong>SISGIZI</strong><small>SD Patemon 02</small></span>
		<b>PUBLIC</b>
	</a>
	<nav aria-label="Navigasi utama">
		<a href="#beranda">Beranda</a>
		<a href="#statistik">Statistik Publik</a>
		<a href="#alur">Alur Pemeriksaan</a>
		<a href="#pemeriksaan">Yang Diperiksa</a>
		<a href="#panduan">Panduan Gizi Anak</a>
		<a href="#kontak">FAQ &amp; Kontak</a>
	</nav>
	<a class="header-action" href="login.php">Masuk Petugas / Guru <span aria-hidden="true">&#8594;</span></a>
</header>

<main>
	<section class="hero" id="beranda">
		<div class="hero-copy">
			<p class="eyebrow"><span></span> Standar WHO &middot; Kemenkes RI</p>
			<h1>Transparansi &amp; Pemantauan<br>Status Gizi Siswa <em>SD Patemon 02</em></h1>
			<p class="hero-lede">Mewujudkan generasi yang sehat, aktif, dan cerdas melalui evaluasi antropometri berkala. Akses terbuka bagi orang tua, guru, dan tenaga kesehatan untuk memahami status gizi bersama secara bertanggung jawab.</p>
			<div class="hero-actions"><a class="button primary" href="#statistik">Lihat Data Statistik Publik <span>&#8594;</span></a><a class="button quiet" href="#panduan">Panduan Gizi &amp; Prinsipku</a></div>
			<div class="hero-meta"><span><b>Mitra Medis</b>Puskesmas Patemon</span><span><b>Metode Pengukuran</b>IMT/U &amp; TBU Standar</span><span><b>Pembaruan Rutin</b>Semester Ganjil 2025</span></div>
		</div>
		<div class="status-panel">
			<div class="panel-heading"><span class="dot-label"><i></i> Status Publik Siswa</span><span>Data terkini</span></div>
			<div class="stat-ring"><div><strong><?= $displaySiswa ?></strong><small>Total Siswa</small></div></div>
			<div class="stat-list"><span><i class="green"></i> Dalam pemantauan <b><?= $displaySiswa ?></b></span><span><i class="yellow"></i> Kelas terdata <b><?= $displayKelas ?></b></span><span><i class="red"></i> Pengukuran tercatat <b><?= $displayPengukuran ?></b></span></div>
			<div class="mini-chart"><div class="chart-label"><strong>Cakupan data</strong><small>Data saat ini</small></div><div class="bars"><span style="height: <?= $chartSiswa ?>%"><b>Siswa</b></span><span style="height: <?= $chartKelas ?>%"><b>Kelas</b></span><span style="height: <?= $chartPengukuran ?>%"><b>Ukur</b></span></div></div>
			<p class="panel-note">Data disajikan dalam bentuk agregat untuk menjaga privasi seluruh siswa.</p>
		</div>
	</section>

	<section class="highlight"><div><span class="mini-icon">&#10003;</span><div><p>STATUS KESEHATAN SEKOLAH</p><strong>Pemantauan rutin, informasi terbuka</strong><small>Data kesehatan siswa dikelola oleh sekolah bersama tenaga kesehatan dan tidak menampilkan identitas individu.</small></div></div><a href="#alur">Pelajari alurnya <span>&#8594;</span></a></section>

	<section class="section public-stats" id="statistik"><div class="section-heading"><p class="eyebrow centered">Ringkasan data sekolah</p><h2>Statistik &amp; Pemantauan Status Gizi Siswa</h2><p>Data agregat terverifikasi untuk membantu orang tua memahami program tanpa membuka identitas siswa.</p></div><div class="metric-grid"><article><span class="metric-icon green">&#9673;</span><strong><?= $displaySiswa ?></strong><h3>Total siswa terdata</h3><p><?= number_format($maleStudents, 0, ',', '.') ?> laki-laki &middot; <?= number_format($femaleStudents, 0, ',', '.') ?> perempuan</p></article><article><span class="metric-icon blue">&#10003;</span><strong><?= $goodPercent ?>%</strong><h3>Gizi baik (normal)</h3><p><?= number_format($goodResults, 0, ',', '.') ?> hasil pengukuran berada pada kategori baik.</p></article><article><span class="metric-icon yellow">&#9888;</span><strong><?= $riskPercent ?>%</strong><h3>Perlu pendampingan</h3><p><?= number_format($riskResults, 0, ',', '.') ?> hasil perlu perhatian dan pemantauan.</p></article><article><span class="metric-icon red">&#10067;</span><strong><?= $problemPercent ?>%</strong><h3>Intervensi khusus</h3><p><?= number_format($problemResults, 0, ',', '.') ?> hasil memerlukan tindak lanjut petugas.</p></article></div><div class="stats-dashboard"><article class="public-chart"><div class="subheading"><h3>Proporsi status gizi siswa</h3><span>Data terbaru</span></div><div class="donut-row"><div class="large-donut" style="--good: <?= $goodPercent ?>%; --risk: <?= $riskPercent ?>%;"><strong><?= number_format($goodResults + $riskResults + $problemResults, 0, ',', '.') ?></strong><small>hasil ukur</small></div><div class="legend"><span><i class="green"></i> Gizi baik <b><?= $goodPercent ?>%</b></span><span><i class="yellow"></i> Berisiko <b><?= $riskPercent ?>%</b></span><span><i class="red"></i> Masalah gizi <b><?= $problemPercent ?>%</b></span></div></div></article><article class="public-chart"><div class="subheading"><h3>Komposisi siswa</h3><span><?= $displayKelas ?> kelas</span></div><div class="gender-bar"><span style="width: <?= $malePercent ?>%"></span><span style="width: <?= $femalePercent ?>%"></span></div><div class="gender-labels"><span>Laki-laki <b><?= number_format($maleStudents, 0, ',', '.') ?> (<?= $malePercent ?>%)</b></span><span>Perempuan <b><?= number_format($femaleStudents, 0, ',', '.') ?> (<?= $femalePercent ?>%)</b></span></div><p class="chart-note">Komposisi ditampilkan sebagai ringkasan agregat sekolah.</p></article></div><div class="class-table"><div class="subheading"><h3>Distribusi status gizi per jenjang kelas</h3><span>Tanpa identitas siswa</span></div><div class="class-head"><span>Kelas</span><span>Siswa</span><span>Gizi baik</span><span>Berisiko</span><span>Masalah</span><span>Proporsi</span></div><?php foreach ($classStats as $class): $classTotal = max((int) $class['total_pengukuran'], 1); $classGood = round(((int) $class['baik'] / $classTotal) * 100); ?><div class="class-row"><strong>Kelas <?= htmlspecialchars($class['nama_kelas'], ENT_QUOTES, 'UTF-8') ?></strong><span><?= (int) $class['total_siswa'] ?></span><span class="good-text"><?= (int) $class['baik'] ?></span><span class="risk-text"><?= (int) $class['berisiko'] ?></span><span class="problem-text"><?= (int) $class['masalah'] ?></span><span class="class-progress"><i style="width: <?= $classGood ?>%"></i></span></div><?php endforeach; ?></div><div class="stats-footnote"><strong>Catatan data:</strong> Statistik diperbarui setelah petugas menyimpan hasil pemeriksaan. Semua informasi individu tetap terlindungi.</div></section>

	<section class="section program" id="program"><div class="section-heading"><p class="eyebrow centered">Komitmen &amp; inisiatif sekolah</p><h2>3 Pilar Program Gizi &amp; Sekolah Sehat</h2><p>Berjalan bersama untuk mendukung lingkungan belajar yang sehat dan kesiapan anak menerima pelajaran.</p></div><div class="card-grid"><article><span class="card-icon mint">&#9829;</span><small>PILAR 01</small><h3>Sarapan Sehat Bersama</h3><p>Kebiasaan makan pagi yang membantu anak memulai aktivitas belajar dengan energi yang cukup.</p><b>&#10003; Menumbuhkan pola hidup sehat</b></article><article><span class="card-icon butter">&#9733;</span><small>PILAR 02</small><h3>Kantin Sehat</h3><p>Pilihan makanan dan minuman yang lebih aman, bersih, dan mendukung kebutuhan gizi anak.</p><b>&#10003; Edukasi pangan beragam</b></article><article><span class="card-icon aqua">&#10010;</span><small>PILAR 03</small><h3>Pemantauan Terpadu</h3><p>Pengukuran berkala dan tindak lanjut bersama sekolah, keluarga, serta tenaga kesehatan.</p><b>&#10003; Informasi terarah untuk orang tua</b></article></div></section>

	<section class="section flow" id="alur"><div class="section-heading"><p class="eyebrow centered">Transparansi operasional</p><h2>Alur Kerja &amp; Pengolahan Data di SISGIZI</h2><p>Proses terstruktur dari pengumpulan hingga informasi yang mudah dipahami.</p></div><div class="flow-grid"><div><span>01</span><strong>Pengumpulan Data</strong><small>Data antropometri siswa dicatat oleh petugas.</small></div><div><span>02</span><strong>Pengolahan Sistem</strong><small>Data dihitung dan disimpan secara terstruktur.</small></div><div><span>03</span><strong>Analisis &amp; Grafik</strong><small>Hasil dibaca sebagai informasi agregat.</small></div><div><span>04</span><strong>Output Laporan</strong><small>Ringkasan membantu evaluasi sekolah.</small></div><div><span>05</span><strong>Tindak Lanjut</strong><small>Temuan dibahas bersama keluarga.</small></div></div></section>

	<section class="section checkup" id="pemeriksaan"><div class="section-heading"><p class="eyebrow centered">Informasi untuk keluarga</p><h2>Apa yang diperiksa dalam pemantauan?</h2><p>Pemeriksaan dilakukan berkala oleh petugas untuk membantu sekolah memahami pertumbuhan siswa secara menyeluruh.</p></div><div class="info-grid"><article><span class="info-number">01</span><h3>Berat badan</h3><p>Dicatat dalam satuan kilogram sebagai salah satu indikator pertumbuhan anak.</p></article><article><span class="info-number">02</span><h3>Tinggi badan</h3><p>Diukur dalam satuan sentimeter dan dibandingkan dengan usia serta jenis kelamin.</p></article><article><span class="info-number">03</span><h3>IMT menurut umur</h3><p>Perhitungan membantu melihat kecenderungan status gizi secara lebih proporsional.</p></article><article><span class="info-number">04</span><h3>Tindak lanjut</h3><p>Hasil yang perlu perhatian dibicarakan bersama orang tua dan tenaga kesehatan.</p></article></div><p class="privacy-note"><strong>Privasi siswa dijaga.</strong> Halaman publik hanya menampilkan informasi agregat. Nama, NISN, tanggal lahir, dan hasil individu hanya dapat diakses petugas yang berwenang.</p></section>

	<section class="section guide" id="panduan"><div class="guide-copy"><p class="eyebrow">Panduan untuk orang tua</p><h2>Prinsip “Isi Piringku” untuk Anak Usia Sekolah Dasar</h2><p>Mulai dari kebiasaan sederhana: porsi seimbang, sarapan cukup, dan air minum yang selalu tersedia.</p><div class="tips"><span><b>1/2</b> Makanan pokok &amp; sayur</span><span><b>1/3</b> Sumber protein</span><span><b>1/4</b> Buah &amp; air putih</span><span><b>1/4</b> Batasi gula, garam, lemak</span></div></div><aside><p class="eyebrow">Agenda UKS</p><h3>Jadwal pengukuran berikutnya</h3><strong class="date">15 <small>OCT<br>2025</small></strong><p>Pemeriksaan antropometri semester I untuk seluruh siswa.</p><a href="login.php">Akses informasi petugas <span>&#8594;</span></a></aside></section>

	<section class="section faq" id="kontak"><div class="section-heading"><p class="eyebrow centered">Bantuan &amp; komunikasi</p><h2>Pertanyaan yang sering ditanyakan</h2><p>Informasi singkat untuk membantu orang tua mengikuti program dengan tenang.</p></div><div class="faq-grid"><details open><summary>Apakah hasil anak dapat dilihat publik?</summary><p>Tidak. Publik hanya melihat ringkasan tanpa identitas. Hasil individu disampaikan melalui jalur sekolah dan petugas yang berwenang.</p></details><details><summary>Kapan pemeriksaan dilakukan?</summary><p>Pemeriksaan dilakukan berkala sesuai agenda sekolah dan koordinasi dengan tenaga kesehatan. Informasi jadwal dapat ditanyakan kepada wali kelas.</p></details><details><summary>Apa yang perlu disiapkan orang tua?</summary><p>Pastikan anak sarapan, cukup minum, dan hadir sesuai jadwal. Sampaikan informasi kesehatan penting kepada wali kelas atau petugas.</p></details><div class="contact-box"><p class="eyebrow">Hubungi sekolah</p><h3>Butuh informasi lebih lanjut?</h3><p>Silakan hubungi wali kelas atau UKS SD Patemon 02 untuk pertanyaan tentang jadwal dan tindak lanjut.</p><a href="login.php">Akses petugas sekolah <span>&#8594;</span></a></div></div></section>
</main>

<footer><div><strong>SISGIZI SD Patemon 02</strong><p>Sistem informasi status gizi siswa yang terbuka, aman, dan bermanfaat bagi komunitas sekolah.</p></div><div><b>LOKASI SEKOLAH</b><p>SD Patemon 02<br>Desa Patemon, Kecamatan Boja</p></div><div><b>TAUTAN NAVIGASI</b><p><a href="#statistik">Statistik publik</a><br><a href="#pemeriksaan">Pemeriksaan</a><br><a href="#kontak">Kontak sekolah</a></p></div></footer>
</body>
</html>
