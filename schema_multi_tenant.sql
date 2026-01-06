-- ============================================================
-- MULTI-TENANT SAAS GARAGE MANAGEMENT SYSTEM
-- Complete Database Schema
-- Generated: 2026-01-02
-- ============================================================
-- 
-- This is the COMPLETE schema with multi-tenant architecture
-- Use this for fresh installations only
--
-- ============================================================

SET SQL_MODE = "NO_AUTO_VALUE_ON_ZERO";
SET FOREIGN_KEY_CHECKS = 0;
START TRANSACTION;
SET time_zone = "+00:00";

/*!40101 SET @OLD_CHARACTER_SET_CLIENT=@@CHARACTER_SET_CLIENT */;
/*!40101 SET @OLD_CHARACTER_SET_RESULTS=@@CHARACTER_SET_RESULTS */;
/*!40101 SET @OLD_COLLATION_CONNECTION=@@COLLATION_CONNECTION */;
/*!40101 SET NAMES utf8mb4 */;

-- ============================================================
-- MASTER TENANT TABLE
-- ============================================================

CREATE TABLE `companies` (
  `id` BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
  `company_code` VARCHAR(50) NOT NULL COMMENT 'Unique tenant identifier for URLs/subdomains',
  `name` VARCHAR(255) NOT NULL,
  `package_type` ENUM('starter', 'business', 'pro', 'enterprise') NOT NULL DEFAULT 'starter',
  `settings_json` JSON DEFAULT NULL COMMENT 'Feature flags and tenant-specific config',
  `status` ENUM('active', 'suspended', 'cancelled', 'trial') NOT NULL DEFAULT 'trial',
  `trial_ends_at` TIMESTAMP NULL DEFAULT NULL,
  `max_users` INT UNSIGNED DEFAULT 5 COMMENT 'User limit per package',
  `max_employees` INT UNSIGNED DEFAULT 10 COMMENT 'Employee limit per package',
  `max_branches` INT UNSIGNED DEFAULT 1 COMMENT 'Branch limit per package',
  `created_at` TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `updated_at` TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  UNIQUE KEY `uk_company_code` (`company_code`),
  KEY `idx_company_status` (`status`),
  KEY `idx_company_package` (`package_type`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Branches Table (One company can have multiple branches)
CREATE TABLE `branches` (
  `id` BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
  `company_id` BIGINT UNSIGNED NOT NULL,
  `branch_code` VARCHAR(20) NOT NULL COMMENT 'Unique code within company',
  `branch_name` VARCHAR(100) NOT NULL,
  `address` TEXT DEFAULT NULL,
  `phone` VARCHAR(20) DEFAULT NULL,
  `email` VARCHAR(100) DEFAULT NULL,
  `manager_id` INT(11) DEFAULT NULL COMMENT 'Branch manager user ID',
  `is_main` TINYINT(1) DEFAULT 0 COMMENT 'Is this the main/head branch',
  `is_active` TINYINT(1) DEFAULT 1,
  `settings_json` JSON DEFAULT NULL COMMENT 'Branch-specific settings',
  `created_at` TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `updated_at` TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  UNIQUE KEY `uk_company_branch_code` (`company_id`, `branch_code`),
  KEY `idx_branches_company` (`company_id`),
  KEY `idx_branches_active` (`company_id`, `is_active`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ============================================================
-- GLOBAL TABLES (No company_id - Shared across tenants)
-- ============================================================

-- Roles Table (Global)
CREATE TABLE `roles` (
  `id` INT(11) NOT NULL AUTO_INCREMENT,
  `role_name` VARCHAR(50) NOT NULL,
  `description` TEXT DEFAULT NULL,
  `is_system_role` TINYINT(1) DEFAULT 0 COMMENT 'System roles cannot be deleted',
  `created_at` TIMESTAMP NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  UNIQUE KEY `role_name` (`role_name`),
  KEY `idx_role_name` (`role_name`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Permissions Table (Global)
CREATE TABLE `permissions` (
  `id` INT(11) NOT NULL AUTO_INCREMENT,
  `permission_name` VARCHAR(50) NOT NULL,
  `permission_code` VARCHAR(50) NOT NULL COMMENT 'e.g., view, create, edit, delete, export',
  `description` TEXT DEFAULT NULL,
  `created_at` TIMESTAMP NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  UNIQUE KEY `permission_code` (`permission_code`),
  KEY `idx_permission_code` (`permission_code`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Pages Table (Global - Navigation structure)
CREATE TABLE `pages` (
  `id` INT(11) NOT NULL AUTO_INCREMENT,
  `page_name` VARCHAR(100) NOT NULL,
  `page_route` VARCHAR(100) NOT NULL COMMENT 'URL route or identifier',
  `page_category` VARCHAR(50) DEFAULT NULL COMMENT 'Category for grouping',
  `description` TEXT DEFAULT NULL,
  `icon` VARCHAR(50) DEFAULT NULL COMMENT 'Icon identifier for UI',
  `display_order` INT(11) DEFAULT 0,
  `is_active` TINYINT(1) DEFAULT 1,
  `parent_page_id` INT(11) DEFAULT NULL COMMENT 'For hierarchical menu structure',
  `created_at` TIMESTAMP NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  UNIQUE KEY `page_route` (`page_route`),
  KEY `parent_page_id` (`parent_page_id`),
  KEY `idx_page_route` (`page_route`),
  KEY `idx_page_category` (`page_category`),
  KEY `idx_page_active` (`is_active`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Sidebar Modules Table (Per-company page visibility)
CREATE TABLE `sidebar_modules` (
  `id` INT(11) NOT NULL AUTO_INCREMENT,
  `company_id` INT(11) NOT NULL DEFAULT 1,
  `page_id` INT(11) NOT NULL,
  `is_visible` TINYINT(1) NOT NULL DEFAULT 1,
  `updated_by` INT(11) DEFAULT NULL,
  `updated_at` TIMESTAMP NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  UNIQUE KEY `company_page` (`company_id`, `page_id`) 
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ============================================================
-- TENANT-SPECIFIC TABLES (With company_id)
-- ============================================================

-- Company Profile Table
CREATE TABLE `company_profile` (
  `id` INT(11) NOT NULL AUTO_INCREMENT,
  `company_id` BIGINT UNSIGNED NOT NULL,
  `name` VARCHAR(255) NOT NULL,
  `address` VARCHAR(255) NOT NULL,
  `mobile_number_1` VARCHAR(255) NOT NULL,
  `mobile_number_2` VARCHAR(255) DEFAULT NULL,
  `email` VARCHAR(255) NOT NULL,
  `image_name` VARCHAR(255) DEFAULT NULL,
  `is_active` INT(11) NOT NULL DEFAULT 1,
  `is_vat` INT(11) NOT NULL DEFAULT 0,
  `tax_number` VARCHAR(255) DEFAULT NULL,
  `tax_percentage` INT(11) DEFAULT 0,
  `customer_id` INT(11) DEFAULT NULL,
  `company_code` VARCHAR(255) DEFAULT NULL,
  `theme` VARCHAR(50) DEFAULT 'dark',
  `favicon` VARCHAR(255) DEFAULT NULL,
  `cashbook_opening_balance` DECIMAL(15,2) DEFAULT 0.00,
  PRIMARY KEY (`id`),
  KEY `idx_company_profile_company` (`company_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Users Table
CREATE TABLE `users` (
  `id` INT(11) NOT NULL AUTO_INCREMENT,
  `company_id` BIGINT UNSIGNED NOT NULL,
  `branch_id` BIGINT UNSIGNED DEFAULT NULL COMMENT 'NULL = access all branches',
  `username` VARCHAR(50) NOT NULL,
  `role_id` INT(11) NOT NULL COMMENT 'Link to roles table for permissions',
  `password_hash` VARCHAR(255) NOT NULL,
  `is_active` TINYINT(1) DEFAULT 1,
  `last_login` TIMESTAMP NULL DEFAULT NULL,
  `created_at` TIMESTAMP NULL DEFAULT CURRENT_TIMESTAMP,
  `updated_at` TIMESTAMP NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  UNIQUE KEY `uk_company_username` (`company_id`, `username`),
  KEY `idx_users_company` (`company_id`),
  KEY `idx_users_branch` (`company_id`, `branch_id`),
  KEY `idx_role_id` (`role_id`),
  KEY `idx_active` (`is_active`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- User Permissions Table
CREATE TABLE `user_permissions` (
  `id` INT(11) NOT NULL AUTO_INCREMENT,
  `company_id` BIGINT UNSIGNED NOT NULL,
  `user_id` INT(11) NOT NULL,
  `page_id` INT(11) NOT NULL,
  `permission_id` INT(11) NOT NULL,
  `is_granted` TINYINT(1) DEFAULT 1 COMMENT 'TRUE to grant, FALSE to revoke',
  `created_at` TIMESTAMP NULL DEFAULT CURRENT_TIMESTAMP,
  `created_by` INT(11) DEFAULT NULL COMMENT 'Admin who set this permission',
  `expires_at` TIMESTAMP NULL DEFAULT NULL COMMENT 'Optional expiration for temporary access',
  PRIMARY KEY (`id`),
  UNIQUE KEY `uk_company_user_page_permission` (`company_id`, `user_id`, `page_id`, `permission_id`),
  KEY `idx_user_perms_company` (`company_id`),
  KEY `permission_id` (`permission_id`),
  KEY `idx_user_permissions_user` (`user_id`),
  KEY `idx_user_permissions_page` (`page_id`),
  KEY `idx_user_permissions_expires` (`expires_at`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Customers Table
CREATE TABLE `customers` (
  `id` INT(11) NOT NULL AUTO_INCREMENT,
  `company_id` BIGINT UNSIGNED NOT NULL,
  `name` VARCHAR(100) NOT NULL,
  `phone` VARCHAR(20) NOT NULL,
  `email` VARCHAR(100) DEFAULT NULL,
  `address` TEXT DEFAULT NULL,
  `total_visits` INT(11) DEFAULT 0,
  `last_visit_date` DATE DEFAULT NULL,
  `created_at` TIMESTAMP NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  KEY `idx_customers_company` (`company_id`),
  KEY `idx_customers_company_phone` (`company_id`, `phone`),
  KEY `idx_customer_email` (`email`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Vehicles Table
CREATE TABLE `vehicles` (
  `id` INT(11) NOT NULL AUTO_INCREMENT,
  `company_id` BIGINT UNSIGNED NOT NULL,
  `customer_id` INT(11) NOT NULL,
  `registration_number` VARCHAR(20) NOT NULL,
  `make` VARCHAR(50) NOT NULL,
  `model` VARCHAR(50) NOT NULL,
  `year` INT(11) DEFAULT NULL,
  `color` VARCHAR(30) DEFAULT NULL,
  `current_mileage` INT(11) DEFAULT NULL,
  `last_service_date` DATE DEFAULT NULL,
  `last_oil_change_date` DATE DEFAULT NULL,
  `created_at` TIMESTAMP NULL DEFAULT CURRENT_TIMESTAMP,
  `updated_at` TIMESTAMP NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  UNIQUE KEY `uk_company_registration` (`company_id`, `registration_number`),
  KEY `idx_vehicles_company` (`company_id`),
  KEY `idx_vehicles_company_customer` (`company_id`, `customer_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Employees Table
CREATE TABLE `employees` (
  `id` INT(11) NOT NULL AUTO_INCREMENT,
  `company_id` BIGINT UNSIGNED NOT NULL,
  `branch_id` BIGINT UNSIGNED DEFAULT NULL COMMENT 'Primary branch assignment',
  `employee_code` VARCHAR(20) NOT NULL,
  `first_name` VARCHAR(100) NOT NULL,
  `last_name` VARCHAR(100) NOT NULL,
  `email` VARCHAR(150) DEFAULT NULL,
  `phone` VARCHAR(20) DEFAULT NULL,
  `position` VARCHAR(100) DEFAULT NULL,
  `department` VARCHAR(100) DEFAULT NULL,
  `hire_date` DATE DEFAULT NULL,
  `salary` DECIMAL(12,2) DEFAULT 0.00,
  `salary_type` ENUM('monthly','daily','commission') DEFAULT 'monthly',
  `address` TEXT DEFAULT NULL,
  `emergency_contact` VARCHAR(100) DEFAULT NULL,
  `emergency_phone` VARCHAR(20) DEFAULT NULL,
  `is_active` TINYINT(1) DEFAULT 1,
  `user_id` INT(11) DEFAULT NULL,
  `notes` TEXT DEFAULT NULL,
  `created_at` TIMESTAMP NULL DEFAULT CURRENT_TIMESTAMP,
  `updated_at` TIMESTAMP NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  UNIQUE KEY `uk_company_employee_code` (`company_id`, `employee_code`),
  UNIQUE KEY `uk_company_employee_email` (`company_id`, `email`),
  KEY `idx_employees_company` (`company_id`),
  KEY `idx_employees_branch` (`company_id`, `branch_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Employee Payments Table
CREATE TABLE `employee_payments` (
  `id` INT(11) NOT NULL AUTO_INCREMENT,
  `company_id` BIGINT UNSIGNED NOT NULL,
  `employee_id` INT(11) NOT NULL,
  `payment_date` DATE NOT NULL,
  `salary_type` ENUM('monthly','daily','commission') NOT NULL,
  `base_amount` DECIMAL(10,2) DEFAULT 0.00,
  `commission_amount` DECIMAL(10,2) DEFAULT 0.00,
  `pending_amount` DECIMAL(10,2) DEFAULT 0.00,
  `total_amount` DECIMAL(10,2) NOT NULL,
  `jobs_count` INT(11) DEFAULT 0,
  `status` ENUM('paid','unpaid') DEFAULT 'unpaid',
  `paid_at` TIMESTAMP NULL DEFAULT NULL,
  `paid_by` INT(11) DEFAULT NULL,
  `notes` TEXT DEFAULT NULL,
  `created_at` TIMESTAMP NULL DEFAULT CURRENT_TIMESTAMP,
  `updated_at` TIMESTAMP NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  UNIQUE KEY `uk_company_employee_date` (`company_id`, `employee_id`, `payment_date`),
  KEY `idx_payments_company` (`company_id`),
  KEY `idx_payments_company_date` (`company_id`, `payment_date`),
  CONSTRAINT `fk_emp_payments_employee` FOREIGN KEY (`employee_id`) REFERENCES `employees` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Service Packages Table
CREATE TABLE `service_packages` (
  `id` INT(11) NOT NULL AUTO_INCREMENT,
  `company_id` BIGINT UNSIGNED NOT NULL,
  `package_name` VARCHAR(100) NOT NULL,
  `description` TEXT DEFAULT NULL,
  `base_price` DECIMAL(10,2) NOT NULL,
  `estimated_duration` INT(11) NOT NULL COMMENT 'Duration in minutes',
  `is_active` TINYINT(1) DEFAULT 1,
  `created_at` TIMESTAMP NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  KEY `idx_packages_company` (`company_id`),
  KEY `idx_packages_company_active` (`company_id`, `is_active`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Service Stages Table
CREATE TABLE `service_stages` (
  `id` INT(11) NOT NULL AUTO_INCREMENT,
  `company_id` BIGINT UNSIGNED NOT NULL,
  `stage_name` VARCHAR(50) NOT NULL,
  `stage_order` INT(11) NOT NULL,
  `icon` VARCHAR(20) DEFAULT NULL COMMENT 'Emoji or icon identifier',
  `estimated_duration` INT(11) DEFAULT NULL COMMENT 'Duration in minutes',
  `created_at` TIMESTAMP NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  KEY `idx_stages_company` (`company_id`),
  KEY `idx_stages_company_order` (`company_id`, `stage_order`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Time Slots Table
CREATE TABLE `time_slots` (
  `id` INT(11) NOT NULL AUTO_INCREMENT,
  `company_id` BIGINT UNSIGNED NOT NULL,
  `slot_start` TIME NOT NULL,
  `slot_end` TIME NOT NULL,
  `max_bookings` INT(11) DEFAULT 3 COMMENT 'Maximum bookings allowed per slot',
  `is_active` TINYINT(1) DEFAULT 1,
  `created_at` TIMESTAMP NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  UNIQUE KEY `uk_company_time_slot` (`company_id`, `slot_start`, `slot_end`),
  KEY `idx_time_slots_company` (`company_id`),
  KEY `idx_slot_time` (`slot_start`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Bookings Table
CREATE TABLE `bookings` (
  `id` INT(11) NOT NULL AUTO_INCREMENT,
  `company_id` BIGINT UNSIGNED NOT NULL,
  `branch_id` BIGINT UNSIGNED DEFAULT NULL COMMENT 'Branch where service will be performed',
  `booking_number` VARCHAR(20) NOT NULL,
  `customer_name` VARCHAR(100) NOT NULL,
  `customer_mobile` VARCHAR(15) NOT NULL,
  `customer_email` VARCHAR(100) DEFAULT NULL,
  `registration_number` VARCHAR(20) DEFAULT NULL,
  `vehicle_make` VARCHAR(100) NOT NULL,
  `vehicle_model` VARCHAR(100) NOT NULL,
  `service_package_id` INT(11) NOT NULL,
  `booking_date` DATE NOT NULL,
  `booking_time` TIME NOT NULL,
  `estimated_duration` INT(11) NOT NULL COMMENT 'Duration in minutes',
  `status` ENUM('pending_approval','approved','rejected','cancelled','completed') DEFAULT 'pending_approval',
  `notes` TEXT DEFAULT NULL,
  `total_amount` DECIMAL(10,2) NOT NULL,
  `approved_by_employee_id` INT(11) DEFAULT NULL COMMENT 'Employee who approved/rejected',
  `approved_at` TIMESTAMP NULL DEFAULT NULL,
  `rejection_reason` TEXT DEFAULT NULL,
  `created_at` TIMESTAMP NULL DEFAULT CURRENT_TIMESTAMP,
  `updated_at` TIMESTAMP NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  UNIQUE KEY `uk_company_booking_number` (`company_id`, `booking_number`),
  KEY `idx_bookings_company` (`company_id`),
  KEY `idx_bookings_branch` (`company_id`, `branch_id`),
  KEY `idx_bookings_company_date` (`company_id`, `booking_date`),
  KEY `idx_bookings_company_status` (`company_id`, `status`),
  KEY `service_package_id` (`service_package_id`),
  KEY `approved_by_employee_id` (`approved_by_employee_id`),
  KEY `idx_booking_customer` (`customer_mobile`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Booking Availability Table
CREATE TABLE `booking_availability` (
  `id` INT(11) NOT NULL AUTO_INCREMENT,
  `company_id` BIGINT UNSIGNED NOT NULL,
  `date` DATE NOT NULL,
  `time_slot_id` INT(11) NOT NULL,
  `available_slots` INT(11) NOT NULL DEFAULT 3,
  `is_holiday` TINYINT(1) DEFAULT 0,
  `created_at` TIMESTAMP NULL DEFAULT CURRENT_TIMESTAMP,
  `updated_at` TIMESTAMP NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  UNIQUE KEY `uk_company_date_slot` (`company_id`, `date`, `time_slot_id`),
  KEY `idx_availability_company` (`company_id`),
  KEY `idx_availability_company_date` (`company_id`, `date`),
  KEY `time_slot_id` (`time_slot_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
 

-- QR Codes Table
CREATE TABLE `qr_codes` (
  `id` INT(11) NOT NULL AUTO_INCREMENT,
  `company_id` BIGINT UNSIGNED NOT NULL,
  `qr_code` VARCHAR(100) NOT NULL COMMENT 'Unique QR identifier',
  `service_id` INT(11) DEFAULT NULL,
  `status` ENUM('active','expired','completed') DEFAULT 'active',
  `color_code` ENUM('red','yellow','green') DEFAULT 'red',
  `generated_at` TIMESTAMP NULL DEFAULT CURRENT_TIMESTAMP,
  `expires_at` TIMESTAMP NULL DEFAULT NULL,
  `scanned_count` INT(11) DEFAULT 0,
  `last_scanned_at` TIMESTAMP NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `uk_company_qr_code` (`company_id`, `qr_code`),
  KEY `idx_qr_codes_company` (`company_id`),
  KEY `idx_qr_codes_company_status` (`company_id`, `status`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Services Table (Job Cards)
CREATE TABLE `services` (
  `id` INT(11) NOT NULL AUTO_INCREMENT,
  `company_id` BIGINT UNSIGNED NOT NULL,
  `branch_id` BIGINT UNSIGNED DEFAULT NULL COMMENT 'Branch where service is performed',
  `job_number` VARCHAR(20) NOT NULL,
  `customer_id` INT(11) NOT NULL,
  `vehicle_id` INT(11) NOT NULL,
  `package_id` INT(11) NOT NULL,
  `assigned_employee_id` INT(11) DEFAULT NULL,
  `qr_id` INT(11) DEFAULT NULL,
  `status` ENUM('waiting','in_progress','quality_check','completed','delivered','cancelled') DEFAULT 'waiting',
  `current_stage_id` INT(11) DEFAULT NULL,
  `progress_percentage` INT(11) DEFAULT 0,
  `start_time` TIMESTAMP NULL DEFAULT NULL,
  `expected_completion_time` TIMESTAMP NULL DEFAULT NULL,
  `actual_completion_time` TIMESTAMP NULL DEFAULT NULL,
  `total_amount` DECIMAL(10,2) DEFAULT NULL,
  `payment_status` ENUM('pending','paid','refunded') DEFAULT 'pending',
  `notes` TEXT DEFAULT NULL,
  `created_at` TIMESTAMP NULL DEFAULT CURRENT_TIMESTAMP,
  `updated_at` TIMESTAMP NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  UNIQUE KEY `uk_company_job_number` (`company_id`, `job_number`),
  UNIQUE KEY `qr_id` (`qr_id`),
  KEY `idx_services_company` (`company_id`),
  KEY `idx_services_branch` (`company_id`, `branch_id`),
  KEY `idx_services_company_status` (`company_id`, `status`),
  KEY `idx_services_company_date` (`company_id`, `created_at`),
  KEY `package_id` (`package_id`),
  KEY `assigned_employee_id` (`assigned_employee_id`),
  KEY `current_stage_id` (`current_stage_id`),
  KEY `idx_service_customer` (`customer_id`),
  KEY `idx_service_vehicle` (`vehicle_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Health Check Reports Table
CREATE TABLE `health_check_reports` (
  `id` INT(11) NOT NULL AUTO_INCREMENT,
  `company_id` BIGINT UNSIGNED NOT NULL,
  `job_id` INT(11) NOT NULL,
  `tyre_condition` VARCHAR(50) DEFAULT NULL,
  `brake_condition` VARCHAR(50) DEFAULT NULL,
  `oil_level` VARCHAR(50) DEFAULT NULL,
  `filter_status` VARCHAR(50) DEFAULT NULL,
  `battery_health` VARCHAR(50) DEFAULT NULL,
  `additional_notes` TEXT DEFAULT NULL,
  `created_at` TIMESTAMP NULL DEFAULT CURRENT_TIMESTAMP,
  `updated_at` TIMESTAMP NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  KEY `idx_health_reports_company` (`company_id`),
  KEY `job_id` (`job_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Suppliers Table
CREATE TABLE `suppliers` (
  `id` INT(11) NOT NULL AUTO_INCREMENT,
  `company_id` BIGINT UNSIGNED NOT NULL,
  `supplier_name` VARCHAR(100) NOT NULL,
  `contact_person` VARCHAR(100) DEFAULT NULL,
  `phone` VARCHAR(20) DEFAULT NULL,
  `email` VARCHAR(100) DEFAULT NULL,
  `address` TEXT DEFAULT NULL,
  `tax_id` VARCHAR(50) DEFAULT NULL COMMENT 'VAT/Tax registration number',
  `payment_terms` VARCHAR(100) DEFAULT NULL COMMENT 'e.g., Net 30, COD',
  `is_active` TINYINT(1) DEFAULT 1,
  `created_at` TIMESTAMP NULL DEFAULT CURRENT_TIMESTAMP,
  `updated_at` TIMESTAMP NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  KEY `idx_suppliers_company` (`company_id`),
  KEY `idx_suppliers_company_active` (`company_id`, `is_active`),
  KEY `idx_supplier_name` (`supplier_name`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Inventory Categories Table
CREATE TABLE `inventory_categories` (
  `id` INT(11) NOT NULL AUTO_INCREMENT,
  `company_id` BIGINT UNSIGNED NOT NULL,
  `category_name` VARCHAR(100) NOT NULL,
  `description` TEXT DEFAULT NULL,
  `parent_category_id` INT(11) DEFAULT NULL COMMENT 'For hierarchical categories',
  `is_active` TINYINT(1) DEFAULT 1,
  `created_at` TIMESTAMP NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  KEY `idx_inv_categories_company` (`company_id`),
  KEY `idx_inv_categories_company_active` (`company_id`, `is_active`),
  KEY `parent_category_id` (`parent_category_id`),
  KEY `idx_category_name` (`category_name`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Inventory Items Table
CREATE TABLE `inventory_items` (
  `id` INT(11) NOT NULL AUTO_INCREMENT,
  `company_id` BIGINT UNSIGNED NOT NULL,
  `branch_id` BIGINT UNSIGNED DEFAULT NULL COMMENT 'NULL = company-wide item, else branch-specific stock',
  `item_code` VARCHAR(50) NOT NULL COMMENT 'SKU or internal code',
  `item_name` VARCHAR(150) NOT NULL,
  `description` TEXT DEFAULT NULL,
  `category_id` INT(11) DEFAULT NULL,
  `unit_of_measure` VARCHAR(20) NOT NULL COMMENT 'e.g., pcs, liters, kg',
  `current_stock` DECIMAL(10,2) DEFAULT 0.00,
  `reorder_level` DECIMAL(10,2) DEFAULT 0.00 COMMENT 'Minimum stock before reorder',
  `max_stock_level` DECIMAL(10,2) DEFAULT NULL COMMENT 'Maximum stock capacity',
  `unit_cost` DECIMAL(10,2) DEFAULT 0.00 COMMENT 'Average cost price',
  `unit_price` DECIMAL(10,2) DEFAULT 0.00 COMMENT 'Selling price',
  `is_active` TINYINT(1) DEFAULT 1,
  `created_at` TIMESTAMP NULL DEFAULT CURRENT_TIMESTAMP,
  `updated_at` TIMESTAMP NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  UNIQUE KEY `uk_company_branch_item_code` (`company_id`, `branch_id`, `item_code`),
  KEY `idx_inv_items_company` (`company_id`),
  KEY `idx_inv_items_branch` (`company_id`, `branch_id`),
  KEY `idx_inv_items_company_active` (`company_id`, `is_active`),
  KEY `idx_inv_items_company_category` (`company_id`, `category_id`),
  KEY `idx_item_name` (`item_name`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Inventory Batches Table (For FIFO tracking)
CREATE TABLE `inventory_batches` (
  `id` INT(11) NOT NULL AUTO_INCREMENT,
  `company_id` BIGINT UNSIGNED NOT NULL,
  `branch_id` BIGINT UNSIGNED DEFAULT NULL,
  `item_id` INT(11) NOT NULL,
  `batch_number` VARCHAR(50) DEFAULT NULL,
  `grn_item_id` INT(11) DEFAULT NULL COMMENT 'Link to source GRN item',
  `quantity_initial` DECIMAL(10,2) NOT NULL,
  `quantity_remaining` DECIMAL(10,2) NOT NULL,
  `unit_cost` DECIMAL(10,2) NOT NULL,
  `expiry_date` DATE DEFAULT NULL,
  `received_date` DATE NOT NULL,
  `is_active` TINYINT(1) DEFAULT 1 COMMENT '0 if empty/closed',
  `created_at` TIMESTAMP NULL DEFAULT CURRENT_TIMESTAMP,
  `updated_at` TIMESTAMP NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  KEY `idx_batches_company` (`company_id`),
  KEY `idx_batches_branch` (`company_id`, `branch_id`),
  KEY `idx_batches_item_fifo` (`company_id`, `item_id`, `received_date`, `id`),
  KEY `idx_batches_active` (`is_active`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Stock Movements Table
CREATE TABLE `stock_movements` (
  `id` INT(11) NOT NULL AUTO_INCREMENT,
  `company_id` BIGINT UNSIGNED NOT NULL,
  `item_id` INT(11) NOT NULL,
  `movement_type` ENUM('grn','usage','adjustment','return','damage') NOT NULL,
  `reference_type` VARCHAR(50) DEFAULT NULL COMMENT 'e.g., service, grn, adjustment',
  `reference_id` INT(11) DEFAULT NULL COMMENT 'ID of the reference record',
  `quantity_change` DECIMAL(10,2) NOT NULL COMMENT 'Positive for IN, Negative for OUT',
  `balance_after` DECIMAL(10,2) NOT NULL COMMENT 'Stock balance after this movement',
  `unit_cost` DECIMAL(10,2) DEFAULT NULL,
  `notes` TEXT DEFAULT NULL,
  `created_by_employee_id` INT(11) DEFAULT NULL,
  `created_at` TIMESTAMP NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  KEY `idx_movements_company` (`company_id`),
  KEY `idx_movements_company_date` (`company_id`, `created_at`),
  KEY `idx_movements_company_item` (`company_id`, `item_id`),
  KEY `created_by_employee_id` (`created_by_employee_id`),
  KEY `idx_movement_type` (`movement_type`),
  KEY `idx_movement_reference` (`reference_type`, `reference_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- GRN (Goods Receipt Note) Table
CREATE TABLE `grn` (
  `id` INT(11) NOT NULL AUTO_INCREMENT,
  `company_id` BIGINT UNSIGNED NOT NULL,
  `branch_id` BIGINT UNSIGNED DEFAULT NULL COMMENT 'Receiving branch',
  `grn_number` VARCHAR(20) NOT NULL,
  `supplier_id` INT(11) NOT NULL,
  `grn_date` DATE NOT NULL,
  `due_date` DATE DEFAULT NULL COMMENT 'Payment due date based on supplier terms',
  `invoice_number` VARCHAR(50) DEFAULT NULL COMMENT 'Supplier invoice reference',
  `total_amount` DECIMAL(10,2) NOT NULL,
  `tax_amount` DECIMAL(10,2) DEFAULT 0.00,
  `discount_amount` DECIMAL(10,2) DEFAULT 0.00,
  `net_amount` DECIMAL(10,2) NOT NULL,
  `amount_paid` DECIMAL(15,2) DEFAULT 0.00 COMMENT 'Total amount paid against this GRN',
  `payment_status` ENUM('unpaid','partial','paid','overpaid') DEFAULT 'unpaid',
  `status` ENUM('draft','received','verified','cancelled') DEFAULT 'draft',
  `received_by_employee_id` INT(11) DEFAULT NULL,
  `verified_by_employee_id` INT(11) DEFAULT NULL,
  `notes` TEXT DEFAULT NULL,
  `created_at` TIMESTAMP NULL DEFAULT CURRENT_TIMESTAMP,
  `updated_at` TIMESTAMP NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  UNIQUE KEY `uk_company_grn_number` (`company_id`, `grn_number`),
  KEY `idx_grn_company` (`company_id`),
  KEY `idx_grn_branch` (`company_id`, `branch_id`),
  KEY `idx_grn_company_date` (`company_id`, `grn_date`),
  KEY `idx_grn_company_status` (`company_id`, `status`),
  KEY `idx_grn_payment_status` (`company_id`, `supplier_id`, `payment_status`),
  KEY `idx_grn_due_date` (`company_id`, `due_date`),
  KEY `received_by_employee_id` (`received_by_employee_id`),
  KEY `verified_by_employee_id` (`verified_by_employee_id`),
  KEY `idx_grn_supplier` (`supplier_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- GRN Items Table
CREATE TABLE `grn_items` (
  `id` INT(11) NOT NULL AUTO_INCREMENT,
  `company_id` BIGINT UNSIGNED NOT NULL,
  `grn_id` INT(11) NOT NULL,
  `item_id` INT(11) NOT NULL,
  `quantity` DECIMAL(10,2) NOT NULL,
  `unit_price` DECIMAL(10,2) NOT NULL,
  `total_price` DECIMAL(10,2) NOT NULL,
  `batch_number` VARCHAR(50) DEFAULT NULL COMMENT 'Batch or lot number',
  `expiry_date` DATE DEFAULT NULL COMMENT 'For perishable items',
  `notes` TEXT DEFAULT NULL,
  `created_at` TIMESTAMP NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  KEY `idx_grn_items_company` (`company_id`),
  KEY `idx_grn_item_grn` (`grn_id`),
  KEY `idx_grn_item_item` (`item_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ============================================================
-- GRN PAYMENT MODULE
-- Supplier payments, allocations, and audit trail
-- ============================================================

-- Audit Log Table (Comprehensive change tracking)
CREATE TABLE `audit_log` (
  `id` BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
  `company_id` BIGINT UNSIGNED NOT NULL,
  `table_name` VARCHAR(50) NOT NULL COMMENT 'Table affected',
  `record_id` INT(11) NOT NULL COMMENT 'Primary key of affected record',
  `action` ENUM('create','update','delete','cancel','approve','reject') NOT NULL,
  `field_name` VARCHAR(50) DEFAULT NULL COMMENT 'Specific field changed',
  `old_value` TEXT DEFAULT NULL,
  `new_value` TEXT DEFAULT NULL,
  `change_summary` VARCHAR(255) DEFAULT NULL COMMENT 'Human-readable summary',
  `reference_type` VARCHAR(50) DEFAULT NULL COMMENT 'e.g., grn, supplier_payment',
  `reference_number` VARCHAR(50) DEFAULT NULL COMMENT 'e.g., GRN-001234, PAY-001',
  `user_id` INT(11) NOT NULL,
  `user_name` VARCHAR(100) DEFAULT NULL COMMENT 'Denormalized for history',
  `ip_address` VARCHAR(45) DEFAULT NULL,
  `user_agent` VARCHAR(255) DEFAULT NULL,
  `created_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  KEY `idx_audit_company` (`company_id`),
  KEY `idx_audit_table_record` (`table_name`, `record_id`),
  KEY `idx_audit_user` (`user_id`),
  KEY `idx_audit_date` (`company_id`, `created_at`),
  KEY `idx_audit_action` (`action`),
  KEY `idx_audit_reference` (`reference_type`, `reference_number`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Supplier Payments Table (Header)
CREATE TABLE `supplier_payments` (
  `id` INT(11) NOT NULL AUTO_INCREMENT,
  `company_id` BIGINT UNSIGNED NOT NULL,
  `branch_id` BIGINT UNSIGNED DEFAULT NULL,
  `payment_number` VARCHAR(20) NOT NULL COMMENT 'Auto-generated: PAY-YYYYMM-XXXX',
  `payment_date` DATE NOT NULL,
  `supplier_id` INT(11) NOT NULL,
  `total_amount` DECIMAL(15,2) NOT NULL COMMENT 'Total payment amount',
  `allocated_amount` DECIMAL(15,2) DEFAULT 0.00 COMMENT 'Amount allocated to GRNs',
  `unallocated_amount` DECIMAL(15,2) DEFAULT 0.00 COMMENT 'Advance/Overpayment',
  `status` ENUM('draft','confirmed','cancelled') DEFAULT 'draft',
  `notes` TEXT DEFAULT NULL,
  `created_by` INT(11) NOT NULL,
  `approved_by` INT(11) DEFAULT NULL,
  `approved_at` TIMESTAMP NULL DEFAULT NULL,
  `created_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
  `updated_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  UNIQUE KEY `uk_company_payment_number` (`company_id`, `payment_number`),
  KEY `idx_sp_supplier` (`supplier_id`),
  KEY `idx_sp_date` (`company_id`, `payment_date`),
  KEY `idx_sp_status` (`company_id`, `status`),
  CONSTRAINT `fk_sp_company` FOREIGN KEY (`company_id`) REFERENCES `companies`(`id`),
  CONSTRAINT `fk_sp_supplier` FOREIGN KEY (`supplier_id`) REFERENCES `suppliers`(`id`),
  CONSTRAINT `fk_sp_created_by` FOREIGN KEY (`created_by`) REFERENCES `users`(`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Supplier Payment Methods (Multi-method support)
CREATE TABLE `supplier_payment_methods` (
  `id` INT(11) NOT NULL AUTO_INCREMENT,
  `company_id` BIGINT UNSIGNED NOT NULL,
  `payment_id` INT(11) NOT NULL,
  `payment_method` ENUM('cash','cheque','bank_transfer','online','other') NOT NULL,
  `amount` DECIMAL(15,2) NOT NULL,
  `reference_number` VARCHAR(100) DEFAULT NULL COMMENT 'Cheque no, transfer ref, etc.',
  `bank_name` VARCHAR(100) DEFAULT NULL,
  `cheque_date` DATE DEFAULT NULL COMMENT 'For post-dated cheques',
  `cheque_status` ENUM('pending','cleared','bounced') DEFAULT NULL,
  `notes` TEXT DEFAULT NULL,
  `created_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  KEY `idx_spm_payment` (`payment_id`),
  KEY `idx_spm_cheque_status` (`cheque_status`, `cheque_date`),
  CONSTRAINT `fk_spm_payment` FOREIGN KEY (`payment_id`) REFERENCES `supplier_payments`(`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Supplier Payment Allocations (Links payments to GRNs)
CREATE TABLE `supplier_payment_allocations` (
  `id` INT(11) NOT NULL AUTO_INCREMENT,
  `company_id` BIGINT UNSIGNED NOT NULL,
  `payment_id` INT(11) NOT NULL,
  `grn_id` INT(11) NOT NULL,
  `allocated_amount` DECIMAL(15,2) NOT NULL COMMENT 'Amount applied to this GRN',
  `created_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  UNIQUE KEY `uk_payment_grn` (`payment_id`, `grn_id`),
  KEY `idx_spa_grn` (`grn_id`),
  CONSTRAINT `fk_spa_payment` FOREIGN KEY (`payment_id`) REFERENCES `supplier_payments`(`id`) ON DELETE CASCADE,
  CONSTRAINT `fk_spa_grn` FOREIGN KEY (`grn_id`) REFERENCES `grn`(`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Supplier Ledger (Running balance for P&L)
CREATE TABLE `supplier_ledger` (
  `id` INT(11) NOT NULL AUTO_INCREMENT,
  `company_id` BIGINT UNSIGNED NOT NULL,
  `supplier_id` INT(11) NOT NULL,
  `transaction_date` DATE NOT NULL,
  `transaction_type` ENUM('grn','payment','adjustment','opening') NOT NULL,
  `reference_id` INT(11) DEFAULT NULL COMMENT 'GRN ID or Payment ID',
  `reference_number` VARCHAR(50) DEFAULT NULL,
  `debit_amount` DECIMAL(15,2) DEFAULT 0.00 COMMENT 'GRN creates debit (we owe)',
  `credit_amount` DECIMAL(15,2) DEFAULT 0.00 COMMENT 'Payment creates credit',
  `balance` DECIMAL(15,2) NOT NULL COMMENT 'Running balance (positive = we owe)',
  `notes` TEXT DEFAULT NULL,
  `created_by` INT(11) DEFAULT NULL,
  `created_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  KEY `idx_sl_supplier_date` (`company_id`, `supplier_id`, `transaction_date`),
  KEY `idx_sl_type` (`transaction_type`),
  CONSTRAINT `fk_sl_supplier` FOREIGN KEY (`supplier_id`) REFERENCES `suppliers`(`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ============================================================
-- GRN TABLE MODIFICATIONS (Payment tracking & Due date)
-- Run these ALTER statements on existing databases
-- ============================================================
-- ALTER TABLE `grn` ADD COLUMN `due_date` DATE DEFAULT NULL AFTER `grn_date`;
-- ALTER TABLE `grn` ADD COLUMN `amount_paid` DECIMAL(15,2) DEFAULT 0.00 AFTER `net_amount`;
-- ALTER TABLE `grn` ADD COLUMN `payment_status` ENUM('unpaid','partial','paid','overpaid') DEFAULT 'unpaid' AFTER `amount_paid`;
-- ALTER TABLE `grn` ADD INDEX `idx_grn_payment_status` (`company_id`, `supplier_id`, `payment_status`);
-- ALTER TABLE `grn` ADD INDEX `idx_grn_due_date` (`company_id`, `due_date`);

-- Quotations Table
CREATE TABLE `quotations` (
  `id` INT(11) NOT NULL AUTO_INCREMENT,
  `company_id` BIGINT UNSIGNED NOT NULL,
  `branch_id` BIGINT UNSIGNED DEFAULT NULL COMMENT 'Issuing branch',
  `quotation_number` VARCHAR(20) NOT NULL,
  `customer_id` INT(11) NOT NULL,
  `customer_name` VARCHAR(100) NOT NULL,
  `subtotal` DECIMAL(10,2) NOT NULL,
  `tax_amount` DECIMAL(10,2) DEFAULT 0.00,
  `discount_amount` DECIMAL(10,2) DEFAULT 0.00,
  `total_amount` DECIMAL(10,2) NOT NULL,
  `valid_until` DATE DEFAULT NULL,
  `status` ENUM('pending','accepted','rejected') DEFAULT 'pending',
  `created_at` TIMESTAMP NULL DEFAULT CURRENT_TIMESTAMP,
  `updated_at` TIMESTAMP NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  UNIQUE KEY `uk_company_quotation_number` (`company_id`, `quotation_number`),
  KEY `idx_quotations_company` (`company_id`),
  KEY `idx_quotations_branch` (`company_id`, `branch_id`),
  KEY `idx_quotations_company_status` (`company_id`, `status`),
  KEY `idx_quotation_customer` (`customer_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Quotation Items Table
CREATE TABLE `quotation_items` (
  `id` INT(11) NOT NULL AUTO_INCREMENT,
  `company_id` BIGINT UNSIGNED NOT NULL,
  `quotation_id` INT(11) NOT NULL,
  `item_type` ENUM('service','inventory','labor','other') NOT NULL,
  `item_id` INT(11) DEFAULT NULL,
  `description` VARCHAR(255) NOT NULL,
  `quantity` DECIMAL(10,2) NOT NULL DEFAULT 1.00,
  `unit_price` DECIMAL(10,2) NOT NULL,
  `total_price` DECIMAL(10,2) NOT NULL,
  `tax_rate` DECIMAL(5,2) DEFAULT 0.00,
  `tax_amount` DECIMAL(10,2) DEFAULT 0.00,
  `created_at` TIMESTAMP NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  KEY `idx_quotation_items_company` (`company_id`),
  KEY `idx_quotation_item_quotation` (`quotation_id`),
  KEY `idx_quotation_item_type` (`item_type`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Invoices Table
CREATE TABLE `invoices` (
  `id` INT(11) NOT NULL AUTO_INCREMENT,
  `company_id` BIGINT UNSIGNED NOT NULL,
  `branch_id` BIGINT UNSIGNED DEFAULT NULL COMMENT 'Issuing branch',
  `service_id` INT(11) DEFAULT NULL COMMENT 'NULL for direct invoices not linked to a service',
  `invoice_number` VARCHAR(20) NOT NULL,
  `customer_id` INT(11) NOT NULL,
  `customer_name` VARCHAR(100) NOT NULL,
  `subtotal` DECIMAL(10,2) NOT NULL,
  `tax_amount` DECIMAL(10,2) DEFAULT 0.00,
  `discount_amount` DECIMAL(10,2) DEFAULT 0.00,
  `total_amount` DECIMAL(10,2) NOT NULL,
  `payment_method` ENUM('cash','card','upi','bank_transfer','other') DEFAULT NULL,
  `payment_date` TIMESTAMP NULL DEFAULT NULL,
  `bill_type` ENUM('cash','credit') DEFAULT 'cash' COMMENT 'Cash bill = immediate payment, Credit bill = pay later',
  `status` ENUM('active','cancelled') DEFAULT 'active',
  `created_at` TIMESTAMP NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  UNIQUE KEY `uk_company_invoice_number` (`company_id`, `invoice_number`),
  KEY `idx_invoices_company` (`company_id`),
  KEY `idx_invoices_branch` (`company_id`, `branch_id`),
  KEY `idx_invoices_company_date` (`company_id`, `created_at`),
  KEY `idx_invoices_company_status` (`company_id`, `status`),
  KEY `idx_invoice_service` (`service_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

ALTER TABLE `invoices` ADD `updated_at` TIMESTAMP NULL AFTER `created_at`;

-- Invoice Items Table
CREATE TABLE `invoice_items` (
  `id` INT(11) NOT NULL AUTO_INCREMENT,
  `company_id` BIGINT UNSIGNED NOT NULL,
  `invoice_id` INT(11) NOT NULL,
  `item_type` ENUM('service','inventory','labor','other') NOT NULL,
  `item_id` INT(11) DEFAULT NULL COMMENT 'Reference to inventory_items if type is inventory',
  `description` VARCHAR(255) NOT NULL,
  `quantity` DECIMAL(10,2) NOT NULL DEFAULT 1.00,
  `unit_price` DECIMAL(10,2) NOT NULL,
  `unit_cost` DECIMAL(10,2) DEFAULT 0.00 COMMENT 'FIFO Cost per unit at time of sale',
  `total_price` DECIMAL(10,2) NOT NULL,
  `total_cost` DECIMAL(10,2) DEFAULT 0.00 COMMENT 'Total FIFO Cost (COGS)',
  `tax_rate` DECIMAL(5,2) DEFAULT 0.00,
  `tax_amount` DECIMAL(10,2) DEFAULT 0.00,
  `created_at` TIMESTAMP NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  KEY `idx_invoice_items_company` (`company_id`),
  KEY `item_id` (`item_id`),
  KEY `idx_invoice_item_invoice` (`invoice_id`),
  KEY `idx_invoice_item_type` (`item_type`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Expense Categories Table
CREATE TABLE `expense_categories` (
  `id` INT(11) NOT NULL AUTO_INCREMENT,
  `company_id` BIGINT UNSIGNED NOT NULL,
  `category_name` VARCHAR(100) NOT NULL,
  `description` TEXT DEFAULT NULL,
  `is_active` TINYINT(1) DEFAULT 1,
  `created_at` TIMESTAMP NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  KEY `idx_exp_cat_company` (`company_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Expenses Table
CREATE TABLE `expenses` (
  `id` INT(11) NOT NULL AUTO_INCREMENT,
  `company_id` BIGINT UNSIGNED NOT NULL,
  `branch_id` BIGINT UNSIGNED DEFAULT NULL,
  `category_id` INT(11) NOT NULL,
  `expense_date` DATE NOT NULL,
  `amount` DECIMAL(10,2) NOT NULL,
  `reference_number` VARCHAR(50) DEFAULT NULL,
  `description` TEXT DEFAULT NULL,
  `paid_to` VARCHAR(100) DEFAULT NULL,
  `payment_method` ENUM('cash','card','bank_transfer','cheque','other') DEFAULT 'cash',
  `status` ENUM('pending','approved','rejected') DEFAULT 'pending',
  `approved_by` INT(11) DEFAULT NULL,
  `created_by` INT(11) NOT NULL,
  `created_at` TIMESTAMP NULL DEFAULT CURRENT_TIMESTAMP,
  `updated_at` TIMESTAMP NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  KEY `idx_expenses_company` (`company_id`),
  KEY `idx_expenses_branch` (`company_id`, `branch_id`),
  KEY `idx_expenses_date` (`expense_date`),
  KEY `idx_expenses_category` (`category_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ============================================================
-- CUSTOMER PAYMENTS MODULE (Accounts Receivable)
-- ============================================================

-- Customer Payments Table (Header)
CREATE TABLE `customer_payments` (
  `id` INT(11) NOT NULL AUTO_INCREMENT,
  `company_id` BIGINT UNSIGNED NOT NULL,
  `branch_id` BIGINT UNSIGNED DEFAULT NULL,
  `payment_number` VARCHAR(20) NOT NULL COMMENT 'Auto-generated: CPAY-YYYYMM-XXXX',
  `payment_date` DATE NOT NULL,
  `customer_id` INT(11) NOT NULL,
  `total_amount` DECIMAL(15,2) NOT NULL COMMENT 'Total payment amount received',
  `allocated_amount` DECIMAL(15,2) DEFAULT 0.00 COMMENT 'Amount allocated to Invoices',
  `unallocated_amount` DECIMAL(15,2) DEFAULT 0.00 COMMENT 'Advance/Overpayment',
  `status` ENUM('draft','confirmed','cancelled') DEFAULT 'draft',
  `notes` TEXT DEFAULT NULL,
  `created_by` INT(11) NOT NULL,
  `approved_by` INT(11) DEFAULT NULL,
  `approved_at` TIMESTAMP NULL DEFAULT NULL,
  `created_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
  `updated_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  UNIQUE KEY `uk_company_cpayment_number` (`company_id`, `payment_number`),
  KEY `idx_cp_customer` (`customer_id`),
  KEY `idx_cp_date` (`company_id`, `payment_date`),
  KEY `idx_cp_status` (`company_id`, `status`),
  CONSTRAINT `fk_cp_company` FOREIGN KEY (`company_id`) REFERENCES `companies`(`id`),
  CONSTRAINT `fk_cp_customer` FOREIGN KEY (`customer_id`) REFERENCES `customers`(`id`),
  CONSTRAINT `fk_cp_created_by` FOREIGN KEY (`created_by`) REFERENCES `users`(`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Customer Payment Methods (Multi-method support)
CREATE TABLE `customer_payment_methods` (
  `id` INT(11) NOT NULL AUTO_INCREMENT,
  `company_id` BIGINT UNSIGNED NOT NULL,
  `payment_id` INT(11) NOT NULL,
  `payment_method` ENUM('cash','cheque','bank_transfer','card','online','other') NOT NULL,
  `amount` DECIMAL(15,2) NOT NULL,
  `account_id` INT(11) DEFAULT NULL COMMENT 'Financial account to deposit to',
  `reference_number` VARCHAR(100) DEFAULT NULL COMMENT 'Cheque no, transfer ref, etc.',
  `bank_name` VARCHAR(100) DEFAULT NULL,
  `cheque_date` DATE DEFAULT NULL COMMENT 'For post-dated cheques',
  `cheque_status` ENUM('pending','cleared','bounced') DEFAULT NULL,
  `notes` TEXT DEFAULT NULL,
  `created_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  KEY `idx_cpm_payment` (`payment_id`),
  KEY `idx_cpm_cheque_status` (`cheque_status`, `cheque_date`),
  CONSTRAINT `fk_cpm_payment` FOREIGN KEY (`payment_id`) REFERENCES `customer_payments`(`id`) ON DELETE CASCADE,
  CONSTRAINT `fk_cpm_account` FOREIGN KEY (`account_id`) REFERENCES `financial_accounts`(`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Customer Payment Allocations (Links payments to Invoices)
CREATE TABLE `customer_payment_allocations` (
  `id` INT(11) NOT NULL AUTO_INCREMENT,
  `company_id` BIGINT UNSIGNED NOT NULL,
  `payment_id` INT(11) NOT NULL,
  `invoice_id` INT(11) NOT NULL,
  `allocated_amount` DECIMAL(15,2) NOT NULL COMMENT 'Amount applied to this Invoice',
  `created_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  UNIQUE KEY `uk_cpayment_invoice` (`payment_id`, `invoice_id`),
  KEY `idx_cpa_invoice` (`invoice_id`),
  CONSTRAINT `fk_cpa_payment` FOREIGN KEY (`payment_id`) REFERENCES `customer_payments`(`id`) ON DELETE CASCADE,
  CONSTRAINT `fk_cpa_invoice` FOREIGN KEY (`invoice_id`) REFERENCES `invoices`(`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;





-- Customer Ledger (Running balance for Accounts Receivable)
CREATE TABLE `customer_ledger` (
  `id` INT(11) NOT NULL AUTO_INCREMENT,
  `company_id` BIGINT UNSIGNED NOT NULL,
  `customer_id` INT(11) NOT NULL,
  `transaction_date` DATE NOT NULL,
  `transaction_type` ENUM('invoice','payment','adjustment','opening','refund') NOT NULL,
  `reference_id` INT(11) DEFAULT NULL COMMENT 'Invoice ID or Payment ID',
  `reference_number` VARCHAR(50) DEFAULT NULL,
  `debit_amount` DECIMAL(15,2) DEFAULT 0.00 COMMENT 'Invoice creates debit (customer owes)',
  `credit_amount` DECIMAL(15,2) DEFAULT 0.00 COMMENT 'Payment creates credit',
  `balance` DECIMAL(15,2) NOT NULL COMMENT 'Running balance (positive = customer owes)',
  `notes` TEXT DEFAULT NULL,
  `created_by` INT(11) DEFAULT NULL,
  `created_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  KEY `idx_cl_customer_date` (`company_id`, `customer_id`, `transaction_date`),
  KEY `idx_cl_type` (`transaction_type`),
  CONSTRAINT `fk_cl_customer` FOREIGN KEY (`customer_id`) REFERENCES `customers`(`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ============================================================
-- DEFAULT DATA INSERTS
-- ============================================================


CREATE TABLE `financial_accounts` (
  `id` INT(11) NOT NULL AUTO_INCREMENT,
  `company_id` BIGINT UNSIGNED NOT NULL,
  `branch_id` BIGINT UNSIGNED DEFAULT NULL,
  
  `account_type` ENUM('cash', 'bank') NOT NULL,
  `account_name` VARCHAR(100) NOT NULL COMMENT 'e.g. Petty Cash, BOC Current',
  
  -- Bank Specific
  `bank_name` VARCHAR(100) DEFAULT NULL,
  `account_number` VARCHAR(50) DEFAULT NULL,
  `currency` VARCHAR(10) DEFAULT 'LKR',
  
  -- Balances
  `opening_balance` DECIMAL(15,2) DEFAULT 0.00 COMMENT 'Balance at system start',
  `current_balance` DECIMAL(15,2) DEFAULT 0.00 COMMENT 'Current running balance',
  
  `is_active` TINYINT(1) DEFAULT 1,
  `is_default` TINYINT(1) DEFAULT 0 COMMENT 'Default account for the type',
  `created_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
  
  PRIMARY KEY (`id`),
  KEY `idx_fa_company` (`company_id`, `account_type`),
  CONSTRAINT `fk_fa_company` FOREIGN KEY (`company_id`) REFERENCES `companies`(`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- 2. Financial Transactions Table
-- Immutable ledger of all movements
CREATE TABLE `financial_transactions` (
  `id` BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
  `company_id` BIGINT UNSIGNED NOT NULL,
  `branch_id` BIGINT UNSIGNED DEFAULT NULL,
  `account_id` INT(11) NOT NULL,
  
  `transaction_date` DATE NOT NULL,
  `transaction_type` ENUM('income','expense','transfer_in','transfer_out','adjustment') NOT NULL,
  
  -- Amounts (Double Entry clarity)
  `debit_amount` DECIMAL(15,2) DEFAULT 0.00 COMMENT 'Money IN (Receipts)',
  `credit_amount` DECIMAL(15,2) DEFAULT 0.00 COMMENT 'Money OUT (Payments)',
  `balance_after` DECIMAL(15,2) NOT NULL COMMENT 'Running balance snapshot',
  
  -- Categorization
  `description` VARCHAR(255) NOT NULL,
  `category_type` VARCHAR(50) DEFAULT NULL COMMENT 'e.g., Sales, Rent, Salary',
  
  -- Linking to Source modules (Audit Trail)
  `reference_type` ENUM('invoice','supplier_payment','customer_payment','expense','salary','transfer','manual') DEFAULT 'manual',
  `reference_id` INT(11) DEFAULT NULL COMMENT 'ID of the source record',
  
  -- Audit
  `created_by` INT(11) NOT NULL,
  `created_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
  
  PRIMARY KEY (`id`),
  KEY `idx_ft_account_date` (`account_id`, `transaction_date`),
  KEY `idx_ft_company_date` (`company_id`, `transaction_date`),
  KEY `idx_ft_reference` (`reference_type`, `reference_id`),
  
  CONSTRAINT `fk_ft_account` FOREIGN KEY (`account_id`) REFERENCES `financial_accounts`(`id`),
  CONSTRAINT `fk_ft_company` FOREIGN KEY (`company_id`) REFERENCES `companies`(`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;


-- 3. Seed Default Accounts for Existing Companies
-- Create a Main Cash and Main Bank for Company 1 (and others if needed)
INSERT INTO `financial_accounts` (company_id, account_type, account_name, is_default)
SELECT id, 'cash', 'Main Cash', 1 FROM companies WHERE id = 1;

INSERT INTO `financial_accounts` (company_id, account_type, account_name, is_default)
SELECT id, 'bank', 'Main Bank Account', 1 FROM companies WHERE id = 1;


-- 4. Alter Existing Payment Tables to Link to Finance
-- Add account_id to payment methods to track WHERE money came from
ALTER TABLE `supplier_payment_methods` 
ADD COLUMN `account_id` INT(11) DEFAULT NULL AFTER `payment_method`,
ADD CONSTRAINT `fk_spm_account` FOREIGN KEY (`account_id`) REFERENCES `financial_accounts`(`id`);

-- Add account_id to Expenses
ALTER TABLE `expenses`
ADD COLUMN `account_id` INT(11) DEFAULT NULL AFTER `payment_method`,
ADD CONSTRAINT `fk_exp_account` FOREIGN KEY (`account_id`) REFERENCES `financial_accounts`(`id`);

-- 5. Add account_id to Invoices (Income)
ALTER TABLE `invoices`
ADD COLUMN `account_id` INT(11) DEFAULT NULL COMMENT 'Deposited to financial_account' AFTER `payment_date`,
ADD CONSTRAINT `fk_inv_account` FOREIGN KEY (`account_id`) REFERENCES `financial_accounts`(`id`);

-- 6. Add bill_type to Invoices (Cash vs Credit)
ALTER TABLE `invoices`
ADD COLUMN `bill_type` ENUM('cash','credit') DEFAULT 'cash' COMMENT 'Cash bill = immediate payment, Credit bill = pay later' AFTER `account_id`;


-- Customer Payments Table (Header)
CREATE TABLE IF NOT EXISTS `customer_payments` (
  `id` INT(11) NOT NULL AUTO_INCREMENT,
  `company_id` BIGINT UNSIGNED NOT NULL,
  `branch_id` BIGINT UNSIGNED DEFAULT NULL,
  `payment_number` VARCHAR(20) NOT NULL COMMENT 'Auto-generated: CPAY-YYYYMM-XXXX',
  `payment_date` DATE NOT NULL,
  `customer_id` INT(11) NOT NULL,
  `total_amount` DECIMAL(15,2) NOT NULL COMMENT 'Total payment amount received',
  `allocated_amount` DECIMAL(15,2) DEFAULT 0.00 COMMENT 'Amount allocated to Invoices',
  `unallocated_amount` DECIMAL(15,2) DEFAULT 0.00 COMMENT 'Advance/Overpayment',
  `status` ENUM('draft','confirmed','cancelled') DEFAULT 'draft',
  `notes` TEXT DEFAULT NULL,
  `created_by` INT(11) NOT NULL,
  `approved_by` INT(11) DEFAULT NULL,
  `approved_at` TIMESTAMP NULL DEFAULT NULL,
  `created_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
  `updated_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  UNIQUE KEY `uk_company_cpayment_number` (`company_id`, `payment_number`),
  KEY `idx_cp_customer` (`customer_id`),
  KEY `idx_cp_date` (`company_id`, `payment_date`),
  KEY `idx_cp_status` (`company_id`, `status`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Customer Payment Methods (Multi-method support)
CREATE TABLE IF NOT EXISTS `customer_payment_methods` (
  `id` INT(11) NOT NULL AUTO_INCREMENT,
  `company_id` BIGINT UNSIGNED NOT NULL,
  `payment_id` INT(11) NOT NULL,
  `payment_method` ENUM('cash','cheque','bank_transfer','card','online','other') NOT NULL,
  `amount` DECIMAL(15,2) NOT NULL,
  `account_id` INT(11) DEFAULT NULL COMMENT 'Financial account to deposit to',
  `reference_number` VARCHAR(100) DEFAULT NULL COMMENT 'Cheque no, transfer ref, etc.',
  `bank_name` VARCHAR(100) DEFAULT NULL,
  `cheque_date` DATE DEFAULT NULL COMMENT 'For post-dated cheques',
  `cheque_status` ENUM('pending','cleared','bounced') DEFAULT NULL,
  `notes` TEXT DEFAULT NULL,
  `created_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  KEY `idx_cpm_payment` (`payment_id`),
  KEY `idx_cpm_cheque_status` (`cheque_status`, `cheque_date`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Customer Payment Allocations (Links payments to Invoices)
CREATE TABLE IF NOT EXISTS `customer_payment_allocations` (
  `id` INT(11) NOT NULL AUTO_INCREMENT,
  `company_id` BIGINT UNSIGNED NOT NULL,
  `payment_id` INT(11) NOT NULL,
  `invoice_id` INT(11) NOT NULL,
  `allocated_amount` DECIMAL(15,2) NOT NULL COMMENT 'Amount applied to this Invoice',
  `created_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  UNIQUE KEY `uk_cpayment_invoice` (`payment_id`, `invoice_id`),
  KEY `idx_cpa_invoice` (`invoice_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Customer Ledger (Running balance for Accounts Receivable)
CREATE TABLE IF NOT EXISTS `customer_ledger` (
  `id` INT(11) NOT NULL AUTO_INCREMENT,
  `company_id` BIGINT UNSIGNED NOT NULL,
  `customer_id` INT(11) NOT NULL,
  `transaction_date` DATE NOT NULL,
  `transaction_type` ENUM('invoice','payment','adjustment','opening','refund') NOT NULL,
  `reference_id` INT(11) DEFAULT NULL COMMENT 'Invoice ID or Payment ID',
  `reference_number` VARCHAR(50) DEFAULT NULL,
  `debit_amount` DECIMAL(15,2) DEFAULT 0.00 COMMENT 'Invoice creates debit (customer owes)',
  `credit_amount` DECIMAL(15,2) DEFAULT 0.00 COMMENT 'Payment creates credit',
  `balance` DECIMAL(15,2) NOT NULL COMMENT 'Running balance (positive = customer owes)',
  `notes` TEXT DEFAULT NULL,
  `created_by` INT(11) DEFAULT NULL,
  `created_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  KEY `idx_cl_customer_date` (`company_id`, `customer_id`, `transaction_date`),
  KEY `idx_cl_type` (`transaction_type`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

ALTER TABLE `customer_payments` ADD CONSTRAINT `fk_cp_company` FOREIGN KEY (`company_id`) REFERENCES `companies`(`id`);
ALTER TABLE `customer_payments` ADD CONSTRAINT `fk_cp_customer` FOREIGN KEY (`customer_id`) REFERENCES `customers`(`id`);
ALTER TABLE `customer_payments` ADD CONSTRAINT `fk_cp_created_by` FOREIGN KEY (`created_by`) REFERENCES `users`(`id`);
ALTER TABLE `customer_payment_methods` ADD CONSTRAINT `fk_cpm_payment` FOREIGN KEY (`payment_id`) REFERENCES `customer_payments`(`id`) ON DELETE CASCADE;
ALTER TABLE `customer_payment_methods` ADD CONSTRAINT `fk_cpm_account` FOREIGN KEY (`account_id`) REFERENCES `financial_accounts`(`id`);
ALTER TABLE `customer_payment_allocations` ADD CONSTRAINT `fk_cpa_payment` FOREIGN KEY (`payment_id`) REFERENCES `customer_payments`(`id`) ON DELETE CASCADE;
ALTER TABLE `customer_payment_allocations` ADD CONSTRAINT `fk_cpa_invoice` FOREIGN KEY (`invoice_id`) REFERENCES `invoices`(`id`);
ALTER TABLE `customer_ledger` ADD CONSTRAINT `fk_cl_customer` FOREIGN KEY (`customer_id`) REFERENCES `customers`(`id`);


-- Insert default company
INSERT INTO `companies` (`id`, `company_code`, `name`, `package_type`, `status`, `settings_json`) VALUES
(1, 'default', 'Default Company', 'pro', 'active', JSON_OBJECT(
  'features', JSON_OBJECT(
    'inventory_enabled', TRUE,
    'quotations_enabled', TRUE,
    'bookings_enabled', TRUE,
    'reports_enabled', TRUE,
    'multi_branch', FALSE
  ),
  'branding', JSON_OBJECT(
    'primary_color', '#3B82F6',
    'logo_url', NULL
  ),
  'notifications', JSON_OBJECT(
    'sms_enabled', FALSE,
    'email_enabled', TRUE
  ),
  'tax', JSON_OBJECT(
    'enabled', TRUE,
    'rate', 15.0
  )
));

-- Insert default roles (Global)
INSERT INTO `roles` (`id`, `role_name`, `description`, `is_system_role`) VALUES
(1, 'Super Admin', 'Full system access', 1),
(2, 'Admin', 'Administrative access', 1),
(3, 'Manager', 'Service management and reports', 1),
(4, 'Supervisor', 'Monitor services and employees', 0),
(5, 'Technician', 'Service execution', 1),
(6, 'Receptionist', 'Customer and vehicle registration', 0),
(7, 'Viewer', 'Read-only access to reports', 0);

-- Insert default permissions (Global)
INSERT INTO `permissions` (`id`, `permission_name`, `permission_code`, `description`) VALUES
(1, 'View', 'view', 'Can view the page/module'),
(2, 'Create', 'create', 'Can create new records'),
(3, 'Edit', 'edit', 'Can edit existing records'),
(4, 'Delete', 'delete', 'Can delete records'),
(5, 'Export', 'export', 'Can export data to Excel/PDF'),
(6, 'Print', 'print', 'Can print documents'),
(7, 'Approve', 'approve', 'Can approve/reject items'),
(8, 'Assign', 'assign', 'Can assign tasks to others');

-- Insert default pages (Global)
-- Insert default pages (Global)
-- Insert default pages (Global)
INSERT INTO `pages` (`id`, `page_name`, `page_route`, `page_category`, `description`, `icon`, `display_order`, `is_active`) VALUES
(1, 'Dashboard', 'views/Dashboard/', 'Dashboard', 'Main Dashboard', 'fas fa-th-large', 1, 1),
(2, 'Services', 'views/Service/', 'Service Management', 'Track active services', 'fas fa-wrench', 10, 1),
(3, 'Service Packages', 'views/Service Package/', 'Service Management', 'Manage service packages', 'fas fa-cubes', 11, 1),
(4, 'Service Stages', 'views/ServiceStage/', 'Service Management', 'Manage workflow stages', 'fas fa-stream', 12, 1),
(5, 'Customers', 'views/Customer/', 'Customer & Vehicle', 'Manage customers', 'fas fa-user-tie', 20, 1),
(6, 'Vehicles', 'views/Vehicle/', 'Customer & Vehicle', 'Manage vehicles', 'fas fa-car-alt', 21, 1),
(7, 'Bookings', 'views/Booking/approve.php', 'Service Management', 'Manage appointments', 'fas fa-calendar-check', 13, 1),
(8, 'Users', 'views/User/', 'System Admin', 'Manage system users', 'fas fa-users-cog', 80, 1),
(9, 'Roles', 'views/Role/', 'System Admin', 'Manage user roles', 'fas fa-user-shield', 81, 1),
(10, 'Permissions', 'views/Permission/', 'System Admin', 'Manage permissions', 'fas fa-lock', 82, 1),
(11, 'Pages', 'views/Page/', 'System Admin', 'Manage system pages', 'fas fa-file-alt', 83, 1),
(12, 'User Permissions', 'views/UserPermission/', 'System Admin', 'Assign permissions', 'fas fa-user-lock', 84, 1),
(13, 'Suppliers', 'views/Supplier/', 'Inventory', 'Manage supplier information', 'fas fa-truck', 50, 1),
(14, 'Inventory Categories', 'views/InventoryCategory/', 'Inventory', 'Organize inventory items by category', 'fas fa-layer-group', 51, 1),
(15, 'Inventory Items', 'views/InventoryItem/', 'Inventory', 'Manage stock items and products', 'fas fa-box', 52, 1),
(16, 'GRN', 'views/GRN/', 'Inventory', 'Goods Receipt Notes - incoming stock', 'fas fa-file-invoice', 53, 1),
(17, 'Invoices', 'views/Invoice/', 'Sales & Billing', 'Manage invoices', 'fas fa-file-invoice-dollar', 40, 1),
(18, 'Employee List', 'views/Employee/', 'Employees', 'Manage employees', 'fas fa-users', 60, 1),
(19, 'Earnings & Payments', 'views/Employee/earnings.php', 'Employees', 'View earnings', 'fas fa-money-bill-wave', 61, 1),
(20, 'Employee Salary Report', 'views/Reports/EmployeeSalary/', 'Reports', 'View employee salaries', 'fas fa-file-invoice-dollar', 30, 1),
(21, 'Company Profile', 'views/Settings/', 'System Admin', 'Company settings', 'fas fa-building', 85, 1),
(22, 'Quotations', 'views/Quotation/', 'Sales & Billing', 'Manage quotations', 'fas fa-file-contract', 41, 1),
(23, 'Expenses', 'views/Expenses/', 'Sales & Billing', 'Manage company expenses', 'fas fa-receipt', 42, 1),
(24, 'Service List', 'views/Service/service_list.php', 'Service Management', 'List all services', 'fas fa-list-alt', 14, 1),
(25, 'Sales Summary', 'views/Reports/', 'Reports', 'Sales Overview', 'fas fa-chart-line', 70, 1),
(26, 'Customer Sales', 'views/Reports/CustomerSales/', 'Reports', 'Customer Sales Report', 'fas fa-users', 71, 1),
(27, 'Service History', 'views/Reports/ServiceHistory/', 'Reports', 'Service History Report', 'fas fa-history', 72, 1),
(28, 'Stock Report', 'views/Reports/Stock/', 'Reports', 'Inventory Stock Report', 'fas fa-boxes', 73, 1),
(29, 'Module Visibility', 'views/ModuleVisibility/', 'System Admin', 'Manage Module Visibility', 'fas fa-eye', 86, 1),
(30, 'UI Visibility', 'views/UIVisibility/', 'System Admin', 'Manage UI Visibility', 'fas fa-layer-group', 87, 1),
(31, 'Customer Vehicles', 'views/Reports/CustomerVehicles/', 'Reports', 'Customer Vehicles Report', 'fas fa-car-alt', 74, 1),
(32, 'Supplier Payments', 'views/SupplierPayment/', 'Inventory', 'Pay suppliers and settle GRNs', 'fas fa-money-check-alt', 54, 1),
(33, 'Customer Payments', 'views/CustomerPayment/', 'Sales & Billing', 'Receive payments from customers and settle invoices', 'fas fa-hand-holding-usd', 43, 1);

INSERT INTO `pages` (`page_name`, `page_route`, `page_category`, `description`, `icon`, `display_order`, `is_active`) VALUES
('Cashbook', 'views/Finance/', 'Finance', 'Daily Cash & Bank Ledger', 'fas fa-book', 55, 1),
('Financial Accounts', 'views/Finance/accounts.php', 'Finance', 'Manage Cash & Bank Accounts', 'fas fa-wallet', 56, 1),
('Inventory Import', 'views/InventoryItem/import.php', 'Inventory', 'Bulk import products from Excel/CSV', 'fas fa-file-import', 53, 1);

-- Insert default branch for company 1
INSERT INTO `branches` (`id`, `company_id`, `branch_code`, `branch_name`, `address`, `is_main`, `is_active`) VALUES
(1, 1, 'MAIN', 'Main Branch', 'Head Office', 1, 1);

-- Insert default admin user for company 1
INSERT INTO `users` (`id`, `company_id`, `branch_id`, `username`, `role_id`, `password_hash`, `is_active`) VALUES
(1, 1, NULL, 'admin', 1, '$2y$10$svwuxC4hAgU6NAIRZVxBfOSEUICs/MuDTI5t1rjHIn4nAUEMzZN1S', 1);

-- Insert default service packages for company 1
INSERT INTO `service_packages` (`company_id`, `package_name`, `description`, `base_price`, `estimated_duration`, `is_active`) VALUES
(1, 'Basic Wash', 'Exterior wash and dry', 500.00, 30, 1),
(1, 'Premium Wash', 'Exterior + Interior cleaning + Polishing', 1200.00, 60, 1),
(1, 'Full Service', 'Complete detailing + Underbody + Polishing + Interior', 2500.00, 120, 1),
(1, 'Express Wash', 'Quick exterior wash', 300.00, 15, 1);

-- Insert default service stages for company 1
INSERT INTO `service_stages` (`company_id`, `stage_name`, `stage_order`, `icon`, `estimated_duration`) VALUES
(1, 'Registration', 1, 'fas fa-clipboard-lis', 5),
(1, 'Pending', 2, 'fas fa-clock', 5),
(1, 'Washing', 3, 'fas fa-spray-can', 15),
(1, 'Underbody Cleaning', 4, 'fas fa-water', 20),
(1, 'Drying', 5, 'fas fa-wind', 10),
(1, 'Polishing', 6, 'fas fa-gem', 25),
(1, 'Quality Check', 7, 'fas fa-clipboard-che', 10),
(1, 'Ready for Delivery', 8, 'fas fa-box-open', 5),
(1, 'Delivered', 9, 'fas fa-check-circle', 5),
(1, 'Cancelled', 10, 'fas fa-times-circle', 5);

-- Insert default time slots for company 1
INSERT INTO `time_slots` (`company_id`, `slot_start`, `slot_end`, `max_bookings`, `is_active`) VALUES
(1, '08:00:00', '09:00:00', 3, 1),
(1, '09:00:00', '10:00:00', 3, 1),
(1, '10:00:00', '11:00:00', 3, 1),
(1, '11:00:00', '12:00:00', 3, 1),
(1, '12:00:00', '13:00:00', 2, 1),
(1, '13:00:00', '14:00:00', 3, 1),
(1, '14:00:00', '15:00:00', 3, 1),
(1, '15:00:00', '16:00:00', 3, 1),
(1, '16:00:00', '17:00:00', 3, 1),
(1, '17:00:00', '18:00:00', 2, 1);

-- Insert default inventory categories for company 1
INSERT INTO `inventory_categories` (`company_id`, `category_name`, `description`, `is_active`) VALUES
(1, 'Engine Parts', NULL, 1),
(1, 'Brakes & Suspension', NULL, 1),
(1, 'Electrical & Electronics', NULL, 1),
(1, 'Body Parts', NULL, 1),
(1, 'Lubricants & Fluids', NULL, 1),
(1, 'Tires & Wheels', NULL, 1),
(1, 'Filters', NULL, 1);

-- Insert default suppliers for company 1
INSERT INTO `suppliers` (`company_id`, `supplier_name`, `contact_person`, `phone`, `email`, `address`, `is_active`) VALUES
(1, 'AutoParts Direct', 'Kamal Perera', '0771234567', 'sales@autoparts.lk', '123 Darley Rd, Colombo 10', 1),
(1, 'Lubricants Plus', 'Nimal Silva', '0719876543', 'orders@lubeplus.lk', '45 High Level Rd, Nugegoda', 1),
(1, 'City Tyres', 'Ravi Kumar', '0765554444', 'info@citytyres.lk', '88 Galle Rd, Dehiwala', 1);

-- Insert default inventory items for company 1
INSERT INTO `inventory_items` (`company_id`, `item_code`, `item_name`, `description`, `category_id`, `unit_of_measure`, `current_stock`, `reorder_level`, `unit_cost`, `unit_price`, `is_active`) VALUES
(1, 'OIL-HELIX-4L', 'Shell Helix HX7 10W-40 (4L)', 'Semi-synthetic motor oil', 5, 'can', 20.00, 5.00, 4500.00, 5800.00, 1),
(1, 'BRK-TOY-COR', 'Brake Pads - Toyota Corolla', 'Front brake pads set', 2, 'set', 10.00, 3.00, 8500.00, 12500.00, 1),
(1, 'FLT-OIL-TOY', 'Oil Filter - Toyota Genuine', 'Compatible with Corolla/Axio', 7, 'pcs', 50.00, 10.00, 1500.00, 2200.00, 1),
(1, 'FLT-AIR-UNI', 'Air Filter - Universal', 'High flow air filter', 7, 'pcs', 15.00, 5.00, 2000.00, 3500.00, 1),
(1, 'TYR-195-65-15', 'Dunlop 195/65R15', 'All-season passenger car tire', 6, 'pcs', 8.00, 4.00, 18000.00, 24500.00, 1);

-- Insert default customers for company 1
INSERT INTO `customers` (`company_id`, `name`, `phone`, `email`, `address`) VALUES
(1, 'John Doe', '0770000001', 'john@example.com', '15 Flower Rd, Colombo 03'),
(1, 'Jane Smith', '0770000002', 'jane@example.com', '22 Park St, Colombo 02'),
(1, 'Robert Brown', '0770000003', 'robert@example.com', '5 Beach Rd, Mount Lavinia');

-- Insert default vehicles for company 1
INSERT INTO `vehicles` (`company_id`, `customer_id`, `registration_number`, `make`, `model`, `year`, `color`, `current_mileage`) VALUES
(1, 1, 'CAB-1234', 'Toyota', 'Corolla Axio', 2018, 'White', 45000),
(1, 2, 'WP-5678', 'Honda', 'Civic', 2020, 'Black', 25000),
(1, 3, 'KW-9999', 'Nissan', 'Sunny', 2015, 'Silver', 85000);


-- Insert default expense categories for company 1
INSERT INTO `expense_categories` (`company_id`, `category_name`, `description`, `is_active`) VALUES
(1, 'Rent', 'Monthly office rent', 1),
(1, 'Utilities', 'Electricity, Water, Internet', 1),
(1, 'Office Supplies', 'Stationery, coffee, etc.', 1),
(1, 'Salaries', 'Employee salaries', 1),
(1, 'Equipment', 'Tools and machinery', 1),
(1, 'Marketing', 'Ads and promotions', 1),
(1, 'Maintenance', 'Repairs and upkeep', 1),
(1, 'Fuel & Transport', 'Fuel for company vehicles', 1),
(1, 'Insurance', 'Business and vehicle insurance', 1),
(1, 'Software Subscriptions', 'SaaS and software licenses', 1),
(1, 'Taxes & Licenses', 'Business operational costs', 1),
(1, 'Other', 'Miscellaneous expenses', 1);

-- Transaction to ensure data integrity
START TRANSACTION;
-- ============================================================
-- Migration Complete
-- ============================================================
CREATE TABLE IF NOT EXISTS `company_ui_settings` (
  `id` BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
  `company_id` BIGINT UNSIGNED NOT NULL,
  `page_id` INT(11) DEFAULT NULL COMMENT 'Link to pages table. NULL if global component',
  `component_key` VARCHAR(100) NOT NULL COMMENT 'Unique identifier string for the UI part',
  `description` VARCHAR(255) DEFAULT NULL COMMENT 'Human readable name for the admin UI',
  `is_visible` TINYINT(1) NOT NULL DEFAULT 0 COMMENT '1 = Show, 0 = Hide',
  `created_by` INT(11) NOT NULL COMMENT 'Super Admin ID who created this rule',
  `updated_by` INT(11) DEFAULT NULL,
  `created_at` TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `updated_at` TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  UNIQUE KEY `uk_company_page_component` (`company_id`, `page_id`, `component_key`), 
  KEY `idx_lookup` (`company_id`, `page_id`, `is_visible`),
  KEY `idx_component` (`component_key`),
  CONSTRAINT `fk_ui_company` FOREIGN KEY (`company_id`) REFERENCES `companies` (`id`) ON DELETE CASCADE,
  CONSTRAINT `fk_ui_page` FOREIGN KEY (`page_id`) REFERENCES `pages` (`id`) ON DELETE CASCADE,
  CONSTRAINT `fk_ui_creator` FOREIGN KEY (`created_by`) REFERENCES `users` (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- 1. Create the new ui_components table
CREATE TABLE IF NOT EXISTS `ui_components` (
  `id` INT(11) NOT NULL AUTO_INCREMENT,
  `component_key` VARCHAR(50) NOT NULL COMMENT 'Unique identifier string',
  `name` VARCHAR(100) NOT NULL COMMENT 'Human readable name',
  `category` VARCHAR(50) NOT NULL COMMENT 'Grouping category',
  `icon` VARCHAR(50) DEFAULT 'fa-cog' COMMENT 'FontAwesome icon class',
  `description` TEXT DEFAULT NULL,
  `is_active` TINYINT(1) DEFAULT 1,
  `created_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  UNIQUE KEY `uk_component_key` (`component_key`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- 2. Insert default components (Migrating from PHP Code)
INSERT INTO `ui_components` (`category`, `component_key`, `name`, `icon`) VALUES
-- Dashboard
('Dashboard', 'dashboard_stats', 'Statistics Cards', 'fa-chart-bar'),
('Dashboard', 'dashboard_recent_sales', 'Recent Sales Table', 'fa-table'),
('Dashboard', 'dashboard_charts', 'Performance Charts', 'fa-chart-pie'),

-- Invoices
('Invoices', 'invoice_create', 'Create Invoice Button', 'fa-plus'),
('Invoices', 'invoice_delete', 'Delete Invoice Button', 'fa-trash'),
('Invoices', 'invoice_export', 'Export Options', 'fa-file-export'),

-- Expenses
('Expenses', 'expense_create', 'Create Expense', 'fa-plus'),
('Expenses', 'expense_approve', 'Approve Expense', 'fa-check'),

-- Customers
('Customers', 'customer_phone', 'Phone Number (View)', 'fa-phone'),
('Customers', 'customer_edit', 'Edit Customer', 'fa-edit'),

-- Reports
('Reports', 'reports_sales', 'Sales Reports', 'fa-chart-line'),
('Reports', 'reports_stock', 'Stock Reports', 'fa-boxes')
ON DUPLICATE KEY UPDATE name = VALUES(name), icon = VALUES(icon), category = VALUES(category);

-- 3. Alter company_ui_settings to add the new foreign key column
-- We check if column exists first to avoid errors on re-runs (MySQL 5.7+ specific, using simple ADD ignore logic often fails, so we just ADD)
-- If this fails it implies it might already exist, but for a one-shot migration:
ALTER TABLE `company_ui_settings` ADD COLUMN `ui_component_id` INT(11) DEFAULT NULL AFTER `page_id`;

-- 4. Migrate existing data: Link settings to component IDs based on the key
UPDATE `company_ui_settings` s
JOIN `ui_components` c ON s.component_key = c.component_key
SET s.ui_component_id = c.id;

-- 5. Add Foreign Key Constraint
ALTER TABLE `company_ui_settings` ADD CONSTRAINT `fk_ui_settings_component` 
FOREIGN KEY (`ui_component_id`) REFERENCES `ui_components` (`id`) ON DELETE CASCADE;

-- 6. Cleanup: Drop the old string key column
-- Only do this if migration was successful (ui_component_id is populated)
-- We'll assume success if we reach here in the transaction 

-- ============================================================
-- Multi-Package Service Job Migration
-- Creates service_items table and modifies services table
-- Generated: 2026-01-03
-- ============================================================

SET FOREIGN_KEY_CHECKS = 0;

-- 1. Create service_items table
CREATE TABLE IF NOT EXISTS `service_items` (
  `id` INT(11) NOT NULL AUTO_INCREMENT,
  `company_id` BIGINT UNSIGNED NOT NULL,
  `service_id` INT(11) NOT NULL,
  `item_type` ENUM('package', 'custom', 'inventory') NOT NULL DEFAULT 'package',
  `related_id` INT(11) DEFAULT NULL COMMENT 'package_id if type=package, item_id if type=inventory',
  `item_name` VARCHAR(255) NOT NULL,
  `description` TEXT DEFAULT NULL,
  `unit_price` DECIMAL(10,2) NOT NULL DEFAULT 0.00,
  `quantity` DECIMAL(10,2) NOT NULL DEFAULT 1.00,
  `discount_amount` DECIMAL(10,2) NOT NULL DEFAULT 0.00,
  `total_price` DECIMAL(10,2) NOT NULL DEFAULT 0.00,
  `created_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
  `updated_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  KEY `idx_service_items_company` (`company_id`),
  KEY `idx_service_items_service` (`service_id`),
  KEY `idx_service_items_type` (`item_type`),
  CONSTRAINT `fk_service_items_service` FOREIGN KEY (`service_id`) REFERENCES `services`(`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- 2. Alter services table to make package_id nullable (backward compatibility)
ALTER TABLE `services` MODIFY `package_id` INT(11) NULL;

-- 3. Migrate existing services to service_items (one-time data migration)
-- This creates a service_item for each existing service based on its package_id
INSERT INTO `service_items` (company_id, service_id, item_type, related_id, item_name, unit_price, quantity, total_price)
SELECT 
    s.company_id,
    s.id,
    'package',
    s.package_id,
    COALESCE(sp.package_name, 'Legacy Service'),
    COALESCE(sp.base_price, s.total_amount),
    1,
    COALESCE(sp.base_price, s.total_amount)
FROM services s
LEFT JOIN service_packages sp ON s.package_id = sp.id
WHERE s.package_id IS NOT NULL
AND NOT EXISTS (SELECT 1 FROM service_items si WHERE si.service_id = s.id); 


-- Add Report Pages to pages table
-- Run this SQL to register all report pages for proper permission control
-- Routes must match the $directoryToRoute mapping in UserPermission.php

-- First, check if Reports parent page exists, if not create it
INSERT INTO pages (page_name, page_route, page_category, description, icon, display_order, is_active, parent_page_id)
SELECT 'Reports', '/reports', 'Reports', 'Reports Module - Sales Summary', 'fas fa-chart-bar', 100, 1, NULL
WHERE NOT EXISTS (SELECT 1 FROM pages WHERE page_route = '/reports');

-- Get the Reports parent page ID
SET @reports_parent_id = (SELECT id FROM pages WHERE page_route = '/reports' LIMIT 1);

-- Insert Customer Sales Report (subdirectory: CustomerSales)
INSERT INTO pages (page_name, page_route, page_category, description, icon, display_order, is_active, parent_page_id)
SELECT 'Customer Sales', '/customer-sales', 'Reports', 'Customer Sales Report', 'fas fa-users', 102, 1, @reports_parent_id
WHERE NOT EXISTS (SELECT 1 FROM pages WHERE page_route = '/customer-sales');

-- Insert Employee Salary Report (subdirectory: EmployeeSalary)  
-- Note: Maps to 'EmployeeSalaryReport' in directoryToRoute, but directory is 'EmployeeSalary'
INSERT INTO pages (page_name, page_route, page_category, description, icon, display_order, is_active, parent_page_id)
SELECT 'Employee Salary', '/employee-salary-report', 'Reports', 'Employee Salary Report', 'fas fa-money-bill-wave', 103, 1, @reports_parent_id
WHERE NOT EXISTS (SELECT 1 FROM pages WHERE page_route = '/employee-salary-report');

-- Insert Service History Report (subdirectory: ServiceHistory)
INSERT INTO pages (page_name, page_route, page_category, description, icon, display_order, is_active, parent_page_id)
SELECT 'Service History', '/service-history', 'Reports', 'Service History Report', 'fas fa-history', 104, 1, @reports_parent_id
WHERE NOT EXISTS (SELECT 1 FROM pages WHERE page_route = '/service-history');

-- Insert Stock Report (subdirectory: Stock within Reports)
INSERT INTO pages (page_name, page_route, page_category, description, icon, display_order, is_active, parent_page_id)
SELECT 'Stock Report', '/stock-report', 'Reports', 'Stock/Inventory Report', 'fas fa-boxes', 105, 1, @reports_parent_id
WHERE NOT EXISTS (SELECT 1 FROM pages WHERE page_route = '/stock-report');

-- Show the added pages
SELECT id, page_name, page_route, parent_page_id FROM pages WHERE page_category = 'Reports';



-- ============================================================
-- FINALIZATION
-- ============================================================

SET FOREIGN_KEY_CHECKS = 1;
COMMIT;

/*!40101 SET CHARACTER_SET_CLIENT=@OLD_CHARACTER_SET_CLIENT */;
/*!40101 SET CHARACTER_SET_RESULTS=@OLD_CHARACTER_SET_RESULTS */;
/*!40101 SET COLLATION_CONNECTION=@OLD_COLLATION_CONNECTION */;
