-- ==========================================================
-- 0. INDEXES
-- ==========================================================

CREATE INDEX idx_safetyEvent_driver_time      ON safety_event (driver_id, timestamp);
CREATE INDEX idx_safetyEvent_severity_review  ON safety_event (severity_id, review_status);
CREATE INDEX idx_driverCertRecord_driver_exp  ON driver_certification_record (driver_id, expiration_date);
CREATE INDEX idx_mechCertRecord_mech_exp      ON mechanic_certification_record (mechanic_id, expiration_date);
CREATE INDEX idx_maintenanceJob_vin_opened    ON maintenance_job (VIN, date_opened);
CREATE INDEX idx_maintenanceActivity_type_rf  ON maintenance_activity (activity_type_id, repeat_fault);


-- ==========================================================
-- A. SAFETY STAFF
-- ==========================================================

-- A1. Dashboard: 4 stat cards (safety-staff.html)
-- Total events this month
SELECT COUNT(*) AS total_events
FROM safety_event
WHERE DATE_FORMAT(timestamp, '%Y-%m') = :month;   -- e.g. '2024-05'

-- Number of events awaiting review
SELECT COUNT(*) AS pending_review
FROM safety_event
WHERE review_status = 'PENDING';

-- Number of drivers currently on safety hold
SELECT COUNT(*) AS drivers_on_hold
FROM driver
WHERE safety_hold = TRUE;

-- Number of PENDING coaching records
SELECT COUNT(*) AS coaching_pending
FROM coaching_record
WHERE status = 'PENDING';


-- A2. Safety Event Log table, with filters (safety-staff.html)
SELECT
    se.event_id,
    se.timestamp,
    d.driver_id, d.full_name AS driver_name,
    se.VIN,
    dp.depot_name,
    et.event_type_name,
    sl.level_name AS severity,
    se.review_status
FROM safety_event se
JOIN driver d          ON d.driver_id = se.driver_id
JOIN depot dp           ON dp.depot_id = se.depot_id
JOIN event_type et      ON et.event_type_id = se.event_type_id
JOIN severity_level sl  ON sl.severity_id = se.severity_id
WHERE (:depot_id   IS NULL OR dp.depot_id = :depot_id)
  AND (:severity   IS NULL OR sl.level_name = :severity)
  AND (:review_status IS NULL OR se.review_status = :review_status)
  AND (:date_from  IS NULL OR se.timestamp >= :date_from)
  AND (:date_to    IS NULL OR se.timestamp <= :date_to)
ORDER BY se.timestamp DESC;


-- A3. Single event detail (safety-event-review.html)
SELECT
    se.event_id, se.timestamp, se.review_status, se.reviewer_comments,
    d.driver_id, d.full_name AS driver_name,
    v.VIN, vc.category_name,
    dp.depot_name,
    et.event_type_name,
    sl.level_name AS severity, sl.points_deducted
FROM safety_event se
JOIN driver d           ON d.driver_id = se.driver_id
JOIN vehicle v           ON v.VIN = se.VIN
JOIN vehicle_category vc ON vc.category_id = v.category_id
JOIN depot dp            ON dp.depot_id = se.depot_id
JOIN event_type et       ON et.event_type_id = se.event_type_id
JOIN severity_level sl   ON sl.severity_id = se.severity_id
WHERE se.event_id = :event_id;

-- A3b. That driver's current-month safety score (the "Driver Status" panel on the right)
SELECT final_score, action_required
FROM monthly_safety_score
WHERE driver_id = :driver_id AND score_month = :score_month;

-- A3c. That driver's other events in the same month (the "Other Events" panel)
SELECT se.event_id, et.event_type_name, sl.level_name AS severity
FROM safety_event se
JOIN event_type et      ON et.event_type_id = se.event_type_id
JOIN severity_level sl  ON sl.severity_id = se.severity_id
WHERE se.driver_id = :driver_id
  AND se.event_id != :event_id
  AND DATE_FORMAT(se.timestamp, '%Y-%m') = :score_month_str;


-- A4. Save a review (submitting the form on safety-event-review.html)
UPDATE safety_event
SET review_status = :review_status,
    reviewer_comments = :comments
WHERE event_id = :event_id;

-- If review_status becomes COMPLETED and the severity was CRITICAL -> release the safety hold
-- (run inside the same transaction as the UPDATE above, handled in PHP)
UPDATE driver
SET safety_hold = FALSE
WHERE driver_id = :driver_id;


-- A5. Monthly Safety Scores page
SELECT
    d.driver_id, d.full_name, dp.depot_name,
    mss.score_month, mss.final_score, mss.action_required
FROM monthly_safety_score mss
JOIN driver d ON d.driver_id = mss.driver_id
JOIN depot dp ON dp.depot_id = d.depot_id
WHERE mss.score_month = :score_month
ORDER BY mss.final_score ASC;


-- A6. Coaching & Retraining page
SELECT
    cr.driver_id, d.full_name,
    cr.trigger_event_id, cr.score_month,
    cr.coaching_type, cr.assigned_date, cr.completion_date, cr.outcome, cr.status
FROM coaching_record cr
JOIN driver d ON d.driver_id = cr.driver_id
WHERE (:status IS NULL OR cr.status = :status)
ORDER BY cr.assigned_date DESC;

-- Update coaching outcome
UPDATE coaching_record
SET completion_date = :completion_date,
    outcome = :outcome,
    status = 'COMPLETED'
WHERE driver_id = :driver_id AND trigger_event_id = :trigger_event_id;


-- A7. Driver Certifications page — certifications expiring soon / already expired
SELECT
    d.driver_id, d.full_name,
    dc.driver_certification_name,
    dcr.issue_date, dcr.expiration_date,
    CASE WHEN dcr.expiration_date < CURDATE() THEN 'EXPIRED'
         WHEN dcr.expiration_date <= DATE_ADD(CURDATE(), INTERVAL 30 DAY) THEN 'EXPIRING SOON'
         ELSE 'VALID' END AS status
FROM driver_certification_record dcr
JOIN driver d ON d.driver_id = dcr.driver_id
JOIN driver_certification dc ON dc.driver_certification_id = dcr.driver_certification_id
WHERE dcr.expiration_date <= DATE_ADD(CURDATE(), INTERVAL 30 DAY)
ORDER BY dcr.expiration_date ASC;


-- A8. Driver & Vehicle Assignment page — check certifications are all present before assigning (validated in PHP before the INSERT)
SELECT COUNT(*) AS missing_certs
FROM category_required_certification crc
WHERE crc.category_id = :category_id
  AND crc.driver_certification_id NOT IN (
      SELECT driver_certification_id
      FROM driver_certification_record
      WHERE driver_id = :driver_id AND expiration_date >= CURDATE()
  );
-- If missing_certs = 0 -> eligible, only then does PHP allow the INSERT into vehicle_driver_assignment

INSERT INTO vehicle_driver_assignment (driver_id, VIN, start_date)
VALUES (:driver_id, :VIN, CURDATE());


-- ==========================================================
-- B. WORKSHOP STAFF
-- ==========================================================

-- B1. Dashboard: 4 stat cards (workshop-staff.html)
SELECT COUNT(*) AS jobs_this_month
FROM maintenance_job
WHERE DATE_FORMAT(date_opened, '%Y-%m') = :month;

SELECT ROUND(AVG(downtime_hours), 1) AS avg_downtime
FROM maintenance_job
WHERE downtime_hours IS NOT NULL
  AND DATE_FORMAT(date_opened, '%Y-%m') = :month;

SELECT COUNT(*) AS unresolved_alerts
FROM predictive_alert
WHERE action_taken NOT IN ('RESOLVED', 'ACKNOWLEDGED') OR action_taken IS NULL;

SELECT SUM(total_cost) AS total_cost
FROM maintenance_job
WHERE DATE_FORMAT(date_opened, '%Y-%m') = :month;


-- B2. Maintenance Jobs table, with filters (workshop-staff.html)
SELECT
    mj.job_id, v.VIN, v.registration_number,
    sb.bay_name, dp.depot_name,
    mj.date_opened, mj.date_closed, mj.downtime_hours,
    mj.job_status, mj.total_cost
FROM maintenance_job mj
JOIN vehicle v      ON v.VIN = mj.VIN
JOIN service_bay sb ON sb.service_bay_id = mj.service_bay_id
JOIN depot dp        ON dp.depot_id = sb.depot_id
WHERE (:depot_id IS NULL OR dp.depot_id = :depot_id)
  AND (:status   IS NULL OR mj.job_status = :status)
  AND (:date_from IS NULL OR mj.date_opened >= :date_from)
  AND (:date_to   IS NULL OR mj.date_opened <= :date_to)
ORDER BY mj.date_opened DESC;


-- B3. Single job detail (job-detail.html)
-- Job information
SELECT
    mj.job_id, mj.date_opened, mj.date_closed, mj.downtime_hours, mj.job_status, mj.total_cost,
    v.VIN, v.registration_number, v.model, v.current_odometer,
    vc.category_name,
    sb.bay_name, dp.depot_name
FROM maintenance_job mj
JOIN vehicle v      ON v.VIN = mj.VIN
JOIN vehicle_category vc ON vc.category_id = v.category_id
JOIN service_bay sb ON sb.service_bay_id = mj.service_bay_id
JOIN depot dp        ON dp.depot_id = sb.depot_id
WHERE mj.job_id = :job_id;

-- Alert linked to this job (if any)
SELECT at2.alert_name, pa.timestamp, pa.action_taken
FROM predictive_alert pa
JOIN alert_type at2 ON at2.alert_type_id = pa.alert_type_id
WHERE pa.job_id = :job_id;

-- List of activities within the job
SELECT
    ma.activity_id, at.activity_type_name,
    ma.diagnostic_result, ma.repeat_fault, ma.warranty_applicable
FROM maintenance_activity ma
JOIN activity_type at ON at.activity_type_id = ma.activity_type_id
WHERE ma.job_id = :job_id
ORDER BY ma.activity_id;

-- Mechanic + labour hours for each activity
SELECT
    mact.activity_id, me.mechanic_id, me.full_name, mact.labour_hours
FROM mechanic_activity mact
JOIN mechanic me ON me.mechanic_id = mact.mechanic_id
WHERE mact.job_id = :job_id
ORDER BY mact.activity_id;

-- Parts used for each activity
SELECT
    ap.activity_id, p.part_number, p.description,
    ap.quantity_used, ap.unit_cost_at_use
FROM activity_part ap
JOIN part p ON p.part_id = ap.part_id
WHERE ap.job_id = :job_id
ORDER BY ap.activity_id;


-- B4. Update job status (submitting the form on job-detail.html)
UPDATE maintenance_job
SET job_status = :job_status,
    date_closed = CASE WHEN :job_status = 'DONE' THEN NOW() ELSE date_closed END,
    downtime_hours = CASE WHEN :job_status = 'DONE'
                          THEN TIMESTAMPDIFF(HOUR, date_opened, NOW())
                          ELSE downtime_hours END
WHERE job_id = :job_id;


-- B5. Predictive Alerts page
SELECT
    pa.VIN, v.registration_number,
    at2.alert_name, pa.timestamp, pa.action_taken, pa.job_id
FROM predictive_alert pa
JOIN vehicle v      ON v.VIN = pa.VIN
JOIN alert_type at2 ON at2.alert_type_id = pa.alert_type_id
WHERE (:action_taken IS NULL OR pa.action_taken = :action_taken)
ORDER BY pa.timestamp DESC;

-- Update how an alert was actioned
UPDATE predictive_alert
SET action_taken = :action_taken
WHERE VIN = :VIN AND timestamp = :timestamp;


-- B6. Mechanics & Activities page — check a mechanic holds the required certification before assigning
SELECT m.mechanic_id, m.full_name
FROM mechanic m
WHERE m.employment_status = 'ACTIVE'
  AND EXISTS (
      SELECT 1
      FROM mechanic_certification_record mcr
      JOIN activity_required_certification arc
        ON arc.mechanic_certification_id = mcr.mechanic_certification_id
      WHERE mcr.mechanic_id = m.mechanic_id
        AND arc.activity_type_id = :activity_type_id
        AND mcr.expiration_date >= CURDATE()
  );

INSERT INTO mechanic_activity (mechanic_id, job_id, activity_id, labour_hours)
VALUES (:mechanic_id, :job_id, :activity_id, :labour_hours);


-- B7. Parts & Suppliers page — parts below reorder threshold
SELECT
    p.part_id, p.part_number, p.description, p.stock_quantity,
    s1.supplier_name AS primary_supplier, p.primary_unit_cost,
    s2.supplier_name AS backup_supplier, p.backup_unit_cost
FROM part p
JOIN supplier s1 ON s1.supplier_id = p.primary_supplier_id
LEFT JOIN supplier s2 ON s2.supplier_id = p.backup_supplier_id
WHERE p.stock_quantity < :reorder_threshold
ORDER BY p.stock_quantity ASC;

-- Orders still awaiting delivery
SELECT
    po.order_id, s.supplier_name, po.order_date, po.expected_delivery_date, po.order_status
FROM part_order po
JOIN supplier s ON s.supplier_id = po.supplier_id
WHERE po.order_status != 'DELIVERED'
ORDER BY po.expected_delivery_date ASC;


-- B8. Warranty Claims page
SELECT
    wc.claim_id, wc.claim_type, wc.claim_date, wc.claim_status,
    m.manufacturer_name, s.supplier_name,
    wcp.job_id, wcp.activity_id, p.part_number
FROM warranty_claim wc
LEFT JOIN manufacturer m ON m.manufacturer_id = wc.manufacturer_id
LEFT JOIN supplier s     ON s.supplier_id = wc.supplier_id
JOIN warranty_claim_part wcp ON wcp.claim_id = wc.claim_id
JOIN part p ON p.part_id = wcp.part_id
ORDER BY wc.claim_date DESC;
