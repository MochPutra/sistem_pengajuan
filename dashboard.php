<?php
require_once 'includes/auth_check.php';
require_once 'config/db.php';
require_once 'includes/functions.php';

$userId = $_SESSION['user']['id'];
if (isAdmin()) {
    $totalStmt = $pdo->query('SELECT COUNT(*) FROM pengajuan');
    $pendingStmt = $pdo->query("SELECT COUNT(*) FROM pengajuan WHERE status = 'Draft'");
    $approvedStmt = $pdo->query("SELECT COUNT(*) FROM pengajuan WHERE status = 'Approved'");
    $cancelledStmt = $pdo->query("SELECT COUNT(*) FROM pengajuan WHERE status = 'Cancelled'");
} else {
    $totalStmt = $pdo->prepare('SELECT COUNT(*) FROM pengajuan WHERE user_id = ?');
    $pendingStmt = $pdo->prepare("SELECT COUNT(*) FROM pengajuan WHERE user_id = ? AND status = 'Draft'");
    $approvedStmt = $pdo->prepare("SELECT COUNT(*) FROM pengajuan WHERE user_id = ? AND status = 'Approved'");
    $cancelledStmt = $pdo->prepare("SELECT COUNT(*) FROM pengajuan WHERE user_id = ? AND status = 'Cancelled'");
    $totalStmt->execute([$userId]);
    $pendingStmt->execute([$userId]);
    $approvedStmt->execute([$userId]);
    $cancelledStmt->execute([$userId]);
}

$totalPengajuan = $totalStmt->fetchColumn();
$pendingPengajuan = $pendingStmt->fetchColumn();
$approvedPengajuan = $approvedStmt->fetchColumn();
$cancelledPengajuan = $cancelledStmt->fetchColumn();
?>
<?php include 'includes/header.php'; ?>
<div class="row g-4">
    <div class="col-12">
        <div class="bg-white rounded-4 shadow-sm p-4 animate-fade-in">
            <div class="d-flex justify-content-between align-items-center">
                <div>
                    <h2>Dashboard</h2>
                    <p class="text-muted">Selamat datang, <?= escape($_SESSION['user']['name']); ?>. Gunakan menu untuk membuat dan melihat pengajuan dokumen/cuti.</p>
                </div>
                <button id="playAudioBtn" class="btn btn-outline-primary">Putar Audio Ringan</button>
            </div>
            <div class="row mt-4 gy-3">
                <div class="col-md-3">
                    <div class="card border-primary h-100 shadow-sm">
                        <div class="card-body">
                            <h6>Total Pengajuan</h6>
                            <h3><?= escape($totalPengajuan); ?></h3>
                        </div>
                    </div>
                </div>
                <div class="col-md-3">
                    <div class="card border-warning h-100 shadow-sm">
                        <div class="card-body">
                            <h6>Draft / Menunggu</h6>
                            <h3><?= escape($pendingPengajuan); ?></h3>
                        </div>
                    </div>
                </div>
                <div class="col-md-3">
                    <div class="card border-success h-100 shadow-sm">
                        <div class="card-body">
                            <h6>Disetujui</h6>
                            <h3><?= escape($approvedPengajuan); ?></h3>
                        </div>
                    </div>
                </div>
                <div class="col-md-3">
                    <div class="card border-danger h-100 shadow-sm">
                        <div class="card-body">
                            <h6>Dibatalkan</h6>
                            <h3><?= escape($cancelledPengajuan); ?></h3>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
    <div class="col-lg-8">
        <div class="card shadow-sm border-0 animate-fade-in delay-1">
            <div class="card-body">
                <h5>Video Tutorial</h5>
                <p class="text-muted">Tonton video singkat ini untuk memahami alur pembuatan pengajuan dan penggunaan tanda tangan digital.</p>
                <video controls class="w-100 rounded-3" style="max-height:380px;">
                    <source src="https://interactive-examples.mdn.mozilla.net/media/cc0-videos/flower.mp4" type="video/mp4">
                    Browser Anda tidak mendukung video HTML5.
                </video>
            </div>
        </div>
    </div>
    <div class="col-lg-4">
        <div class="card shadow-sm border-0 animate-fade-in delay-2">
            <div class="card-body">
                <h5>Catatan Sistem</h5>
                <ul class="list-group list-group-flush">
                    <li class="list-group-item">Gunakan tombol "Buat Pengajuan" untuk mengisi form.</li>
                    <li class="list-group-item">Unggah lampiran dokumen lebih dari satu file sekaligus.</li>
                    <li class="list-group-item">Tanda tangan digital akan otomatis tersimpan saat submit.</li>
                </ul>
            </div>
        </div>
    </div>
</div>
<?php include 'includes/footer.php'; ?>
