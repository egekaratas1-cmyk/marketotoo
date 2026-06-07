<?php
// ============================================
// AKILLI SEPET SAYFASI
// ============================================
?>
<!DOCTYPE html>
<html lang="tr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta name="description" content="Akıllı sepetinizi oluşturun, karma ve tek market fiyatlarını karşılaştırın.">
    <title>Akıllı Sepet | MarketOto</title>
    <link rel="stylesheet" href="assets/css/style.css">
</head>
<body data-page="basket">

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
            <li><a href="basket.php" class="active"><span class="nav-icon">🛒</span> Akıllı Sepet <span id="basket-badge" style="background:var(--gradient-danger);color:#fff;font-size:0.7rem;padding:2px 7px;border-radius:var(--radius-full);display:none;">0</span></a></li>
            <li><a href="register.php"><span class="nav-icon">👤</span> Kayıt Ol</a></li>
            <li><a href="admin_login.php"><span class="nav-icon">🔐</span> Admin</a></li>
        </ul>
    </div>
</nav>

<main class="main-content">
    <div class="section-header">
        <h1>🧠 Akıllı Sepet</h1>
        <p>Ürünlerinizi ekleyin, en uygun fiyat kombinasyonunu bulun!</p>
    </div>

    <div class="basket-layout">
        <!-- SOL: SEPET İÇERİĞİ VE KARŞILAŞTIRMA -->
        <div>
            <!-- SEPETİM -->
            <div class="glass-card" style="margin-bottom:2rem;">
                <div style="display:flex;justify-content:space-between;align-items:center;margin-bottom:1rem;">
                    <h3 style="font-family:var(--font-primary);font-weight:700;font-size:1.2rem;color:var(--neutral-100);">📦 Sepetim</h3>
                    <button class="btn btn-secondary btn-sm" onclick="clearBasket()">🗑️ Temizle</button>
                </div>
                <div id="basket-items">
                    <!-- JS ile doldurulacak -->
                </div>
            </div>

            <!-- KARŞILAŞTIRMA SONUÇLARI -->
            <div id="comparison-results"></div>
        </div>

        <!-- SAĞ SIDEBAR -->
        <div class="basket-sidebar">
            <div class="basket-summary">
                <h3>💰 Sepet Özeti</h3>
                <div class="basket-total">
                    <span class="total-label">Mevcut Toplam</span>
                    <span class="total-amount" id="basket-total-amount">₺0.00</span>
                </div>
                <button class="btn btn-primary btn-lg" style="width:100%;margin-top:1.5rem;" onclick="calculateSmartBasket()">
                    🧠 En Ucuz Sepeti Bul!
                </button>
                <p style="color:var(--neutral-500);font-size:0.8rem;margin-top:0.75rem;text-align:center;">
                    Karma + Tek Market karşılaştırması yapılır
                </p>
            </div>

            <!-- Hızlı Ürün Ekleme -->
            <div class="glass-card" style="margin-top:1.5rem;">
                <h3 style="font-family:var(--font-primary);font-weight:700;font-size:1rem;color:var(--neutral-100);margin-bottom:1rem;">⚡ Hızlı Ekle</h3>
                <div class="form-group" style="margin-bottom:0.75rem;">
                    <input type="text" id="quick-search" placeholder="Ürün adı ara..." style="padding:10px 14px;font-size:0.85rem;">
                </div>
                <div id="quick-results" style="max-height:300px;overflow-y:auto;"></div>
            </div>
        </div>
    </div>
</main>

<footer class="footer">
    <p>© 2026 MarketOto — İsrafı azalt, tasarruf et.</p>
</footer>

<script src="assets/js/app.js"></script>
<script>
// Quick search for basket page
document.addEventListener('DOMContentLoaded', () => {
    const quickInput = document.getElementById('quick-search');
    const quickResults = document.getElementById('quick-results');
    if (!quickInput || !quickResults) return;

    let timer;
    quickInput.addEventListener('input', (e) => {
        clearTimeout(timer);
        const query = e.target.value.trim();
        if (query.length < 2) {
            quickResults.innerHTML = '';
            return;
        }
        timer = setTimeout(async () => {
            try {
                const res = await fetch('api_products.php?arama=' + encodeURIComponent(query));
                const data = await res.json();
                if (data.products && data.products.length > 0) {
                    quickResults.innerHTML = data.products.slice(0, 8).map(p => {
                        const price = p.efektif_fiyat || p.satis_fiyati;
                        return `
                            <div style="display:flex;justify-content:space-between;align-items:center;padding:8px 0;border-bottom:1px solid rgba(255,255,255,0.04);cursor:pointer;" onclick="addToBasket(${p.inventory_id}, '${p.urun_adi.replace(/'/g, "\\'")}', '${p.market_isim}', ${price}); this.style.opacity='0.5';">
                                <div>
                                    <div style="font-size:0.85rem;color:var(--neutral-200);">${p.urun_adi}</div>
                                    <div style="font-size:0.7rem;color:var(--neutral-500);">🏪 ${p.market_isim}</div>
                                </div>
                                <span style="font-family:var(--font-primary);font-weight:700;color:var(--primary-400);font-size:0.9rem;">₺${parseFloat(price).toFixed(2)}</span>
                            </div>
                        `;
                    }).join('');
                } else {
                    quickResults.innerHTML = '<p style="color:var(--neutral-500);font-size:0.85rem;text-align:center;padding:1rem 0;">Sonuç bulunamadı</p>';
                }
            } catch (err) {
                quickResults.innerHTML = '<p style="color:var(--danger-400);font-size:0.85rem;text-align:center;">Hata oluştu</p>';
            }
        }, 300);
    });
});
</script>
</body>
</html>
