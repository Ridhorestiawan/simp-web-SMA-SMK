-- --------------------------------------------------------
-- Database: simp_web
-- Sistem Informasi Manajemen Pembelajaran Berbasis Web
-- SMA/SMK — Admin, Guru, Siswa
-- --------------------------------------------------------

CREATE DATABASE IF NOT EXISTS simpweb CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;
USE simpweb;

-- --------------------------------------------------------
-- Tabel: users
-- Menyimpan akun Admin, Guru, dan Siswa
-- --------------------------------------------------------
CREATE TABLE users (
    id          INT AUTO_INCREMENT PRIMARY KEY,
    nama        VARCHAR(100) NOT NULL,
    email       VARCHAR(100) NOT NULL UNIQUE,
    password    VARCHAR(255) NOT NULL,
    role        ENUM('admin','guru','siswa') NOT NULL,
    foto        VARCHAR(255) DEFAULT NULL,
    updated_at  TIMESTAMP NULL ON UPDATE CURRENT_TIMESTAMP,
    created_at  TIMESTAMP DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------
-- Tabel: kelas
-- Data kelas dengan wali kelas (referensi ke users role=guru)
-- --------------------------------------------------------
CREATE TABLE kelas (
    id_kelas    INT AUTO_INCREMENT PRIMARY KEY,
    nama_kelas  VARCHAR(50) NOT NULL,
    wali_kelas  INT,
    FOREIGN KEY (wali_kelas) REFERENCES users(id) ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------
-- Tabel: kelas_siswa
-- Relasi many-to-many antara siswa dan kelas
-- --------------------------------------------------------
CREATE TABLE kelas_siswa (
    id          INT AUTO_INCREMENT PRIMARY KEY,
    id_kelas    INT NOT NULL,
    id_siswa    INT NOT NULL,
    FOREIGN KEY (id_kelas) REFERENCES kelas(id_kelas) ON DELETE CASCADE,
    FOREIGN KEY (id_siswa) REFERENCES users(id) ON DELETE CASCADE,
    UNIQUE KEY unique_siswa_kelas (id_kelas, id_siswa)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------
-- Tabel: materi
-- File materi yang diupload oleh Guru
-- --------------------------------------------------------
CREATE TABLE materi (
    id_materi       INT AUTO_INCREMENT PRIMARY KEY,
    id_guru         INT NOT NULL,
    id_kelas        INT NOT NULL,
    judul           VARCHAR(150) NOT NULL,
    deskripsi       TEXT,
    file            VARCHAR(255) NOT NULL,
    tanggal_upload  DATETIME DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (id_guru)  REFERENCES users(id) ON DELETE CASCADE,
    FOREIGN KEY (id_kelas) REFERENCES kelas(id_kelas) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------
-- Tabel: tugas
-- Tugas yang dibuat oleh Guru dengan deadline
-- --------------------------------------------------------
CREATE TABLE tugas (
    id_tugas    INT AUTO_INCREMENT PRIMARY KEY,
    id_guru     INT NOT NULL,
    id_kelas    INT NOT NULL,
    judul       VARCHAR(150) NOT NULL,
    deskripsi   TEXT,
    file        VARCHAR(255) DEFAULT NULL,
    deadline    DATETIME NOT NULL,
    created_at  TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (id_guru)  REFERENCES users(id) ON DELETE CASCADE,
    FOREIGN KEY (id_kelas) REFERENCES kelas(id_kelas) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------
-- Tabel: pengumpulan_tugas
-- Jawaban tugas yang dikumpulkan siswa + nilai dari guru
-- --------------------------------------------------------
CREATE TABLE pengumpulan_tugas (
    id_pengumpulan  INT AUTO_INCREMENT PRIMARY KEY,
    id_tugas        INT NOT NULL,
    id_siswa        INT NOT NULL,
    file            VARCHAR(255),
    nilai           FLOAT DEFAULT NULL,
    catatan_guru    TEXT DEFAULT NULL,
    status          ENUM('belum','dikumpulkan','dinilai') DEFAULT 'belum',
    waktu_kumpul    DATETIME,
    FOREIGN KEY (id_tugas)  REFERENCES tugas(id_tugas) ON DELETE CASCADE,
    FOREIGN KEY (id_siswa)  REFERENCES users(id) ON DELETE CASCADE,
    UNIQUE KEY unique_tugas_siswa (id_tugas, id_siswa)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------
-- Tabel: absensi
-- Sesi absensi per kelas per tanggal
-- --------------------------------------------------------
CREATE TABLE absensi (
    id_absensi  INT AUTO_INCREMENT PRIMARY KEY,
    id_kelas    INT NOT NULL,
    id_guru     INT NOT NULL,
    tanggal     DATE NOT NULL,
    FOREIGN KEY (id_kelas) REFERENCES kelas(id_kelas) ON DELETE CASCADE,
    FOREIGN KEY (id_guru)  REFERENCES users(id) ON DELETE CASCADE,
    UNIQUE KEY unique_kelas_tanggal (id_kelas, tanggal)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------
-- Tabel: absensi_detail
-- Status kehadiran setiap siswa dalam sesi absensi
-- --------------------------------------------------------
CREATE TABLE absensi_detail (
    id_detail   INT AUTO_INCREMENT PRIMARY KEY,
    id_absensi  INT NOT NULL,
    id_siswa    INT NOT NULL,
    status      ENUM('hadir','izin','sakit','alfa') DEFAULT 'alfa',
    FOREIGN KEY (id_absensi) REFERENCES absensi(id_absensi) ON DELETE CASCADE,
    FOREIGN KEY (id_siswa)   REFERENCES users(id) ON DELETE CASCADE,
    UNIQUE KEY unique_absensi_siswa (id_absensi, id_siswa)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------
-- Seed Data: Akun Admin Default
-- Password: admin123 (bcrypt hash)
-- --------------------------------------------------------
INSERT INTO users (nama, email, password, role) VALUES
('Administrator', 'admin@simp.web', '$2y$10$WdMtrJz2xlRCsWj3aip8eewaNrCXHkST9yPPneTiIOz4FNIoWXwxa', 'admin'),
('Ridho', 'ridho@simp.web', '$2y$10$dtkQEfQOKY4XCyiiQG9g5eyLIneASJ4PaFInGqRLSx29i8c7uIjYq', 'admin');

-- --------------------------------------------------------
-- Seed Data: Kelas Default
-- --------------------------------------------------------
INSERT INTO kelas (nama_kelas) VALUES
('X'),
('XI'),
('XII');
