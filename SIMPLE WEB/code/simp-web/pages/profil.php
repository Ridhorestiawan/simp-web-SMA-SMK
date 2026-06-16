<?php
$title = 'Profil Saya';
$currentPage = 'profil';
require_once __DIR__ . '/../includes/header.php';
require_once __DIR__ . '/../config/database.php';

$userId = $_SESSION['user_id'];
$message = '';
$error = '';

$stmt = $pdo->prepare("SELECT * FROM users WHERE id = ?");
$stmt->execute([$userId]);
$user = $stmt->fetch();

if (!$user) {
    header("Location: ../auth/login.php");
    exit;
}

$fotoPath = $user['foto'] ? '../' . $user['foto'] : null;

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (!verify_csrf($_POST['_csrf_token'] ?? '')) {
        $error = 'Token CSRF tidak valid. Silakan refresh halaman.';
    } else {
        $action = $_POST['action'] ?? '';

        if ($action === 'update_nama') {
            $nama = trim($_POST['nama'] ?? '');
            if ($nama === '') {
                $error = 'Nama tidak boleh kosong.';
            } else {
                $stmt = $pdo->prepare("UPDATE users SET nama = ? WHERE id = ?");
                $stmt->execute([$nama, $userId]);
                $_SESSION['nama'] = $nama;
                $message = 'Nama berhasil diperbarui.';
            }
        } elseif ($action === 'update_email') {
            $email = trim($_POST['email'] ?? '');
            if ($email === '' || !filter_var($email, FILTER_VALIDATE_EMAIL)) {
                $error = 'Email tidak valid.';
            } else {
                $stmt = $pdo->prepare("SELECT id FROM users WHERE email = ? AND id != ?");
                $stmt->execute([$email, $userId]);
                if ($stmt->fetch()) {
                    $error = 'Email sudah digunakan oleh pengguna lain.';
                } else {
                    $stmt = $pdo->prepare("UPDATE users SET email = ? WHERE id = ?");
                    $stmt->execute([$email, $userId]);
                    $_SESSION['email'] = $email;
                    $message = 'Email berhasil diperbarui.';
                }
            }
        } elseif ($action === 'update_password') {
            $currentPassword = $_POST['current_password'] ?? '';
            $newPassword = $_POST['new_password'] ?? '';
            $confirmPassword = $_POST['confirm_password'] ?? '';

            if (!password_verify($currentPassword, $user['password'])) {
                $error = 'Password saat ini salah.';
            } elseif (strlen($newPassword) < 6) {
                $error = 'Password baru minimal 6 karakter.';
            } elseif ($newPassword !== $confirmPassword) {
                $error = 'Konfirmasi password tidak cocok.';
            } else {
                $hash = password_hash($newPassword, PASSWORD_DEFAULT);
                $stmt = $pdo->prepare("UPDATE users SET password = ? WHERE id = ?");
                $stmt->execute([$hash, $userId]);
                $message = 'Password berhasil diperbarui.';
            }
        } elseif ($action === 'update_foto') {
            if (isset($_FILES['foto']) && $_FILES['foto']['error'] === UPLOAD_ERR_OK) {
                $allowedTypes = ['image/jpeg', 'image/png', 'image/gif', 'image/webp'];
                $maxSize = 2 * 1024 * 1024;

                if (!in_array($_FILES['foto']['type'], $allowedTypes)) {
                    $error = 'Tipe file harus JPG, PNG, GIF, atau WebP.';
                } elseif ($_FILES['foto']['size'] > $maxSize) {
                    $error = 'Ukuran file maksimal 2MB.';
                } else {
                    $ext = pathinfo($_FILES['foto']['name'], PATHINFO_EXTENSION);
                    $filename = 'user_' . $userId . '_' . time() . '.' . $ext;
                    $uploadDir = __DIR__ . '/../assets/uploads/profil/';
                    $destPath = $uploadDir . $filename;

                    if (!is_dir($uploadDir)) {
                        $error = 'Direktori upload tidak ditemukan.';
                    } elseif (!move_uploaded_file($_FILES['foto']['tmp_name'], $destPath)) {
                        $error = 'Gagal mengupload file.';
                    } else {
                        if ($user['foto'] && file_exists(__DIR__ . '/../' . $user['foto'])) {
                            unlink(__DIR__ . '/../' . $user['foto']);
                        }
                        $relativePath = 'assets/uploads/profil/' . $filename;
                        $stmt = $pdo->prepare("UPDATE users SET foto = ? WHERE id = ?");
                        $stmt->execute([$relativePath, $userId]);
                        $user['foto'] = $relativePath;
                        $fotoPath = '../' . $relativePath;
                        $message = 'Foto profil berhasil diperbarui.';
                    }
                }
            } else {
                $error = 'Pilih file foto terlebih dahulu.';
            }
        }

        $stmt = $pdo->prepare("SELECT * FROM users WHERE id = ?");
        $stmt->execute([$userId]);
        $user = $stmt->fetch();
    }
}

$initial = strtoupper(substr($user['nama'], 0, 1));
?>
<div class="max-w-2xl mx-auto space-y-gutter">
    <h1 class="font-headline-md text-headline-md text-on-surface mb-2">Profil Saya</h1>
    <p class="font-body-md text-body-md text-on-surface-variant mb-6">Kelola informasi akun pribadi Anda</p>

    <?php if ($message): ?>
        <div class="px-4 py-3 bg-[#d1e7dd] border border-[#badbcc] text-[#0f5132] rounded flex items-center gap-2">
            <span class="material-symbols-outlined text-lg">check_circle</span>
            <p class="font-body-md text-body-md"><?= htmlspecialchars($message) ?></p>
        </div>
    <?php endif; ?>
    <?php if ($error): ?>
        <div class="px-4 py-3 bg-error-container border border-error rounded flex items-center gap-2">
            <span class="material-symbols-outlined text-error text-lg">error</span>
            <p class="font-body-md text-body-md text-on-error-container"><?= htmlspecialchars($error) ?></p>
        </div>
    <?php endif; ?>

    <div class="bg-surface-container-lowest rounded-lg p-8 shadow-sm border border-outline-variant">
        <div class="flex flex-col md:flex-row items-center gap-6 mb-8 pb-6 border-b border-outline-variant">
            <div class="relative w-24 h-24 rounded-full overflow-hidden border-2 border-primary bg-primary-fixed flex items-center justify-center shrink-0">
                <?php if ($fotoPath && file_exists(__DIR__ . '/../' . $user['foto'])): ?>
                    <img src="<?= htmlspecialchars($fotoPath) ?>" alt="Foto Profil" class="w-full h-full object-cover">
                <?php else: ?>
                    <span class="text-primary text-[32px] font-bold"><?= htmlspecialchars($initial) ?></span>
                <?php endif; ?>
            </div>
            <div class="text-center md:text-left">
                <h2 class="font-headline-md text-headline-md text-on-surface"><?= htmlspecialchars($user['nama']) ?></h2>
                <p class="font-body-md text-body-md text-on-surface-variant"><?= htmlspecialchars($user['email']) ?></p>
                <span class="inline-block mt-2 px-3 py-1 bg-primary-container text-on-primary-container font-label-sm text-label-sm rounded"><?= $roleLabel ?></span>
            </div>
        </div>

        <form method="POST" class="mb-8 pb-6 border-b border-outline-variant">
            <?= csrf_field() ?>
            <input type="hidden" name="action" value="update_nama">
            <h3 class="font-title-sm text-title-sm text-on-surface mb-3">Ubah Nama</h3>
            <div class="flex flex-col md:flex-row gap-3">
                <input type="text" name="nama" value="<?= htmlspecialchars($user['nama']) ?>" required
                       class="flex-1 px-4 py-3 bg-surface border border-outline-variant rounded focus:ring-2 focus:ring-primary focus:border-primary outline-none text-body-md">
                <button type="submit" class="px-5 py-3 bg-primary text-on-primary font-title-sm text-title-sm rounded hover:bg-primary-container transition-all whitespace-nowrap">Simpan Nama</button>
            </div>
        </form>

        <form method="POST" class="mb-8 pb-6 border-b border-outline-variant">
            <?= csrf_field() ?>
            <input type="hidden" name="action" value="update_email">
            <h3 class="font-title-sm text-title-sm text-on-surface mb-3">Ubah Email</h3>
            <div class="flex flex-col md:flex-row gap-3">
                <input type="email" name="email" value="<?= htmlspecialchars($user['email']) ?>" required
                       class="flex-1 px-4 py-3 bg-surface border border-outline-variant rounded focus:ring-2 focus:ring-primary focus:border-primary outline-none text-body-md">
                <button type="submit" class="px-5 py-3 bg-primary text-on-primary font-title-sm text-title-sm rounded hover:bg-primary-container transition-all whitespace-nowrap">Simpan Email</button>
            </div>
        </form>

        <form method="POST" class="mb-8 pb-6 border-b border-outline-variant">
            <?= csrf_field() ?>
            <input type="hidden" name="action" value="update_password">
            <h3 class="font-title-sm text-title-sm text-on-surface mb-3">Ganti Password</h3>
            <div class="space-y-3">
                <input type="password" name="current_password" placeholder="Password saat ini" required
                       class="w-full px-4 py-3 bg-surface border border-outline-variant rounded focus:ring-2 focus:ring-primary focus:border-primary outline-none text-body-md">
                <input type="password" name="new_password" placeholder="Password baru (min. 6 karakter)" required
                       class="w-full px-4 py-3 bg-surface border border-outline-variant rounded focus:ring-2 focus:ring-primary focus:border-primary outline-none text-body-md">
                <input type="password" name="confirm_password" placeholder="Konfirmasi password baru" required
                       class="w-full px-4 py-3 bg-surface border border-outline-variant rounded focus:ring-2 focus:ring-primary focus:border-primary outline-none text-body-md">
                <button type="submit" class="px-5 py-3 bg-primary text-on-primary font-title-sm text-title-sm rounded hover:bg-primary-container transition-all">Ubah Password</button>
            </div>
        </form>

        <form method="POST" enctype="multipart/form-data">
            <?= csrf_field() ?>
            <input type="hidden" name="action" value="update_foto">
            <h3 class="font-title-sm text-title-sm text-on-surface mb-3">Foto Profil</h3>
            <div class="flex flex-col md:flex-row gap-3 items-start">
                <input type="file" name="foto" accept="image/jpeg,image/png,image/gif,image/webp" required
                       class="flex-1 w-full px-4 py-3 bg-surface border border-outline-variant rounded file:mr-3 file:py-1 file:px-3 file:rounded file:border-0 file:bg-primary file:text-on-primary file:font-label-sm file:text-label-sm text-body-md">
                <button type="submit" class="px-5 py-3 bg-primary text-on-primary font-title-sm text-title-sm rounded hover:bg-primary-container transition-all whitespace-nowrap">Upload Foto</button>
            </div>
            <p class="font-label-sm text-label-sm text-outline mt-2">Format: JPG, PNG, GIF, WebP. Maks. 2MB.</p>
        </form>
    </div>
</div>

<?php require_once __DIR__ . '/../includes/footer.php'; ?>
