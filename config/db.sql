CREATE DATABASE sisgizi;

USE sisgizi;

CREATE TABLE users (
    id INT AUTO_INCREMENT PRIMARY KEY,
    username VARCHAR(50) NOT NULL UNIQUE,
    password VARCHAR(255) NOT NULL,
    nama VARCHAR(100) NOT NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
);

INSERT INTO users (username, password, nama)
VALUES ('admin', '$2y$10$QfbybaVY65Q.NH9lao4DGuKU0n5Bb4Jf3qO4lUcDZWDIy7ml6NPrG', 'Administrator');

CREATE TABLE kelas (
    id INT AUTO_INCREMENT PRIMARY KEY,
    nama_kelas VARCHAR(20) NOT NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
);

INSERT INTO kelas (nama_kelas) VALUES
('I'),
('II'),
('III'),
('IV'),
('V'),
('VI');

CREATE TABLE siswa (
    id INT AUTO_INCREMENT PRIMARY KEY,
    nisn VARCHAR(20) NOT NULL UNIQUE,
    nama VARCHAR(100) NOT NULL,
    jenis_kelamin ENUM('L','P') NOT NULL,
    tanggal_lahir DATE NOT NULL,
    kelas_id INT NOT NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,

    FOREIGN KEY (kelas_id) REFERENCES kelas(id)
        ON UPDATE CASCADE
        ON DELETE RESTRICT
);

CREATE TABLE periode_pengukuran (
    id INT AUTO_INCREMENT PRIMARY KEY,
    nama_periode VARCHAR(100) NOT NULL,
    tanggal_mulai DATE NOT NULL,
    tanggal_selesai DATE NOT NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
);


CREATE TABLE pengukuran (
    id INT AUTO_INCREMENT PRIMARY KEY,
    siswa_id INT NOT NULL,
    periode_id INT NOT NULL,

    tanggal_pengukuran DATE NOT NULL,
    berat_badan DECIMAL(5,2) NOT NULL,
    tinggi_badan DECIMAL(5,2) NOT NULL,

    imt DECIMAL(5,2),
    z_score DECIMAL(5,2),

    status_gizi ENUM(
        'Gizi Baik',
        'Berisiko (Kurang)',
        'Berisiko (Lebih)',
        'Masalah Gizi'
    ) NOT NULL,

    catatan TEXT,

    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,

    FOREIGN KEY (siswa_id) REFERENCES siswa(id)
        ON UPDATE CASCADE
        ON DELETE CASCADE,

    FOREIGN KEY (periode_id) REFERENCES periode_pengukuran(id)
        ON UPDATE CASCADE
        ON DELETE CASCADE
);

CREATE TABLE intervensi (
    id INT AUTO_INCREMENT PRIMARY KEY,
    pengukuran_id INT NOT NULL,
    jenis_intervensi VARCHAR(100) NOT NULL,
    keterangan TEXT,
    tanggal DATE,
    status ENUM('Belum', 'Proses', 'Selesai') DEFAULT 'Belum',

    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,

    FOREIGN KEY (pengukuran_id) REFERENCES pengukuran(id)
        ON UPDATE CASCADE
        ON DELETE CASCADE
);