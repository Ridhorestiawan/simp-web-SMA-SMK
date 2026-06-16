<?php

ini_set('session.cookie_httponly', 1);
ini_set('session.cookie_samesite', 'Lax');
ini_set('session.use_strict_mode', 1);
session_start();
require_once __DIR__ . '/../includes/functions.php';

if (isset($_SESSION['user_id'])) {
    $role = $_SESSION['role'];
    $redirect = match ($role) {
        'admin' => '../pages/admin/dashboard.php',
        'guru'  => '../pages/guru/dashboard.php',
        'siswa' => '../pages/siswa/dashboard.php',
        default => 'login.php',
    };
    header("Location: $redirect");
    exit;
}

$error = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (!verify_csrf($_POST['_csrf_token'] ?? '')) {
        $error = 'Token CSRF tidak valid. Silakan refresh halaman.';
    } else {
    $email    = trim($_POST['email'] ?? '');
    $password = trim($_POST['password'] ?? '');

    if ($email === '' || $password === '') {
        $error = 'Email dan password harus diisi.';
    } else {
        require_once __DIR__ . '/../config/database.php';

        $stmt = $pdo->prepare("SELECT id, nama, email, password, role FROM users WHERE email = ?");
        $stmt->execute([$email]);
        $user = $stmt->fetch();

        if (!$user) {
            $error = 'Email tidak terdaftar.';
        } elseif (!password_verify($password, $user['password'])) {
            $error = 'Password salah.';
        } else {
            session_regenerate_id(true);
            $_SESSION['user_id'] = $user['id'];
            $_SESSION['nama']    = $user['nama'];
            $_SESSION['email']   = $user['email'];
            $_SESSION['role']    = $user['role'];

            $redirect = match ($user['role']) {
                'admin' => '../pages/admin/dashboard.php',
                'guru'  => '../pages/guru/dashboard.php',
                'siswa' => '../pages/siswa/dashboard.php',
                default => 'login.php',
            };
            header("Location: $redirect");
            exit;
        }
    }
    }
}
?>
<!DOCTYPE html>
<html lang="id">
<head>
<meta charset="utf-8"/>
<meta content="width=device-width, initial-scale=1.0" name="viewport"/>
<title>Login - SIMP Web</title>
<script src="https://cdn.tailwindcss.com?plugins=forms,container-queries"></script>
<link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700&display=swap" rel="stylesheet"/>
<link href="https://fonts.googleapis.com/css2?family=Material+Symbols+Outlined:wght,FILL@100..700,0..1&display=swap" rel="stylesheet"/>
<script>
tailwind.config = {
    darkMode: "class",
    theme: {
        extend: {
            colors: {
                "primary": "#005bbf",
                "primary-container": "#1a73e8",
                "on-primary": "#ffffff",
                "on-primary-container": "#ffffff",
                "surface": "#f8f9fd",
                "surface-dim": "#d9dade",
                "surface-bright": "#f8f9fd",
                "surface-container-lowest": "#ffffff",
                "surface-container-low": "#f2f3f7",
                "surface-container": "#edeef2",
                "surface-container-high": "#e7e8ec",
                "surface-container-highest": "#e1e2e6",
                "on-surface": "#191c1f",
                "on-surface-variant": "#414754",
                "inverse-surface": "#2e3134",
                "inverse-on-surface": "#eff1f5",
                "outline": "#727785",
                "outline-variant": "#c1c6d6",
                "error": "#ba1a1a",
                "on-error": "#ffffff",
                "error-container": "#ffdad6",
                "on-error-container": "#93000a",
                "inverse-primary": "#adc7ff",
                "primary-fixed": "#d8e2ff",
                "primary-fixed-dim": "#adc7ff",
                "on-primary-fixed": "#001a41",
                "on-primary-fixed-variant": "#004493",
                "secondary": "#5e5e62",
                "on-secondary": "#ffffff",
                "secondary-container": "#e3e2e6",
                "on-secondary-container": "#646468",
                "secondary-fixed": "#e3e2e6",
                "secondary-fixed-dim": "#c7c6ca",
                "on-secondary-fixed": "#1a1b1e",
                "on-secondary-fixed-variant": "#46474a",
                "tertiary": "#5a5e63",
                "on-tertiary": "#ffffff",
                "tertiary-container": "#73777c",
                "on-tertiary-container": "#ffffff",
                "tertiary-fixed": "#dfe3e8",
                "tertiary-fixed-dim": "#c3c7cc",
                "on-tertiary-fixed": "#181c20",
                "on-tertiary-fixed-variant": "#43474c",
                "background": "#f8f9fd",
                "surface-variant": "#e1e2e6",
                "surface-tint": "#005bc0",
            },
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
.material-symbols-outlined {
    font-variation-settings: 'FILL' 0, 'wght' 400, 'GRAD' 0, 'opsz' 24;
}
.custom-shadow {
    box-shadow: 0 2px 8px rgba(0, 0, 0, 0.08);
}
.split-bg {
    background: linear-gradient(135deg, #d8e2ff 0%, #f8f9fd 50%, #ffffff 100%);
}
</style>
</head>
<body class="bg-surface font-body-md text-on-surface antialiased">
<main class="min-h-screen split-bg flex flex-col items-center justify-center px-margin-mobile md:px-margin-desktop py-6">
    <!-- Decorative -->
    <div aria-hidden="true" class="fixed" style="width:320px;height:320px;background:radial-gradient(circle,rgba(0,91,191,0.12) 0%,transparent 70%);top:-80px;right:-80px;pointer-events:none"></div>
    <div aria-hidden="true" class="fixed" style="width:220px;height:220px;background:radial-gradient(circle,rgba(90,94,99,0.08) 0%,transparent 70%);bottom:-40px;left:-40px;pointer-events:none"></div>
    <div class="w-full max-w-[420px] bg-surface-container-lowest rounded-lg custom-shadow overflow-hidden p-6 md:p-8 flex flex-col items-center animate-scale-in">
        <!-- Logo -->
        <div class="mb-6 flex flex-col items-center">
            <div class="w-20 h-20 rounded-full stat-icon mb-4">
                <span class="material-symbols-outlined text-primary text-3xl">school</span>
            </div>
            <h1 class="font-headline-md text-headline-md text-primary text-center leading-tight">LMS SMA/SMK</h1>
            <p class="font-label-sm text-label-sm text-outline mt-2 tracking-wide uppercase">Sistem Informasi Manajemen Pembelajaran</p>
        </div>

        <!-- Error Message -->
        <?php if ($error): ?>
            <div class="w-full mb-4 px-4 py-3 bg-error-container border border-error rounded flex items-center gap-2">
                <span class="material-symbols-outlined text-error text-lg">error</span>
                <p class="font-body-md text-body-md text-on-error-container"><?= htmlspecialchars($error) ?></p>
            </div>
        <?php endif; ?>

        <!-- Form -->
        <form class="w-full space-y-6" method="POST" action="">
            <?= csrf_field() ?>
            <!-- Email -->
            <div class="space-y-1.5">
                <label class="block font-label-sm text-label-sm text-on-surface-variant" for="email">Email Institusi</label>
                <div class="relative">
                    <span class="material-symbols-outlined absolute left-3 top-1/2 -translate-y-1/2 text-outline text-lg">mail</span>
                    <input class="w-full pl-10 pr-4 py-3 bg-surface border border-outline-variant rounded focus:ring-2 focus:ring-primary focus:border-primary transition-all outline-none text-body-md"
                           id="email" name="email" placeholder="nama@sekolah.sch.id" required type="email"
                           value="<?= htmlspecialchars($_POST['email'] ?? '') ?>"/>
                </div>
            </div>
            <!-- Password -->
            <div class="space-y-1.5">
                <label class="block font-label-sm text-label-sm text-on-surface-variant" for="password">Kata Sandi</label>
                <div class="relative">
                    <span class="material-symbols-outlined absolute left-3 top-1/2 -translate-y-1/2 text-outline text-lg">lock</span>
                    <input class="w-full pl-10 pr-12 py-3 bg-surface border border-outline-variant rounded focus:ring-2 focus:ring-primary focus:border-primary transition-all outline-none text-body-md"
                           id="password" name="password" placeholder="••••••••" required type="password"/>
                    <button type="button" onclick="togglePassword()"
                            class="absolute right-3 top-1/2 -translate-y-1/2 text-outline hover:text-primary transition-colors p-1">
                        <span class="material-symbols-outlined text-lg" id="pw-icon">visibility</span>
                    </button>
                </div>
            </div>
            <!-- Submit -->
            <button class="w-full bg-primary text-on-primary font-title-sm text-title-sm py-4 rounded hover:bg-primary-container active:scale-[0.98] transition-all duration-150 shadow-sm flex items-center justify-center gap-2" type="submit">
                Masuk
                <span class="material-symbols-outlined">arrow_forward</span>
            </button>
        </form>

        <!-- Footer -->
        <div class="mt-8 pt-6 border-t border-outline-variant w-full text-center">
            <p class="font-label-sm text-label-sm text-outline">&copy; 2024 Manajemen Sekolah Terpadu. Hak Cipta Dilindungi.</p>
        </div>
    </div>
</main>
<script>
function togglePassword() {
    const pwInput = document.getElementById('password');
    const pwIcon  = document.getElementById('pw-icon');
    if (pwInput.type === 'password') {
        pwInput.type = 'text';
        pwIcon.textContent = 'visibility_off';
    } else {
        pwInput.type = 'password';
        pwIcon.textContent = 'visibility';
    }
}
</script>
</body>
</html>
