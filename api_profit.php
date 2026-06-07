<?php
// ============================================
// API: KÂR / ZARAR VERİLERİ ENDPOINT'İ (api_profit.php)
// ============================================
//
// Admin panelindeki (profit.php) kâr/zarar raporlama sayfası için
// finansal verileri döndüren GET API'sidir.
//
// KULLANIM: GET /api_profit.php
//
// DÖNDÜRÜR (JSON):
//   {
//     "success": true,
//     "ozet": { toplam_satis, toplam_kar, toplam_siparis, ortalama_siparis },
//     "gunluk": [ ... ],        ← Son 7 günlük satış/kâr grafiği verisi
//     "son_satislar": [ ... ],  ← Son 20 satış kaydı (tablo için)
//     "kategoriler": [ ... ]    ← Kategori bazlı kâr analizi
//   }
// ============================================
require_once 'config.php';

header('Content-Type: application/json; charset=utf-8');

$db = getDB();

// ── 1. GENEL ÖZET İSTATİSTİKLERİ ──
// Tüm zamanların toplam satış, kâr, sipariş sayısı ve ortalama sipariş tutarı
// COALESCE: NULL değerleri 0 olarak döndürür (veri yoksa hata vermemesi için)
$ozet = $db->query("
    SELECT
        COALESCE(SUM(toplam_tutar), 0) as toplam_satis,
        COALESCE(SUM(toplam_kar), 0) as toplam_kar,
        COUNT(*) as toplam_siparis,
        COALESCE(AVG(toplam_tutar), 0) as ortalama_siparis
    FROM sales
")->fetch();

// ── 2. GÜNLÜK SATIŞ VE KÂR (SON 7 GÜN) ──
// Grafik (bar chart) çizmek için günlük bazda satış ve kâr toplamları
// DATE(tarih): Zaman damgasından sadece tarihi alır
$gunluk = $db->query("
    SELECT
        DATE(tarih) as tarih,
        COALESCE(SUM(toplam_tutar), 0) as toplam_tutar,
        COALESCE(SUM(toplam_kar), 0) as toplam_kar,
        COUNT(*) as siparis_sayisi
    FROM sales
    WHERE tarih >= DATE_SUB(CURDATE(), INTERVAL 7 DAY)
    GROUP BY DATE(tarih)
    ORDER BY tarih ASC
")->fetchAll();

// ── 3. SON SATIŞLAR (TABLO İÇİN) ──
// En son yapılan 20 satış kaydını müşteri bilgisiyle birlikte getir
// LEFT JOIN: Müşterisi olmayan (misafir) satışları da gösterir
$sonSatislar = $db->query("
    SELECT
        s.id,
        COALESCE(c.isim, 'Misafir') as musteri,
        s.toplam_tutar,
        s.toplam_kar,
        s.tarih
    FROM sales s
    LEFT JOIN customers c ON s.customer_id = c.id
    ORDER BY s.tarih DESC
    LIMIT 20
")->fetchAll();

// ── 4. KATEGORİ BAZLI KÂR ANALİZİ ──
// Hangi kategoriden ne kadar satış yapılmış ve ne kadar kâr edilmiş
// 3 tablo birleştiriliyor: sale_items → inventory → products
$kategoriler = $db->query("
    SELECT
        p.kategori,
        COUNT(si.id) as satis_adedi,
        COALESCE(SUM(si.kar), 0) as toplam_kar
    FROM sale_items si
    JOIN inventory i ON si.inventory_id = i.id
    JOIN products p ON i.product_id = p.id
    GROUP BY p.kategori
    ORDER BY toplam_kar DESC
")->fetchAll();

// ── JSON YANIT DÖNDÜR ──
jsonResponse([
    'success'       => true,
    'ozet'          => $ozet,           // Genel istatistikler
    'gunluk'        => $gunluk,         // Grafik verisi
    'son_satislar'  => $sonSatislar,    // Satış tablosu
    'kategoriler'   => $kategoriler,    // Kategori analizi
]);

