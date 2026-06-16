<?php
$title = 'Upload Materi';
$currentPage = 'materi';
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

    if ($action === 'upload') {
        $judul    = trim($_POST['judul'] ?? '');
        $deskripsi = trim($_POST['deskripsi'] ?? '');
        $idKelas  = $_POST['id_kelas'] ?? 0;

        if ($judul === '' || !$idKelas) {
            $message = 'Judul dan kelas harus diisi.';
            $messageType = 'error';
        } elseif (!isset($_FILES['file']) || $_FILES['file']['error'] !== UPLOAD_ERR_OK) {
            $message = 'File gagal diupload.';
            $messageType = 'error';
        } else {
            $file = $_FILES['file'];
            $ext = strtolower(pathinfo($file['name'], PATHINFO_EXTENSION));
            $allowed = ['pdf', 'ppt', 'pptx', 'mp4', 'doc', 'docx', 'xls', 'xlsx'];
            if (!in_array($ext, $allowed)) {
                $message = 'Tipe file tidak diizinkan.';
                $messageType = 'error';
            } elseif ($file['size'] > 50 * 1024 * 1024) {
                $message = 'Ukuran file maksimal 50MB.';
                $messageType = 'error';
            } elseif (!is_dir(UPLOAD_PATH_MATERI) || !is_writable(UPLOAD_PATH_MATERI)) {
                $message = 'Direktori upload tidak dapat ditulisi. Hubungi administrator.';
                $messageType = 'error';
            } else {
                $fileName = uniqid('materi_') . '.' . $ext;
                $dest = UPLOAD_PATH_MATERI . $fileName;
                if (move_uploaded_file($file['tmp_name'], $dest)) {
                    $stmt = $pdo->prepare("INSERT INTO materi (id_guru, id_kelas, judul, deskripsi, file) VALUES (?, ?, ?, ?, ?)");
                    $stmt->execute([$idGuru, $idKelas, $judul, $deskripsi, $fileName]);
                    $message = 'Materi berhasil diupload.';
                    $messageType = 'success';
                } else {
                    $message = 'Gagal menyimpan file.';
                    $messageType = 'error';
                }
            }
        }
    } elseif ($action === 'hapus') {
        $idMateri = $_POST['id_materi'] ?? 0;
        $stmt = $pdo->prepare("SELECT file FROM materi WHERE id_materi = ? AND id_guru = ?");
        $stmt->execute([$idMateri, $idGuru]);
        $m = $stmt->fetch();
        if ($m) {
            @unlink(UPLOAD_PATH_MATERI . $m['file']);
            $pdo->prepare("DELETE FROM materi WHERE id_materi = ?")->execute([$idMateri]);
            $message = 'Materi berhasil dihapus.';
            $messageType = 'success';
        }
    }
    }
}

$kelasList = $pdo->query("SELECT * FROM kelas ORDER BY nama_kelas")->fetchAll();
$materiList = $pdo->prepare("
    SELECT m.*, k.nama_kelas FROM materi m
    JOIN kelas k ON m.id_kelas = k.id_kelas
    WHERE m.id_guru = ? ORDER BY m.tanggal_upload DESC
");
$materiList->execute([$idGuru]);
$materiList = $materiList->fetchAll();
?>
<div class="max-w-[1440px] mx-auto space-y-gutter">
    <?php if ($message): ?>
        <div class="px-4 py-3 rounded-lg flex items-center gap-2 border <?= $messageType === 'error' ? 'bg-error-container border-error text-on-error-container' : 'bg-green-50 border-green-300 text-green-800' ?>">
            <span class="material-symbols-outlined text-lg"><?= $messageType === 'error' ? 'error' : 'check_circle' ?></span>
            <p class="font-body-md"><?= htmlspecialchars($message) ?></p>
        </div>
    <?php endif; ?>

    <div class="flex flex-col md:flex-row md:items-end justify-between gap-4">
        <div>
            <h2 class="font-display-lg text-display-lg text-on-surface">Materi Pembelajaran</h2>
            <p class="text-on-surface-variant font-body-md">Kelola dan unggah materi ajar untuk siswa.</p>
        </div>
    </div>

    <form method="POST" enctype="multipart/form-data" class="grid grid-cols-1 lg:grid-cols-12 gap-gutter">
            <?= csrf_field() ?>
            <input type="hidden" name="action" value="upload">
        <div class="lg:col-span-7 space-y-gutter">
            <section class="bg-surface-container-lowest p-6 rounded-lg soft-shadow border border-outline-variant">
                <h3 class="font-title-sm mb-6 flex items-center gap-2">
                    <span class="material-symbols-outlined text-primary">description</span> Informasi Materi
                </h3>
                <div class="space-y-6">
                    <div class="space-y-1.5">
                        <label class="block font-label-sm text-on-surface-variant">Judul Materi</label>
                        <input name="judul" required class="w-full px-4 py-3 bg-transparent border border-outline rounded focus:border-primary focus:ring-1 focus:ring-primary outline-none transition-all font-body-md" placeholder="Contoh: Integral Tentu Dasar">
                    </div>
                    <div class="space-y-1.5">
                        <label class="block font-label-sm text-on-surface-variant">Kelas</label>
                        <select name="id_kelas" required class="w-full px-4 py-3 bg-transparent border border-outline rounded focus:border-primary focus:ring-1 focus:ring-primary outline-none transition-all font-body-md appearance-none bg-[url('data:image/svg+xml;charset=US-ASCII,%3Csvg%20xmlns%3D%22http%3A%2F%2Fwww.w3.org%2F2000%2Fsvg%22%20width%3D%2224%22%20height%3D%2224%22%20viewBox%3D%220%200%2024%2024%22%20fill%3D%22none%22%20stroke%3D%22%23727785%22%20stroke-width%3D%222%22%20stroke-linecap%3D%22round%22%20stroke-linejoin%3D%22round%22%3E%3Cpolyline%20points%3D%226%209%2012%2015%2018%209%22%3E%3C%2Fpolyline%3E%3C%2Fsvg%3E')] bg-[length:20px_20px] bg-[right_12px_center] bg-no-repeat">
                            <option value="">Pilih Kelas</option>
                            <?php foreach ($kelasList as $k): ?>
                            <option value="<?= $k['id_kelas'] ?>"><?= htmlspecialchars($k['nama_kelas']) ?></option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                    <div class="space-y-1.5">
                        <label class="block font-label-sm text-on-surface-variant">Deskripsi</label>
                        <textarea name="deskripsi" class="w-full px-4 py-3 bg-transparent border border-outline rounded focus:border-primary focus:ring-1 focus:ring-primary outline-none transition-all font-body-md" rows="4" placeholder="Ringkasan materi..."></textarea>
                    </div>
                </div>
            </section>
        </div>
        <div class="lg:col-span-5 space-y-gutter">
            <section class="bg-surface-container-lowest p-6 rounded-lg soft-shadow border border-outline-variant h-full flex flex-col">
                <h3 class="font-title-sm mb-6 flex items-center gap-2">
                    <span class="material-symbols-outlined text-primary">upload_file</span> Unggah Berkas
                </h3>
                <div class="flex-grow border-2 border-dashed border-outline-variant rounded-lg bg-surface-container-low flex flex-col items-center justify-center p-8 text-center group cursor-pointer hover:border-primary transition-all" onclick="document.getElementById('fileInput').click()">
                    <div class="w-16 h-16 rounded-full bg-primary-fixed flex items-center justify-center text-primary mb-4 group-hover:scale-110 transition-transform">
                        <span class="material-symbols-outlined text-[32px]">cloud_upload</span>
                    </div>
                    <p class="font-title-sm text-on-surface mb-1">Tarik dan lepas file di sini</p>
                    <p class="text-on-surface-variant font-label-sm mb-4">atau klik untuk menelusuri</p>
                    <div class="flex gap-2 text-on-surface-variant opacity-60">
                        <span class="material-symbols-outlined">picture_as_pdf</span>
                        <span class="material-symbols-outlined">present_to_all</span>
                        <span class="material-symbols-outlined">description</span>
                        <span class="material-symbols-outlined">movie</span>
                    </div>
                    <p class="mt-4 text-[11px] text-on-surface-variant">PDF, PPT, DOCX, MP4 (Maks. 50MB)</p>
                    <input id="fileInput" name="file" type="file" class="hidden" onchange="document.getElementById('fileName').textContent = this.files[0]?.name || ''">
                </div>
                <div id="fileName" class="mt-3 font-label-sm text-on-surface-variant text-center"></div>
                <div class="mt-6 flex gap-3 justify-end">
                    <button type="submit" class="px-6 py-2 bg-primary text-on-primary rounded font-label-sm hover:opacity-90 active:scale-95 transition-all flex items-center gap-2">
                        <span class="material-symbols-outlined text-[18px]">save</span> Simpan Materi
                    </button>
                </div>
            </section>
        </div>
    </form>

    <!-- History -->
    <div class="lg:col-span-12">
        <section class="bg-surface-container-lowest rounded-lg soft-shadow border border-outline-variant overflow-hidden">
            <div class="p-6 border-b border-outline-variant flex items-center justify-between">
                <h3 class="font-title-sm flex items-center gap-2">
                    <span class="material-symbols-outlined text-primary">history</span> Riwayat Unggahan
                </h3>
            </div>
            <div class="overflow-x-auto">
                <table class="w-full text-left border-collapse">
                    <thead class="bg-surface-container-low">
                        <tr>
                            <th class="px-6 py-4 font-label-sm text-on-surface-variant uppercase tracking-wider">Judul</th>
                            <th class="px-6 py-4 font-label-sm text-on-surface-variant uppercase tracking-wider">Kelas</th>
                            <th class="px-6 py-4 font-label-sm text-on-surface-variant uppercase tracking-wider">Tanggal</th>
                            <th class="px-6 py-4 font-label-sm text-on-surface-variant uppercase tracking-wider text-right">Aksi</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-outline-variant">
                        <?php if (empty($materiList)): ?>
                        <tr><td colspan="4" class="px-6 py-12 text-center font-body-md text-on-surface-variant">Belum ada materi.</td></tr>
                        <?php else: ?>
                            <?php foreach ($materiList as $m): ?>
                            <tr class="hover:bg-surface-container-low transition-colors">
                                <td class="px-6 py-4 font-body-md font-semibold text-on-surface"><?= htmlspecialchars($m['judul']) ?></td>
                                <td class="px-6 py-4"><span class="px-2 py-1 bg-secondary-container text-on-secondary-container rounded text-[10px] font-bold"><?= htmlspecialchars($m['nama_kelas']) ?></span></td>
                                <td class="px-6 py-4 text-on-surface-variant"><?= date('d M Y', strtotime($m['tanggal_upload'])) ?></td>
                                <td class="px-6 py-4 text-right">
                                    <div class="flex justify-end gap-2">
                                        <a href="../../assets/uploads/materi/<?= urlencode($m['file']) ?>" download class="p-2 hover:bg-primary-container/20 text-primary rounded transition-all" title="Unduh">
                                            <span class="material-symbols-outlined text-[20px]">download</span>
                                        </a>
                                        <form method="POST" class="inline" onsubmit="return confirm('Hapus materi ini?')">
                                            <?= csrf_field() ?>
                                            <input type="hidden" name="action" value="hapus">
                                            <input type="hidden" name="id_materi" value="<?= $m['id_materi'] ?>">
                                            <button class="p-2 hover:bg-error-container/20 text-error rounded transition-all" title="Hapus">
                                                <span class="material-symbols-outlined text-[20px]">delete</span>
                                            </button>
                                        </form>
                                    </div>
                                </td>
                            </tr>
                            <?php endforeach; ?>
                        <?php endif; ?>
                    </tbody>
                </table>
            </div>
        </section>
    </div>
</div>
<?php require_once __DIR__ . '/../../includes/footer.php'; ?>
