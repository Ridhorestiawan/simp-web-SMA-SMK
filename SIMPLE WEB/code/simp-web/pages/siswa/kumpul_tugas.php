<?php
$title = 'Kumpul Tugas';
$currentPage = 'tugas';
require_once __DIR__ . '/../../includes/header.php';
require_once __DIR__ . '/../../config/database.php';
require_once __DIR__ . '/../../config/app.php';

$idSiswa = $_SESSION['user_id'];
$message = ''; $messageType = '';

// Get student's class
$kelasSiswa = $pdo->prepare("
    SELECT k.id_kelas, k.nama_kelas FROM kelas_siswa ks
    JOIN kelas k ON ks.id_kelas = k.id_kelas
    WHERE ks.id_siswa = ?
");
$kelasSiswa->execute([$idSiswa]);
$kelasSiswa = $kelasSiswa->fetch();

// Handle upload
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['kumpul'])) {
    if (!verify_csrf($_POST['_csrf_token'] ?? '')) {
        $message = 'Token CSRF tidak valid. Silakan refresh halaman.';
        $messageType = 'error';
    } else {
    $idTugas = $_POST['id_tugas'] ?? 0;
    $file = $_FILES['file'] ?? null;

    // Verify tugas exists and belongs to student's class
    $cek = $pdo->prepare("SELECT * FROM tugas WHERE id_tugas = ? AND id_kelas = ? AND deadline > NOW()");
    $cek->execute([$idTugas, $kelasSiswa['id_kelas'] ?? 0]);
    $tugas = $cek->fetch();

    if (!$tugas) {
        $message = 'Tugas tidak valid atau sudah melewati deadline.';
        $messageType = 'error';
    } elseif (!$file || $file['error'] !== UPLOAD_ERR_OK) {
        $message = 'Pilih file untuk dikumpulkan.';
        $messageType = 'error';
    } else {
        $ext = strtolower(pathinfo($file['name'], PATHINFO_EXTENSION));
        if (!in_array($ext, ALLOWED_FILE_TYPES_TUGAS)) {
            $message = 'Tipe file tidak diizinkan.';
            $messageType = 'error';
            } elseif ($file['size'] > MAX_FILE_SIZE) {
                $message = 'Ukuran file maksimal 50MB.';
                $messageType = 'error';
            } elseif (!is_dir(UPLOAD_PATH_TUGAS) || !is_writable(UPLOAD_PATH_TUGAS)) {
                $message = 'Direktori upload tidak dapat ditulisi. Hubungi administrator.';
                $messageType = 'error';
            } else {
                $fileName = uniqid('tugas_') . '.' . $ext;
            $dest = UPLOAD_PATH_TUGAS . $fileName;
            if (move_uploaded_file($file['tmp_name'], $dest)) {
                // Check if already submitted
                $existing = $pdo->prepare("SELECT id_pengumpulan, file FROM pengumpulan_tugas WHERE id_tugas = ? AND id_siswa = ?");
                $existing->execute([$idTugas, $idSiswa]);
                $ex = $existing->fetch();

                if ($ex) {
                    // Replace
                    @unlink(UPLOAD_PATH_TUGAS . $ex['file']);
                    $pdo->prepare("UPDATE pengumpulan_tugas SET file = ?, status = 'dikumpulkan', waktu_kumpul = NOW() WHERE id_pengumpulan = ?")
                        ->execute([$fileName, $ex['id_pengumpulan']]);
                    $message = 'Tugas berhasil diperbarui.';
                } else {
                    $pdo->prepare("INSERT INTO pengumpulan_tugas (id_tugas, id_siswa, file, status) VALUES (?, ?, ?, 'dikumpulkan')")
                        ->execute([$idTugas, $idSiswa, $fileName]);
                    $message = 'Tugas berhasil dikumpulkan.';
                }
                $messageType = 'success';
            } else {
                $message = 'Gagal menyimpan file.';
                $messageType = 'error';
            }
        }
    }
    }
}

// Get tasks for student's class
$tugasList = [];
if ($kelasSiswa) {
    $tl = $pdo->prepare("
        SELECT t.*, u.nama as nama_guru,
            (SELECT id_pengumpulan FROM pengumpulan_tugas WHERE id_tugas = t.id_tugas AND id_siswa = ?) as id_pengumpulan,
            (SELECT status FROM pengumpulan_tugas WHERE id_tugas = t.id_tugas AND id_siswa = ?) as status_saya,
            (SELECT file FROM pengumpulan_tugas WHERE id_tugas = t.id_tugas AND id_siswa = ?) as file_saya,
            (SELECT nilai FROM pengumpulan_tugas WHERE id_tugas = t.id_tugas AND id_siswa = ?) as nilai_saya,
            (SELECT catatan_guru FROM pengumpulan_tugas WHERE id_tugas = t.id_tugas AND id_siswa = ?) as catatan_saya
        FROM tugas t
        JOIN users u ON t.id_guru = u.id
        WHERE t.id_kelas = ?
        ORDER BY t.deadline DESC
    ");
    $tl->execute([$idSiswa, $idSiswa, $idSiswa, $idSiswa, $idSiswa, $kelasSiswa['id_kelas']]);
    $tugasList = $tl->fetchAll();
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
        <h2 class="font-display-lg text-display-lg text-on-surface">Kumpul Tugas</h2>
        <p class="text-on-surface-variant">
            <?php if ($kelasSiswa): ?>
                Kelas <?= htmlspecialchars($kelasSiswa['nama_kelas']) ?> — Kumpulkan tugas sebelum deadline.
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
    <?php elseif (empty($tugasList)): ?>
    <div class="flex flex-col items-center justify-center py-20 px-4 text-center">
        <div class="w-20 h-20 mb-4 rounded-full bg-surface-container-high flex items-center justify-center">
            <span class="material-symbols-outlined text-outline text-4xl">assignment</span></div>
        <h3 class="font-headline-md text-on-surface">Belum ada tugas</h3>
        <p class="font-body-md text-on-surface-variant mt-1">Guru belum memberikan tugas untuk kelas ini.</p>
    </div>
    <?php else: ?>
        <?php foreach ($tugasList as $t):
            $deadlinePassed = strtotime($t['deadline']) < time();
            $isSubmitted = $t['status_saya'] && $t['status_saya'] !== 'belum';
            $isGraded = $t['status_saya'] === 'dinilai';
        ?>
        <section class="bg-surface-container-lowest rounded-lg soft-shadow border border-outline-variant overflow-hidden">
            <div class="p-6 border-b border-outline-variant">
                <div class="flex flex-col md:flex-row md:items-center justify-between gap-4">
                    <div class="flex items-start gap-4">
                        <div class="w-14 h-14 rounded-md bg-primary-container/20 flex items-center justify-center shrink-0">
                            <span class="material-symbols-outlined text-primary text-2xl">description</span>
                        </div>
                        <div>
                            <h3 class="font-title-sm text-on-surface"><?= htmlspecialchars($t['judul']) ?></h3>
                            <p class="font-label-sm text-on-surface-variant"><?= htmlspecialchars($t['nama_guru']) ?></p>
                            <p class="font-body-md text-on-surface-variant mt-1"><?= nl2br(htmlspecialchars($t['deskripsi'] ?? '')) ?></p>
                            <?php if ($t['file']): ?>
                            <div class="mt-2">
                                <a href="../../assets/uploads/tugas/<?= urlencode($t['file']) ?>" target="_blank" class="inline-flex items-center gap-1 font-label-sm text-primary hover:underline">
                                    <span class="material-symbols-outlined text-[16px]">attach_file</span>
                                    Lampiran Tugas
                                </a>
                            </div>
                            <?php endif; ?>
                            <div class="flex items-center gap-3 mt-2">
                                <span class="font-label-sm <?= $deadlinePassed ? 'text-error' : 'text-on-surface-variant' ?> flex items-center gap-1">
                                    <span class="material-symbols-outlined text-[16px]">event</span>
                                    Deadline: <?= date('d M Y H:i', strtotime($t['deadline'])) ?>
                                </span>
                                <span class="w-1 h-1 rounded-full bg-outline-variant"></span>
                                <span class="font-label-sm <?= $isSubmitted ? 'text-success' : ($deadlinePassed ? 'text-error' : 'text-warning') ?>">
                                    <?= $isGraded ? 'Sudah Dinilai' : ($isSubmitted ? 'Sudah Dikumpulkan' : ($deadlinePassed ? 'Terlewat' : 'Belum Dikumpulkan')) ?>
                                </span>
                            </div>
                        </div>
                    </div>
                    <div class="flex items-center gap-3 shrink-0">
                        <?php if ($isGraded && $t['nilai_saya'] !== null): ?>
                        <div class="text-center px-4 py-2 bg-success-container/20 rounded-lg">
                            <p class="font-label-sm text-success">Nilai</p>
                            <p class="text-2xl font-bold text-success"><?= $t['nilai_saya'] ?></p>
                        </div>
                        <?php endif; ?>
                        <?php if ($isSubmitted && !$deadlinePassed): ?>
                        <button class="px-5 py-2 border border-outline-variant rounded font-label-sm hover:bg-surface-container-low transition-all" onclick="document.getElementById('form_<?= $t['id_tugas'] ?>').classList.toggle('hidden')">
                            <span class="material-symbols-outlined text-[18px] align-middle">edit</span> Ganti
                        </button>
                        <?php endif; ?>
                    </div>
                </div>
            </div>

            <?php if (!$deadlinePassed || $isSubmitted): ?>
            <!-- Upload Form -->
            <div id="form_<?= $t['id_tugas'] ?>" class="<?= $isSubmitted ? 'hidden' : '' ?> border-b border-outline-variant">
                <form method="POST" enctype="multipart/form-data" class="p-6">
                    <?= csrf_field() ?>
                    <input type="hidden" name="id_tugas" value="<?= $t['id_tugas'] ?>">
                    <div class="flex flex-col sm:flex-row items-start sm:items-center gap-4">
                        <div class="flex-grow w-full sm:w-auto">
                            <label class="flex items-center gap-3 px-4 py-3 border-2 border-dashed border-outline-variant rounded-lg cursor-pointer hover:border-primary transition-all group">
                                <span class="material-symbols-outlined text-outline group-hover:text-primary">cloud_upload</span>
                                <span class="font-body-md text-on-surface-variant group-hover:text-on-surface transition-colors" id="label_<?= $t['id_tugas'] ?>">
                                    <?= $isSubmitted ? 'Ganti file tugas...' : 'Pilih file tugas...' ?>
                                </span>
                                <input type="file" name="file" class="hidden" onchange="document.getElementById('label_<?= $t['id_tugas'] ?>').textContent = this.files[0]?.name || '<?= $isSubmitted ? 'Ganti file tugas...' : 'Pilih file tugas...' ?>'" required>
                            </label>
                            <p class="font-label-sm text-on-surface-variant mt-1">PDF, DOC, DOCX, ZIP, RAR, JPG, PNG (Maks. 50MB)</p>
                        </div>
                        <button type="submit" name="kumpul" class="px-6 py-3 bg-primary text-on-primary rounded font-label-sm hover:opacity-90 active:scale-95 transition-all flex items-center gap-2 shrink-0">
                            <span class="material-symbols-outlined text-[18px]">upload</span>
                            <?= $isSubmitted ? 'Perbarui' : 'Kumpulkan' ?>
                        </button>
                    </div>
                </form>
            </div>
            <?php if ($isSubmitted && $t['file_saya']): ?>
            <div class="px-6 py-3 bg-primary-container/10 flex items-center gap-2">
                <span class="material-symbols-outlined text-[18px] text-primary">check_circle</span>
                <span class="font-label-sm text-on-surface-variant">File terkumpul: <?= htmlspecialchars($t['file_saya']) ?></span>
                <a href="../../assets/uploads/tugas/<?= urlencode($t['file_saya']) ?>" download class="ml-auto text-primary font-label-sm hover:underline">Unduh</a>
            </div>
            <?php endif; ?>
            <?php else: ?>
            <div class="px-6 py-3 bg-error-container/10 flex items-center gap-2">
                <span class="material-symbols-outlined text-[18px] text-error">block</span>
                <span class="font-label-sm text-error">Deadline sudah lewat. Tidak bisa mengumpulkan tugas.</span>
            </div>
            <?php endif; ?>

            <?php if ($isGraded && $t['catatan_saya']): ?>
            <div class="px-6 py-3 bg-surface-container-low flex items-start gap-2">
                <span class="material-symbols-outlined text-[18px] text-on-surface-variant mt-0.5">rate_review</span>
                <div>
                    <span class="font-label-sm text-on-surface-variant">Catatan Guru:</span>
                    <p class="font-body-md text-on-surface"><?= nl2br(htmlspecialchars($t['catatan_saya'])) ?></p>
                </div>
            </div>
            <?php endif; ?>
        </section>
        <?php endforeach; ?>
    <?php endif; ?>
</div>
<?php require_once __DIR__ . '/../../includes/footer.php'; ?>
