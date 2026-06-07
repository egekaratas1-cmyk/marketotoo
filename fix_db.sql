DROP DATABASE IF EXISTS akilli_market;
CREATE DATABASE akilli_market CHARACTER SET utf8mb4 COLLATE utf8mb4_turkish_ci;
USE akilli_market;
SOURCE C:/Users/Egeka/OneDrive/Masaüstü/marketoto{internetprog]/database.sql;

CREATE TABLE admins (
    id INT AUTO_INCREMENT PRIMARY KEY,
    isim VARCHAR(100) NOT NULL,
    kullanici_adi VARCHAR(50) NOT NULL UNIQUE,
    sifre VARCHAR(255) NOT NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB;

INSERT INTO admins (isim, kullanici_adi, sifre) VALUES ('Admin', 'admin', '$2y$10$JcsZcQO/Mv5L5n2/zeG93O/homcDBYiXdlY2PwbsPY8.LhXE62MQ.');
