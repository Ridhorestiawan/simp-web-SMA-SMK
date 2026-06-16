<?php

ini_set('session.cookie_httponly', 1);
ini_set('session.cookie_samesite', 'Lax');
ini_set('session.use_strict_mode', 1);
session_start();
require_once __DIR__ . '/functions.php';

$baseDir = '/rpl/code/simp-web';
$currentDir = dirname($_SERVER['SCRIPT_NAME']);
$relativeDir = substr($currentDir, strlen($baseDir));
$depth = $relativeDir ? substr_count($relativeDir, '/') : 0;
$rootPath = $depth > 0 ? str_repeat('../', $depth) : './';

if (!isset($_SESSION['user_id'])) {
    header("Location: " . $rootPath . "auth/login.php");
    exit;
}

$role   = $_SESSION['role'];
$nama   = $_SESSION['nama'];
$email  = $_SESSION['email'] ?? '';

$pageDir = dirname($_SERVER['SCRIPT_NAME']);
$roleMap = ['/admin' => 'admin', '/guru' => 'guru', '/siswa' => 'siswa'];
$requiredRole = null;
foreach ($roleMap as $suffix => $r) {
    if (str_ends_with($pageDir, $suffix)) {
        $requiredRole = $r;
        break;
    }
}
if ($requiredRole !== null && $role !== $requiredRole) {
    $dashboard = match ($role) {
        'admin' => $rootPath . 'pages/admin/dashboard.php',
        'guru'  => $rootPath . 'pages/guru/dashboard.php',
        'siswa' => $rootPath . 'pages/siswa/dashboard.php',
        default => $rootPath . 'auth/login.php',
    };
    header("Location: $dashboard");
    exit;
}

$title = $title ?? 'SIMP Web';

$roleLabel = match ($role) {
    'admin' => 'Administrator',
    'guru'  => 'Guru',
    'siswa' => 'Siswa',
    default => 'Pengguna',
};
$brandLink = match ($role) {
    'admin' => $rootPath . 'pages/admin/dashboard.php',
    'guru'  => $rootPath . 'pages/guru/dashboard.php',
    'siswa' => $rootPath . 'pages/siswa/dashboard.php',
    default => $rootPath . 'auth/login.php',
};
?>
<!DOCTYPE html>
<html lang="id">
<head>
<meta charset="utf-8"/>
<meta content="width=device-width, initial-scale=1.0" name="viewport"/>
<title><?= htmlspecialchars($title) ?> - SIMP Web</title>
<script src="https://cdn.tailwindcss.com?plugins=forms,container-queries"></script>
<link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700&display=swap" rel="stylesheet"/>
<link href="https://fonts.googleapis.com/css2?family=Material+Symbols+Outlined:wght,FILL@100..700,0..1&display=swap" rel="stylesheet"/>
<script>
tailwind.config = {
    darkMode: "class",
    theme: {
        extend: {
            colors: <?php include __DIR__ . '/colors.json'; ?>,
            borderRadius: {
                sm: "0.125rem",
                DEFAULT: "0.25rem",
                md: "0.375rem",
                lg: "0.5rem",
                xl: "0.75rem",
                full: "9999px",
            },
            spacing: {
                "unit": "4px",
                "container-max-width": "1440px",
                "margin-mobile": "16px",
                "gutter": "24px",
                "margin-desktop": "32px",
            },
            fontFamily: {
                "display-lg": ["Inter"],
                "headline-md": ["Inter"],
                "body-lg": ["Inter"],
                "body-md": ["Inter"],
                "title-sm": ["Inter"],
                "display-lg-mobile": ["Inter"],
                "code-snippet": ["jetbrainsMono"],
                "label-sm": ["Inter"],
            },
            fontSize: {
                "display-lg": ["32px", { lineHeight: "40px", letterSpacing: "-0.02em", fontWeight: "700" }],
                "headline-md": ["20px", { lineHeight: "28px", fontWeight: "600" }],
                "body-lg": ["16px", { lineHeight: "24px", fontWeight: "400" }],
                "body-md": ["14px", { lineHeight: "20px", fontWeight: "400" }],
                "title-sm": ["16px", { lineHeight: "24px", fontWeight: "600" }],
                "display-lg-mobile": ["24px", { lineHeight: "32px", letterSpacing: "-0.01em", fontWeight: "700" }],
                "code-snippet": ["13px", { lineHeight: "18px", fontWeight: "400" }],
                "label-sm": ["12px", { lineHeight: "16px", letterSpacing: "0.01em", fontWeight: "500" }],
            },
        },
    },
};
</script>
<style>
body { font-family: 'Inter', sans-serif; }
.material-symbols-outlined {
    font-variation-settings: 'FILL' 0, 'wght' 400, 'GRAD' 0, 'opsz' 24;
    vertical-align: middle;
}
.no-scrollbar::-webkit-scrollbar { display: none; }
.no-scrollbar { -ms-overflow-style: none; scrollbar-width: none; }
</style>
<link rel="stylesheet" href="<?= $rootPath ?>assets/css/style.css">
</head>
<body class="bg-background text-on-surface antialiased">

<!-- TopAppBar -->
<header class="fixed top-0 w-full z-50 flex justify-between items-center px-margin-mobile md:px-margin-desktop h-16 bg-surface header-gradient-border shadow-sm">
    <div class="flex items-center gap-4">
        <button class="md:hidden p-2 hover:bg-surface-container-high rounded-full transition-transform active:scale-95" onclick="toggleMobileSidebar()">
            <span class="material-symbols-outlined text-primary">menu</span>
        </button>
        <a href="<?= $brandLink ?>" class="hover:opacity-80 transition-opacity">
            <h1 class="font-headline-md text-headline-md font-bold text-primary">LMS SMA/SMK</h1>
        </a>
    </div>
    <div class="relative flex items-center gap-4">
        <div class="hidden md:flex flex-col items-end">
            <span class="font-label-sm text-label-sm text-on-surface"><?= htmlspecialchars($nama) ?></span>
            <span class="text-[10px] text-on-surface-variant uppercase tracking-wider"><?= $roleLabel ?></span>
        </div>
        <button id="avatarBtn" class="w-10 h-10 rounded-full overflow-hidden border border-outline-variant bg-primary-fixed flex items-center justify-center cursor-pointer hover:ring-2 hover:ring-primary transition-all" onclick="toggleDropdown()">
            <?php
            $avatarInitial = strtoupper(substr($nama, 0, 1));
            ?>
            <span class="material-symbols-outlined text-primary">person</span>
        </button>
        <!-- Dropdown -->
        <div id="avatarDropdown" class="absolute right-0 top-full mt-2 w-48 bg-surface-container-lowest rounded-lg shadow-lg border border-outline-variant py-2 hidden z-50">
            <div class="px-4 py-2 border-b border-outline-variant md:hidden">
                <p class="font-label-sm text-label-sm text-on-surface"><?= htmlspecialchars($nama) ?></p>
                <p class="text-[10px] text-on-surface-variant uppercase tracking-wider"><?= $roleLabel ?></p>
            </div>
            <a href="<?= $rootPath ?>pages/profil.php" class="flex items-center gap-3 px-4 py-2.5 text-on-surface-variant hover:bg-surface-container-low hover:text-on-surface transition-all font-body-md text-body-md">
                <span class="material-symbols-outlined text-lg">manage_accounts</span>
                Profil Saya
            </a>
            <a href="<?= $rootPath ?>auth/logout.php" class="flex items-center gap-3 px-4 py-2.5 text-error hover:bg-error-container/10 transition-all font-body-md text-body-md">
                <span class="material-symbols-outlined text-lg">logout</span>
                Keluar
            </a>
        </div>
    </div>
</header>

<script>
function toggleDropdown() {
    const dd = document.getElementById('avatarDropdown');
    dd.classList.toggle('hidden');
}
document.addEventListener('click', function(e) {
    const dd = document.getElementById('avatarDropdown');
    const btn = document.getElementById('avatarBtn');
    if (dd && !dd.classList.contains('hidden') && !dd.contains(e.target) && !btn.contains(e.target)) {
        dd.classList.add('hidden');
    }
});
</script>

<div class="flex pt-16 min-h-screen">
