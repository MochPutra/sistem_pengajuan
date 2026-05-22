<?php
require_once 'includes/auth_check.php';
require_once 'config/db.php';
require_once 'includes/functions.php';

header('Content-Type: application/json; charset=utf-8');
$id = isset($_GET['id']) ? (int) $_GET['id'] : 0;
$stmt = $pdo->prepare('SELECT * FROM pengajuan WHERE id = ? LIMIT 1');
$stmt->execute([$id]);
$pengajuan = $stmt->fetch();

if (!$pengajuan) {
    echo json_encode(['success' => false, 'message' => 'Pengajuan tidak ditemukan.']);
    exit;
}

if (!isAdmin() && $pengajuan['user_id'] !== $_SESSION['user']['id']) {
    echo json_encode(['success' => false, 'message' => 'Akses ditolak.']);
    exit;
}

$filesStmt = $pdo->prepare('SELECT * FROM pengajuan_files WHERE pengajuan_id = ?');
$filesStmt->execute([$id]);
$files = $filesStmt->fetchAll();

$attachments = [];
foreach ($files as $file) {
    $attachments[] = [
        'filename' => $file['filename'],
        'filepath' => $file['filepath'],
    ];
}

echo json_encode([
    'success' => true,
    'attachments' => $attachments,
    'signature' => $pengajuan['signature_data'],
]);
