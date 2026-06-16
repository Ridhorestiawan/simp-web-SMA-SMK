<?php
$title = 'Lihat Materi';
$currentPage = 'materi';
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

$materiList = [];
$grouped = [];
if ($kelasSiswa) {
    $ml = $pdo->prepare("
        SELECT m.*, u.nama as nama_guru FROM materi m
        JOIN users u ON m.id_guru = u.id
        WHERE m.id_kelas = ?
        ORDER BY m.tanggal_upload DESC
    ");
    $ml->execute([$kelasSiswa['id_kelas']]);
    $materiList = $ml->fetchAll();

    // Group by month/year
    foreach ($materiList as $m) {
        $key = date('F Y', strtotime($m['tanggal_upload']));
        $grouped[$key][] = $m;
    }
}
?>
<div class="max-w-[1440px] mx-auto space-y-8">
    <div>
        <h2 class="font-display-lg text-display-lg text-on-surface">Materi Pembelajaran</h2>
        <p class="text-on-surface-variant">
            <?php if ($kelasSiswa): ?>
                Kelas <?= htmlspecialchars($kelasSiswa['nama_kelas']) ?> — <?= count($materiList) ?> materi tersedia.
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
    <?php elseif (empty($materiList)): ?>
    <div class="flex flex-col items-center justify-center py-20 px-4 text-center">
        <div class="w-20 h-20 mb-4 rounded-full bg-surface-container-high flex items-center justify-center">
            <span class="material-symbols-outlined text-outline text-4xl">book</span></div>
        <h3 class="font-headline-md text-on-surface">Belum ada materi</h3>
        <p class="font-body-md text-on-surface-variant mt-1">Guru belum mengupload materi untuk kelas ini.</p>
    </div>
    <?php else: ?>
        <?php foreach ($grouped as $bulan => $items): ?>
        <section class="bg-surface-container-lowest rounded-lg soft-shadow border border-outline-variant overflow-hidden">
            <div class="px-6 py-4 bg-surface-container-low border-b border-outline-variant">
                <h3 class="font-title-sm flex items-center gap-2">
                    <span class="material-symbols-outlined text-primary">calendar_month</span>
                    <?= $bulan ?>
                </h3>
            </div>
            <div class="divide-y divide-outline-variant">
                <?php foreach ($items as $m): ?>
                <div class="p-5 hover:bg-surface-container-low transition-colors">
                    <div class="flex items-start justify-between gap-4">
                        <div class="flex items-start gap-4 flex-grow">
                            <div class="w-14 h-14 rounded-md bg-primary-container/20 flex items-center justify-center shrink-0">
                                <span class="material-symbols-outlined text-primary text-2xl">
                                    <?php
                                    $ext = pathinfo($m['file'], PATHINFO_EXTENSION);
                                    echo match($ext) {
                                        'pdf' => 'picture_as_pdf',
                                        'ppt', 'pptx' => 'present_to_all',
                                        'mp4' => 'movie',
                                        'doc', 'docx' => 'description',
                                        'xls', 'xlsx' => 'table_chart',
                                        default => 'insert_drive_file'
                                    };
                                    ?>
                                </span>
                            </div>
                            <div>
                                <h4 class="font-title-sm text-on-surface"><?= htmlspecialchars($m['judul']) ?></h4>
                                <?php if ($m['deskripsi']): ?>
                                <p class="font-body-md text-on-surface-variant mt-1 line-clamp-2"><?= htmlspecialchars($m['deskripsi']) ?></p>
                                <?php endif; ?>
                                <div class="flex items-center gap-3 mt-2">
                                    <span class="font-label-sm text-on-surface-variant flex items-center gap-1">
                                        <span class="material-symbols-outlined text-[14px]">person</span>
                                        <?= htmlspecialchars($m['nama_guru']) ?>
                                    </span>
                                    <span class="w-1 h-1 rounded-full bg-outline-variant"></span>
                                    <span class="font-label-sm text-on-surface-variant flex items-center gap-1">
                                        <span class="material-symbols-outlined text-[14px]">calendar_today</span>
                                        <?= date('d M Y', strtotime($m['tanggal_upload'])) ?>
                                    </span>
                                    <span class="w-1 h-1 rounded-full bg-outline-variant"></span>
                                    <span class="font-label-sm text-on-surface-variant uppercase"><?= strtoupper($ext) ?></span>
                                </div>
                            </div>
                        </div>
                        <a href="../../assets/uploads/materi/<?= urlencode($m['file']) ?>" download
                           class="px-5 py-2 bg-primary text-on-primary rounded font-label-sm hover:opacity-90 active:scale-95 transition-all flex items-center gap-2 shrink-0">
                            <span class="material-symbols-outlined text-[18px]">download</span>
                            <span class="hidden sm:inline">Unduh</span>
                        </a>
                    </div>
                </div>
                <?php endforeach; ?>
            </div>
        </section>
        <?php endforeach; ?>
    <?php endif; ?>
</div>
<?php require_once __DIR__ . '/../../includes/footer.php'; ?>
