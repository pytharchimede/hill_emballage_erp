CREATE TABLE IF NOT EXISTS storefront_customers (
  id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
  first_name VARCHAR(120) NOT NULL,
  last_name VARCHAR(120) NOT NULL,
  email VARCHAR(190) NULL,
  phone VARCHAR(40) NULL,
  password_hash VARCHAR(255) NOT NULL,
  erp_client_id INT NULL,
  is_active TINYINT(1) NOT NULL DEFAULT 1,
  created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
  updated_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  UNIQUE KEY uq_storefront_customer_email (email),
  UNIQUE KEY uq_storefront_customer_phone (phone),
  INDEX idx_storefront_customer_erp_client (erp_client_id)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS storefront_customer_sessions (
  id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
  customer_id BIGINT UNSIGNED NOT NULL,
  token_hash CHAR(64) NOT NULL UNIQUE,
  expires_at DATETIME NOT NULL,
  created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
  last_used_at DATETIME NULL,
  CONSTRAINT fk_storefront_session_customer FOREIGN KEY (customer_id) REFERENCES storefront_customers(id) ON DELETE CASCADE,
  INDEX idx_storefront_session_expiry (expires_at)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS storefront_customer_addresses (
  id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
  customer_id BIGINT UNSIGNED NOT NULL,
  label VARCHAR(80) NOT NULL DEFAULT 'Adresse',
  recipient_name VARCHAR(180) NOT NULL,
  phone VARCHAR(40) NOT NULL,
  address_line TEXT NOT NULL,
  city VARCHAR(120) NOT NULL,
  delivery_note TEXT NULL,
  is_default TINYINT(1) NOT NULL DEFAULT 0,
  created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
  updated_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  CONSTRAINT fk_storefront_address_customer FOREIGN KEY (customer_id) REFERENCES storefront_customers(id) ON DELETE CASCADE,
  INDEX idx_storefront_address_customer (customer_id)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

ALTER TABLE storefront_orders
  ADD COLUMN customer_id BIGINT UNSIGNED NULL AFTER id,
  ADD INDEX idx_storefront_orders_customer (customer_id),
  ADD CONSTRAINT fk_storefront_orders_customer FOREIGN KEY (customer_id) REFERENCES storefront_customers(id) ON DELETE SET NULL;
