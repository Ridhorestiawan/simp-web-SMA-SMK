<?php
$title = 'Dashboard Siswa';
$currentPage = 'dashboard';
require_once __DIR__ . '/../../includes/header.php';
require_once __DIR__ . '/../../config/database.php';

$idSiswa = $_SESSION['user_id'];

// Get student's class
$kelasSiswa = $pdo->prepare("
    SELECT k.id_kelas, k.nama_kelas FROM kelas_siswa ks
    JOIN kelas k ON ks.id_kelas = k.id_kelas
    WHERE ks.id_siswa = ?
");
$kelasSiswa->execute([$idSiswa]);
$kelasSiswa = $kelasSiswa->fetch();

// Stats
$totalMateri = 0; $totalTugas = 0; $sudahKumpul = 0; $rataNilai = 0;
if ($kelasSiswa) {
    $idKelas = $kelasSiswa['id_kelas'];

    $totalMateri = $pdo->prepare("SELECT COUNT(*) FROM materi WHERE id_kelas = ?");
    $totalMateri->execute([$idKelas]);
    $totalMateri = $totalMateri->fetchColumn();

    $totalTugas = $pdo->prepare("SELECT COUNT(*) FROM tugas WHERE id_kelas = ?");
    $totalTugas->execute([$idKelas]);
    $totalTugas = $totalTugas->fetchColumn();

    $sudahKumpul = $pdo->prepare("SELECT COUNT(*) FROM pengumpulan_tugas WHERE id_siswa = ? AND status != 'belum'");
    $sudahKumpul->execute([$idSiswa]);
    $sudahKumpul = $sudahKumpul->fetchColumn();

    $rataNilai = $pdo->prepare("SELECT AVG(nilai) FROM pengumpulan_tugas WHERE id_siswa = ? AND nilai IS NOT NULL");
    $rataNilai->execute([$idSiswa]);
    $rataNilai = number_format($rataNilai->fetchColumn() ?: 0, 1);
}

// Recent materials
$recentMateri = [];
if ($kelasSiswa) {
    $rm = $pdo->prepare("
        SELECT m.*, u.nama as nama_guru FROM materi m
        JOIN users u ON m.id_guru = u.id
        WHERE m.id_kelas = ? ORDER BY m.tanggal_upload DESC LIMIT 4
    ");
    $rm->execute([$kelasSiswa['id_kelas']]);
    $recentMateri = $rm->fetchAll();
}

// Active tasks (not past deadline)
$activeTugas = [];
if ($kelasSiswa) {
    $at = $pdo->prepare("
        SELECT t.*, u.nama as nama_guru,
            (SELECT status FROM pengumpulan_tugas WHERE id_tugas = t.id_tugas AND id_siswa = ?) as status_saya
        FROM tugas t
        JOIN users u ON t.id_guru = u.id
        WHERE t.id_kelas = ? AND t.deadline > NOW()
        ORDER BY t.deadline ASC LIMIT 4
    ");
    $at->execute([$idSiswa, $kelasSiswa['id_kelas']]);
    $activeTugas = $at->fetchAll();
}

// Latest grades
$nilaiTerbaru = [];
if ($kelasSiswa) {
    $nt = $pdo->prepare("
        SELECT pt.*, t.judul as judul_tugas FROM pengumpulan_tugas pt
        JOIN tugas t ON pt.id_tugas = t.id_tugas
        WHERE pt.id_siswa = ? AND pt.nilai IS NOT NULL
        ORDER BY pt.waktu_kumpul DESC LIMIT 4
    ");
    $nt->execute([$idSiswa]);
    $nilaiTerbaru = $nt->fetchAll();
}
?>
<div class="max-w-[1440px] mx-auto space-y-8">
    <div>
        <h2 class="font-display-lg text-display-lg text-on-surface">Halo, <?= htmlspecialchars($_SESSION['nama']) ?>!</h2>
        <p class="text-on-surface-variant">
            <?php if ($kelasSiswa): ?>
                Kelas <?= htmlspecialchars($kelasSiswa['nama_kelas']) ?> — Pantau materi, tugas, dan nilai kamu.
            <?php else: ?>
                Kamu belum terdaftar di kelas mana pun. Hubungi admin.
            <?php endif; ?>
        </p>
    </div>

    <?php if ($kelasSiswa): ?>
    <!-- Stats -->
    <div class="animate-stagger grid grid-cols-2 md:grid-cols-4 gap-gutter">
        <div class="bg-surface-container-lowest p-6 rounded-lg soft-shadow border border-outline-variant card-hover-lift">
            <div class="flex items-center gap-4">
                <div class="stat-icon w-12 h-12"><span class="material-symbols-outlined text-primary">book</span></div>
                <div><p class="font-label-sm text-on-surface-variant">Materi</p><p class="text-[28px] font-bold text-gradient"><?= $totalMateri ?></p></div>
            </div>
        </div>
        <div class="bg-surface-container-lowest p-6 rounded-lg soft-shadow border border-outline-variant card-hover-lift">
            <div class="flex items-center gap-4">
                <div class="stat-icon w-12 h-12"><span class="material-symbols-outlined text-primary">assignment</span></div>
                <div><p class="font-label-sm text-on-surface-variant">Tugas</p><p class="text-[28px] font-bold text-gradient"><?= $totalTugas ?></p></div>
            </div>
        </div>
        <div class="bg-surface-container-lowest p-6 rounded-lg soft-shadow border border-outline-variant card-hover-lift">
            <div class="flex items-center gap-4">
                <div class="stat-icon w-12 h-12"><span class="material-symbols-outlined text-primary">check_circle</span></div>
                <div><p class="font-label-sm text-on-surface-variant">Dikumpul</p><p class="text-[28px] font-bold text-gradient"><?= $sudahKumpul ?>/<?= $totalTugas ?></p></div>
            </div>
        </div>
        <div class="bg-surface-container-lowest p-6 rounded-lg soft-shadow border border-outline-variant card-hover-lift">
            <div class="flex items-center gap-4">
                <div class="stat-icon w-12 h-12"><span class="material-symbols-outlined text-primary">grade</span></div>
                <div><p class="font-label-sm text-on-surface-variant">Rata-rata</p><p class="text-[28px] font-bold text-gradient"><?= $rataNilai ?></p></div>
            </div>
        </div>
    </div>

    <div class="grid grid-cols-1 lg:grid-cols-2 gap-gutter">
        <!-- Recent Materials -->
        <section class="bg-surface-container-lowest rounded-lg soft-shadow border border-outline-variant">
            <div class="p-6 border-b border-outline-variant flex items-center justify-between">
                <h3 class="font-title-sm flex items-center gap-2"><span class="material-symbols-outlined text-primary">book</span> Materi Terbaru</h3>
                <a href="lihat_materi.php" class="text-primary font-label-sm hover:underline">Lihat Semua</a>
            </div>
            <div class="divide-y divide-outline-variant">
                <?php if (empty($recentMateri)): ?>
                <div class="p-6 text-center text-on-surface-variant">Belum ada materi.</div>
                <?php else: ?>
                    <?php foreach ($recentMateri as $m): ?>
                    <div class="p-5 hover:bg-surface-container-low transition-colors">
                        <div class="flex items-center gap-3">
                            <div class="w-10 h-10 rounded-md bg-surface-container-high flex items-center justify-center">
                                <span class="material-symbols-outlined text-on-surface-variant">description</span>
                            </div>
                            <div class="flex-grow">
                                <p class="font-title-sm text-on-surface"><?= htmlspecialchars($m['judul']) ?></p>
                                <p class="font-label-sm text-on-surface-variant"><?= htmlspecialchars($m['nama_guru']) ?> — <?= date('d M Y', strtotime($m['tanggal_upload'])) ?></p>
                            </div>
                            <a href="../../assets/uploads/materi/<?= urlencode($m['file']) ?>" download class="p-2 hover:bg-primary-container/20 text-primary rounded">
                                <span class="material-symbols-outlined text-[20px]">download</span>
                            </a>
                        </div>
                    </div>
                    <?php endforeach; ?>
                <?php endif; ?>
            </div>
        </section>

        <!-- Active Tasks -->
        <section class="bg-surface-container-lowest rounded-lg soft-shadow border border-outline-variant">
            <div class="p-6 border-b border-outline-variant flex items-center justify-between">
                <h3 class="font-title-sm flex items-center gap-2"><span class="material-symbols-outlined text-tertiary">assignment</span> Tugas Aktif</h3>
                <a href="kumpul_tugas.php" class="text-primary font-label-sm hover:underline">Lihat Semua</a>
            </div>
            <div class="divide-y divide-outline-variant">
                <?php if (empty($activeTugas)): ?>
                <div class="p-6 text-center text-on-surface-variant">Tidak ada tugas aktif.</div>
                <?php else: ?>
                    <?php foreach ($activeTugas as $t): ?>
                    <?php
                        $sisa = strtotime($t['deadline']) - time();
                        $jamSisa = floor($sisa / 3600);
                        $hariSisa = floor($sisa / 86400);
                        $status = $t['status_saya'] ?? 'belum';
                    ?>
                    <div class="p-5 hover:bg-surface-container-low transition-colors">
                        <div class="flex items-center justify-between">
                            <div>
                                <p class="font-title-sm text-on-surface"><?= htmlspecialchars($t['judul']) ?></p>
                                <p class="font-label-sm text-on-surface-variant"><?= htmlspecialchars($t['nama_guru']) ?></p>
                            </div>
                            <?php if ($status === 'dikumpulkan' || $status === 'dinilai'): ?>
                            <span class="px-3 py-1 bg-green-100 text-green-800 rounded-full text-[10px] font-bold">Selesai</span>
                            <?php else: ?>
                            <span class="px-3 py-1 bg-amber-100 text-amber-800 rounded-full text-[10px] font-bold">Belum</span>
                            <?php endif; ?>
                        </div>
                        <div class="flex items-center gap-2 mt-2">
                            <span class="material-symbols-outlined text-[16px] text-on-surface-variant">schedule</span>
                            <span class="font-label-sm <?= $hariSisa < 1 ? 'text-error' : 'text-on-surface-variant' ?>">
                                Sisa <?= $hariSisa >= 1 ? "$hariSisa hari" : ($jamSisa >= 1 ? "$jamSisa jam" : "Kurang dari 1 jam") ?>
                            </span>
                        </div>
                    </div>
                    <?php endforeach; ?>
                <?php endif; ?>
            </div>
        </section>
    </div>

    <!-- Recent Grades -->
    <section class="bg-surface-container-lowest rounded-lg soft-shadow border border-outline-variant">
        <div class="p-6 border-b border-outline-variant flex items-center justify-between">
            <h3 class="font-title-sm flex items-center gap-2"><span class="material-symbols-outlined text-success">grade</span> Nilai Terbaru</h3>
            <a href="lihat_nilai.php" class="text-primary font-label-sm hover:underline">Lihat Semua</a>
        </div>
        <?php if (empty($nilaiTerbaru)): ?>
        <div class="p-6 text-center text-on-surface-variant">Belum ada nilai.</div>
        <?php else: ?>
        <div class="overflow-x-auto">
            <table class="w-full text-left border-collapse">
                <thead class="bg-surface-container-low">
                    <tr>
                        <th class="px-6 py-4 font-label-sm text-on-surface-variant uppercase tracking-wider">Tugas</th>
                        <th class="px-6 py-4 font-label-sm text-on-surface-variant uppercase tracking-wider">Status</th>
                        <th class="px-6 py-4 font-label-sm text-on-surface-variant uppercase tracking-wider">Nilai</th>
                        <th class="px-6 py-4 font-label-sm text-on-surface-variant uppercase tracking-wider hidden md:table-cell">Catatan</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-outline-variant">
                    <?php foreach ($nilaiTerbaru as $n): ?>
                    <tr class="hover:bg-surface-container-low transition-colors">
                        <td class="px-6 py-4 font-title-sm text-on-surface"><?= htmlspecialchars($n['judul_tugas']) ?></td>
                        <td class="px-6 py-4">
                            <span class="px-3 py-1 rounded-full text-[10px] font-bold <?= $n['status'] === 'dinilai' ? 'bg-green-100 text-green-800' : 'bg-gray-100 text-gray-600' ?>"><?= ucfirst($n['status']) ?></span>
                        </td>
                        <td class="px-6 py-4 font-bold text-lg <?= ($n['nilai'] ?? 0) >= 75 ? 'text-success' : 'text-error' ?>"><?= $n['nilai'] ?? '-' ?></td>
                        <td class="px-6 py-4 text-on-surface-variant hidden md:table-cell"><?= htmlspecialchars($n['catatan_guru'] ?? '-') ?></td>
                    </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        </div>
        <?php endif; ?>
    </section>

    <!-- Quick Actions -->
    <div class="grid grid-cols-1 sm:grid-cols-3 gap-gutter">
        <a href="lihat_materi.php" class="flex items-center gap-4 p-5 bg-surface-container-lowest rounded-lg soft-shadow border border-outline-variant hover:border-primary transition-all group">
            <div class="w-12 h-12 rounded-full bg-primary-container flex items-center justify-center text-primary group-hover:scale-110 transition-transform">
                <span class="material-symbols-outlined">book</span></div>
            <div><p class="font-title-sm text-on-surface">Lihat Materi</p><p class="font-label-sm text-on-surface-variant">Belajar dari materi ajar</p></div>
        </a>
        <a href="kumpul_tugas.php" class="flex items-center gap-4 p-5 bg-surface-container-lowest rounded-lg soft-shadow border border-outline-variant hover:border-primary transition-all group">
            <div class="w-12 h-12 rounded-full bg-tertiary-container flex items-center justify-center text-tertiary group-hover:scale-110 transition-transform">
                <span class="material-symbols-outlined">upload_file</span></div>
            <div><p class="font-title-sm text-on-surface">Kumpul Tugas</p><p class="font-label-sm text-on-surface-variant">Upload tugas kamu</p></div>
        </a>
        <a href="lihat_nilai.php" class="flex items-center gap-4 p-5 bg-surface-container-lowest rounded-lg soft-shadow border border-outline-variant hover:border-primary transition-all group">
            <div class="w-12 h-12 rounded-full bg-secondary-container flex items-center justify-center text-secondary group-hover:scale-110 transition-transform">
                <span class="material-symbols-outlined">assessment</span></div>
            <div><p class="font-title-sm text-on-surface">Cek Nilai</p><p class="font-label-sm text-on-surface-variant">Lihat hasil penilaian</p></div>
        </a>
    </div>
    <?php endif; ?>
</div>
<?php require_once __DIR__ . '/../../includes/footer.php'; ?>
