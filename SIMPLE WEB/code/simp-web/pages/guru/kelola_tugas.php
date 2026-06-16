<?php
$title = 'Kelola Tugas';
$currentPage = 'tugas';
require_once __DIR__ . '/../../includes/header.php';
require_once __DIR__ . '/../../config/database.php';
require_once __DIR__ . '/../../config/app.php';

$idGuru = $_SESSION['user_id'];
$message = ''; $messageType = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (!verify_csrf($_POST['_csrf_token'] ?? '')) {
        $message = 'Token CSRF tidak valid. Silakan refresh halaman.';
        $messageType = 'error';
    } else {
    $action = $_POST['action'] ?? '';

    if ($action === 'tambah' || $action === 'edit') {
        $judul    = trim($_POST['judul'] ?? '');
        $deskripsi = trim($_POST['deskripsi'] ?? '');
        $idKelas  = $_POST['id_kelas'] ?? 0;
        $deadline = str_replace('T', ' ', $_POST['deadline'] ?? '');

        if ($judul === '' || !$idKelas || $deadline === '') {
            $message = 'Judul, kelas, dan deadline harus diisi.';
            $messageType = 'error';
        } else {
            // Handle file upload
            $fileName = null;
            $fileError = null;
            $hasFile = isset($_FILES['file']) && $_FILES['file']['error'] !== UPLOAD_ERR_NO_FILE;

            if ($hasFile) {
                if ($_FILES['file']['error'] !== UPLOAD_ERR_OK) {
                    $fileError = 'File gagal diupload.';
                } else {
                    $file = $_FILES['file'];
                    $ext = strtolower(pathinfo($file['name'], PATHINFO_EXTENSION));
                    if (!in_array($ext, ALLOWED_FILE_TYPES_TUGAS)) {
                        $fileError = 'Tipe file tidak diizinkan.';
                    } elseif ($file['size'] > MAX_FILE_SIZE) {
                        $fileError = 'Ukuran file maksimal 50MB.';
                    } elseif (!is_dir(UPLOAD_PATH_TUGAS) || !is_writable(UPLOAD_PATH_TUGAS)) {
                        $fileError = 'Direktori upload tidak dapat ditulisi. Hubungi administrator.';
                    } else {
                        $fileName = uniqid('tugas_') . '.' . $ext;
                        if (!move_uploaded_file($file['tmp_name'], UPLOAD_PATH_TUGAS . $fileName)) {
                            $fileError = 'Gagal menyimpan file.';
                            $fileName = null;
                        }
                    }
                }
            }

            if ($fileError) {
                $message = $fileError;
                $messageType = 'error';
            } else {
                if ($action === 'tambah') {
                    $stmt = $pdo->prepare("INSERT INTO tugas (id_guru, id_kelas, judul, deskripsi, file, deadline) VALUES (?, ?, ?, ?, ?, ?)");
                    $stmt->execute([$idGuru, $idKelas, $judul, $deskripsi, $fileName, $deadline]);
                    $message = 'Tugas berhasil dibuat.';
                } else {
                    $idTugas = $_POST['id_tugas'] ?? 0;
                    if ($fileName) {
                        $stmt = $pdo->prepare("SELECT file FROM tugas WHERE id_tugas = ? AND id_guru = ?");
                        $stmt->execute([$idTugas, $idGuru]);
                        $oldFile = $stmt->fetchColumn();
                        if ($oldFile) {
                            @unlink(UPLOAD_PATH_TUGAS . $oldFile);
                        }
                        $stmt = $pdo->prepare("UPDATE tugas SET judul=?, deskripsi=?, id_kelas=?, file=?, deadline=? WHERE id_tugas=? AND id_guru=?");
                        $stmt->execute([$judul, $deskripsi, $idKelas, $fileName, $deadline, $idTugas, $idGuru]);
                    } else {
                        $stmt = $pdo->prepare("UPDATE tugas SET judul=?, deskripsi=?, id_kelas=?, deadline=? WHERE id_tugas=? AND id_guru=?");
                        $stmt->execute([$judul, $deskripsi, $idKelas, $deadline, $idTugas, $idGuru]);
                    }
                    $message = 'Tugas berhasil diperbarui.';
                }
                $messageType = 'success';
            }
        }
    } elseif ($action === 'hapus') {
        $idTugas = $_POST['id_tugas'] ?? 0;
        $pdo->prepare("DELETE FROM tugas WHERE id_tugas=? AND id_guru=?")->execute([$idTugas, $idGuru]);
        $message = 'Tugas berhasil dihapus.';
        $messageType = 'success';
    }
    }
}

$kelasList = $pdo->query("SELECT * FROM kelas ORDER BY nama_kelas")->fetchAll();
$tugasList = $pdo->prepare("
    SELECT t.*, k.nama_kelas,
        (SELECT COUNT(*) FROM pengumpulan_tugas pt WHERE pt.id_tugas = t.id_tugas AND pt.status != 'belum') as sudah_kumpul,
        (SELECT COUNT(*) FROM kelas_siswa ks WHERE ks.id_kelas = t.id_kelas) as total_siswa
    FROM tugas t
    JOIN kelas k ON t.id_kelas = k.id_kelas
    WHERE t.id_guru = ? ORDER BY t.created_at DESC
");
$tugasList->execute([$idGuru]);
$tugasList = $tugasList->fetchAll();
?>
<div class="max-w-[1440px] mx-auto space-y-8">
    <?php if ($message): ?>
        <div class="px-4 py-3 rounded-lg flex items-center gap-2 border <?= $messageType === 'error' ? 'bg-error-container border-error text-on-error-container' : 'bg-green-50 border-green-300 text-green-800' ?>">
            <span class="material-symbols-outlined text-lg"><?= $messageType === 'error' ? 'error' : 'check_circle' ?></span>
            <p class="font-body-md"><?= htmlspecialchars($message) ?></p>
        </div>
    <?php endif; ?>

    <div class="flex flex-col md:flex-row md:items-end justify-between gap-4">
        <div>
            <h2 class="font-display-lg text-display-lg text-on-surface">Manajemen Tugas</h2>
            <p class="text-on-surface-variant">Pantau perkembangan tugas siswa secara terorganisir.</p>
        </div>
        <button onclick="openModalTambah()" class="flex items-center justify-center gap-2 px-6 py-3 bg-primary text-on-primary rounded font-title-sm active:scale-95 transition-all">
            <span class="material-symbols-outlined">add</span> Buat Tugas Baru
        </button>
    </div>

    <!-- Stats -->
    <div class="grid grid-cols-1 md:grid-cols-4 gap-gutter">
        <div class="bg-surface-container-lowest p-6 rounded-lg soft-shadow border border-outline-variant flex items-center gap-4">
            <div class="w-12 h-12 rounded-full bg-primary/10 flex items-center justify-center text-primary">
                <span class="material-symbols-outlined">task</span></div>
            <div><p class="font-label-sm text-on-surface-variant">Total Tugas</p><p class="text-2xl font-bold"><?= count($tugasList) ?></p></div>
        </div>
        <div class="bg-surface-container-lowest p-6 rounded-lg soft-shadow border border-outline-variant flex items-center gap-4">
            <div class="w-12 h-12 rounded-full bg-error/10 flex items-center justify-center text-error">
                <span class="material-symbols-outlined">pending_actions</span></div>
            <div><p class="font-label-sm text-on-surface-variant">Belum Dinilai</p>
                <p class="text-2xl font-bold"><?php $stmt = $pdo->prepare("SELECT COUNT(*) FROM pengumpulan_tugas pt JOIN tugas t ON pt.id_tugas=t.id_tugas WHERE t.id_guru=? AND pt.status='dikumpulkan'"); $stmt->execute([$idGuru]); echo $stmt->fetchColumn(); ?></p>
            </div>
        </div>
        <div class="bg-surface-container-lowest p-6 rounded-lg soft-shadow border border-outline-variant flex items-center gap-4">
            <div class="w-12 h-12 rounded-full bg-tertiary-container/10 flex items-center justify-center text-tertiary">
                <span class="material-symbols-outlined">group</span></div>
            <div><p class="font-label-sm text-on-surface-variant">Total Siswa</p><p class="text-2xl font-bold"><?= $pdo->query("SELECT COUNT(*) FROM users WHERE role='siswa'")->fetchColumn() ?></p></div>
        </div>
        <div class="bg-primary p-6 rounded-lg soft-shadow flex items-center gap-4 text-on-primary">
            <div class="w-12 h-12 rounded-full bg-white/20 flex items-center justify-center">
                <span class="material-symbols-outlined">trending_up</span></div>
            <div><p class="font-label-sm opacity-90">Rata-rata Nilai</p><p class="text-2xl font-bold"><?= number_format($pdo->query("SELECT AVG(nilai) FROM pengumpulan_tugas WHERE nilai IS NOT NULL")->fetchColumn() ?: 0, 1) ?></p></div>
        </div>
    </div>

    <!-- Task List -->
    <div class="space-y-4">
        <?php if (empty($tugasList)): ?>
        <div class="flex flex-col items-center justify-center py-20 px-4 text-center">
            <div class="w-24 h-24 mb-4 rounded-full bg-surface-container-high flex items-center justify-center">
                <span class="material-symbols-outlined text-outline text-5xl">assignment</span></div>
            <h3 class="font-headline-md text-on-surface">Belum ada tugas</h3>
            <p class="font-body-md text-on-surface-variant mt-2">Buat tugas pertama Anda.</p>
        </div>
        <?php else: ?>
            <?php foreach ($tugasList as $t): ?>
            <?php
                $progress = $t['total_siswa'] > 0 ? round($t['sudah_kumpul'] / $t['total_siswa'] * 100) : 0;
                $deadlinePassed = strtotime($t['deadline']) < time();
            ?>
            <div class="bg-surface-container-lowest p-5 rounded-lg soft-shadow border border-outline-variant transition-all group">
                <div class="flex flex-col md:flex-row md:items-center justify-between gap-4">
                    <div class="flex items-start gap-4">
                        <div class="w-12 h-12 rounded-md bg-surface-container-high flex items-center justify-center group-hover:bg-primary-container group-hover:text-on-primary-container transition-colors">
                            <span class="material-symbols-outlined">description</span>
                        </div>
                        <div>
                            <h4 class="font-title-sm text-on-surface"><?= htmlspecialchars($t['judul']) ?></h4>
                            <p class="text-body-md text-on-surface-variant"><?= htmlspecialchars($t['nama_kelas']) ?></p>
                            <div class="flex items-center gap-3 mt-2">
                                <div class="flex items-center gap-1 font-label-sm text-on-surface-variant">
                                    <span class="material-symbols-outlined text-[16px]">event</span>
                                    Deadline: <?= date('d M Y H:i', strtotime($t['deadline'])) ?>
                                </div>
                                <?php if ($t['file']): ?>
                                <span class="w-1 h-1 rounded-full bg-outline-variant"></span>
                                <a href="../../assets/uploads/tugas/<?= urlencode($t['file']) ?>" target="_blank" class="flex items-center gap-1 font-label-sm text-primary hover:underline">
                                    <span class="material-symbols-outlined text-[16px]">attach_file</span>
                                    Lampiran
                                </a>
                                <?php endif; ?>
                                <span class="w-1 h-1 rounded-full bg-outline-variant"></span>
                                <div class="flex items-center gap-1 font-label-sm <?= $deadlinePassed ? 'text-error' : 'text-primary' ?>">
                                    <span class="material-symbols-outlined text-[16px]"><?= $deadlinePassed ? 'warning' : 'check_circle' ?></span>
                                    <?= $t['sudah_kumpul'] ?>/<?= $t['total_siswa'] ?> Dikumpulkan
                                </div>
                            </div>
                        </div>
                    </div>
                    <div class="flex items-center gap-4">
                        <div class="text-right hidden sm:block">
                            <div class="w-32 h-2 bg-surface-container-high rounded-full overflow-hidden">
                                <div class="h-full <?= $deadlinePassed ? 'bg-error' : 'bg-primary' ?> rounded-full" style="width: <?= $progress ?>%"></div>
                            </div>
                            <p class="font-label-sm text-on-surface-variant mt-1"><?= $progress ?>%</p>
                        </div>
                        <button onclick="openModalEdit(<?= $t['id_tugas'] ?>)" class="p-2 hover:bg-surface-container-high rounded-full" title="Edit">
                            <span class="material-symbols-outlined text-[20px]">edit</span>
                        </button>
                        <form method="POST" class="inline" onsubmit="return confirm('Hapus tugas ini?')">
                            <?= csrf_field() ?>
                            <input type="hidden" name="action" value="hapus">
                            <input type="hidden" name="id_tugas" value="<?= $t['id_tugas'] ?>">
                            <button class="p-2 hover:bg-error-container/20 rounded-full text-error" title="Hapus">
                                <span class="material-symbols-outlined text-[20px]">delete</span>
                            </button>
                        </form>
                    </div>
                </div>
            </div>
            <?php endforeach; ?>
        <?php endif; ?>
    </div>
</div>

<!-- Modal Tambah -->
<div id="modalTambah" class="fixed inset-0 z-50 hidden bg-black/30 flex items-center justify-center p-4" onclick="if(event.target===this)closeModal('modalTambah')">
    <div class="bg-surface-container-lowest rounded-lg soft-shadow max-w-lg w-full p-6">
        <div class="flex justify-between items-center mb-6">
            <h3 class="font-headline-md" id="modalTitle">Buat Tugas Baru</h3>
            <button onclick="closeModal('modalTambah')" class="p-2 hover:bg-surface-container-high rounded-full"><span class="material-symbols-outlined">close</span></button>
        </div>
        <form method="POST" enctype="multipart/form-data">
            <?= csrf_field() ?>
            <input type="hidden" name="action" id="formAction" value="tambah">
            <input type="hidden" name="id_tugas" id="formIdTugas">
            <div class="space-y-4">
                <div>
                    <label class="block font-label-sm text-on-surface-variant mb-1">Judul Tugas</label>
                    <input name="judul" id="formJudul" required class="w-full px-4 py-3 bg-surface border border-outline-variant rounded focus:ring-2 focus:ring-primary focus:border-primary outline-none text-body-md">
                </div>
                <div>
                    <label class="block font-label-sm text-on-surface-variant mb-1">Kelas</label>
                    <select name="id_kelas" id="formKelas" required class="w-full px-4 py-3 bg-surface border border-outline-variant rounded focus:ring-2 focus:ring-primary focus:border-primary outline-none text-body-md">
                        <option value="">— Pilih Kelas —</option>
                        <?php foreach ($kelasList as $k): ?>
                        <option value="<?= $k['id_kelas'] ?>"><?= htmlspecialchars($k['nama_kelas']) ?></option>
                        <?php endforeach; ?>
                    </select>
                </div>
                <div>
                    <label class="block font-label-sm text-on-surface-variant mb-1">Deskripsi</label>
                    <textarea name="deskripsi" id="formDeskripsi" class="w-full px-4 py-3 bg-surface border border-outline-variant rounded focus:ring-2 focus:ring-primary focus:border-primary outline-none text-body-md" rows="3"></textarea>
                </div>
                <div>
                    <label class="block font-label-sm text-on-surface-variant mb-1">File Tugas <span class="text-on-surface-variant opacity-70">(opsional)</span></label>
                    <input type="file" name="file" id="formFile" accept=".pdf,.doc,.docx,.zip,.rar,.jpg,.jpeg,.png" class="w-full px-4 py-3 bg-surface border border-outline-variant rounded text-body-md file:mr-4 file:py-2 file:px-4 file:rounded file:border-0 file:bg-primary-container file:text-on-primary-container file:font-label-sm hover:file:opacity-90">
                    <p class="font-label-sm text-on-surface-variant mt-1">PDF, DOC, DOCX, ZIP, RAR, JPG, PNG (Maks. 50MB)</p>
                    <div id="currentFileInfo" class="hidden mt-2 p-3 bg-primary-container/10 rounded flex items-center gap-2">
                        <span class="material-symbols-outlined text-[18px] text-primary">attach_file</span>
                        <span class="font-label-sm text-on-surface-variant flex-1" id="currentFileName"></span>
                        <a id="currentFileLink" href="#" target="_blank" class="text-primary font-label-sm hover:underline">Unduh</a>
                    </div>
                </div>
                <div>
                    <label class="block font-label-sm text-on-surface-variant mb-1">Deadline</label>
                    <input type="datetime-local" name="deadline" id="formDeadline" required class="w-full px-4 py-3 bg-surface border border-outline-variant rounded focus:ring-2 focus:ring-primary focus:border-primary outline-none text-body-md">
                </div>
            </div>
            <div class="mt-6 flex justify-end gap-3">
                <button type="button" onclick="closeModal('modalTambah')" class="px-4 py-2 border border-outline-variant rounded font-label-sm hover:bg-surface-container-low">Batal</button>
                <button type="submit" class="px-6 py-2 bg-primary text-on-primary rounded font-label-sm">Simpan</button>
            </div>
        </form>
    </div>
</div>

<script>
const tugasData = <?= json_encode($tugasList) ?>;

function closeModal(id) { document.getElementById(id).classList.add('hidden'); }
function openModalTambah() {
    document.getElementById('formAction').value = 'tambah';
    document.getElementById('modalTitle').textContent = 'Buat Tugas Baru';
    document.getElementById('formIdTugas').value = '';
    document.getElementById('formJudul').value = '';
    document.getElementById('formKelas').value = '';
    document.getElementById('formDeskripsi').value = '';
    document.getElementById('formDeadline').value = '';
    document.getElementById('formFile').value = '';
    document.getElementById('currentFileInfo').classList.add('hidden');
    document.getElementById('modalTambah').classList.remove('hidden');
}
function openModalEdit(id) {
    const t = tugasData.find(x => x.id_tugas == id);
    if (!t) return;
    document.getElementById('formAction').value = 'edit';
    document.getElementById('modalTitle').textContent = 'Edit Tugas';
    document.getElementById('formIdTugas').value = t.id_tugas;
    document.getElementById('formJudul').value = t.judul;
    document.getElementById('formKelas').value = t.id_kelas;
    document.getElementById('formDeskripsi').value = t.deskripsi || '';
    document.getElementById('formDeadline').value = t.deadline.replace(' ', 'T');
    document.getElementById('formFile').value = '';
    const fileInfo = document.getElementById('currentFileInfo');
    const fileName = document.getElementById('currentFileName');
    const fileLink = document.getElementById('currentFileLink');
    if (t.file) {
        fileName.textContent = t.file;
        fileLink.href = '../../assets/uploads/tugas/' + encodeURIComponent(t.file);
        fileInfo.classList.remove('hidden');
    } else {
        fileInfo.classList.add('hidden');
    }
    document.getElementById('modalTambah').classList.remove('hidden');
}
</script>
<?php require_once __DIR__ . '/../../includes/footer.php'; ?>
