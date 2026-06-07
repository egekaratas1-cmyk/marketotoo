-- ============================================
-- MARKETOTO - VERİTABANI
-- ============================================

CREATE DATABASE IF NOT EXISTS akilli_market
  CHARACTER SET utf8mb4
  COLLATE utf8mb4_turkish_ci;

USE akilli_market;

-- ---------- MARKETLER ----------
DROP TABLE IF EXISTS sale_items;
DROP TABLE IF EXISTS sales;
DROP TABLE IF EXISTS inventory;
DROP TABLE IF EXISTS products;
DROP TABLE IF EXISTS customers;
DROP TABLE IF EXISTS markets;

CREATE TABLE markets (
    id INT AUTO_INCREMENT PRIMARY KEY,
    isim VARCHAR(100) NOT NULL,
    sube VARCHAR(150) NOT NULL,
    adres VARCHAR(255) DEFAULT NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB;

-- ---------- MÜŞTERİLER ----------
CREATE TABLE customers (
    id INT AUTO_INCREMENT PRIMARY KEY,
    isim VARCHAR(100) NOT NULL,
    telefon_numarasi VARCHAR(15) NOT NULL UNIQUE,
    email VARCHAR(150) DEFAULT NULL,
    kayit_tarihi TIMESTAMP DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB;

-- ---------- ÜRÜNLER ----------
CREATE TABLE products (
    id INT AUTO_INCREMENT PRIMARY KEY,
    barkod VARCHAR(50) NOT NULL UNIQUE,
    urun_adi VARCHAR(150) NOT NULL,
    kategori VARCHAR(80) NOT NULL,
    birim VARCHAR(30) DEFAULT 'Adet',
    resim_url VARCHAR(255) DEFAULT NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB;

-- ---------- ENVANTER / STOK ----------
CREATE TABLE inventory (
    id INT AUTO_INCREMENT PRIMARY KEY,
    market_id INT NOT NULL,
    product_id INT NOT NULL,
    alis_fiyati DECIMAL(10,2) NOT NULL,
    satis_fiyati DECIMAL(10,2) NOT NULL,
    indirimli_fiyat DECIMAL(10,2) DEFAULT NULL,
    son_kullanma_tarihi DATE NOT NULL,
    stok_miktari INT NOT NULL DEFAULT 0,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (market_id) REFERENCES markets(id) ON DELETE CASCADE,
    FOREIGN KEY (product_id) REFERENCES products(id) ON DELETE CASCADE
) ENGINE=InnoDB;

-- ---------- SATIŞLAR ----------
CREATE TABLE sales (
    id INT AUTO_INCREMENT PRIMARY KEY,
    customer_id INT DEFAULT NULL,
    toplam_tutar DECIMAL(10,2) NOT NULL,
    toplam_kar DECIMAL(10,2) NOT NULL DEFAULT 0,
    tarih TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (customer_id) REFERENCES customers(id) ON DELETE SET NULL
) ENGINE=InnoDB;

-- ---------- SATIŞ DETAYLARI ----------
CREATE TABLE sale_items (
    id INT AUTO_INCREMENT PRIMARY KEY,
    sale_id INT NOT NULL,
    inventory_id INT NOT NULL,
    miktar INT NOT NULL DEFAULT 1,
    birim_fiyat DECIMAL(10,2) NOT NULL,
    kar DECIMAL(10,2) NOT NULL DEFAULT 0,
    FOREIGN KEY (sale_id) REFERENCES sales(id) ON DELETE CASCADE,
    FOREIGN KEY (inventory_id) REFERENCES inventory(id) ON DELETE CASCADE
) ENGINE=InnoDB;


-- ============================================
-- ÖRNEK VERİLER
-- ============================================

-- MARKETLER
INSERT INTO markets (isim, sube, adres) VALUES
('A101',  'Kadıköy Şubesi',     'Kadıköy Merkez Mahallesi No:12, İstanbul'),
('BİM',   'Beşiktaş Şubesi',    'Beşiktaş Caddesi No:45, İstanbul'),
('ŞOK',   'Üsküdar Şubesi',     'Üsküdar Meydanı Sokak No:7, İstanbul'),
('Migros','Bakırköy Şubesi',     'Bakırköy AVM No:3, İstanbul'),
('CarrefourSA','Ataşehir Şubesi','Ataşehir Bulvarı No:88, İstanbul'),
('A101',  'Maltepe Şubesi',      'Maltepe Sahil Yolu No:22, İstanbul'),
('BİM',   'Fatih Şubesi',        'Fatih Millet Caddesi No:9, İstanbul'),
('Migros','Çankaya Şubesi',      'Çankaya Tunalı Hilmi Cad. No:55, Ankara'),
('ŞOK',   'Bornova Şubesi',      'Bornova Kazım Karabekir No:18, İzmir'),
('CarrefourSA','Nilüfer Şubesi', 'Nilüfer Özlüce AVM No:5, Bursa');

-- ÜRÜNLER
INSERT INTO products (barkod, urun_adi, kategori, birim, resim_url) VALUES
('8690000000001', 'Tam Yağlı Süt 1L',         'Süt Ürünleri',    'Adet', 'https://img.icons8.com/color/96/milk.png'),
('8690000000002', 'Beyaz Peynir 500g',         'Süt Ürünleri',    'Adet', 'https://img.icons8.com/color/96/cheese.png'),
('8690000000003', 'Yoğurt 1kg',               'Süt Ürünleri',    'Adet', 'https://img.icons8.com/color/96/yogurt.png'),
('8690000000004', 'Kaşar Peynir 350g',         'Süt Ürünleri',    'Adet', 'https://img.icons8.com/color/96/cheese.png'),
('8690000000005', 'Tavuk Göğüs 1kg',          'Et & Tavuk',      'kg',   'https://img.icons8.com/color/96/chicken-leg.png'),
('8690000000006', 'Dana Kıyma 500g',           'Et & Tavuk',      'Adet', 'https://img.icons8.com/color/96/steak.png'),
('8690000000007', 'Domates 1kg',               'Meyve & Sebze',   'kg',   'https://img.icons8.com/color/96/tomato.png'),
('8690000000008', 'Salatalık 1kg',             'Meyve & Sebze',   'kg',   'https://img.icons8.com/color/96/cucumber.png'),
('8690000000009', 'Elma 1kg',                  'Meyve & Sebze',   'kg',   'https://img.icons8.com/color/96/apple.png'),
('8690000000010', 'Muz 1kg',                   'Meyve & Sebze',   'kg',   'https://img.icons8.com/color/96/banana.png'),
('8690000000011', 'Ekmek 1 Adet',              'Fırın',           'Adet', 'https://img.icons8.com/color/96/bread.png'),
('8690000000012', 'Makarna 500g',              'Temel Gıda',      'Adet', 'https://img.icons8.com/color/96/spaghetti.png'),
('8690000000013', 'Pirinç 1kg',               'Temel Gıda',      'Adet', 'https://img.icons8.com/color/96/rice-bowl.png'),
('8690000000014', 'Ayçiçek Yağı 1L',          'Temel Gıda',      'Adet', 'https://img.icons8.com/color/96/olive-oil.png'),
('8690000000015', 'Toz Şeker 1kg',            'Temel Gıda',      'Adet', 'https://img.icons8.com/color/96/sugar-cubes.png'),
('8690000000016', 'Çay 1kg',                   'İçecekler',       'Adet', 'https://img.icons8.com/color/96/tea.png'),
('8690000000017', 'Türk Kahvesi 250g',         'İçecekler',       'Adet', 'https://img.icons8.com/color/96/coffee-beans.png'),
('8690000000018', 'Maden Suyu 6lı',           'İçecekler',       'Adet', 'https://img.icons8.com/color/96/water-bottle.png'),
('8690000000019', 'Deterjan 4kg',              'Temizlik',        'Adet', 'https://img.icons8.com/color/96/washing-machine.png'),
('8690000000020', 'Bulaşık Tableti 40lı',     'Temizlik',        'Adet', 'https://img.icons8.com/color/96/soap.png'),
('8690000000021', 'Zeytin 500g',               'Kahvaltılık',     'Adet', 'https://img.icons8.com/color/96/olive.png'),
('8690000000022', 'Bal 450g',                  'Kahvaltılık',     'Adet', 'https://img.icons8.com/color/96/honey.png'),
('8690000000023', 'Tereyağı 250g',            'Süt Ürünleri',    'Adet', 'https://img.icons8.com/color/96/butter.png'),
('8690000000024', 'Ton Balığı 160g',          'Konserve',        'Adet', 'https://img.icons8.com/color/96/fish-food.png'),
('8690000000025', 'Ketçap 600g',               'Sos & Baharat',   'Adet', 'https://img.icons8.com/color/96/ketchup.png'),
('8690000000026', 'Kola 1L',                   'İçecekler',       'Adet', 'https://img.icons8.com/color/96/cola.png'),
('8690000000027', 'Portakal Suyu 1L',          'İçecekler',       'Adet', 'https://img.icons8.com/color/96/orange-juice.png'),
('8690000000028', 'Çikolata 80g',              'Atıştırmalık',    'Adet', 'https://img.icons8.com/color/96/chocolate-bar.png'),
('8690000000029', 'Cips 150g',                 'Atıştırmalık',    'Adet', 'https://img.icons8.com/color/96/nachos.png'),
('8690000000030', 'Bisküvi 200g',              'Atıştırmalık',    'Adet', 'https://img.icons8.com/color/96/cookies.png'),
('8690000000031', 'Şampuan 500ml',             'Kişisel Bakım',   'Adet', 'https://img.icons8.com/color/96/shampoo.png'),
('8690000000032', 'Diş Macunu 100ml',          'Kişisel Bakım',   'Adet', 'https://img.icons8.com/color/96/toothpaste.png'),
('8690000000033', 'Yumurta 15li',              'Kahvaltılık',     'Adet', 'https://img.icons8.com/color/96/eggs.png'),
('8690000000034', 'Margarin 250g',             'Temel Gıda',      'Adet', 'https://img.icons8.com/color/96/butter.png'),
('8690000000035', 'Tavuk But 1kg',             'Et & Tavuk',      'kg',   'https://img.icons8.com/color/96/chicken-leg.png'),
('8690000000036', 'Limon 1kg',                 'Meyve & Sebze',   'kg',   'https://img.icons8.com/color/96/lemon.png'),
('8690000000037', 'Patates 2kg',               'Meyve & Sebze',   'kg',   'https://img.icons8.com/color/96/potato.png'),
('8690000000038', 'Soğan 1kg',                 'Meyve & Sebze',   'kg',   'https://img.icons8.com/color/96/onion.png'),
('8690000000039', 'Havuç 1kg',                 'Meyve & Sebze',   'kg',   'https://img.icons8.com/color/96/carrot.png'),
('8690000000040', 'Nohut 1kg',                 'Temel Gıda',      'Adet', 'https://img.icons8.com/color/96/peanuts.png');

-- ENVANTER (Farklı marketlerde, farklı fiyatlarla, farklı SKT)
-- A101 (market_id = 1)
INSERT INTO inventory (market_id, product_id, alis_fiyati, satis_fiyati, indirimli_fiyat, son_kullanma_tarihi, stok_miktari) VALUES
(1, 1,  18.00, 24.90, NULL,  DATE_ADD(CURDATE(), INTERVAL 10 DAY), 50),
(1, 2,  45.00, 64.90, NULL,  DATE_ADD(CURDATE(), INTERVAL 3 DAY),  20),
(1, 3,  22.00, 32.50, 27.90, DATE_ADD(CURDATE(), INTERVAL 2 DAY),  35),
(1, 4,  55.00, 79.90, NULL,  DATE_ADD(CURDATE(), INTERVAL 15 DAY), 25),
(1, 5,  85.00, 119.90, 99.90, DATE_ADD(CURDATE(), INTERVAL 1 DAY), 10),
(1, 7,  8.00,  14.90, NULL,  DATE_ADD(CURDATE(), INTERVAL 5 DAY),  80),
(1, 9,  12.00, 19.90, NULL,  DATE_ADD(CURDATE(), INTERVAL 7 DAY),  60),
(1, 11, 5.00,  8.50,  NULL,  DATE_ADD(CURDATE(), INTERVAL 1 DAY),  100),
(1, 12, 12.00, 18.90, 14.90, DATE_ADD(CURDATE(), INTERVAL 60 DAY), 200),
(1, 14, 65.00, 89.90, NULL,  DATE_ADD(CURDATE(), INTERVAL 90 DAY), 40),
(1, 16, 120.00, 169.90, NULL, DATE_ADD(CURDATE(), INTERVAL 180 DAY), 30),
(1, 21, 30.00, 44.90, NULL,  DATE_ADD(CURDATE(), INTERVAL 20 DAY), 45),
(1, 23, 40.00, 59.90, 49.90, DATE_ADD(CURDATE(), INTERVAL 4 DAY),  15),
(1, 24, 18.00, 27.90, NULL,  DATE_ADD(CURDATE(), INTERVAL 120 DAY), 70),
(1, 25, 22.00, 34.90, NULL,  DATE_ADD(CURDATE(), INTERVAL 90 DAY), 55);

-- BİM (market_id = 2)
INSERT INTO inventory (market_id, product_id, alis_fiyati, satis_fiyati, indirimli_fiyat, son_kullanma_tarihi, stok_miktari) VALUES
(2, 1,  17.50, 22.90, NULL,  DATE_ADD(CURDATE(), INTERVAL 8 DAY),  45),
(2, 2,  42.00, 59.90, 49.90, DATE_ADD(CURDATE(), INTERVAL 2 DAY),  15),
(2, 3,  20.00, 29.90, NULL,  DATE_ADD(CURDATE(), INTERVAL 6 DAY),  40),
(2, 5,  82.00, 114.90, NULL, DATE_ADD(CURDATE(), INTERVAL 3 DAY),  12),
(2, 6,  95.00, 139.90, 119.90, DATE_ADD(CURDATE(), INTERVAL 2 DAY), 8),
(2, 8,  6.00,  11.90, NULL,  DATE_ADD(CURDATE(), INTERVAL 4 DAY),  90),
(2, 10, 25.00, 39.90, NULL,  DATE_ADD(CURDATE(), INTERVAL 3 DAY),  30),
(2, 11, 4.50,  7.50,  NULL,  DATE_ADD(CURDATE(), INTERVAL 1 DAY),  120),
(2, 12, 11.00, 16.90, NULL,  DATE_ADD(CURDATE(), INTERVAL 45 DAY), 180),
(2, 13, 35.00, 49.90, NULL,  DATE_ADD(CURDATE(), INTERVAL 120 DAY), 60),
(2, 15, 28.00, 39.90, NULL,  DATE_ADD(CURDATE(), INTERVAL 200 DAY), 100),
(2, 17, 50.00, 74.90, 64.90, DATE_ADD(CURDATE(), INTERVAL 5 DAY),  20),
(2, 19, 85.00, 124.90, NULL, DATE_ADD(CURDATE(), INTERVAL 365 DAY), 25),
(2, 22, 70.00, 99.90, NULL,  DATE_ADD(CURDATE(), INTERVAL 60 DAY), 18),
(2, 25, 20.00, 32.90, 28.90, DATE_ADD(CURDATE(), INTERVAL 3 DAY),  40);

-- ŞOK (market_id = 3)
INSERT INTO inventory (market_id, product_id, alis_fiyati, satis_fiyati, indirimli_fiyat, son_kullanma_tarihi, stok_miktari) VALUES
(3, 1,  17.00, 23.50, 19.90, DATE_ADD(CURDATE(), INTERVAL 2 DAY),  30),
(3, 3,  21.00, 31.90, NULL,  DATE_ADD(CURDATE(), INTERVAL 5 DAY),  50),
(3, 4,  52.00, 74.90, NULL,  DATE_ADD(CURDATE(), INTERVAL 10 DAY), 20),
(3, 5,  80.00, 109.90, NULL, DATE_ADD(CURDATE(), INTERVAL 4 DAY),  18),
(3, 7,  7.50,  12.90, NULL,  DATE_ADD(CURDATE(), INTERVAL 3 DAY),  100),
(3, 9,  11.00, 17.90, 14.90, DATE_ADD(CURDATE(), INTERVAL 2 DAY),  70),
(3, 10, 24.00, 37.90, NULL,  DATE_ADD(CURDATE(), INTERVAL 5 DAY),  25),
(3, 11, 4.00,  7.00,  NULL,  DATE_ADD(CURDATE(), INTERVAL 1 DAY),  150),
(3, 13, 33.00, 47.90, NULL,  DATE_ADD(CURDATE(), INTERVAL 90 DAY), 55),
(3, 14, 62.00, 84.90, 74.90, DATE_ADD(CURDATE(), INTERVAL 30 DAY), 35),
(3, 16, 115.00, 159.90, NULL, DATE_ADD(CURDATE(), INTERVAL 150 DAY), 28),
(3, 18, 18.00, 26.90, NULL,  DATE_ADD(CURDATE(), INTERVAL 90 DAY), 80),
(3, 20, 55.00, 79.90, NULL,  DATE_ADD(CURDATE(), INTERVAL 180 DAY), 15),
(3, 22, 68.00, 94.90, 84.90, DATE_ADD(CURDATE(), INTERVAL 8 DAY),  12),
(3, 23, 38.00, 54.90, NULL,  DATE_ADD(CURDATE(), INTERVAL 6 DAY),  22);

-- Migros (market_id = 4)
INSERT INTO inventory (market_id, product_id, alis_fiyati, satis_fiyati, indirimli_fiyat, son_kullanma_tarihi, stok_miktari) VALUES
(4, 1,  19.00, 26.90, NULL,  DATE_ADD(CURDATE(), INTERVAL 12 DAY), 60),
(4, 2,  48.00, 69.90, NULL,  DATE_ADD(CURDATE(), INTERVAL 7 DAY),  30),
(4, 4,  58.00, 84.90, NULL,  DATE_ADD(CURDATE(), INTERVAL 20 DAY), 18),
(4, 6,  98.00, 144.90, NULL, DATE_ADD(CURDATE(), INTERVAL 5 DAY),  10),
(4, 7,  9.00,  16.90, NULL,  DATE_ADD(CURDATE(), INTERVAL 6 DAY),  70),
(4, 8,  7.00,  13.90, NULL,  DATE_ADD(CURDATE(), INTERVAL 5 DAY),  65),
(4, 10, 26.00, 42.90, NULL,  DATE_ADD(CURDATE(), INTERVAL 4 DAY),  20),
(4, 12, 13.00, 19.90, NULL,  DATE_ADD(CURDATE(), INTERVAL 50 DAY), 150),
(4, 14, 68.00, 94.90, NULL,  DATE_ADD(CURDATE(), INTERVAL 100 DAY), 50),
(4, 15, 30.00, 42.90, NULL,  DATE_ADD(CURDATE(), INTERVAL 250 DAY), 90),
(4, 17, 55.00, 79.90, NULL,  DATE_ADD(CURDATE(), INTERVAL 30 DAY), 25),
(4, 19, 88.00, 129.90, NULL, DATE_ADD(CURDATE(), INTERVAL 300 DAY), 20),
(4, 21, 32.00, 49.90, NULL,  DATE_ADD(CURDATE(), INTERVAL 25 DAY), 35),
(4, 24, 20.00, 29.90, NULL,  DATE_ADD(CURDATE(), INTERVAL 150 DAY), 60),
(4, 25, 24.00, 37.90, NULL,  DATE_ADD(CURDATE(), INTERVAL 80 DAY), 45);

-- CarrefourSA (market_id = 5)
INSERT INTO inventory (market_id, product_id, alis_fiyati, satis_fiyati, indirimli_fiyat, son_kullanma_tarihi, stok_miktari) VALUES
(5, 2,  44.00, 62.90, NULL,  DATE_ADD(CURDATE(), INTERVAL 5 DAY),  25),
(5, 3,  23.00, 34.90, NULL,  DATE_ADD(CURDATE(), INTERVAL 4 DAY),  30),
(5, 5,  88.00, 124.90, 109.90, DATE_ADD(CURDATE(), INTERVAL 2 DAY), 8),
(5, 6,  96.00, 142.90, NULL, DATE_ADD(CURDATE(), INTERVAL 3 DAY),  12),
(5, 8,  6.50,  12.90, NULL,  DATE_ADD(CURDATE(), INTERVAL 3 DAY),  85),
(5, 9,  13.00, 21.90, NULL,  DATE_ADD(CURDATE(), INTERVAL 8 DAY),  50),
(5, 11, 5.50,  9.00,  NULL,  DATE_ADD(CURDATE(), INTERVAL 1 DAY),  80),
(5, 13, 36.00, 52.90, NULL,  DATE_ADD(CURDATE(), INTERVAL 100 DAY), 40),
(5, 15, 29.00, 41.90, NULL,  DATE_ADD(CURDATE(), INTERVAL 220 DAY), 75),
(5, 16, 122.00, 174.90, NULL, DATE_ADD(CURDATE(), INTERVAL 200 DAY), 22),
(5, 18, 19.00, 28.90, NULL,  DATE_ADD(CURDATE(), INTERVAL 100 DAY), 65),
(5, 20, 58.00, 84.90, NULL,  DATE_ADD(CURDATE(), INTERVAL 200 DAY), 10),
(5, 21, 31.00, 46.90, NULL,  DATE_ADD(CURDATE(), INTERVAL 18 DAY), 40),
(5, 23, 42.00, 62.90, NULL,  DATE_ADD(CURDATE(), INTERVAL 8 DAY),  18),
(5, 24, 19.00, 28.90, 24.90, DATE_ADD(CURDATE(), INTERVAL 10 DAY), 55);

-- A101 Maltepe (market_id = 6)
INSERT INTO inventory (market_id, product_id, alis_fiyati, satis_fiyati, indirimli_fiyat, son_kullanma_tarihi, stok_miktari) VALUES
(6, 1,  17.50, 23.90, NULL,  DATE_ADD(CURDATE(), INTERVAL 9 DAY),  40),
(6, 5,  83.00, 115.90, 99.90, DATE_ADD(CURDATE(), INTERVAL 2 DAY), 8),
(6, 7,  8.50,  15.50, NULL,  DATE_ADD(CURDATE(), INTERVAL 4 DAY),  60),
(6, 11, 4.80,  7.90,  NULL,  DATE_ADD(CURDATE(), INTERVAL 1 DAY),  90),
(6, 26, 8.00,  13.90, NULL,  DATE_ADD(CURDATE(), INTERVAL 14 DAY), 50),
(6, 28, 6.00,  9.90,  NULL,  DATE_ADD(CURDATE(), INTERVAL 30 DAY), 80),
(6, 33, 28.00, 39.90, NULL,  DATE_ADD(CURDATE(), INTERVAL 5 DAY),  25),
(6, 36, 6.00,  10.90, NULL,  DATE_ADD(CURDATE(), INTERVAL 6 DAY),  70),
(6, 37, 8.00,  14.90, NULL,  DATE_ADD(CURDATE(), INTERVAL 10 DAY), 45),
(6, 40, 22.00, 34.90, 29.90, DATE_ADD(CURDATE(), INTERVAL 90 DAY), 30);

-- BİM Fatih (market_id = 7)
INSERT INTO inventory (market_id, product_id, alis_fiyati, satis_fiyati, indirimli_fiyat, son_kullanma_tarihi, stok_miktari) VALUES
(7, 2,  43.00, 61.90, 54.90, DATE_ADD(CURDATE(), INTERVAL 3 DAY),  20),
(7, 3,  21.00, 30.90, NULL,  DATE_ADD(CURDATE(), INTERVAL 5 DAY),  35),
(7, 8,  5.50,  10.90, NULL,  DATE_ADD(CURDATE(), INTERVAL 3 DAY),  75),
(7, 12, 11.50, 17.90, NULL,  DATE_ADD(CURDATE(), INTERVAL 50 DAY), 150),
(7, 27, 12.00, 19.90, NULL,  DATE_ADD(CURDATE(), INTERVAL 7 DAY),  30),
(7, 29, 10.00, 16.90, NULL,  DATE_ADD(CURDATE(), INTERVAL 20 DAY), 55),
(7, 30, 5.00,  8.90,  NULL,  DATE_ADD(CURDATE(), INTERVAL 25 DAY), 100),
(7, 31, 28.00, 44.90, NULL,  DATE_ADD(CURDATE(), INTERVAL 180 DAY), 18),
(7, 35, 68.00, 94.90, 79.90, DATE_ADD(CURDATE(), INTERVAL 2 DAY),  12),
(7, 38, 5.00,  9.90,  NULL,  DATE_ADD(CURDATE(), INTERVAL 8 DAY),  85),
(7, 39, 7.00,  12.90, NULL,  DATE_ADD(CURDATE(), INTERVAL 6 DAY),  60);

-- Migros Çankaya (market_id = 8)
INSERT INTO inventory (market_id, product_id, alis_fiyati, satis_fiyati, indirimli_fiyat, son_kullanma_tarihi, stok_miktari) VALUES
(8, 1,  19.50, 27.90, NULL,  DATE_ADD(CURDATE(), INTERVAL 11 DAY), 55),
(8, 4,  57.00, 82.90, NULL,  DATE_ADD(CURDATE(), INTERVAL 18 DAY), 20),
(8, 6,  97.00, 142.90, NULL, DATE_ADD(CURDATE(), INTERVAL 4 DAY),  10),
(8, 9,  13.00, 22.90, NULL,  DATE_ADD(CURDATE(), INTERVAL 5 DAY),  45),
(8, 14, 67.00, 92.90, NULL,  DATE_ADD(CURDATE(), INTERVAL 95 DAY), 40),
(8, 17, 53.00, 77.90, NULL,  DATE_ADD(CURDATE(), INTERVAL 28 DAY), 22),
(8, 26, 9.00,  15.90, NULL,  DATE_ADD(CURDATE(), INTERVAL 10 DAY), 60),
(8, 28, 7.00,  11.90, 9.90,  DATE_ADD(CURDATE(), INTERVAL 5 DAY),  40),
(8, 32, 18.00, 29.90, NULL,  DATE_ADD(CURDATE(), INTERVAL 200 DAY), 30),
(8, 34, 15.00, 24.90, NULL,  DATE_ADD(CURDATE(), INTERVAL 60 DAY), 50),
(8, 37, 7.50,  13.90, NULL,  DATE_ADD(CURDATE(), INTERVAL 12 DAY), 55);

-- ŞOK Bornova (market_id = 9)
INSERT INTO inventory (market_id, product_id, alis_fiyati, satis_fiyati, indirimli_fiyat, son_kullanma_tarihi, stok_miktari) VALUES
(9, 1,  16.50, 21.90, 18.90, DATE_ADD(CURDATE(), INTERVAL 1 DAY),  20),
(9, 5,  79.00, 107.90, NULL, DATE_ADD(CURDATE(), INTERVAL 3 DAY),  15),
(9, 10, 23.00, 36.90, NULL,  DATE_ADD(CURDATE(), INTERVAL 4 DAY),  35),
(9, 13, 34.00, 48.90, NULL,  DATE_ADD(CURDATE(), INTERVAL 100 DAY), 50),
(9, 15, 27.00, 38.90, NULL,  DATE_ADD(CURDATE(), INTERVAL 180 DAY), 80),
(9, 19, 83.00, 119.90, NULL, DATE_ADD(CURDATE(), INTERVAL 300 DAY), 20),
(9, 27, 11.00, 17.90, NULL,  DATE_ADD(CURDATE(), INTERVAL 6 DAY),  40),
(9, 29, 9.50,  14.90, NULL,  DATE_ADD(CURDATE(), INTERVAL 15 DAY), 65),
(9, 33, 26.00, 37.90, 32.90, DATE_ADD(CURDATE(), INTERVAL 3 DAY),  18),
(9, 36, 5.50,  9.90,  NULL,  DATE_ADD(CURDATE(), INTERVAL 5 DAY),  80),
(9, 39, 6.50,  11.90, NULL,  DATE_ADD(CURDATE(), INTERVAL 7 DAY),  55);

-- CarrefourSA Nilüfer (market_id = 10)
INSERT INTO inventory (market_id, product_id, alis_fiyati, satis_fiyati, indirimli_fiyat, son_kullanma_tarihi, stok_miktari) VALUES
(10, 2,  46.00, 65.90, NULL,  DATE_ADD(CURDATE(), INTERVAL 6 DAY),  22),
(10, 6,  94.00, 138.90, 119.90, DATE_ADD(CURDATE(), INTERVAL 2 DAY), 7),
(10, 9,  12.50, 20.90, NULL,  DATE_ADD(CURDATE(), INTERVAL 7 DAY),  40),
(10, 16, 118.00, 168.90, NULL, DATE_ADD(CURDATE(), INTERVAL 190 DAY), 25),
(10, 20, 56.00, 82.90, NULL,  DATE_ADD(CURDATE(), INTERVAL 190 DAY), 12),
(10, 22, 72.00, 102.90, NULL, DATE_ADD(CURDATE(), INTERVAL 55 DAY), 15),
(10, 26, 8.50,  14.50, NULL,  DATE_ADD(CURDATE(), INTERVAL 12 DAY), 70),
(10, 30, 4.50,  7.90,  NULL,  DATE_ADD(CURDATE(), INTERVAL 30 DAY), 90),
(10, 31, 30.00, 47.90, NULL,  DATE_ADD(CURDATE(), INTERVAL 150 DAY), 20),
(10, 35, 70.00, 99.90, NULL, DATE_ADD(CURDATE(), INTERVAL 3 DAY),  10),
(10, 38, 4.50,  8.90,  NULL,  DATE_ADD(CURDATE(), INTERVAL 9 DAY),  70),
(10, 40, 23.00, 36.90, NULL, DATE_ADD(CURDATE(), INTERVAL 100 DAY), 35);

-- ÖRNEK MÜŞTERİLER
INSERT INTO customers (isim, telefon_numarasi, email) VALUES
('Ahmet Yılmaz',  '05321234567', 'ahmet@example.com'),
('Fatma Demir',   '05339876543', 'fatma@example.com'),
('Mehmet Kaya',   '05411112233', NULL),
('Ayşe Çelik',   '05527778899', 'ayse@example.com'),
('Ali Öztürk',   '05066665544', NULL);

-- ÖRNEK SATIŞLAR
INSERT INTO sales (customer_id, toplam_tutar, toplam_kar, tarih) VALUES
(1, 156.80, 42.30, DATE_SUB(NOW(), INTERVAL 3 DAY)),
(2, 234.50, 65.20, DATE_SUB(NOW(), INTERVAL 2 DAY)),
(3, 89.70,  22.10, DATE_SUB(NOW(), INTERVAL 1 DAY)),
(1, 312.40, 88.50, NOW()),
(4, 178.90, 49.60, NOW());

INSERT INTO sale_items (sale_id, inventory_id, miktar, birim_fiyat, kar) VALUES
(1, 1, 2, 24.90, 13.80),
(1, 9, 3, 18.90, 20.70),
(1, 8, 1, 8.50,  3.50),
(1, 6, 1, 14.90, 6.90),
(2, 16, 2, 22.90, 10.80),
(2, 17, 1, 29.90, 9.90),
(2, 20, 1, 16.90, 5.90),
(2, 24, 3, 49.90, 44.70),
(3, 23, 1, 7.50,  3.00),
(3, 22, 2, 11.90, 11.80),
(3, 26, 1, 39.90, 11.90),
(4, 1,  3, 24.90, 20.70),
(4, 5,  1, 99.90, 14.90),
(4, 10, 2, 89.90, 49.80),
(4, 15, 1, 34.90, 12.90),
(5, 31, 1, 23.50, 6.50),
(5, 34, 2, 109.90, 59.80),
(5, 38, 1, 7.00,  3.00);
