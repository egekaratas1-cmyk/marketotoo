<?php
// ============================================
// API: ADMİN İŞLEMLERİ (CRUD - Ekle/Güncelle/Sil/Listele)
// ============================================
require_once 'auth.php';
require_once 'config.php';

header('Content-Type: application/json; charset=utf-8');

// Admin kontrolü
if (!isAdminLoggedIn()) {
    jsonResponse(['error' => 'Yetkisiz erişim. Lütfen giriş yapın.'], 403);
}

// GET istekleri (Listeleme)
if ($_SERVER['REQUEST_METHOD'] === 'GET') {
    $action = $_GET['action'] ?? '';
    $db = getDB();

    switch ($action) {
        case 'list_products':
            $products = $db->query("SELECT * FROM products ORDER BY urun_adi")->fetchAll();
            jsonResponse(['success' => true, 'products' => $products]);
            break;

        case 'list_inventory':
            $inventory = $db->query("
                SELECT i.*, p.urun_adi, p.kategori, p.barkod, m.isim as market_isim, m.sube,
                       DATEDIFF(i.son_kullanma_tarihi, CURDATE()) as kalan_gun
                FROM inventory i
                JOIN products p ON i.product_id = p.id
                JOIN markets m ON i.market_id = m.id
                WHERE i.stok_miktari > 0
                ORDER BY i.son_kullanma_tarihi ASC
            ")->fetchAll();
            jsonResponse(['success' => true, 'inventory' => $inventory]);
            break;

        case 'list_customers':
            $customers = $db->query("SELECT * FROM customers ORDER BY kayit_tarihi DESC")->fetchAll();
            jsonResponse(['success' => true, 'customers' => $customers]);
            break;

        case 'get_product':
            $id = intval($_GET['id'] ?? 0);
            $stmt = $db->prepare("SELECT * FROM products WHERE id = :id");
            $stmt->execute([':id' => $id]);
            $product = $stmt->fetch();
            if ($product) {
                jsonResponse(['success' => true, 'product' => $product]);
            } else {
                jsonResponse(['error' => 'Ürün bulunamadı.'], 404);
            }
            break;

        case 'get_inventory':
            $id = intval($_GET['id'] ?? 0);
            $stmt = $db->prepare("
                SELECT i.*, p.urun_adi, m.isim as market_isim
                FROM inventory i
                JOIN products p ON i.product_id = p.id
                JOIN markets m ON i.market_id = m.id
                WHERE i.id = :id
            ");
            $stmt->execute([':id' => $id]);
            $inv = $stmt->fetch();
            if ($inv) {
                jsonResponse(['success' => true, 'inventory' => $inv]);
            } else {
                jsonResponse(['error' => 'Envanter kaydı bulunamadı.'], 404);
            }
            break;

        case 'get_customer':
            $id = intval($_GET['id'] ?? 0);
            $stmt = $db->prepare("SELECT * FROM customers WHERE id = :id");
            $stmt->execute([':id' => $id]);
            $customer = $stmt->fetch();
            if ($customer) {
                jsonResponse(['success' => true, 'customer' => $customer]);
            } else {
                jsonResponse(['error' => 'Müşteri bulunamadı.'], 404);
            }
            break;

        default:
            jsonResponse(['error' => 'Bilinmeyen GET işlemi: ' . $action], 400);
    }
    exit;
}

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    jsonResponse(['error' => 'Geçersiz istek.'], 405);
}

$action = $_POST['action'] ?? '';
$db = getDB();

switch ($action) {
    // ==========================================
    // ÜRÜN İŞLEMLERİ
    // ==========================================

    // ---- ÜRÜN EKLE ----
    case 'add_product':
        $barkod = trim($_POST['barkod'] ?? '');
        $urunAdi = trim($_POST['urun_adi'] ?? '');
        $kategori = trim($_POST['kategori'] ?? '');
        $birim = trim($_POST['birim'] ?? 'Adet');

        if (empty($barkod) || empty($urunAdi) || empty($kategori)) {
            jsonResponse(['error' => 'Barkod, ürün adı ve kategori zorunludur.'], 400);
        }

        $check = $db->prepare("SELECT id FROM products WHERE barkod = :barkod");
        $check->execute([':barkod' => $barkod]);
        if ($check->fetch()) {
            jsonResponse(['error' => 'Bu barkod numarası zaten kayıtlı!'], 409);
        }

        $stmt = $db->prepare("INSERT INTO products (barkod, urun_adi, kategori, birim) VALUES (:barkod, :urun_adi, :kategori, :birim)");
        $stmt->execute([
            ':barkod'   => $barkod,
            ':urun_adi' => $urunAdi,
            ':kategori' => $kategori,
            ':birim'    => $birim,
        ]);

        jsonResponse([
            'success' => true,
            'message' => 'Ürün başarıyla eklendi.',
            'product_id' => $db->lastInsertId(),
        ]);
        break;

    // ---- ÜRÜN GÜNCELLE ----
    case 'update_product':
        $id = intval($_POST['id'] ?? 0);
        $barkod = trim($_POST['barkod'] ?? '');
        $urunAdi = trim($_POST['urun_adi'] ?? '');
        $kategori = trim($_POST['kategori'] ?? '');
        $birim = trim($_POST['birim'] ?? 'Adet');

        if ($id <= 0) {
            jsonResponse(['error' => 'Geçersiz ürün ID.'], 400);
        }
        if (empty($barkod) || empty($urunAdi) || empty($kategori)) {
            jsonResponse(['error' => 'Barkod, ürün adı ve kategori zorunludur.'], 400);
        }

        // Başka üründe aynı barkod var mı?
        $check = $db->prepare("SELECT id FROM products WHERE barkod = :barkod AND id != :id");
        $check->execute([':barkod' => $barkod, ':id' => $id]);
        if ($check->fetch()) {
            jsonResponse(['error' => 'Bu barkod başka bir ürüne ait!'], 409);
        }

        $stmt = $db->prepare("UPDATE products SET barkod = :barkod, urun_adi = :urun_adi, kategori = :kategori, birim = :birim WHERE id = :id");
        $stmt->execute([
            ':id'       => $id,
            ':barkod'   => $barkod,
            ':urun_adi' => $urunAdi,
            ':kategori' => $kategori,
            ':birim'    => $birim,
        ]);

        jsonResponse(['success' => true, 'message' => 'Ürün başarıyla güncellendi.']);
        break;

    // ---- ÜRÜN SİL ----
    case 'delete_product':
        $id = intval($_POST['id'] ?? 0);
        if ($id <= 0) {
            jsonResponse(['error' => 'Geçersiz ürün ID.'], 400);
        }

        // Envanterde bu ürün var mı kontrol et
        $check = $db->prepare("SELECT COUNT(*) as cnt FROM inventory WHERE product_id = :id");
        $check->execute([':id' => $id]);
        $count = $check->fetch()['cnt'];
        if ($count > 0) {
            jsonResponse(['error' => "Bu ürüne ait $count envanter kaydı var. Önce envanter kayıtlarını silin."], 400);
        }

        $stmt = $db->prepare("DELETE FROM products WHERE id = :id");
        $stmt->execute([':id' => $id]);

        if ($stmt->rowCount() > 0) {
            jsonResponse(['success' => true, 'message' => 'Ürün başarıyla silindi.']);
        } else {
            jsonResponse(['error' => 'Ürün bulunamadı.'], 404);
        }
        break;

    // ==========================================
    // ENVANTER İŞLEMLERİ
    // ==========================================

    // ---- ENVANTER EKLE ----
    case 'add_inventory':
        $marketId = intval($_POST['market_id'] ?? 0);
        $productId = intval($_POST['product_id'] ?? 0);
        $alisFiyati = floatval($_POST['alis_fiyati'] ?? 0);
        $satisFiyati = floatval($_POST['satis_fiyati'] ?? 0);
        $indirimli = $_POST['indirimli_fiyat'] ?? '';
        $skt = trim($_POST['son_kullanma_tarihi'] ?? '');
        $stok = intval($_POST['stok_miktari'] ?? 0);

        if ($marketId <= 0 || $productId <= 0) {
            jsonResponse(['error' => 'Market ve ürün seçimi zorunludur.'], 400);
        }
        if ($alisFiyati <= 0 || $satisFiyati <= 0) {
            jsonResponse(['error' => 'Fiyatlar 0\'dan büyük olmalıdır.'], 400);
        }
        if (empty($skt)) {
            jsonResponse(['error' => 'Son kullanma tarihi zorunludur.'], 400);
        }
        if ($stok <= 0) {
            jsonResponse(['error' => 'Stok miktarı 0\'dan büyük olmalıdır.'], 400);
        }

        $stmt = $db->prepare("
            INSERT INTO inventory (market_id, product_id, alis_fiyati, satis_fiyati, indirimli_fiyat, son_kullanma_tarihi, stok_miktari)
            VALUES (:market_id, :product_id, :alis, :satis, :indirimli, :skt, :stok)
        ");
        $stmt->execute([
            ':market_id'  => $marketId,
            ':product_id' => $productId,
            ':alis'       => $alisFiyati,
            ':satis'      => $satisFiyati,
            ':indirimli'  => ($indirimli !== '' && $indirimli > 0) ? floatval($indirimli) : null,
            ':skt'        => $skt,
            ':stok'       => $stok,
        ]);

        jsonResponse([
            'success' => true,
            'message' => 'Envanter kaydı eklendi.',
            'inventory_id' => $db->lastInsertId(),
        ]);
        break;

    // ---- ENVANTER GÜNCELLE ----
    case 'update_inventory':
        $id = intval($_POST['id'] ?? 0);
        $alisFiyati = floatval($_POST['alis_fiyati'] ?? 0);
        $satisFiyati = floatval($_POST['satis_fiyati'] ?? 0);
        $indirimli = $_POST['indirimli_fiyat'] ?? '';
        $skt = trim($_POST['son_kullanma_tarihi'] ?? '');
        $stok = intval($_POST['stok_miktari'] ?? 0);

        if ($id <= 0) {
            jsonResponse(['error' => 'Geçersiz envanter ID.'], 400);
        }
        if ($alisFiyati <= 0 || $satisFiyati <= 0) {
            jsonResponse(['error' => 'Fiyatlar 0\'dan büyük olmalıdır.'], 400);
        }
        if (empty($skt)) {
            jsonResponse(['error' => 'Son kullanma tarihi zorunludur.'], 400);
        }
        if ($stok < 0) {
            jsonResponse(['error' => 'Stok miktarı negatif olamaz.'], 400);
        }

        $stmt = $db->prepare("
            UPDATE inventory SET
                alis_fiyati = :alis,
                satis_fiyati = :satis,
                indirimli_fiyat = :indirimli,
                son_kullanma_tarihi = :skt,
                stok_miktari = :stok
            WHERE id = :id
        ");
        $stmt->execute([
            ':id'        => $id,
            ':alis'      => $alisFiyati,
            ':satis'     => $satisFiyati,
            ':indirimli' => ($indirimli !== '' && $indirimli > 0) ? floatval($indirimli) : null,
            ':skt'       => $skt,
            ':stok'      => $stok,
        ]);

        jsonResponse(['success' => true, 'message' => 'Envanter kaydı güncellendi.']);
        break;

    // ---- ENVANTER SİL ----
    case 'delete_inventory':
        $id = intval($_POST['id'] ?? 0);
        if ($id <= 0) {
            jsonResponse(['error' => 'Geçersiz envanter ID.'], 400);
        }

        // Satış kayıtlarında kullanılıyor mu?
        $check = $db->prepare("SELECT COUNT(*) as cnt FROM sale_items WHERE inventory_id = :id");
        $check->execute([':id' => $id]);
        $count = $check->fetch()['cnt'];
        if ($count > 0) {
            jsonResponse(['error' => "Bu envanter kaydına ait $count satış bulunuyor. Silinemez."], 400);
        }

        $stmt = $db->prepare("DELETE FROM inventory WHERE id = :id");
        $stmt->execute([':id' => $id]);

        if ($stmt->rowCount() > 0) {
            jsonResponse(['success' => true, 'message' => 'Envanter kaydı silindi.']);
        } else {
            jsonResponse(['error' => 'Envanter kaydı bulunamadı.'], 404);
        }
        break;

    // ==========================================
    // MÜŞTERİ İŞLEMLERİ
    // ==========================================

    // ---- MÜŞTERİ GÜNCELLE ----
    case 'update_customer':
        $id = intval($_POST['id'] ?? 0);
        $isim = trim($_POST['isim'] ?? '');
        $telefon = trim($_POST['telefon'] ?? '');
        $email = trim($_POST['email'] ?? '');

        if ($id <= 0) {
            jsonResponse(['error' => 'Geçersiz müşteri ID.'], 400);
        }
        if (mb_strlen($isim) < 2) {
            jsonResponse(['error' => 'İsim en az 2 karakter olmalıdır.'], 400);
        }
        if (!preg_match('/^05\d{9}$/', $telefon)) {
            jsonResponse(['error' => 'Geçerli bir telefon numarası girin (05XXXXXXXXX).'], 400);
        }
        if ($email && !filter_var($email, FILTER_VALIDATE_EMAIL)) {
            jsonResponse(['error' => 'Geçerli bir e-posta adresi girin.'], 400);
        }

        // Başka müşteride aynı telefon var mı?
        $check = $db->prepare("SELECT id FROM customers WHERE telefon_numarasi = :telefon AND id != :id");
        $check->execute([':telefon' => $telefon, ':id' => $id]);
        if ($check->fetch()) {
            jsonResponse(['error' => 'Bu telefon numarası başka bir müşteriye ait!'], 409);
        }

        $stmt = $db->prepare("UPDATE customers SET isim = :isim, telefon_numarasi = :telefon, email = :email WHERE id = :id");
        $stmt->execute([
            ':id'      => $id,
            ':isim'    => $isim,
            ':telefon' => $telefon,
            ':email'   => $email ?: null,
        ]);

        jsonResponse(['success' => true, 'message' => 'Müşteri bilgileri güncellendi.']);
        break;

    // ---- MÜŞTERİ SİL ----
    case 'delete_customer':
        $id = intval($_POST['id'] ?? 0);
        if ($id <= 0) {
            jsonResponse(['error' => 'Geçersiz müşteri ID.'], 400);
        }

        $stmt = $db->prepare("DELETE FROM customers WHERE id = :id");
        $stmt->execute([':id' => $id]);

        if ($stmt->rowCount() > 0) {
            jsonResponse(['success' => true, 'message' => 'Müşteri silindi.']);
        } else {
            jsonResponse(['error' => 'Müşteri bulunamadı.'], 404);
        }
        break;

    default:
        jsonResponse(['error' => 'Bilinmeyen işlem: ' . $action], 400);
}
