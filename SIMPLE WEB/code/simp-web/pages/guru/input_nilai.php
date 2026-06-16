<?php
$title = 'Input Penilaian';
$currentPage = 'nilai';
require_once __DIR__ . '/../../includes/header.php';
require_once __DIR__ . '/../../config/database.php';

$idGuru = $_SESSION['user_id'];
$message = ''; $messageType = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['simpan_nilai'])) {
    if (!verify_csrf($_POST['_csrf_token'] ?? '')) {
        $message = 'Token CSRF tidak valid. Silakan refresh halaman.';
        $messageType = 'error';
    } else {
    $idTugas = $_POST['id_tugas'] ?? 0;
    $nilaiArr = $_POST['nilai'] ?? [];
    $catatanArr = $_POST['catatan'] ?? [];

    $stmt = $pdo->prepare("UPDATE pengumpulan_tugas SET nilai = ?, catatan_guru = ?, status = 'dinilai' WHERE id_pengumpulan = ?");

    foreach ($nilaiArr as $idPengumpulan => $nilai) {
        $n = is_numeric($nilai) && $nilai >= 0 && $nilai <= 100 ? (int)$nilai : null;
        $catatan = trim($catatanArr[$idPengumpulan] ?? '');
        $stmt->execute([$n, $catatan, $idPengumpulan]);
    }
    $message = 'Nilai berhasil disimpan.';
    $messageType = 'success';
    }
}

$tugasList = $pdo->prepare("SELECT t.*, k.nama_kelas FROM tugas t JOIN kelas k ON t.id_kelas = k.id_kelas WHERE t.id_guru = ? ORDER BY t.created_at DESC");
$tugasList->execute([$idGuru]);
$tugasList = $tugasList->fetchAll();

$kelasList = $pdo->query("SELECT k.*, COALESCE(u.nama, '(Belum ada)') as wali FROM kelas k LEFT JOIN users u ON k.wali_kelas = u.id ORDER BY k.nama_kelas")->fetchAll();

$selectedTugas = null; $siswaNilai = [];
if (isset($_GET['id_tugas'])) {
    $st = $pdo->prepare("SELECT t.*, k.nama_kelas FROM tugas t JOIN kelas k ON t.id_kelas = k.id_kelas WHERE t.id_tugas = ? AND t.id_guru = ?");
    $st->execute([$_GET['id_tugas'], $idGuru]);
    $selectedTugas = $st->fetch();

    if ($selectedTugas) {
        $sn = $pdo->prepare("
            SELECT pt.*, u.nama as nama_siswa, u.email
            FROM pengumpulan_tugas pt
            JOIN users u ON pt.id_siswa = u.id
            WHERE pt.id_tugas = ?
            ORDER BY pt.status ASC, u.nama ASC
        ");
        $sn->execute([$_GET['id_tugas']]);
        $siswaNilai = $sn->fetchAll();
    }
}
?>
<div class="max-w-[1440px] mx-auto space-y-8">
    <?php if ($message): ?>
        <div class="px-4 py-3 rounded-lg flex items-center gap-2 border bg-green-50 border-green-300 text-green-800">
            <span class="material-symbols-outlined text-lg">check_circle</span>
            <p class="font-body-md"><?= htmlspecialchars($message) ?></p>
        </div>
    <?php endif; ?>

    <div>
        <h2 class="font-display-lg text-display-lg text-on-surface">Input Penilaian</h2>
        <p class="text-on-surface-variant">Beri nilai dan catatan pada tugas yang dikumpulkan siswa.</p>
    </div>

    <!-- Pilih Tugas -->
    <section class="bg-surface-container-lowest p-6 rounded-lg soft-shadow border border-outline-variant">
        <h3 class="font-title-sm mb-4 flex items-center gap-2">
            <span class="material-symbols-outlined text-primary">assignment</span> Pilih Tugas
        </h3>
        <div class="grid grid-cols-1 md:grid-cols-4 gap-3">
            <?php if (empty($tugasList)): ?>
                <p class="md:col-span-4 text-on-surface-variant py-4 text-center">Belum ada tugas.</p>
            <?php else: ?>
                <?php foreach ($tugasList as $t): ?>
                <a href="?id_tugas=<?= $t['id_tugas'] ?>" class="p-4 rounded-lg border <?= ($selectedTugas && $selectedTugas['id_tugas'] == $t['id_tugas']) ? 'border-primary bg-primary-container/10 ring-2 ring-primary' : 'border-outline-variant hover:border-primary hover:bg-primary-container/5' ?> transition-all">
                    <p class="font-title-sm text-on-surface"><?= htmlspecialchars($t['judul']) ?></p>
                    <p class="font-label-sm text-on-surface-variant"><?= htmlspecialchars($t['nama_kelas']) ?></p>
                    <p class="font-label-sm text-on-surface-variant">Deadline: <?= date('d/m/Y', strtotime($t['deadline'])) ?></p>
                </a>
                <?php endforeach; ?>
            <?php endif; ?>
        </div>
    </section>

    <?php if ($selectedTugas && !empty($siswaNilai)): ?>
    <form method="POST">
        <?= csrf_field() ?>
        <input type="hidden" name="id_tugas" value="<?= $selectedTugas['id_tugas'] ?>">
        <section class="bg-surface-container-lowest rounded-lg soft-shadow border border-outline-variant overflow-hidden">
            <div class="p-6 border-b border-outline-variant flex flex-col sm:flex-row sm:items-center justify-between gap-3">
                <h3 class="font-title-sm"><?= htmlspecialchars($selectedTugas['judul']) ?> — <?= htmlspecialchars($selectedTugas['nama_kelas']) ?></h3>
                <button type="submit" name="simpan_nilai" class="px-6 py-2 bg-primary text-on-primary rounded font-label-sm hover:opacity-90 active:scale-95 transition-all flex items-center gap-2">
                    <span class="material-symbols-outlined text-[18px]">save</span> Simpan Semua
                </button>
            </div>
            <div class="overflow-x-auto">
                <table class="w-full text-left border-collapse">
                    <thead class="bg-surface-container-low">
                        <tr>
                            <th class="px-6 py-4 font-label-sm text-on-surface-variant uppercase tracking-wider">Siswa</th>
                            <th class="px-6 py-4 font-label-sm text-on-surface-variant uppercase tracking-wider">Status</th>
                            <th class="px-6 py-4 font-label-sm text-on-surface-variant uppercase tracking-wider">File</th>
                            <th class="px-6 py-4 font-label-sm text-on-surface-variant uppercase tracking-wider w-24">Nilai</th>
                            <th class="px-6 py-4 font-label-sm text-on-surface-variant uppercase tracking-wider hidden md:table-cell">Catatan Guru</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-outline-variant">
                        <?php foreach ($siswaNilai as $s): ?>
                        <tr class="hover:bg-surface-container-low transition-colors">
                            <td class="px-6 py-4">
                                <p class="font-title-sm text-on-surface"><?= htmlspecialchars($s['nama_siswa']) ?></p>
                            </td>
                            <td class="px-6 py-4">
                                <?php
                                    $statusMap = ['dikumpulkan' => ['Waiting','bg-amber-100 text-amber-800'], 'dinilai' => ['Done','bg-green-100 text-green-800'], 'terlambat' => ['Late','bg-red-100 text-red-800']];
                                    $st = $statusMap[$s['status']] ?? ['Unknown','bg-gray-100 text-gray-600'];
                                ?>
                                <span class="px-3 py-1 rounded-full text-[10px] font-bold <?= $st[1] ?>"><?= $st[0] ?></span>
                            </td>
                            <td class="px-6 py-4">
                                <?php if ($s['file']): ?>
                                <a href="../../assets/uploads/tugas/<?= urlencode($s['file']) ?>" target="_blank" class="inline-flex items-center gap-1 font-label-sm text-primary hover:underline">
                                    <span class="material-symbols-outlined text-[18px]">attach_file</span>
                                    Lihat
                                </a>
                                <?php else: ?>
                                <span class="font-label-sm text-on-surface-variant">—</span>
                                <?php endif; ?>
                            </td>
                            <td class="px-6 py-4">
                                <input type="number" name="nilai[<?= $s['id_pengumpulan'] ?>]" value="<?= htmlspecialchars($s['nilai'] ?? '') ?>" min="0" max="100" class="w-20 px-3 py-2 bg-surface border border-outline-variant rounded focus:ring-2 focus:ring-primary focus:border-primary outline-none text-center text-body-md" placeholder="—">
                            </td>
                            <td class="px-6 py-4 hidden md:table-cell">
                                <input type="text" name="catatan[<?= $s['id_pengumpulan'] ?>]" value="<?= htmlspecialchars($s['catatan_guru'] ?? '') ?>" class="w-full px-3 py-2 bg-transparent border border-transparent focus:border-primary focus:bg-surface rounded outline-none transition-all text-body-md" placeholder="Tambah catatan...">
                            </td>
                        </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            </div>
            <div class="p-6 border-t border-outline-variant flex justify-end">
                <button type="submit" name="simpan_nilai" class="px-8 py-2 bg-primary text-on-primary rounded font-label-sm hover:opacity-90 active:scale-95 transition-all flex items-center gap-2">
                    <span class="material-symbols-outlined text-[18px]">save</span> Simpan Semua Nilai
                </button>
            </div>
        </section>
    </form>
    <?php elseif ($selectedTugas): ?>
    <div class="flex flex-col items-center justify-center py-20 px-4 text-center">
        <div class="w-20 h-20 mb-4 rounded-full bg-surface-container-high flex items-center justify-center">
            <span class="material-symbols-outlined text-outline text-4xl">error_outline</span></div>
        <h3 class="font-headline-md text-on-surface">Belum ada pengumpulan</h3>
        <p class="font-body-md text-on-surface-variant mt-1">Siswa belum mengumpulkan tugas untuk tugas ini.</p>
    </div>
    <?php endif; ?>
</div>
<?php require_once __DIR__ . '/../../includes/footer.php'; ?>
