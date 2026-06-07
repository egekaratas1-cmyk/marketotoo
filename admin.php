<?php
// ============================================
// ADMİN PANELİ - TAM CRUD
// ============================================
require_once 'auth.php';
require_once 'config.php';
requireAdmin();

$db = getDB();

// İstatistikler
$stats = $db->query("
    SELECT 
        (SELECT COUNT(*) FROM products) as toplam_urun,
        (SELECT COUNT(*) FROM customers) as toplam_musteri,
        (SELECT COUNT(*) FROM markets) as toplam_market,
        (SELECT COUNT(*) FROM inventory WHERE stok_miktari > 0 AND son_kullanma_tarihi >= CURDATE()) as aktif_stok,
        (SELECT COUNT(*) FROM inventory WHERE son_kullanma_tarihi < CURDATE()) as suresi_gecen,
        (SELECT COALESCE(SUM(toplam_kar), 0) FROM sales) as toplam_kar
")->fetch();

// Son eklenen müşteriler
$sonMusteriler = $db->query("
    SELECT * FROM customers ORDER BY kayit_tarihi DESC LIMIT 10
")->fetchAll();

// SKT geçmiş / yaklaşan ürünler
$sktUrunler = $db->query("
    SELECT i.*, p.urun_adi, p.kategori, m.isim as market_isim,
           DATEDIFF(i.son_kullanma_tarihi, CURDATE()) as kalan_gun
    FROM inventory i
    JOIN products p ON i.product_id = p.id
    JOIN markets m ON i.market_id = m.id
    WHERE i.stok_miktari > 0
    ORDER BY i.son_kullanma_tarihi ASC
    LIMIT 20
")->fetchAll();

// Tüm ürünler
$tumUrunler = $db->query("SELECT * FROM products ORDER BY urun_adi")->fetchAll();

// Tüm marketler
$tumMarketler = $db->query("SELECT * FROM markets ORDER BY isim")->fetchAll();

$adminName = getAdminName();
?>
<!DOCTYPE html>
<html lang="tr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta name="description" content="MarketOto Yönetim Paneli">
    <title>Admin Panel | MarketOto</title>
    <link rel="stylesheet" href="assets/css/style.css">
    <style>
        .admin-header { display: flex; justify-content: space-between; align-items: center; margin-bottom: 2rem; }
        .admin-welcome { display: flex; align-items: center; gap: 12px; }
        .admin-welcome .avatar { width: 48px; height: 48px; background: var(--gradient-accent); border-radius: 50%; display: flex; align-items: center; justify-content: center; font-size: 1.3rem; box-shadow: var(--shadow-glow); }
        .admin-welcome .info h2 { font-family: var(--font-primary); font-weight: 700; font-size: 1.2rem; color: var(--neutral-100); }
        .admin-welcome .info p { font-size: 0.8rem; color: var(--neutral-500); }
        .quick-actions { display: flex; gap: 10px; }
        .panel-card { background: var(--surface-card); backdrop-filter: blur(16px); border: 1px solid var(--surface-glass-border); border-radius: var(--radius-lg); padding: 1.5rem; }
        .panel-card h3 { font-family: var(--font-primary); font-weight: 700; font-size: 1.15rem; color: var(--neutral-100); margin-bottom: 1rem; padding-bottom: 0.75rem; border-bottom: 1px solid var(--surface-glass-border); display: flex; align-items: center; gap: 8px; }
        .mini-form { display: grid; grid-template-columns: 1fr 1fr; gap: 1rem; }
        .mini-form .full { grid-column: 1 / -1; }
        .mini-form input, .mini-form select { padding: 10px 14px; font-size: 0.85rem; }
        .mini-form label { font-size: 0.8rem; }
        .skt-indicator { display: inline-block; width: 10px; height: 10px; border-radius: 50%; margin-right: 6px; }
        .skt-indicator.red { background: var(--danger-500); }
        .skt-indicator.orange { background: var(--accent-500); }
        .skt-indicator.green { background: var(--primary-500); }
        .customer-list-item { display: flex; justify-content: space-between; align-items: center; padding: 10px 0; border-bottom: 1px solid rgba(255,255,255,0.04); }
        .customer-list-item:last-child { border-bottom: none; }
        .customer-list-item .cust-info { display: flex; align-items: center; gap: 10px; }
        .customer-list-item .cust-avatar { width: 36px; height: 36px; background: rgba(16,185,129,0.15); border-radius: 50%; display: flex; align-items: center; justify-content: center; font-size: 0.9rem; color: var(--primary-400); }
        .customer-list-item .cust-name { font-weight: 600; font-size: 0.9rem; color: var(--neutral-200); }
        .customer-list-item .cust-phone { font-size: 0.8rem; color: var(--neutral-500); }

        /* ACTION BUTTONS */
        .action-btns { display: flex; gap: 6px; }
        .btn-icon { width: 32px; height: 32px; border: none; border-radius: var(--radius-sm); cursor: pointer; display: flex; align-items: center; justify-content: center; font-size: 0.85rem; transition: var(--transition-base); }
        .btn-icon.edit { background: rgba(59,130,246,0.15); color: #60a5fa; }
        .btn-icon.edit:hover { background: rgba(59,130,246,0.3); }
        .btn-icon.delete { background: rgba(239,68,68,0.15); color: #f87171; }
        .btn-icon.delete:hover { background: rgba(239,68,68,0.3); }

        /* MODAL */
        .modal-overlay { display: none; position: fixed; top: 0; left: 0; width: 100%; height: 100%; background: rgba(0,0,0,0.6); backdrop-filter: blur(4px); z-index: 9999; align-items: center; justify-content: center; }
        .modal-overlay.active { display: flex; }
        .modal { background: var(--surface-card); border: 1px solid var(--surface-glass-border); border-radius: var(--radius-xl); padding: 2rem; width: 90%; max-width: 520px; max-height: 90vh; overflow-y: auto; box-shadow: var(--shadow-xl); animation: modalIn 0.3s ease; }
        @keyframes modalIn { from { opacity: 0; transform: translateY(-20px) scale(0.95); } to { opacity: 1; transform: translateY(0) scale(1); } }
        .modal-header { display: flex; justify-content: space-between; align-items: center; margin-bottom: 1.5rem; padding-bottom: 1rem; border-bottom: 1px solid var(--surface-glass-border); }
        .modal-header h3 { font-family: var(--font-primary); font-weight: 700; font-size: 1.2rem; color: var(--neutral-100); margin: 0; border: none; padding: 0; }
        .modal-close { width: 36px; height: 36px; border: 1px solid var(--surface-glass-border); border-radius: 50%; background: transparent; color: var(--neutral-400); font-size: 1.2rem; cursor: pointer; display: flex; align-items: center; justify-content: center; transition: var(--transition-base); }
        .modal-close:hover { background: rgba(255,255,255,0.05); color: var(--neutral-100); }
        .modal-footer { display: flex; gap: 10px; justify-content: flex-end; margin-top: 1.5rem; padding-top: 1rem; border-top: 1px solid var(--surface-glass-border); }
        .confirm-text { color: var(--neutral-300); font-size: 0.95rem; line-height: 1.6; margin-bottom: 0.5rem; }
        .confirm-text strong { color: var(--danger-400); }

        @media (max-width: 768px) {
            .admin-header { flex-direction: column; gap: 1rem; align-items: flex-start; }
            .quick-actions { flex-wrap: wrap; }
            .mini-form { grid-template-columns: 1fr; }
            .action-btns { flex-wrap: wrap; }
        }
    </style>
</head>
<body data-page="admin">

<div class="bg-particles">
    <div class="particle"></div><div class="particle"></div><div class="particle"></div>
    <div class="particle"></div><div class="particle"></div><div class="particle"></div>
</div>

<!-- NAVBAR -->
<nav class="navbar">
    <div class="navbar-inner">
        <a href="admin.php" class="navbar-logo">
            <span class="logo-icon">🛡️</span>
            Yönetim Paneli
        </a>
        <button class="navbar-toggle" id="navbar-toggle">☰</button>
        <ul class="navbar-menu" id="navbar-menu">
            <li><a href="index.php"><span class="nav-icon">🏠</span> Site</a></li>
            <li><a href="admin.php" class="active"><span class="nav-icon">⚙️</span> Panel</a></li>
            <li><a href="profit.php"><span class="nav-icon">📊</span> Kâr/Zarar</a></li>
            <li><a href="logout.php" style="color:var(--danger-400);"><span class="nav-icon">🚪</span> Çıkış</a></li>
        </ul>
    </div>
</nav>

<main class="main-content">
    <!-- ADMIN HEADER -->
    <div class="admin-header">
        <div class="admin-welcome">
            <div class="avatar">👤</div>
            <div class="info">
                <h2>Hoş geldin, <?= htmlspecialchars($adminName) ?>!</h2>
                <p>Son giriş: <?= date('d.m.Y H:i') ?></p>
            </div>
        </div>
        <div class="quick-actions">
            <a href="profit.php" class="btn btn-secondary btn-sm">📊 Kâr Raporu</a>
            <a href="index.php" class="btn btn-secondary btn-sm">🌐 Siteyi Gör</a>
            <a href="logout.php" class="btn btn-danger btn-sm">🚪 Çıkış</a>
        </div>
    </div>

    <!-- İSTATİSTİK KARTLARI -->
    <div class="stats-grid">
        <div class="stat-card">
            <div class="stat-icon green">📦</div>
            <div class="stat-content">
                <div class="stat-value"><?= $stats['toplam_urun'] ?></div>
                <div class="stat-label">Toplam Ürün</div>
            </div>
        </div>
        <div class="stat-card">
            <div class="stat-icon blue">👥</div>
            <div class="stat-content">
                <div class="stat-value"><?= $stats['toplam_musteri'] ?></div>
                <div class="stat-label">Müşteri</div>
            </div>
        </div>
        <div class="stat-card">
            <div class="stat-icon amber">🏪</div>
            <div class="stat-content">
                <div class="stat-value"><?= $stats['toplam_market'] ?></div>
                <div class="stat-label">Market</div>
            </div>
        </div>
        <div class="stat-card">
            <div class="stat-icon green">✅</div>
            <div class="stat-content">
                <div class="stat-value"><?= $stats['aktif_stok'] ?></div>
                <div class="stat-label">Aktif Stok</div>
            </div>
        </div>
        <div class="stat-card">
            <div class="stat-icon red">⚠️</div>
            <div class="stat-content">
                <div class="stat-value"><?= $stats['suresi_gecen'] ?></div>
                <div class="stat-label">SKT Geçmiş</div>
            </div>
        </div>
        <div class="stat-card">
            <div class="stat-icon purple">💰</div>
            <div class="stat-content">
                <div class="stat-value">₺<?= number_format($stats['toplam_kar'], 2, ',', '.') ?></div>
                <div class="stat-label">Toplam Kâr</div>
            </div>
        </div>
    </div>

    <!-- TABS -->
    <div id="admin-tabs-container">
        <div class="tabs">
            <button class="active" data-tab="tab-stok">📦 Stok/SKT</button>
            <button data-tab="tab-urunler">🏷️ Ürünler</button>
            <button data-tab="tab-urun-ekle">➕ Ürün Ekle</button>
            <button data-tab="tab-envanter">📋 Envanter Ekle</button>
            <button data-tab="tab-musteriler">👥 Müşteriler</button>
        </div>

        <!-- TAB: STOK / SKT TAKİBİ -->
        <div id="tab-stok" class="tab-content active">
            <div class="table-wrapper">
                <table class="data-table">
                    <thead>
                        <tr>
                            <th>Durum</th>
                            <th>Ürün</th>
                            <th>Market</th>
                            <th>Alış</th>
                            <th>Satış</th>
                            <th>İndirimli</th>
                            <th>SKT</th>
                            <th>Kalan</th>
                            <th>Stok</th>
                            <th>İşlem</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($sktUrunler as $u): 
                            $kalan = $u['kalan_gun'];
                            if ($kalan < 0) { $indicatorClass = 'red'; $statusText = 'GEÇMİŞ'; }
                            elseif ($kalan <= 3) { $indicatorClass = 'red'; $statusText = 'KRİTİK'; }
                            elseif ($kalan <= 7) { $indicatorClass = 'orange'; $statusText = 'YAKIN'; }
                            else { $indicatorClass = 'green'; $statusText = 'NORMAL'; }
                        ?>
                        <tr id="inv-row-<?= $u['id'] ?>">
                            <td><span class="skt-indicator <?= $indicatorClass ?>"></span><?= $statusText ?></td>
                            <td><strong><?= htmlspecialchars($u['urun_adi']) ?></strong><br><small style="color:var(--neutral-500);"><?= htmlspecialchars($u['kategori']) ?></small></td>
                            <td><?= htmlspecialchars($u['market_isim']) ?></td>
                            <td>₺<?= number_format($u['alis_fiyati'], 2) ?></td>
                            <td>₺<?= number_format($u['satis_fiyati'], 2) ?></td>
                            <td><?= $u['indirimli_fiyat'] ? '₺' . number_format($u['indirimli_fiyat'], 2) : '-' ?></td>
                            <td><?= date('d.m.Y', strtotime($u['son_kullanma_tarihi'])) ?></td>
                            <td style="color:<?= $indicatorClass === 'red' ? 'var(--danger-400)' : ($indicatorClass === 'orange' ? 'var(--accent-400)' : 'var(--primary-400)') ?>;font-weight:700;">
                                <?= $kalan ?> gün
                            </td>
                            <td><?= $u['stok_miktari'] ?></td>
                            <td>
                                <div class="action-btns">
                                    <button class="btn-icon edit" onclick="editInventory(<?= $u['id'] ?>)" title="Düzenle">✏️</button>
                                    <button class="btn-icon delete" onclick="deleteInventory(<?= $u['id'] ?>, '<?= htmlspecialchars(addslashes($u['urun_adi'])) ?>')" title="Sil">🗑️</button>
                                </div>
                            </td>
                        </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            </div>
        </div>

        <!-- TAB: ÜRÜN YÖNETİMİ -->
        <div id="tab-urunler" class="tab-content">
            <div class="panel-card">
                <h3>🏷️ Tüm Ürünler (<?= count($tumUrunler) ?>)</h3>
                <div class="table-wrapper">
                    <table class="data-table">
                        <thead>
                            <tr>
                                <th>ID</th>
                                <th>Barkod</th>
                                <th>Ürün Adı</th>
                                <th>Kategori</th>
                                <th>Birim</th>
                                <th>İşlem</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php foreach ($tumUrunler as $u): ?>
                            <tr id="product-row-<?= $u['id'] ?>">
                                <td>#<?= $u['id'] ?></td>
                                <td><code style="color:var(--accent-400);font-size:0.8rem;"><?= htmlspecialchars($u['barkod']) ?></code></td>
                                <td><strong><?= htmlspecialchars($u['urun_adi']) ?></strong></td>
                                <td><?= htmlspecialchars($u['kategori']) ?></td>
                                <td><?= htmlspecialchars($u['birim']) ?></td>
                                <td>
                                    <div class="action-btns">
                                        <button class="btn-icon edit" onclick="editProduct(<?= $u['id'] ?>)" title="Düzenle">✏️</button>
                                        <button class="btn-icon delete" onclick="deleteProduct(<?= $u['id'] ?>, '<?= htmlspecialchars(addslashes($u['urun_adi'])) ?>')" title="Sil">🗑️</button>
                                    </div>
                                </td>
                            </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                </div>
            </div>
        </div>

        <!-- TAB: ÜRÜN EKLE -->
        <div id="tab-urun-ekle" class="tab-content">
            <div class="panel-card">
                <h3>➕ Yeni Ürün Ekle</h3>
                <form id="form-urun-ekle" class="mini-form">
                    <div class="form-group">
                        <label for="urun-barkod">Barkod</label>
                        <input type="text" id="urun-barkod" placeholder="8690000000099" required>
                    </div>
                    <div class="form-group">
                        <label for="urun-adi">Ürün Adı</label>
                        <input type="text" id="urun-adi" placeholder="Örn: Kakaolu Süt 200ml" required>
                    </div>
                    <div class="form-group">
                        <label for="urun-kategori">Kategori</label>
                        <select id="urun-kategori">
                            <option value="Süt Ürünleri">Süt Ürünleri</option>
                            <option value="Et & Tavuk">Et & Tavuk</option>
                            <option value="Meyve & Sebze">Meyve & Sebze</option>
                            <option value="Temel Gıda">Temel Gıda</option>
                            <option value="İçecekler">İçecekler</option>
                            <option value="Fırın">Fırın</option>
                            <option value="Kahvaltılık">Kahvaltılık</option>
                            <option value="Konserve">Konserve</option>
                            <option value="Sos & Baharat">Sos & Baharat</option>
                            <option value="Temizlik">Temizlik</option>
                        </select>
                    </div>
                    <div class="form-group">
                        <label for="urun-birim">Birim</label>
                        <select id="urun-birim">
                            <option value="Adet">Adet</option>
                            <option value="kg">kg</option>
                            <option value="lt">lt</option>
                        </select>
                    </div>
                    <div class="full">
                        <button type="submit" class="btn btn-primary" style="width:100%;">➕ Ürünü Kaydet</button>
                    </div>
                </form>
            </div>
        </div>

        <!-- TAB: ENVANTER EKLE -->
        <div id="tab-envanter" class="tab-content">
            <div class="panel-card">
                <h3>📋 Yeni Envanter (Stok) Kaydı</h3>
                <form id="form-envanter-ekle" class="mini-form">
                    <div class="form-group">
                        <label for="env-market">Market</label>
                        <select id="env-market" required>
                            <option value="">Market Seçin</option>
                            <?php foreach ($tumMarketler as $m): ?>
                                <option value="<?= $m['id'] ?>"><?= htmlspecialchars($m['isim'] . ' - ' . $m['sube']) ?></option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                    <div class="form-group">
                        <label for="env-urun">Ürün</label>
                        <select id="env-urun" required>
                            <option value="">Ürün Seçin</option>
                            <?php foreach ($tumUrunler as $u): ?>
                                <option value="<?= $u['id'] ?>"><?= htmlspecialchars($u['urun_adi']) ?></option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                    <div class="form-group">
                        <label for="env-alis">Alış Fiyatı (₺)</label>
                        <input type="number" step="0.01" id="env-alis" placeholder="0.00" required>
                    </div>
                    <div class="form-group">
                        <label for="env-satis">Satış Fiyatı (₺)</label>
                        <input type="number" step="0.01" id="env-satis" placeholder="0.00" required>
                    </div>
                    <div class="form-group">
                        <label for="env-indirimli">İndirimli Fiyat (₺) <small style="color:var(--neutral-500);">(Opsiyonel)</small></label>
                        <input type="number" step="0.01" id="env-indirimli" placeholder="Boş bırakılabilir">
                    </div>
                    <div class="form-group">
                        <label for="env-skt">Son Kullanma Tarihi</label>
                        <input type="date" id="env-skt" required>
                    </div>
                    <div class="form-group">
                        <label for="env-stok">Stok Miktarı</label>
                        <input type="number" id="env-stok" placeholder="0" required>
                    </div>
                    <div class="form-group" style="display:flex;align-items:flex-end;">
                        <button type="submit" class="btn btn-primary" style="width:100%;">📋 Envanteri Kaydet</button>
                    </div>
                </form>
            </div>
        </div>

        <!-- TAB: MÜŞTERİLER -->
        <div id="tab-musteriler" class="tab-content">
            <div class="panel-card">
                <h3>👥 Kayıtlı Müşteriler (<?= count($sonMusteriler) ?>)</h3>
                <?php if (empty($sonMusteriler)): ?>
                    <div class="empty-state">
                        <div class="empty-icon">👥</div>
                        <h3>Henüz müşteri yok</h3>
                    </div>
                <?php else: ?>
                    <?php foreach ($sonMusteriler as $m): ?>
                    <div class="customer-list-item" id="customer-row-<?= $m['id'] ?>">
                        <div class="cust-info">
                            <div class="cust-avatar"><?= mb_substr($m['isim'], 0, 1) ?></div>
                            <div>
                                <div class="cust-name"><?= htmlspecialchars($m['isim']) ?></div>
                                <div class="cust-phone">📱 <?= htmlspecialchars($m['telefon_numarasi']) ?><?= $m['email'] ? ' · 📧 ' . htmlspecialchars($m['email']) : '' ?></div>
                            </div>
                        </div>
                        <div style="display:flex;align-items:center;gap:12px;">
                            <span style="font-size:0.75rem;color:var(--neutral-600);"><?= date('d.m.Y', strtotime($m['kayit_tarihi'])) ?></span>
                            <div class="action-btns">
                                <button class="btn-icon edit" onclick="editCustomer(<?= $m['id'] ?>)" title="Düzenle">✏️</button>
                                <button class="btn-icon delete" onclick="deleteCustomer(<?= $m['id'] ?>, '<?= htmlspecialchars(addslashes($m['isim'])) ?>')" title="Sil">🗑️</button>
                            </div>
                        </div>
                    </div>
                    <?php endforeach; ?>
                <?php endif; ?>
            </div>
        </div>
    </div>
</main>

<!-- ==================== MODALS ==================== -->

<!-- ÜRÜN DÜZENLEME MODAL -->
<div class="modal-overlay" id="modal-edit-product">
    <div class="modal">
        <div class="modal-header">
            <h3>✏️ Ürün Düzenle</h3>
            <button class="modal-close" onclick="closeModal('modal-edit-product')">✕</button>
        </div>
        <form id="form-edit-product" class="mini-form">
            <input type="hidden" id="edit-product-id">
            <div class="form-group">
                <label>Barkod</label>
                <input type="text" id="edit-product-barkod" required>
            </div>
            <div class="form-group">
                <label>Ürün Adı</label>
                <input type="text" id="edit-product-adi" required>
            </div>
            <div class="form-group">
                <label>Kategori</label>
                <select id="edit-product-kategori">
                    <option value="Süt Ürünleri">Süt Ürünleri</option>
                    <option value="Et & Tavuk">Et & Tavuk</option>
                    <option value="Meyve & Sebze">Meyve & Sebze</option>
                    <option value="Temel Gıda">Temel Gıda</option>
                    <option value="İçecekler">İçecekler</option>
                    <option value="Fırın">Fırın</option>
                    <option value="Kahvaltılık">Kahvaltılık</option>
                    <option value="Konserve">Konserve</option>
                    <option value="Sos & Baharat">Sos & Baharat</option>
                    <option value="Temizlik">Temizlik</option>
                </select>
            </div>
            <div class="form-group">
                <label>Birim</label>
                <select id="edit-product-birim">
                    <option value="Adet">Adet</option>
                    <option value="kg">kg</option>
                    <option value="lt">lt</option>
                </select>
            </div>
            <div class="full modal-footer">
                <button type="button" class="btn btn-secondary btn-sm" onclick="closeModal('modal-edit-product')">İptal</button>
                <button type="submit" class="btn btn-primary btn-sm">💾 Güncelle</button>
            </div>
        </form>
    </div>
</div>

<!-- ENVANTER DÜZENLEME MODAL -->
<div class="modal-overlay" id="modal-edit-inventory">
    <div class="modal">
        <div class="modal-header">
            <h3>✏️ Envanter Düzenle</h3>
            <button class="modal-close" onclick="closeModal('modal-edit-inventory')">✕</button>
        </div>
        <div id="edit-inv-product-name" style="color:var(--primary-400);font-weight:600;margin-bottom:1rem;font-size:0.95rem;"></div>
        <form id="form-edit-inventory" class="mini-form">
            <input type="hidden" id="edit-inv-id">
            <div class="form-group">
                <label>Alış Fiyatı (₺)</label>
                <input type="number" step="0.01" id="edit-inv-alis" required>
            </div>
            <div class="form-group">
                <label>Satış Fiyatı (₺)</label>
                <input type="number" step="0.01" id="edit-inv-satis" required>
            </div>
            <div class="form-group">
                <label>İndirimli Fiyat (₺) <small style="color:var(--neutral-500);">(Opsiyonel)</small></label>
                <input type="number" step="0.01" id="edit-inv-indirimli">
            </div>
            <div class="form-group">
                <label>Son Kullanma Tarihi</label>
                <input type="date" id="edit-inv-skt" required>
            </div>
            <div class="form-group">
                <label>Stok Miktarı</label>
                <input type="number" id="edit-inv-stok" required>
            </div>
            <div class="form-group"></div>
            <div class="full modal-footer">
                <button type="button" class="btn btn-secondary btn-sm" onclick="closeModal('modal-edit-inventory')">İptal</button>
                <button type="submit" class="btn btn-primary btn-sm">💾 Güncelle</button>
            </div>
        </form>
    </div>
</div>

<!-- MÜŞTERİ DÜZENLEME MODAL -->
<div class="modal-overlay" id="modal-edit-customer">
    <div class="modal">
        <div class="modal-header">
            <h3>✏️ Müşteri Düzenle</h3>
            <button class="modal-close" onclick="closeModal('modal-edit-customer')">✕</button>
        </div>
        <form id="form-edit-customer" class="mini-form">
            <input type="hidden" id="edit-cust-id">
            <div class="form-group full">
                <label>👤 Ad Soyad</label>
                <input type="text" id="edit-cust-isim" required>
            </div>
            <div class="form-group">
                <label>📱 Telefon</label>
                <input type="tel" id="edit-cust-telefon" maxlength="11" required>
            </div>
            <div class="form-group">
                <label>📧 E-posta</label>
                <input type="email" id="edit-cust-email">
            </div>
            <div class="full modal-footer">
                <button type="button" class="btn btn-secondary btn-sm" onclick="closeModal('modal-edit-customer')">İptal</button>
                <button type="submit" class="btn btn-primary btn-sm">💾 Güncelle</button>
            </div>
        </form>
    </div>
</div>

<!-- SİLME ONAY MODAL -->
<div class="modal-overlay" id="modal-confirm-delete">
    <div class="modal" style="max-width:420px;">
        <div class="modal-header">
            <h3>⚠️ Silme Onayı</h3>
            <button class="modal-close" onclick="closeModal('modal-confirm-delete')">✕</button>
        </div>
        <p class="confirm-text" id="confirm-delete-text"></p>
        <div class="modal-footer">
            <button type="button" class="btn btn-secondary btn-sm" onclick="closeModal('modal-confirm-delete')">İptal</button>
            <button type="button" class="btn btn-danger btn-sm" id="confirm-delete-btn">🗑️ Evet, Sil</button>
        </div>
    </div>
</div>

<footer class="footer">
    <p>© 2026 MarketOto — Yönetim Paneli</p>
</footer>

<script src="assets/js/app.js"></script>
<script>
// ==========================================
// TABS INIT
// ==========================================
initTabs('#admin-tabs-container');

// ==========================================
// MODAL FONKSİYONLARI
// ==========================================
function openModal(id) {
    document.getElementById(id).classList.add('active');
}
function closeModal(id) {
    document.getElementById(id).classList.remove('active');
}
document.addEventListener('keydown', (e) => {
    if (e.key === 'Escape') {
        document.querySelectorAll('.modal-overlay.active').forEach(m => m.classList.remove('active'));
    }
});
document.querySelectorAll('.modal-overlay').forEach(overlay => {
    overlay.addEventListener('click', (e) => {
        if (e.target === overlay) overlay.classList.remove('active');
    });
});

// ==========================================
// ÜRÜN CRUD
// ==========================================

// CREATE
document.getElementById('form-urun-ekle')?.addEventListener('submit', async (e) => {
    e.preventDefault();
    const data = new FormData();
    data.append('action', 'add_product');
    data.append('barkod', document.getElementById('urun-barkod').value);
    data.append('urun_adi', document.getElementById('urun-adi').value);
    data.append('kategori', document.getElementById('urun-kategori').value);
    data.append('birim', document.getElementById('urun-birim').value);
    try {
        const res = await fetch('api_admin.php', { method: 'POST', body: data });
        const json = await res.json();
        if (json.success) {
            showToast('Ürün başarıyla eklendi! ✅', 'success');
            e.target.reset();
            setTimeout(() => location.reload(), 1500);
        } else {
            showToast(json.error || 'Hata oluştu', 'error');
        }
    } catch (err) { showToast('Sunucu hatası!', 'error'); }
});

// READ + EDIT MODAL
async function editProduct(id) {
    try {
        const res = await fetch('api_admin.php?action=get_product&id=' + id);
        const json = await res.json();
        if (json.success) {
            const p = json.product;
            document.getElementById('edit-product-id').value = p.id;
            document.getElementById('edit-product-barkod').value = p.barkod;
            document.getElementById('edit-product-adi').value = p.urun_adi;
            document.getElementById('edit-product-kategori').value = p.kategori;
            document.getElementById('edit-product-birim').value = p.birim;
            openModal('modal-edit-product');
        } else { showToast(json.error || 'Ürün bulunamadı', 'error'); }
    } catch (err) { showToast('Sunucu hatası!', 'error'); }
}

// UPDATE
document.getElementById('form-edit-product')?.addEventListener('submit', async (e) => {
    e.preventDefault();
    const data = new FormData();
    data.append('action', 'update_product');
    data.append('id', document.getElementById('edit-product-id').value);
    data.append('barkod', document.getElementById('edit-product-barkod').value);
    data.append('urun_adi', document.getElementById('edit-product-adi').value);
    data.append('kategori', document.getElementById('edit-product-kategori').value);
    data.append('birim', document.getElementById('edit-product-birim').value);
    try {
        const res = await fetch('api_admin.php', { method: 'POST', body: data });
        const json = await res.json();
        if (json.success) {
            showToast('Ürün güncellendi! ✅', 'success');
            closeModal('modal-edit-product');
            setTimeout(() => location.reload(), 1000);
        } else { showToast(json.error || 'Hata oluştu', 'error'); }
    } catch (err) { showToast('Sunucu hatası!', 'error'); }
});

// DELETE
function deleteProduct(id, name) {
    document.getElementById('confirm-delete-text').innerHTML =
        `<strong>"${name}"</strong> ürünü kalıcı olarak silinecektir.<br>Bu işlem geri alınamaz.`;
    const btn = document.getElementById('confirm-delete-btn');
    btn.onclick = async () => {
        const data = new FormData();
        data.append('action', 'delete_product');
        data.append('id', id);
        try {
            const res = await fetch('api_admin.php', { method: 'POST', body: data });
            const json = await res.json();
            if (json.success) {
                showToast('Ürün silindi! 🗑️', 'success');
                closeModal('modal-confirm-delete');
                const row = document.getElementById('product-row-' + id);
                if (row) { row.style.transition='opacity 0.3s'; row.style.opacity='0'; setTimeout(() => row.remove(), 300); }
            } else { showToast(json.error || 'Hata oluştu', 'error'); }
        } catch (err) { showToast('Sunucu hatası!', 'error'); }
    };
    openModal('modal-confirm-delete');
}

// ==========================================
// ENVANTER CRUD
// ==========================================

// CREATE
document.getElementById('form-envanter-ekle')?.addEventListener('submit', async (e) => {
    e.preventDefault();
    const data = new FormData();
    data.append('action', 'add_inventory');
    data.append('market_id', document.getElementById('env-market').value);
    data.append('product_id', document.getElementById('env-urun').value);
    data.append('alis_fiyati', document.getElementById('env-alis').value);
    data.append('satis_fiyati', document.getElementById('env-satis').value);
    data.append('indirimli_fiyat', document.getElementById('env-indirimli').value);
    data.append('son_kullanma_tarihi', document.getElementById('env-skt').value);
    data.append('stok_miktari', document.getElementById('env-stok').value);
    try {
        const res = await fetch('api_admin.php', { method: 'POST', body: data });
        const json = await res.json();
        if (json.success) {
            showToast('Envanter kaydı eklendi! ✅', 'success');
            e.target.reset();
            setTimeout(() => location.reload(), 1500);
        } else { showToast(json.error || 'Hata oluştu', 'error'); }
    } catch (err) { showToast('Sunucu hatası!', 'error'); }
});

// READ + EDIT MODAL
async function editInventory(id) {
    try {
        const res = await fetch('api_admin.php?action=get_inventory&id=' + id);
        const json = await res.json();
        if (json.success) {
            const inv = json.inventory;
            document.getElementById('edit-inv-id').value = inv.id;
            document.getElementById('edit-inv-alis').value = inv.alis_fiyati;
            document.getElementById('edit-inv-satis').value = inv.satis_fiyati;
            document.getElementById('edit-inv-indirimli').value = inv.indirimli_fiyat || '';
            document.getElementById('edit-inv-skt').value = inv.son_kullanma_tarihi;
            document.getElementById('edit-inv-stok').value = inv.stok_miktari;
            document.getElementById('edit-inv-product-name').textContent = '📦 ' + inv.urun_adi + ' — 🏪 ' + inv.market_isim;
            openModal('modal-edit-inventory');
        } else { showToast(json.error || 'Envanter bulunamadı', 'error'); }
    } catch (err) { showToast('Sunucu hatası!', 'error'); }
}

// UPDATE
document.getElementById('form-edit-inventory')?.addEventListener('submit', async (e) => {
    e.preventDefault();
    const data = new FormData();
    data.append('action', 'update_inventory');
    data.append('id', document.getElementById('edit-inv-id').value);
    data.append('alis_fiyati', document.getElementById('edit-inv-alis').value);
    data.append('satis_fiyati', document.getElementById('edit-inv-satis').value);
    data.append('indirimli_fiyat', document.getElementById('edit-inv-indirimli').value);
    data.append('son_kullanma_tarihi', document.getElementById('edit-inv-skt').value);
    data.append('stok_miktari', document.getElementById('edit-inv-stok').value);
    try {
        const res = await fetch('api_admin.php', { method: 'POST', body: data });
        const json = await res.json();
        if (json.success) {
            showToast('Envanter güncellendi! ✅', 'success');
            closeModal('modal-edit-inventory');
            setTimeout(() => location.reload(), 1000);
        } else { showToast(json.error || 'Hata oluştu', 'error'); }
    } catch (err) { showToast('Sunucu hatası!', 'error'); }
});

// DELETE
function deleteInventory(id, name) {
    document.getElementById('confirm-delete-text').innerHTML =
        `<strong>"${name}"</strong> envanter kaydı silinecektir.<br>Bu işlem geri alınamaz.`;
    const btn = document.getElementById('confirm-delete-btn');
    btn.onclick = async () => {
        const data = new FormData();
        data.append('action', 'delete_inventory');
        data.append('id', id);
        try {
            const res = await fetch('api_admin.php', { method: 'POST', body: data });
            const json = await res.json();
            if (json.success) {
                showToast('Envanter silindi! 🗑️', 'success');
                closeModal('modal-confirm-delete');
                const row = document.getElementById('inv-row-' + id);
                if (row) { row.style.transition='opacity 0.3s'; row.style.opacity='0'; setTimeout(() => row.remove(), 300); }
            } else { showToast(json.error || 'Hata oluştu', 'error'); }
        } catch (err) { showToast('Sunucu hatası!', 'error'); }
    };
    openModal('modal-confirm-delete');
}

// ==========================================
// MÜŞTERİ CRUD
// ==========================================

// READ + EDIT MODAL
async function editCustomer(id) {
    try {
        const res = await fetch('api_admin.php?action=get_customer&id=' + id);
        const json = await res.json();
        if (json.success) {
            const c = json.customer;
            document.getElementById('edit-cust-id').value = c.id;
            document.getElementById('edit-cust-isim').value = c.isim;
            document.getElementById('edit-cust-telefon').value = c.telefon_numarasi;
            document.getElementById('edit-cust-email').value = c.email || '';
            openModal('modal-edit-customer');
        } else { showToast(json.error || 'Müşteri bulunamadı', 'error'); }
    } catch (err) { showToast('Sunucu hatası!', 'error'); }
}

// UPDATE
document.getElementById('form-edit-customer')?.addEventListener('submit', async (e) => {
    e.preventDefault();
    const data = new FormData();
    data.append('action', 'update_customer');
    data.append('id', document.getElementById('edit-cust-id').value);
    data.append('isim', document.getElementById('edit-cust-isim').value);
    data.append('telefon', document.getElementById('edit-cust-telefon').value);
    data.append('email', document.getElementById('edit-cust-email').value);
    try {
        const res = await fetch('api_admin.php', { method: 'POST', body: data });
        const json = await res.json();
        if (json.success) {
            showToast('Müşteri güncellendi! ✅', 'success');
            closeModal('modal-edit-customer');
            setTimeout(() => location.reload(), 1000);
        } else { showToast(json.error || 'Hata oluştu', 'error'); }
    } catch (err) { showToast('Sunucu hatası!', 'error'); }
});

// DELETE
function deleteCustomer(id, name) {
    document.getElementById('confirm-delete-text').innerHTML =
        `<strong>"${name}"</strong> müşteri kaydı silinecektir.<br>Bu işlem geri alınamaz.`;
    const btn = document.getElementById('confirm-delete-btn');
    btn.onclick = async () => {
        const data = new FormData();
        data.append('action', 'delete_customer');
        data.append('id', id);
        try {
            const res = await fetch('api_admin.php', { method: 'POST', body: data });
            const json = await res.json();
            if (json.success) {
                showToast('Müşteri silindi! 🗑️', 'success');
                closeModal('modal-confirm-delete');
                const row = document.getElementById('customer-row-' + id);
                if (row) { row.style.transition='opacity 0.3s'; row.style.opacity='0'; setTimeout(() => row.remove(), 300); }
            } else { showToast(json.error || 'Hata oluştu', 'error'); }
        } catch (err) { showToast('Sunucu hatası!', 'error'); }
    };
    openModal('modal-confirm-delete');
}
</script>
</body>
</html>
