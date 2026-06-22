SHOW DATABASES;

CREATE DATABASE `library`;

USE library;

SHOW TABLES;

CREATE TABLE `vehicle_category` (
	`category_id` INT NOT NULL AUTO_INCREMENT,
    `category_name` VARCHAR(100) NOT NULL,
    PRIMARY KEY(`category_id`)
);

CREATE TABLE `vehicle_status` (
    `status_name` VARCHAR(100) NOT NULL,
    PRIMARY KEY(`status_name`)
);
CREATE TABLE `vehicle_manufacturer` (
	`warranty_provider_id` INT NOT NULL AUTO_INCREMENT,
    `manufacturer_model` VARCHAR(100) NOT NULL,
    PRIMARY KEY(`warranty_provider_id`),
    FOREIGN KEY (`warranty_provider_id`) REFERENCES `warranty_provider(warranty_provider_id)`
);
CREATE TABLE `vehicle` (
	`vehicle_id` INT NOT NULL AUTO_INCREMENT,
    `registration_number` INT NOT NULL,
    `VIN` INT NOT NULL,
    `category_id` INT NOT NULL,
    `manufacturer_id` VARCHAR(100) NOT NULL,
    `year_of_manufacture` INT NOT NULL,
    `current_odometer_reading` FLOAT NOT NULL,
    `depot_location` VARCHAR(100) NOT NULL,
    `status_name` VARCHAR(100) NOT NULL,
    PRIMARY KEY(`vehicle_id`),
    FOREIGN KEY(`category_id`) REFERENCES `vehicle_category(category_id)`,
    FOREIGN KEY(`manufacturer_id`) REFERENCES `vehicle_manufacturer(warranty_provider_id)`,
    FOREIGN KEY(`status_name`) REFERENCES `vehicle_status(status_name)`
);
CREATE TABLE `driver` (
	`driver_id` INT NOT NULL AUTO_INCREMENT,
    `driver_name` VARCHAR(100) NOT NULL,
    `contact_information` VARCHAR(100) NOT NULL,
    `depot_assignment` VARCHAR(100) NOT NULL,
    `employment_status` VARCHAR(100) NOT NULL,
    `emergency_contact_details` INT NOT NULL,
    PRIMARY KEY(`driver_id`)
);
CREATE TABLE `vehicle_driver` (
	`driver_id` INT NOT NULL AUTO_INCREMENT,
    `vehicle_id` INT NOT NULL ,
    PRIMARY KEY(`driver_id`),
    PRIMARY KEY(`vehicle_id`),
    FOREIGN KEY(`driver_id`) REFERENCES `driver(driver_id)`,
    FOREIGN KEY(`vehicle_id`) REFERENCES `vehicle(vehicle_id)`
);
CREATE TABLE `certification` (
	`cert_id` INT NOT NULL AUTO_INCREMENT,
    `cert_name` VARCHAR(100) NOT NULL,
    PRIMARY KEY(`cert_id`)
);
CREATE TABLE `event_type` (
	`event_type_id` INT NOT NULL AUTO_INCREMENT,
	`event_type` VARCHAR(100) NOT NULL,
    PRIMARY KEY(`event_type_id`)
);
CREATE TABLE `event_severities` (
	`severity_id` INT NOT NULL AUTO_INCREMENT,
	`event_severity` ENUM ('LOW','MEDIUM','HIGH','CRITICAL'),
    `point_deducted` ENUM ('-2','-5','-10','-20'),
    PRIMARY KEY(`severity_id`)
);
CREATE TABLE `driver_safety_event_log` (
	`event_id` INT NOT NULL AUTO_INCREMENT,
    `timestamp` TIMESTAMP NOT NULL,
    `driver_id` INT NOT NULL,
    `event_type_id` INT NOT NULL,
    `severity_id` INT NOT NULL,
    PRIMARY KEY(`event_id`),
    FOREIGN KEY (`driver_id`) REFERENCES `driver(driver_id)`,
    FOREIGN KEY (`event_type_id`) REFERENCES `event_type(event_type_id)`,
    FOREIGN KEY (`severity_id`) REFERENCES `event_severities(severity_id)`
);
CREATE TABLE `driver_certifications_currently` (
	`cert_id` INT NOT NULL,
    `driver_id` INT NOT NULL,
    `expiration_date` DATE NOT NULL,
    PRIMARY KEY(`cert_id`),
    PRIMARY KEY(`driver_id`),
    FOREIGN KEY (`cert_id`) REFERENCES `certification(cert_id)`,
    FOREIGN KEY (`driver_id`) REFERENCES `driver(driver_id)`
);
CREATE TABLE `vehicle_required_certification` (
	`cert_id` INT NOT NULL,
    `category_id` INT NOT NULL,
    PRIMARY KEY(`cert_id`),
    PRIMARY KEY(`category_id`),
    FOREIGN KEY (`cert_id`) REFERENCES `certification(cert_id)`,
    FOREIGN KEY (`category_id`) REFERENCES `vehicle_category(category_id)`
);
CREATE TABLE `job_level_information` (
	`job_id` INT NOT NULL AUTO_INCREMENT,
    `vehicle_id`INT NOT NULL,
	`workshop` VARCHAR(100) NOT NULL,
    `date_opened` DATE NOT NULL,
    `date_closed` DATE NOT NULL,
    `datetime` DATETIME NOT NULL,
    `total_cost` FLOAT NOT NULL,
    PRIMARY KEY(`job_id`),
    FOREIGN KEY (`vehicle_id`) REFERENCES `vehicle(vehicle_id)`
);
CREATE TABLE `warranty_provider` (
    `warranty_provider_id` INT NOT NULL AUTO_INCREMENT,
    PRIMARY KEY(`warranty_provider_id`)
);