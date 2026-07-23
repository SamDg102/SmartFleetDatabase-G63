USE `G63System`;

-- ==========================================
-- 1. LOOKUP TABLES
-- ==========================================

INSERT INTO `depot` (`depot_id`, `depot_name`, `depot_location`) VALUES
(1, 'Ha Noi Depot', 'Ha Noi, Vietnam'),
(2, 'Da Nang Depot', 'Da Nang, Vietnam'),
(3, 'Ho Chi Minh City Depot', 'Ho Chi Minh City, Vietnam'),
(4, 'Can Tho Depot', 'Can Tho, Vietnam');

INSERT INTO `vehicle_category` (`category_id`, `category_name`) VALUES
(1, 'Delivery Van'),
(2, 'Refrigerated Truck'),
(3, 'Electric Van'),
(4, 'Service Vehicle'),
(5, 'Heavy Transport Truck');

INSERT INTO `manufacturer` (`manufacturer_id`, `manufacturer_name`, `contact_info`) VALUES
(1, 'Hino Motors', 'sales@hino-vn.com'),
(2, 'Isuzu', 'contact@isuzu-vn.com'),
(3, 'VinFast', 'fleet@vinfast.vn'),
(4, 'Mercedes-Benz', 'commercial@mercedes-benz.vn'),
(5, 'Hyundai', 'trucks@hyundai-vn.com');

INSERT INTO `severity_level` (`severity_id`, `level_name`, `points_deducted`, `deduction_condition`, `additional_deduction`) VALUES
(1, 'LOW', -2, 'More than 3 speeding events in a month', -10),
(2, 'MEDIUM', -5, 'More than 2 fatigue warnings in a month', -15),
(3, 'HIGH', -10, 'Any critical event', -10),
(4, 'CRITICAL', -20, NULL, 0);

INSERT INTO `driver_certification` (`driver_certification_id`, `driver_certification_name`) VALUES
('CERT-STD', 'Standard Licence'),
('CERT-HV', 'Heavy Vehicle Licence'),
('CERT-REF', 'Refrigerated Transport Certification'),
('CERT-EV', 'EV Certification'),
('CERT-HAZ', 'Hazardous Goods Certification');

INSERT INTO `mechanic_certification` (`mechanic_certification_id`, `mechanic_certification_name`) VALUES
(1, 'Standard Vehicle Mechanic Licence'),
(2, 'EV Technician Certification'),
(3, 'Refrigeration Systems Certification'),
(4, 'Heavy Vehicle Mechanic Licence');

INSERT INTO `activity_type` (`activity_type_id`, `activity_type_name`) VALUES
(1, 'Routine Inspection'),
(2, 'Preventative Servicing'),
(3, 'Diagnostic Testing'),
(4, 'Emergency Repair'),
(5, 'Component Replacement'),
(6, 'EV Battery / Electrical Repair'),
(7, 'Refrigeration System Repair'),
(8, 'Heavy Vehicle Repair'),
(9, 'Brake Service'),
(10, 'Tyre Replacement');

INSERT INTO `alert_type` (`alert_type_id`, `alert_name`, `description`) VALUES
(1, 'Brake Wear Warning', 'Brake pad or disc wear approaching unsafe threshold'),
(2, 'Engine Overheating Risk', 'Engine temperature trending above safe operating range'),
(3, 'Battery Degradation', 'EV battery capacity degrading faster than expected'),
(4, 'Oil Quality Deterioration', 'Engine oil quality below recommended standard'),
(5, 'Transmission Fault Warning', 'Transmission system reporting fault codes'),
(6, 'Cooling System Anomaly', 'Refrigeration or engine cooling system irregularity'),
(7, 'Tyre Pressure Irregularity', 'Tyre pressure outside recommended range');

INSERT INTO `event_type` (`event_type_id`, `event_type_name`) VALUES
(1, 'Harsh Braking'),
(2, 'Rapid Acceleration'),
(3, 'Excessive Speeding'),
(4, 'Sharp Cornering'),
(5, 'Excessive Idling'),
(6, 'Fatigue Warning'),
(7, 'Seatbelt Violation'),
(8, 'Phone Distraction Alert');

INSERT INTO `supplier` (`supplier_id`, `supplier_name`, `contact_info`, `delivery_lead_time`) VALUES
(1, 'Global Auto Parts Co', 'orders@globalautoparts.com', '5-7 business days'),
(2, 'Mekong Parts Supply', 'sales@mekongparts.vn', '3-5 business days'),
(3, 'VN Truck Components Ltd', 'contact@vntruckcomponents.vn', '7-10 business days');

-- ==========================================
-- 2. Fleet & Driver Management
-- ==========================================

-- Vehicle Certification Matrix (from brief)
INSERT INTO `category_required_certification` (`driver_certification_id`, `category_id`) VALUES
('CERT-STD', 1),                 -- Delivery Van
('CERT-STD', 2), ('CERT-HV', 2), ('CERT-REF', 2),   -- Refrigerated Truck
('CERT-STD', 3), ('CERT-EV', 3), -- Electric Van
('CERT-STD', 4),                 -- Service Vehicle
('CERT-HV', 5), ('CERT-HAZ', 5); -- Heavy Transport Truck

-- Driver IDs kept numeric (112, 204, 331, 417) to mirror the D-112 / D-204 / D-331 / D-417 codes in the brief
INSERT INTO `driver` (`driver_id`, `full_name`, `contact_info`, `licence_type`, `licence_expiry_date`, `depot_id`, `employment_status`, `emergency_contact_details`) VALUES
(112, 'Nguyen Van An', '090-111-2233', 'Standard + EV', '2027-03-01', 1, 'ACTIVE', 'Nguyen Thi Hoa - 090-111-2200'),
(204, 'Tran Thi Bich', '091-222-3344', 'Heavy Vehicle + Refrigerated', '2026-09-15', 3, 'ACTIVE', 'Tran Van Cuong - 091-222-3300'),
(331, 'Le Quoc Minh', '092-333-4455', 'Standard', '2026-06-21', 1, 'ACTIVE', 'Le Thi Mai - 092-333-4400'),
(417, 'Pham Duc Long', '093-444-5566', 'Heavy Vehicle + Hazardous Goods', '2026-11-18', 4, 'ACTIVE', 'Pham Thi Nga - 093-444-5500');

INSERT INTO `vehicle` (`VIN`, `category_id`, `registration_number`, `manufacturer_id`, `model`, `year_of_manufacture`, `current_odometer`, `depot_id`, `vehicle_status`) VALUES
('RVHNDV00001VIN01', 1, '29A-123.45', 1, 'Hino 300 Series', 2021, 45310, 1, 'ACTIVE'),
('RVHCTR00002VIN02', 2, '51C-789.01', 4, 'Mercedes-Benz Actros Reefer', 2020, 112480, 3, 'ACTIVE'),
('RVDNEV00003VIN03', 3, '43E-456.78', 3, 'VinFast EC Van', 2023, 12300, 2, 'AVAILABLE');

-- Example driver certifications from brief, with fabricated issue_date (1-2 years before expiry)
INSERT INTO `driver_certification_record` (`driver_certification_id`, `driver_id`, `issue_date`, `expiration_date`) VALUES
('CERT-STD', 112, '2023-02-14', '2025-02-14'),
('CERT-EV', 112, '2023-04-30', '2025-04-30'),
('CERT-HV', 204, '2023-12-08', '2025-12-08'),
('CERT-REF', 204, '2024-05-01', '2026-05-01'),
('CERT-STD', 331, '2024-06-21', '2026-06-21'),
('CERT-HAZ', 417, '2023-11-18', '2026-11-18');

-- VIN 1 is shared: driver 112 has the long-standing assignment, driver 331 also drives it (per event E095)
-- end_date = NULL means the assignment is still active
INSERT INTO `vehicle_driver_assignment` (`driver_id`, `VIN`, `start_date`, `end_date`) VALUES
(112, 'RVHNDV00001VIN01', '2024-01-01', NULL),
(331, 'RVHNDV00001VIN01', '2024-05-13', NULL),
(204, 'RVHCTR00002VIN02', '2024-01-01', NULL);

-- ==========================================
-- 3. Safety & Incident Management
-- ==========================================

-- Example Driver Safety Event Log from brief (E091-E096)
-- event_id auto-increments 1-6 in insertion order below (E091=1, E092=2, ... E096=6)
INSERT INTO `safety_event` (`driver_id`, `depot_id`, `VIN`, `timestamp`, `event_type_id`, `severity_id`, `review_required`, `reviewer_comments`) VALUES
(112, 1, 'RVHNDV00001VIN01', '2024-05-10 08:14:00', 1, 1, FALSE, NULL),                                   -- E091 Harsh Braking, Low
(112, 1, 'RVHNDV00001VIN01', '2024-05-10 09:30:00', 3, 3, TRUE,  'High-severity speeding event - pending review'), -- E092 Speeding, High
(204, 3, 'RVHCTR00002VIN02', '2024-05-11 11:00:00', 4, 2, FALSE, NULL),                                   -- E093 Sharp Cornering, Medium
(112, 2, 'RVDNEV00003VIN03', '2024-05-12 14:20:00', 6, 3, TRUE,  'Fatigue warning - driver referred for check'),   -- E094 Fatigue Warning, High
(331, 1, 'RVHNDV00001VIN01', '2024-05-13 07:42:00', 5, 1, FALSE, NULL),                                   -- E095 Excessive Idling, Low
(204, 3, 'RVHCTR00002VIN02', '2024-05-13 18:05:00', 3, 4, TRUE,  'Critical event - driver made inactive pending review'); -- E096 Speeding, Critical

-- Monthly scores calculated from the events above:
-- driver 112: -2 (E091 Low) -10 (E092 High) -10 (E094 High) = 78
-- driver 204: -5 (E093 Medium) -20 (E096 Critical) = 75 -> triggers "must attend driver coaching" rule
-- driver 331: -2 (E095 Low) = 98
-- driver 417: no events this month = 100
INSERT INTO `monthly_safety_score` (`driver_id`, `score_month`, `final_score`, `action_required`) VALUES
(112, '2024-05-01', 78, NULL),
(204, '2024-05-01', 75, 'Driver coaching required'),
(331, '2024-05-01', 98, NULL),
(417, '2024-05-01', 100, NULL);

-- Driver 204's critical event (E096, event_id 6) triggers mandatory retraining
INSERT INTO `coaching_record` (`driver_id`, `trigger_event_id`, `score_month`, `coaching_type`, `assigned_date`, `completion_date`, `outcome`, `status`) VALUES
(204, 6, '2024-05-01', 'RETRAINING', '2024-05-14', NULL, NULL, 'PENDING');

-- ==========================================
-- 4. Maintenance & Workshop Operations
-- ==========================================

INSERT INTO `service_bay` (`service_bay_id`, `bay_name`, `bay_status`, `depot_id`) VALUES
(1, 'HN-Bay-1', 'FREE', 1),
(2, 'HCMC-Bay-1', 'FREE', 3);

-- Mechanic IDs kept numeric (12, 15, 7, 9) to mirror the ME-12 / ME-15 / ME-07 / ME-09 codes in the brief
INSERT INTO `mechanic` (`mechanic_id`, `full_name`, `depot_id`, `employment_status`) VALUES
(12, 'Hoang Van Duc', 1, 'ACTIVE'),
(15, 'Pham Thi Lan', 1, 'ACTIVE'),
(7, 'Nguyen Thi Mai', 3, 'ACTIVE'),
(9, 'Tran Quoc Bao', 3, 'ACTIVE');

INSERT INTO `mechanic_certification_record` (`mechanic_certification_id`, `mechanic_id`, `issue_date`, `expiration_date`) VALUES
(1, 12, '2022-01-10', '2027-01-10'),
(1, 15, '2022-03-05', '2027-03-05'),
(1, 7, '2021-11-20', '2026-11-20'),
(1, 9, '2021-11-20', '2026-11-20'),
(3, 9, '2023-06-01', '2026-06-01');   -- Tran Quoc Bao also holds Refrigeration Systems Certification

-- Required certification matrix from brief
INSERT INTO `activity_required_certification` (`mechanic_certification_id`, `activity_type_id`) VALUES
(1, 1), (1, 2), (1, 3), (1, 4), (1, 5),  -- Standard Vehicle Mechanic Licence activities
(2, 6),                                  -- EV Battery / Electrical Repair
(3, 7),                                  -- Refrigeration System Repair
(4, 8);                                  -- Heavy Vehicle Repair

-- Maintenance job records from brief (M1021, M1022)
INSERT INTO `maintenance_job` (`job_id`, `VIN`, `service_bay_id`, `date_opened`, `date_closed`, `downtime_hours`, `job_status`, `total_cost`) VALUES
(1021, 'RVHNDV00001VIN01', 1, '2024-05-12 09:00:00', '2024-05-13 03:00:00', 18.0, 'DONE', 1800000.00),
(1022, 'RVHCTR00002VIN02', 2, '2024-05-14 08:00:00', '2024-05-14 14:00:00', 6.0, 'DONE', 3610000.00);

-- Activity records from brief
INSERT INTO `maintenance_activity` (`job_id`, `activity_id`, `activity_type_id`, `diagnostic_result`, `repeat_fault`, `warranty_applicable`) VALUES
(1021, 1, 9, 'Pads worn below 3mm', FALSE, FALSE),                                  -- Brake Service
(1021, 2, 10, 'Worn unevenly - possible alignment issue', TRUE, FALSE),             -- Tyre Replacement
(1022, 1, 2, 'OK', FALSE, FALSE),                                                   -- Preventative Servicing
(1022, 2, 7, 'Belt cracked - 3rd replacement this year', TRUE, TRUE);               -- Refrigeration System Repair

-- Mechanic labour records from brief
INSERT INTO `mechanic_activity` (`mechanic_id`, `job_id`, `activity_id`, `labour_hours`) VALUES
(12, 1021, 1, 2.5),
(15, 1021, 1, 2.5),
(12, 1021, 2, 1.0),
(7, 1022, 1, 1.5),
(9, 1022, 2, 2.0);

-- Predictive alerts that led to these jobs
INSERT INTO `predictive_alert` (`VIN`, `alert_type_id`, `job_id`, `timestamp`, `action_taken`) VALUES
('RVHNDV00001VIN01', 1, 1021, '2024-05-11 20:00:00', 'SCHEDULED INSPECTION'),  -- Brake Wear Warning -> M1021
('RVHCTR00002VIN02', 6, 1022, '2024-05-13 22:00:00', 'ESCALATED');            -- Cooling System Anomaly -> M1022

-- ==========================================
-- 5. Supply & Warranty
-- ==========================================

INSERT INTO `part` (`part_id`, `part_number`, `description`, `primary_supplier_id`, `primary_unit_cost`, `backup_supplier_id`, `backup_unit_cost`) VALUES
(1, 'BRK-PAD-STD', 'Standard brake pad set', 1, 350000.00, 2, 380000.00),
(2, 'TYRE-19R', '19-inch commercial tyre', 3, 1200000.00, NULL, NULL),
(3, 'REF-BELT-01', 'Refrigeration compressor belt', 2, 450000.00, 3, 470000.00);

INSERT INTO `part_order` (`order_id`, `supplier_id`, `order_date`, `expected_delivery_date`, `actual_delivery_date`, `order_status`) VALUES
(1, 1, '2024-05-01', '2024-05-10', '2024-05-09', 'DELIVERED');

INSERT INTO `part_order_detail` (`part_id`, `order_id`, `quantity_order`, `unit_price`) VALUES
(1, 1, 20, 350000.00);

-- Parts consumed during the maintenance activities above
INSERT INTO `activity_part` (`job_id`, `activity_id`, `part_id`, `quantity_used`) VALUES
(1021, 1, 1, 4),   -- 4 brake pads used in the Brake Service
(1021, 2, 2, 2),   -- 2 tyres used in the Tyre Replacement
(1022, 2, 3, 1);   -- 1 refrigeration belt used in the Refrigeration System Repair

-- The refrigeration belt failure is a repeat fault (3rd time this year) and warranty-eligible,
-- so a claim is raised against the parts supplier (belt, not a manufacturer defect)
INSERT INTO `warranty_claim` (`claim_id`, `claim_type`, `claim_date`, `claim_status`, `manufacturer_id`, `supplier_id`) VALUES
(1, 'Component Warranty', '2024-05-15', 'SUBMITTED', NULL, 2);

INSERT INTO `warranty_claim_part` (`claim_id`, `job_id`, `activity_id`, `part_id`) VALUES
(1, 1022, 2, 3);
