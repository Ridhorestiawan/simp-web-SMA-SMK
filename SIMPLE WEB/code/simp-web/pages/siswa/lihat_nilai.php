<?php
$title = 'Lihat Nilai';
$currentPage = 'nilai';
require_once __DIR__ . '/../../includes/header.php';
require_once __DIR__ . '/../../config/database.php';

$idSiswa = $_SESSION['user_id'];

$kelasSiswa = $pdo->prepare("
    SELECT k.id_kelas, k.nama_kelas FROM kelas_siswa ks
    JOIN kelas k ON ks.id_kelas = k.id_kelas
    WHERE ks.id_siswa = ?
");
$kelasSiswa->execute([$idSiswa]);
$kelasSiswa = $kelasSiswa->fetch();

$nilaiList = [];
$stats = ['total' => 0, 'dinilai' => 0, 'rata2' => 0, 'tertinggi' => 0, 'terendah' => 0];

if ($kelasSiswa) {
    $nl = $pdo->prepare("
        SELECT t.judul as judul_tugas, t.deadline, u.nama as nama_guru,
            pt.status, pt.nilai, pt.catatan_guru, pt.waktu_kumpul
        FROM tugas t
        LEFT JOIN pengumpulan_tugas pt ON pt.id_tugas = t.id_tugas AND pt.id_siswa = ?
        JOIN users u ON t.id_guru = u.id
        WHERE t.id_kelas = ?
        ORDER BY t.deadline DESC
    ");
    $nl->execute([$idSiswa, $kelasSiswa['id_kelas']]);
    $nilaiList = $nl->fetchAll();

    $total = count($nilaiList);
    $dinilai = 0;
    $nilaiArr = [];
    foreach ($nilaiList as $n) {
        if ($n['status'] === 'dinilai' && $n['nilai'] !== null) {
            $dinilai++;
            $nilaiArr[] = $n['nilai'];
        }
    }
    $stats['total'] = $total;
    $stats['dinilai'] = $dinilai;
    $stats['rata2'] = !empty($nilaiArr) ? number_format(array_sum($nilaiArr) / count($nilaiArr), 1) : 0;
    $stats['tertinggi'] = !empty($nilaiArr) ? max($nilaiArr) : 0;
    $stats['terendah'] = !empty($nilaiArr) ? min($nilaiArr) : 0;
}
?>
<div class="max-w-[1440px] mx-auto space-y-8">
    <div>
        <h2 class="font-display-lg text-display-lg text-on-surface">Nilai Akademik</h2>
        <p class="text-on-surface-variant">
            <?php if ($kelasSiswa): ?>
                Kelas <?= htmlspecialchars($kelasSiswa['nama_kelas']) ?> — Pantau perkembangan nilai kamu.
            <?php else: ?>
                Kamu belum terdaftar di kelas mana pun.
            <?php endif; ?>
        </p>
    </div>

    <?php if (!$kelasSiswa): ?>
    <div class="flex flex-col items-center justify-center py-20 px-4 text-center">
        <div class="w-20 h-20 mb-4 rounded-full bg-surface-container-high flex items-center justify-center">
            <span class="material-symbols-outlined text-outline text-4xl">school</span></div>
        <h3 class="font-headline-md text-on-surface">Belum ada kelas</h3>
        <p class="font-body-md text-on-surface-variant mt-1">Hubungi admin untuk mendaftarkan kelas.</p>
    </div>
    <?php elseif (empty($nilaiList)): ?>
    <div class="flex flex-col items-center justify-center py-20 px-4 text-center">
        <div class="w-20 h-20 mb-4 rounded-full bg-surface-container-high flex items-center justify-center">
            <span class="material-symbols-outlined text-outline text-4xl">assessment</span></div>
        <h3 class="font-headline-md text-on-surface">Belum ada tugas</h3>
        <p class="font-body-md text-on-surface-variant mt-1">Guru belum memberikan tugas untuk kelas ini.</p>
    </div>
    <?php else: ?>
    <!-- Stats Summary -->
    <div class="grid grid-cols-2 md:grid-cols-4 gap-gutter">
        <div class="bg-surface-container-lowest p-6 rounded-lg soft-shadow border border-outline-variant">
            <p class="font-label-sm text-on-surface-variant">Total Tugas</p>
            <p class="text-[28px] font-bold"><?= $stats['total'] ?></p>
        </div>
        <div class="bg-surface-container-lowest p-6 rounded-lg soft-shadow border border-outline-variant">
            <p class="font-label-sm text-on-surface-variant">Sudah Dinilai</p>
            <p class="text-[28px] font-bold"><?= $stats['dinilai'] ?></p>
        </div>
        <div class="bg-surface-container-lowest p-6 rounded-lg soft-shadow border border-outline-variant">
            <p class="font-label-sm text-on-surface-variant">Rata-rata</p>
            <p class="text-[28px] font-bold <?= $stats['rata2'] >= 75 ? 'text-success' : 'text-error' ?>"><?= $stats['rata2'] ?></p>
        </div>
        <div class="bg-surface-container-lowest p-6 rounded-lg soft-shadow border border-outline-variant">
            <p class="font-label-sm text-on-surface-variant">Tertinggi / Terendah</p>
            <p class="text-[28px] font-bold"><?= $stats['tertinggi'] ?> / <?= $stats['terendah'] ?></p>
        </div>
    </div>

    <!-- Grade Table -->
    <section class="bg-surface-container-lowest rounded-lg soft-shadow border border-outline-variant overflow-hidden">
        <div class="overflow-x-auto">
            <table class="w-full text-left border-collapse">
                <thead class="bg-surface-container-low">
                    <tr>
                        <th class="px-6 py-4 font-label-sm text-on-surface-variant uppercase tracking-wider">Tugas</th>
                        <th class="px-6 py-4 font-label-sm text-on-surface-variant uppercase tracking-wider">Guru</th>
                        <th class="px-6 py-4 font-label-sm text-on-surface-variant uppercase tracking-wider">Deadline</th>
                        <th class="px-6 py-4 font-label-sm text-on-surface-variant uppercase tracking-wider">Status</th>
                        <th class="px-6 py-4 font-label-sm text-on-surface-variant uppercase tracking-wider">Nilai</th>
                        <th class="px-6 py-4 font-label-sm text-on-surface-variant uppercase tracking-wider hidden md:table-cell">Catatan</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-outline-variant">
                    <?php foreach ($nilaiList as $n): ?>
                    <?php
                        $deadlinePassed = strtotime($n['deadline']) < time();
                        $status = $n['status'] ?? 'belum';
                        $statusLabel = match($status) {
                            'dinilai' => ['Sudah Dinilai', 'bg-green-100 text-green-800'],
                            'dikumpulkan' => ['Dikumpulkan', 'bg-blue-100 text-blue-800'],
                            'terlambat' => ['Terlambat', 'bg-red-100 text-red-800'],
                            default => ['Belum', 'bg-gray-100 text-gray-600'],
                        };
                    ?>
                    <tr class="hover:bg-surface-container-low transition-colors">
                        <td class="px-6 py-4 font-title-sm text-on-surface"><?= htmlspecialchars($n['judul_tugas']) ?></td>
                        <td class="px-6 py-4 text-on-surface-variant"><?= htmlspecialchars($n['nama_guru']) ?></td>
                        <td class="px-6 py-4">
                            <span class="font-label-sm <?= $deadlinePassed ? 'text-error' : 'text-on-surface-variant' ?>"><?= date('d/m/Y', strtotime($n['deadline'])) ?></span>
                        </td>
                        <td class="px-6 py-4">
                            <span class="px-3 py-1 rounded-full text-[10px] font-bold <?= $statusLabel[1] ?>"><?= $statusLabel[0] ?></span>
                        </td>
                        <td class="px-6 py-4">
                            <?php if ($n['nilai'] !== null): ?>
                            <span class="font-bold text-lg <?= $n['nilai'] >= 75 ? 'text-success' : 'text-error' ?>"><?= $n['nilai'] ?></span>
                            <?php else: ?>
                            <span class="text-on-surface-variant">—</span>
                            <?php endif; ?>
                        </td>
                        <td class="px-6 py-4 text-on-surface-variant hidden md:table-cell"><?= htmlspecialchars($n['catatan_guru'] ?? '-') ?></td>
                    </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        </div>
    </section>

    <!-- Legend -->
    <div class="flex flex-wrap gap-6 text-sm bg-surface-container-lowest p-4 rounded-lg border border-outline-variant">
        <div class="flex items-center gap-2"><span class="w-3 h-3 rounded-full bg-green-500"></span><span class="text-on-surface-variant">≥ 75 (Tuntas)</span></div>
        <div class="flex items-center gap-2"><span class="w-3 h-3 rounded-full bg-error"></span><span class="text-on-surface-variant">&lt; 75 (Belum Tuntas)</span></div>
        <div class="flex items-center gap-2"><span class="w-3 h-3 rounded-full bg-blue-400"></span><span class="text-on-surface-variant">Dikumpulkan</span></div>
        <div class="flex items-center gap-2"><span class="w-3 h-3 rounded-full bg-gray-300"></span><span class="text-on-surface-variant">Belum Dikumpulkan</span></div>
    </div>
    <?php endif; ?>
</div>
<?php require_once __DIR__ . '/../../includes/footer.php'; ?>
