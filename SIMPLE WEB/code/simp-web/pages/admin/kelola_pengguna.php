<?php
$title = 'Kelola Akun';
$currentPage = 'pengguna';
require_once __DIR__ . '/../../includes/header.php';
require_once __DIR__ . '/../../config/database.php';

$message = '';
$messageType = '';
$tab = $_GET['tab'] ?? 'guru';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (!verify_csrf($_POST['_csrf_token'] ?? '')) {
        $message = 'Token CSRF tidak valid. Silakan refresh halaman.';
        $messageType = 'error';
    } else {
    $action = $_POST['action'] ?? '';

    if ($action === 'tambah' || $action === 'edit') {
        $nama  = trim($_POST['nama'] ?? '');
        $email = trim($_POST['email'] ?? '');
        $role  = $_POST['role'] ?? 'siswa';
        $pass  = $_POST['password'] ?? '';

        if ($nama === '' || $email === '') {
            $message = 'Nama dan email harus diisi.';
            $messageType = 'error';
        } else {
            if ($action === 'tambah') {
                if ($pass === '') {
                    $message = 'Password harus diisi untuk akun baru.';
                    $messageType = 'error';
                } else {
                    $hash = password_hash($pass, PASSWORD_BCRYPT);
                    $stmt = $pdo->prepare("INSERT INTO users (nama, email, password, role) VALUES (?, ?, ?, ?)");
                    $stmt->execute([$nama, $email, $hash, $role]);
                    $message = 'Akun berhasil ditambahkan.';
                    $messageType = 'success';
                }
            } else {
                $id = $_POST['id'] ?? 0;
                if ($pass !== '') {
                    $hash = password_hash($pass, PASSWORD_BCRYPT);
                    $stmt = $pdo->prepare("UPDATE users SET nama=?, email=?, password=?, role=? WHERE id=?");
                    $stmt->execute([$nama, $email, $hash, $role, $id]);
                } else {
                    $stmt = $pdo->prepare("UPDATE users SET nama=?, email=?, role=? WHERE id=?");
                    $stmt->execute([$nama, $email, $role, $id]);
                }
                $message = 'Akun berhasil diperbarui.';
                $messageType = 'success';
            }
        }
    } elseif ($action === 'hapus') {
        $id = $_POST['id'] ?? 0;
        $pdo->prepare("DELETE FROM users WHERE id = ? AND role != 'admin'")->execute([$id]);
        $message = 'Akun berhasil dihapus.';
        $messageType = 'success';
    }
    $tab = $_POST['tab'] ?? $tab;
    }
}

$guruList  = $pdo->query("SELECT * FROM users WHERE role='guru' ORDER BY nama")->fetchAll();
$siswaList = $pdo->query("SELECT * FROM users WHERE role='siswa' ORDER BY nama")->fetchAll();
$currentList = ($tab === 'guru') ? $guruList : $siswaList;
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
            <h2 class="font-display-lg text-display-lg text-on-surface">Kelola Akun</h2>
            <p class="font-body-md text-body-md text-on-surface-variant">Manajemen data guru dan siswa dalam sistem akademik.</p>
        </div>
        <button onclick="openModalTambah()" class="flex items-center justify-center gap-2 px-6 py-3 bg-primary text-on-primary rounded font-label-sm font-bold shadow-sm hover:brightness-110 active:scale-95 transition-all">
            <span class="material-symbols-outlined">person_add</span>
            Tambah Pengguna
        </button>
    </div>

    <div class="grid grid-cols-1 lg:grid-cols-12 gap-6">
        <div class="lg:col-span-3 space-y-6">
            <div class="bg-surface-container-lowest p-6 rounded-lg soft-shadow border border-outline-variant">
                <h3 class="font-title-sm text-title-sm mb-4">Tipe Akun</h3>
                <div class="flex flex-col gap-2">
                    <a href="?tab=guru" class="w-full text-left px-4 py-3 rounded flex justify-between items-center transition-all <?= $tab === 'guru' ? 'bg-primary-container text-on-primary-container' : 'text-on-surface-variant hover:bg-surface-container-low' ?>">
                        <span class="font-label-sm font-bold">Guru</span>
                        <?php if ($tab === 'guru'): ?><span class="material-symbols-outlined text-[18px]" style="font-variation-settings:'FILL'1">check_circle</span><?php endif; ?>
                    </a>
                    <a href="?tab=siswa" class="w-full text-left px-4 py-3 rounded flex justify-between items-center transition-all <?= $tab === 'siswa' ? 'bg-primary-container text-on-primary-container' : 'text-on-surface-variant hover:bg-surface-container-low' ?>">
                        <span class="font-label-sm font-bold">Siswa</span>
                        <?php if ($tab === 'siswa'): ?><span class="material-symbols-outlined text-[18px]" style="font-variation-settings:'FILL'1">check_circle</span><?php endif; ?>
                    </a>
                </div>
            </div>
            <div class="bg-primary p-6 rounded-lg text-on-primary shadow-lg flex flex-col justify-between h-40">
                <span class="material-symbols-outlined text-4xl opacity-20 self-end">groups</span>
                <div>
                    <p class="font-label-sm uppercase tracking-wider opacity-80">Total <?= $tab === 'guru' ? 'Guru' : 'Siswa' ?></p>
                    <h4 class="text-4xl font-bold"><?= count($currentList) ?></h4>
                </div>
            </div>
        </div>

        <div class="lg:col-span-9 bg-surface-container-lowest rounded-lg soft-shadow border border-outline-variant flex flex-col">
            <div class="p-6 border-b border-outline-variant flex flex-col md:flex-row gap-4 items-center justify-between">
                <div class="relative w-full md:w-96">
                    <span class="material-symbols-outlined absolute left-3 top-1/2 -translate-y-1/2 text-outline">search</span>
                    <input id="searchInput" class="w-full pl-10 pr-4 py-2 bg-surface-container-low border border-outline-variant rounded focus:ring-2 focus:ring-primary focus:border-primary outline-none text-body-md transition-all" placeholder="Cari nama atau email..." oninput="filterTable()">
                </div>
            </div>

            <div class="overflow-x-auto">
                <table class="w-full text-left border-collapse" id="userTable">
                    <thead>
                        <tr class="bg-surface-container-low text-on-surface-variant border-b border-outline-variant">
                            <th class="px-6 py-4 font-label-sm uppercase tracking-wider w-16">No</th>
                            <th class="px-6 py-4 font-label-sm uppercase tracking-wider">Nama</th>
                            <th class="px-6 py-4 font-label-sm uppercase tracking-wider">Email</th>
                            <th class="px-6 py-4 font-label-sm uppercase tracking-wider text-right">Aksi</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-outline-variant">
                        <?php if (empty($currentList)): ?>
                        <tr><td colspan="4" class="px-6 py-12 text-center font-body-md text-on-surface-variant">Tidak ada data <?= htmlspecialchars($tab, ENT_QUOTES, 'UTF-8') ?>.</td></tr>
                        <?php else: ?>
                            <?php foreach ($currentList as $i => $user): ?>
                            <tr class="bg-white hover:bg-surface-container transition-colors" data-nama="<?= htmlspecialchars(strtolower($user['nama'])) ?>" data-email="<?= htmlspecialchars(strtolower($user['email'])) ?>">
                                <td class="px-6 py-4 font-body-md text-on-surface-variant"><?= str_pad($i + 1, 2, '0', STR_PAD_LEFT) ?></td>
                                <td class="px-6 py-4">
                                    <div class="flex items-center gap-3">
                                        <div class="w-8 h-8 rounded-full bg-primary-fixed text-on-primary-fixed flex items-center justify-center font-bold text-xs"><?= htmlspecialchars(strtoupper(substr($user['nama'], 0, 2)), ENT_QUOTES, 'UTF-8') ?></div>
                                        <span class="font-body-md font-semibold"><?= htmlspecialchars($user['nama']) ?></span>
                                    </div>
                                </td>
                                <td class="px-6 py-4 font-body-md text-on-surface-variant"><?= htmlspecialchars($user['email']) ?></td>
                                <td class="px-6 py-4 text-right">
                                    <div class="flex justify-end gap-2">
                                        <button onclick="openModalEdit(<?= $user['id'] ?>, '<?= htmlspecialchars($user['nama'], ENT_QUOTES) ?>', '<?= htmlspecialchars($user['email'], ENT_QUOTES) ?>', '<?= $user['role'] ?>')" class="p-2 text-primary hover:bg-primary/10 rounded transition-colors"><span class="material-symbols-outlined text-[20px]">edit</span></button>
                                        <button onclick="if(confirm('Hapus akun <?= htmlspecialchars($user['nama'], ENT_QUOTES) ?>?')){ document.getElementById('hapusId').value=<?= $user['id'] ?>; document.getElementById('formHapus').submit(); }" class="p-2 text-error hover:bg-error/10 rounded transition-colors"><span class="material-symbols-outlined text-[20px]">delete</span></button>
                                    </div>
                                </td>
                            </tr>
                            <?php endforeach; ?>
                        <?php endif; ?>
                    </tbody>
                </table>
            </div>
            <div class="p-6 border-t border-outline-variant flex items-center justify-between">
                <p class="font-label-sm text-label-sm text-on-surface-variant">Menampilkan <?= count($currentList) ?> <?= htmlspecialchars($tab, ENT_QUOTES, 'UTF-8') ?></p>
            </div>
        </div>
    </div>
</div>

<!-- Form Hapus -->
<form id="formHapus" method="POST" class="hidden">
    <?= csrf_field() ?>
    <input type="hidden" name="action" value="hapus">
    <input type="hidden" name="id" id="hapusId">
    <input type="hidden" name="tab" value="<?= htmlspecialchars($tab, ENT_QUOTES, 'UTF-8') ?>">
</form>

<!-- Modal Tambah/Edit -->
<div id="modalUser" class="fixed inset-0 z-50 hidden bg-black/30 flex items-center justify-center p-4" onclick="if(event.target===this)closeModal('modalUser')">
    <div class="bg-surface-container-lowest rounded-lg soft-shadow max-w-lg w-full p-6">
        <div class="flex justify-between items-center mb-6">
            <h3 class="font-headline-md text-headline-md text-on-surface" id="modalUserTitle">Tambah Pengguna</h3>
            <button onclick="closeModal('modalUser')" class="p-2 hover:bg-surface-container-high rounded-full"><span class="material-symbols-outlined">close</span></button>
        </div>
        <form method="POST">
            <?= csrf_field() ?>
            <input type="hidden" name="action" id="formAction" value="tambah">
            <input type="hidden" name="id" id="editId">
            <input type="hidden" name="tab" value="<?= htmlspecialchars($tab, ENT_QUOTES, 'UTF-8') ?>">
            <div class="space-y-4">
                <div>
                    <label class="block font-label-sm text-label-sm text-on-surface-variant mb-1">Nama Lengkap</label>
                    <input name="nama" id="formNama" required class="w-full px-4 py-3 bg-surface border border-outline-variant rounded focus:ring-2 focus:ring-primary focus:border-primary outline-none text-body-md" placeholder="Nama lengkap">
                </div>
                <div>
                    <label class="block font-label-sm text-label-sm text-on-surface-variant mb-1">Email</label>
                    <input type="email" name="email" id="formEmail" required class="w-full px-4 py-3 bg-surface border border-outline-variant rounded focus:ring-2 focus:ring-primary focus:border-primary outline-none text-body-md" placeholder="email@sekolah.sch.id">
                </div>
                <div>
                    <label class="block font-label-sm text-label-sm text-on-surface-variant mb-1">Role</label>
                    <select name="role" id="formRole" class="w-full px-4 py-3 bg-surface border border-outline-variant rounded focus:ring-2 focus:ring-primary focus:border-primary outline-none text-body-md">
                        <option value="guru">Guru</option>
                        <option value="siswa">Siswa</option>
                    </select>
                </div>
                <div>
                    <label class="block font-label-sm text-label-sm text-on-surface-variant mb-1">
                        Password <span id="passLabel" class="text-on-surface-variant">(untuk akun baru)</span>
                    </label>
                    <input type="password" name="password" id="formPassword" class="w-full px-4 py-3 bg-surface border border-outline-variant rounded focus:ring-2 focus:ring-primary focus:border-primary outline-none text-body-md" placeholder="Kosongkan jika tidak ingin ganti">
                </div>
            </div>
            <div class="mt-6 flex justify-end gap-3">
                <button type="button" onclick="closeModal('modalUser')" class="px-4 py-2 border border-outline-variant rounded font-label-sm hover:bg-surface-container-low">Batal</button>
                <button type="submit" class="px-6 py-2 bg-primary text-on-primary rounded font-label-sm">Simpan</button>
            </div>
        </form>
    </div>
</div>

<script>
function closeModal(id) { document.getElementById(id).classList.add('hidden'); }

function openModalTambah() {
    document.getElementById('formAction').value = 'tambah';
    document.getElementById('modalUserTitle').textContent = 'Tambah Pengguna';
    document.getElementById('formNama').value = '';
    document.getElementById('formEmail').value = '';
    document.getElementById('formRole').value = 'guru';
    document.getElementById('formPassword').value = '';
    document.getElementById('passLabel').textContent = '(untuk akun baru)';
    document.getElementById('formPassword').required = true;
    document.getElementById('modalUser').classList.remove('hidden');
}

function openModalEdit(id, nama, email, role) {
    document.getElementById('formAction').value = 'edit';
    document.getElementById('modalUserTitle').textContent = 'Edit Pengguna';
    document.getElementById('editId').value = id;
    document.getElementById('formNama').value = nama;
    document.getElementById('formEmail').value = email;
    document.getElementById('formRole').value = role;
    document.getElementById('formPassword').value = '';
    document.getElementById('passLabel').textContent = '(kosongkan jika tidak diganti)';
    document.getElementById('formPassword').required = false;
    document.getElementById('modalUser').classList.remove('hidden');
}

function filterTable() {
    const q = document.getElementById('searchInput').value.toLowerCase();
    document.querySelectorAll('#userTable tbody tr').forEach(row => {
        if (row.dataset.nama && row.dataset.email) {
            row.style.display = row.dataset.nama.includes(q) || row.dataset.email.includes(q) ? '' : 'none';
        }
    });
}
</script>
<?php require_once __DIR__ . '/../../includes/footer.php'; ?>
