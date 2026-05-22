<?php
require_once 'includes/auth_check.php';
require_once 'config/db.php';
require_once 'includes/functions.php';

$id = isset($_GET['id']) ? (int) $_GET['id'] : 0;
$stmt = $pdo->prepare('SELECT * FROM pengajuan WHERE id = ? LIMIT 1');
$stmt->execute([$id]);
$pengajuan = $stmt->fetch();

if (!$pengajuan) {
    flash('Pengajuan tidak ditemukan.', 'danger');
    header('Location: pengajuan.php');
    exit;
}

if (!isAdmin() && $pengajuan['user_id'] !== $_SESSION['user']['id']) {
    flash('Anda tidak memiliki izin untuk menghapus pengajuan ini.', 'danger');
    header('Location: pengajuan.php');
    exit;
}

$filesStmt = $pdo->prepare('SELECT * FROM pengajuan_files WHERE pengajuan_id = ?');
$filesStmt->execute([$id]);
$files = $filesStmt->fetchAll();
foreach ($files as $file) {
    $path = __DIR__ . '/' . $file['filepath'];
    if (file_exists($path)) {
        @unlink($path);
    }
}

$pdo->prepare('DELETE FROM pengajuan_files WHERE pengajuan_id = ?')->execute([$id]);
$pdo->prepare('DELETE FROM pengajuan WHERE id = ?')->execute([$id]);
flash('Pengajuan berhasil dihapus.', 'success');
header('Location: pengajuan.php');
exit;
