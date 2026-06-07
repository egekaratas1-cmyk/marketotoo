<?php
// ============================================
// MÜŞTERİ KAYIT SAYFASI
// ============================================
?>
<!DOCTYPE html>
<html lang="tr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta name="description" content="MarketOto'ya telefon numaranızla hızlıca kaydolun ve fırsatları kaçırmayın.">
    <title>Müşteri Kayıt | MarketOto</title>
    <link rel="stylesheet" href="assets/css/style.css">
</head>
<body data-page="register">

<div class="bg-particles">
    <div class="particle"></div><div class="particle"></div><div class="particle"></div>
    <div class="particle"></div><div class="particle"></div><div class="particle"></div>
    <div class="particle"></div><div class="particle"></div>
</div>

<nav class="navbar">
    <div class="navbar-inner">
        <a href="index.php" class="navbar-logo">
            <span class="logo-icon">🛒</span>
            MarketOto
        </a>
        <button class="navbar-toggle" id="navbar-toggle">☰</button>
        <ul class="navbar-menu" id="navbar-menu">
            <li><a href="index.php"><span class="nav-icon">🏠</span> Ana Sayfa</a></li>
            <li><a href="basket.php"><span class="nav-icon">🛒</span> Akıllı Sepet <span id="basket-badge" style="background:var(--gradient-danger);color:#fff;font-size:0.7rem;padding:2px 7px;border-radius:var(--radius-full);display:none;">0</span></a></li>
            <li><a href="register.php" class="active"><span class="nav-icon">👤</span> Kayıt Ol</a></li>
            <li><a href="admin_login.php"><span class="nav-icon">🔐</span> Admin</a></li>
        </ul>
    </div>
</nav>

<main class="main-content">
    <div class="register-layout">
        <!-- SOL: İLLÜSTRASYON -->
        <div class="register-illustration">
            <div class="big-emoji">🛒</div>
            <h2>Akıllı Alışverişe<br>Hoş Geldiniz!</h2>
            <p>Telefon numaranızla kayıt olun, size özel fırsatları, indirimli sepetleri ve SKT uyarılarını takip edin.</p>
            <div style="margin-top:2rem;display:flex;flex-direction:column;gap:1rem;align-items:flex-start;">
                <div style="display:flex;align-items:center;gap:10px;color:var(--primary-400);font-weight:500;">
                    <span style="background:rgba(16,185,129,0.15);padding:8px;border-radius:var(--radius-sm);font-size:1.2rem;">💰</span>
                    En ucuz fiyat karşılaştırması
                </div>
                <div style="display:flex;align-items:center;gap:10px;color:var(--accent-400);font-weight:500;">
                    <span style="background:rgba(245,158,11,0.15);padding:8px;border-radius:var(--radius-sm);font-size:1.2rem;">📅</span>
                    SKT uyarıları ve fırsatlar
                </div>
                <div style="display:flex;align-items:center;gap:10px;color:#60a5fa;font-weight:500;">
                    <span style="background:rgba(59,130,246,0.15);padding:8px;border-radius:var(--radius-sm);font-size:1.2rem;">🧠</span>
                    Akıllı sepet önerileri
                </div>
            </div>
        </div>

        <!-- SAĞ: KAYIT FORMU -->
        <div class="register-form-card">
            <h2>Müşteri Kayıt</h2>
            <p class="subtitle">Bilgilerinizi girerek hemen kaydolun</p>

            <form id="register-form">
                <div class="form-group">
                    <label for="isim">👤 Ad Soyad</label>
                    <input type="text" id="isim" name="isim" placeholder="Örn: Ahmet Yılmaz" required>
                </div>

                <div class="form-group">
                    <label for="telefon">📱 Telefon Numarası</label>
                    <input type="tel" id="telefon" name="telefon" placeholder="05XXXXXXXXX" maxlength="11" required>
                    <div class="input-hint">05 ile başlayan 11 haneli numaranızı girin</div>
                </div>

                <div class="form-group">
                    <label for="email">📧 E-posta <span style="color:var(--neutral-600);">(Opsiyonel)</span></label>
                    <input type="email" id="email" name="email" placeholder="ornek@email.com">
                </div>

                <button type="submit" class="btn btn-primary btn-lg" style="width:100%;margin-top:0.5rem;">
                    🚀 Kayıt Ol
                </button>
            </form>
        </div>
    </div>
</main>

<footer class="footer">
    <p>© 2026 MarketOto — İsrafı azalt, tasarruf et.</p>
</footer>

<script src="assets/js/app.js"></script>
</body>
</html>
