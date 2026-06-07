<?php
// ============================================
// MARKETOTO - ÖRNEK YAPILANDIRMA DOSYASI
// ============================================
// Bu dosyayı kopyalayıp 'config.php' olarak yeniden adlandırın
// ve kendi veritabanı bilgilerinizi girin.
// config.php dosyası .gitignore ile repo'ya eklenmez.
// ============================================

// Veritabanı sunucu adresi
define('DB_HOST', 'localhost');

// Veritabanı adı
define('DB_NAME', 'akilli_market');

// Veritabanı kullanıcı adı
define('DB_USER', 'root');

// Veritabanı şifresi
define('DB_PASS', '');

// Karakter seti
define('DB_CHARSET', 'utf8mb4');

function getDB() {
    static $pdo = null;
    if ($pdo === null) {
        try {
            $dsn = "mysql:host=" . DB_HOST . ";dbname=" . DB_NAME . ";charset=" . DB_CHARSET;
            $options = [
                PDO::ATTR_ERRMODE            => PDO::ERRMODE_EXCEPTION,
                PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
                PDO::ATTR_EMULATE_PREPARES   => false,
            ];
            $pdo = new PDO($dsn, DB_USER, DB_PASS, $options);
            $pdo->exec("SET NAMES 'utf8mb4' COLLATE 'utf8mb4_turkish_ci'");
        } catch (PDOException $e) {
            die(json_encode(['error' => 'Veritabanı bağlantı hatası: ' . $e->getMessage()]));
        }
    }
    return $pdo;
}

function jsonResponse($data, $statusCode = 200) {
    http_response_code($statusCode);
    header('Content-Type: application/json; charset=utf-8');
    echo json_encode($data, JSON_UNESCAPED_UNICODE);
    exit;
}

function calculateDiscount($expiryDate) {
    $today = new DateTime();
    $expiry = new DateTime($expiryDate);
    $diff = $today->diff($expiry)->days;
    $isPast = $expiry < $today;

    if ($isPast) return 0;
    if ($diff <= 1) return 50;
    if ($diff <= 3) return 35;
    if ($diff <= 5) return 25;
    if ($diff <= 7) return 15;
    if ($diff <= 14) return 10;
    return 0;
}

function getEffectivePrice($satisFiyati, $indirimlieFiyat, $expiryDate) {
    if ($indirimlieFiyat !== null && $indirimlieFiyat > 0) {
        return $indirimlieFiyat;
    }
    $discount = calculateDiscount($expiryDate);
    if ($discount > 0) {
        return round($satisFiyati * (1 - $discount / 100), 2);
    }
    return $satisFiyati;
}
