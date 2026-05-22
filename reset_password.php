<?php
require_once 'config/db.php';

$credentials = [
    'admin@mail.test' => 'admin123',
    'pegawai@mail.test' => 'pegawai123',
];

$results = [];
foreach ($credentials as $email => $plainPassword) {
    $hashedPassword = password_hash($plainPassword, PASSWORD_BCRYPT);
    $stmt = $pdo->prepare('UPDATE users SET password = ? WHERE email = ?');
    $stmt->execute([$hashedPassword, $email]);
    $results[] = [
        'email' => $email,
        'updated' => $stmt->rowCount(),
        'password' => $plainPassword,
    ];
}
?>
<?php include 'includes/header.php'; ?>
<div class="container mt-4">
    <div class="card shadow-sm border-0">
        <div class="card-body">
            <h3 class="card-title mb-3">Reset Password (Sementara)</h3>
            <p class="text-muted">Password akun akan di-reset ke nilai default berikut:</p>
            <ul>
                <li>admin@mail.test &rarr; admin123</li>
                <li>pegawai@mail.test &rarr; pegawai123</li>
            </ul>
            <?php foreach ($results as $result): ?>
                <div class="alert alert-<?php echo $result['updated'] ? 'success' : 'warning'; ?>">
                    <?= htmlspecialchars($result['email']); ?>: 
                    <?= $result['updated'] ? 'Berhasil di-reset.' : 'Tidak ditemukan akun atau tidak ada perubahan.'; ?>
                </div>
            <?php endforeach; ?>
            <a href="login.php" class="btn btn-primary">Kembali ke Login</a>
        </div>
    </div>
</div>
<?php include 'includes/footer.php'; ?>
