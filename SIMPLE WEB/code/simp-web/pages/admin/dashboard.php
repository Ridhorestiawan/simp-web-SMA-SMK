<?php
$title = 'Dashboard Admin';
$currentPage = 'dashboard';
require_once __DIR__ . '/../../includes/header.php';
require_once __DIR__ . '/../../config/database.php';

$totalGuru = $pdo->query("SELECT COUNT(*) FROM users WHERE role='guru'")->fetchColumn();
$totalSiswa = $pdo->query("SELECT COUNT(*) FROM users WHERE role='siswa'")->fetchColumn();
$totalKelas = $pdo->query("SELECT COUNT(*) FROM kelas")->fetchColumn();

$recentActivity = $pdo->query("
    (SELECT u.nama, 'materi' as aksi, m.judul as detail, m.tanggal_upload as waktu
     FROM materi m JOIN users u ON m.id_guru = u.id
     ORDER BY m.tanggal_upload DESC LIMIT 5)
    UNION ALL
    (SELECT u.nama, 'tugas' as aksi, t.judul as detail, pt.waktu_kumpul as waktu
     FROM pengumpulan_tugas pt JOIN tugas t ON pt.id_tugas = t.id_tugas
     JOIN users u ON pt.id_siswa = u.id
     WHERE pt.waktu_kumpul IS NOT NULL
     ORDER BY pt.waktu_kumpul DESC LIMIT 5)
    ORDER BY waktu DESC LIMIT 8
")->fetchAll();
?>
<div class="max-w-[1440px] mx-auto space-y-8">
    <div class="flex flex-col md:flex-row md:items-end justify-between gap-4">
        <div>
            <h2 class="font-display-lg-mobile md:font-display-lg text-display-lg-mobile md:text-display-lg text-on-surface">Selamat datang, <?= htmlspecialchars($_SESSION['nama']) ?></h2>
            <p class="font-body-md text-body-md text-on-surface-variant mt-1" id="current-date"></p>
        </div>
    </div>

    <div class="animate-stagger grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-4 gap-gutter">
        <div class="bg-surface-container-lowest p-6 rounded-lg soft-shadow border border-outline-variant card-hover-lift">
            <div class="flex justify-between items-start mb-4">
                <div class="stat-icon w-12 h-12">
                    <span class="material-symbols-outlined text-primary">person</span>
                </div>
            </div>
            <h3 class="font-label-sm text-label-sm text-on-surface-variant uppercase tracking-wider">Total Guru</h3>
            <p class="text-[32px] font-bold text-gradient mt-1"><?= $totalGuru ?></p>
        </div>
        <div class="bg-surface-container-lowest p-6 rounded-lg soft-shadow border border-outline-variant card-hover-lift">
            <div class="flex justify-between items-start mb-4">
                <div class="stat-icon w-12 h-12">
                    <span class="material-symbols-outlined text-primary">group</span>
                </div>
            </div>
            <h3 class="font-label-sm text-label-sm text-on-surface-variant uppercase tracking-wider">Total Siswa</h3>
            <p class="text-[32px] font-bold text-gradient mt-1"><?= $totalSiswa ?></p>
        </div>
        <div class="bg-surface-container-lowest p-6 rounded-lg soft-shadow border border-outline-variant card-hover-lift">
            <div class="flex justify-between items-start mb-4">
                <div class="stat-icon w-12 h-12">
                    <span class="material-symbols-outlined text-primary">school</span>
                </div>
            </div>
            <h3 class="font-label-sm text-label-sm text-on-surface-variant uppercase tracking-wider">Total Kelas</h3>
            <p class="text-[32px] font-bold text-gradient mt-1"><?= $totalKelas ?></p>
        </div>
        <div class="bg-surface-container-lowest p-6 rounded-lg soft-shadow border border-outline-variant card-hover-lift">
            <div class="flex justify-between items-start mb-4">
                <div class="stat-icon w-12 h-12">
                    <span class="material-symbols-outlined text-primary">description</span>
                </div>
            </div>
            <h3 class="font-label-sm text-label-sm text-on-surface-variant uppercase tracking-wider">Total Materi</h3>
            <p class="text-[32px] font-bold text-gradient mt-1"><?= $pdo->query("SELECT COUNT(*) FROM materi")->fetchColumn() ?></p>
        </div>
    </div>

    <div class="grid grid-cols-1 lg:grid-cols-3 gap-gutter">
        <div class="lg:col-span-2 bg-surface-container-lowest rounded-lg soft-shadow border border-outline-variant overflow-hidden">
            <div class="p-6 border-b border-outline-variant flex justify-between items-center">
                <h3 class="font-headline-md text-headline-md text-on-surface">Aktivitas Terbaru</h3>
            </div>
            <div class="overflow-x-auto">
                <table class="w-full text-left border-collapse">
                    <thead>
                        <tr class="bg-surface-container-low">
                            <th class="p-4 font-label-sm text-label-sm text-on-surface-variant">Pengguna</th>
                            <th class="p-4 font-label-sm text-label-sm text-on-surface-variant">Aksi</th>
                            <th class="p-4 font-label-sm text-label-sm text-on-surface-variant">Detail</th>
                            <th class="p-4 font-label-sm text-label-sm text-on-surface-variant">Waktu</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-outline-variant">
                        <?php if (empty($recentActivity)): ?>
                            <tr>
                                <td colspan="4" class="p-8 text-center font-body-md text-on-surface-variant">Belum ada aktivitas.</td>
                            </tr>
                        <?php else: ?>
                            <?php foreach ($recentActivity as $act): ?>
                            <tr class="hover:bg-surface-container-low transition-colors">
                                <td class="p-4"><span class="font-body-md text-body-md text-on-surface"><?= htmlspecialchars($act['nama']) ?></span></td>
                                <td class="p-4">
                                    <span class="px-2 py-1 rounded-full bg-secondary-container text-on-secondary-container text-[10px] font-semibold uppercase">
                                        <?= htmlspecialchars($act['aksi']) ?>
                                    </span>
                                </td>
                                <td class="p-4 font-body-md text-body-md text-on-surface-variant"><?= htmlspecialchars($act['detail']) ?></td>
                                <td class="p-4 font-label-sm text-label-sm text-on-surface-variant"><?= htmlspecialchars($act['waktu']) ?></td>
                            </tr>
                            <?php endforeach; ?>
                        <?php endif; ?>
                    </tbody>
                </table>
            </div>
        </div>

        <div class="flex flex-col gap-gutter">
            <div class="bg-primary p-6 rounded-lg soft-shadow text-on-primary relative overflow-hidden">
                <div class="relative z-10">
                    <h3 class="font-title-sm text-title-sm mb-2">Menu Cepat</h3>
                    <p class="font-label-sm text-label-sm opacity-80 mb-6">Manajemen data sekolah</p>
                    <div class="grid grid-cols-2 gap-3">
                        <a href="kelola_pengguna.php" class="bg-white/10 hover:bg-white/20 p-3 rounded flex flex-col items-center gap-2 transition-all group">
                            <span class="material-symbols-outlined group-hover:scale-110 transition-transform">person_add</span>
                            <span class="font-label-sm text-label-sm text-center">Atur Akun</span>
                        </a>
                        <a href="kelola_kelas.php" class="bg-white/10 hover:bg-white/20 p-3 rounded flex flex-col items-center gap-2 transition-all group">
                            <span class="material-symbols-outlined group-hover:scale-110 transition-transform">add_box</span>
                            <span class="font-label-sm text-label-sm text-center">Buat Kelas</span>
                        </a>
                        <a href="laporan.php" class="bg-white/10 hover:bg-white/20 p-3 rounded flex flex-col items-center gap-2 transition-all group">
                            <span class="material-symbols-outlined group-hover:scale-110 transition-transform">analytics</span>
                            <span class="font-label-sm text-label-sm text-center">Laporan</span>
                        </a>
                        <a href="kelola_pengguna.php" class="bg-white/10 hover:bg-white/20 p-3 rounded flex flex-col items-center gap-2 transition-all group">
                            <span class="material-symbols-outlined group-hover:scale-110 transition-transform">group_add</span>
                            <span class="font-label-sm text-label-sm text-center">Tambah Siswa</span>
                        </a>
                    </div>
                </div>
                <div class="absolute -bottom-10 -right-10 w-40 h-40 bg-white/10 rounded-full blur-3xl"></div>
            </div>
        </div>
    </div>
</div>
<?php require_once __DIR__ . '/../../includes/footer.php'; ?>
