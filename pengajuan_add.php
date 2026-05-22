<?php
require_once 'includes/auth_check.php';
require_once 'config/db.php';
require_once 'includes/functions.php';

$errors = [];
$title = '';
$description = '';
$signature = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $title = trim($_POST['title'] ?? '');
    $description = trim($_POST['description'] ?? '');
    $signature = $_POST['signature'] ?? '';

    if ($title === '') {
        $errors[] = 'Judul pengajuan harus diisi.';
    }
    if ($description === '') {
        $errors[] = 'Deskripsi pengajuan harus diisi.';
    }
    if ($signature === '') {
        $errors[] = 'Tanda tangan digital harus dibuat.';
    }

    if (empty($errors)) {
        $insert = $pdo->prepare('INSERT INTO pengajuan (user_id, title, description, signature_data, status, created_at) VALUES (?, ?, ?, ?, ?, NOW())');
        $insert->execute([$_SESSION['user']['id'], $title, $description, $signature, 'Draft']);
        $pengajuanId = $pdo->lastInsertId();

        if (!empty($_FILES['attachments']['name'][0])) {
            $allowedTypes = ['application/pdf', 'image/jpeg', 'image/jpg', 'image/png'];
            $uploadDir = __DIR__ . '/uploads/attachments/';
            $stmtFile = $pdo->prepare('INSERT INTO pengajuan_files (pengajuan_id, filename, filepath, mime_type, uploaded_at) VALUES (?, ?, ?, ?, NOW())');

            foreach ($_FILES['attachments']['error'] as $index => $error) {
                if ($error !== UPLOAD_ERR_OK) {
                    continue;
                }
                $tmpName = $_FILES['attachments']['tmp_name'][$index];
                $originalName = basename($_FILES['attachments']['name'][$index]);
                $mimeType = finfo_file(finfo_open(FILEINFO_MIME_TYPE), $tmpName);

                if (!in_array($mimeType, $allowedTypes, true)) {
                    continue;
                }

                $extension = pathinfo($originalName, PATHINFO_EXTENSION);
                $newName = uniqid('lampiran_', true) . '.' . $extension;
                $destination = $uploadDir . $newName;

                if (move_uploaded_file($tmpName, $destination)) {
                    $stmtFile->execute([$pengajuanId, $originalName, 'uploads/attachments/' . $newName, $mimeType]);
                }
            }
        }

        flash('Pengajuan berhasil dibuat dan disimpan sebagai Draft.', 'success');
        header('Location: pengajuan.php');
        exit;
    }
}
?>
<?php include 'includes/header.php'; ?>
<div class="row justify-content-center">
    <div class="col-lg-10">
        <div class="card shadow-sm border-0">
            <div class="card-body">
                <h4 class="card-title mb-4">Buat Pengajuan Baru</h4>
                <?php if ($errors): ?>
                    <div class="alert alert-danger">
                        <ul class="mb-0">
                            <?php foreach ($errors as $error): ?>
                                <li><?= escape($error); ?></li>
                            <?php endforeach; ?>
                        </ul>
                    </div>
                <?php endif; ?>
                <form method="post" enctype="multipart/form-data">
                    <div class="mb-3">
                        <label class="form-label">Judul Pengajuan</label>
                        <input type="text" name="title" class="form-control" value="<?= escape($title); ?>" required>
                    </div>
                    <div class="mb-3">
                        <label class="form-label">Deskripsi / Alasan</label>
                        <textarea name="description" class="form-control" rows="5" required><?= escape($description); ?></textarea>
                    </div>
                    <div class="mb-3">
                        <label class="form-label">Unggah Lampiran (PDF/JPG/PNG)</label>
                        <input type="file" name="attachments[]" class="form-control" multiple accept="application/pdf,image/jpeg,image/png">
                        <div class="form-text">Pilih lebih dari satu file jika diperlukan.</div>
                    </div>
                    <div class="mb-3">
                        <label class="form-label">Tanda Tangan Digital</label>
                        <div class="signature-wrapper border rounded p-2 bg-white">
                            <canvas id="signature-pad" width="800" height="250"></canvas>
                        </div>
                        <div class="mt-2">
                            <button type="button" class="btn btn-secondary btn-sm me-2" id="clear-signature">Bersihkan</button>
                            <span class="text-muted">Gambar tanda tangan Anda, lalu submit formulir.</span>
                        </div>
                    </div>
                    <input type="hidden" name="signature" id="signatureInput" value="<?= escape($signature); ?>">
                    <button type="submit" class="btn btn-primary">Simpan Pengajuan</button>
                    <a href="pengajuan.php" class="btn btn-outline-secondary">Kembali</a>
                </form>
            </div>
        </div>
    </div>
</div>
<?php include 'includes/footer.php'; ?>
