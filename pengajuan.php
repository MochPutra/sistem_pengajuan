<?php
require_once 'includes/auth_check.php';
require_once 'config/db.php';
require_once 'includes/functions.php';

$userId = $_SESSION['user']['id'];
if (isAdmin()) {
    $stmt = $pdo->query('SELECT p.*, u.name AS employee, (SELECT COUNT(*) FROM pengajuan_files f WHERE f.pengajuan_id = p.id) AS file_count FROM pengajuan p JOIN users u ON p.user_id = u.id ORDER BY p.created_at DESC');
} else {
    $stmt = $pdo->prepare('SELECT p.*, u.name AS employee, (SELECT COUNT(*) FROM pengajuan_files f WHERE f.pengajuan_id = p.id) AS file_count FROM pengajuan p JOIN users u ON p.user_id = u.id WHERE p.user_id = ? ORDER BY p.created_at DESC');
    $stmt->execute([$userId]);
}
$submissions = $stmt->fetchAll();
?>
<?php include 'includes/header.php'; ?>
<div class="d-flex justify-content-between align-items-center mb-4">
    <div>
        <h2>Riwayat Pengajuan</h2>
        <p class="text-muted">Lihat daftar pengajuan dengan pencarian, filter, dan export data.</p>
    </div>
    <a href="pengajuan_add.php" class="btn btn-primary"><i class="bi bi-plus-circle me-1"></i> Buat Pengajuan</a>
</div>
<div class="card shadow-sm border-0">
    <div class="card-body">
        <div class="table-responsive">
            <table id="pengajuanTable" class="table table-bordered table-hover align-middle">
                <thead class="table-light">
                    <tr>
                        <th>No</th>
                        <th>Judul</th>
                        <th>Pegawai</th>
                        <th>Status</th>
                        <th>Tanggal</th>
                        <th>Files</th>
                        <th>Aksi</th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ($submissions as $index => $row): ?>
                        <tr>
                            <td><?= $index + 1; ?></td>
                            <td><?= escape($row['title']); ?></td>
                            <td><?= escape($row['employee']); ?></td>
                            <td><?= escape($row['status']); ?></td>
                            <td><?= escape($row['created_at']); ?></td>
                            <td><?= escape($row['file_count']); ?></td>
                            <td>
                                <button type="button" class="btn btn-sm btn-info view-details" 
                                    data-bs-toggle="modal" data-bs-target="#detailModal"
                                    data-title="<?= escape($row['title']); ?>"
                                    data-employee="<?= escape($row['employee']); ?>"
                                    data-status="<?= escape($row['status']); ?>"
                                    data-created="<?= escape($row['created_at']); ?>"
                                    data-desc="<?= escape($row['description']); ?>"
                                    data-files-count="<?= escape($row['file_count']); ?>"
                                    data-attachment-query="<?= $row['id']; ?>"
                                    data-signature="<?= escape($row['signature_data']); ?>"
                                >Detail</button>
                                <?php if (isAdmin() || $row['user_id'] === $userId): ?>
                                    <a href="pengajuan_edit.php?id=<?= $row['id']; ?>" class="btn btn-sm btn-warning">Edit</a>
                                    <button type="button" class="btn btn-sm btn-danger btn-delete" data-bs-toggle="modal" data-bs-target="#deleteModal" data-id="<?= $row['id']; ?>">Hapus</button>
                                <?php endif; ?>
                            </td>
                        </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        </div>
    </div>
</div>

<!-- Detail modal -->
<div class="modal fade" id="detailModal" tabindex="-1" aria-labelledby="detailModalLabel" aria-hidden="true">
    <div class="modal-dialog modal-lg modal-dialog-scrollable">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title" id="detailModalLabel">Detail Pengajuan</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
            </div>
            <div class="modal-body">
                <div class="mb-3"><strong>Judul:</strong> <span id="detailTitle"></span></div>
                <div class="mb-3"><strong>Pegawai:</strong> <span id="detailEmployee"></span></div>
                <div class="mb-3"><strong>Status:</strong> <span id="detailStatus"></span></div>
                <div class="mb-3"><strong>Tanggal:</strong> <span id="detailCreated"></span></div>
                <div class="mb-3"><strong>Deskripsi:</strong><p id="detailDesc" class="mb-0"></p></div>
                <div class="mb-3"><strong>Jumlah Lampiran:</strong> <span id="detailFilesCount"></span></div>
                <div class="mb-3"><strong>Signature:</strong><div id="detailSignature" class="border rounded p-2 bg-light"></div></div>
                <div class="mb-3">
                    <strong>File Lampiran:</strong>
                    <div id="detailAttachments"></div>
                </div>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Tutup</button>
            </div>
        </div>
    </div>
</div>

<!-- Delete confirmation modal -->
<div class="modal fade" id="deleteModal" tabindex="-1" aria-labelledby="deleteModalLabel" aria-hidden="true">
    <div class="modal-dialog">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title" id="deleteModalLabel">Konfirmasi Hapus</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
            </div>
            <div class="modal-body">
                Apakah Anda yakin ingin menghapus pengajuan ini? Tindakan ini tidak dapat dikembalikan.
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Batal</button>
                <a href="#" id="confirmDeleteBtn" class="btn btn-danger">Hapus</a>
            </div>
        </div>
    </div>
</div>

<?php include 'includes/footer.php'; ?>
