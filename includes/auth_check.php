<?php
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

// Jika tidak ada data session 'user', tendang kembali ke login.php
if (!isset($_SESSION['user'])) {
    header('Location: login.php');
    exit;
}
?>