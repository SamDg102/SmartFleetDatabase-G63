
SHOW DATABASES;

CREATE DATABASE `G63System`;

USE `G63System`;

SHOW TABLES;

CREATE TABLE `driver_certification` (
	`driver_certification_id` VARCHAR(100) NOT NULL,
    `driver_certification_name` VARCHAR(100) NOT NULL,
    PRIMARY KEY(`driver_certification_id`)
);
CREATE TABLE `vehicle_category` (
	`category_id` INT NOT NULL AUTO_INCREMENT,
	`category_name` VARCHAR(100) NOT NULL,
	PRIMARY KEY(`category_id`)
);
CREATE TABLE `category_required_certification` (
	`driver_certification_id` VARCHAR(100) NOT NULL,
	`category_id` INT NOT NULL,
	PRIMARY KEY(`driver_certification_id`,`category_id`),
    FOREIGN KEY(`driver_certification_id`) REFERENCES `driver_certification`(`driver_certification_id`),
    FOREIGN KEY(`category_id`) REFERENCES `vehicle_category`(`category_id`)
);
CREATE TABLE `depot` (
	`depot_id` INT NOT NULL AUTO_INCREMENT,
    `depot_name` VARCHAR(100) NOT NULL,
    `depot_location` VARCHAR(255) NOT NULL,
    PRIMARY KEY(`depot_id`)
);
CREATE TABLE `vehicle_status` (
	`vehicle_status_id` INT NOT NULL AUTO_INCREMENT,
    `vehicle_status_name` VARCHAR(100) NOT NULL,
    `assignability` VARCHAR(100) NOT NULL,
    PRIMARY KEY(`vehicle_status_id`)
);
CREATE TABLE `manufacturer` (
	`manufacturer_id` INT NOT NULL AUTO_INCREMENT,
    `manufacturer_name` VARCHAR(255),
    `contact_info` VARCHAR(100) NOT NULL,
    PRIMARY KEY(`manufacturer_id`)
);
CREATE TABLE `driver` (
	`driver_id` INT NOT NULL AUTO_INCREMENT,
    `full_name` VARCHAR(255) NOT NULL,
    `contact_info` VARCHAR(100) NOT NULL,
    `licence_type` VARCHAR(100) NOT NULL,
    `licence_expiry_date` DATE NOT NULL,
    `depot_id` INT NOT NULL,
    `employment_status` VARCHAR(100) NOT NULL,
    `emergency_contact_details` VARCHAR(100) NOT NULL,
    PRIMARY KEY(`driver_id`),
    FOREIGN KEY(`depot_id`) REFERENCES `depot`(`depot_id`)
);
CREATE TABLE `vehicle` (
	`VIN` VARCHAR(17) NOT NULL,
    `vehicle_status_id` INT NOT NULL,
    `category_id` INT NOT NULL,
    `registration_number` VARCHAR(100) NOT NULL,
    `manufacturer_id` INT NOT NULL,
    `year_of_manufacture` INT NOT NULL,
    `current_odometer` FLOAT NOT NULL,
    `depot_location` VARCHAR(100) NOT NULL,
    `status_name` VARCHAR(100) NOT NULL,
    PRIMARY KEY(`VIN`),
    FOREIGN KEY(`vehicle_status_id`) REFERENCES `vehicle_status`(`vehicle_status_id`),
    FOREIGN KEY(`category_id`) REFERENCES `vehicle_category`(`category_id`),
    FOREIGN KEY(`manufacturer_id`) REFERENCES `manufacturer`(`manufacturer_id`)
);
CREATE TABLE `driver_certification_record` (
	`driver_certification_id` VARCHAR(100) NOT NULL,
    `driver_id` INT NOT NULL,
    `expiration_date` DATE NOT NULL,
    PRIMARY KEY(`driver_certification_id`,`driver_id`),
    FOREIGN KEY (`driver_certification_id`) REFERENCES `driver_certification`(`driver_certification_id`),
    FOREIGN KEY (`driver_id`) REFERENCES `driver`(`driver_id`)
);
CREATE TABLE `vehicle_driver_assignment` (
	`driver_id` INT NOT NULL AUTO_INCREMENT,
    `VIN` VARCHAR(17) NOT NULL ,
    `start_date`DATE NOT NULL,
    `end_date` DATE NOT NULL,
    PRIMARY KEY(`driver_id`,`VIN`,`start_date`),
    FOREIGN KEY(`driver_id`) REFERENCES `driver`(`driver_id`),
    FOREIGN KEY(`VIN`) REFERENCES `vehicle`(`VIN`)
);
CREATE TABLE `monthly_safety_score` (
	`driver_id` INT NOT NULL AUTO_INCREMENT,
	`score_month`DATE NOT NULL,
    `final_score` TINYINT UNSIGNED NOT NULL,
    `action_required`VARCHAR(255) NOT NULL,
    PRIMARY KEY(`driver_id`)
);

CREATE TABLE `severity_level` (
	`severity_id` INT NOT NULL AUTO_INCREMENT,
	`level_name` ENUM ('LOW','MEDIUM','HIGH','CRITICAL') NOT NULL,
    `points_deducted` INT NOT NULL,
    PRIMARY KEY(`severity_id`)
);

CREATE TABLE `safety_event` (
	`event_id` INT NOT NULL AUTO_INCREMENT,
    `driver_id` INT NOT NULL,
    `depot_id` INT NOT NULL,
    `VIN` VARCHAR(17) NOT NULL,
    `timestamp` TIMESTAMP NOT NULL,
    `event_type` VARCHAR(100) NOT NULL,
    `severity_id` INT NOT NULL,
    `review_required` BOOLEAN,
    `reviewer_comments` VARCHAR(255) NOT NULL,
    PRIMARY KEY(`event_id`),
    FOREIGN KEY (`driver_id`) REFERENCES `driver`(`driver_id`),
    FOREIGN KEY (`depot_id`) REFERENCES `depot`(`depot_id`),
    FOREIGN KEY(`VIN`) REFERENCES `vehicle`(`VIN`),
    FOREIGN KEY (`severity_id`) REFERENCES `severity_level`(`severity_id`)
);



CREATE TABLE service_bay (
    service_bay_id TINYINT UNSIGNED NOT NULL AUTO_INCREMENT PRIMARY KEY,
	bay_name VARCHAR(15) NOT NULL,
	bay_status ENUM('IN USE', 'FREE', 'UNDER MAINTENANCE') DEFAULT 'FREE',
    depot_id INT NOT NULL,
    CONSTRAINT fk_depotid FOREIGN KEY (depot_id) REFERENCES depot(depot_id) 
);

CREATE TABLE maintenance_job (
    job_id INT NOT NULL AUTO_INCREMENT PRIMARY KEY,
    VIN VARCHAR(17) NOT NULL,
    bay_id TINYINT UNSIGNED NOT NULL,
    date_opened DATE NOT NULL,
    date_closed DATE NOT NULL,
    downtime_hours TIME NOT NULL,
    job_status ENUM ('NOT STARTED','IN PROGRESS','DONE') NOT NULL,
    CONSTRAINT fk_VIN FOREIGN KEY (VIN) REFERENCES vehicle(VIN),
    CONSTRAINT fk_servicebayid FOREIGN KEY (bay_id) REFERENCES service_bay(bay_id)
);

CREATE TABLE mechanic_certification (
    mechanic_certification_id TINYINT UNSIGNED NOT NULL AUTO_INCREMENT PRIMARY KEY,
    mechanic_certification_name VARCHAR(255) NOT NULL
);

CREATE TABLE activity_type (
    activity_type_id TINYINT UNSIGNED NOT NULL AUTO_INCREMENT PRIMARY KEY,
    activity_type_name VARCHAR(255) NOT NULL
);

CREATE TABLE alert_type (
    alert_type_id TINYINT UNSIGNED NOT NULL AUTO_INCREMENT PRIMARY KEY,
    alert_name VARCHAR(255) NOT NULL,
    description VARCHAR(255) NOT NULL
);

CREATE TABLE mechanic (
    mechanic_id INT NOT NULL AUTO_INCREMENT PRIMARY KEY,
    full_name VARCHAR(255) NOT NULL,
    depot_id INT NOT NULL,
    employment_status BOOLEAN NOT NULL,
    CONSTRAINT fk_depotidINmechanic FOREIGN KEY (depot_id) REFERENCES depot(depot_id)
);

CREATE TABLE mechanic_certification_record (
    mechanic_certification_id TINYINT UNSIGNED NOT NULL,
    mechanic_id INT NOT NULL,
    expiration_date DATE NOT NULL,
    CONSTRAINT pk_mechanicCertificationRecord PRIMARY KEY (mechanic_certification_id,mechanic_id),
    CONSTRAINT fk_mechanicCertificationidINrecord FOREIGN KEY (mechanic_certification_id) REFERENCES mechanic_certification(mechanic_certification_id),
    CONSTRAINT fk_mechanicidINrecord FOREIGN KEY (mechanic_id) REFERENCES mechanic(mechanic_id)  
);

CREATE TABLE maintenance_activity (
    job_id INT NOT NULL,
    activity_id TINYINT UNSIGNED NOT NULL,
    activity_type_id TINYINT UNSIGNED NOT NULL,
    diagnostic_result VARCHAR(255) NOT NULL,
    repeat_fault TINYINT UNSIGNED NOT NULL,
    warranty_applicable VARCHAR(255) NOT NULL,
    CONSTRAINT pk_maintenanceActivity PRIMARY KEY (job_id,activity_id),
    CONSTRAINT fk_jobidINactivity FOREIGN KEY (job_id) REFERENCES maintenance_job(job_id),
    CONSTRAINT fk_activityTypeidINactivity FOREIGN KEY (activity_type_id) REFERENCES activity_type(activity_type_id)
);

CREATE TABLE activity_required_certification (
    mechanic_certification_id TINYINT UNSIGNED NOT NULL,
    job_id INT NOT NULL,
    activity_id TINYINT UNSIGNED NOT NULL,
    CONSTRAINT pk_activityRequiredCertification PRIMARY KEY (mechanic_certification_id,job_id,activity_id),
    CONSTRAINT fk_mechanicCertificationidINrequired FOREIGN KEY (mechanic_certification_id) REFERENCES mechanic_certification(mechanic_certification_id),
    CONSTRAINT fk_jobidActivityidINrequired FOREIGN KEY (job_id,activity_id) REFERENCES maintenance_activity(job_id,activity_id)
);

CREATE TABLE mechanic_activity (
    mechanic_id INT NOT NULL,
    job_id INT NOT NULL,
    activity_id TINYINT UNSIGNED NOT NULL,
    labour_hours TIME NOT NULL,
    CONSTRAINT pk_mechanicLabour PRIMARY KEY (mechanic_id,job_id,activity_id),
    CONSTRAINT fk_mechanicidINlabour FOREIGN KEY (mechanic_id) REFERENCES mechanic(mechanic_id),
    CONSTRAINT fk_jobidActivityidINlabour FOREIGN KEY (job_id,activity_id) REFERENCES maintenance_activity(job_id,activity_id)
);

CREATE TABLE predictive_alert (
    alert_id INT NOT NULL AUTO_INCREMENT PRIMARY KEY,
    VIN VARCHAR(17) NOT NULL,
    alert_type_id TINYINT UNSIGNED NOT NULL,
    job_id INT NULL,
    timestamp TIMESTAMP NOT NULL,
    action_taken VARCHAR(255),
    CONSTRAINT fk_vinINalert FOREIGN KEY (VIN) REFERENCES vehicle(VIN),
    CONSTRAINT fk_alertTypeidINalert FOREIGN KEY (alert_type_id) REFERENCES alert_type(alert_type_id),
    CONSTRAINT fk_jobidINalert FOREIGN KEY (job_id) REFERENCES maintenance_job(job_id)
);



CREATE TABLE supplier (
    supplier_id INT UNSIGNED NOT NULL AUTO_INCREMENT PRIMARY KEY,
    supplier_name VARCHAR(100) NOT NULL,
    contact_info VARCHAR(100) NOT NULL,
    delivery_lead_time VARCHAR(100) NULL
);

CREATE TABLE part (
    part_id INT UNSIGNED NOT NULL AUTO_INCREMENT PRIMARY KEY,
    primary_supplier_id INT UNSIGNED NOT NULL,
    primary_unit_cost DECIMAL(10,0) NOT NULL,
    backup_supplier_id INT UNSIGNED NULL,
    backup_unit_cost DECIMAL(10,0) NULL,
    CONSTRAINT fk_primarySupplieridINpart FOREIGN KEY (primary_supplier_id) REFERENCES supplier(supplier_id),
    CONSTRAINT fk_backupSupplieridINpart FOREIGN KEY (backup_supplier_id) REFERENCES supplier(supplier_id),
    CONSTRAINT chk_primaryUnitCost CHECK (primary_unit_cost >=0.0),
    CONSTRAINT chk_backupUnitCost CHECK (backup_unit_cost >=0.0)
);

CREATE TABLE part_order (
    order_id INT UNSIGNED NOT NULL PRIMARY KEY,
    supplier_id INT UNSIGNED NOT NULL,
    order_date DATE NOT NULL,
    expected_delivery_date DATE NOT NULL,
    order_status ENUM ('PREPARING','IN TRANSIT','DELIVERED','CANCELED') DEFAULT 'PREPARING',
    CONSTRAINT fk_supplieridINpartOrder FOREIGN KEY (supplier_id) REFERENCES supplier(supplier_id)
);

CREATE TABLE part_order_detail (
    part_id INT UNSIGNED NOT NULL,
    order_id INT UNSIGNED NOT NULL,
    quantity_order INT UNSIGNED NOT NULL,
    unit_price DECIMAL(10,0) NOT NULL,
    CONSTRAINT pk_partOrderDetail PRIMARY KEY(part_id,order_id),
    CONSTRAINT fk_partidINpartOrderDetail FOREIGN KEY(part_id) REFERENCES part(part_id),
    CONSTRAINT fk_orderidINpartOrderDetail FOREIGN KEY(order_id) REFERENCES part_order(order_id),
    CONSTRAINT chk_unitPrice CHECK(unit_price >=0.0)
);

CREATE TABLE activity_part (
    job_id INT NOT NULL,
    activity_id TINYINT UNSIGNED NOT NULL,
    part_id INT UNSIGNED NOT NULL,
    quantity_used TINYINT UNSIGNED NOT NULL,
    CONSTRAINT pk_activityPart PRIMARY KEY(job_id,activity_id,part_id),
    CONSTRAINT fk_jobidActivityidINactivityPart FOREIGN KEY(job_id,activity_id) REFERENCES maintenance_activity(job_id,activity_id),
    CONSTRAINT fk_partidINactivityPart FOREIGN KEY(part_id) REFERENCES part(part_id)
);

CREATE TABLE warranty_claim (
    claim_id INT UNSIGNED NOT NULL PRIMARY KEY,
    job_id INT NOT NULL,
    activity_id TINYINT UNSIGNED NOT NULL,
    part_id INT UNSIGNED NOT NULL,
    claim_type ENUM ('Standard Warranty','Component Warranty','Parts Warranty','Defect Campaigns') NULL,
    claim_date DATE NULL,
    claim_status ENUM ('SUBMITTED','CLARIFICATION NEEDED','APPROVED','REJECTED') DEFAULT 'SUBMITTED',
    manufacturer_id INT NULL,
    supplier_id INT UNSIGNED NULL,
    CONSTRAINT fk_jobidActivityidPartidINwarrantyClaim FOREIGN KEY(job_id,activity_id,part_id) REFERENCES activity_part(job_id,activity_id,part_id),
    CONSTRAINT fk_manufactureridINwarrantyClaim FOREIGN KEY(manufacturer_id) REFERENCES manufacturer(manufacturer_id),
    CONSTRAINT fk_supplieridINwarrantyClaim FOREIGN KEY(supplier_id) REFERENCES supplier(supplier_id)
);
