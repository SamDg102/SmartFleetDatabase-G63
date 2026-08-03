-- =====================================================================
-- G63System — FLEET MANAGEMENT DATABASE
-- =====================================================================
-- Schema for a fleet management system, covering 4 main business areas:
--   1. Fleet & Driver Management       -> vehicles, drivers, certifications, assignments
--   2. Safety & Incident Management    -> safety events, monthly safety scores, coaching
--   3. Maintenance & Workshop Ops      -> maintenance jobs, mechanics, predictive alerts
--   4. Supply & Warranty               -> parts, stock, purchase orders, warranty claims
--
-- IMPORTANT NOTE:
-- A lot of the business logic is implemented via TRIGGERS (at the end of
-- this file), NOT in the application layer. E.g. driver.safety_hold is
-- automatically turned on/off, part.stock_quantity is automatically
-- decremented/incremented, etc. Read the TRIGGERS section carefully
-- before writing CRUD code for the related tables (safety_event,
-- coaching_record, activity_part, vehicle_driver_assignment,
-- warranty_claim).
-- =====================================================================

CREATE DATABASE IF NOT EXISTS `G63System` DEFAULT CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;
USE `G63System`;

-- ==========================================
-- 1. LOOKUP TABLES
-- Reference/lookup tables, rarely change, referenced by other business
-- tables. Nothing special here, just need to know they exist.
-- ==========================================

-- Driver license/certification types (e.g. heavy truck cert, passenger bus cert...)
CREATE TABLE `driver_certification` (
    `driver_certification_id` INT UNSIGNED NOT NULL AUTO_INCREMENT,
    `driver_certification_name` VARCHAR(100) NOT NULL,
    PRIMARY KEY(`driver_certification_id`),
    UNIQUE KEY `uq_driverCertificationName` (`driver_certification_name`)
) ENGINE=InnoDB;

-- Vehicle category/class (e.g. light truck, heavy truck, passenger bus...)
CREATE TABLE `vehicle_category` (
    `category_id` INT UNSIGNED NOT NULL AUTO_INCREMENT,
    `category_name` VARCHAR(100) NOT NULL,
    PRIMARY KEY(`category_id`)
) ENGINE=InnoDB;

-- Depot — every driver, vehicle, mechanic, and service_bay belongs to a depot
CREATE TABLE `depot` (
    `depot_id` INT UNSIGNED NOT NULL AUTO_INCREMENT,
    `depot_name` VARCHAR(100) NOT NULL,
    `depot_location` VARCHAR(255) NOT NULL,
    PRIMARY KEY(`depot_id`)
) ENGINE=InnoDB;

-- Vehicle manufacturer
CREATE TABLE `manufacturer` (
    `manufacturer_id` INT UNSIGNED NOT NULL AUTO_INCREMENT,
    `manufacturer_name` VARCHAR(255),
    `contact_info` VARCHAR(100) NOT NULL,
    PRIMARY KEY(`manufacturer_id`)
) ENGINE=InnoDB;

-- Severity level of a safety incident.
-- level_name = CRITICAL/HIGH triggers automatic behavior (see trg_safetyEvent_*).
-- points_deducted: points subtracted from monthly_safety_score when this
-- severity occurs.
CREATE TABLE `severity_level` (
    `severity_id` INT UNSIGNED NOT NULL AUTO_INCREMENT,
    `level_name` ENUM ('LOW', 'MEDIUM', 'HIGH', 'CRITICAL') NOT NULL,
    `points_deducted` INT NOT NULL,
    `deduction_condition` VARCHAR(255) NULL,
    `additional_deduction` INT DEFAULT 0,
    PRIMARY KEY(`severity_id`)
) ENGINE=InnoDB;

-- Mechanic certification types (equivalent of driver_certification for mechanics)
CREATE TABLE `mechanic_certification` (
    `mechanic_certification_id` TINYINT UNSIGNED NOT NULL AUTO_INCREMENT,
    `mechanic_certification_name` VARCHAR(255) NOT NULL,
    PRIMARY KEY (`mechanic_certification_id`)
) ENGINE=InnoDB;

-- Maintenance activity types (e.g. oil change, brake inspection...)
CREATE TABLE `activity_type` (
    `activity_type_id` TINYINT UNSIGNED NOT NULL AUTO_INCREMENT,
    `activity_type_name` VARCHAR(255) NOT NULL,
    PRIMARY KEY (`activity_type_id`)
) ENGINE=InnoDB;

-- Predictive maintenance alert types
CREATE TABLE `alert_type` (
    `alert_type_id` TINYINT UNSIGNED NOT NULL AUTO_INCREMENT,
    `alert_name` VARCHAR(255) NOT NULL,
    `description` VARCHAR(255) NOT NULL,
    PRIMARY KEY (`alert_type_id`)
) ENGINE=InnoDB;

-- Safety incident types (e.g. speeding, hard braking, collision...)
CREATE TABLE `event_type` (
    `event_type_id` TINYINT UNSIGNED NOT NULL AUTO_INCREMENT,
    `event_type_name` VARCHAR(100) NOT NULL,
    PRIMARY KEY (`event_type_id`)
) ENGINE=InnoDB;

-- Parts supplier
CREATE TABLE `supplier` (
    `supplier_id` INT UNSIGNED NOT NULL AUTO_INCREMENT,
    `supplier_name` VARCHAR(100) NOT NULL,
    `contact_info` VARCHAR(100) NOT NULL,
    `delivery_lead_time` VARCHAR(100) NULL,
    PRIMARY KEY (`supplier_id`)
) ENGINE=InnoDB;

-- ==========================================
-- 2. FLEET & DRIVER MANAGEMENT
-- ==========================================

-- Many-to-many table: "vehicle category X requires driver certification Y".
-- Used by trg_vehicleAssignment_check to validate before a vehicle is assigned.
CREATE TABLE `category_required_certification` (
    `driver_certification_id` INT UNSIGNED NOT NULL,
    `category_id` INT UNSIGNED NOT NULL,
    PRIMARY KEY (`driver_certification_id`, `category_id`),
    FOREIGN KEY (`driver_certification_id`) REFERENCES `driver_certification`(`driver_certification_id`) ON DELETE RESTRICT ON UPDATE CASCADE,
    FOREIGN KEY (`category_id`) REFERENCES `vehicle_category`(`category_id`) ON DELETE RESTRICT ON UPDATE CASCADE
) ENGINE=InnoDB;

-- Driver.
-- employment_status: must be ACTIVE for the driver to be assigned a vehicle.
-- safety_hold: DO NOT set manually from the app for the CRITICAL case — this
--   column is automatically turned ON by a trigger when a CRITICAL
--   safety_event is inserted, and automatically turned OFF once that event
--   is reviewed or a retraining is completed (see the TRIGGERS section).
CREATE TABLE `driver` (
    `driver_id` INT UNSIGNED NOT NULL AUTO_INCREMENT,
    `full_name` VARCHAR(255) NOT NULL,
    `contact_info` VARCHAR(100) NOT NULL,
    `licence_type` VARCHAR(100) NOT NULL,
    `licence_expiry_date` DATE NOT NULL,
    `depot_id` INT UNSIGNED NOT NULL,
    `employment_status` ENUM('ACTIVE', 'ON LEAVE', 'SUSPENDED', 'TERMINATED') NOT NULL DEFAULT 'ACTIVE',
    `emergency_contact_details` VARCHAR(100) NOT NULL,
    `safety_hold` BOOLEAN NOT NULL DEFAULT FALSE,
    PRIMARY KEY (`driver_id`),
    FOREIGN KEY (`depot_id`) REFERENCES `depot`(`depot_id`) ON DELETE RESTRICT ON UPDATE CASCADE
) ENGINE=InnoDB;

-- Vehicle. PK is VIN (17-character chassis number) — NOT an auto-increment id.
-- vehicle_status determines whether the vehicle can be assigned to a driver
-- (see condition 1 in trg_vehicleAssignment_check).
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

-- Driver certification history (a driver can have multiple issue/renewal
-- records of the same certification -> unique on (cert, driver, issue_date)).
-- issue_date/expiration_date are used by trg_vehicleAssignment_check to check
-- whether a certification is still valid at the assignment start date.
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

-- Vehicle-to-driver assignment history. Composite PK (driver_id, VIN,
-- start_date) -> a driver can drive multiple vehicles over time,
-- end_date = NULL means it's the current (active) assignment.
-- EVERY INSERT into this table is validated by trg_vehicleAssignment_check
-- (vehicle available, driver active, driver not on hold, driver holds valid
-- required certs, safety score > 50) -> the application layer MUST catch
-- SQLSTATE 45000 errors and surface the message to the user; it's a
-- business-rule error, not a system failure.
CREATE TABLE `vehicle_driver_assignment` (
    `driver_id` INT UNSIGNED NOT NULL,
    `VIN` VARCHAR(17) NOT NULL,
    `start_date` DATE NOT NULL,
    `end_date` DATE NULL,
    PRIMARY KEY (`driver_id`, `VIN`, `start_date`),
    FOREIGN KEY (`driver_id`) REFERENCES `driver`(`driver_id`) ON DELETE RESTRICT ON UPDATE CASCADE,
    FOREIGN KEY (`VIN`) REFERENCES `vehicle`(`VIN`) ON DELETE RESTRICT ON UPDATE CASCADE
) ENGINE=InnoDB;

-- ==========================================
-- 3. SAFETY & INCIDENT MANAGEMENT
-- ==========================================

-- Driver's safety score per month, default 100 (0-100 scale).
-- Referenced by trg_vehicleAssignment_check: if the most recent month's
-- score is <= 50, the driver cannot be given a new vehicle assignment.
CREATE TABLE `monthly_safety_score` (
    `driver_id` INT UNSIGNED NOT NULL,
    `score_month` DATE NOT NULL,
    `final_score` SMALLINT NOT NULL DEFAULT 100,
    `action_required` VARCHAR(255) NULL,
    PRIMARY KEY (`driver_id`, `score_month`),
    FOREIGN KEY (`driver_id`) REFERENCES `driver`(`driver_id`) ON DELETE RESTRICT ON UPDATE CASCADE,
    CONSTRAINT `chk_finalScore` CHECK (`final_score` BETWEEN 0 AND 100)
) ENGINE=InnoDB;

-- A safety incident that occurs while a driver is driving.
-- review_required: DO NOT set manually — automatically becomes TRUE if the
--   severity is HIGH/CRITICAL (trg_safetyEvent_autoReview).
-- When severity = CRITICAL: driver.safety_hold is automatically set to TRUE
--   right after insert (trg_safetyEvent_criticalHold).
-- When review_status is updated to COMPLETED: if the driver has no other
--   CRITICAL event still pending review, safety_hold is automatically
--   cleared (trg_safetyEvent_reviewCompleted).
CREATE TABLE `safety_event` (
    `event_id` INT UNSIGNED NOT NULL AUTO_INCREMENT,
    `driver_id` INT UNSIGNED NOT NULL,
    `depot_id` INT UNSIGNED NOT NULL,
    `VIN` VARCHAR(17) NOT NULL,
    `timestamp` TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
    `event_type_id` TINYINT UNSIGNED NOT NULL,
    `severity_id` INT UNSIGNED NOT NULL,
    `review_required` BOOLEAN DEFAULT FALSE,
    `review_status` ENUM('PENDING', 'COMPLETED') NOT NULL DEFAULT 'PENDING',
    `reviewer_comments` VARCHAR(255) NULL,
    PRIMARY KEY (`event_id`),
    FOREIGN KEY (`driver_id`) REFERENCES `driver`(`driver_id`) ON DELETE RESTRICT ON UPDATE CASCADE,
    FOREIGN KEY (`depot_id`) REFERENCES `depot`(`depot_id`) ON DELETE RESTRICT ON UPDATE CASCADE,
    FOREIGN KEY (`VIN`) REFERENCES `vehicle`(`VIN`) ON DELETE RESTRICT ON UPDATE CASCADE,
    FOREIGN KEY (`event_type_id`) REFERENCES `event_type`(`event_type_id`) ON DELETE RESTRICT ON UPDATE CASCADE,
    FOREIGN KEY (`severity_id`) REFERENCES `severity_level`(`severity_id`) ON DELETE RESTRICT ON UPDATE CASCADE
) ENGINE=InnoDB;

-- Coaching/retraining record for a driver.
-- Can be triggered by ONE of 2 sources (optional, NOT mutually exclusive by
-- constraint like warranty_claim is -- if the business needs it, add a
-- trigger for that):
--   - trigger_event_id: caused by a specific safety_event
--   - score_month:      caused by a low monthly safety score
-- coaching_type = RETRAINING + status changed to COMPLETED triggers
-- trg_coaching_releaseHold (may clear the driver's safety_hold).
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
-- 4. MAINTENANCE & WORKSHOP OPERATIONS
-- ==========================================

-- Repair bay/area in the workshop
CREATE TABLE `service_bay` (
    `service_bay_id` TINYINT UNSIGNED NOT NULL AUTO_INCREMENT,
    `bay_name` VARCHAR(15) NOT NULL,
    `bay_status` ENUM('IN USE', 'FREE', 'UNDER MAINTENANCE') DEFAULT 'FREE',
    `depot_id` INT UNSIGNED NOT NULL,
    PRIMARY KEY (`service_bay_id`),
    FOREIGN KEY (`depot_id`) REFERENCES `depot`(`depot_id`) ON DELETE RESTRICT ON UPDATE CASCADE
) ENGINE=InnoDB;

-- One instance of a vehicle going into the workshop for maintenance/repair.
-- downtime_hours and total_cost are DENORMALIZED (summary) columns — the
-- schema currently has NO trigger that recalculates them from
-- maintenance_activity/activity_part/mechanic_activity. The application
-- layer must update these itself (or a new trigger/scheduled job could be
-- added to automate this).
CREATE TABLE `maintenance_job` (
    `job_id` INT UNSIGNED NOT NULL AUTO_INCREMENT,
    `VIN` VARCHAR(17) NOT NULL,
    `service_bay_id` TINYINT UNSIGNED NOT NULL,
    `date_opened` DATETIME NOT NULL,
    `date_closed` DATETIME NULL,
    `downtime_hours` DECIMAL(5,1) NULL,
    `job_status` ENUM('NOT STARTED', 'IN PROGRESS', 'DONE') NOT NULL DEFAULT 'NOT STARTED',
    `total_cost` DECIMAL(12,2) NULL,
    PRIMARY KEY (`job_id`),
    FOREIGN KEY (`VIN`) REFERENCES `vehicle`(`VIN`) ON DELETE RESTRICT ON UPDATE CASCADE,
    FOREIGN KEY (`service_bay_id`) REFERENCES `service_bay`(`service_bay_id`) ON DELETE RESTRICT ON UPDATE CASCADE
) ENGINE=InnoDB;

-- Mechanic
CREATE TABLE `mechanic` (
    `mechanic_id` INT UNSIGNED NOT NULL AUTO_INCREMENT,
    `full_name` VARCHAR(255) NOT NULL,
    `depot_id` INT UNSIGNED NOT NULL,
    `employment_status` ENUM('ACTIVE', 'ON LEAVE', 'SUSPENDED', 'TERMINATED') NOT NULL DEFAULT 'ACTIVE',
    PRIMARY KEY (`mechanic_id`),
    FOREIGN KEY (`depot_id`) REFERENCES `depot`(`depot_id`) ON DELETE RESTRICT ON UPDATE CASCADE
) ENGINE=InnoDB;

-- Mechanic certification history (equivalent of driver_certification_record)
CREATE TABLE `mechanic_certification_record` (
    `record_id` INT UNSIGNED NOT NULL AUTO_INCREMENT,
    `mechanic_certification_id` TINYINT UNSIGNED NOT NULL,
    `mechanic_id` INT UNSIGNED NOT NULL,
    `issue_date` DATE NOT NULL,
    `expiration_date` DATE NOT NULL,
    PRIMARY KEY (`record_id`),
    UNIQUE KEY `uq_mechanicCert_issue` (`mechanic_certification_id`, `mechanic_id`, `issue_date`),
    FOREIGN KEY (`mechanic_certification_id`) REFERENCES `mechanic_certification`(`mechanic_certification_id`) ON DELETE RESTRICT ON UPDATE CASCADE,
    FOREIGN KEY (`mechanic_id`) REFERENCES `mechanic`(`mechanic_id`) ON DELETE RESTRICT ON UPDATE CASCADE
) ENGINE=InnoDB;

-- An individual activity performed within a maintenance job.
-- Composite PK (job_id, activity_id): activity_id is a sequence number
-- SCOPED TO a single job_id (TINYINT, NOT a global auto-increment) -> the
-- application layer must compute activity_id = MAX(activity_id)+1 within
-- that job_id when inserting.
CREATE TABLE `maintenance_activity` (
    `job_id` INT UNSIGNED NOT NULL,
    `activity_id` TINYINT UNSIGNED NOT NULL,
    `activity_type_id` TINYINT UNSIGNED NOT NULL,
    `diagnostic_result` VARCHAR(255) NOT NULL,
    `repeat_fault` BOOLEAN NOT NULL DEFAULT FALSE,
    `warranty_applicable` BOOLEAN NOT NULL DEFAULT FALSE,
    PRIMARY KEY (`job_id`, `activity_id`),
    FOREIGN KEY (`job_id`) REFERENCES `maintenance_job`(`job_id`) ON DELETE RESTRICT ON UPDATE CASCADE,
    FOREIGN KEY (`activity_type_id`) REFERENCES `activity_type`(`activity_type_id`) ON DELETE RESTRICT ON UPDATE CASCADE
) ENGINE=InnoDB;

-- Many-to-many table: "activity type X requires mechanic certification Y".
-- NOTE: unlike category_required_certification (which IS enforced by a
-- trigger during vehicle assignment), this table has NO trigger validating
-- it — if you need to enforce it (mechanic without the right cert cannot be
-- assigned to the activity), you must add the check in the application
-- layer or write a new trigger.
CREATE TABLE `activity_required_certification` (
    `mechanic_certification_id` TINYINT UNSIGNED NOT NULL,
    `activity_type_id` TINYINT UNSIGNED NOT NULL,
    PRIMARY KEY (`mechanic_certification_id`, `activity_type_id`),
    FOREIGN KEY (`mechanic_certification_id`) REFERENCES `mechanic_certification`(`mechanic_certification_id`) ON DELETE RESTRICT ON UPDATE CASCADE,
    FOREIGN KEY (`activity_type_id`) REFERENCES `activity_type`(`activity_type_id`) ON DELETE RESTRICT ON UPDATE CASCADE
) ENGINE=InnoDB;

-- Records which mechanic worked on which activity and how many labor hours
CREATE TABLE `mechanic_activity` (
    `mechanic_id` INT UNSIGNED NOT NULL,
    `job_id` INT UNSIGNED NOT NULL,
    `activity_id` TINYINT UNSIGNED NOT NULL,
    `labour_hours` DECIMAL(4,1) NOT NULL,
    PRIMARY KEY (`mechanic_id`, `job_id`, `activity_id`),
    FOREIGN KEY (`mechanic_id`) REFERENCES `mechanic`(`mechanic_id`) ON DELETE RESTRICT ON UPDATE CASCADE,
    FOREIGN KEY (`job_id`, `activity_id`) REFERENCES `maintenance_activity`(`job_id`, `activity_id`) ON DELETE RESTRICT ON UPDATE CASCADE
) ENGINE=InnoDB;

-- Predictive maintenance alert for a vehicle.
-- job_id is nullable: if the alert leads to opening a maintenance_job to
-- handle it, link to that job; otherwise leave it NULL.
CREATE TABLE `predictive_alert` (
    `alert_id` INT UNSIGNED NOT NULL AUTO_INCREMENT,
    `VIN` VARCHAR(17) NOT NULL,
    `alert_type_id` TINYINT UNSIGNED NOT NULL,
    `job_id` INT UNSIGNED NULL,
    `timestamp` TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
    `action_taken` ENUM('ACKNOWLEDGED', 'SCHEDULED INSPECTION', 'ESCALATED', 'RESOLVED') NOT NULL,
    PRIMARY KEY (`alert_id`),
    FOREIGN KEY (`VIN`) REFERENCES `vehicle`(`VIN`) ON DELETE RESTRICT ON UPDATE CASCADE,
    FOREIGN KEY (`alert_type_id`) REFERENCES `alert_type`(`alert_type_id`) ON DELETE RESTRICT ON UPDATE CASCADE,
    FOREIGN KEY (`job_id`) REFERENCES `maintenance_job`(`job_id`) ON DELETE SET NULL ON UPDATE CASCADE
) ENGINE=InnoDB;

-- ==========================================
-- 5. SUPPLY & WARRANTY
-- ==========================================

-- Part. Each part has a primary supplier (required) plus an optional backup
-- supplier, each with its own unit cost.
-- stock_quantity: DO NOT update manually — automatically decremented/
-- incremented by the trg_activityPart_* triggers whenever activity_part
-- rows are inserted/updated/deleted.
CREATE TABLE `part` (
    `part_id` INT UNSIGNED NOT NULL AUTO_INCREMENT,
    `part_number` VARCHAR(100) NOT NULL,
    `description` VARCHAR(255) NOT NULL,
    `primary_supplier_id` INT UNSIGNED NOT NULL,
    `primary_unit_cost` DECIMAL(12,2) NOT NULL,
    `backup_supplier_id` INT UNSIGNED NULL,
    `backup_unit_cost` DECIMAL(12,2) NULL,
    `stock_quantity` INT UNSIGNED NOT NULL DEFAULT 0,
    PRIMARY KEY (`part_id`),
    UNIQUE KEY `uq_partNumber` (`part_number`),
    FOREIGN KEY (`primary_supplier_id`) REFERENCES `supplier`(`supplier_id`) ON DELETE RESTRICT ON UPDATE CASCADE,
    FOREIGN KEY (`backup_supplier_id`) REFERENCES `supplier`(`supplier_id`) ON DELETE SET NULL ON UPDATE CASCADE,
    CONSTRAINT `chk_primaryUnitCost` CHECK (`primary_unit_cost` >= 0.0),
    CONSTRAINT `chk_backupUnitCost` CHECK (`backup_unit_cost` >= 0.0)
) ENGINE=InnoDB;

-- Purchase order for parts sent to a supplier
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

-- Line-item detail for each part in a purchase order (quantity ordered,
-- unit price AT THE TIME of ordering — NOT the current price in the part table)
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

-- A part consumed by a specific maintenance activity.
-- unit_cost_at_use: a SNAPSHOT of the part's unit cost at the time it was
--   used (not the part table's current price, since prices can change over
--   time) — if left NULL on insert, the trigger auto-fills it with
--   part.primary_unit_cost as it is at that moment.
-- EVERY insert/update/delete on this table affects part.stock_quantity via
--   the trg_activityPart_* triggers (see the TRIGGERS section below).
--   -> DO NOT manually increment/decrement stock_quantity in the app layer.
CREATE TABLE `activity_part` (
    `job_id` INT UNSIGNED NOT NULL,
    `activity_id` TINYINT UNSIGNED NOT NULL,
    `part_id` INT UNSIGNED NOT NULL,
    `quantity_used` TINYINT UNSIGNED NOT NULL,
    `unit_cost_at_use` DECIMAL(12,2) NULL COMMENT 'Snapshot of part unit cost at time of use',
    PRIMARY KEY (`job_id`, `activity_id`, `part_id`),
    FOREIGN KEY (`job_id`, `activity_id`) REFERENCES `maintenance_activity`(`job_id`, `activity_id`) ON DELETE RESTRICT ON UPDATE CASCADE,
    FOREIGN KEY (`part_id`) REFERENCES `part`(`part_id`) ON DELETE RESTRICT ON UPDATE CASCADE,
    CONSTRAINT `chk_unitCostAtUse` CHECK (`unit_cost_at_use` >= 0.0)
) ENGINE=InnoDB;

-- Warranty claim.
-- IMPORTANT: exactly one of manufacturer_id / supplier_id must be set (not
-- both, not neither) — enforced by the trg_warrantyClaim_insert /
-- trg_warrantyClaim_update triggers, NOT a CHECK constraint (MySQL doesn't
-- reliably support this kind of XOR-between-two-columns CHECK before 8.0.16).
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

-- A specific part (used in an activity) included in a warranty claim
CREATE TABLE `warranty_claim_part` (
    `claim_id` INT UNSIGNED NOT NULL,
    `job_id` INT UNSIGNED NOT NULL,
    `activity_id` TINYINT UNSIGNED NOT NULL,
    `part_id` INT UNSIGNED NOT NULL,
    PRIMARY KEY (`claim_id`, `job_id`, `activity_id`, `part_id`),
    FOREIGN KEY (`claim_id`) REFERENCES `warranty_claim`(`claim_id`) ON DELETE RESTRICT ON UPDATE CASCADE,
    FOREIGN KEY (`job_id`, `activity_id`, `part_id`) REFERENCES `activity_part`(`job_id`, `activity_id`, `part_id`) ON DELETE RESTRICT ON UPDATE CASCADE
) ENGINE=InnoDB;

-- =====================================================================
-- TRIGGERS — THE MOST IMPORTANT SECTION, READ CAREFULLY BEFORE WRITING CRUD
-- All of the system's "implicit" business rules live here.
-- =====================================================================

DELIMITER $$

-- ---------------------------------------------------------------
-- [1] trg_warrantyClaim_insert
-- XOR constraint: inserting a warranty_claim must set EXACTLY ONE of
-- (manufacturer_id, supplier_id) — not both, not neither.
-- ---------------------------------------------------------------
CREATE TRIGGER `trg_warrantyClaim_insert`
BEFORE INSERT ON `warranty_claim`
FOR EACH ROW
BEGIN
    IF (NEW.manufacturer_id IS NULL AND NEW.supplier_id IS NULL)
       OR (NEW.manufacturer_id IS NOT NULL AND NEW.supplier_id IS NOT NULL) THEN
        SIGNAL SQLSTATE '45000'
        SET MESSAGE_TEXT = 'Error: Exactly one of manufacturer_id or supplier_id must be set on warranty_claim';
    END IF;
END$$

-- ---------------------------------------------------------------
-- [2] trg_warrantyClaim_update
-- Same as [1] but for UPDATE (ensures an update can't be used to violate
-- the XOR constraint).
-- ---------------------------------------------------------------
CREATE TRIGGER `trg_warrantyClaim_update`
BEFORE UPDATE ON `warranty_claim`
FOR EACH ROW
BEGIN
    IF (NEW.manufacturer_id IS NULL AND NEW.supplier_id IS NULL)
       OR (NEW.manufacturer_id IS NOT NULL AND NEW.supplier_id IS NOT NULL) THEN
        SIGNAL SQLSTATE '45000'
        SET MESSAGE_TEXT = 'Error: Exactly one of manufacturer_id or supplier_id must be set on warranty_claim';
    END IF;
END$$

-- ---------------------------------------------------------------
-- [3] trg_safetyEvent_autoReview
-- On safety_event insert: if the severity is HIGH or CRITICAL,
-- automatically set review_required = TRUE (regardless of what the app
-- passed in).
-- -> The app does NOT need to (and should not) set review_required manually.
-- ---------------------------------------------------------------
CREATE TRIGGER `trg_safetyEvent_autoReview`
BEFORE INSERT ON `safety_event`
FOR EACH ROW
BEGIN
    DECLARE sev_name VARCHAR(20);

    SELECT `level_name` INTO sev_name
    FROM `severity_level`
    WHERE `severity_id` = NEW.severity_id;

    IF sev_name IN ('HIGH', 'CRITICAL') THEN
        SET NEW.review_required = TRUE;
    END IF;
END$$

-- ---------------------------------------------------------------
-- [4] trg_safetyEvent_criticalHold
-- After a safety_event insert: if the severity is CRITICAL, automatically
-- lock the driver (driver.safety_hold = TRUE). A driver on hold CANNOT be
-- given a new vehicle assignment (see [8] trg_vehicleAssignment_check).
-- ---------------------------------------------------------------
CREATE TRIGGER `trg_safetyEvent_criticalHold`
AFTER INSERT ON `safety_event`
FOR EACH ROW
BEGIN
    DECLARE sev_name VARCHAR(20);

    SELECT `level_name` INTO sev_name
    FROM `severity_level`
    WHERE `severity_id` = NEW.severity_id;

    IF sev_name = 'CRITICAL' THEN
        UPDATE `driver` SET `safety_hold` = TRUE WHERE `driver_id` = NEW.driver_id;
    END IF;
END$$

-- ---------------------------------------------------------------
-- [5] trg_safetyEvent_reviewCompleted
-- When a safety_event's review_status changes to COMPLETED: check whether
-- the driver has any OTHER CRITICAL event still not fully reviewed.
-- If NONE remain -> clear safety_hold (driver.safety_hold = FALSE).
-- This is one of two paths that can clear the hold (the other is [6]).
-- ---------------------------------------------------------------
CREATE TRIGGER `trg_safetyEvent_reviewCompleted`
AFTER UPDATE ON `safety_event`
FOR EACH ROW
BEGIN
    DECLARE remaining_critical INT DEFAULT 0;

    IF NEW.review_status = 'COMPLETED' AND OLD.review_status <> 'COMPLETED' THEN
        SELECT COUNT(*) INTO remaining_critical
        FROM `safety_event` se
        JOIN `severity_level` sl ON sl.severity_id = se.severity_id
        WHERE se.driver_id = NEW.driver_id
          AND sl.level_name = 'CRITICAL'
          AND se.review_status <> 'COMPLETED';

        IF remaining_critical = 0 THEN
            UPDATE `driver` SET `safety_hold` = FALSE WHERE `driver_id` = NEW.driver_id;
        END IF;
    END IF;
END$$

-- ---------------------------------------------------------------
-- [6] trg_coaching_releaseHold
-- Purpose: when a RETRAINING coaching_record transitions to COMPLETED,
-- clear the driver's hold — BUT only if the driver has no other CRITICAL
-- safety_event still pending review. This logic is kept consistent with
-- trigger [5] trg_safetyEvent_reviewCompleted.
--
-- NOTE: [5] and [6] are two INDEPENDENT paths that both clear
-- safety_hold, sharing the SAME "no CRITICAL event pending review" check.
-- If the hold-release logic is changed later, update BOTH triggers to keep
-- them in sync.
-- ---------------------------------------------------------------
CREATE TRIGGER `trg_coaching_releaseHold`
AFTER UPDATE ON `coaching_record`
FOR EACH ROW
BEGIN
    DECLARE remaining_critical INT DEFAULT 0;

    IF NEW.coaching_type = 'RETRAINING'
       AND NEW.status = 'COMPLETED'
       AND (OLD.status IS NULL OR OLD.status <> 'COMPLETED') THEN

        SELECT COUNT(*) INTO remaining_critical
        FROM `safety_event` se
        JOIN `severity_level` sl ON sl.severity_id = se.severity_id
        WHERE se.driver_id = NEW.driver_id
          AND sl.level_name = 'CRITICAL'
          AND se.review_status <> 'COMPLETED';

        IF remaining_critical = 0 THEN
            UPDATE `driver` SET `safety_hold` = FALSE WHERE `driver_id` = NEW.driver_id;
        END IF;
    END IF;
END$$

-- ---------------------------------------------------------------
-- [7] Group of 5 triggers that keep part.stock_quantity in sync with the
-- activity_part table. The app must NOT manually increment/decrement
-- stock_quantity — just insert/update/delete activity_part correctly, and
-- these triggers will keep stock in sync automatically.
-- ---------------------------------------------------------------

-- [7a] trg_activityPart_beforeInsert
-- Before recording a part's usage: check there's enough stock (blocks the
-- insert via SIGNAL if not). If unit_cost_at_use is left NULL, auto-fill it
-- with the part's current primary_unit_cost (price snapshot).
CREATE TRIGGER `trg_activityPart_beforeInsert`
BEFORE INSERT ON `activity_part`
FOR EACH ROW
BEGIN
    DECLARE current_stock INT UNSIGNED;
    DECLARE base_cost DECIMAL(12,2);

    SELECT `stock_quantity`, `primary_unit_cost` 
    INTO current_stock, base_cost
    FROM `part` 
    WHERE `part_id` = NEW.part_id;

    IF current_stock < NEW.quantity_used THEN
        SIGNAL SQLSTATE '45000'
        SET MESSAGE_TEXT = 'Error: Not enough stock available for this part';
    END IF;

    IF NEW.unit_cost_at_use IS NULL THEN
        SET NEW.unit_cost_at_use = base_cost;
    END IF;
END$$

-- [7b] trg_activityPart_afterInsert
-- After a part usage is recorded: decrement stock accordingly.
CREATE TRIGGER `trg_activityPart_afterInsert`
AFTER INSERT ON `activity_part`
FOR EACH ROW
BEGIN
    UPDATE `part`
    SET `stock_quantity` = `stock_quantity` - NEW.quantity_used
    WHERE `part_id` = NEW.part_id;
END$$

-- [7c] trg_activityPart_afterDelete
-- When an activity_part row is deleted (part usage reverted): restore stock.
CREATE TRIGGER `trg_activityPart_afterDelete`
AFTER DELETE ON `activity_part`
FOR EACH ROW
BEGIN
    UPDATE `part`
    SET `stock_quantity` = `stock_quantity` + OLD.quantity_used
    WHERE `part_id` = OLD.part_id;
END$$

-- [7d] trg_activityPart_beforeUpdate
-- When quantity_used is changed (part_id unchanged): if the quantity used is
-- INCREASED, check there's enough stock to cover the difference (qty_diff)
-- before allowing the update.
-- NOTE: if part_id itself is changed (switched to a different part) during
-- an UPDATE, this pair of triggers [7d]/[7e] does NOT handle that case
-- (guarded by NEW.part_id = OLD.part_id). The application layer should avoid
-- updating part_id directly — instead DELETE the old row and INSERT a new
-- one so the stock triggers behave correctly.
CREATE TRIGGER `trg_activityPart_beforeUpdate`
BEFORE UPDATE ON `activity_part`
FOR EACH ROW
BEGIN
    DECLARE current_stock INT UNSIGNED;
    DECLARE qty_diff INT;

    IF NEW.part_id = OLD.part_id AND NEW.quantity_used <> OLD.quantity_used THEN
        SET qty_diff = NEW.quantity_used - OLD.quantity_used;

        IF qty_diff > 0 THEN
            SELECT `stock_quantity` INTO current_stock
            FROM `part` WHERE `part_id` = NEW.part_id;

            IF current_stock < qty_diff THEN
                SIGNAL SQLSTATE '45000'
                SET MESSAGE_TEXT = 'Error: Not enough stock available to increase quantity_used';
            END IF;
        END IF;
    END IF;
END$$

-- [7e] trg_activityPart_afterUpdate
-- After a successful quantity_used update: adjust stock by exactly qty_diff
-- (can be negative if the quantity used was reduced).
CREATE TRIGGER `trg_activityPart_afterUpdate`
AFTER UPDATE ON `activity_part`
FOR EACH ROW
BEGIN
    DECLARE qty_diff INT;

    IF NEW.part_id = OLD.part_id AND NEW.quantity_used <> OLD.quantity_used THEN
        SET qty_diff = NEW.quantity_used - OLD.quantity_used;

        UPDATE `part`
        SET `stock_quantity` = `stock_quantity` - qty_diff
        WHERE `part_id` = NEW.part_id;
    END IF;
END$$

-- ---------------------------------------------------------------
-- [8] trg_vehicleAssignment_check
-- Before inserting into vehicle_driver_assignment, checks 5 conditions IN
-- SEQUENCE; failing any one of them raises its own SIGNAL error (with a
-- clear message the app can surface directly to the user):
--   1) Vehicle must NOT be UNDER MAINTENANCE / OUT OF SERVICE / RETIRED
--   2) Driver must have employment_status = ACTIVE
--   3) Driver must NOT currently be on safety_hold = TRUE
--   4) Driver must hold ALL required certifications for that vehicle
--      category (category_required_certification), and each certification
--      must be VALID (unexpired) at start_date
--      (issue_date <= start_date <= expiration_date)
--   5) The driver's most recent monthly safety score (if any) must be > 50
-- -> The application layer MUST catch SQLSTATE 45000 errors when inserting
--    an assignment.
-- ---------------------------------------------------------------
CREATE TRIGGER `trg_vehicleAssignment_check`
BEFORE INSERT ON `vehicle_driver_assignment`
FOR EACH ROW
BEGIN
    DECLARE v_status VARCHAR(30);
    DECLARE v_category_id INT UNSIGNED;
    DECLARE d_status VARCHAR(30);
    DECLARE d_hold BOOLEAN;
    DECLARE missing_certs INT DEFAULT 0;
    DECLARE latest_score SMALLINT;

    -- Vehicle must be available for assignment
    SELECT `vehicle_status`, `category_id` INTO v_status, v_category_id
    FROM `vehicle` WHERE `VIN` = NEW.VIN;

    IF v_status IN ('UNDER MAINTENANCE', 'OUT OF SERVICE', 'RETIRED') THEN
        SIGNAL SQLSTATE '45000'
        SET MESSAGE_TEXT = 'Error: Cannot assign a vehicle that is under maintenance or out of service';
    END IF;

    -- Driver must be an active employee and not on a safety hold
    SELECT `employment_status`, `safety_hold` INTO d_status, d_hold
    FROM `driver` WHERE `driver_id` = NEW.driver_id;

    IF d_status <> 'ACTIVE' THEN
        SIGNAL SQLSTATE '45000'
        SET MESSAGE_TEXT = 'Error: Cannot assign a vehicle to a driver who is not an active employee';
    END IF;

    IF d_hold = TRUE THEN
        SIGNAL SQLSTATE '45000'
        SET MESSAGE_TEXT = 'Error: Driver is on safety hold following a critical event and cannot be assigned a vehicle';
    END IF;

    -- Driver must hold ALL required certifications for the vehicle category, and MUST BE UNEXPIRED on assignment start date
    SELECT COUNT(*) INTO missing_certs
    FROM `category_required_certification` crc
    WHERE crc.category_id = v_category_id
      AND NOT EXISTS (
          SELECT 1 FROM `driver_certification_record` dcr
          WHERE dcr.driver_id = NEW.driver_id
            AND dcr.driver_certification_id = crc.driver_certification_id
            AND dcr.issue_date <= NEW.start_date
            AND dcr.expiration_date >= NEW.start_date
      );

    IF missing_certs > 0 THEN
        SIGNAL SQLSTATE '45000'
        SET MESSAGE_TEXT = 'Error: Driver lacks a required, unexpired certification for this vehicle category';
    END IF;

    -- Driver's most recent monthly safety score must be above 50
    SELECT final_score INTO latest_score
    FROM `monthly_safety_score`
    WHERE driver_id = NEW.driver_id
    ORDER BY score_month DESC
    LIMIT 1;

    IF latest_score IS NOT NULL AND latest_score <= 50 THEN
        SIGNAL SQLSTATE '45000'
        SET MESSAGE_TEXT = 'Error: Driver safety score is 50 or below; safety training required before assignment';
    END IF;
END$$

DELIMITER ;
