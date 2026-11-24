-- =====================================================================
-- CloudCart database schema + sample data (MySQL 8 / Amazon RDS MySQL)
--
-- WARNING: running this script DROPS and re-creates all CloudCart tables,
-- so any existing data in them is lost.
--
-- Run it with:   mysql -u root -p < schema.sql
-- =====================================================================

CREATE DATABASE IF NOT EXISTS cloudcart
    CHARACTER SET utf8mb4
    COLLATE utf8mb4_unicode_ci;

USE cloudcart;

-- Drop in reverse order of dependency (children first)
DROP TABLE IF EXISTS sessions;
DROP TABLE IF EXISTS order_items;
DROP TABLE IF EXISTS orders;
DROP TABLE IF EXISTS cart_items;
DROP TABLE IF EXISTS products;
DROP TABLE IF EXISTS users;

-- ---------------------------------------------------------------------
-- users: customers and admins
-- ---------------------------------------------------------------------
CREATE TABLE users (
    id            INT AUTO_INCREMENT PRIMARY KEY,
    name          VARCHAR(100)  NOT NULL,
    email         VARCHAR(255)  NOT NULL UNIQUE,   -- UNIQUE prevents duplicate registrations
    password_hash VARCHAR(255)  NOT NULL,          -- never store plain passwords
    is_admin      TINYINT(1)    NOT NULL DEFAULT 0,
    created_at    TIMESTAMP     NOT NULL DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB;

-- ---------------------------------------------------------------------
-- products: the items for sale
-- image_url is either a path inside the public folder (e.g. images/mouse.svg)
-- or a full URL (later: an Amazon S3 URL).
-- ---------------------------------------------------------------------
CREATE TABLE products (
    id          INT AUTO_INCREMENT PRIMARY KEY,
    name        VARCHAR(150)   NOT NULL,
    description TEXT,
    price       DECIMAL(10,2)  NOT NULL,
    image_url   VARCHAR(255)   NOT NULL DEFAULT 'images/placeholder.svg',
    stock       INT            NOT NULL DEFAULT 0,
    created_at  TIMESTAMP      NOT NULL DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB;

-- ---------------------------------------------------------------------
-- cart_items: one row per (user, product); keeps the cart in the database
-- so it works on any EC2 instance behind the load balancer.
-- ---------------------------------------------------------------------
CREATE TABLE cart_items (
    id         INT AUTO_INCREMENT PRIMARY KEY,
    user_id    INT NOT NULL,
    product_id INT NOT NULL,
    quantity   INT NOT NULL DEFAULT 1,
    created_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
    UNIQUE KEY uq_cart_user_product (user_id, product_id),
    FOREIGN KEY (user_id)    REFERENCES users(id)    ON DELETE CASCADE,
    FOREIGN KEY (product_id) REFERENCES products(id) ON DELETE CASCADE
) ENGINE=InnoDB;

-- ---------------------------------------------------------------------
-- orders: one row per confirmed checkout
-- ---------------------------------------------------------------------
CREATE TABLE orders (
    id                   INT AUTO_INCREMENT PRIMARY KEY,
    user_id              INT           NOT NULL,
    total_amount         DECIMAL(10,2) NOT NULL,
    status               VARCHAR(30)   NOT NULL DEFAULT 'Confirmed',
    shipping_name        VARCHAR(100)  NOT NULL,
    shipping_address     VARCHAR(255)  NOT NULL,
    shipping_city        VARCHAR(100)  NOT NULL,
    shipping_postal_code VARCHAR(20)   NOT NULL,
    created_at           TIMESTAMP     NOT NULL DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (user_id) REFERENCES users(id)
) ENGINE=InnoDB;

-- ---------------------------------------------------------------------
-- order_items: the products inside each order.
-- The name and price are copied at purchase time, so old orders stay
-- correct even if the product is later edited or deleted.
-- ---------------------------------------------------------------------
CREATE TABLE order_items (
    id           INT AUTO_INCREMENT PRIMARY KEY,
    order_id     INT           NOT NULL,
    product_id   INT           NULL,
    product_name VARCHAR(150)  NOT NULL,
    price        DECIMAL(10,2) NOT NULL,
    quantity     INT           NOT NULL,
    FOREIGN KEY (order_id)   REFERENCES orders(id)   ON DELETE CASCADE,
    FOREIGN KEY (product_id) REFERENCES products(id) ON DELETE SET NULL
) ENGINE=InnoDB;

-- ---------------------------------------------------------------------
-- sessions: PHP login sessions are stored here (instead of in files on one
-- server) so that two EC2 instances behind a load balancer share them.
-- ---------------------------------------------------------------------
CREATE TABLE sessions (
    id          VARCHAR(128)  NOT NULL PRIMARY KEY,
    data        MEDIUMTEXT    NOT NULL,
    last_access INT UNSIGNED  NOT NULL
) ENGINE=InnoDB;

-- ---------------------------------------------------------------------
-- Sample products
-- ---------------------------------------------------------------------
INSERT INTO products (name, description, price, image_url, stock) VALUES
('Wireless Headphones', 'Over-ear Bluetooth headphones with deep bass, noise isolation and 30 hours of battery life.', 79.99, 'images/headphones.svg', 25),
('Smart Watch', 'Fitness tracker with heart-rate monitor, sleep tracking and a bright always-on display.', 129.50, 'images/smartwatch.svg', 15),
('Laptop Backpack', 'Water-resistant 15.6 inch laptop backpack with padded straps and a hidden anti-theft pocket.', 45.00, 'images/backpack.svg', 40),
('Bluetooth Speaker', 'Portable waterproof speaker with 360-degree sound and 12 hours of playtime.', 59.90, 'images/speaker.svg', 30),
('Mechanical Keyboard', 'Compact mechanical keyboard with tactile switches and blue backlighting.', 89.00, 'images/keyboard.svg', 20),
('Wireless Mouse', 'Ergonomic 2.4 GHz wireless mouse with adjustable DPI and silent clicks.', 24.99, 'images/mouse.svg', 50),
('USB-C Hub', '7-in-1 USB-C hub with HDMI, SD card reader, USB 3.0 ports and 100W power delivery.', 39.95, 'images/hub.svg', 35),
('LED Desk Lamp', 'Dimmable LED desk lamp with three colour modes and a built-in USB charging port.', 32.50, 'images/lamp.svg', 18);
