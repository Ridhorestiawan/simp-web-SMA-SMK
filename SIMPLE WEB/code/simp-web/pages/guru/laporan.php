<?php
$title = 'Laporan';
$currentPage = 'laporan';
require_once __DIR__ . '/../../includes/header.php';
require_once __DIR__ . '/../../config/database.php';

$idGuru = $_SESSION['user_id'];

// Get teacher's classes
$kelasList = $pdo->prepare("
    SELECT DISTINCT k.* FROM kelas k
    JOIN tugas t ON t.id_kelas = k.id_kelas
    WHERE t.id_guru = ?
    ORDER BY k.nama_kelas
");
$kelasList->execute([$idGuru]);
$kelasList = $kelasList->fetchAll();

$selectedKelas = $_GET['id_kelas'] ?? 0;
$selectedTugas = $_GET['id_tugas'] ?? 0;
$view = $_GET['view'] ?? 'nilai';

$tugasList = [];
if ($selectedKelas) {
    $tl = $pdo->prepare("SELECT * FROM tugas WHERE id_guru = ? AND id_kelas = ? ORDER BY created_at DESC");
    $tl->execute([$idGuru, $selectedKelas]);
    $tugasList = $tl->fetchAll();
}

// Statistik per kelas
$statsKelas = [];
foreach ($kelasList as $k) {
    $s = $pdo->prepare("
        SELECT
            (SELECT COUNT(*) FROM kelas_siswa WHERE id_kelas = ?) as total_siswa,
            (SELECT COUNT(*) FROM tugas WHERE id_kelas = ? AND id_guru = ?) as total_tugas,
            (SELECT ROUND(AVG(pt.nilai), 1) FROM pengumpulan_tugas pt JOIN tugas t ON pt.id_tugas = t.id_tugas WHERE t.id_kelas = ? AND t.id_guru = ? AND pt.nilai IS NOT NULL) as rata_nilai
    ");
    $s->execute([$k['id_kelas'], $k['id_kelas'], $idGuru, $k['id_kelas'], $idGuru]);
    $statsKelas[$k['id_kelas']] = $s->fetch();
}

// Detail nilai per tugas
$detailNilai = [];
if ($selectedTugas) {
    $dn = $pdo->prepare("
        SELECT u.nama as nama_siswa, pt.status, pt.nilai, pt.catatan_guru, pt.waktu_kumpul
        FROM pengumpulan_tugas pt
        JOIN users u ON pt.id_siswa = u.id
        JOIN kelas_siswa ks ON ks.id_siswa = u.id
        WHERE pt.id_tugas = ? AND ks.id_kelas = ?
        ORDER BY u.nama ASC
    ");
    $dn->execute([$selectedTugas, $selectedKelas]);
    $detailNilai = $dn->fetchAll();
}

// Absensi stats per kelas
$absensiStats = [];
if ($selectedKelas) {
    $as = $pdo->prepare("
        SELECT ad.status, COUNT(*) as total
        FROM absensi a
        JOIN absensi_detail ad ON a.id_absensi = ad.id_absensi
        WHERE a.id_kelas = ?
        GROUP BY ad.status
    ");
    $as->execute([$selectedKelas]);
    $absensiStats = $as->fetchAll();
    $totalAbsensi = array_sum(array_column($absensiStats, 'total'));
}
?>
<div class="max-w-[1440px] mx-auto space-y-8">
    <div>
        <h2 class="font-display-lg text-display-lg text-on-surface">Laporan Akademik</h2>
        <p class="text-on-surface-variant">Pantau perkembangan akademik dan kehadiran kelas.</p>
    </div>

    <!-- Cards per Kelas -->
    <div class="grid grid-cols-1 md:grid-cols-3 gap-gutter">
        <?php foreach ($kelasList as $k): ?>
        <?php $sk = $statsKelas[$k['id_kelas']] ?? ['total_siswa'=>0,'total_tugas'=>0,'rata_nilai'=>0]; ?>
        <a href="?id_kelas=<?= $k['id_kelas'] ?>&view=<?= htmlspecialchars($view, ENT_QUOTES, 'UTF-8') ?>" class="bg-surface-container-lowest p-6 rounded-lg soft-shadow border <?= $selectedKelas == $k['id_kelas'] ? 'border-primary ring-2 ring-primary' : 'border-outline-variant hover:border-primary' ?> transition-all">
            <div class="flex items-center justify-between mb-4">
                <h3 class="font-title-sm text-on-surface"><?= htmlspecialchars($k['nama_kelas']) ?></h3>
                <span class="w-10 h-10 rounded-full bg-primary-container/20 flex items-center justify-center text-primary">
                    <span class="material-symbols-outlined">school</span>
                </span>
            </div>
            <div class="grid grid-cols-3 gap-2 text-center">
                <div><p class="text-2xl font-bold"><?= $sk['total_siswa'] ?></p><p class="text-[10px] text-on-surface-variant uppercase tracking-wider">Siswa</p></div>
                <div><p class="text-2xl font-bold"><?= $sk['total_tugas'] ?></p><p class="text-[10px] text-on-surface-variant uppercase tracking-wider">Tugas</p></div>
                <div><p class="text-2xl font-bold"><?= $sk['rata_nilai'] ?: '-' ?></p><p class="text-[10px] text-on-surface-variant uppercase tracking-wider">Rata²</p></div>
            </div>
        </a>
        <?php endforeach; ?>
    </div>

    <?php if ($selectedKelas): ?>
    <!-- Tab Nilai / Absensi -->
    <div class="flex gap-2 border-b border-outline-variant">
        <a href="?id_kelas=<?= htmlspecialchars($selectedKelas, ENT_QUOTES, 'UTF-8') ?>&view=nilai" class="px-5 py-3 font-label-sm uppercase tracking-wider border-b-2 transition-all <?= $view === 'nilai' ? 'border-primary text-primary' : 'border-transparent text-on-surface-variant hover:text-on-surface' ?>">Rekap Nilai</a>
        <a href="?id_kelas=<?= htmlspecialchars($selectedKelas, ENT_QUOTES, 'UTF-8') ?>&view=absensi" class="px-5 py-3 font-label-sm uppercase tracking-wider border-b-2 transition-all <?= $view === 'absensi' ? 'border-primary text-primary' : 'border-transparent text-on-surface-variant hover:text-on-surface' ?>">Rekap Absensi</a>
    </div>

    <?php if ($view === 'nilai'): ?>
        <div class="grid grid-cols-1 lg:grid-cols-4 gap-gutter">
            <div class="lg:col-span-1 space-y-2">
                <p class="font-label-sm text-on-surface-variant">Pilih Tugas</p>
                <?php if (empty($tugasList)): ?>
                    <p class="text-on-surface-variant text-sm py-2">Belum ada tugas.</p>
                <?php else: ?>
                    <?php foreach ($tugasList as $t): ?>
                    <a href="?id_kelas=<?= htmlspecialchars($selectedKelas, ENT_QUOTES, 'UTF-8') ?>&view=nilai&id_tugas=<?= $t['id_tugas'] ?>" class="block p-4 rounded-lg border <?= $selectedTugas == $t['id_tugas'] ? 'border-primary bg-primary-container/10' : 'border-outline-variant hover:border-primary' ?> transition-all">
                        <p class="font-title-sm text-on-surface"><?= htmlspecialchars($t['judul']) ?></p>
                        <p class="font-label-sm text-on-surface-variant">Deadline: <?= date('d/m/y', strtotime($t['deadline'])) ?></p>
                    </a>
                    <?php endforeach; ?>
                <?php endif; ?>
            </div>
            <div class="lg:col-span-3">
                <?php if ($selectedTugas && !empty($detailNilai)): ?>
                    <section class="bg-surface-container-lowest rounded-lg soft-shadow border border-outline-variant overflow-hidden">
                        <div class="p-6 border-b border-outline-variant">
                            <h3 class="font-title-sm">Detail Nilai</h3>
                        </div>
                        <div class="overflow-x-auto">
                            <table class="w-full text-left border-collapse">
                                <thead class="bg-surface-container-low">
                                    <tr>
                                        <th class="px-6 py-4 font-label-sm text-on-surface-variant uppercase tracking-wider">Siswa</th>
                                        <th class="px-6 py-4 font-label-sm text-on-surface-variant uppercase tracking-wider">Status</th>
                                        <th class="px-6 py-4 font-label-sm text-on-surface-variant uppercase tracking-wider">Nilai</th>
                                        <th class="px-6 py-4 font-label-sm text-on-surface-variant uppercase tracking-wider hidden md:table-cell">Catatan</th>
                                    </tr>
                                </thead>
                                <tbody class="divide-y divide-outline-variant">
                                    <?php foreach ($detailNilai as $d): ?>
                                    <tr class="hover:bg-surface-container-low transition-colors">
                                        <td class="px-6 py-4 font-body-md text-on-surface"><?= htmlspecialchars($d['nama_siswa']) ?></td>
                                        <td class="px-6 py-4">
                                            <span class="px-3 py-1 rounded-full text-[10px] font-bold <?= $d['status'] === 'dinilai' ? 'bg-green-100 text-green-800' : ($d['status'] === 'dikumpulkan' ? 'bg-amber-100 text-amber-800' : 'bg-gray-100 text-gray-600') ?>"><?= ucfirst($d['status']) ?></span>
                                        </td>
                                        <td class="px-6 py-4 font-bold text-lg <?= ($d['nilai'] ?? 0) >= 75 ? 'text-success' : ($d['nilai'] ? 'text-error' : 'text-on-surface-variant') ?>"><?= $d['nilai'] ?? '-' ?></td>
                                        <td class="px-6 py-4 text-on-surface-variant hidden md:table-cell"><?= htmlspecialchars($d['catatan_guru'] ?? '-') ?></td>
                                    </tr>
                                    <?php endforeach; ?>
                                </tbody>
                            </table>
                        </div>
                    </section>
                <?php elseif ($selectedTugas): ?>
                    <div class="flex flex-col items-center justify-center py-16 text-center">
                        <span class="material-symbols-outlined text-outline text-5xl mb-3">assignment</span>
                        <p class="text-on-surface-variant">Belum ada data pengumpulan.</p>
                    </div>
                <?php else: ?>
                    <div class="flex flex-col items-center justify-center py-16 text-center">
                        <span class="material-symbols-outlined text-outline text-5xl mb-3">left_panel_open</span>
                        <p class="text-on-surface-variant">Pilih tugas dari sebelah kiri.</p>
                    </div>
                <?php endif; ?>
            </div>
        </div>
    <?php elseif ($view === 'absensi'): ?>
        <section class="bg-surface-container-lowest rounded-lg soft-shadow border border-outline-variant overflow-hidden">
            <div class="p-6 border-b border-outline-variant">
                <h3 class="font-title-sm">Rekap Absensi</h3>
            </div>
            <?php if (!empty($absensiStats)): ?>
            <div class="p-6 space-y-4">
                <?php $colors = ['hadir' => 'bg-success', 'sakit' => 'bg-yellow-500', 'izin' => 'bg-blue-500', 'alfa' => 'bg-error']; ?>
                <?php foreach ($absensiStats as $a): ?>
                <?php $pct = $totalAbsensi > 0 ? round($a['total'] / $totalAbsensi * 100, 1) : 0; ?>
                <div class="flex items-center gap-4">
                    <span class="w-20 font-label-sm text-on-surface-variant uppercase"><?= $a['status'] ?></span>
                    <div class="flex-grow h-6 bg-surface-container-high rounded-full overflow-hidden">
                        <div class="h-full <?= $colors[$a['status']] ?? 'bg-primary' ?> rounded-full transition-all" style="width: <?= $pct ?>%"></div>
                    </div>
                    <span class="w-16 text-right font-title-sm"><?= $a['total'] ?> (<?= $pct ?>%)</span>
                </div>
                <?php endforeach; ?>
            </div>
            <?php else: ?>
            <div class="p-12 text-center text-on-surface-variant">Belum ada data absensi.</div>
            <?php endif; ?>
        </section>
    <?php endif; ?>
    <?php endif; ?>
</div>
<?php require_once __DIR__ . '/../../includes/footer.php'; ?>
