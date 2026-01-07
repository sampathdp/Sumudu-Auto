-- ============================================================
-- Vehicle Service History Table
-- Records each service event for tracking history
-- ============================================================

CREATE TABLE IF NOT EXISTS `vehicle_service_history` (
  `id` INT(11) NOT NULL AUTO_INCREMENT,
  `company_id` BIGINT UNSIGNED NOT NULL,
  `vehicle_id` INT(11) NOT NULL,
  `invoice_id` INT(11) DEFAULT NULL COMMENT 'Link to invoice if created from invoice',
  
  -- Service Details
  `service_date` DATE NOT NULL,
  `current_mileage` INT(11) DEFAULT NULL,
  `next_service_mileage` INT(11) DEFAULT NULL,
  `next_service_date` DATE DEFAULT NULL,
  
  `notes` TEXT DEFAULT NULL,
  `created_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
  
  PRIMARY KEY (`id`),
  KEY `idx_service_history_company` (`company_id`),
  KEY `idx_service_history_vehicle` (`vehicle_id`),
  KEY `idx_service_history_invoice` (`invoice_id`),
  KEY `idx_service_history_date` (`company_id`, `vehicle_id`, `service_date`),
  
  CONSTRAINT `fk_service_history_company` FOREIGN KEY (`company_id`) REFERENCES `companies` (`id`) ON DELETE CASCADE,
  CONSTRAINT `fk_service_history_vehicle` FOREIGN KEY (`vehicle_id`) REFERENCES `vehicles` (`id`) ON DELETE CASCADE,
  CONSTRAINT `fk_service_history_invoice` FOREIGN KEY (`invoice_id`) REFERENCES `invoices` (`id`) ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

