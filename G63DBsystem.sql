CREATE DATABASE IF NOT EXISTS `G63System` DEFAULT CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;
USE `G63System`;

-- ==========================================
-- 1. LOOKUP TABLES
-- ==========================================

CREATE TABLE `driver_certification` (
    `driver_certification_id` INT UNSIGNED NOT NULL AUTO_INCREMENT,
    `driver_certification_name` VARCHAR(100) NOT NULL,
    PRIMARY KEY(`driver_certification_id`),
    UNIQUE KEY `uq_driverCertificationName` (`driver_certification_name`)
) ENGINE=InnoDB;

CREATE TABLE `vehicle_category` (
    `category_id` INT UNSIGNED NOT NULL AUTO_INCREMENT,
    `category_name` VARCHAR(100) NOT NULL,
    PRIMARY KEY(`category_id`)
) ENGINE=InnoDB;

CREATE TABLE `depot` (
    `depot_id` INT UNSIGNED NOT NULL AUTO_INCREMENT,
    `depot_name` VARCHAR(100) NOT NULL,
    `depot_location` VARCHAR(255) NOT NULL,
    PRIMARY KEY(`depot_id`)
) ENGINE=InnoDB;

CREATE TABLE `manufacturer` (
    `manufacturer_id` INT UNSIGNED NOT NULL AUTO_INCREMENT,
    `manufacturer_name` VARCHAR(255),
    `contact_info` VARCHAR(100) NOT NULL,
    PRIMARY KEY(`manufacturer_id`)
) ENGINE=InnoDB;

CREATE TABLE `severity_level` (
    `severity_id` INT UNSIGNED NOT NULL AUTO_INCREMENT,
    `level_name` ENUM ('LOW', 'MEDIUM', 'HIGH', 'CRITICAL') NOT NULL,
    `points_deducted` INT NOT NULL,
    `deduction_condition` VARCHAR(255) NULL,
    `additional_deduction` INT DEFAULT 0,
    PRIMARY KEY(`severity_id`)
) ENGINE=InnoDB;

CREATE TABLE `mechanic_certification` (
    `mechanic_certification_id` SMALLINT UNSIGNED NOT NULL AUTO_INCREMENT,
    `mechanic_certification_name` VARCHAR(255) NOT NULL,
    PRIMARY KEY (`mechanic_certification_id`)
) ENGINE=InnoDB;

CREATE TABLE `activity_type` (
    `activity_type_id` SMALLINT UNSIGNED NOT NULL AUTO_INCREMENT,
    `activity_type_name` VARCHAR(255) NOT NULL,
    PRIMARY KEY (`activity_type_id`)
) ENGINE=InnoDB;

CREATE TABLE `alert_type` (
    `alert_type_id` SMALLINT UNSIGNED NOT NULL AUTO_INCREMENT,
    `alert_name` VARCHAR(255) NOT NULL,
    `description` VARCHAR(255) NOT NULL,
    PRIMARY KEY (`alert_type_id`)
) ENGINE=InnoDB;

CREATE TABLE `event_type` (
    `event_type_id` SMALLINT UNSIGNED NOT NULL AUTO_INCREMENT,
    `event_type_name` VARCHAR(100) NOT NULL,
    PRIMARY KEY (`event_type_id`)
) ENGINE=InnoDB;

CREATE TABLE `supplier` (
    `supplier_id` INT UNSIGNED NOT NULL AUTO_INCREMENT,
    `supplier_name` VARCHAR(100) NOT NULL,
    `contact_info` VARCHAR(100) NOT NULL,
    `delivery_lead_time_days` INT UNSIGNED NULL,
    PRIMARY KEY (`supplier_id`)
) ENGINE=InnoDB;

-- ==========================================
-- 2. Fleet & Driver Management
-- ==========================================

CREATE TABLE `category_required_certification` (
    `driver_certification_id` INT UNSIGNED NOT NULL,
    `category_id` INT UNSIGNED NOT NULL,
    PRIMARY KEY (`driver_certification_id`, `category_id`),
    FOREIGN KEY (`driver_certification_id`) REFERENCES `driver_certification`(`driver_certification_id`) ON DELETE RESTRICT ON UPDATE CASCADE,
    FOREIGN KEY (`category_id`) REFERENCES `vehicle_category`(`category_id`) ON DELETE RESTRICT ON UPDATE CASCADE
) ENGINE=InnoDB;

CREATE TABLE `driver` (
    `driver_id` INT UNSIGNED NOT NULL AUTO_INCREMENT,
    `full_name` VARCHAR(255) NOT NULL,
    `contact_info` VARCHAR(100) NOT NULL,
    `licence_type` VARCHAR(100) NOT NULL,
    `licence_expiry_date` DATE NOT NULL,
    `depot_id` INT UNSIGNED NOT NULL,
    `employment_status` ENUM('ACTIVE', 'ON LEAVE', 'SUSPENDED', 'TERMINATED') NOT NULL DEFAULT 'ACTIVE',
    `emergency_contact_details` VARCHAR(100) NOT NULL,
    PRIMARY KEY (`driver_id`),
    FOREIGN KEY (`depot_id`) REFERENCES `depot`(`depot_id`) ON DELETE RESTRICT ON UPDATE CASCADE,
    INDEX `idx_employment_status` (`employment_status`)
) ENGINE=InnoDB;

CREATE TABLE `vehicle` (
    `VIN` VARCHAR(17) NOT NULL,
    `category_id` INT UNSIGNED NOT NULL,
    `registration_number` VARCHAR(100) NOT NULL,
    `manufacturer_id` INT UNSIGNED NOT NULL,
    `model` VARCHAR(100) NOT NULL,
    `year_of_manufacture` INT NOT NULL,
    `current_odometer` DECIMAL(10,1) NOT NULL DEFAULT 0.0,
    `depot_id` INT UNSIGNED NOT NULL,
    `vehicle_status` ENUM('ACTIVE', 'AVAILABLE', 'UNDER MAINTENANCE', 'AWAITING INSPECTION', 'OUT OF SERVICE', 'RETIRED') NOT NULL DEFAULT 'AVAILABLE',
    PRIMARY KEY (`VIN`),
    UNIQUE KEY `uq_registrationNumber` (`registration_number`),
    FOREIGN KEY (`category_id`) REFERENCES `vehicle_category`(`category_id`) ON DELETE RESTRICT ON UPDATE CASCADE,
    FOREIGN KEY (`manufacturer_id`) REFERENCES `manufacturer`(`manufacturer_id`) ON DELETE RESTRICT ON UPDATE CASCADE,
    FOREIGN KEY (`depot_id`) REFERENCES `depot`(`depot_id`) ON DELETE RESTRICT ON UPDATE CASCADE
) ENGINE=InnoDB;

CREATE TABLE `driver_certification_record` (
    `record_id` INT UNSIGNED NOT NULL AUTO_INCREMENT,
    `driver_certification_id` INT UNSIGNED NOT NULL,
    `driver_id` INT UNSIGNED NOT NULL,
    `issue_date` DATE NOT NULL,
    `expiration_date` DATE NOT NULL,
    PRIMARY KEY (`record_id`),
    UNIQUE KEY `uq_driverCert_issue` (`driver_certification_id`, `driver_id`, `issue_date`),
    FOREIGN KEY (`driver_certification_id`) REFERENCES `driver_certification`(`driver_certification_id`) ON DELETE RESTRICT ON UPDATE CASCADE,
    FOREIGN KEY (`driver_id`) REFERENCES `driver`(`driver_id`) ON DELETE RESTRICT ON UPDATE CASCADE
) ENGINE=InnoDB;

CREATE TABLE `vehicle_driver_assignment` (
    `assignment_id` INT UNSIGNED NOT NULL AUTO_INCREMENT,
    `driver_id` INT UNSIGNED NOT NULL,
    `VIN` VARCHAR(17) NOT NULL,
    `start_date` DATE NOT NULL,
    `end_date` DATE NULL,
    PRIMARY KEY (`assignment_id`),
    FOREIGN KEY (`driver_id`) REFERENCES `driver`(`driver_id`) ON DELETE RESTRICT ON UPDATE CASCADE,
    FOREIGN KEY (`VIN`) REFERENCES `vehicle`(`VIN`) ON DELETE RESTRICT ON UPDATE CASCADE,
    INDEX `idx_driver_active` (`driver_id`, `end_date`),
    INDEX `idx_vin_active` (`VIN`, `end_date`)
) ENGINE=InnoDB;

-- ==========================================
-- 3. Safety & Incident Management
-- ==========================================

CREATE TABLE `monthly_safety_score` (
    `driver_id` INT UNSIGNED NOT NULL,
    `score_month` DATE NOT NULL,
    `final_score` SMALLINT NOT NULL DEFAULT 100,
    `action_required` VARCHAR(255) NULL,
    PRIMARY KEY (`driver_id`, `score_month`),
    FOREIGN KEY (`driver_id`) REFERENCES `driver`(`driver_id`) ON DELETE RESTRICT ON UPDATE CASCADE,
    CONSTRAINT `chk_finalScore` CHECK (`final_score` BETWEEN 0 AND 100)
) ENGINE=InnoDB;

CREATE TABLE `safety_event` (
    `event_id` INT UNSIGNED NOT NULL AUTO_INCREMENT,
    `driver_id` INT UNSIGNED NOT NULL,
    `depot_id` INT UNSIGNED NOT NULL,
    `VIN` VARCHAR(17) NOT NULL,
    `timestamp` TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
    `odometer_reading` DECIMAL(10,1) NULL,
    `event_type_id` SMALLINT UNSIGNED NOT NULL,
    `severity_id` INT UNSIGNED NOT NULL,
    `review_status` ENUM('PENDING', 'COMPLETED') NOT NULL DEFAULT 'PENDING',
    `reviewer_comments` VARCHAR(255) NULL,
    PRIMARY KEY (`event_id`),
    FOREIGN KEY (`driver_id`) REFERENCES `driver`(`driver_id`) ON DELETE RESTRICT ON UPDATE CASCADE,
    FOREIGN KEY (`depot_id`) REFERENCES `depot`(`depot_id`) ON DELETE RESTRICT ON UPDATE CASCADE,
    FOREIGN KEY (`VIN`) REFERENCES `vehicle`(`VIN`) ON DELETE RESTRICT ON UPDATE CASCADE,
    FOREIGN KEY (`event_type_id`) REFERENCES `event_type`(`event_type_id`) ON DELETE RESTRICT ON UPDATE CASCADE,
    FOREIGN KEY (`severity_id`) REFERENCES `severity_level`(`severity_id`) ON DELETE RESTRICT ON UPDATE CASCADE,
    INDEX `idx_event_timestamp` (`timestamp`),
    INDEX `idx_driver_event` (`driver_id`, `timestamp`)
) ENGINE=InnoDB;

CREATE TABLE `coaching_record` (
    `coaching_id` INT UNSIGNED NOT NULL AUTO_INCREMENT,
    `driver_id` INT UNSIGNED NOT NULL,
    `trigger_event_id` INT UNSIGNED NULL COMMENT 'safety_event that triggered this coaching, if any',
    `score_month` DATE NULL COMMENT 'monthly_safety_score period that triggered this coaching, if any',
    `coaching_type` ENUM('COACHING', 'RETRAINING') NOT NULL,
    `assigned_date` DATE NOT NULL,
    `completion_date` DATE NULL,
    `outcome` VARCHAR(255) NULL,
    `status` ENUM('PENDING', 'IN PROGRESS', 'COMPLETED', 'CANCELLED') NOT NULL DEFAULT 'PENDING',
    PRIMARY KEY (`coaching_id`),
    FOREIGN KEY (`driver_id`) REFERENCES `driver`(`driver_id`) ON DELETE RESTRICT ON UPDATE CASCADE,
    FOREIGN KEY (`trigger_event_id`) REFERENCES `safety_event`(`event_id`) ON DELETE SET NULL ON UPDATE CASCADE,
    FOREIGN KEY (`driver_id`, `score_month`) REFERENCES `monthly_safety_score`(`driver_id`, `score_month`) ON DELETE RESTRICT ON UPDATE CASCADE
) ENGINE=InnoDB;
-- ==========================================
-- 4. Maintenance & Workshop Operations
-- ==========================================

CREATE TABLE `service_bay` (
    `service_bay_id` SMALLINT UNSIGNED NOT NULL AUTO_INCREMENT,
    `bay_name` VARCHAR(15) NOT NULL,
    `bay_status` ENUM('IN USE', 'FREE', 'UNDER MAINTENANCE') DEFAULT 'FREE',
    `depot_id` INT UNSIGNED NOT NULL,
    PRIMARY KEY (`service_bay_id`),
    FOREIGN KEY (`depot_id`) REFERENCES `depot`(`depot_id`) ON DELETE RESTRICT ON UPDATE CASCADE
) ENGINE=InnoDB;

CREATE TABLE `maintenance_job` (
    `job_id` INT UNSIGNED NOT NULL AUTO_INCREMENT,
    `VIN` VARCHAR(17) NOT NULL,
    `service_bay_id` SMALLINT UNSIGNED NOT NULL,
    `date_opened` DATETIME NOT NULL,
    `date_closed` DATETIME NULL,
    `downtime_hours` DECIMAL(5,1) NULL,
    `job_status` ENUM('NOT STARTED', 'IN PROGRESS', 'DONE') NOT NULL DEFAULT 'NOT STARTED',
    `total_cost` DECIMAL(12,2) NULL,
    PRIMARY KEY (`job_id`),
    FOREIGN KEY (`VIN`) REFERENCES `vehicle`(`VIN`) ON DELETE RESTRICT ON UPDATE CASCADE,
    FOREIGN KEY (`service_bay_id`) REFERENCES `service_bay`(`service_bay_id`) ON DELETE RESTRICT ON UPDATE CASCADE,
    INDEX `idx_job_status_date` (`job_status`, `date_opened`)
) ENGINE=InnoDB;

CREATE TABLE `mechanic` (
    `mechanic_id` INT UNSIGNED NOT NULL AUTO_INCREMENT,
    `full_name` VARCHAR(255) NOT NULL,
    `depot_id` INT UNSIGNED NOT NULL,
    `employment_status` ENUM('ACTIVE', 'ON LEAVE', 'SUSPENDED', 'TERMINATED') NOT NULL DEFAULT 'ACTIVE',
    PRIMARY KEY (`mechanic_id`),
    FOREIGN KEY (`depot_id`) REFERENCES `depot`(`depot_id`) ON DELETE RESTRICT ON UPDATE CASCADE
) ENGINE=InnoDB;

CREATE TABLE `mechanic_certification_record` (
    `record_id` INT UNSIGNED NOT NULL AUTO_INCREMENT,
    `mechanic_certification_id` SMALLINT UNSIGNED NOT NULL,
    `mechanic_id` INT UNSIGNED NOT NULL,
    `issue_date` DATE NOT NULL,
    `expiration_date` DATE NOT NULL,
    PRIMARY KEY (`record_id`),
    UNIQUE KEY `uq_mechanicCert_issue` (`mechanic_certification_id`, `mechanic_id`, `issue_date`),
    FOREIGN KEY (`mechanic_certification_id`) REFERENCES `mechanic_certification`(`mechanic_certification_id`) ON DELETE RESTRICT ON UPDATE CASCADE,
    FOREIGN KEY (`mechanic_id`) REFERENCES `mechanic`(`mechanic_id`) ON DELETE RESTRICT ON UPDATE CASCADE
) ENGINE=InnoDB;

CREATE TABLE `maintenance_activity` (
    `activity_id` INT UNSIGNED NOT NULL AUTO_INCREMENT,
    `job_id` INT UNSIGNED NOT NULL,
    `activity_type_id` SMALLINT UNSIGNED NOT NULL,
    `diagnostic_result` VARCHAR(255) NOT NULL,
    `repeat_fault` BOOLEAN NOT NULL DEFAULT FALSE,
    `warranty_applicable` BOOLEAN NOT NULL DEFAULT FALSE,
    PRIMARY KEY (`activity_id`),
    INDEX `idx_job_id` (`job_id`),
    UNIQUE KEY `uq_job_activity` (`job_id`, `activity_id`),
    FOREIGN KEY (`job_id`) REFERENCES `maintenance_job`(`job_id`) ON DELETE RESTRICT ON UPDATE CASCADE,
    FOREIGN KEY (`activity_type_id`) REFERENCES `activity_type`(`activity_type_id`) ON DELETE RESTRICT ON UPDATE CASCADE
) ENGINE=InnoDB;

CREATE TABLE `activity_required_certification` (
    `mechanic_certification_id` SMALLINT UNSIGNED NOT NULL,
    `activity_type_id` SMALLINT UNSIGNED NOT NULL,
    PRIMARY KEY (`mechanic_certification_id`, `activity_type_id`),
    FOREIGN KEY (`mechanic_certification_id`) REFERENCES `mechanic_certification`(`mechanic_certification_id`) ON DELETE RESTRICT ON UPDATE CASCADE,
    FOREIGN KEY (`activity_type_id`) REFERENCES `activity_type`(`activity_type_id`) ON DELETE RESTRICT ON UPDATE CASCADE
) ENGINE=InnoDB;

CREATE TABLE `mechanic_activity` (
    `mechanic_id` INT UNSIGNED NOT NULL,
    `activity_id` INT UNSIGNED NOT NULL,
    `labour_hours` DECIMAL(4,1) NOT NULL,
    PRIMARY KEY (`mechanic_id`, `activity_id`),
    FOREIGN KEY (`mechanic_id`) REFERENCES `mechanic`(`mechanic_id`) ON DELETE RESTRICT ON UPDATE CASCADE,
    FOREIGN KEY (`activity_id`) REFERENCES `maintenance_activity`(`activity_id`) ON DELETE RESTRICT ON UPDATE CASCADE
) ENGINE=InnoDB;

CREATE TABLE `predictive_alert` (
    `alert_id` INT UNSIGNED NOT NULL AUTO_INCREMENT,
    `VIN` VARCHAR(17) NOT NULL,
   `alert_type_id` SMALLINT UNSIGNED NOT NULL,
    `job_id` INT UNSIGNED NULL,
    `timestamp` TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
    `action_taken` ENUM('ACKNOWLEDGED', 'SCHEDULED INSPECTION', 'ESCALATED', 'RESOLVED') NOT NULL,
    PRIMARY KEY (`alert_id`),
    FOREIGN KEY (`VIN`) REFERENCES `vehicle`(`VIN`) ON DELETE RESTRICT ON UPDATE CASCADE,
    FOREIGN KEY (`alert_type_id`) REFERENCES `alert_type`(`alert_type_id`) ON DELETE RESTRICT ON UPDATE CASCADE,
    FOREIGN KEY (`job_id`) REFERENCES `maintenance_job`(`job_id`) ON DELETE SET NULL ON UPDATE CASCADE
) ENGINE=InnoDB;

-- ==========================================
-- 5. Supply & Warranty
-- ==========================================

CREATE TABLE `part` (
    `part_id` INT UNSIGNED NOT NULL AUTO_INCREMENT,
    `part_number` VARCHAR(100) NOT NULL,
    `description` VARCHAR(255) NOT NULL,
    `primary_supplier_id` INT UNSIGNED NOT NULL,
    `primary_unit_cost` DECIMAL(12,2) NOT NULL,
    `backup_supplier_id` INT UNSIGNED NULL,
    `backup_unit_cost` DECIMAL(12,2) NULL,
    `stock_quantity` INT UNSIGNED NOT NULL DEFAULT 0,
    `reorder_level` INT UNSIGNED NOT NULL DEFAULT 5,
    PRIMARY KEY (`part_id`),
    UNIQUE KEY `uq_partNumber` (`part_number`),
    FOREIGN KEY (`primary_supplier_id`) REFERENCES `supplier`(`supplier_id`) ON DELETE RESTRICT ON UPDATE CASCADE,
    FOREIGN KEY (`backup_supplier_id`) REFERENCES `supplier`(`supplier_id`) ON DELETE SET NULL ON UPDATE CASCADE,
    CONSTRAINT `chk_primaryUnitCost` CHECK (`primary_unit_cost` >= 0.0),
    CONSTRAINT `chk_backupUnitCost` CHECK (`backup_unit_cost` >= 0.0)
) ENGINE=InnoDB;

CREATE TABLE `part_order` (
    `order_id` INT UNSIGNED NOT NULL AUTO_INCREMENT,
    `supplier_id` INT UNSIGNED NOT NULL,
    `order_date` DATE NOT NULL,
    `expected_delivery_date` DATE NOT NULL,
    `actual_delivery_date` DATE NULL,
    `order_status` ENUM('PREPARING', 'IN TRANSIT', 'DELIVERED', 'CANCELED') DEFAULT 'PREPARING',
    PRIMARY KEY (`order_id`),
    FOREIGN KEY (`supplier_id`) REFERENCES `supplier`(`supplier_id`) ON DELETE RESTRICT ON UPDATE CASCADE
) ENGINE=InnoDB;

CREATE TABLE `part_order_detail` (
    `part_id` INT UNSIGNED NOT NULL,
    `order_id` INT UNSIGNED NOT NULL,
    `quantity_order` INT UNSIGNED NOT NULL,
    `unit_price` DECIMAL(12,2) NOT NULL,
    PRIMARY KEY (`part_id`, `order_id`),
    FOREIGN KEY (`part_id`) REFERENCES `part`(`part_id`) ON DELETE RESTRICT ON UPDATE CASCADE,
    FOREIGN KEY (`order_id`) REFERENCES `part_order`(`order_id`) ON DELETE RESTRICT ON UPDATE CASCADE,
    CONSTRAINT `chk_unitPrice` CHECK (`unit_price` >= 0.0)
) ENGINE=InnoDB;

CREATE TABLE `activity_part` (
    `activity_id` INT UNSIGNED NOT NULL,
    `part_id` INT UNSIGNED NOT NULL,
    `quantity_used` INT UNSIGNED NOT NULL,
    `unit_cost_at_use` DECIMAL(12,2) NOT NULL COMMENT 'Snapshot of part unit cost at time of use, so historical job cost stays accurate even if part.primary_unit_cost changes later',
    PRIMARY KEY (`activity_id`, `part_id`),
    FOREIGN KEY (`activity_id`) REFERENCES `maintenance_activity`(`activity_id`) ON DELETE RESTRICT ON UPDATE CASCADE,
    FOREIGN KEY (`part_id`) REFERENCES `part`(`part_id`) ON DELETE RESTRICT ON UPDATE CASCADE,
    CONSTRAINT `chk_unitCostAtUse` CHECK (`unit_cost_at_use` >= 0.0),
    CONSTRAINT `chk_quantity_used` CHECK (`quantity_used` > 0)
) ENGINE=InnoDB;

CREATE TABLE `warranty_claim` (
    `claim_id` INT UNSIGNED NOT NULL AUTO_INCREMENT,
    `claim_type` ENUM('Standard Warranty', 'Component Warranty', 'Parts Warranty', 'Defect Campaigns') NULL,
    `claim_date` DATE NULL,
    `claim_status` ENUM('SUBMITTED', 'CLARIFICATION NEEDED', 'APPROVED', 'REJECTED') DEFAULT 'SUBMITTED',
    `manufacturer_id` INT UNSIGNED NULL,
    `supplier_id` INT UNSIGNED NULL,
    PRIMARY KEY (`claim_id`),
    FOREIGN KEY (`manufacturer_id`) REFERENCES `manufacturer`(`manufacturer_id`) ON DELETE SET NULL ON UPDATE CASCADE,
    FOREIGN KEY (`supplier_id`) REFERENCES `supplier`(`supplier_id`) ON DELETE SET NULL ON UPDATE CASCADE
) ENGINE=InnoDB;

CREATE TABLE `warranty_claim_part` (
    `claim_part_id` INT UNSIGNED NOT NULL AUTO_INCREMENT,
    `claim_id` INT UNSIGNED NOT NULL,
    `activity_id` INT UNSIGNED NOT NULL,
    `part_id` INT UNSIGNED NOT NULL,
    PRIMARY KEY (`claim_part_id`),
    FOREIGN KEY (`claim_id`) REFERENCES `warranty_claim`(`claim_id`) ON DELETE RESTRICT ON UPDATE CASCADE,
    FOREIGN KEY (`activity_id`, `part_id`) REFERENCES `activity_part`(`activity_id`, `part_id`) ON DELETE RESTRICT ON UPDATE CASCADE
) ENGINE=InnoDB;
