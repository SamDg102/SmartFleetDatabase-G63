-- ==========================================================
-- 1. LOOKUP TABLES
-- ==========================================================

-- Driver certifications (theo Vehicle Certification Matrix)
INSERT INTO `driver_certification` (`driver_certification_id`, `driver_certification_name`) VALUES
(1, 'Standard Licence'),
(2, 'Heavy Vehicle Licence'),
(3, 'Refrigerated Transport Certification'),
(4, 'EV Certification'),
(5, 'Hazardous Goods Certification');

-- Vehicle categories
INSERT INTO `vehicle_category` (`category_id`, `category_name`) VALUES
(1, 'Delivery Van'),
(2, 'Refrigerated Truck'),
(3, 'Electric Van'),
(4, 'Service Vehicle'),
(5, 'Heavy Transport Truck');

-- Depots
INSERT INTO `depot` (`depot_id`, `depot_name`, `depot_location`) VALUES
(1, 'Ha Noi Depot', 'Ha Noi, Vietnam'),
(2, 'Da Nang Depot', 'Da Nang, Vietnam'),
(3, 'Ho Chi Minh City Depot', 'Ho Chi Minh City, Vietnam'),
(4, 'Can Tho Depot', 'Can Tho, Vietnam');

-- Manufacturers
INSERT INTO `manufacturer` (`manufacturer_id`, `manufacturer_name`, `contact_info`) VALUES
(1, 'Hino Motors', 'sales@hino-vn.com'),
(2, 'Isuzu Vietnam', 'contact@isuzu-vn.com'),
(3, 'VinFast', 'fleet@vinfast.vn'),
(4, 'Hyundai Thanh Cong', 'support@hyundai-tc.vn'),
(5, 'Thaco Truck', 'info@thaco.com.vn');

-- Severity levels (theo bảng Event Penalties)
INSERT INTO `severity_level` (`severity_id`, `level_name`, `points_deducted`, `deduction_condition`, `additional_deduction`) VALUES
(1, 'LOW', -2, 'More than 3 speeding events in a month', -10),
(2, 'MEDIUM', -5, 'More than 2 fatigue warnings in a month', -15),
(3, 'HIGH', -10, 'Any critical event', -10),
(4, 'CRITICAL', -20, NULL, 0);

-- Mechanic certifications (theo bảng Required Mechanic Certification)
INSERT INTO `mechanic_certification` (`mechanic_certification_id`, `mechanic_certification_name`) VALUES
(1, 'Standard Vehicle Mechanic Licence'),
(2, 'EV Technician Certification'),
(3, 'Refrigeration Systems Certification'),
(4, 'Heavy Vehicle Mechanic Licence');

-- Activity types
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

-- Alert types (từ Predictive Alert examples)
INSERT INTO `alert_type` (`alert_type_id`, `alert_name`, `description`) VALUES
(1, 'Brake Wear Warning', 'Brake pads or discs approaching minimum wear threshold'),
(2, 'Engine Overheating Risk', 'Engine temperature trending above safe operating range'),
(3, 'Battery Degradation', 'Battery capacity degrading below expected performance'),
(4, 'Oil Quality Deterioration', 'Engine oil quality below acceptable standard'),
(5, 'Transmission Fault Warning', 'Abnormal transmission behaviour detected'),
(6, 'Cooling System Anomaly', 'Cooling system readings outside normal range'),
(7, 'Tyre Pressure Irregularity', 'Tyre pressure inconsistent across axles');

-- Event types (Driver Behaviour Monitoring)
INSERT INTO `event_type` (`event_type_id`, `event_type_name`) VALUES
(1, 'Harsh Braking'),
(2, 'Rapid Acceleration'),
(3, 'Excessive Speeding'),
(4, 'Sharp Cornering'),
(5, 'Excessive Idling'),
(6, 'Fatigue Warning'),
(7, 'Seatbelt Violation'),
(8, 'Phone Distraction Alert');

-- Suppliers
INSERT INTO `supplier` (`supplier_id`, `supplier_name`, `contact_info`, `delivery_lead_time`) VALUES
(1, 'Viet Auto Parts Co', 'orders@vietautoparts.vn', '5 days'),
(2, 'Saigon Truck Supplies', 'sales@saigontrucksupplies.vn', '3 days'),
(3, 'Global Fleet Parts Ltd', 'export@globalfleetparts.com', '14 days');

-- ==========================================================
-- 2. FLEET & DRIVER MANAGEMENT
-- ==========================================================

-- Category required certifications (Vehicle Certification Matrix)
INSERT INTO `category_required_certification` (`driver_certification_id`, `category_id`) VALUES
(1, 1),  -- Standard Licence -> Delivery Van
(1, 2),  -- Standard Licence -> Refrigerated Truck
(2, 2),  -- Heavy Vehicle Licence -> Refrigerated Truck
(3, 2),  -- Refrigerated Transport Certification -> Refrigerated Truck
(1, 3),  -- Standard Licence -> Electric Van
(4, 3),  -- EV Certification -> Electric Van
(1, 4),  -- Standard Licence -> Service Vehicle
(2, 5),  -- Heavy Vehicle Licence -> Heavy Transport Truck
(5, 5);  -- Hazardous Goods Certification -> Heavy Transport Truck

-- Drivers (D-112, D-204, D-331, D-417 từ ví dụ, thêm 2 driver phụ)
INSERT INTO `driver` (`driver_id`, `full_name`, `contact_info`, `licence_type`, `licence_expiry_date`, `depot_id`, `employment_status`, `emergency_contact_details`, `safety_hold`) VALUES
(112, 'Nguyen Van An', 'nguyen.van.an@fleetco.vn / 0912-345-678', 'B2', '2027-01-01', 1, 'ACTIVE', 'Nguyen Thi Hoa - 0912-000-111 (wife)', FALSE),
(204, 'Tran Thi Bich', 'tran.thi.bich@fleetco.vn / 0918-222-333', 'C', '2026-06-01', 3, 'ACTIVE', 'Tran Van Long - 0918-000-222 (brother)', TRUE),
(331, 'Le Quoc Minh', 'le.quoc.minh@fleetco.vn / 0933-444-555', 'B2', '2026-06-21', 1, 'ACTIVE', 'Le Thi Hang - 0933-000-333 (mother)', FALSE),
(417, 'Pham Duc Long', 'pham.duc.long@fleetco.vn / 0987-666-777', 'C', '2027-01-01', 3, 'ACTIVE', 'Pham Thi Nga - 0987-000-444 (wife)', FALSE),
(502, 'Do Thi Kim Ngan', 'do.kim.ngan@fleetco.vn / 0909-888-999', 'B2', '2027-03-15', 4, 'ACTIVE', 'Do Van Tam - 0909-000-555 (father)', FALSE),
(518, 'Vu Manh Cuong', 'vu.manh.cuong@fleetco.vn / 0977-111-222', 'B2', '2026-09-10', 2, 'ACTIVE', 'Vu Thi Lan - 0977-000-666 (wife)', FALSE);

-- Vehicles (VIN 17 ký tự, registration_number theo ví dụ trong Safety Event Log / Maintenance Job)
INSERT INTO `vehicle` (`VIN`, `category_id`, `registration_number`, `manufacturer_id`, `model`, `year_of_manufacture`, `current_odometer`, `depot_id`, `vehicle_status`) VALUES
('VN1DV2020HN000123', 1, '29A-123.45', 1, '300 Series', 2020, 45310.0, 1, 'ACTIVE'),
('VN2RT2019HC000789', 2, '51C-789.01', 2, 'NQR', 2019, 112480.0, 3, 'ACTIVE'),
('VN3EV2022DN000456', 3, '43E-456.78', 3, 'VF e34 Cargo', 2022, 12300.0, 2, 'ACTIVE'),
('VN4SV2021CT000234', 4, '65B-234.56', 4, 'H150', 2021, 30500.0, 4, 'AVAILABLE'),
('VN5HT2018HN000987', 5, '29H-987.65', 5, 'Auman C160', 2018, 205000.0, 1, 'UNDER MAINTENANCE');

-- Driver certification records (từ Example driver certifications table)
INSERT INTO `driver_certification_record` (`driver_certification_id`, `driver_id`, `issue_date`, `expiration_date`) VALUES
(1, 112, '2023-02-14', '2025-02-14'),
(4, 112, '2023-04-30', '2025-04-30'),
(2, 204, '2023-12-08', '2025-12-08'),
(3, 204, '2024-05-01', '2026-05-01'),
(1, 331, '2024-06-21', '2026-06-21'),
(5, 417, '2024-11-18', '2026-11-18'),
(1, 417, '2024-11-18', '2026-11-18'),
(1, 502, '2024-01-10', '2026-01-10'),
(1, 518, '2024-02-20', '2026-02-20');

-- Vehicle-driver assignments (một số xe được chia sẻ giữa nhiều tài xế)
INSERT INTO `vehicle_driver_assignment` (`driver_id`, `VIN`, `start_date`, `end_date`) VALUES
(112, 'VN1DV2020HN000123', '2024-01-01', NULL),
(331, 'VN1DV2020HN000123', '2024-05-13', NULL),
(204, 'VN2RT2019HC000789', '2024-01-01', NULL),
(112, 'VN3EV2022DN000456', '2024-05-01', NULL),
(502, 'VN4SV2021CT000234', '2024-02-01', NULL);

-- ==========================================================
-- 3. SAFETY & INCIDENT MANAGEMENT
-- ==========================================================

-- Monthly safety scores (May 2024), tính theo Event Penalties
-- Driver 112: LOW(-2) + HIGH(-10) + HIGH(-10) = 78 -> no action required
-- Driver 204: MEDIUM(-5) + CRITICAL(-20) + additional(-10, "Any critical event") = 65 -> coaching required
-- Driver 331: LOW(-2) = 98 -> no action required
-- Driver 417: no events recorded in May -> full score
INSERT INTO `monthly_safety_score` (`driver_id`, `score_month`, `final_score`, `action_required`) VALUES
(112, '2024-05-01', 78, NULL),
(204, '2024-05-01', 65, 'Driver coaching required (score <= 75)'),
(331, '2024-05-01', 98, NULL),
(417, '2024-05-01', 100, NULL);

-- Safety events (Example Driver Safety Event Log E091-E096)
INSERT INTO `safety_event` (`event_id`, `driver_id`, `depot_id`, `VIN`, `timestamp`, `event_type_id`, `severity_id`, `review_required`, `review_status`, `reviewer_comments`) VALUES
(91, 112, 1, 'VN1DV2020HN000123', '2024-05-10 08:14:00', 1, 1, FALSE, 'COMPLETED', NULL),
(92, 112, 1, 'VN1DV2020HN000123', '2024-05-10 09:30:00', 3, 3, TRUE, 'PENDING', NULL),
(93, 204, 3, 'VN2RT2019HC000789', '2024-05-11 11:00:00', 4, 2, FALSE, 'COMPLETED', NULL),
(94, 112, 2, 'VN3EV2022DN000456', '2024-05-12 14:20:00', 6, 3, TRUE, 'PENDING', NULL),
(95, 331, 1, 'VN1DV2020HN000123', '2024-05-13 07:42:00', 5, 1, FALSE, 'COMPLETED', NULL),
(96, 204, 3, 'VN2RT2019HC000789', '2024-05-13 18:05:00', 3, 4, TRUE, 'PENDING', 'Critical speeding event - driver placed on safety hold pending review');

-- Coaching record triggered by the critical event (E096) for driver 204
INSERT INTO `coaching_record` (`driver_id`, `trigger_event_id`, `score_month`, `coaching_type`, `assigned_date`, `completion_date`, `outcome`, `status`) VALUES
(204, 96, '2024-05-01', 'RETRAINING', '2024-05-16', NULL, NULL, 'PENDING');

-- ==========================================================
-- 4. MAINTENANCE & WORKSHOP OPERATIONS
-- ==========================================================

-- Service bays (mỗi depot có 1 workshop, nhiều bay)
INSERT INTO `service_bay` (`service_bay_id`, `bay_name`, `bay_status`, `depot_id`) VALUES
(1, 'HN-Bay-1', 'FREE', 1),
(2, 'HN-Bay-2', 'FREE', 1),
(3, 'HC-Bay-1', 'FREE', 3),
(4, 'HC-Bay-2', 'FREE', 3),
(5, 'DN-Bay-1', 'FREE', 2),
(6, 'CT-Bay-1', 'FREE', 4);

-- Maintenance jobs (M1021, M1022 từ ví dụ Job-Level Information)
INSERT INTO `maintenance_job` (`job_id`, `VIN`, `service_bay_id`, `date_opened`, `date_closed`, `downtime_hours`, `job_status`, `total_cost`) VALUES
(1021, 'VN1DV2020HN000123', 1, '2024-05-12 09:00:00', '2024-05-13 03:00:00', 18.0, 'DONE', 1800000.00),
(1022, 'VN2RT2019HC000789', 3, '2024-05-14 08:00:00', '2024-05-14 14:00:00', 6.0, 'DONE', 3610000.00);

-- Mechanics (ME-12, ME-15, ME-07, ME-09 từ ví dụ Activity-Level Information)
INSERT INTO `mechanic` (`mechanic_id`, `full_name`, `depot_id`, `employment_status`) VALUES
(12, 'Hoang Van Duc', 1, 'ACTIVE'),
(15, 'Pham Thi Lan', 1, 'ACTIVE'),
(7, 'Nguyen Thi Mai', 3, 'ACTIVE'),
(9, 'Tran Quoc Bao', 3, 'ACTIVE');

-- Mechanic certification records
INSERT INTO `mechanic_certification_record` (`mechanic_certification_id`, `mechanic_id`, `issue_date`, `expiration_date`) VALUES
(1, 12, '2020-01-10', '2027-01-10'),
(1, 15, '2019-06-15', '2026-06-15'),
(1, 7, '2021-03-01', '2027-03-01'),
(3, 9, '2020-09-01', '2026-09-01'),
(1, 9, '2020-09-01', '2026-09-01');

-- Activity required certifications (Workshops and Mechanics table)
INSERT INTO `activity_required_certification` (`mechanic_certification_id`, `activity_type_id`) VALUES
(1, 1),   -- Standard Vehicle Mechanic Licence -> Routine Inspection
(1, 2),   -- Standard Vehicle Mechanic Licence -> Preventative Servicing
(1, 3),   -- Standard Vehicle Mechanic Licence -> Diagnostic Testing
(1, 4),   -- Standard Vehicle Mechanic Licence -> Emergency Repair
(1, 5),   -- Standard Vehicle Mechanic Licence -> Component Replacement
(1, 9),   -- Standard Vehicle Mechanic Licence -> Brake Service
(1, 10),  -- Standard Vehicle Mechanic Licence -> Tyre Replacement
(2, 6),   -- EV Technician Certification -> EV Battery / Electrical Repair
(3, 7),   -- Refrigeration Systems Certification -> Refrigeration System Repair
(4, 8);   -- Heavy Vehicle Mechanic Licence -> Heavy Vehicle Repair

-- Maintenance activities (từ ví dụ Activity-Level Information)
INSERT INTO `maintenance_activity` (`job_id`, `activity_id`, `activity_type_id`, `diagnostic_result`, `repeat_fault`, `warranty_applicable`) VALUES
(1021, 1, 9, 'Pads worn below 3mm', FALSE, FALSE),
(1021, 2, 10, 'Worn unevenly - possible alignment issue', TRUE, FALSE),
(1022, 1, 2, 'OK', FALSE, FALSE),
(1022, 2, 7, 'Belt cracked - 3rd replacement this year', TRUE, TRUE);

-- Mechanic activity (labour hours từ ví dụ)
INSERT INTO `mechanic_activity` (`mechanic_id`, `job_id`, `activity_id`, `labour_hours`) VALUES
(12, 1021, 1, 2.5),
(15, 1021, 1, 2.5),
(12, 1021, 2, 1.0),
(7, 1022, 1, 1.5),
(9, 1022, 2, 2.0);

-- Predictive alerts (onboard diagnostic system examples)
INSERT INTO `predictive_alert` (`VIN`, `alert_type_id`, `job_id`, `timestamp`, `action_taken`) VALUES
('VN1DV2020HN000123', 1, 1021, '2024-05-11 20:00:00', 'SCHEDULED INSPECTION'),
('VN2RT2019HC000789', 6, 1022, '2024-05-13 22:00:00', 'ESCALATED'),
('VN5HT2018HN000987', 2, NULL, '2024-05-20 10:15:00', 'ACKNOWLEDGED');

-- ==========================================================
-- 5. SUPPLY & WARRANTY (Extension Tasks)
-- ==========================================================

-- Parts
INSERT INTO `part` (`part_id`, `part_number`, `description`, `primary_supplier_id`, `primary_unit_cost`, `backup_supplier_id`, `backup_unit_cost`, `stock_quantity`) VALUES
(1, 'BRK-PAD-001', 'Brake Pad Set', 1, 450000.00, 2, 480000.00, 50),
(2, 'TYRE-215-001', '215/75R16 Tyre', 2, 1200000.00, NULL, NULL, 20),
(3, 'REF-BELT-01', 'Refrigeration Compressor Belt', 3, 350000.00, 1, 375000.00, 15);

-- Part orders
INSERT INTO `part_order` (`order_id`, `supplier_id`, `order_date`, `expected_delivery_date`, `actual_delivery_date`, `order_status`) VALUES
(1, 1, '2024-05-05', '2024-05-10', '2024-05-09', 'DELIVERED'),
(2, 3, '2024-05-01', '2024-05-15', NULL, 'IN TRANSIT');

-- Part order details
INSERT INTO `part_order_detail` (`part_id`, `order_id`, `quantity_order`, `unit_price`) VALUES
(1, 1, 10, 450000.00),
(3, 2, 5, 350000.00);

-- Parts used in activities (snapshot cost at time of use)
INSERT INTO `activity_part` (`job_id`, `activity_id`, `part_id`, `quantity_used`, `unit_cost_at_use`) VALUES
(1021, 1, 1, 1, 450000.00),
(1021, 2, 2, 4, 1200000.00),
(1022, 2, 3, 1, 350000.00);

-- Warranty claims
INSERT INTO `warranty_claim` (`claim_id`, `claim_type`, `claim_date`, `claim_status`, `manufacturer_id`, `supplier_id`) VALUES
(1, 'Parts Warranty', '2024-05-15', 'SUBMITTED', NULL, 3);

-- Warranty claim parts (links warranty claim to the refrigeration belt replacement)
INSERT INTO `warranty_claim_part` (`claim_id`, `job_id`, `activity_id`, `part_id`) VALUES
(1, 1022, 2, 3);
