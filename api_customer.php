<?php
// ============================================
// API: MÜŞTERİ KAYIT ENDPOINT'İ (api_customer.php)
// ============================================
//
// Yeni müşteri kaydı oluşturmak için kullanılan POST API'sidir.
// register.php sayfasındaki form bu endpoint'e istek gönderir.
//
// KULLANIM: POST /api_customer.php
//   Form verileri: isim, telefon, email (opsiyonel)
//
// DÖNDÜRÜR (JSON):
//   Başarılı → { "success": true, "customer": { id, isim, telefon, email } }
//   Hata     → { "error": "Hata mesajı" }
//
// DOĞRULAMA KURALLARI:
//   - İsim: minimum 2 karakter
//   - Telefon: 05XXXXXXXXX formatında, benzersiz olmalı
//   - Email: opsiyonel, geçerli formatta olmalı
// ============================================
require_once 'config.php';

header('Content-Type: application/json; charset=utf-8');

// Sadece POST isteklerine izin ver (GET ile müşteri kaydedilemez)
if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    jsonResponse(['error' => 'Geçersiz istek metodu.'], 405);
}

// ── FORM VERİLERİNİ OKU VE TEMİZLE ──
// trim(): Baştaki ve sondaki boşlukları temizler
$isim = trim($_POST['isim'] ?? '');
$telefon = trim($_POST['telefon'] ?? '');
$email = trim($_POST['email'] ?? '');

// ── GİRDİ DOĞRULAMA (VALIDATION) ──
$errors = [];

// İsim kontrolü: en az 2 karakter (tek harf isim olmaz)
if (mb_strlen($isim) < 2) {
    $errors[] = 'İsim en az 2 karakter olmalıdır.';
}

// Telefon kontrolü: Türkiye formatı 05XXXXXXXXX (11 haneli)
if (!preg_match('/^05\d{9}$/', $telefon)) {
    $errors[] = 'Geçerli bir telefon numarası girin (05XXXXXXXXX).';
}

// Email kontrolü: opsiyonel ama girilmişse geçerli formatta olmalı
if ($email && !filter_var($email, FILTER_VALIDATE_EMAIL)) {
    $errors[] = 'Geçerli bir e-posta adresi girin.';
}

// Doğrulama hataları varsa 400 Bad Request ile döndür
if (!empty($errors)) {
    jsonResponse(['error' => implode(' ', $errors)], 400);
}

$db = getDB();

// ── TELEFON NUMARASI TEKRARLILIK KONTROLÜ ──
// Aynı telefonla kayıtlı müşteri var mı diye kontrol et
$stmt = $db->prepare("SELECT id, isim FROM customers WHERE telefon_numarasi = :telefon");
$stmt->execute([':telefon' => $telefon]);
$existing = $stmt->fetch();

// Zaten varsa 409 Conflict hatası döndür
if ($existing) {
    jsonResponse([
        'error' => 'Bu telefon numarasıyla zaten bir müşteri kayıtlıdır: ' . $existing['isim']
    ], 409);
}

// ── YENİ MÜŞTERİYİ VERİTABANINA KAYDET ──
$stmt = $db->prepare("
    INSERT INTO customers (isim, telefon_numarasi, email)
    VALUES (:isim, :telefon, :email)
");

$stmt->execute([
    ':isim'    => $isim,
    ':telefon' => $telefon,
    ':email'   => $email ?: null,  // Boş string ise NULL olarak kaydet
]);

// Son eklenen kaydın ID'sini al
$customerId = $db->lastInsertId();

// ── BAŞARILI YANIT DÖNDÜR ──
jsonResponse([
    'success' => true,
    'customer' => [
        'id' => $customerId,
        'isim' => $isim,
        'telefon' => $telefon,
        'email' => $email,
    ]
]);
