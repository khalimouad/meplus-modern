-- =============================================================================
-- ME PLUS Maroc - Schéma de Base de Données (MySQL 5.7+ / 8.x / MariaDB)
-- =============================================================================

CREATE TABLE IF NOT EXISTS `admins` (
  `id` INT AUTO_INCREMENT PRIMARY KEY,
  `username` VARCHAR(64) NOT NULL UNIQUE,
  `email` VARCHAR(128) DEFAULT '',
  `password_hash` VARCHAR(255) NOT NULL,
  `created_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
  `last_login` TIMESTAMP NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS `formations` (
  `id` INT AUTO_INCREMENT PRIMARY KEY,
  `code` VARCHAR(32) NOT NULL UNIQUE,
  `title` VARCHAR(255) NOT NULL,
  `domain` VARCHAR(100) NOT NULL,
  `duration_days` VARCHAR(50) DEFAULT '2-3 jours',
  `duration_hours` VARCHAR(50) DEFAULT '14-21 heures',
  `public_cible` TEXT,
  `prerequis` TEXT,
  `objectifs_json` TEXT,
  `programme_json` TEXT,
  `certification` VARCHAR(100) DEFAULT 'Certifiante / Homologuée',
  `attached_file` VARCHAR(255) DEFAULT '',
  `is_featured` TINYINT(1) DEFAULT 0,
  `is_published` TINYINT(1) DEFAULT 1,
  `sort_order` INT DEFAULT 0,
  `created_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
  `updated_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  INDEX (`domain`),
  INDEX (`is_published`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS `page_sections` (
  `id` INT AUTO_INCREMENT PRIMARY KEY,
  `page_key` VARCHAR(64) NOT NULL,
  `section_key` VARCHAR(64) NOT NULL,
  `title` TEXT,
  `subtitle` TEXT,
  `content_json` TEXT,
  `updated_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  UNIQUE KEY `uk_page_section` (`page_key`, `section_key`),
  INDEX (`page_key`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS `consultants` (
  `id` INT AUTO_INCREMENT PRIMARY KEY,
  `name` VARCHAR(128) NOT NULL,
  `role` VARCHAR(255) NOT NULL,
  `tag` VARCHAR(100) DEFAULT 'Expert Senior',
  `avatar` VARCHAR(255) NOT NULL,
  `credentials` TEXT,
  `specialties_json` TEXT,
  `is_active` TINYINT(1) DEFAULT 1,
  `sort_order` INT DEFAULT 0
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS `clients` (
  `id` INT AUTO_INCREMENT PRIMARY KEY,
  `name` VARCHAR(128) NOT NULL,
  `sector` VARCHAR(128) DEFAULT '',
  `badge` VARCHAR(64) DEFAULT '',
  `logo` VARCHAR(255) NOT NULL,
  `is_active` TINYINT(1) DEFAULT 1,
  `sort_order` INT DEFAULT 0
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS `documents` (
  `id` INT AUTO_INCREMENT PRIMARY KEY,
  `title` VARCHAR(255) NOT NULL,
  `category` VARCHAR(100) NOT NULL,
  `bo_ref` VARCHAR(100) DEFAULT '',
  `description` TEXT,
  `date_text` VARCHAR(50) DEFAULT '2026',
  `file_path` VARCHAR(255) NOT NULL,
  `file_size` INT DEFAULT 0,
  `created_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS `leads` (
  `id` INT AUTO_INCREMENT PRIMARY KEY,
  `full_name` VARCHAR(128) NOT NULL,
  `company` VARCHAR(128) DEFAULT '',
  `phone` VARCHAR(64) DEFAULT '',
  `email` VARCHAR(128) DEFAULT '',
  `subject` VARCHAR(255) DEFAULT '',
  `message` TEXT,
  `is_read` TINYINT(1) DEFAULT 0,
  `created_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
