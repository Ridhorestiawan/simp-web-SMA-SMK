<?php

$role      = $_SESSION['role'];
$currentPage = $currentPage ?? 'dashboard';

$basePath = match ($role) {
    'admin' => '../pages/admin',
    'guru'  => '../pages/guru',
    'siswa' => '../pages/siswa',
    default => '#',
};

$logoutPath = $rootPath . 'auth/logout.php';

$navItems = match ($role) {
    'admin' => [
        ['id' => 'dashboard', 'label' => 'Dashboard',      'icon' => 'dashboard',   'href' => "$basePath/dashboard.php"],
        ['id' => 'kelas',     'label' => 'Kelola Kelas',    'icon' => 'school',      'href' => "$basePath/kelola_kelas.php"],
        ['id' => 'pengguna',  'label' => 'Kelola Akun',     'icon' => 'person',      'href' => "$basePath/kelola_pengguna.php"],
        ['id' => 'laporan',   'label' => 'Laporan',         'icon' => 'assessment',  'href' => "$basePath/laporan.php"],
        ['id' => 'profil',    'label' => 'Profil Saya',     'icon' => 'manage_accounts', 'href' => $rootPath . 'pages/profil.php'],
    ],
    'guru' => [
        ['id' => 'dashboard',     'label' => 'Dashboard',       'icon' => 'dashboard',     'href' => "$basePath/dashboard.php"],
        ['id' => 'materi',        'label' => 'Upload Materi',   'icon' => 'book',           'href' => "$basePath/upload_materi.php"],
        ['id' => 'tugas',         'label' => 'Kelola Tugas',    'icon' => 'assignment',     'href' => "$basePath/kelola_tugas.php"],
        ['id' => 'nilai',         'label' => 'Input Nilai',     'icon' => 'grade',          'href' => "$basePath/input_nilai.php"],
        ['id' => 'absensi',       'label' => 'Absensi',         'icon' => 'how_to_reg',     'href' => "$basePath/absensi.php"],
        ['id' => 'laporan',       'label' => 'Laporan',         'icon' => 'description',    'href' => "$basePath/laporan.php"],
        ['id' => 'profil',        'label' => 'Profil Saya',     'icon' => 'manage_accounts', 'href' => $rootPath . 'pages/profil.php'],
    ],
    'siswa' => [
        ['id' => 'dashboard',     'label' => 'Dashboard',       'icon' => 'dashboard',     'href' => "$basePath/dashboard.php"],
        ['id' => 'materi',        'label' => 'Lihat Materi',    'icon' => 'book',           'href' => "$basePath/lihat_materi.php"],
        ['id' => 'tugas',         'label' => 'Kumpul Tugas',    'icon' => 'assignment',     'href' => "$basePath/kumpul_tugas.php"],
        ['id' => 'nilai',         'label' => 'Nilai',           'icon' => 'assessment',     'href' => "$basePath/lihat_nilai.php"],
        ['id' => 'profil',        'label' => 'Profil Saya',     'icon' => 'manage_accounts', 'href' => $rootPath . 'pages/profil.php'],
    ],
    default => [],
};
?>

<!-- Sidebar (Desktop) -->
<aside id="sidebar" class="fixed inset-y-0 left-0 z-40 hidden md:flex flex-col w-64 bg-surface border-r border-outline-variant mt-16 shadow-sm">
    <!-- Profile -->
    <div class="p-6 flex flex-col gap-1 border-b border-outline-variant bg-surface-container-low">
        <p class="font-title-sm text-title-sm text-on-surface"><?= htmlspecialchars($_SESSION['nama']) ?></p>
        <p class="font-body-md text-body-md text-on-surface-variant"><?= $roleLabel ?></p>
    </div>

    <!-- Nav Items -->
    <nav class="flex-1 py-4 overflow-y-auto no-scrollbar">
        <?php foreach ($navItems as $item): ?>
            <?php $isActive = ($currentPage === $item['id']); ?>
            <a class="flex items-center gap-3 px-4 py-2.5 mx-2 my-0.5 rounded-lg transition-all duration-200 <?= $isActive
                ? 'nav-item-active bg-primary-container/20 text-on-surface font-semibold shadow-sm'
                : 'nav-item text-on-surface-variant hover:bg-surface-container-high' ?>"
               href="<?= $item['href'] ?>">
                <span class="nav-icon-bg">
                    <span class="material-symbols-outlined text-[20px] <?= $isActive ? 'text-primary' : '' ?>"><?= $item['icon'] ?></span>
                </span>
                <span class="font-body-md text-body-md"><?= $item['label'] ?></span>
            </a>
        <?php endforeach; ?>
    </nav>

    <!-- Logout -->
    <div class="p-4 border-t border-outline-variant">
        <a href="<?= $logoutPath ?>"
           class="flex items-center gap-4 px-4 py-3 w-full text-error hover:bg-error-container/10 rounded transition-all">
            <span class="material-symbols-outlined">logout</span>
            <span class="font-body-md text-body-md font-semibold">Keluar</span>
        </a>
    </div>
</aside>

<!-- Mobile Sidebar Overlay -->
<div id="sidebarOverlay" class="fixed inset-0 z-30 bg-black/20 hidden md:hidden" onclick="toggleMobileSidebar()"></div>

<!-- Main Content Wrapper -->
<main class="flex-1 md:ml-64 p-margin-mobile md:p-margin-desktop pt-8 pb-24 md:pb-8 min-h-screen">
