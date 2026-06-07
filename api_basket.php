<?php
// ============================================
// API: AKILLI SEPET HESAPLAMA ENDPOINT'İ (api_basket.php)
// ============================================
//
// Kullanıcının sepetindeki ürünler için tüm marketlerdeki fiyatları
// karşılaştırır ve en uygun alışveriş stratejisini hesaplar.
//
// İKİ STRATEJİ HESAPLANIR:
//   1. KARMA SEPET: Her ürünü en ucuz marketten al (farklı marketlerden)
//   2. TEK MARKET SEPETLERİ: Tüm ürünleri tek marketten alsan ne olur?
//
// KULLANIM: POST /api_basket.php
//   Body (JSON): { "products": ["Süt 1L", "Ekmek 1 Adet", ...] }
//
// DÖNDÜRÜR (JSON):
//   {
//     "success": true,
//     "karma_sepet": { urunler, toplam },
//     "tek_market_sepetler": [ { market, urunler, toplam, eksik } ],
//     "tasarruf": 12.50   ← Karma sepet ile en pahalı market arasındaki fark
//   }
// ============================================
require_once 'config.php';

header('Content-Type: application/json; charset=utf-8');

// Sadece POST isteklerine izin ver
if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    jsonResponse(['error' => 'Geçersiz istek metodu.'], 405);
}

// ── İSTEK VERİSİNİ OKU ──
// Frontend JSON gövdesi gönderir, php://input ile okuyoruz
$input = json_decode(file_get_contents('php://input'), true);
$productNames = $input['products'] ?? []; // Sepetteki ürün adları dizisi

// Boş sepet kontrolü
if (empty($productNames)) {
    jsonResponse(['error' => 'Ürün listesi boş.'], 400);
}

$db = getDB();

// ── HER ÜRÜN İÇİN TÜM MARKETLERDEKİ FİYATLARI BUL ──
// Prepared statement için dinamik placeholder'lar oluştur (:name0, :name1, ...)
$placeholders = [];
$params = [];
foreach ($productNames as $i => $name) {
    $placeholders[] = ":name{$i}";
    $params[":name{$i}"] = $name;
}
$inClause = implode(',', $placeholders);

// 3 tabloyu birleştirerek ürünlerin tüm marketlerdeki fiyatlarını sorgula
// Sadece stokta olan ve süresi geçmemiş ürünler dahil edilir

$sql = "
    SELECT
        p.urun_adi,
        m.id as market_id,
        m.isim as market_isim,
        i.satis_fiyati,
        i.indirimli_fiyat,
        i.son_kullanma_tarihi,
        i.stok_miktari
    FROM inventory i
    JOIN products p ON i.product_id = p.id
    JOIN markets m ON i.market_id = m.id
    WHERE p.urun_adi IN ({$inClause})
      AND i.stok_miktari > 0
      AND i.son_kullanma_tarihi >= CURDATE()
    ORDER BY p.urun_adi, i.satis_fiyati ASC
";

$stmt = $db->prepare($sql);
$stmt->execute($params);
$rows = $stmt->fetchAll();

// ── SONUÇLARI ÜRÜN BAZLI ORGANIZE ET ──
// Her ürün adı için hangi marketlerde hangi fiyatla satıldığını haritalandır
// Yapı: { "ÜrünAdı": [ {market_id, market, fiyat, orijinal}, ... ] }
$productMarkets = [];
foreach ($rows as $row) {
    // Efektif fiyatı hesapla (indirimli veya SKT bazlı)
    $efektif = getEffectivePrice($row['satis_fiyati'], $row['indirimli_fiyat'], $row['son_kullanma_tarihi']);
    $key = $row['urun_adi'];

    if (!isset($productMarkets[$key])) {
        $productMarkets[$key] = [];
    }
    $productMarkets[$key][] = [
        'market_id'   => $row['market_id'],
        'market'      => $row['market_isim'],
        'fiyat'       => $efektif,
        'orijinal'    => $row['satis_fiyati'],
    ];
}

// ── STRATEJI 1: KARMA SEPET ──
// Her ürünü en ucuz olan marketten al (farklı marketlerden topla)
// Bu genellikle en ucuz seçenektir ama birden fazla markete gitmeyi gerektirir
$karmaSepet = [];
$karmaTotal = 0;

foreach ($productNames as $name) {
    if (isset($productMarkets[$name]) && count($productMarkets[$name]) > 0) {
        // En ucuz olanı seç
        usort($productMarkets[$name], fn($a, $b) => $a['fiyat'] <=> $b['fiyat']);
        $cheapest = $productMarkets[$name][0];
        $karmaSepet[] = [
            'urun_adi' => $name,
            'market'   => $cheapest['market'],
            'fiyat'    => $cheapest['fiyat'],
        ];
        $karmaTotal += $cheapest['fiyat'];
    } else {
        $karmaSepet[] = [
            'urun_adi' => $name,
            'market'   => 'Bulunamadı',
            'fiyat'    => 0,
        ];
    }
}

// ── STRATEJI 2: TEK MARKET SEPETLERİ ──
// Tüm ürünleri tek bir marketten alsan toplam ne kadar tutar?
// Her market için ayrı ayrı hesapla
$allMarkets = $db->query("SELECT id, isim FROM markets ORDER BY isim")->fetchAll();

$tekMarketSepetler = [];

foreach ($allMarkets as $market) {
    $marketId = $market['id'];
    $marketName = $market['isim'];
    $sepetUrunler = [];
    $toplam = 0;
    $eksik = 0;

    foreach ($productNames as $name) {
        $found = false;
        if (isset($productMarkets[$name])) {
            foreach ($productMarkets[$name] as $pm) {
                if ($pm['market_id'] == $marketId) {
                    $sepetUrunler[] = [
                        'urun_adi'   => $name,
                        'fiyat'      => $pm['fiyat'],
                        'bulunamadi' => false,
                    ];
                    $toplam += $pm['fiyat'];
                    $found = true;
                    break;
                }
            }
        }
        if (!$found) {
            $sepetUrunler[] = [
                'urun_adi'   => $name,
                'fiyat'      => 0,
                'bulunamadi' => true,
            ];
            $eksik++;
        }
    }

    $tekMarketSepetler[] = [
        'market'  => $marketName,
        'urunler' => $sepetUrunler,
        'toplam'  => $toplam,
        'eksik'   => $eksik,
    ];
}

// ── TEK MARKET SEPETLERİNİ SIRALA ──
// Öncelik: 1) Eksik ürünü az olan (tüm ürünleri bulunan market önce)
//          2) Toplam fiyatı düşük olan
usort($tekMarketSepetler, function($a, $b) {
    if ($a['eksik'] !== $b['eksik']) return $a['eksik'] - $b['eksik'];
    return $a['toplam'] <=> $b['toplam'];
});

// ── TASARRUF HESAPLA ──
// Karma sepet ile en pahalı tek market arasındaki farkı hesapla
// Bu, kullanıcıya "karma sepet kullanarak ne kadar tasarruf edebilirsiniz" gösterir
$enPahali = 0;
foreach ($tekMarketSepetler as $s) {
    // Sadece tüm ürünleri olan marketleri dikkate al
    if ($s['eksik'] === 0 && $s['toplam'] > $enPahali) {
        $enPahali = $s['toplam'];
    }
}
$tasarruf = $enPahali > 0 ? round($enPahali - $karmaTotal, 2) : 0;

// ── JSON YANIT DÖNDÜR ──
jsonResponse([
    'success' => true,
    'karma_sepet' => [                          // En ucuz karma kombinasyon
        'urunler' => $karmaSepet,
        'toplam'  => round($karmaTotal, 2),
    ],
    'tek_market_sepetler' => $tekMarketSepetler, // Her market için ayrı sepet
    'tasarruf' => $tasarruf,                     // Karma sepet tasarruf miktarı (₺)
]);
