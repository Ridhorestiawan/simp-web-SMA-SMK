<?php
$title = 'Dashboard Guru';
$currentPage = 'dashboard';
require_once __DIR__ . '/../../includes/header.php';
require_once __DIR__ . '/../../config/database.php';

$idGuru = $_SESSION['user_id'];

// Stats
$totalKelas = $pdo->prepare("SELECT COUNT(DISTINCT t.id_kelas) FROM tugas t WHERE t.id_guru = ?");
$totalKelas->execute([$idGuru]);
$totalKelas = $totalKelas->fetchColumn();

$totalTugas = $pdo->prepare("SELECT COUNT(*) FROM tugas WHERE id_guru = ?");
$totalTugas->execute([$idGuru]);
$totalTugas = $totalTugas->fetchColumn();

$totalMateri = $pdo->prepare("SELECT COUNT(*) FROM materi WHERE id_guru = ?");
$totalMateri->execute([$idGuru]);
$totalMateri = $totalMateri->fetchColumn();

$belumDinilai = $pdo->prepare("SELECT COUNT(*) FROM pengumpulan_tugas pt JOIN tugas t ON pt.id_tugas = t.id_tugas WHERE t.id_guru = ? AND pt.status = 'dikumpulkan'");
$belumDinilai->execute([$idGuru]);
$belumDinilai = $belumDinilai->fetchColumn();

// Recent tasks with submission count
$recentTugas = $pdo->prepare("
    SELECT t.*, k.nama_kelas,
        (SELECT COUNT(*) FROM pengumpulan_tugas pt WHERE pt.id_tugas = t.id_tugas) as sudah_kumpul,
        (SELECT COUNT(*) FROM kelas_siswa ks WHERE ks.id_kelas = t.id_kelas) as total_siswa
    FROM tugas t
    JOIN kelas k ON t.id_kelas = k.id_kelas
    WHERE t.id_guru = ?
    ORDER BY t.created_at DESC LIMIT 5
");
$recentTugas->execute([$idGuru]);
$recentTugas = $recentTugas->fetchAll();

// Recent submissions (need grading)
$recentSubmissions = $pdo->prepare("
    SELECT pt.*, u.nama as nama_siswa, t.judul as judul_tugas, t.deadline
    FROM pengumpulan_tugas pt
    JOIN users u ON pt.id_siswa = u.id
    JOIN tugas t ON pt.id_tugas = t.id_tugas
    WHERE t.id_guru = ? AND pt.status = 'dikumpulkan'
    ORDER BY pt.waktu_kumpul DESC LIMIT 5
");
$recentSubmissions->execute([$idGuru]);
$recentSubmissions = $recentSubmissions->fetchAll();

// Notifications: upcoming deadlines (next 7 days)
$deadlines = $pdo->prepare("
    SELECT t.*, k.nama_kelas,
        (SELECT COUNT(*) FROM pengumpulan_tugas pt WHERE pt.id_tugas = t.id_tugas AND pt.status != 'belum') as sudah_kumpul,
        (SELECT COUNT(*) FROM kelas_siswa ks WHERE ks.id_kelas = t.id_kelas) as total_siswa
    FROM tugas t
    JOIN kelas k ON t.id_kelas = k.id_kelas
    WHERE t.id_guru = ? AND t.deadline BETWEEN NOW() AND DATE_ADD(NOW(), INTERVAL 7 DAY)
    ORDER BY t.deadline ASC LIMIT 5
");
$deadlines->execute([$idGuru]);
$deadlines = $deadlines->fetchAll();
?>
<div class="max-w-[1440px] mx-auto space-y-8">
    <div>
        <h2 class="font-display-lg text-display-lg text-on-surface">Selamat datang, <?= htmlspecialchars($_SESSION['nama']) ?>!</h2>
        <p class="text-on-surface-variant">Kelola pembelajaran, pantau tugas, dan evaluasi siswa.</p>
    </div>

    <!-- Stat Cards -->
    <div class="animate-stagger grid grid-cols-2 md:grid-cols-4 gap-gutter">
        <div class="bg-surface-container-lowest p-6 rounded-lg soft-shadow border border-outline-variant card-hover-lift">
            <div class="flex items-center gap-4">
                <div class="stat-icon w-12 h-12"><span class="material-symbols-outlined text-primary">school</span></div>
                <div><p class="font-label-sm text-on-surface-variant">Kelas</p><p class="text-[28px] font-bold text-gradient"><?= $totalKelas ?></p></div>
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
                <div class="stat-icon w-12 h-12"><span class="material-symbols-outlined text-primary">description</span></div>
                <div><p class="font-label-sm text-on-surface-variant">Materi</p><p class="text-[28px] font-bold text-gradient"><?= $totalMateri ?></p></div>
            </div>
        </div>
        <div class="bg-surface-container-lowest p-6 rounded-lg soft-shadow border border-outline-variant card-hover-lift">
            <div class="flex items-center gap-4">
                <div class="stat-icon w-12 h-12"><span class="material-symbols-outlined <?= $belumDinilai > 0 ? 'text-error' : 'text-primary' ?>"><?= $belumDinilai > 0 ? 'pending' : 'check' ?></span></div>
                <div><p class="font-label-sm text-on-surface-variant">Belum Dinilai</p><p class="text-[28px] font-bold text-gradient"><?= $belumDinilai ?></p></div>
            </div>
        </div>
    </div>

    <div class="grid grid-cols-1 lg:grid-cols-2 gap-gutter">
        <!-- Recent Tasks -->
        <section class="bg-surface-container-lowest rounded-lg soft-shadow border border-outline-variant">
            <div class="p-6 border-b border-outline-variant flex items-center justify-between">
                <h3 class="font-title-sm flex items-center gap-2"><span class="material-symbols-outlined text-primary">history</span> Tugas Terbaru</h3>
                <a href="kelola_tugas.php" class="text-primary font-label-sm hover:underline">Lihat Semua</a>
            </div>
            <div class="divide-y divide-outline-variant">
                <?php if (empty($recentTugas)): ?>
                <div class="p-6 text-center text-on-surface-variant">Belum ada tugas.</div>
                <?php else: ?>
                    <?php foreach ($recentTugas as $t): ?>
                    <div class="p-5 hover:bg-surface-container-low transition-colors">
                        <div class="flex items-center justify-between">
                            <div>
                                <p class="font-title-sm text-on-surface"><?= htmlspecialchars($t['judul']) ?></p>
                                <p class="font-label-sm text-on-surface-variant"><?= htmlspecialchars($t['nama_kelas']) ?></p>
                            </div>
                            <div class="text-right">
                                <p class="font-title-sm"><?= $t['sudah_kumpul'] ?>/<?= $t['total_siswa'] ?></p>
                                <p class="text-[10px] text-on-surface-variant uppercase tracking-wider">Mengumpul</p>
                            </div>
                        </div>
                        <div class="mt-2 flex items-center gap-2">
                            <div class="flex-grow h-1.5 bg-surface-container-high rounded-full overflow-hidden">
                                <?php $progress = $t['total_siswa'] > 0 ? round($t['sudah_kumpul'] / $t['total_siswa'] * 100) : 0; ?>
                                <div class="h-full bg-primary rounded-full" style="width: <?= $progress ?>%"></div>
                            </div>
                            <span class="font-label-sm text-on-surface-variant"><?= $progress ?>%</span>
                        </div>
                    </div>
                    <?php endforeach; ?>
                <?php endif; ?>
            </div>
        </section>

        <!-- Need Grading -->
        <section class="bg-surface-container-lowest rounded-lg soft-shadow border border-outline-variant">
            <div class="p-6 border-b border-outline-variant flex items-center justify-between">
                <h3 class="font-title-sm flex items-center gap-2"><span class="material-symbols-outlined text-error">rate_review</span> Perlu Dinilai</h3>
                <a href="input_nilai.php" class="text-primary font-label-sm hover:underline">Nilai Sekarang</a>
            </div>
            <div class="divide-y divide-outline-variant">
                <?php if (empty($recentSubmissions)): ?>
                <div class="p-6 text-center text-on-surface-variant">Semua tugas sudah dinilai.</div>
                <?php else: ?>
                    <?php foreach ($recentSubmissions as $s): ?>
                    <div class="p-5 hover:bg-surface-container-low transition-colors">
                        <div class="flex items-center gap-3">
                            <div class="w-10 h-10 rounded-full bg-surface-container-high flex items-center justify-center">
                                <span class="material-symbols-outlined text-on-surface-variant">person</span>
                            </div>
                            <div class="flex-grow">
                                <p class="font-title-sm text-on-surface"><?= htmlspecialchars($s['nama_siswa']) ?></p>
                                <p class="font-label-sm text-on-surface-variant"><?= htmlspecialchars($s['judul_tugas']) ?></p>
                            </div>
                            <span class="px-2 py-1 bg-amber-100 text-amber-800 rounded text-[10px] font-bold">Waiting</span>
                        </div>
                    </div>
                    <?php endforeach; ?>
                <?php endif; ?>
            </div>
        </section>
    </div>

    <!-- Upcoming Deadlines -->
    <?php if (!empty($deadlines)): ?>
    <section class="bg-surface-container-lowest rounded-lg soft-shadow border border-outline-variant">
        <div class="p-6 border-b border-outline-variant flex items-center justify-between">
            <h3 class="font-title-sm flex items-center gap-2"><span class="material-symbols-outlined text-warning">alarm</span> Deadline Mendatang (7 Hari)</h3>
        </div>
        <div class="divide-y divide-outline-variant">
            <?php foreach ($deadlines as $d): ?>
            <div class="p-5 flex items-center justify-between">
                <div>
                    <p class="font-title-sm text-on-surface"><?= htmlspecialchars($d['judul']) ?></p>
                    <p class="font-label-sm text-on-surface-variant"><?= htmlspecialchars($d['nama_kelas']) ?> — Deadline <?= date('d M Y H:i', strtotime($d['deadline'])) ?></p>
                </div>
                <div class="flex items-center gap-2">
                    <span class="font-label-sm text-on-surface-variant"><?= $d['sudah_kumpul'] ?>/<?= $d['total_siswa'] ?></span>
                    <div class="w-20 h-1.5 bg-surface-container-high rounded-full overflow-hidden">
                        <?php $p = $d['total_siswa'] > 0 ? round($d['sudah_kumpul'] / $d['total_siswa'] * 100) : 0; ?>
                        <div class="h-full <?= $p < 30 ? 'bg-error' : 'bg-warning' ?> rounded-full" style="width: <?= $p ?>%"></div>
                    </div>
                </div>
            </div>
            <?php endforeach; ?>
        </div>
    </section>
    <?php endif; ?>

    <!-- Quick Actions -->
    <div class="grid grid-cols-1 sm:grid-cols-3 gap-gutter">
        <a href="upload_materi.php" class="flex items-center gap-4 p-5 bg-surface-container-lowest rounded-lg soft-shadow border border-outline-variant hover:border-primary hover:bg-primary-container/5 transition-all group">
            <div class="w-12 h-12 rounded-full bg-primary-container flex items-center justify-center text-primary group-hover:scale-110 transition-transform">
                <span class="material-symbols-outlined">upload_file</span>
            </div>
            <div><p class="font-title-sm text-on-surface">Upload Materi</p><p class="font-label-sm text-on-surface-variant">Bagikan materi ajar</p></div>
        </a>
        <a href="kelola_tugas.php" class="flex items-center gap-4 p-5 bg-surface-container-lowest rounded-lg soft-shadow border border-outline-variant hover:border-primary hover:bg-primary-container/5 transition-all group">
            <div class="w-12 h-12 rounded-full bg-tertiary-container flex items-center justify-center text-tertiary group-hover:scale-110 transition-transform">
                <span class="material-symbols-outlined">playlist_add</span>
            </div>
            <div><p class="font-title-sm text-on-surface">Buat Tugas</p><p class="font-label-sm text-on-surface-variant">Berikan tugas ke siswa</p></div>
        </a>
        <a href="absensi.php" class="flex items-center gap-4 p-5 bg-surface-container-lowest rounded-lg soft-shadow border border-outline-variant hover:border-primary hover:bg-primary-container/5 transition-all group">
            <div class="w-12 h-12 rounded-full bg-secondary-container flex items-center justify-center text-secondary group-hover:scale-110 transition-transform">
                <span class="material-symbols-outlined">fact_check</span>
            </div>
            <div><p class="font-title-sm text-on-surface">Absensi</p><p class="font-label-sm text-on-surface-variant">Catat kehadiran siswa</p></div>
        </a>
    </div>
</div>
<?php require_once __DIR__ . '/../../includes/footer.php'; ?>
