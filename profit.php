<?php
// ============================================
// KÂR / ZARAR RAPORU SAYFASI
// ============================================
require_once 'auth.php';
requireAdmin();
?>
<!DOCTYPE html>
<html lang="tr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta name="description" content="Market kâr ve zarar raporlarını görüntüleyin, satış istatistiklerini takip edin.">
    <title>Kâr / Zarar Raporu | MarketOto</title>
    <link rel="stylesheet" href="assets/css/style.css">
</head>
<body data-page="profit">

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
            <li><a href="index.php"><span class="nav-icon">🏠</span> Site</a></li>
            <li><a href="admin.php"><span class="nav-icon">⚙️</span> Panel</a></li>
            <li><a href="profit.php" class="active"><span class="nav-icon">📊</span> Kâr/Zarar</a></li>
            <li><a href="logout.php" style="color:var(--danger-400);"><span class="nav-icon">🚪</span> Çıkış</a></li>
        </ul>
    </div>
</nav>

<main class="main-content">
    <div class="section-header">
        <h1>📊 Kâr / Zarar Raporu</h1>
        <p>Satış istatistikleri, kârlılık analizi ve detaylı raporlar</p>
    </div>

    <!-- İSTATİSTİK KARTLARI -->
    <div class="stats-grid">
        <div class="stat-card">
            <div class="stat-icon green">💰</div>
            <div class="stat-content">
                <div class="stat-value" id="profit-total-sales">₺0</div>
                <div class="stat-label">Toplam Satış</div>
            </div>
        </div>
        <div class="stat-card">
            <div class="stat-icon blue">📈</div>
            <div class="stat-content">
                <div class="stat-value" id="profit-total-profit">₺0</div>
                <div class="stat-label">Toplam Kâr</div>
            </div>
        </div>
        <div class="stat-card">
            <div class="stat-icon amber">🛒</div>
            <div class="stat-content">
                <div class="stat-value" id="profit-total-orders">0</div>
                <div class="stat-label">Toplam Sipariş</div>
            </div>
        </div>
        <div class="stat-card">
            <div class="stat-icon purple">📊</div>
            <div class="stat-content">
                <div class="stat-value" id="profit-avg-order">₺0</div>
                <div class="stat-label">Ortalama Sipariş</div>
            </div>
        </div>
    </div>

    <!-- KÂR GRAFİĞİ -->
    <div class="chart-container">
        <h3>📈 Son 7 Günlük Satış & Kâr</h3>
        <div style="display:flex;gap:1.5rem;margin-bottom:1rem;">
            <div style="display:flex;align-items:center;gap:6px;font-size:0.8rem;color:var(--neutral-400);">
                <div style="width:12px;height:12px;border-radius:3px;background:var(--gradient-accent);"></div> Satış
            </div>
            <div style="display:flex;align-items:center;gap:6px;font-size:0.8rem;color:var(--neutral-400);">
                <div style="width:12px;height:12px;border-radius:3px;background:var(--gradient-gold);"></div> Kâr
            </div>
        </div>
        <div class="bar-chart" id="profit-chart">
            <div style="text-align:center;width:100;"><div class="spinner"></div></div>
        </div>
    </div>

    <!-- SON SATIŞLAR TABLOSU -->
    <div class="section-header" style="margin-top:2rem;">
        <h2>🧾 Son Satışlar</h2>
    </div>

    <div class="table-wrapper">
        <table class="data-table">
            <thead>
                <tr>
                    <th>Sipariş #</th>
                    <th>Müşteri</th>
                    <th>Toplam Tutar</th>
                    <th>Kâr</th>
                    <th>Tarih</th>
                </tr>
            </thead>
            <tbody id="sales-tbody">
                <tr><td colspan="5" style="text-align:center;"><div class="spinner" style="margin:1rem auto;"></div></td></tr>
            </tbody>
        </table>
    </div>
</main>

<footer class="footer">
    <p>© 2026 MarketOto — İsrafı azalt, tasarruf et.</p>
</footer>

<script src="assets/js/app.js"></script>
</body>
</html>
