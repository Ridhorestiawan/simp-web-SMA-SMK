# RANCANGAN LENGKAP SISTEM INFORMASI MANAJEMEN PEMBELAJARAN BERBASIS WEB (SIMP Web)

> Dibuat berdasarkan SRS v1.0 - Ridho Restiawan (2413025031)
> Pendidikan Teknologi Informasi 2024

---

## BAGIAN 1 : STRUKTUR FOLDER PROYEK

```
simp-web/
├── index.php                        # Entry point / redirect ke login
├── config/
│   └── database.php                 # Koneksi MySQL (PDO)
├── auth/
│   ├── login.php                    # Proses login & session
│   └── logout.php                   # Destroy session & redirect
├── assets/
│   ├── css/
│   │   └── style.css                # Stylesheet utama
│   ├── js/
│   │   └── main.js                  # Script global
│   └── uploads/
│       ├── materi/                  # File materi yang diupload guru
│       └── tugas/                   # File jawaban tugas siswa
├── pages/
│   ├── admin/
│   │   ├── dashboard.php
│   │   ├── kelola_kelas.php
│   │   ├── kelola_pengguna.php
│   │   └── laporan.php
│   ├── guru/
│   │   ├── dashboard.php
│   │   ├── upload_materi.php
│   │   ├── kelola_tugas.php
│   │   ├── input_nilai.php
│   │   ├── absensi.php
│   │   └── laporan.php
│   └── siswa/
│       ├── dashboard.php
│       ├── lihat_materi.php
│       ├── kumpul_tugas.php
│       └── lihat_nilai.php
├── includes/
│   ├── header.php                   # Navbar & session check
│   ├── footer.php
│   └── sidebar.php                  # Sidebar navigasi per role
└── sql/
    └── simp_web.sql                 # File dump database
```

---

## BAGIAN 2 : SKEMA DATABASE LENGKAP (MySQL)

```sql
-- --------------------------------------------------------
-- Database: simp_web
-- --------------------------------------------------------

CREATE DATABASE IF NOT EXISTS simp_web CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;
USE simp_web;

-- Tabel: users
CREATE TABLE users (
    id          INT AUTO_INCREMENT PRIMARY KEY,
    nama        VARCHAR(100) NOT NULL,
    email       VARCHAR(100) NOT NULL UNIQUE,
    password    VARCHAR(255) NOT NULL,           -- bcrypt hash
    role        ENUM('admin','guru','siswa') NOT NULL,
    created_at  TIMESTAMP DEFAULT CURRENT_TIMESTAMP
);

-- Tabel: kelas
CREATE TABLE kelas (
    id_kelas    INT AUTO_INCREMENT PRIMARY KEY,
    nama_kelas  VARCHAR(50) NOT NULL,
    wali_kelas  INT,                             -- FK -> users.id (role guru)
    FOREIGN KEY (wali_kelas) REFERENCES users(id) ON DELETE SET NULL
);

-- Tabel: kelas_siswa (relasi many-to-many siswa & kelas)
CREATE TABLE kelas_siswa (
    id          INT AUTO_INCREMENT PRIMARY KEY,
    id_kelas    INT NOT NULL,
    id_siswa    INT NOT NULL,
    FOREIGN KEY (id_kelas) REFERENCES kelas(id_kelas) ON DELETE CASCADE,
    FOREIGN KEY (id_siswa) REFERENCES users(id) ON DELETE CASCADE
);

-- Tabel: materi
CREATE TABLE materi (
    id_materi       INT AUTO_INCREMENT PRIMARY KEY,
    id_guru         INT NOT NULL,
    id_kelas        INT NOT NULL,
    judul           VARCHAR(150) NOT NULL,
    deskripsi       TEXT,
    file            VARCHAR(255) NOT NULL,        -- path file upload
    tanggal_upload  DATETIME DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (id_guru)  REFERENCES users(id) ON DELETE CASCADE,
    FOREIGN KEY (id_kelas) REFERENCES kelas(id_kelas) ON DELETE CASCADE
);

-- Tabel: tugas
CREATE TABLE tugas (
    id_tugas    INT AUTO_INCREMENT PRIMARY KEY,
    id_guru     INT NOT NULL,
    id_kelas    INT NOT NULL,
    judul       VARCHAR(150) NOT NULL,
    deskripsi   TEXT,
    deadline    DATETIME NOT NULL,
    created_at  TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (id_guru)  REFERENCES users(id) ON DELETE CASCADE,
    FOREIGN KEY (id_kelas) REFERENCES kelas(id_kelas) ON DELETE CASCADE
);

-- Tabel: pengumpulan_tugas
CREATE TABLE pengumpulan_tugas (
    id_pengumpulan  INT AUTO_INCREMENT PRIMARY KEY,
    id_tugas        INT NOT NULL,
    id_siswa        INT NOT NULL,
    file            VARCHAR(255),                 -- path file jawaban
    nilai           FLOAT DEFAULT NULL,
    status          ENUM('belum','dikumpulkan','dinilai') DEFAULT 'belum',
    waktu_kumpul    DATETIME,
    FOREIGN KEY (id_tugas)  REFERENCES tugas(id_tugas) ON DELETE CASCADE,
    FOREIGN KEY (id_siswa)  REFERENCES users(id) ON DELETE CASCADE
);

-- Tabel: absensi (sesi per kelas per tanggal)
CREATE TABLE absensi (
    id_absensi  INT AUTO_INCREMENT PRIMARY KEY,
    id_kelas    INT NOT NULL,
    id_guru     INT NOT NULL,
    tanggal     DATE NOT NULL,
    FOREIGN KEY (id_kelas) REFERENCES kelas(id_kelas) ON DELETE CASCADE,
    FOREIGN KEY (id_guru)  REFERENCES users(id) ON DELETE CASCADE
);

-- Tabel: absensi_detail (status kehadiran tiap siswa)
CREATE TABLE absensi_detail (
    id_detail   INT AUTO_INCREMENT PRIMARY KEY,
    id_absensi  INT NOT NULL,
    id_siswa    INT NOT NULL,
    status      ENUM('hadir','izin','sakit','alfa') DEFAULT 'alfa',
    FOREIGN KEY (id_absensi) REFERENCES absensi(id_absensi) ON DELETE CASCADE,
    FOREIGN KEY (id_siswa)   REFERENCES users(id) ON DELETE CASCADE
);
```

---

## BAGIAN 3 : DESKRIPSI HALAMAN & FITUR PER ROLE

### [A] HALAMAN PUBLIK

#### 1. Halaman Login (login.php)

| Elemen | Deskripsi |
|--------|-----------|
| Form | email, password |
| Validasi | cek ke tabel users |
| Jika valid | set session (id, nama, role), redirect ke dashboard sesuai role |
| Jika tidak valid | tampilkan pesan error |
| Session keys | `$_SESSION['user_id']`, `$_SESSION['nama']`, `$_SESSION['role']` |

### [B] ADMIN

#### 1. Dashboard Admin

- Ringkasan statistik: total guru, total siswa, total kelas
- Aktivitas terbaru (log tugas / materi terbaru)

#### 2. Kelola Kelas

- Tampilkan daftar kelas (tabel: nama_kelas, wali_kelas)
- Tombol: Tambah Kelas, Edit, Hapus
- Form Tambah/Edit: nama_kelas, pilih wali_kelas (dropdown guru)
- Assign siswa ke kelas (checkbox list siswa)

#### 3. Kelola Akun Pengguna

- Tab: Guru | Siswa
- Tampilkan daftar sesuai role
- Tombol: Tambah, Edit, Hapus
- Form: nama, email, password (auto-hash bcrypt), role
- Reset password opsional saat edit

#### 4. Laporan

- Filter: pilih kelas, pilih periode (bulan/semester)
- Tampilkan rekap nilai per siswa per kelas
- Tampilkan rekap absensi per siswa per kelas
- Tombol: Export ke PDF / Print

### [C] GURU

#### 1. Dashboard Guru

- Menu cepat: Upload Materi, Buat Tugas, Absensi, Nilai, Laporan
- Daftar tugas aktif yang belum melewati deadline
- Notifikasi tugas yang sudah dikumpulkan siswa (belum dinilai)

#### 2. Upload Materi

- Form: judul, deskripsi, pilih kelas, upload file (PDF/PPT/Video)
- Validasi tipe file & ukuran (max sesuai konfigurasi server)
- Daftar materi yang sudah diupload (bisa edit/hapus)

#### 3. Kelola Tugas

- Form buat tugas: judul, deskripsi, pilih kelas, deadline (date-time picker)
- Daftar tugas: tampilkan judul, kelas, deadline, jumlah yang sudah kumpul
- Tombol: Edit, Hapus tugas

#### 4. Input Nilai

- Pilih tugas dari dropdown
- Tampilkan tabel: nama siswa, status pengumpulan, file, kolom input nilai
- Submit nilai -> update tabel pengumpulan_tugas (nilai, status='dinilai')

#### 5. Absensi Online

- Pilih kelas & tanggal
- Cek: apakah sesi absensi sudah dibuat hari ini (query ke tabel absensi)
- Jika belum: buat sesi baru, tampilkan daftar siswa dengan toggle status
- Status: Hadir / Izin / Sakit / Alfa (radio button per siswa)
- Submit -> simpan ke absensi_detail

#### 6. Laporan (Guru)

- Filter: pilih kelas
- Rekap nilai siswa per tugas
- Rekap absensi siswa per bulan
- Grafik sederhana: persentase kehadiran

### [D] SISWA

#### 1. Dashboard Siswa

- Daftar materi terbaru dari kelas siswa
- Daftar tugas aktif (belum dikumpulkan, belum deadline)
- Nilai terkini yang sudah diinput guru

#### 2. Lihat Materi

- Daftar materi sesuai kelas siswa (filter per mata pelajaran opsional)
- Tombol: Download / Lihat Preview

#### 3. Kumpul Tugas

- Daftar tugas yang harus dikumpulkan (beserta deadline & status)
- Klik tugas -> form upload jawaban (file PDF/Word/ZIP)
- Validasi: hanya bisa kumpul sebelum deadline
- Status berubah jadi 'dikumpulkan'

#### 4. Lihat Nilai

- Tabel: nama tugas, tanggal kumpul, nilai, status
- Jika belum dinilai: tampilkan "-"

---

## BAGIAN 4 : ALUR LOGIKA UTAMA (PSEUDOCODE)

### [1] Proses Login

```
START
  Buka halaman login
  Input email & password
  IF email kosong OR password kosong THEN
    Tampilkan error "Field tidak boleh kosong"
  ELSE
    Query: SELECT * FROM users WHERE email = input_email
    IF user tidak ditemukan THEN
      Tampilkan error "Email tidak terdaftar"
    ELSE
      Verifikasi password_hash(input_password) == users.password
      IF tidak cocok THEN
        Tampilkan error "Password salah"
      ELSE
        Set $_SESSION['user_id'] = users.id
        Set $_SESSION['nama']    = users.nama
        Set $_SESSION['role']    = users.role
        IF role == 'admin'  THEN redirect ke /pages/admin/dashboard.php
        IF role == 'guru'   THEN redirect ke /pages/guru/dashboard.php
        IF role == 'siswa'  THEN redirect ke /pages/siswa/dashboard.php
      END IF
    END IF
  END IF
END
```

### [2] Proses Upload Materi (Guru)

```
START
  Guru memilih kelas & mengisi form materi
  Klik submit
  Validasi: judul tidak kosong, kelas dipilih, file diupload
  Cek tipe file: hanya PDF, PPT, PPTX, MP4 yang diizinkan
  Cek ukuran file: max 50MB (sesuai php.ini)
  IF validasi gagal THEN tampilkan pesan error
  ELSE
    Generate nama file unik (uniqid + ekstensi asli)
    Pindahkan file ke /assets/uploads/materi/
    INSERT INTO materi (id_guru, id_kelas, judul, deskripsi, file, tanggal_upload)
    Tampilkan pesan sukses
  END IF
END
```

### [3] Proses Pengumpulan Tugas (Siswa)

```
START
  Siswa membuka halaman tugas
  Sistem query tugas berdasarkan id_kelas siswa
  Siswa memilih tugas & upload file jawaban
  Validasi: file tidak kosong, deadline belum lewat
  IF deadline sudah lewat THEN
    Tampilkan "Batas waktu pengumpulan telah habis"
  ELSE IF file tidak valid THEN
    Tampilkan pesan error format file
  ELSE
    Generate nama file unik
    Pindahkan ke /assets/uploads/tugas/
    Cek apakah sudah pernah mengumpulkan (id_tugas + id_siswa)
    IF sudah ada THEN UPDATE (replace file lama)
    ELSE INSERT INTO pengumpulan_tugas (id_tugas, id_siswa, file, status, waktu_kumpul)
    Tampilkan pesan sukses
  END IF
END
```

### [4] Proses Absensi (Guru)

```
START
  Guru memilih kelas & tanggal
  Query: SELECT * FROM absensi WHERE id_kelas = X AND tanggal = Y
  IF sudah ada sesi THEN
    Load data absensi_detail yang sudah ada (mode edit)
  ELSE
    INSERT INTO absensi (id_kelas, id_guru, tanggal)
    Ambil id_absensi baru
    Query siswa di kelas: SELECT id_siswa FROM kelas_siswa WHERE id_kelas = X
    Untuk setiap siswa: INSERT INTO absensi_detail (id_absensi, id_siswa, status='alfa')
  END IF
  Tampilkan form daftar siswa dengan status toggle (Hadir/Izin/Sakit/Alfa)
  Guru submit form
  UPDATE absensi_detail SET status = input_status WHERE id_detail = X
  Tampilkan pesan sukses
END
```

---

## BAGIAN 5 : KEBUTUHAN FUNGSIONAL & NON-FUNGSIONAL (RINGKASAN)

### FUNGSIONAL

| Kode | Nama | Aktor |
|------|------|-------|
| KF-01 | Login | Admin, Guru, Siswa |
| KF-02 | Kelola Kelas | Admin |
| KF-03 | Kelola Akun | Admin |
| KF-04 | Upload Materi | Guru |
| KF-05 | Kelola Tugas | Guru |
| KF-06 | Kumpul Tugas | Siswa |
| KF-07 | Input Nilai | Guru |
| KF-08 | Absensi Online | Guru |
| KF-09 | Lihat Laporan | Admin, Guru |
| KF-10 | Lihat Materi | Siswa |
| KF-11 | Logout | Admin, Guru, Siswa |

### NON-FUNGSIONAL

| Kode | Nama | Deskripsi |
|------|------|-----------|
| KNF-01 | Usability | UI mudah digunakan tanpa pelatihan panjang |
| KNF-02 | Performance | Minimal 50 user concurrent |
| KNF-03 | Security | Session-based auth + role-based access |
| KNF-04 | Compatibility | Chrome, Firefox, Edge versi terbaru |
| KNF-05 | Availability | 24/7 selama server aktif |
| KNF-06 | Maintainability | Kode modular, terdokumentasi |
| KNF-07 | Reliability | Backup database berkala |

---

## BAGIAN 6 : STACK TEKNOLOGI

| Komponen | Teknologi |
|----------|-----------|
| Backend | PHP (Native / dapat dikembangkan ke Laravel) |
| Database | MySQL 5.7+ |
| Web Server | Apache (XAMPP untuk development) |
| Frontend | HTML5, CSS3, JavaScript (Vanilla / Bootstrap 5) |
| Browser | Chrome, Firefox, Edge (versi terbaru) |
| OS Server | Windows (XAMPP) / Linux (Apache + PHP) |
| Hardware | Client minimal RAM 2GB, Processor 1GHz |
| Jaringan | Intranet sekolah / Internet |

---

## BAGIAN 7 : SDLC - WATERFALL PLAN

| Fase | Output | Aktivitas |
|------|--------|-----------|
| 1. Requirement Analysis | Dokumen SRS (sudah selesai) | Identifikasi kebutuhan, wawancara stakeholder |
| 2. System Design | Desain sistem (ERD, DFD, Use Case, Wireframe) | Perancangan database, UI mockup, arsitektur sistem |
| 3. Implementation | Source code lengkap | Coding backend PHP, frontend HTML/CSS/JS, integrasi DB |
| 4. Testing | Laporan pengujian Blackbox | Uji semua fitur per role, uji validasi form, uji hak akses |
| 5. Deployment | Sistem berjalan di server sekolah | Upload ke server, konfigurasi, sosialisasi ke pengguna |
| 6. Maintenance | Laporan pemeliharaan | Perbaikan bug, pembaruan fitur atas permintaan |

---

## BAGIAN 8 : RANCANGAN TABEL USE CASE LENGKAP

### UC-01 | Login

| Komponen | Deskripsi |
|----------|-----------|
| Aktor | Admin, Guru, Siswa |
| Pre-kondisi | Pengguna belum login |
| Post-kondisi | Session aktif, redirect ke dashboard sesuai role |
| Flow | Buka login -> input email+password -> validasi -> cek role -> redirect |

### UC-02 | Kelola Kelas

| Komponen | Deskripsi |
|----------|-----------|
| Aktor | Admin |
| Pre-kondisi | Admin sudah login |
| Post-kondisi | Data kelas berhasil ditambah/diubah/dihapus |
| Flow | Dashboard admin -> menu kelas -> form CRUD -> simpan ke DB |

### UC-03 | Kelola Akun

| Komponen | Deskripsi |
|----------|-----------|
| Aktor | Admin |
| Pre-kondisi | Admin sudah login |
| Post-kondisi | Akun guru/siswa berhasil dikelola |
| Flow | Dashboard admin -> menu pengguna -> tab guru/siswa -> form CRUD |

### UC-04 | Upload Materi

| Komponen | Deskripsi |
|----------|-----------|
| Aktor | Guru |
| Pre-kondisi | Guru sudah login, memiliki kelas |
| Post-kondisi | File materi tersimpan di server, data tersimpan di DB |
| Flow | Dashboard guru -> upload materi -> isi form -> upload file -> simpan |

### UC-05 | Kelola Tugas

| Komponen | Deskripsi |
|----------|-----------|
| Aktor | Guru |
| Pre-kondisi | Guru sudah login |
| Post-kondisi | Tugas berhasil dibuat/diubah/dihapus |
| Flow | Dashboard guru -> kelola tugas -> form buat/edit tugas -> simpan |

### UC-06 | Pengumpulan Tugas

| Komponen | Deskripsi |
|----------|-----------|
| Aktor | Siswa |
| Pre-kondisi | Siswa sudah login, tugas belum deadline |
| Post-kondisi | File jawaban tersimpan, status berubah jadi 'dikumpulkan' |
| Flow | Dashboard siswa -> kumpul tugas -> pilih tugas -> upload file |

### UC-07 | Input Nilai

| Komponen | Deskripsi |
|----------|-----------|
| Aktor | Guru |
| Pre-kondisi | Siswa sudah mengumpulkan tugas |
| Post-kondisi | Nilai tersimpan, status berubah jadi 'dinilai' |
| Flow | Dashboard guru -> input nilai -> pilih tugas -> isi nilai per siswa |

### UC-08 | Absensi Online

| Komponen | Deskripsi |
|----------|-----------|
| Aktor | Guru |
| Pre-kondisi | Guru sudah login, memiliki kelas |
| Post-kondisi | Data kehadiran siswa tersimpan |
| Flow | Dashboard guru -> absensi -> pilih kelas & tanggal -> isi status -> simpan |

### UC-09 | Lihat Laporan

| Komponen | Deskripsi |
|----------|-----------|
| Aktor | Admin, Guru |
| Pre-kondisi | Sudah login sebagai admin/guru |
| Post-kondisi | Laporan nilai & absensi ditampilkan |
| Flow | Menu laporan -> filter kelas/periode -> tampil data -> opsional export |

---

## BAGIAN 9 : PANDUAN SESSION & KEAMANAN DASAR

Setiap halaman yang memerlukan login wajib menyertakan:

```php
<?php
session_start();
if (!isset($_SESSION['user_id'])) {
    header("Location: /auth/login.php");
    exit;
}
// Cek role untuk halaman tertentu:
if ($_SESSION['role'] !== 'admin') {
    header("Location: /auth/login.php");
    exit;
}
?>
```

### Aturan Keamanan

| No | Aturan |
|----|--------|
| 1 | Password disimpan dengan `password_hash($pass, PASSWORD_BCRYPT)` |
| 2 | Verifikasi dengan `password_verify($input, $hash)` |
| 3 | Gunakan prepared statement (PDO) untuk semua query |
| 4 | Validasi & sanitasi semua input sebelum diproses |
| 5 | Cek role di setiap halaman sesuai hak akses |
| 6 | Nama file upload di-rename menggunakan `uniqid()` untuk menghindari overwrite |

---

## BAGIAN 10 : CONTOH KONEKSI DATABASE (config/database.php)

```php
<?php
$host     = 'localhost';
$dbname   = 'simp_web';
$username = 'root';
$password = '';

try {
    $pdo = new PDO(
        "mysql:host=$host;dbname=$dbname;charset=utf8mb4",
        $username,
        $password,
        [
            PDO::ATTR_ERRMODE            => PDO::ERRMODE_EXCEPTION,
            PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
        ]
    );
} catch (PDOException $e) {
    die("Koneksi gagal: " . $e->getMessage());
}
?>
```

---

**END OF DOCUMENT**

Sistem Informasi Manajemen Pembelajaran Berbasis Web v1.0
Ridho Restiawan - 2413025031 - PTI 2024
