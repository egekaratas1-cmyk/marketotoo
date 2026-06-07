<?php
// ============================================
// MARKETOTO - GENEL YAPILANDIRMA DOSYASI (config.php)
// ============================================
//
// Bu dosya projenin temel yapılandırma ayarlarını içerir.
// Tüm PHP sayfaları bu dosyayı "require_once" ile dahil eder.
//
// İÇERİK:
//   1. Veritabanı bağlantı sabitleri (host, isim, kullanıcı, şifre)
//   2. PDO ile veritabanına bağlanan getDB() fonksiyonu
//   3. JSON API yanıtı döndüren jsonResponse() yardımcı fonksiyonu
//   4. Son kullanma tarihine (SKT) göre indirim hesaplayan calculateDiscount()
//   5. İndirimli veya SKT bazlı efektif fiyat hesaplayan getEffectivePrice()
// ============================================

// ── 1. VERİTABANI BAĞLANTI SABİTLERİ ──
// Railway ortam değişkenlerini önce kontrol et, yoksa yerel XAMPP değerlerini kullan
define('DB_HOST',    getenv('MYSQLHOST')     ?: 'localhost');
define('DB_PORT',    getenv('MYSQLPORT')     ?: '3306');
define('DB_NAME',    getenv('MYSQLDATABASE') ?: 'akilli_market');
define('DB_USER',    getenv('MYSQLUSER')     ?: 'root');
define('DB_PASS',    getenv('MYSQLPASSWORD') ?: '');
define('DB_CHARSET', 'utf8mb4');

// ── 2. VERİTABANI BAĞLANTI FONKSİYONU ──
// Singleton pattern ile tek bir PDO bağlantısı oluşturur.
// Her çağrıda yeni bağlantı açmaz, mevcut bağlantıyı döndürür.
// Döndürür: PDO nesnesi
function getDB() {
    static $pdo = null; // Statik değişken: fonksiyon çağrıları arasında kalıcı
    if ($pdo === null) {
        try {
            // DSN (Data Source Name): MySQL bağlantı dizesi
            $dsn = "mysql:host=" . DB_HOST . ";port=" . DB_PORT . ";dbname=" . DB_NAME . ";charset=" . DB_CHARSET;

            // PDO seçenekleri:
            // - ERRMODE_EXCEPTION: Hatalarda exception fırlatır (try/catch ile yakalarız)
            // - FETCH_ASSOC: Sorgu sonuçlarını ilişkisel dizi olarak döndürür
            // - EMULATE_PREPARES=false: Gerçek prepared statement kullanır (güvenlik)
            $options = [
                PDO::ATTR_ERRMODE            => PDO::ERRMODE_EXCEPTION,
                PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
                PDO::ATTR_EMULATE_PREPARES   => false,
            ];
            $pdo = new PDO($dsn, DB_USER, DB_PASS, $options);

            // Türkçe karakter desteği için collation ayarı
            $pdo->exec("SET NAMES 'utf8mb4' COLLATE 'utf8mb4_turkish_ci'");
        } catch (PDOException $e) {
            // Bağlantı hatası varsa JSON hata mesajı döndür ve çık
            die(json_encode(['error' => 'Veritabanı bağlantı hatası: ' . $e->getMessage()]));
        }
    }
    return $pdo;
}

// ── 3. JSON YANIT YARDIMCI FONKSİYONU ──
// API endpoint'lerinden tutarlı JSON yanıt döndürmek için kullanılır.
// Parametreler:
//   $data       - Döndürülecek veri (dizi/nesne)
//   $statusCode - HTTP durum kodu (varsayılan: 200 OK)
function jsonResponse($data, $statusCode = 200) {
    http_response_code($statusCode);
    header('Content-Type: application/json; charset=utf-8');
    // JSON_UNESCAPED_UNICODE: Türkçe karakterleri olduğu gibi bırakır (ö, ü, ş vb.)
    echo json_encode($data, JSON_UNESCAPED_UNICODE);
    exit; // Yanıt gönderildikten sonra scripti sonlandır
}

// ── 4. SKT İNDİRİM YÜZDESI HESAPLAMA ──
// Son kullanma tarihine kalan gün sayısına göre otomatik indirim yüzdesi belirler.
// Bu fonksiyon sayesinde SKT'si yaklaşan ürünler otomatik ucuzlar ve israf azalır.
//
// Kural tablosu:
//   Süresi geçmiş   → %0 (satışa uygun değil)
//   1 gün kalmış    → %50 indirim
//   2-3 gün kalmış  → %35 indirim
//   4-5 gün kalmış  → %25 indirim
//   6-7 gün kalmış  → %15 indirim
//   8-14 gün kalmış → %10 indirim
//   14+ gün kalmış  → %0 (indirim yok)
//
// Parametre: $expiryDate - Son kullanma tarihi (Y-m-d formatında string)
// Döndürür:  int - İndirim yüzdesi (0-50 arası)
function calculateDiscount($expiryDate) {
    $today = new DateTime();
    $expiry = new DateTime($expiryDate);
    $diff = $today->diff($expiry)->days;    // Kalan gün sayısı
    $isPast = $expiry < $today;              // Tarih geçmiş mi?

    if ($isPast) return 0;   // Süresi geçmiş ürünlerde indirim yok (satılamaz)
    if ($diff <= 1) return 50;
    if ($diff <= 3) return 35;
    if ($diff <= 5) return 25;
    if ($diff <= 7) return 15;
    if ($diff <= 14) return 10;
    return 0; // 14 günden fazla kalmış, indirim uygulanmaz
}

// ── 5. EFEKTİF (GERÇEKLEŞTİRİLEN) FİYAT HESAPLAMA ──
// Bir ürünün müşteriye gösterilen nihai fiyatını hesaplar.
// Öncelik sırası:
//   1. Elle girilmiş indirimli fiyat (admin panelden belirlenir)
//   2. SKT bazlı otomatik indirim (calculateDiscount fonksiyonu ile)
//   3. Normal satış fiyatı (hiç indirim yoksa)
//
// Parametreler:
//   $satisFiyati    - Ürünün normal satış fiyatı
//   $indirimlieFiyat - Admin tarafından girilen indirimli fiyat (null olabilir)
//   $expiryDate      - Son kullanma tarihi
// Döndürür: float - Müşteriye yansıyacak nihai fiyat
function getEffectivePrice($satisFiyati, $indirimlieFiyat, $expiryDate) {
    // 1. Önce elle girilmiş indirimli fiyat var mı kontrol et
    if ($indirimlieFiyat !== null && $indirimlieFiyat > 0) {
        return $indirimlieFiyat;
    }
    // 2. SKT bazlı otomatik indirim hesapla
    $discount = calculateDiscount($expiryDate);
    if ($discount > 0) {
        return round($satisFiyati * (1 - $discount / 100), 2);
    }
    // 3. Hiç indirim yoksa normal fiyatı döndür
    return $satisFiyati;
}

