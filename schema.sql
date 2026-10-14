-- Mini Project 3 - Inventory (Relational Database + Advanced PHP)
-- Jalankan: mysql -u root -p < database/schema.sql

CREATE DATABASE IF NOT EXISTS inventory_db
  CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;
USE inventory_db;

DROP TABLE IF EXISTS stock_transactions;
DROP TABLE IF EXISTS products;
DROP TABLE IF EXISTS categories;
DROP TABLE IF EXISTS users;

CREATE TABLE users (
  id            INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
  name          VARCHAR(100) NOT NULL,
  username      VARCHAR(50)  NOT NULL UNIQUE,
  password_hash VARCHAR(255) NOT NULL,
  role          ENUM('admin','staff') NOT NULL DEFAULT 'staff',
  created_at    TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB;

CREATE TABLE categories (
  id          INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
  name        VARCHAR(100) NOT NULL UNIQUE,
  description VARCHAR(255) NULL,
  created_at  TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB;

CREATE TABLE products (
  id          INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
  category_id INT UNSIGNED NOT NULL,
  sku         VARCHAR(30)  NOT NULL UNIQUE,
  name        VARCHAR(150) NOT NULL,
  price       DECIMAL(12,2) NOT NULL DEFAULT 0 CHECK (price >= 0),
  stock       INT NOT NULL DEFAULT 0 CHECK (stock >= 0),
  min_stock   INT NOT NULL DEFAULT 5 CHECK (min_stock >= 0),
  created_at  TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
  updated_at  TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  CONSTRAINT fk_products_category FOREIGN KEY (category_id)
    REFERENCES categories(id) ON UPDATE CASCADE ON DELETE RESTRICT,
  INDEX idx_products_name (name)
) ENGINE=InnoDB;

CREATE TABLE stock_transactions (
  id         INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
  product_id INT UNSIGNED NOT NULL,
  user_id    INT UNSIGNED NOT NULL,
  type       ENUM('in','out') NOT NULL,
  quantity   INT UNSIGNED NOT NULL CHECK (quantity > 0),
  note       VARCHAR(255) NULL,
  created_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
  CONSTRAINT fk_tx_product FOREIGN KEY (product_id) REFERENCES products(id)
    ON UPDATE CASCADE ON DELETE RESTRICT,
  CONSTRAINT fk_tx_user FOREIGN KEY (user_id) REFERENCES users(id)
    ON UPDATE CASCADE ON DELETE RESTRICT,
  INDEX idx_tx_created (created_at)
) ENGINE=InnoDB;

-- Data awal kategori & produk (user dibuat lewat: php database/seed.php)
INSERT INTO categories (name, description) VALUES
 ('Elektronik','Perangkat elektronik'),
 ('Alat Tulis','Perlengkapan menulis & kantor'),
 ('Minuman','Minuman kemasan');

INSERT INTO products (category_id, sku, name, price, stock, min_stock) VALUES
 (1,'ELK-001','Mouse Wireless',85000,20,5),
 (1,'ELK-002','Keyboard Mechanical',350000,8,5),
 (2,'ATK-001','Pulpen Hitam (box)',25000,50,10),
 (2,'ATK-002','Buku Tulis A5',8000,3,10),
 (3,'MNM-001','Air Mineral 600ml',4000,100,24);
