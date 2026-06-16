<?php
$title = 'Laporan Akademik';
$currentPage = 'laporan';
require_once __DIR__ . '/../../includes/header.php';
require_once __DIR__ . '/../../config/database.php';

$kelasList = $pdo->query("SELECT * FROM kelas ORDER BY nama_kelas")->fetchAll();
$filterKelas = $_GET['kelas'] ?? '';

$rekapNilai = [];
$rekapAbsensi = [];
$totalHadir = 0; $totalIzin = 0; $totalSakit = 0; $totalAlfa = 0; $totalAbsen = 0;

if ($filterKelas) {
    $rekapNilai = $pdo->prepare("
        SELECT u.nama, u.id,
            ROUND(AVG(pt.nilai), 1) as avg_nilai,
            MAX(pt.nilai) as max_nilai,
            MIN(pt.nilai) as min_nilai
        FROM users u
        JOIN kelas_siswa ks ON u.id = ks.id_siswa
        LEFT JOIN pengumpulan_tugas pt ON u.id = pt.id_siswa
        WHERE ks.id_kelas = ? AND u.role = 'siswa'
        GROUP BY u.id, u.nama
        ORDER BY avg_nilai DESC
    ");
    $rekapNilai->execute([$filterKelas]);
    $rekapNilai = $rekapNilai->fetchAll();

    $siswaIds = $pdo->prepare("SELECT id_siswa FROM kelas_siswa WHERE id_kelas = ?");
    $siswaIds->execute([$filterKelas]);
    $siswaIds = $siswaIds->fetchAll(PDO::FETCH_COLUMN);

    if (!empty($siswaIds)) {
        $placeholders = implode(',', array_fill(0, count($siswaIds), '?'));
        $params = array_merge([$filterKelas], $siswaIds);
        $rekapAbsensi = $pdo->prepare("
            SELECT ad.status, COUNT(*) as jumlah
            FROM absensi_detail ad
            JOIN absensi a ON ad.id_absensi = a.id_absensi
            WHERE a.id_kelas = ? AND ad.id_siswa IN ($placeholders)
            GROUP BY ad.status
        ");
        $rekapAbsensi->execute($params);
        $rekapAbsensi = $rekapAbsensi->fetchAll();

        foreach ($rekapAbsensi as $r) {
            switch ($r['status']) {
                case 'hadir': $totalHadir = $r['jumlah']; break;
                case 'izin':  $totalIzin = $r['jumlah']; break;
                case 'sakit': $totalSakit = $r['jumlah']; break;
                case 'alfa':  $totalAlfa = $r['jumlah']; break;
            }
        }
        $totalAbsen = $totalHadir + $totalIzin + $totalSakit + $totalAlfa;
    }
}

$siswaList = [];
if ($filterKelas) {
    $siswaList = $pdo->prepare("SELECT u.id, u.nama FROM users u JOIN kelas_siswa ks ON u.id = ks.id_siswa WHERE ks.id_kelas = ? ORDER BY u.nama");
    $siswaList->execute([$filterKelas]);
    $siswaList = $siswaList->fetchAll();
}
?>
<div class="max-w-[1440px] mx-auto">
    <div class="flex flex-col md:flex-row md:items-end justify-between gap-4 mb-8">
        <div>
            <h2 class="font-display-lg text-display-lg-mobile md:text-display-lg text-on-surface">Laporan Akademik</h2>
            <p class="font-body-md text-body-md text-on-surface-variant">Analisis rekapitulasi nilai dan kehadiran siswa.</p>
        </div>
        <div class="flex gap-3">
            <button class="flex items-center gap-2 px-4 py-2 border border-outline-variant bg-surface text-on-surface rounded hover:bg-surface-container-high transition-all soft-shadow" onclick="alert('Fitur export akan segera tersedia.')">
                <span class="material-symbols-outlined text-[20px]">description</span>
                <span class="font-label-sm">Export PDF</span>
            </button>
            <button class="flex items-center gap-2 px-4 py-2 border border-outline-variant bg-surface text-on-surface rounded hover:bg-surface-container-high transition-all soft-shadow" onclick="alert('Fitur export akan segera tersedia.')">
                <span class="material-symbols-outlined text-[20px]">table_view</span>
                <span class="font-label-sm">Excel</span>
            </button>
        </div>
    </div>

    <!-- Filter -->
    <form method="GET" class="bg-surface-container-lowest p-6 rounded-lg soft-shadow mb-8 border border-outline-variant/30">
        <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-4 gap-6">
            <div class="flex flex-col gap-1.5">
                <label class="font-label-sm text-label-sm text-on-surface-variant">Kelas</label>
                <select name="kelas" class="form-select w-full rounded border-outline-variant focus:border-primary focus:ring-1 focus:ring-primary text-body-md" onchange="this.form.submit()">
                    <option value="">— Pilih Kelas —</option>
                    <?php foreach ($kelasList as $k): ?>
                    <option value="<?= $k['id_kelas'] ?>" <?= $filterKelas == $k['id_kelas'] ? 'selected' : '' ?>><?= htmlspecialchars($k['nama_kelas']) ?></option>
                    <?php endforeach; ?>
                </select>
            </div>
            <?php if ($filterKelas): ?>
            <div class="flex flex-col gap-1.5">
                <label class="font-label-sm text-label-sm text-on-surface-variant">Siswa</label>
                <select class="form-select w-full rounded border-outline-variant focus:border-primary focus:ring-1 focus:ring-primary text-body-md">
                    <option value="">Semua Siswa</option>
                    <?php foreach ($siswaList as $s): ?>
                    <option value="<?= $s['id'] ?>"><?= htmlspecialchars($s['nama']) ?></option>
                    <?php endforeach; ?>
                </select>
            </div>
            <?php endif; ?>
            <div class="flex flex-col gap-1.5">
                <label class="font-label-sm text-label-sm text-on-surface-variant">Periode</label>
                <select class="form-select w-full rounded border-outline-variant focus:border-primary focus:ring-1 focus:ring-primary text-body-md">
                    <option>Semester Ganjil 2024/2025</option>
                    <option>Semester Genap 2024/2025</option>
                </select>
            </div>
        </div>
    </form>

    <?php if ($filterKelas): ?>
    <div class="grid grid-cols-1 lg:grid-cols-12 gap-8">
        <!-- Rekap Nilai -->
        <section class="lg:col-span-8 bg-surface-container-lowest rounded-lg soft-shadow border border-outline-variant/30 overflow-hidden">
            <div class="p-6 border-b border-outline-variant/30">
                <h3 class="font-title-sm text-title-sm text-on-surface">Rekap Nilai Siswa</h3>
            </div>
            <div class="overflow-x-auto">
                <table class="w-full text-left border-collapse">
                    <thead>
                        <tr class="bg-surface-container-low border-b border-outline-variant">
                            <th class="p-4 font-label-sm text-label-sm text-on-surface-variant uppercase tracking-wider">Nama Siswa</th>
                            <th class="p-4 font-label-sm text-label-sm text-on-surface-variant uppercase tracking-wider text-center">Rata-rata</th>
                            <th class="p-4 font-label-sm text-label-sm text-on-surface-variant uppercase tracking-wider text-center">Tertinggi</th>
                            <th class="p-4 font-label-sm text-label-sm text-on-surface-variant uppercase tracking-wider text-center">Terendah</th>
                            <th class="p-4 font-label-sm text-label-sm text-on-surface-variant uppercase tracking-wider text-center">Status</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-outline-variant/30">
                        <?php if (empty($rekapNilai)): ?>
                        <tr><td colspan="5" class="p-8 text-center font-body-md text-on-surface-variant">Tidak ada data nilai untuk kelas ini.</td></tr>
                        <?php else: ?>
                            <?php foreach ($rekapNilai as $r): ?>
                            <tr class="hover:bg-surface-container-low/50 transition-colors">
                                <td class="p-4 font-body-md text-on-surface"><?= htmlspecialchars($r['nama']) ?></td>
                                <td class="p-4 font-body-md text-center font-semibold <?= $r['avg_nilai'] ? '' : 'text-on-surface-variant' ?>"><?= $r['avg_nilai'] ?: '-' ?></td>
                                <td class="p-4 font-body-md text-center text-primary"><?= $r['max_nilai'] ?: '-' ?></td>
                                <td class="p-4 font-body-md text-center"><?= $r['min_nilai'] ?: '-' ?></td>
                                <td class="p-4 text-center">
                                    <?php if ($r['avg_nilai']): ?>
                                        <?php if ($r['avg_nilai'] >= 85): ?>
                                        <span class="px-3 py-1 bg-green-100 text-green-800 text-[10px] font-bold uppercase rounded-full">Excellent</span>
                                        <?php elseif ($r['avg_nilai'] >= 70): ?>
                                        <span class="px-3 py-1 bg-green-100 text-green-800 text-[10px] font-bold uppercase rounded-full">Passed</span>
                                        <?php else: ?>
                                        <span class="px-3 py-1 bg-red-100 text-red-800 text-[10px] font-bold uppercase rounded-full">Remedial</span>
                                        <?php endif; ?>
                                    <?php else: ?>
                                    <span class="px-3 py-1 bg-surface-container-high text-on-surface-variant text-[10px] font-bold uppercase rounded-full">—</span>
                                    <?php endif; ?>
                                </td>
                            </tr>
                            <?php endforeach; ?>
                        <?php endif; ?>
                    </tbody>
                </table>
            </div>
        </section>

        <!-- Rekap Absensi -->
        <section class="lg:col-span-4 flex flex-col gap-8">
            <div class="bg-surface-container-lowest p-6 rounded-lg soft-shadow border border-outline-variant/30 flex-1">
                <div class="flex items-center justify-between mb-6">
                    <h3 class="font-title-sm text-title-sm text-on-surface">Rekap Absensi</h3>
                    <span class="material-symbols-outlined text-primary">trending_up</span>
                </div>
                <div class="space-y-6">
                    <div>
                        <div class="flex justify-between items-center mb-2">
                            <span class="font-body-md text-body-md text-on-surface">Hadir</span>
                            <span class="font-label-sm font-bold text-primary"><?= $totalAbsen ? round($totalHadir / $totalAbsen * 100) : 0 ?>%</span>
                        </div>
                        <div class="h-2 w-full bg-surface-container-high rounded-full overflow-hidden">
                            <div class="h-full bg-primary" style="width: <?= $totalAbsen ? round($totalHadir / $totalAbsen * 100) : 0 ?>%"></div>
                        </div>
                        <p class="mt-1 font-label-sm text-[10px] text-on-surface-variant uppercase"><?= $totalHadir ?> Sesi</p>
                    </div>
                    <div>
                        <div class="flex justify-between items-center mb-2">
                            <span class="font-body-md text-body-md text-on-surface">Izin / Sakit</span>
                            <span class="font-label-sm font-bold text-secondary"><?= $totalAbsen ? round(($totalIzin + $totalSakit) / $totalAbsen * 100) : 0 ?>%</span>
                        </div>
                        <div class="h-2 w-full bg-surface-container-high rounded-full overflow-hidden">
                            <div class="h-full bg-secondary" style="width: <?= $totalAbsen ? round(($totalIzin + $totalSakit) / $totalAbsen * 100) : 0 ?>%"></div>
                        </div>
                        <p class="mt-1 font-label-sm text-[10px] text-on-surface-variant uppercase"><?= $totalIzin + $totalSakit ?> Sesi</p>
                    </div>
                    <div>
                        <div class="flex justify-between items-center mb-2">
                            <span class="font-body-md text-body-md text-on-surface">Alfa</span>
                            <span class="font-label-sm font-bold text-error"><?= $totalAbsen ? round($totalAlfa / $totalAbsen * 100) : 0 ?>%</span>
                        </div>
                        <div class="h-2 w-full bg-surface-container-high rounded-full overflow-hidden">
                            <div class="h-full bg-error" style="width: <?= $totalAbsen ? round($totalAlfa / $totalAbsen * 100) : 0 ?>%"></div>
                        </div>
                        <p class="mt-1 font-label-sm text-[10px] text-on-surface-variant uppercase"><?= $totalAlfa ?> Sesi</p>
                    </div>
                </div>
            </div>
        </section>
    </div>

    <div class="mt-8 flex flex-wrap gap-4">
        <div class="px-4 py-2 bg-surface-container-high rounded-full border border-outline-variant flex items-center gap-2">
            <span class="w-2 h-2 rounded-full bg-primary"></span>
            <span class="font-label-sm text-label-sm text-on-surface-variant">Total Siswa: <?= count($rekapNilai) ?></span>
        </div>
        <div class="px-4 py-2 bg-surface-container-high rounded-full border border-outline-variant flex items-center gap-2">
            <span class="w-2 h-2 rounded-full bg-green-500"></span>
            <span class="font-label-sm text-label-sm text-on-surface-variant">Tuntas: <?= count(array_filter($rekapNilai, fn($r) => $r['avg_nilai'] && $r['avg_nilai'] >= 70)) ?></span>
        </div>
        <div class="px-4 py-2 bg-surface-container-high rounded-full border border-outline-variant flex items-center gap-2">
            <span class="w-2 h-2 rounded-full bg-error"></span>
            <span class="font-label-sm text-label-sm text-on-surface-variant">Remedial: <?= count(array_filter($rekapNilai, fn($r) => $r['avg_nilai'] && $r['avg_nilai'] < 70)) ?></span>
        </div>
    </div>
    <?php else: ?>
    <div class="flex flex-col items-center justify-center py-20 px-4 text-center">
        <div class="w-24 h-24 mb-4 rounded-full bg-surface-container-high flex items-center justify-center">
            <span class="material-symbols-outlined text-outline text-5xl">assessment</span>
        </div>
        <h3 class="font-headline-md text-headline-md text-on-surface">Pilih Kelas</h3>
        <p class="font-body-md text-body-md text-on-surface-variant mt-2 max-w-sm">Pilih kelas untuk melihat laporan nilai dan absensi.</p>
    </div>
    <?php endif; ?>
</div>
<?php require_once __DIR__ . '/../../includes/footer.php'; ?>
