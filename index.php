<?php
// ============================================
// ANA SAYFA - FIRSAT ÜRÜNLERİ
// ============================================
require_once 'config.php';

$db = getDB();

// Kategori listesi
$categories = $db->query("SELECT DISTINCT kategori FROM products ORDER BY kategori")->fetchAll(PDO::FETCH_COLUMN);

// İstatistikler
$stats = $db->query("
    SELECT 
        (SELECT COUNT(*) FROM inventory WHERE stok_miktari > 0) as toplam_urun,
        (SELECT COUNT(*) FROM inventory WHERE son_kullanma_tarihi <= DATE_ADD(CURDATE(), INTERVAL 7 DAY) AND stok_miktari > 0) as skt_yaklasan,
        (SELECT COUNT(*) FROM inventory WHERE indirimli_fiyat IS NOT NULL AND stok_miktari > 0) as indirimli,
        (SELECT COUNT(*) FROM markets) as market_sayisi
")->fetch();
?>
<!DOCTYPE html>
<html lang="tr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta name="description" content="MarketOto - Son kullanma tarihi yaklaşan ürünlerde en uygun fiyatları bulun, akıllı sepetinizi oluşturun.">
    <title>MarketOto | İsrafı Önle, Tasarruf Et</title>
    <link rel="stylesheet" href="assets/css/style.css">
</head>
<body data-page="index">

<!-- Background Particles -->
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
        <button class="navbar-toggle" id="navbar-toggle">☰</button>
        <ul class="navbar-menu" id="navbar-menu">
            <li><a href="index.php" class="active"><span class="nav-icon">🏠</span> Ana Sayfa</a></li>
            <li><a href="basket.php"><span class="nav-icon">🛒</span> Akıllı Sepet <span id="basket-badge" style="background:var(--gradient-danger);color:#fff;font-size:0.7rem;padding:2px 7px;border-radius:var(--radius-full);display:none;">0</span></a></li>
            <li><a href="register.php"><span class="nav-icon">👤</span> Kayıt Ol</a></li>
            <li><a href="admin_login.php"><span class="nav-icon">🔐</span> Admin</a></li>
        </ul>
    </div>
</nav>

<main class="main-content">
    <!-- HERO BANNER -->
    <div class="hero-banner">
        <h1>🛒 MarketOto ile Akıllı Tasarruf!</h1>
        <p>Son kullanma tarihi yaklaşan ürünleri keşfedin, en uygun fiyatları karşılaştırın ve bütçenize en uygun sepeti oluşturun.</p>
        <div class="hero-stats">
            <div class="hero-stat">
                <div class="stat-value" id="stat-total"><?= $stats['toplam_urun'] ?? 0 ?></div>
                <div class="stat-label">Toplam Ürün</div>
            </div>
            <div class="hero-stat">
                <div class="stat-value" id="stat-expiring"><?= $stats['skt_yaklasan'] ?? 0 ?></div>
                <div class="stat-label">SKT Yaklaşan</div>
            </div>
            <div class="hero-stat">
                <div class="stat-value" id="stat-discounted"><?= $stats['indirimli'] ?? 0 ?></div>
                <div class="stat-label">İndirimli</div>
            </div>
            <div class="hero-stat">
                <div class="stat-value" id="stat-markets"><?= $stats['market_sayisi'] ?? 0 ?></div>
                <div class="stat-label">Market</div>
            </div>
        </div>
    </div>

    <!-- FILTER BAR -->
    <div class="filter-bar">
        <div class="search-box">
            <span class="search-icon">🔍</span>
            <input type="text" id="search-input" placeholder="Ürün ara... (örn: süt, ekmek, tavuk)">
        </div>
        <div class="filter-pills">
            <button class="filter-pill active" data-filter="all">Tümü</button>
            <?php foreach ($categories as $cat): ?>
                <button class="filter-pill" data-filter="<?= htmlspecialchars($cat) ?>"><?= htmlspecialchars($cat) ?></button>
            <?php endforeach; ?>
        </div>
    </div>

    <!-- SECTION HEADER -->
    <div class="section-header">
        <h2>🔥 Fırsat Ürünleri</h2>
        <p>SKT'si yaklaşan, indirimli ve en uygun fiyatlı ürünler</p>
    </div>

    <!-- PRODUCTS GRID -->
    <div class="products-grid" id="products-grid">
        <div style="grid-column:1/-1;text-align:center;"><div class="spinner"></div></div>
    </div>
</main>

<footer class="footer">
    <p>© 2026 MarketOto — İsrafı azalt, tasarruf et. PHP & MySQL ile geliştirildi.</p>
</footer>

<script src="assets/js/app.js"></script>
</body>
</html>
