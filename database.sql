-- ================================================================
-- database.sql — LuxCarry Cybersecurity Research Platform
-- Run this in phpMyAdmin or via: mysql -u root < database.sql
-- ================================================================

CREATE DATABASE IF NOT EXISTS `luxury-handbag-website`
    CHARACTER SET utf8mb4
    COLLATE utf8mb4_unicode_ci;

USE `luxury-handbag-website`;

-- ── Users ────────────────────────────────────────────────────────
CREATE TABLE IF NOT EXISTS users (
    UserID        INT AUTO_INCREMENT PRIMARY KEY,
    Username      VARCHAR(50)  NOT NULL UNIQUE,
    PasswordHash  VARCHAR(255) NOT NULL,
    Email         VARCHAR(100) NOT NULL UNIQUE,
    IsMFAEnabled  BOOLEAN      NOT NULL DEFAULT FALSE,
    MFASecretKey  VARCHAR(255) DEFAULT NULL,
    LoginAttempts INT          NOT NULL DEFAULT 0,
    LockedUntil   DATETIME     DEFAULT NULL,       -- Lockout expiry
    ResetToken    VARCHAR(64)  DEFAULT NULL,        -- Password reset
    ResetExpires  DATETIME     DEFAULT NULL,
    CreatedAt     TIMESTAMP    NOT NULL DEFAULT CURRENT_TIMESTAMP,

    INDEX idx_username (Username),
    INDEX idx_email    (Email),
    INDEX idx_reset    (ResetToken)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ── Products ─────────────────────────────────────────────────────
CREATE TABLE IF NOT EXISTS products (
    ProductID   INT AUTO_INCREMENT PRIMARY KEY,
    ProductName VARCHAR(100)   NOT NULL,
    Price       DECIMAL(10,2)  NOT NULL,
    Description TEXT           DEFAULT NULL,
    Image       VARCHAR(255)   DEFAULT NULL,
    Stock       INT            NOT NULL DEFAULT 100,
    CreatedAt   TIMESTAMP      NOT NULL DEFAULT CURRENT_TIMESTAMP,

    INDEX idx_name (ProductName)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ── Orders ───────────────────────────────────────────────────────
CREATE TABLE IF NOT EXISTS orders (
    OrderID   INT AUTO_INCREMENT PRIMARY KEY,
    UserID    INT            NOT NULL,
    Total     DECIMAL(10,2)  NOT NULL,
    Status    ENUM('pending','paid','shipped','delivered','cancelled')
              NOT NULL DEFAULT 'pending',
    CreatedAt TIMESTAMP      NOT NULL DEFAULT CURRENT_TIMESTAMP,

    FOREIGN KEY (UserID) REFERENCES users(UserID) ON DELETE CASCADE,
    INDEX idx_user (UserID)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ── Order Items ──────────────────────────────────────────────────
CREATE TABLE IF NOT EXISTS order_items (
    OrderItemID INT AUTO_INCREMENT PRIMARY KEY,
    OrderID     INT           NOT NULL,
    ProductID   INT           NOT NULL,
    Quantity    INT           NOT NULL DEFAULT 1,
    UnitPrice   DECIMAL(10,2) NOT NULL,

    FOREIGN KEY (OrderID)   REFERENCES orders(OrderID)   ON DELETE CASCADE,
    FOREIGN KEY (ProductID) REFERENCES products(ProductID) ON DELETE RESTRICT,
    INDEX idx_order   (OrderID),
    INDEX idx_product (ProductID)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ================================================================
-- SEED DATA
-- ================================================================

-- Demo products (luxury handbag catalog)
INSERT INTO products (ProductName, Price, Description, Image) VALUES
('Classic Noir Tote',        1250.00, 'Handcrafted Italian calfskin with gold hardware.',        NULL),
('Signature Quilted Flap',    890.00, 'Timeless quilted lambskin with chain strap.',             NULL),
('Mini Crossbody',            540.00, 'Compact pebbled leather for everyday elegance.',          NULL),
('Heritage Bucket Bag',       720.00, 'Supple drawstring bucket in vegetable-tanned leather.',   NULL),
('Executive Briefcase',      1480.00, 'Full-grain leather briefcase with laptop compartment.',   NULL),
('Evening Clutch',            380.00, 'Satin-finish micro bag with crystal clasp.',              NULL);

-- ================================================================
-- DEMO USERS (for research / testing)
-- Passwords hashed with bcrypt cost 12
--
-- demo_user    password: demo123
-- mfa_user     password: mfa123      (MFA enabled — set key via app)
-- no_mfa_user  password: nomfa123
-- ================================================================

-- NOTE: Replace these hashes if you regenerate them.
-- Generated with: password_hash('demo123', PASSWORD_BCRYPT, ['cost'=>12])
INSERT INTO users (Username, Email, PasswordHash, IsMFAEnabled) VALUES
('demo_user',   'demo@luxcarry.test',   '$2y$12$UypN3DdlVb9s.7iWZVyFDusRQFbBfx6FNWUf8TnTT1TNTe5KLQXQG', FALSE),
('no_mfa_user', 'nomfa@luxcarry.test',  '$2y$12$UypN3DdlVb9s.7iWZVyFDusRQFbBfx6FNWUf8TnTT1TNTe5KLQXQG', FALSE),
('mfa_user',    'mfa@luxcarry.test',    '$2y$12$UypN3DdlVb9s.7iWZVyFDusRQFbBfx6FNWUf8TnTT1TNTe5KLQXQG', FALSE);

-- NOTE: To enable MFA for mfa_user:
--   1. Log in as mfa_user
--   2. Go to /user/enable_mfa.php
--   3. Scan QR code with Google Authenticator
--   4. The MFASecretKey column will be populated automatically
