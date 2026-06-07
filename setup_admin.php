<?php
// Admin tablosu oluştur ve varsayılan admin ekle
require_once 'config.php';

$db = getDB();

// Admins tablosu oluştur
$db->exec("
    CREATE TABLE IF NOT EXISTS admins (
        id INT AUTO_INCREMENT PRIMARY KEY,
        kullanici_adi VARCHAR(50) NOT NULL UNIQUE,
        sifre VARCHAR(255) NOT NULL,
        isim VARCHAR(100) NOT NULL,
        created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
    ) ENGINE=InnoDB
");

// Varsayılan admin ekle (admin / admin123)
$hash = password_hash('admin123', PASSWORD_DEFAULT);
$stmt = $db->prepare("INSERT IGNORE INTO admins (kullanici_adi, sifre, isim) VALUES (:user, :pass, :isim)");
$stmt->execute([
    ':user' => 'admin',
    ':pass' => $hash,
    ':isim' => 'Yönetici',
]);

echo "Admin tablosu oluşturuldu!\n";
echo "Kullanıcı: admin\n";
echo "Şifre: admin123\n";
