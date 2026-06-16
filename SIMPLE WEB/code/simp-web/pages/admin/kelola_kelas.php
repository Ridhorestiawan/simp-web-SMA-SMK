<?php
$title = 'Kelola Kelas';
$currentPage = 'kelas';
require_once __DIR__ . '/../../includes/header.php';
require_once __DIR__ . '/../../config/database.php';

$message = '';
$messageType = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (!verify_csrf($_POST['_csrf_token'] ?? '')) {
        $message = 'Token CSRF tidak valid. Silakan refresh halaman.';
        $messageType = 'error';
    } else {
    $action = $_POST['action'] ?? '';

    if ($action === 'tambah') {
        $namaKelas  = trim($_POST['nama_kelas'] ?? '');
        $waliKelas  = $_POST['wali_kelas'] ?? '';
        if ($namaKelas === '') {
            $message = 'Nama kelas harus diisi.';
            $messageType = 'error';
        } else {
            $stmt = $pdo->prepare("INSERT INTO kelas (nama_kelas, wali_kelas) VALUES (?, ?)");
            $stmt->execute([$namaKelas, $waliKelas ?: null]);
            $idKelas = $pdo->lastInsertId();
            if (!empty($_POST['siswa'])) {
                $stmtSiswa = $pdo->prepare("INSERT INTO kelas_siswa (id_kelas, id_siswa) VALUES (?, ?)");
                foreach ($_POST['siswa'] as $idSiswa) {
                    $stmtSiswa->execute([$idKelas, $idSiswa]);
                }
            }
            $message = 'Kelas berhasil ditambahkan.';
            $messageType = 'success';
        }
    } elseif ($action === 'edit') {
        $idKelas   = $_POST['id_kelas'] ?? 0;
        $namaKelas = trim($_POST['nama_kelas'] ?? '');
        $waliKelas = $_POST['wali_kelas'] ?? '';
        if ($namaKelas === '') {
            $message = 'Nama kelas harus diisi.';
            $messageType = 'error';
        } else {
            $stmt = $pdo->prepare("UPDATE kelas SET nama_kelas = ?, wali_kelas = ? WHERE id_kelas = ?");
            $stmt->execute([$namaKelas, $waliKelas ?: null, $idKelas]);
            $pdo->prepare("DELETE FROM kelas_siswa WHERE id_kelas = ?")->execute([$idKelas]);
            if (!empty($_POST['siswa'])) {
                $stmtSiswa = $pdo->prepare("INSERT INTO kelas_siswa (id_kelas, id_siswa) VALUES (?, ?)");
                foreach ($_POST['siswa'] as $idSiswa) {
                    $stmtSiswa->execute([$idKelas, $idSiswa]);
                }
            }
            $message = 'Kelas berhasil diperbarui.';
            $messageType = 'success';
        }
    } elseif ($action === 'hapus') {
        $idKelas = $_POST['id_kelas'] ?? 0;
        $pdo->prepare("DELETE FROM kelas WHERE id_kelas = ?")->execute([$idKelas]);
        $message = 'Kelas berhasil dihapus.';
        $messageType = 'success';
    }
    }
}

$kelasList = $pdo->query("
    SELECT k.*, u.nama as wali_nama,
        (SELECT COUNT(*) FROM kelas_siswa ks WHERE ks.id_kelas = k.id_kelas) as jumlah_siswa
    FROM kelas k
    LEFT JOIN users u ON k.wali_kelas = u.id
    ORDER BY k.nama_kelas
")->fetchAll();

$guruList = $pdo->query("SELECT id, nama FROM users WHERE role='guru' ORDER BY nama")->fetchAll();
$siswaList = $pdo->query("SELECT id, nama FROM users WHERE role='siswa' ORDER BY nama")->fetchAll();

$siswaPerKelas = [];
if (!empty($kelasList)) {
    $ids = array_column($kelasList, 'id_kelas');
    $placeholders = implode(',', array_fill(0, count($ids), '?'));
    $stmt = $pdo->prepare("SELECT id_kelas, id_siswa FROM kelas_siswa WHERE id_kelas IN ($placeholders)");
    $stmt->execute($ids);
    foreach ($stmt->fetchAll() as $row) {
        $siswaPerKelas[$row['id_kelas']][] = $row['id_siswa'];
    }
}
?>
<div class="max-w-[1440px] mx-auto">
    <?php if ($message): ?>
        <div class="mb-6 px-4 py-3 rounded flex items-center gap-2 border <?= $messageType === 'error' ? 'bg-error-container border-error text-on-error-container' : 'bg-green-50 border-green-300 text-green-800' ?>">
            <span class="material-symbols-outlined text-lg"><?= $messageType === 'error' ? 'error' : 'check_circle' ?></span>
            <p class="font-body-md text-body-md"><?= htmlspecialchars($message) ?></p>
        </div>
    <?php endif; ?>

    <div class="flex flex-col md:flex-row md:items-center justify-between gap-4 mb-8">
        <div>
            <h2 class="font-display-lg-mobile md:font-display-lg text-display-lg-mobile md:text-display-lg text-on-surface">Kelola Kelas</h2>
            <p class="font-body-md text-body-md text-on-surface-variant">Manajemen data kelas, wali kelas, dan daftar siswa.</p>
        </div>
        <button onclick="openModal('modalTambah')" class="bg-primary text-on-primary px-6 py-3 rounded font-title-sm flex items-center justify-center gap-2 active:scale-95 transition-transform soft-shadow">
            <span class="material-symbols-outlined text-[20px]">add</span>
            Buat Kelas Baru
        </button>
    </div>

    <div class="grid grid-cols-2 md:grid-cols-4 gap-4 mb-8">
        <div class="bg-surface-container-lowest p-4 rounded-lg border border-outline-variant soft-shadow">
            <p class="font-label-sm text-label-sm text-on-surface-variant uppercase tracking-wider">Total Kelas</p>
            <p class="font-headline-md text-headline-md text-primary mt-1"><?= count($kelasList) ?></p>
        </div>
        <div class="bg-surface-container-lowest p-4 rounded-lg border border-outline-variant soft-shadow">
            <p class="font-label-sm text-label-sm text-on-surface-variant uppercase tracking-wider">Total Siswa</p>
            <p class="font-headline-md text-headline-md text-primary mt-1"><?= $pdo->query("SELECT COUNT(*) FROM users WHERE role='siswa'")->fetchColumn() ?></p>
        </div>
        <div class="bg-surface-container-lowest p-4 rounded-lg border border-outline-variant soft-shadow">
            <p class="font-label-sm text-label-sm text-on-surface-variant uppercase tracking-wider">Wali Kelas</p>
            <p class="font-headline-md text-headline-md text-primary mt-1"><?= count(array_filter($kelasList, fn($k) => $k['wali_kelas'])) ?></p>
        </div>
        <div class="bg-surface-container-lowest p-4 rounded-lg border border-outline-variant soft-shadow">
            <p class="font-label-sm text-label-sm text-on-surface-variant uppercase tracking-wider">Total Guru</p>
            <p class="font-headline-md text-headline-md text-primary mt-1"><?= count($guruList) ?></p>
        </div>
    </div>

    <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-3 xl:grid-cols-4 gap-6">
        <?php if (empty($kelasList)): ?>
        <div class="col-span-full flex flex-col items-center justify-center py-20 px-4 text-center">
            <div class="w-24 h-24 mb-4 rounded-full bg-surface-container-high flex items-center justify-center">
                <span class="material-symbols-outlined text-outline text-5xl">school</span>
            </div>
            <h3 class="font-headline-md text-headline-md text-on-surface">Belum ada kelas</h3>
            <p class="font-body-md text-body-md text-on-surface-variant mt-2 max-w-sm">Mulai dengan membuat kelas pertama.</p>
            <button onclick="openModal('modalTambah')" class="mt-6 text-primary font-title-sm flex items-center gap-2 hover:underline">
                <span class="material-symbols-outlined">add_circle</span>
                Buat Kelas
            </button>
        </div>
        <?php endif; ?>

        <?php foreach ($kelasList as $kelas): ?>
        <div class="bg-surface-container-lowest p-6 rounded-lg border border-outline-variant soft-shadow group transition-all">
            <div class="flex justify-between items-start mb-4">
                <div class="w-12 h-12 rounded-md bg-primary-container/10 flex items-center justify-center text-primary">
                    <span class="material-symbols-outlined text-[32px]">groups</span>
                </div>
                <div class="flex gap-1">
                    <button onclick="openEdit(<?= $kelas['id_kelas'] ?>)" class="p-2 hover:bg-surface-container-high rounded-full text-on-surface-variant" title="Edit">
                        <span class="material-symbols-outlined text-[20px]">edit</span>
                    </button>
                    <button onclick="confirmHapus(<?= $kelas['id_kelas'] ?>, '<?= htmlspecialchars($kelas['nama_kelas'], ENT_QUOTES) ?>')" class="p-2 hover:bg-error-container/20 rounded-full text-error" title="Hapus">
                        <span class="material-symbols-outlined text-[20px]">delete</span>
                    </button>
                </div>
            </div>
            <h3 class="font-title-sm text-title-sm text-on-surface"><?= htmlspecialchars($kelas['nama_kelas']) ?></h3>
            <div class="mt-4 space-y-2">
                <div class="flex items-center gap-2">
                    <span class="material-symbols-outlined text-on-surface-variant text-[18px]">person</span>
                    <span class="font-body-md text-body-md text-on-surface-variant"><?= htmlspecialchars($kelas['wali_nama'] ?? '—') ?></span>
                </div>
                <div class="flex items-center gap-2">
                    <span class="material-symbols-outlined text-on-surface-variant text-[18px]">account_circle</span>
                    <span class="font-body-md text-body-md text-on-surface-variant"><?= $kelas['jumlah_siswa'] ?> Siswa</span>
                </div>
            </div>
        </div>
        <?php endforeach; ?>
    </div>
</div>

<!-- Modal Tambah -->
<div id="modalTambah" class="fixed inset-0 z-50 hidden bg-black/30 flex items-center justify-center p-4" onclick="if(event.target===this)closeModal('modalTambah')">
    <div class="bg-surface-container-lowest rounded-lg soft-shadow max-w-lg w-full max-h-[90vh] overflow-y-auto p-6">
        <div class="flex justify-between items-center mb-6">
            <h3 class="font-headline-md text-headline-md text-on-surface">Tambah Kelas</h3>
            <button onclick="closeModal('modalTambah')" class="p-2 hover:bg-surface-container-high rounded-full"><span class="material-symbols-outlined">close</span></button>
        </div>
        <form method="POST">
            <?= csrf_field() ?>
            <input type="hidden" name="action" value="tambah">
            <div class="space-y-4">
                <div>
                    <label class="block font-label-sm text-label-sm text-on-surface-variant mb-1">Nama Kelas</label>
                    <input name="nama_kelas" required class="w-full px-4 py-3 bg-surface border border-outline-variant rounded focus:ring-2 focus:ring-primary focus:border-primary outline-none text-body-md" placeholder="X IPA 1">
                </div>
                <div>
                    <label class="block font-label-sm text-label-sm text-on-surface-variant mb-1">Wali Kelas</label>
                    <select name="wali_kelas" class="w-full px-4 py-3 bg-surface border border-outline-variant rounded focus:ring-2 focus:ring-primary focus:border-primary outline-none text-body-md">
                        <option value="">— Pilih Guru —</option>
                        <?php foreach ($guruList as $g): ?>
                        <option value="<?= $g['id'] ?>"><?= htmlspecialchars($g['nama']) ?></option>
                        <?php endforeach; ?>
                    </select>
                </div>
                <div>
                    <label class="block font-label-sm text-label-sm text-on-surface-variant mb-1">Assign Siswa</label>
                    <div class="max-h-40 overflow-y-auto space-y-1 border border-outline-variant rounded p-2">
                        <?php foreach ($siswaList as $s): ?>
                        <label class="flex items-center gap-2 cursor-pointer hover:bg-surface-container-low px-2 py-1 rounded">
                            <input type="checkbox" name="siswa[]" value="<?= $s['id'] ?>" class="w-4 h-4 text-primary border-outline-variant rounded focus:ring-primary">
                            <span class="font-body-md text-body-md text-on-surface"><?= htmlspecialchars($s['nama']) ?></span>
                        </label>
                        <?php endforeach; ?>
                    </div>
                </div>
            </div>
            <div class="mt-6 flex justify-end gap-3">
                <button type="button" onclick="closeModal('modalTambah')" class="px-4 py-2 border border-outline-variant rounded font-label-sm hover:bg-surface-container-low">Batal</button>
                <button type="submit" class="px-6 py-2 bg-primary text-on-primary rounded font-label-sm">Simpan</button>
            </div>
        </form>
    </div>
</div>

<!-- Modal Edit -->
<div id="modalEdit" class="fixed inset-0 z-50 hidden bg-black/30 flex items-center justify-center p-4" onclick="if(event.target===this)closeModal('modalEdit')">
    <div class="bg-surface-container-lowest rounded-lg soft-shadow max-w-lg w-full max-h-[90vh] overflow-y-auto p-6">
        <div class="flex justify-between items-center mb-6">
            <h3 class="font-headline-md text-headline-md text-on-surface">Edit Kelas</h3>
            <button onclick="closeModal('modalEdit')" class="p-2 hover:bg-surface-container-high rounded-full"><span class="material-symbols-outlined">close</span></button>
        </div>
        <form method="POST">
            <?= csrf_field() ?>
            <input type="hidden" name="action" value="edit">
            <input type="hidden" name="id_kelas" id="edit_id_kelas">
            <div class="space-y-4">
                <div>
                    <label class="block font-label-sm text-label-sm text-on-surface-variant mb-1">Nama Kelas</label>
                    <input name="nama_kelas" id="edit_nama_kelas" required class="w-full px-4 py-3 bg-surface border border-outline-variant rounded focus:ring-2 focus:ring-primary focus:border-primary outline-none text-body-md">
                </div>
                <div>
                    <label class="block font-label-sm text-label-sm text-on-surface-variant mb-1">Wali Kelas</label>
                    <select name="wali_kelas" id="edit_wali_kelas" class="w-full px-4 py-3 bg-surface border border-outline-variant rounded focus:ring-2 focus:ring-primary focus:border-primary outline-none text-body-md">
                        <option value="">— Pilih Guru —</option>
                        <?php foreach ($guruList as $g): ?>
                        <option value="<?= $g['id'] ?>"><?= htmlspecialchars($g['nama']) ?></option>
                        <?php endforeach; ?>
                    </select>
                </div>
                <div>
                    <label class="block font-label-sm text-label-sm text-on-surface-variant mb-1">Assign Siswa</label>
                    <div class="max-h-40 overflow-y-auto space-y-1 border border-outline-variant rounded p-2">
                        <?php foreach ($siswaList as $s): ?>
                        <label class="flex items-center gap-2 cursor-pointer hover:bg-surface-container-low px-2 py-1 rounded">
                            <input type="checkbox" name="siswa[]" value="<?= $s['id'] ?>" class="siswa_check_edit w-4 h-4 text-primary border-outline-variant rounded focus:ring-primary">
                            <span class="font-body-md text-body-md text-on-surface"><?= htmlspecialchars($s['nama']) ?></span>
                        </label>
                        <?php endforeach; ?>
                    </div>
                </div>
            </div>
            <div class="mt-6 flex justify-end gap-3">
                <button type="button" onclick="closeModal('modalEdit')" class="px-4 py-2 border border-outline-variant rounded font-label-sm hover:bg-surface-container-low">Batal</button>
                <button type="submit" class="px-6 py-2 bg-primary text-on-primary rounded font-label-sm">Simpan</button>
            </div>
        </form>
    </div>
</div>

<!-- Form Hapus -->
<form id="formHapus" method="POST" class="hidden">
    <?= csrf_field() ?>
    <input type="hidden" name="action" value="hapus">
    <input type="hidden" name="id_kelas" id="hapus_id_kelas">
</form>

<script>
const siswaPerKelas = <?= json_encode($siswaPerKelas) ?>;

function openModal(id) { document.getElementById(id).classList.remove('hidden'); }
function closeModal(id) { document.getElementById(id).classList.add('hidden'); }

function openEdit(id) {
    const kelasData = <?= json_encode(array_map(fn($k) => ['id_kelas' => $k['id_kelas'], 'nama_kelas' => $k['nama_kelas'], 'wali_kelas' => $k['wali_kelas']], $kelasList)) ?>;
    const k = kelasData.find(x => x.id_kelas == id);
    if (!k) return;
    document.getElementById('edit_id_kelas').value = k.id_kelas;
    document.getElementById('edit_nama_kelas').value = k.nama_kelas;
    document.getElementById('edit_wali_kelas').value = k.wali_kelas || '';
    document.querySelectorAll('.siswa_check_edit').forEach(cb => {
        cb.checked = siswaPerKelas[id] ? siswaPerKelas[id].includes(parseInt(cb.value)) : false;
    });
    openModal('modalEdit');
}

function confirmHapus(id, nama) {
    if (confirm(`Hapus kelas "${nama}"? Semua data terkait akan ikut terhapus.`)) {
        document.getElementById('hapus_id_kelas').value = id;
        document.getElementById('formHapus').submit();
    }
}
</script>
<?php require_once __DIR__ . '/../../includes/footer.php'; ?>
