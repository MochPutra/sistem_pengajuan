<?php
require_once 'includes/auth_check.php';
require_once 'config/db.php';
require_once 'includes/functions.php';

// Pastikan hanya mahasiswa yang bisa mengakses halaman ini
if (isAdmin()) {
    header('Location: dashboard_admin.php');
    exit;
}

$userId = $_SESSION['user']['id'];

// Mengambil statistik khusus untuk mahasiswa yang sedang login
$totalStmt = $pdo->prepare('SELECT COUNT(*) FROM pengajuan WHERE user_id = ?');
$pendingStmt = $pdo->prepare("SELECT COUNT(*) FROM pengajuan WHERE user_id = ? AND status = 'Draft'");
$approvedStmt = $pdo->prepare("SELECT COUNT(*) FROM pengajuan WHERE user_id = ? AND status = 'Approved'");
$cancelledStmt = $pdo->prepare("SELECT COUNT(*) FROM pengajuan WHERE user_id = ? AND status = 'Cancelled'");

$totalStmt->execute([$userId]);
$pendingStmt->execute([$userId]);
$approvedStmt->execute([$userId]);
$cancelledStmt->execute([$userId]);

$totalPengajuan = $totalStmt->fetchColumn();
$pendingPengajuan = $pendingStmt->fetchColumn();
$approvedPengajuan = $approvedStmt->fetchColumn();
$cancelledPengajuan = $cancelledStmt->fetchColumn();

// Mengambil 5 pengajuan terbaru untuk ditampilkan di tabel
$recentStmt = $pdo->prepare("SELECT * FROM pengajuan WHERE user_id = ? ORDER BY created_at DESC LIMIT 5");
$recentStmt->execute([$userId]);
$recentPengajuan = $recentStmt->fetchAll();
?>
<?php include 'includes/header.php'; ?>

<style>
    .dashboard-header {
        background: linear-gradient(135deg, #2b6aff 0%, #1e50cc 100%);
        color: white;
        border-radius: 1rem;
        padding: 2rem;
        margin-bottom: 2rem;
        box-shadow: 0 10px 25px rgba(43, 106, 255, 0.2);
    }
    
    .stat-card {
        border: none;
        border-radius: 1rem;
        transition: transform 0.3s ease, box-shadow 0.3s ease;
        overflow: hidden;
    }
    
    .stat-card:hover {
        transform: translateY(-5px);
        box-shadow: 0 10px 20px rgba(0,0,0,0.08) !important;
    }

    .icon-box {
        width: 54px;
        height: 54px;
        border-radius: 12px;
        display: flex;
        align-items: center;
        justify-content: center;
    }

    .bg-light-primary { background-color: #e0e7ff; color: #4f46e5; }
    .bg-light-warning { background-color: #fef3c7; color: #d97706; }
    .bg-light-success { background-color: #d1fae5; color: #059669; }
    .bg-light-danger  { background-color: #fee2e2; color: #dc2626; }

    .stat-value {
        font-size: 2rem;
        font-weight: 800;
        margin-bottom: 0;
        color: #2b3445;
    }

    .stat-label {
        font-size: 0.875rem;
        font-weight: 600;
        text-transform: uppercase;
        letter-spacing: 0.5px;
        color: #6b7280;
    }

    .quick-action-card {
        border: 1px solid #e5e7eb;
        border-radius: 0.75rem;
        transition: all 0.2s;
        cursor: pointer;
        text-decoration: none;
        color: inherit;
        display: block;
    }

    .quick-action-card:hover {
        border-color: #2b6aff;
        background-color: #f8faff;
        color: #2b6aff;
    }

    .status-badge {
        padding: 0.35em 0.8em;
        font-size: 0.75em;
        font-weight: 600;
        border-radius: 50rem;
    }
    .badge-draft { background-color: #fef3c7; color: #d97706; }
    .badge-approved { background-color: #d1fae5; color: #059669; }
    .badge-cancelled { background-color: #fee2e2; color: #dc2626; }
</style>

<div class="row g-4">
    <div class="col-12">
        <div class="dashboard-header d-flex justify-content-between align-items-center animate-fade-in">
            <div>
                <h2 class="fw-bold mb-2">Halo, <?= escape($_SESSION['user']['name']); ?>! 👋</h2>
                <p class="mb-0 opacity-75">Ini adalah ringkasan aktivitas persuratan akademik Anda.</p>
            </div>
            <a href="pengajuan_baru.php" class="btn btn-light text-primary fw-bold d-flex align-items-center gap-2 rounded-pill px-4 shadow-sm">
                <svg width="20" height="20" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M12 4v16m8-8H4"></path></svg>
                Buat Pengajuan Baru
            </a>
        </div>
    </div>

    <div class="col-12 animate-fade-in delay-1">
        <div class="row gy-4">
            <div class="col-xl-3 col-md-6">
                <div class="card stat-card shadow-sm h-100">
                    <div class="card-body p-4 d-flex justify-content-between align-items-center">
                        <div>
                            <p class="stat-label mb-1">Total Saya</p>
                            <h3 class="stat-value"><?= escape($totalPengajuan); ?></h3>
                        </div>
                        <div class="icon-box bg-light-primary">
                            <svg width="28" height="28" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z"></path></svg>
                        </div>
                    </div>
                </div>
            </div>

            <div class="col-xl-3 col-md-6">
                <div class="card stat-card shadow-sm h-100">
                    <div class="card-body p-4 d-flex justify-content-between align-items-center">
                        <div>
                            <p class="stat-label mb-1">Diproses</p>
                            <h3 class="stat-value"><?= escape($pendingPengajuan); ?></h3>
                        </div>
                        <div class="icon-box bg-light-warning">
                            <svg width="28" height="28" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M12 8v4l3 3m6-3a9 9 0 11-18 0 9 9 0 0118 0z"></path></svg>
                        </div>
                    </div>
                </div>
            </div>

            <div class="col-xl-3 col-md-6">
                <div class="card stat-card shadow-sm h-100">
                    <div class="card-body p-4 d-flex justify-content-between align-items-center">
                        <div>
                            <p class="stat-label mb-1">Selesai</p>
                            <h3 class="stat-value"><?= escape($approvedPengajuan); ?></h3>
                        </div>
                        <div class="icon-box bg-light-success">
                            <svg width="28" height="28" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z"></path></svg>
                        </div>
                    </div>
                </div>
            </div>

            <div class="col-xl-3 col-md-6">
                <div class="card stat-card shadow-sm h-100">
                    <div class="card-body p-4 d-flex justify-content-between align-items-center">
                        <div>
                            <p class="stat-label mb-1">Ditolak/Batal</p>
                            <h3 class="stat-value"><?= escape($cancelledPengajuan); ?></h3>
                        </div>
                        <div class="icon-box bg-light-danger">
                            <svg width="28" height="28" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M10 14l2-2m0 0l2-2m-2 2l-2-2m2 2l2 2m7-2a9 9 0 11-18 0 9 9 0 0118 0z"></path></svg>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <div class="col-lg-4 animate-fade-in delay-2">
        <div class="card shadow-sm border-0 rounded-4 h-100">
            <div class="card-body p-4">
                <h5 class="fw-bold mb-4">Layanan Cepat</h5>
                
                <a href="pengajuan_baru.php?jenis=aktif_kuliah" class="quick-action-card p-3 mb-3">
                    <div class="d-flex align-items-center gap-3">
                        <div class="bg-light p-2 rounded">
                            <svg width="24" height="24" fill="none" stroke="#2b6aff" stroke-width="2" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M12 14l9-5-9-5-9 5 9 5zm0 0l6.16-3.422a12.083 12.083 0 01.665 6.479A11.952 11.952 0 0012 20.055a11.952 11.952 0 00-6.824-2.998 12.078 12.078 0 01.665-6.479L12 14zm-4 6v-7.5l4-2.222"></path></svg>
                        </div>
                        <div>
                            <h6 class="mb-1 fw-bold">Keterangan Aktif Kuliah</h6>
                            <p class="mb-0 text-muted small">Untuk keperluan beasiswa/instansi</p>
                        </div>
                    </div>
                </a>

                <a href="pengajuan_baru.php?jenis=pengantar_magang" class="quick-action-card p-3 mb-3">
                    <div class="d-flex align-items-center gap-3">
                        <div class="bg-light p-2 rounded">
                            <svg width="24" height="24" fill="none" stroke="#d97706" stroke-width="2" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M21 13.255A23.931 23.931 0 0112 15c-3.183 0-6.22-.62-9-1.745M16 6V4a2 2 0 00-2-2h-4a2 2 0 00-2 2v2m4 6h.01M5 20h14a2 2 0 002-2V8a2 2 0 00-2-2H5a2 2 0 00-2 2v10a2 2 0 002 2z"></path></svg>
                        </div>
                        <div>
                            <h6 class="mb-1 fw-bold">Pengantar Magang / PKL</h6>
                            <p class="mb-0 text-muted small">Permohonan ke perusahaan/instansi</p>
                        </div>
                    </div>
                </a>

                <a href="pengajuan_baru.php?jenis=penelitian" class="quick-action-card p-3">
                    <div class="d-flex align-items-center gap-3">
                        <div class="bg-light p-2 rounded">
                            <svg width="24" height="24" fill="none" stroke="#059669" stroke-width="2" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z"></path></svg>
                        </div>
                        <div>
                            <h6 class="mb-1 fw-bold">Izin Penelitian / Riset</h6>
                            <p class="mb-0 text-muted small">Untuk pengambilan data tugas akhir</p>
                        </div>
                    </div>
                </a>
            </div>
        </div>
    </div>

    <div class="col-lg-8 animate-fade-in delay-3">
        <div class="card shadow-sm border-0 rounded-4 h-100">
            <div class="card-body p-4">
                <div class="d-flex justify-content-between align-items-center mb-4">
                    <h5 class="fw-bold mb-0">Riwayat Pengajuan Terbaru</h5>
                    <a href="riwayat_pengajuan.php" class="text-primary text-decoration-none small fw-bold">Lihat Semua</a>
                </div>

                <div class="table-responsive">
                    <table class="table table-hover align-middle">
                        <thead class="table-light text-muted small">
                            <tr>
                                <th>No. Tiket</th>
                                <th>Jenis Surat</th>
                                <th>Tanggal</th>
                                <th>Status</th>
                                <th class="text-end">Aksi</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php if (empty($recentPengajuan)): ?>
                                <tr>
                                    <td colspan="5" class="text-center py-4 text-muted">Belum ada riwayat pengajuan surat.</td>
                                </tr>
                            <?php else: ?>
                                <?php foreach ($recentPengajuan as $row): ?>
                                    <tr>
                                        <td class="fw-bold text-secondary">#<?= escape($row['id']); ?></td>
                                        <td><?= escape($row['jenis_surat']); ?></td>
                                        <td><?= date('d M Y', strtotime($row['created_at'])); ?></td>
                                        <td>
                                            <?php 
                                                $statusClass = 'badge-draft';
                                                $statusText = 'Diproses';
                                                if ($row['status'] == 'Approved') { $statusClass = 'badge-approved'; $statusText = 'Selesai'; }
                                                if ($row['status'] == 'Cancelled') { $statusClass = 'badge-cancelled'; $statusText = 'Dibatalkan'; }
                                            ?>
                                            <span class="status-badge <?= $statusClass; ?>"><?= $statusText; ?></span>
                                        </td>
                                        <td class="text-end">
                                            <a href="detail_pengajuan.php?id=<?= $row['id']; ?>" class="btn btn-sm btn-outline-primary rounded-pill px-3">Detail</a>
                                        </td>
                                    </tr>
                                <?php endforeach; ?>
                            <?php endif; ?>
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
    </div>
</div>

<?php include 'includes/footer.php'; ?>