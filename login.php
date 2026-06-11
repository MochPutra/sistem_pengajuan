<?php
session_start();
require_once 'config/db.php';
require_once 'includes/functions.php';

// CEK 1: Jika user sudah login sebelumnya dan membuka halaman login lagi
if (isset($_SESSION['user'])) {
    if ($_SESSION['user']['role'] === 'Admin') {
        header('Location: dashboard_admin.php');
    } else {
        header('Location: dashboard_mahasiswa.php');
    }
    exit;
}

$error = '';
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $email = trim($_POST['email'] ?? '');
    $password = $_POST['password'] ?? '';

    if ($email === '' || $password === '') {
        $error = 'Email dan password harus diisi.';
    } else {
        $stmt = $pdo->prepare('SELECT * FROM users WHERE email = ? LIMIT 1');
        $stmt->execute([$email]);
        $user = $stmt->fetch();

        if ($user && password_verify($password, $user['password'])) {
            $_SESSION['user'] = [
                'id' => $user['id'],
                'name' => $user['name'],
                'role' => $user['role'],
                'email' => $user['email'],
            ];
            
            // CEK 2: Arahkan ke dashboard yang benar setelah berhasil login
            if ($user['role'] === 'Admin') {
                header('Location: dashboard_admin.php');
            } else {
                header('Location: dashboard_mahasiswa.php');
            }
            exit;
        }

        $error = 'Email atau password salah.';
    }
}
?>
<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Login - Sistem Pengajuan Surat</title>
    <style>
        /* Reset & General Setup */
        * {
            box-sizing: border-box;
        }
        body, html {
            margin: 0;
            padding: 0;
            height: 100%;
            font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;
            background-color: #ffffff;
            overflow-x: hidden;
        }
        
        .sso-wrapper {
            display: flex;
            min-height: 100vh;
            width: 100%;
        }

        /* Kiri: Area Login Form */
        .sso-left {
            flex: 0 0 45%;
            display: flex;
            flex-direction: column;
            justify-content: center;
            align-items: center;
            padding: 2rem;
            background-color: #ffffff;
        }

        .login-container {
            width: 100%;
            max-width: 380px;
        }

        .logo-container {
            text-align: center;
            margin-bottom: 30px;
        }

        .logo-container img {
            width: 84px;
            height: auto;
            display: block;
            margin: 0 auto 12px;
        }

        .sso-title {
            font-size: 28px;
            font-weight: 800;
            margin: 0 0 5px 0;
            color: #000;
        }

        .sso-subtitle {
            font-size: 13px;
            color: #666;
            margin-bottom: 25px;
            line-height: 1.5;
        }

        .btn-google {
            display: flex;
            align-items: center;
            justify-content: center;
            background-color: #d97706;
            color: white;
            border: none;
            border-radius: 6px;
            padding: 12px;
            width: 100%;
            font-weight: 600;
            font-size: 14px;
            cursor: pointer;
            margin-bottom: 25px;
            text-decoration: none;
            transition: background-color 0.2s;
        }
        
        .btn-google:hover {
            background-color: #b46204;
        }

        /* Form Styles */
        .form-group {
            margin-bottom: 18px;
            position: relative;
        }

        .form-label-wrapper {
            display: flex;
            justify-content: space-between;
            margin-bottom: 6px;
        }

        .form-label {
            font-size: 12px;
            font-weight: 600;
            color: #333;
        }

        .text-danger-star {
            color: #dc3545;
        }

        .forgot-password {
            font-size: 12px;
            color: #2b6aff;
            text-decoration: none;
        }

        .form-control-custom {
            width: 100%;
            padding: 10px 14px;
            border: 1px solid #d1d5db;
            border-radius: 6px;
            font-size: 14px;
            transition: border-color 0.2s;
        }

        .form-control-custom:focus {
            outline: none;
            border-color: #2b6aff;
            box-shadow: 0 0 0 3px rgba(43, 106, 255, 0.1);
        }

        .password-wrapper {
            position: relative;
        }

        .toggle-password {
            position: absolute;
            right: 12px;
            top: 50%;
            transform: translateY(-50%);
            color: #9ca3af;
            cursor: pointer;
        }

        .remember-me {
            display: flex;
            align-items: center;
            font-size: 13px;
            color: #4b5563;
            margin-bottom: 25px;
        }

        .remember-me input {
            margin-right: 8px;
            cursor: pointer;
        }

        .btn-login {
            background-color: #2b6aff;
            color: white;
            border: none;
            border-radius: 6px;
            padding: 12px;
            width: 100%;
            font-weight: 600;
            font-size: 15px;
            cursor: pointer;
            transition: background-color 0.2s;
        }

        .btn-login:hover {
            background-color: #1e50cc;
        }

        /* Kanan: Area Informasi */
        .sso-right {
            flex: 1;
            background-color: #3b82f6;
            background-image: linear-gradient(135deg, #3b82f6 0%, #2563eb 100%);
            color: white;
            display: flex;
            flex-direction: column;
            justify-content: center;
            align-items: center;
            padding: 40px;
        }

        .hero-title {
            font-size: 38px;
            font-weight: 700;
            margin: 0 0 8px 0;
            text-align: center;
            letter-spacing: -0.5px;
        }

        .hero-subtitle {
            font-size: 20px;
            font-weight: 400;
            margin: 0 0 15px 0;
            text-align: center;
        }

        .hero-desc {
            font-size: 15px;
            opacity: 0.9;
            margin-bottom: 50px;
            text-align: center;
            max-width: 80%;
            line-height: 1.6;
        }

        .stats-container {
            display: flex;
            gap: 60px;
            text-align: center;
        }

        .stat-item h4 {
            font-size: 32px;
            font-weight: 700;
            margin: 0 0 4px 0;
        }

        .stat-item p {
            font-size: 14px;
            margin: 0;
            opacity: 0.9;
        }

        /* Responsif untuk Mobile */
        @media (max-width: 992px) {
            body { height: auto; overflow-y: auto; }
            .sso-wrapper { flex-direction: column; height: auto; min-height: 100vh; }
            .sso-left, .sso-right { flex: none; width: 100%; }
            .sso-left { padding: 40px 20px; }
            .sso-right { padding: 60px 20px; }
        }
    </style>
</head>
<body>

<div class="sso-wrapper">
    <div class="sso-left">
        <div class="login-container">
            <div class="logo-container">
                <img src="assets/img/logo-ummi.png" alt="Logo UMMI" onerror="this.src='https://via.placeholder.com/84x84?text=UMMI'">
                <h1 class="sso-title">Sistem Pengajuan Surat</h1>
                <p class="sso-subtitle">Portal layanan administrasi persuratan akademik dan kemahasiswaan</p>
            </div>

            <button class="btn-google">
                <svg style="width:18px; height:18px; margin-right:10px;" viewBox="0 0 24 24">
                    <path fill="currentColor" d="M12.545,10.239v3.821h5.445c-0.712,2.315-2.647,3.972-5.445,3.972c-3.332,0-6.033-2.701-6.033-6.032s2.701-6.032,6.033-6.032c1.498,0,2.866,0.549,3.921,1.453l2.814-2.814C17.503,2.988,15.139,2,12.545,2C7.021,2,2.543,6.477,2.543,12s4.478,10,10.002,10c8.396,0,10.249-7.85,9.426-11.748L12.545,10.239z"/>
                </svg>
                Masuk dengan Google
            </button>

            <?php if ($error): ?>
                <div style="background-color: #fee2e2; color: #dc2626; padding: 10px; border-radius: 6px; margin-bottom: 20px; font-size: 14px; text-align: center;">
                    <?= htmlspecialchars($error, ENT_QUOTES, 'UTF-8'); ?>
                </div>
            <?php endif; ?>

            <form method="post" action="login.php">
                <div class="form-group">
                    <div class="form-label-wrapper">
                        <label class="form-label">Email / Username / NIM / NIDN / NIP<span class="text-danger-star">*</span></label>
                    </div>
                    <input type="text" name="email" class="form-control-custom" required>
                </div>

                <div class="form-group">
                    <div class="form-label-wrapper">
                        <label class="form-label">Kata sandi<span class="text-danger-star">*</span></label>
                        <a href="#" class="forgot-password">Lupa kata sandi?</a>
                    </div>
                    <div class="password-wrapper">
                        <input type="password" name="password" class="form-control-custom" required>
                        <span class="toggle-password">
                            <svg width="18" height="18" fill="currentColor" viewBox="0 0 24 24">
                                <path d="M12 4.5C7 4.5 2.73 7.61 1 12c1.73 4.39 6 7.5 11 7.5s9.27-3.11 11-7.5c-1.73-4.39-6-7.5-11-7.5zM12 17c-2.76 0-5-2.24-5-5s2.24-5 5-5 5 2.24 5 5-2.24 5-5 5zm0-8c-1.66 0-3 1.34-3 3s1.34 3 3 3 3-1.34 3-3-1.34-3-3-3z"/>
                            </svg>
                        </span>
                    </div>
                </div>

                <div class="remember-me">
                    <input type="checkbox" id="ingat" name="ingat">
                    <label for="ingat">Ingat saya</label>
                </div>

                <button type="submit" class="btn-login">Masuk ke Sistem</button>
            </form>
        </div>
    </div>

    <div class="sso-right">
        <h2 class="hero-title">Selamat Datang di Layanan Persuratan</h2>
        <h3 class="hero-subtitle">Universitas Muhammadiyah Sukabumi</h3>
        <p class="hero-desc">Platform terpadu untuk mempermudah proses pengajuan, pelacakan status, dan validasi dokumen administrasi Anda secara efisien dan transparan.</p>

        <div class="stats-container">
            <div class="stat-item">
                <h4>15+</h4>
                <p>Jenis Surat</p>
            </div>
            <div class="stat-item">
                <h4>12K+</h4>
                <p>Surat Terproses</p>
            </div>
            <div class="stat-item">
                <h4>100%</h4>
                <p>Paperless</p>
            </div>
        </div>
    </div>
</div>

</body>
</html>