<?php
$title = 'Absensi Siswa';
$currentPage = 'absensi';
require_once __DIR__ . '/../../includes/header.php';
require_once __DIR__ . '/../../config/database.php';

$idGuru = $_SESSION['user_id'];
$message = ''; $messageType = '';

$kelasList = $pdo->query("SELECT k.*, COALESCE(u.nama, '-') as wali FROM kelas k LEFT JOIN users u ON k.wali_kelas = u.id ORDER BY k.nama_kelas")->fetchAll();

$selectedKelas = $_GET['id_kelas'] ?? 0;
$selectedDate  = $_GET['tanggal'] ?? date('Y-m-d');
$siswaList = [];

if ($selectedKelas) {
    $sl = $pdo->prepare("
        SELECT u.id, u.nama, u.email
        FROM kelas_siswa ks
        JOIN users u ON ks.id_siswa = u.id
        WHERE ks.id_kelas = ?
        ORDER BY u.nama ASC
    ");
    $sl->execute([$selectedKelas]);
    $siswaList = $sl->fetchAll();

    // Check existing
    $cek = $pdo->prepare("SELECT id_absensi FROM absensi WHERE id_kelas = ? AND tanggal = ?");
    $cek->execute([$selectedKelas, $selectedDate]);
    $existingAbsensi = $cek->fetch();

    $existingDetails = [];
    if ($existingAbsensi) {
        $d = $pdo->prepare("SELECT id_siswa, status FROM absensi_detail WHERE id_absensi = ?");
        $d->execute([$existingAbsensi['id_absensi']]);
        foreach ($d->fetchAll() as $r) {
            $existingDetails[$r['id_siswa']] = $r['status'];
        }
    }

    if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['simpan_absensi'])) {
        if (!verify_csrf($_POST['_csrf_token'] ?? '')) {
            $message = 'Token CSRF tidak valid. Silakan refresh halaman.';
            $messageType = 'error';
        } else {
        $tanggal = $_POST['tanggal'] ?? date('Y-m-d');
        $idKelas = $_POST['id_kelas'] ?? 0;
        $dataAbsen = $_POST['absen'] ?? [];

        // Cek existing atau buat baru
        $cek2 = $pdo->prepare("SELECT id_absensi FROM absensi WHERE id_kelas = ? AND tanggal = ?");
        $cek2->execute([$idKelas, $tanggal]);
        $ex = $cek2->fetch();

        if ($ex) {
            $idAbsensi = $ex['id_absensi'];
        } else {
            $pdo->prepare("INSERT INTO absensi (id_kelas, id_guru, tanggal) VALUES (?, ?, ?)")->execute([$idKelas, $idGuru, $tanggal]);
            $idAbsensi = $pdo->lastInsertId();
        }

        $pdo->prepare("DELETE FROM absensi_detail WHERE id_absensi = ?")->execute([$idAbsensi]);

        $stmt = $pdo->prepare("INSERT INTO absensi_detail (id_absensi, id_siswa, status) VALUES (?, ?, ?)");
        foreach ($dataAbsen as $idSiswa => $status) {
            if (in_array($status, ['hadir','sakit','izin','alfa'])) {
                $stmt->execute([$idAbsensi, $idSiswa, $status]);
            }
        }
        $message = 'Absensi berhasil disimpan.';
        $messageType = 'success';
        }
    }
}
?>
<div class="max-w-[1440px] mx-auto space-y-8">
    <?php if ($message): ?>
        <div class="px-4 py-3 rounded-lg flex items-center gap-2 border <?= $messageType === 'error' ? 'bg-error-container border-error text-on-error-container' : 'bg-green-50 border-green-300 text-green-800' ?>">
            <span class="material-symbols-outlined text-lg"><?= $messageType === 'error' ? 'error' : 'check_circle' ?></span>
            <p class="font-body-md"><?= htmlspecialchars($message) ?></p>
        </div>
    <?php endif; ?>

    <div>
        <h2 class="font-display-lg text-display-lg text-on-surface">Absensi Siswa</h2>
        <p class="text-on-surface-variant">Catat kehadiran siswa harian per kelas.</p>
    </div>

    <!-- Filter -->
    <form method="GET" class="grid grid-cols-1 md:grid-cols-3 gap-4">
        <div class="space-y-1">
            <label class="font-label-sm text-on-surface-variant">Kelas</label>
            <select name="id_kelas" onchange="this.form.submit()" class="w-full px-4 py-3 bg-surface-container-lowest border border-outline-variant rounded focus:ring-2 focus:ring-primary focus:border-primary outline-none text-body-md">
                <option value="">— Pilih Kelas —</option>
                <?php foreach ($kelasList as $k): ?>
                <option value="<?= $k['id_kelas'] ?>" <?= $selectedKelas == $k['id_kelas'] ? 'selected' : '' ?>><?= htmlspecialchars($k['nama_kelas']) ?></option>
                <?php endforeach; ?>
            </select>
        </div>
        <div class="space-y-1">
            <label class="font-label-sm text-on-surface-variant">Tanggal</label>
            <input type="date" name="tanggal" value="<?= htmlspecialchars($selectedDate, ENT_QUOTES, 'UTF-8') ?>" onchange="this.form.submit()" class="w-full px-4 py-3 bg-surface-container-lowest border border-outline-variant rounded focus:ring-2 focus:ring-primary focus:border-primary outline-none text-body-md">
        </div>
        <div class="flex items-end">
            <a href="absensi.php" class="px-4 py-3 border border-outline-variant rounded font-label-sm hover:bg-surface-container-low transition-all flex items-center gap-2">
                <span class="material-symbols-outlined text-[18px]">refresh</span> Reset
            </a>
        </div>
    </form>

    <?php if ($selectedKelas && !empty($siswaList)): ?>
    <form method="POST">
        <?= csrf_field() ?>
        <input type="hidden" name="id_kelas" value="<?= $selectedKelas ?>">
        <input type="hidden" name="tanggal" value="<?= htmlspecialchars($selectedDate, ENT_QUOTES, 'UTF-8') ?>">
        <section class="bg-surface-container-lowest rounded-lg soft-shadow border border-outline-variant overflow-hidden">
            <div class="p-6 border-b border-outline-variant flex items-center justify-between">
                <h3 class="font-title-sm flex items-center gap-2">
                    <span class="material-symbols-outlined text-primary">people</span>
                    <?= count($siswaList) ?> Siswa
                </h3>
                <button type="submit" name="simpan_absensi" class="px-6 py-2 bg-primary text-on-primary rounded font-label-sm hover:opacity-90 active:scale-95 transition-all flex items-center gap-2">
                    <span class="material-symbols-outlined text-[18px]">save</span> Simpan Absensi
                </button>
            </div>
            <div class="overflow-x-auto">
                <table class="w-full text-left border-collapse">
                    <thead class="bg-surface-container-low">
                        <tr>
                            <th class="px-6 py-4 font-label-sm text-on-surface-variant uppercase tracking-wider">#</th>
                            <th class="px-6 py-4 font-label-sm text-on-surface-variant uppercase tracking-wider">Nama Siswa</th>
                            <th class="px-6 py-4 font-label-sm text-on-surface-variant uppercase tracking-wider text-center">Hadir</th>
                            <th class="px-6 py-4 font-label-sm text-on-surface-variant uppercase tracking-wider text-center">Sakit</th>
                            <th class="px-6 py-4 font-label-sm text-on-surface-variant uppercase tracking-wider text-center">Izin</th>
                            <th class="px-6 py-4 font-label-sm text-on-surface-variant uppercase tracking-wider text-center">Alfa</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-outline-variant">
                        <?php $no = 1; ?>
                        <?php foreach ($siswaList as $s): ?>
                        <?php $currentStatus = $existingDetails[$s['id']] ?? ''; ?>
                        <tr class="hover:bg-surface-container-low transition-colors">
                            <td class="px-6 py-4 font-body-md text-on-surface-variant"><?= $no++ ?></td>
                            <td class="px-6 py-4 font-title-sm text-on-surface"><?= htmlspecialchars($s['nama']) ?></td>
                            <?php foreach (['hadir','sakit','izin','alfa'] as $st): ?>
                            <td class="px-6 py-4 text-center">
                                <label class="inline-flex items-center justify-center cursor-pointer group">
                                    <input type="radio" name="absen[<?= $s['id'] ?>]" value="<?= $st ?>" <?= $currentStatus === $st ? 'checked' : '' ?> required class="peer hidden">
                                    <span class="w-10 h-10 flex items-center justify-center rounded-full border-2 border-outline-variant peer-checked:border-transparent transition-all
                                        <?= $st === 'hadir' ? 'peer-checked:bg-success peer-checked:text-white' : '' ?>
                                        <?= $st === 'sakit' ? 'peer-checked:bg-yellow-500 peer-checked:text-white' : '' ?>
                                        <?= $st === 'izin' ? 'peer-checked:bg-blue-500 peer-checked:text-white' : '' ?>
                                        <?= $st === 'alfa' ? 'peer-checked:bg-error peer-checked:text-white' : '' ?>
                                        group-hover:scale-110 transition-transform">
                                        <span class="material-symbols-outlined text-lg">
                                            <?= $st === 'hadir' ? 'check' : ($st === 'sakit' ? 'sick' : ($st === 'izin' ? 'event_busy' : 'block')) ?>
                                        </span>
                                    </span>
                                </label>
                            </td>
                            <?php endforeach; ?>
                        </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            </div>
            <div class="p-6 border-t border-outline-variant flex gap-6 text-sm">
                <div class="flex items-center gap-2"><span class="w-3 h-3 rounded-full bg-success"></span><span class="text-on-surface-variant">Hadir</span></div>
                <div class="flex items-center gap-2"><span class="w-3 h-3 rounded-full bg-yellow-500"></span><span class="text-on-surface-variant">Sakit</span></div>
                <div class="flex items-center gap-2"><span class="w-3 h-3 rounded-full bg-blue-500"></span><span class="text-on-surface-variant">Izin</span></div>
                <div class="flex items-center gap-2"><span class="w-3 h-3 rounded-full bg-error"></span><span class="text-on-surface-variant">Alfa</span></div>
            </div>
        </section>
    </form>
    <?php elseif ($selectedKelas): ?>
    <div class="flex flex-col items-center justify-center py-20 px-4 text-center">
        <div class="w-20 h-20 mb-4 rounded-full bg-surface-container-high flex items-center justify-center">
            <span class="material-symbols-outlined text-outline text-4xl">group_off</span></div>
        <h3 class="font-headline-md text-on-surface">Tidak ada siswa</h3>
        <p class="font-body-md text-on-surface-variant mt-1">Belum ada siswa terdaftar di kelas ini.</p>
    </div>
    <?php endif; ?>
</div>
<?php require_once __DIR__ . '/../../includes/footer.php'; ?>
