<?php
session_start();

// Cek apakah pengguna sudah login atau belum
if (isset($_SESSION['user'])) {
    // Jika sudah login, arahkan ke dashboard sesuai role
    if ($_SESSION['user']['role'] === 'admin') {
        header('Location: dashboard_admin.php');
    } else {
        header('Location: dashboard_mahasiswa.php');
    }
    exit;
} else {
    // Jika belum login, paksa ke halaman login.php
    header('Location: login.php');
    exit;
}
?>