<?php

session_start();

if (isset($_SESSION['user_id'])) {
    $role = $_SESSION['role'];
    if ($role === 'admin') {
        header("Location: pages/admin/dashboard.php");
    } elseif ($role === 'guru') {
        header("Location: pages/guru/dashboard.php");
    } elseif ($role === 'siswa') {
        header("Location: pages/siswa/dashboard.php");
    } else {
        header("Location: auth/login.php");
    }
} else {
    header("Location: auth/login.php");
}
exit;
