CREATE TABLE IF NOT EXISTS storefront_orders (
  id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
  order_number VARCHAR(40) NOT NULL UNIQUE,
  customer_first_name VARCHAR(120) NOT NULL,
  customer_last_name VARCHAR(120) NOT NULL,
  customer_email VARCHAR(190) NOT NULL,
  customer_phone VARCHAR(40) NOT NULL,
  delivery_address TEXT NOT NULL,
  delivery_city VARCHAR(120) NOT NULL,
  delivery_note TEXT NULL,
  subtotal INT UNSIGNED NOT NULL DEFAULT 0,
  delivery_fee INT UNSIGNED NOT NULL DEFAULT 0,
  total_amount INT UNSIGNED NOT NULL DEFAULT 0,
  currency_code VARCHAR(8) NOT NULL DEFAULT '952',
  payment_method VARCHAR(40) NOT NULL,
  payment_channel VARCHAR(60) NULL,
  payment_status VARCHAR(30) NOT NULL DEFAULT 'unpaid',
  order_status VARCHAR(30) NOT NULL DEFAULT 'pending',
  payment_reference VARCHAR(80) NULL UNIQUE,
  payment_provider VARCHAR(40) NULL,
  paid_at DATETIME NULL,
  created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
  updated_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  INDEX idx_storefront_orders_email (customer_email),
  INDEX idx_storefront_orders_payment_status (payment_status),
  INDEX idx_storefront_orders_status (order_status)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS storefront_order_items (
  id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
  order_id BIGINT UNSIGNED NOT NULL,
  product_id INT UNSIGNED NOT NULL,
  product_name VARCHAR(255) NOT NULL,
  quantity INT UNSIGNED NOT NULL,
  unit_price INT UNSIGNED NOT NULL,
  line_total INT UNSIGNED NOT NULL,
  created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
  CONSTRAINT fk_storefront_items_order FOREIGN KEY (order_id) REFERENCES storefront_orders(id) ON DELETE CASCADE,
  INDEX idx_storefront_items_order (order_id)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS storefront_payment_events (
  id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
  order_id BIGINT UNSIGNED NULL,
  provider VARCHAR(40) NOT NULL,
  reference_number VARCHAR(80) NULL,
  response_code VARCHAR(20) NULL,
  amount INT UNSIGNED NULL,
  payload_json LONGTEXT NOT NULL,
  created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
  CONSTRAINT fk_storefront_payment_event_order FOREIGN KEY (order_id) REFERENCES storefront_orders(id) ON DELETE SET NULL,
  INDEX idx_storefront_payment_event_reference (reference_number)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
