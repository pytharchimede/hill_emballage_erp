CREATE TABLE IF NOT EXISTS payment_gateway_settings (
  id INT AUTO_INCREMENT PRIMARY KEY,
  provider VARCHAR(50) NOT NULL UNIQUE,
  enabled TINYINT(1) NOT NULL DEFAULT 1,
  merchant_id VARCHAR(100) NOT NULL,
  environment VARCHAR(20) NOT NULL DEFAULT 'production',
  api_base_url VARCHAR(255) NULL,
  notification_url VARCHAR(255) NULL,
  return_url VARCHAR(255) NULL,
  currency_code VARCHAR(10) NOT NULL DEFAULT '952',
  allow_cash_on_delivery TINYINT(1) NOT NULL DEFAULT 1,
  created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
  updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
);

INSERT INTO payment_gateway_settings (
  provider, enabled, merchant_id, environment, currency_code, allow_cash_on_delivery
) VALUES (
  'paiement_pro', 1, 'PP-F92731', 'production', '952', 1
) ON DUPLICATE KEY UPDATE
  merchant_id = VALUES(merchant_id),
  allow_cash_on_delivery = VALUES(allow_cash_on_delivery);

CREATE TABLE IF NOT EXISTS payment_transactions (
  id BIGINT AUTO_INCREMENT PRIMARY KEY,
  sale_id INT NULL,
  provider VARCHAR(50) NOT NULL,
  payment_method VARCHAR(50) NOT NULL,
  internal_reference VARCHAR(100) NOT NULL UNIQUE,
  provider_reference VARCHAR(150) NULL UNIQUE,
  payer_account VARCHAR(150) NULL,
  amount DECIMAL(15,2) NOT NULL,
  currency VARCHAR(10) NOT NULL DEFAULT 'XOF',
  status VARCHAR(40) NOT NULL DEFAULT 'pending',
  provider_response_code VARCHAR(50) NULL,
  provider_hash VARCHAR(255) NULL,
  delivery_authorized TINYINT(1) NOT NULL DEFAULT 0,
  raw_request LONGTEXT NULL,
  raw_callback LONGTEXT NULL,
  paid_at DATETIME NULL,
  created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
  updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  INDEX idx_payment_transactions_sale_id (sale_id),
  INDEX idx_payment_transactions_status (status)
);

ALTER TABLE sales
  ADD COLUMN IF NOT EXISTS payment_method VARCHAR(50) NULL AFTER type_vente,
  ADD COLUMN IF NOT EXISTS payment_status VARCHAR(40) NOT NULL DEFAULT 'unpaid' AFTER payment_method,
  ADD COLUMN IF NOT EXISTS delivery_authorized TINYINT(1) NOT NULL DEFAULT 0 AFTER payment_status;
