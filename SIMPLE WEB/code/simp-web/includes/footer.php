</main>
</div>

<!-- Bottom Nav (Mobile) -->
<nav class="md:hidden fixed bottom-0 w-full z-50 flex justify-around items-center h-16 bg-surface border-t border-outline-variant shadow-lg safe-bottom">
    <?php
    $mobileNav = match ($role) {
        'admin' => [
            ['icon' => 'dashboard',      'label' => 'Home',     'href' => '../pages/admin/dashboard.php',                'active' => $currentPage === 'dashboard'],
            ['icon' => 'school',         'label' => 'Kelas',    'href' => '../pages/admin/kelola_kelas.php',             'active' => $currentPage === 'kelas'],
            ['icon' => 'person',         'label' => 'Akun',     'href' => '../pages/admin/kelola_pengguna.php',          'active' => $currentPage === 'pengguna'],
            ['icon' => 'manage_accounts','label' => 'Profil',  'href' => $rootPath . 'pages/profil.php',                          'active' => $currentPage === 'profil'],
            ['icon' => 'logout',         'label' => 'Keluar',  'href' => $rootPath . 'auth/logout.php',                            'active' => false],
        ],
        'guru' => [
            ['icon' => 'dashboard',      'label' => 'Home',     'href' => '../pages/guru/dashboard.php',                 'active' => $currentPage === 'dashboard'],
            ['icon' => 'book',           'label' => 'Materi',   'href' => '../pages/guru/upload_materi.php',             'active' => $currentPage === 'materi'],
            ['icon' => 'assignment',     'label' => 'Tugas',    'href' => '../pages/guru/kelola_tugas.php',              'active' => $currentPage === 'tugas'],
            ['icon' => 'manage_accounts','label' => 'Profil',  'href' => $rootPath . 'pages/profil.php',                          'active' => $currentPage === 'profil'],
            ['icon' => 'logout',         'label' => 'Keluar',  'href' => $rootPath . 'auth/logout.php',                            'active' => false],
        ],
        'siswa' => [
            ['icon' => 'dashboard',      'label' => 'Home',     'href' => '../pages/siswa/dashboard.php',                'active' => $currentPage === 'dashboard'],
            ['icon' => 'book',           'label' => 'Materi',   'href' => '../pages/siswa/lihat_materi.php',            'active' => $currentPage === 'materi'],
            ['icon' => 'assignment',     'label' => 'Tugas',    'href' => '../pages/siswa/kumpul_tugas.php',            'active' => $currentPage === 'tugas'],
            ['icon' => 'manage_accounts','label' => 'Profil',  'href' => $rootPath . 'pages/profil.php',                          'active' => $currentPage === 'profil'],
            ['icon' => 'logout',         'label' => 'Keluar',  'href' => $rootPath . 'auth/logout.php',                            'active' => false],
        ],
        default => [],
    };
    ?>
    <?php foreach ($mobileNav as $item): ?>
        <a href="<?= $item['href'] ?>"
           class="flex flex-col items-center justify-center px-3 py-1 transition-all active:scale-90 <?= $item['active'] ? 'text-primary' : 'text-on-surface-variant' ?>">
            <span class="material-symbols-outlined <?= $item['active'] ? 'text-primary' : '' ?>"
                  style="<?= $item['active'] ? "font-variation-settings: 'FILL' 1;" : '' ?>"><?= $item['icon'] ?></span>
            <span class="font-label-sm text-[10px]"><?= $item['label'] ?></span>
        </a>
    <?php endforeach; ?>
</nav>

<script src="<?= $rootPath ?>assets/js/main.js"></script>
<script>
function toggleMobileSidebar() {
    const sidebar = document.getElementById('sidebar');
    const overlay = document.getElementById('sidebarOverlay');
    if (sidebar) {
        sidebar.classList.toggle('hidden');
        sidebar.classList.toggle('flex');
        if (overlay) overlay.classList.toggle('hidden');
    }
}

document.addEventListener('DOMContentLoaded', function () {
    const today = new Date();
    const options = { weekday: 'long', year: 'numeric', month: 'long', day: 'numeric' };
    const el = document.getElementById('current-date');
    if (el) el.innerText = today.toLocaleDateString('id-ID', options);
});
</script>
</body>
</html>
