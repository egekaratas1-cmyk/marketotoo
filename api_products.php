<?php
// ============================================
// API: ÜRÜN LİSTESİ ENDPOINT'İ (api_products.php)
// ============================================
//
// Bu dosya frontend'in (app.js) AJAX çağrılarıyla kullandığı
// ürün listeleme API'sidir. GET isteği ile çalışır.
//
// KULLANIM:
//   GET /api_products.php                    → Tüm ürünleri listeler
//   GET /api_products.php?kategori=İçecekler → Kategoriye göre filtreler
//   GET /api_products.php?arama=süt          → İsim/kategori/market bazlı arama
//
// DÖNDÜRÜR (JSON):
//   {
//     "success": true,
//     "products": [ ... ],   ← Ürün listesi (envanter bilgileriyle)
//     "stats": { ... }       ← Dashboard istatistikleri
//   }
//
// BAĞIMLILIKLAR:
//   - config.php: Veritabanı bağlantısı ve yardımcı fonksiyonlar
//   - Tablolar: inventory, products, markets
// ============================================

// Yapılandırma dosyasını dahil et (DB bağlantısı + yardımcı fonksiyonlar)
require_once 'config.php';

// Bu endpoint JSON döndürür, Content-Type header'ını ayarla
header('Content-Type: application/json; charset=utf-8');

// Veritabanı bağlantısını al
$db = getDB();

// ── GET PARAMETRELERİNİ OKU ──
// ?kategori=... parametresi: Belirli bir kategoriye göre filtreleme
$kategori = $_GET['kategori'] ?? null;

// ?arama=... parametresi: Ürün adı, kategori veya market adına göre arama
$arama = $_GET['arama'] ?? null;

// ── DİNAMİK WHERE KOŞULLARI OLUŞTUR ──
// Temel koşullar: Stokta olan ve süresi geçmemiş ürünler
$where = ["i.stok_miktari > 0", "i.son_kullanma_tarihi >= CURDATE()"];
$params = []; // Prepared statement parametreleri (SQL injection koruması)

// Kategori filtresi varsa WHERE'e ekle
if ($kategori && $kategori !== 'all') {
    $where[] = "p.kategori = :kategori";
    $params[':kategori'] = $kategori;
}

// Arama filtresi varsa WHERE'e ekle (ürün adı, kategori VEYA market adında arar)
if ($arama) {
    $where[] = "(p.urun_adi LIKE :arama OR p.kategori LIKE :arama2 OR m.isim LIKE :arama3)";
    $params[':arama'] = "%{$arama}%";   // Ürün adında ara
    $params[':arama2'] = "%{$arama}%";  // Kategoride ara
    $params[':arama3'] = "%{$arama}%";  // Market adında ara
}

// WHERE koşullarını AND ile birleştir
$whereSQL = implode(' AND ', $where);

// ── ANA SORGU: ÜRÜN + ENVANTER + MARKET BİLGİLERİNİ BİRLEŞTİR ──
// 3 tabloyu JOIN ile birleştiriyoruz:
//   inventory (i) → Stok, fiyat, SKT bilgisi
//   products  (p) → Ürün adı, kategori, barkod, resim
//   markets   (m) → Market adı ve şube bilgisi
//
// Sıralama: Önce SKT'si en yakın (kalan_gun ASC), sonra en ucuz (satis_fiyati ASC)
// LIMIT 50: Performans için maksimum 50 ürün döndür
$sql = "
    SELECT 
        i.id as inventory_id,
        p.id as product_id,
        p.urun_adi,
        p.kategori,
        p.birim,
        p.resim_url,
        m.isim as market_isim,
        m.sube,
        i.satis_fiyati,
        i.alis_fiyati,
        i.indirimli_fiyat,
        i.son_kullanma_tarihi,
        i.stok_miktari,
        DATEDIFF(i.son_kullanma_tarihi, CURDATE()) as kalan_gun
    FROM inventory i
    JOIN products p ON i.product_id = p.id
    JOIN markets m ON i.market_id = m.id
    WHERE {$whereSQL}
    ORDER BY kalan_gun ASC, i.satis_fiyati ASC
    LIMIT 50
";

// Prepared statement ile sorguyu çalıştır (SQL injection koruması)
$stmt = $db->prepare($sql);
$stmt->execute($params);
$products = $stmt->fetchAll();

// ── EFEKTİF FİYAT VE İNDİRİM YÜZDESI HESAPLA ──
// Her ürün için config.php'deki getEffectivePrice() fonksiyonunu kullanarak
// müşteriye gösterilecek nihai fiyatı ve indirim yüzdesini hesapla
foreach ($products as &$p) {
    // Efektif fiyat: İndirimli fiyat > SKT indirimi > Normal fiyat öncelik sırasıyla
    $efektif = getEffectivePrice($p['satis_fiyati'], $p['indirimli_fiyat'], $p['son_kullanma_tarihi']);
    $p['efektif_fiyat'] = $efektif;

    // İndirim yüzdesi hesapla (frontend'de badge göstermek için)
    if ($efektif < $p['satis_fiyati']) {
        $p['indirim_yuzdesi'] = round((1 - $efektif / $p['satis_fiyati']) * 100);
    } else {
        $p['indirim_yuzdesi'] = 0;
    }
}

// ── DASHBOARD İSTATİSTİKLERİ ──
// Ana sayfadaki hero banner'da gösterilen genel istatistikler
$stats = $db->query("
    SELECT 
        (SELECT COUNT(*) FROM inventory WHERE stok_miktari > 0 AND son_kullanma_tarihi >= CURDATE()) as toplam_urun,
        (SELECT COUNT(*) FROM inventory WHERE son_kullanma_tarihi <= DATE_ADD(CURDATE(), INTERVAL 7 DAY) AND stok_miktari > 0 AND son_kullanma_tarihi >= CURDATE()) as skt_yaklasan,
        (SELECT COUNT(*) FROM inventory WHERE indirimli_fiyat IS NOT NULL AND stok_miktari > 0) as indirimli,
        (SELECT COUNT(*) FROM markets) as market_sayisi
")->fetch();

// ── JSON YANIT DÖNDÜR ──
echo json_encode([
    'success' => true,
    'products' => $products, // Ürün listesi
    'stats' => $stats        // İstatistikler
], JSON_UNESCAPED_UNICODE);

