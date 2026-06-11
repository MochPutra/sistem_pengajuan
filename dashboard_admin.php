<?php
require_once 'includes/auth_check.php';
require_once 'config/db.php';
require_once 'includes/functions.php';

// Jika yang mengakses bukan Admin, tendang ke dashboard mahasiswa
if (!isAdmin()) {
    header('Location: dashboard_mahasiswa.php');
    exit;
}

$userId = $_SESSION['user']['id'];

// ── Stat utama ──────────────────────────────────────────────────────────────
$totalPengajuan    = $pdo->query('SELECT COUNT(*) FROM pengajuan')->fetchColumn();
$pendingPengajuan  = $pdo->query("SELECT COUNT(*) FROM pengajuan WHERE status = 'Draft'")->fetchColumn();
$approvedPengajuan = $pdo->query("SELECT COUNT(*) FROM pengajuan WHERE status = 'Approved'")->fetchColumn();
$cancelledPengajuan= $pdo->query("SELECT COUNT(*) FROM pengajuan WHERE status = 'Cancelled'")->fetchColumn();

// ── Tren bulan ini vs bulan lalu ─────────────────────────────────────────────
$thisMonth = date('Y-m');
$lastMonth = date('Y-m', strtotime('-1 month'));

$trendThis = $pdo->query("SELECT COUNT(*) FROM pengajuan WHERE DATE_FORMAT(created_at,'%Y-%m') = '$thisMonth'")->fetchColumn();
$trendLast = $pdo->query("SELECT COUNT(*) FROM pengajuan WHERE DATE_FORMAT(created_at,'%Y-%m') = '$lastMonth'")->fetchColumn();
$trendPct  = $trendLast > 0 ? round((($trendThis - $trendLast) / $trendLast) * 100) : 0;

// ── Pengajuan Draft > 3 hari (notifikasi mendesak) ───────────────────────────
$urgentCount = $pdo->query("
    SELECT COUNT(*) FROM pengajuan
    WHERE status = 'Draft' AND created_at < NOW() - INTERVAL 3 DAY
")->fetchColumn();

// ── Chart: pengajuan per hari (7 hari terakhir) ──────────────────────────────
$chartStmt = $pdo->query("
    SELECT DATE(created_at) AS tgl, COUNT(*) AS jumlah
    FROM pengajuan
    WHERE created_at >= NOW() - INTERVAL 7 DAY
    GROUP BY DATE(created_at)
    ORDER BY tgl ASC
");
$chartData = $chartStmt->fetchAll(PDO::FETCH_ASSOC);

// ── Chart: distribusi status (donut) ─────────────────────────────────────────
$statusData = $pdo->query("
    SELECT status, COUNT(*) AS jumlah FROM pengajuan GROUP BY status
")->fetchAll(PDO::FETCH_ASSOC);

// ── Tabel pengajuan terbaru (10 terakhir) ────────────────────────────────────
$filter       = $_GET['filter'] ?? 'all';
$search       = trim($_GET['search'] ?? '');
$allowFilters = ['all', 'Draft', 'Approved', 'Cancelled'];
if (!in_array($filter, $allowFilters)) $filter = 'all';

$whereClause  = '';
$params       = [];

if ($filter !== 'all') {
    $whereClause .= ' AND p.status = ?';
    $params[]    = $filter;
}
if ($search !== '') {
    $whereClause .= ' AND (u.name LIKE ? OR p.id LIKE ?)';
    $params[]    = "%$search%";
    $params[]    = "%$search%";
}

$recentStmt = $pdo->prepare("    
    SELECT p.id, u.name AS mahasiswa, u.email AS user_email, p.jenis_surat, p.status, p.created_at
    FROM pengajuan p
    JOIN users u ON p.user_id = u.id
    WHERE 1=1 $whereClause
    ORDER BY p.created_at DESC
    LIMIT 10
");
$recentStmt->execute($params);
$recentList = $recentStmt->fetchAll(PDO::FETCH_ASSOC);

// ── Rata-rata waktu proses (hari) ────────────────────────────────────────────
$avgProcess = $pdo->query("
    SELECT ROUND(AVG(DATEDIFF(updated_at, created_at)), 1)
    FROM pengajuan
    WHERE status = 'Approved' AND updated_at IS NOT NULL
")->fetchColumn() ?? 0;
?>
<?php include 'includes/header.php'; ?>

<!-- Chart.js CDN -->
<script src="https://cdn.jsdelivr.net/npm/chart.js@4.4.0/dist/chart.umd.min.js"></script>

<style>
/* ─── Layout ──────────────────────────────────────────────────────────────── */
.dashboard-wrap   { display: flex; flex-direction: column; gap: 1.5rem; }
.dashboard-header { background: #fff; border: 1px solid #e5e7eb; border-radius: 1rem; padding: 1.75rem 2rem; }
@media (prefers-color-scheme: dark) { .dashboard-header { background: #1f2937; border-color: #374151; } }

/* ─── Notifikasi banner ───────────────────────────────────────────────────── */
.notif-banner { display: flex; align-items: center; gap: .75rem;
    background: #fffbeb; border: 1px solid #fde68a; border-radius: .75rem; padding: .9rem 1.25rem; }
.notif-banner .notif-icon { color: #d97706; font-size: 1.25rem; flex-shrink: 0; }
.notif-banner .notif-text { font-size: .875rem; color: #92400e; flex: 1; }
.notif-banner .btn-sm { font-size: .75rem; font-weight: 600; color: #b45309;
    border: 1px solid #f59e0b; border-radius: .5rem; padding: .3rem .75rem;
    background: transparent; cursor: pointer; white-space: nowrap; }
.notif-banner .btn-sm:hover { background: #fef3c7; }

/* ─── Stat cards ──────────────────────────────────────────────────────────── */
.stat-grid  { display: grid; grid-template-columns: repeat(4, 1fr); gap: 1rem; }
.stat-card  { background: #fff; border: 1px solid #e5e7eb; border-radius: 1rem;
               padding: 1.25rem 1.5rem; transition: transform .25s, box-shadow .25s; }
.stat-card:hover { transform: translateY(-4px); box-shadow: 0 8px 24px rgba(0,0,0,.07); }
.stat-top   { display: flex; justify-content: space-between; align-items: flex-start; margin-bottom: .75rem; }
.icon-box   { width: 48px; height: 48px; border-radius: 12px;
               display: flex; align-items: center; justify-content: center; font-size: 1.25rem; }
.icon-blue  { background: #eff6ff; color: #2563eb; }
.icon-amber { background: #fffbeb; color: #d97706; }
.icon-green { background: #f0fdf4; color: #16a34a; }
.icon-red   { background: #fef2f2; color: #dc2626; }
.trend-badge { font-size: .7rem; font-weight: 700; padding: .2rem .55rem; border-radius: 99px; }
.trend-up    { background: #dcfce7; color: #15803d; }
.trend-down  { background: #fee2e2; color: #dc2626; }
.trend-flat  { background: #f3f4f6; color: #6b7280; }
.stat-value  { font-size: 2rem; font-weight: 800; color: #111827; margin-bottom: .1rem; }
.stat-label  { font-size: .8rem; font-weight: 600; text-transform: uppercase;
               letter-spacing: .4px; color: #6b7280; }

/* ─── Charts row ──────────────────────────────────────────────────────────── */
.charts-row { display: grid; grid-template-columns: 2fr 1fr; gap: 1rem; }
.chart-card  { background: #fff; border: 1px solid #e5e7eb; border-radius: 1rem; padding: 1.25rem 1.5rem; }
.chart-title { font-size: .875rem; font-weight: 700; color: #374151; margin-bottom: 1rem; display: flex; align-items: center; gap: .5rem; }
.chart-canvas-wrap { position: relative; height: 200px; }

/* ─── Quick actions ───────────────────────────────────────────────────────── */
.action-grid { display: grid; grid-template-columns: repeat(4, 1fr); gap: .75rem; }
.action-btn  { background: #fff; border: 1px solid #e5e7eb; border-radius: .875rem;
               padding: 1rem; text-align: center; cursor: pointer;
               transition: background .2s, box-shadow .2s; text-decoration: none; display: block; }
.action-btn:hover { background: #f9fafb; box-shadow: 0 4px 12px rgba(0,0,0,.06); }
.action-btn i    { font-size: 1.5rem; color: #4f46e5; margin-bottom: .5rem; display: block; }
.action-btn span { font-size: .8rem; color: #374151; font-weight: 500; }

/* ─── Table ───────────────────────────────────────────────────────────────── */
.table-card  { background: #fff; border: 1px solid #e5e7eb; border-radius: 1rem;
               padding: 1.25rem 1.5rem; overflow: hidden; }
.filter-bar  { display: flex; align-items: center; gap: .5rem; margin-bottom: 1rem; flex-wrap: wrap; }
.filter-bar .search-input {
    flex: 1; min-width: 200px; height: 38px; border: 1px solid #d1d5db;
    border-radius: .625rem; padding: 0 .875rem; font-size: .875rem; outline: none;
    color: #111827; background: #f9fafb; }
.filter-bar .search-input:focus { border-color: #6366f1; background: #fff; }
.filter-chip { height: 38px; padding: 0 .875rem; border: 1px solid #d1d5db;
    border-radius: .625rem; font-size: .8rem; font-weight: 600; background: #f9fafb;
    color: #374151; cursor: pointer; transition: all .15s; white-space: nowrap; }
.filter-chip:hover  { border-color: #6366f1; color: #6366f1; }
.filter-chip.active { background: #eef2ff; border-color: #6366f1; color: #4f46e5; }
.table-wrap  { overflow-x: auto; }
.data-table  { width: 100%; border-collapse: collapse; font-size: .875rem; }
.data-table th { font-size: .75rem; font-weight: 700; text-transform: uppercase;
    letter-spacing: .4px; color: #6b7280; padding: .75rem .5rem; border-bottom: 2px solid #f3f4f6;
    text-align: left; white-space: nowrap; }
.data-table td { padding: .75rem .5rem; border-bottom: 1px solid #f9fafb; vertical-align: middle; }
.data-table tr:last-child td { border-bottom: none; }
.data-table tr:hover td { background: #fafafa; }
.td-name     { font-weight: 600; color: #111827; }
.td-nim      { font-size: .75rem; color: #9ca3af; }
.td-date     { color: #6b7280; white-space: nowrap; }
.badge       { display: inline-block; font-size: .7rem; font-weight: 700;
    padding: .25rem .65rem; border-radius: 99px; }
.badge-draft     { background: #fef3c7; color: #92400e; }
.badge-approved  { background: #dcfce7; color: #166534; }
.badge-cancelled { background: #fee2e2; color: #991b1b; }
.badge-review    { background: #eff6ff; color: #1d4ed8; }

.empty-state { text-align: center; padding: 2.5rem; color: #9ca3af; font-size: .875rem; }

/* ─── Extra stat row ──────────────────────────────────────────────────────── */
.extra-row   { display: grid; grid-template-columns: 1fr 1fr; gap: 1rem; }
.extra-card  { background: #fff; border: 1px solid #e5e7eb; border-radius: 1rem; padding: 1.25rem 1.5rem; }

/* ─── Responsive ──────────────────────────────────────────────────────────── */
@media (max-width: 1024px) { .stat-grid { grid-template-columns: repeat(2,1fr); } .charts-row { grid-template-columns: 1fr; } .action-grid { grid-template-columns: repeat(2,1fr); } }
@media (max-width: 640px)  { .stat-grid { grid-template-columns: 1fr; } .extra-row { grid-template-columns: 1fr; } }
</style>

<div class="dashboard-wrap">

    <!-- ── Header ─────────────────────────────────────────────────────── -->
    <div class="dashboard-header shadow-sm">
        <div class="d-flex justify-content-between align-items-start flex-wrap gap-2">
            <div>
                <h2 class="fw-bold mb-1">Dashboard Admin</h2>
                <p class="text-muted mb-0">Selamat datang, <strong><?= escape($_SESSION['user']['name']); ?></strong>
                    &nbsp;·&nbsp; <?= date('l, d F Y'); ?></p>
            </div>
            <a href="export_pengajuan.php" class="btn btn-outline-secondary btn-sm d-flex align-items-center gap-1">
                <svg width="16" height="16" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" d="M12 10v6m0 0l-3-3m3 3l3-3M3 17V7a2 2 0 012-2h6l2 2h6a2 2 0 012 2v10a2 2 0 01-2 2H5a2 2 0 01-2-2z"/>
                </svg>
                Ekspor Data
            </a>
        </div>
    </div>

    <!-- ── Banner notifikasi ───────────────────────────────────────────── -->
    <?php if ($urgentCount > 0): ?>
    <div class="notif-banner">
        <svg class="notif-icon" width="22" height="22" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24">
            <path stroke-linecap="round" stroke-linejoin="round" d="M15 17h5l-1.405-1.405A2.032 2.032 0 0118 14.158V11a6.002 6.002 0 00-4-5.659V5a2 2 0 10-4 0v.341C7.67 6.165 6 8.388 6 11v3.159c0 .538-.214 1.055-.595 1.436L4 17h5m6 0v1a3 3 0 11-6 0v-1m6 0H9"/>
        </svg>
        <span class="notif-text">
            Terdapat <strong><?= $urgentCount; ?> pengajuan Draft</strong> yang belum ditinjau lebih dari 3 hari.
        </span>
        <a href="kelola_pengajuan.php?filter=Draft&urgent=1" class="btn-sm">Tinjau Sekarang →</a>
    </div>
    <?php endif; ?>

    <!-- ── Stat cards ──────────────────────────────────────────────────── -->
    <div class="stat-grid">
        <div class="stat-card">
            <div class="stat-top">
                <div class="icon-box icon-blue">
                    <svg width="24" height="24" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" d="M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z"/>
                    </svg>
                </div>
                <?php
                    $cls = $trendPct > 0 ? 'trend-up' : ($trendPct < 0 ? 'trend-down' : 'trend-flat');
                    $sym = $trendPct > 0 ? '▲' : ($trendPct < 0 ? '▼' : '–');
                ?>
                <span class="trend-badge <?= $cls; ?>"><?= $sym; ?> <?= abs($trendPct); ?>%</span>
            </div>
            <div class="stat-value"><?= escape($totalPengajuan); ?></div>
            <div class="stat-label">Total Pengajuan</div>
        </div>

        <div class="stat-card">
            <div class="stat-top">
                <div class="icon-box icon-amber">
                    <svg width="24" height="24" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" d="M12 8v4l3 3m6-3a9 9 0 11-18 0 9 9 0 0118 0z"/>
                    </svg>
                </div>
                <?php $urgentBadge = $urgentCount > 0 ? '<span class="trend-badge trend-down">' . $urgentCount . ' mendesak</span>' : ''; ?>
                <?= $urgentBadge; ?>
            </div>
            <div class="stat-value"><?= escape($pendingPengajuan); ?></div>
            <div class="stat-label">Menunggu (Draft)</div>
        </div>

        <div class="stat-card">
            <div class="stat-top">
                <div class="icon-box icon-green">
                    <svg width="24" height="24" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z"/>
                    </svg>
                </div>
            </div>
            <div class="stat-value"><?= escape($approvedPengajuan); ?></div>
            <div class="stat-label">Disetujui</div>
        </div>

        <div class="stat-card">
            <div class="stat-top">
                <div class="icon-box icon-red">
                    <svg width="24" height="24" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" d="M10 14l2-2m0 0l2-2m-2 2l-2-2m2 2l2 2m7-2a9 9 0 11-18 0 9 9 0 0118 0z"/>
                    </svg>
                </div>
            </div>
            <div class="stat-value"><?= escape($cancelledPengajuan); ?></div>
            <div class="stat-label">Dibatalkan</div>
        </div>
    </div>

    <!-- ── Charts ──────────────────────────────────────────────────────── -->
    <div class="charts-row">
        <div class="chart-card">
            <div class="chart-title">
                <svg width="16" height="16" fill="none" stroke="#6366f1" stroke-width="2" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" d="M7 12l3-3 3 3 4-4M8 21l4-4 4 4M3 4h18M4 4h16v12a1 1 0 01-1 1H5a1 1 0 01-1-1V4z"/>
                </svg>
                Pengajuan 7 Hari Terakhir
            </div>
            <div class="chart-canvas-wrap">
                <canvas id="lineChart"></canvas>
            </div>
        </div>

        <div class="chart-card">
            <div class="chart-title">
                <svg width="16" height="16" fill="none" stroke="#f59e0b" stroke-width="2" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" d="M11 3.055A9.001 9.001 0 1020.945 13H11V3.055z"/>
                    <path stroke-linecap="round" stroke-linejoin="round" d="M20.488 9H15V3.512A9.025 9.025 0 0120.488 9z"/>
                </svg>
                Distribusi Status
            </div>
            <div class="chart-canvas-wrap" style="height:180px;">
                <canvas id="donutChart"></canvas>
            </div>
        </div>
    </div>

    <!-- ── Extra stats row ─────────────────────────────────────────────── -->
    <div class="extra-row">
        <div class="extra-card d-flex align-items-center gap-3">
            <div class="icon-box icon-blue" style="flex-shrink:0;">
                <svg width="22" height="22" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" d="M13 10V3L4 14h7v7l9-11h-7z"/>
                </svg>
            </div>
            <div>
                <div class="stat-label">Rata-rata Waktu Proses</div>
                <div class="stat-value" style="font-size:1.6rem;"><?= $avgProcess; ?> <small style="font-size:1rem;font-weight:500;color:#6b7280;">hari</small></div>
            </div>
        </div>
        <div class="extra-card d-flex align-items-center gap-3">
            <div class="icon-box icon-green" style="flex-shrink:0;">
                <svg width="22" height="22" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" d="M9 19v-6a2 2 0 00-2-2H5a2 2 0 00-2 2v6a2 2 0 002 2h2a2 2 0 002-2zm0 0V9a2 2 0 012-2h2a2 2 0 012 2v10m-6 0a2 2 0 002 2h2a2 2 0 002-2m0 0V5a2 2 0 012-2h2a2 2 0 012 2v14a2 2 0 01-2 2h-2a2 2 0 01-2-2z"/>
                </svg>
            </div>
            <div>
                <div class="stat-label">Tingkat Persetujuan</div>
                <?php $rate = $totalPengajuan > 0 ? round(($approvedPengajuan / $totalPengajuan) * 100) : 0; ?>
                <div class="stat-value" style="font-size:1.6rem;"><?= $rate; ?><small style="font-size:1rem;font-weight:500;color:#6b7280;">%</small></div>
            </div>
        </div>
    </div>

    <!-- ── Quick Actions ───────────────────────────────────────────────── -->
    <div>
        <p class="stat-label mb-2">Aksi Cepat</p>
        <div class="action-grid">
            <a href="tambah_pengajuan.php" class="action-btn">
                <svg width="28" height="28" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24" style="color:#4f46e5;margin:0 auto .5rem;">
                    <path stroke-linecap="round" stroke-linejoin="round" d="M12 4v16m8-8H4"/>
                </svg>
                <span>Buat Pengajuan</span>
            </a>
            <a href="export_pengajuan.php" class="action-btn">
                <svg width="28" height="28" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24" style="color:#4f46e5;margin:0 auto .5rem;">
                    <path stroke-linecap="round" stroke-linejoin="round" d="M4 16v1a3 3 0 003 3h10a3 3 0 003-3v-1m-4-4l-4 4m0 0l-4-4m4 4V4"/>
                </svg>
                <span>Ekspor Excel/PDF</span>
            </a>
            <a href="kelola_pengguna.php" class="action-btn">
                <svg width="28" height="28" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24" style="color:#4f46e5;margin:0 auto .5rem;">
                    <path stroke-linecap="round" stroke-linejoin="round" d="M12 4.354a4 4 0 110 5.292M15 21H3v-1a6 6 0 0112 0v1zm0 0h6v-1a6 6 0 00-9-5.197M13 7a4 4 0 11-8 0 4 4 0 018 0z"/>
                </svg>
                <span>Kelola Pengguna</span>
            </a>
            <a href="laporan_bulanan.php" class="action-btn">
                <svg width="28" height="28" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24" style="color:#4f46e5;margin:0 auto .5rem;">
                    <path stroke-linecap="round" stroke-linejoin="round" d="M9 17v-2m3 2v-4m3 4v-6m2 10H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z"/>
                </svg>
                <span>Laporan Bulanan</span>
            </a>
        </div>
    </div>

    <!-- ── Tabel pengajuan terbaru ─────────────────────────────────────── -->
    <div class="table-card">
        <div class="chart-title">
            <svg width="16" height="16" fill="none" stroke="#6366f1" stroke-width="2" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" d="M4 6h16M4 10h16M4 14h16M4 18h16"/>
            </svg>
            Pengajuan Terbaru
        </div>

        <!-- Filter + Search -->
        <form method="GET" action="">
            <div class="filter-bar">
                <input type="text" name="search" class="search-input"
                    placeholder="Cari nama mahasiswa atau ID…"
                    value="<?= escape($search); ?>">
                <button type="submit" name="filter" value="all"
                    class="filter-chip <?= $filter === 'all' ? 'active' : ''; ?>">Semua</button>
                <button type="submit" name="filter" value="Draft"
                    class="filter-chip <?= $filter === 'Draft' ? 'active' : ''; ?>">Draft</button>
                <button type="submit" name="filter" value="Approved"
                    class="filter-chip <?= $filter === 'Approved' ? 'active' : ''; ?>">Approved</button>
                <button type="submit" name="filter" value="Cancelled"
                    class="filter-chip <?= $filter === 'Cancelled' ? 'active' : ''; ?>">Cancelled</button>
            </div>
        </form>

        <div class="table-wrap">
            <table class="data-table">
                <thead>
                    <tr>
                        <th>#</th>
                        <th>Mahasiswa</th>
                        <th>Jenis Dokumen</th>
                        <th>Tanggal</th>
                        <th>Status</th>
                        <th>Aksi</th>
                    </tr>
                </thead>
                <tbody>
                    <?php if (empty($recentList)): ?>
                    <tr><td colspan="6" class="empty-state">Tidak ada data yang cocok.</td></tr>
                    <?php else: foreach ($recentList as $i => $row): ?>
                    <tr>
                        <td style="color:#9ca3af;font-size:.8rem;"><?= $i + 1; ?></td>
                        <td>
                            <div class="td-name"><?= escape($row['mahasiswa']); ?></div>
                            <div class="td-nim"><?= escape($row['user_email'] ?? '–'); ?></div>
                        </td>
                        <td><?= escape($row['jenis_surat']); ?></td>
                        <td class="td-date"><?= date('d M Y', strtotime($row['created_at'])); ?></td>
                        <td>
                            <?php
                            $badgeClass = match($row['status']) {
                                'Approved'  => 'badge-approved',
                                'Cancelled' => 'badge-cancelled',
                                'Review'    => 'badge-review',
                                default     => 'badge-draft',
                            };
                            ?>
                            <span class="badge <?= $badgeClass; ?>"><?= escape($row['status']); ?></span>
                        </td>
                        <td>
                            <a href="detail_pengajuan.php?id=<?= (int)$row['id']; ?>"
                               class="btn btn-sm btn-outline-secondary py-1 px-2"
                               style="font-size:.75rem;">Detail</a>
                        </td>
                    </tr>
                    <?php endforeach; endif; ?>
                </tbody>
            </table>
        </div>

        <div class="mt-3 text-end">
            <a href="kelola_pengajuan.php" class="text-decoration-none"
               style="font-size:.8rem;color:#6366f1;font-weight:600;">
                Lihat semua pengajuan →
            </a>
        </div>
    </div>

</div><!-- /.dashboard-wrap -->

<!-- ── Chart.js inisialisasi ──────────────────────────────────────────── -->
<script>
(function () {
    // ── Data dari PHP ────────────────────────────────────────
    const chartRaw   = <?= json_encode($chartData); ?>;
    const statusRaw  = <?= json_encode($statusData); ?>;

    // ── Warna tema ────────────────────────────────────────────
    const isDark = window.matchMedia('(prefers-color-scheme: dark)').matches;
    const gridColor  = isDark ? 'rgba(255,255,255,.07)' : 'rgba(0,0,0,.06)';
    const labelColor = isDark ? '#9ca3af' : '#6b7280';

    Chart.defaults.font.family = "'Inter', 'Segoe UI', sans-serif";
    Chart.defaults.font.size   = 12;

    // ── Line chart ────────────────────────────────────────────
    const labels = chartRaw.map(r => {
        const d = new Date(r.tgl);
        return d.toLocaleDateString('id-ID', { weekday: 'short', day: 'numeric' });
    });
    const values = chartRaw.map(r => parseInt(r.jumlah));

    new Chart(document.getElementById('lineChart'), {
        type: 'line',
        data: {
            labels,
            datasets: [{
                label: 'Pengajuan',
                data: values,
                borderColor: '#6366f1',
                backgroundColor: 'rgba(99,102,241,.12)',
                borderWidth: 2.5,
                pointRadius: 4,
                pointBackgroundColor: '#6366f1',
                fill: true,
                tension: 0.4,
            }]
        },
        options: {
            responsive: true,
            maintainAspectRatio: false,
            plugins: { legend: { display: false } },
            scales: {
                x: { grid: { color: gridColor }, ticks: { color: labelColor } },
                y: { grid: { color: gridColor }, ticks: { color: labelColor, precision: 0 }, beginAtZero: true }
            }
        }
    });

    // ── Donut chart ───────────────────────────────────────────
    const statusColors = {
        'Draft':     '#fbbf24',
        'Approved':  '#34d399',
        'Cancelled': '#f87171',
        'Review':    '#60a5fa',
    };
    const dLabels = statusRaw.map(r => r.status);
    const dValues = statusRaw.map(r => parseInt(r.jumlah));
    const dColors = dLabels.map(l => statusColors[l] ?? '#d1d5db');

    new Chart(document.getElementById('donutChart'), {
        type: 'doughnut',
        data: {
            labels: dLabels,
            datasets: [{
                data: dValues,
                backgroundColor: dColors,
                borderWidth: 2,
                borderColor: isDark ? '#1f2937' : '#fff',
            }]
        },
        options: {
            responsive: true,
            maintainAspectRatio: false,
            cutout: '68%',
            plugins: {
                legend: {
                    position: 'right',
                    labels: { color: labelColor, boxWidth: 12, padding: 12, font: { size: 12 } }
                }
            }
        }
    });
})();
</script>

<?php include 'includes/footer.php'; ?>