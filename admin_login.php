<?php
// ============================================
// ADMİN GİRİŞ SAYFASI
// ============================================
require_once 'auth.php';

// Zaten giriş yapmışsa admin paneline yönlendir
if (isAdminLoggedIn()) {
    header('Location: admin.php');
    exit;
}

$error = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    require_once 'config.php';
    
    $kullanici = trim($_POST['kullanici_adi'] ?? '');
    $sifre = $_POST['sifre'] ?? '';
    
    if (empty($kullanici) || empty($sifre)) {
        $error = 'Kullanıcı adı ve şifre gereklidir.';
    } else {
        $db = getDB();
        $stmt = $db->prepare("SELECT * FROM admins WHERE kullanici_adi = :user LIMIT 1");
        $stmt->execute([':user' => $kullanici]);
        $admin = $stmt->fetch();
        
        if ($admin && password_verify($sifre, $admin['sifre'])) {
            $_SESSION['admin_id'] = $admin['id'];
            $_SESSION['admin_isim'] = $admin['isim'];
            $_SESSION['admin_kullanici'] = $admin['kullanici_adi'];
            header('Location: admin.php');
            exit;
        } else {
            $error = 'Kullanıcı adı veya şifre hatalı!';
        }
    }
}
?>
<!DOCTYPE html>
<html lang="tr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta name="description" content="MarketOto Yönetici Giriş Paneli">
    <title>Admin Giriş | MarketOto</title>
    <link rel="stylesheet" href="assets/css/style.css">
    <style>
        .login-container {
            display: flex;
            align-items: center;
            justify-content: center;
            min-height: calc(100vh - 70px);
            padding: 2rem;
        }
        .login-card {
            width: 100%;
            max-width: 440px;
            background: var(--surface-card);
            backdrop-filter: blur(20px);
            border: 1px solid var(--surface-glass-border);
            border-radius: var(--radius-xl);
            padding: 3rem 2.5rem;
            box-shadow: var(--shadow-xl);
        }
        .login-card .lock-icon {
            width: 70px;
            height: 70px;
            background: var(--gradient-accent);
            border-radius: 50%;
            display: flex;
            align-items: center;
            justify-content: center;
            font-size: 2rem;
            margin: 0 auto 1.5rem;
            box-shadow: var(--shadow-glow);
            animation: float 3s ease-in-out infinite;
        }
        .login-card h1 {
            font-family: var(--font-primary);
            font-weight: 800;
            font-size: 1.75rem;
            color: var(--neutral-100);
            text-align: center;
            margin-bottom: 0.5rem;
        }
        .login-card .subtitle {
            text-align: center;
            color: var(--neutral-500);
            font-size: 0.95rem;
            margin-bottom: 2rem;
        }
        .divider {
            display: flex;
            align-items: center;
            gap: 1rem;
            margin: 1.5rem 0;
            color: var(--neutral-600);
            font-size: 0.8rem;
        }
        .divider::before, .divider::after {
            content: '';
            flex: 1;
            height: 1px;
            background: var(--surface-glass-border);
        }
        .login-footer {
            text-align: center;
            margin-top: 1.5rem;
        }
        .login-footer a {
            color: var(--primary-400);
            font-size: 0.9rem;
            transition: var(--transition-base);
        }
        .login-footer a:hover {
            color: var(--primary-300);
            text-decoration: underline;
        }
        .password-wrapper {
            position: relative;
        }
        .password-wrapper .toggle-pass {
            position: absolute;
            right: 14px;
            top: 50%;
            transform: translateY(-50%);
            background: none;
            border: none;
            color: var(--neutral-500);
            cursor: pointer;
            font-size: 1.1rem;
            padding: 4px;
        }
        .password-wrapper .toggle-pass:hover {
            color: var(--neutral-300);
        }
    </style>
</head>
<body data-page="admin-login">

<div class="bg-particles">
    <div class="particle"></div><div class="particle"></div><div class="particle"></div>
    <div class="particle"></div><div class="particle"></div><div class="particle"></div>
    <div class="particle"></div><div class="particle"></div>
</div>

<!-- NAVBAR -->
<nav class="navbar">
    <div class="navbar-inner">
        <a href="index.php" class="navbar-logo">
            <span class="logo-icon">🛒</span>
            MarketOto
        </a>
        <ul class="navbar-menu" id="navbar-menu">
            <li><a href="index.php"><span class="nav-icon">🏠</span> Ana Sayfa</a></li>
            <li><a href="basket.php"><span class="nav-icon">🛒</span> Akıllı Sepet</a></li>
            <li><a href="register.php"><span class="nav-icon">👤</span> Kayıt Ol</a></li>
            <li><a href="admin_login.php" class="active"><span class="nav-icon">🔐</span> Admin</a></li>
        </ul>
    </div>
</nav>

<div class="login-container">
    <div class="login-card">
        <div class="lock-icon">🔐</div>
        <h1>Yönetici Girişi</h1>
        <p class="subtitle">MarketOto yönetim paneline erişmek için giriş yapın</p>

        <?php if ($error): ?>
            <div class="alert alert-error">
                ❌ <?= htmlspecialchars($error) ?>
            </div>
        <?php endif; ?>

        <form method="POST" action="" id="login-form">
            <div class="form-group">
                <label for="kullanici_adi">👤 Kullanıcı Adı</label>
                <input type="text" id="kullanici_adi" name="kullanici_adi" 
                       placeholder="Kullanıcı adınızı girin" 
                       value="<?= htmlspecialchars($_POST['kullanici_adi'] ?? '') ?>"
                       required autocomplete="username">
            </div>

            <div class="form-group">
                <label for="sifre">🔑 Şifre</label>
                <div class="password-wrapper">
                    <input type="password" id="sifre" name="sifre" 
                           placeholder="Şifrenizi girin" 
                           required autocomplete="current-password">
                    <button type="button" class="toggle-pass" onclick="togglePassword()">👁️</button>
                </div>
            </div>

            <button type="submit" class="btn btn-primary btn-lg" style="width:100%;margin-top:0.5rem;">
                🚀 Giriş Yap
            </button>
        </form>

        <div class="divider">veya</div>

        <div class="login-footer">
            <a href="index.php">← Ana Sayfaya Dön</a>
        </div>
    </div>
</div>

<script>
function togglePassword() {
    const input = document.getElementById('sifre');
    const btn = document.querySelector('.toggle-pass');
    if (input.type === 'password') {
        input.type = 'text';
        btn.textContent = '🙈';
    } else {
        input.type = 'password';
        btn.textContent = '👁️';
    }
}

// Focus first input
document.getElementById('kullanici_adi').focus();
</script>
</body>
</html>
