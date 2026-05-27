CREATE TABLE IF NOT EXISTS clients (
  client_id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
  client_name VARCHAR(120) NOT NULL,
  phone VARCHAR(40) NULL,
  email VARCHAR(190) NULL,
  line_user_id VARCHAR(190) NULL,
  line_display_name VARCHAR(190) NULL,
  brand_name VARCHAR(190) NULL,
  location_area VARCHAR(190) NULL,
  client_status VARCHAR(40) NOT NULL DEFAULT 'active',
  created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
  updated_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  notes TEXT NULL,
  UNIQUE KEY uq_clients_line_user_id (line_user_id),
  KEY idx_clients_email (email),
  KEY idx_clients_phone (phone),
  KEY idx_clients_status (client_status)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS course_intakes (
  intake_id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
  client_id BIGINT UNSIGNED NOT NULL,
  source VARCHAR(40) NOT NULL DEFAULT 'line_ai',
  course_name VARCHAR(190) NOT NULL,
  course_type VARCHAR(120) NULL,
  course_format VARCHAR(120) NULL,
  course_location VARCHAR(190) NULL,
  target_audience TEXT NULL,
  course_features TEXT NULL,
  intake_status VARCHAR(40) NOT NULL DEFAULT '已建檔',
  raw_payload JSON NULL,
  created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
  updated_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  CONSTRAINT fk_course_intakes_client
    FOREIGN KEY (client_id) REFERENCES clients(client_id),
  KEY idx_course_intakes_client (client_id),
  KEY idx_course_intakes_status (intake_status),
  KEY idx_course_intakes_created_at (created_at)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
