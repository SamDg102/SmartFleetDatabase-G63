<?php
session_start();
include 'includes/conndb.php';

$jobId = $_GET['job_id'] ?? 1021;

// Update Job Status
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $newStatus = $_POST['job_status'];
    
    if ($newStatus === 'DONE') {
        $stmt = $conn->prepare("UPDATE maintenance_job SET job_status = ?, date_closed = NOW(), downtime_hours = TIMESTAMPDIFF(HOUR, date_opened, NOW()) WHERE job_id = ?");
        $stmt->bind_param("si", $newStatus, $jobId);
    } else {
        $stmt = $conn->prepare("UPDATE maintenance_job SET job_status = ? WHERE job_id = ?");
        $stmt->bind_param("si", $newStatus, $jobId);
    }
    $stmt->execute();
}

// B3. Single Job Query
$sqlJob = "SELECT mj.job_id, mj.date_opened, mj.date_closed, mj.downtime_hours, mj.job_status, mj.total_cost,
                  v.VIN, v.registration_number, v.model, v.current_odometer, vc.category_name, sb.bay_name, dp.depot_name
           FROM maintenance_job mj
           JOIN vehicle v       ON v.VIN = mj.VIN
           JOIN vehicle_category vc ON vc.category_id = v.category_id
           JOIN service_bay sb ON sb.service_bay_id = mj.service_bay_id
           JOIN depot dp        ON dp.depot_id = sb.depot_id
           WHERE mj.job_id = '$jobId'";
$job = $conn->query($sqlJob)->fetch_assoc();

if (!$job) die("Job not found!");

// Linked Alert Query
$sqlAlert = "SELECT at2.alert_name FROM predictive_alert pa JOIN alert_type at2 ON at2.alert_type_id = pa.alert_type_id WHERE pa.job_id = '$jobId'";
$alert = $conn->query($sqlAlert)->fetch_assoc();

// Activities Query
$sqlAct = "SELECT ma.activity_id, at.activity_type_name, ma.diagnostic_result, ma.repeat_fault, ma.warranty_applicable
           FROM maintenance_activity ma
           JOIN activity_type at ON at.activity_type_id = ma.activity_type_id
           WHERE ma.job_id = '$jobId'";
$activities = $conn->query($sqlAct);

// Parts Query
$sqlParts = "SELECT ap.activity_id, p.part_number, p.description, ap.quantity_used, ap.unit_cost_at_use
             FROM activity_part ap JOIN part p ON p.part_id = ap.part_id WHERE ap.job_id = '$jobId'";
$parts = $conn->query($sqlParts);

// Mechanics Query
$sqlMechs = "SELECT mact.activity_id, me.mechanic_id, me.full_name, mact.labour_hours
             FROM mechanic_activity mact JOIN mechanic me ON me.mechanic_id = mact.mechanic_id WHERE mact.job_id = '$jobId'";
$mechanics = $conn->query($sqlMechs);
?>
<!DOCTYPE html>
<html lang="en">
    <head>
        <meta charset="UTF-8">
        <meta name="viewport" content="width=device-width, initial-scale=1.0">
        <title>G63 Workshop Staff - Job Detail</title>
        <link rel="preconnect" href="https://fonts.googleapis.com">
        <link rel="stylesheet" href="https://fonts.googleapis.com/css2?family=Ubuntu:wght@400;500;700&display=swap">
        <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.1/css/all.min.css">
        <link rel="stylesheet" href="styles/navbar.css">
        <link rel="stylesheet" href="styles/staff.css">
    </head>

    <body>
        <?php $currentPage = 'workshop-staff'; include 'includes/navbar.php'; ?>

        <main class="scorepage">
            <a href="workshop-staff.php" class="back-link"><i class="fa-solid fa-arrow-left"></i>Back to Maintenance Jobs</a>

            <div class="topbar">
                <div>
                    <h1>Job M<?= $job['job_id'] ?></h1>
                    <p><?= htmlspecialchars($job['registration_number']) ?> &middot; <?= htmlspecialchars($job['category_name']) ?> &middot; <?= htmlspecialchars($job['bay_name']) ?>, <?= htmlspecialchars($job['depot_name']) ?></p>
                </div>
                <span class="badge badge-<?= strtolower(str_replace(' ', '', $job['job_status'])) ?>"><?= strtoupper($job['job_status']) ?></span>
            </div>
            <hr>

            <div class="detail-grid">
                <div>
                    <div class="card">
                        <h3>Job Summary</h3>
                        <dl class="info-list">
                            <div><dt>Opened</dt><dd><?= $job['date_opened'] ?></dd></div>
                            <div><dt>Closed</dt><dd><?= $job['date_closed'] ?? 'Pending' ?></dd></div>
                            <div><dt>Downtime</dt><dd><?= $job['downtime_hours'] ? $job['downtime_hours'].' hours' : 'N/A' ?></dd></div>
                            <div><dt>Total cost</dt><dd><?= number_format($job['total_cost'], 0, ',', '.') ?>₫</dd></div>
                            <div><dt>Linked alert</dt><dd><?= htmlspecialchars($alert['alert_name'] ?? 'None') ?></dd></div>
                            <div><dt>Odometer</dt><dd><?= number_format($job['current_odometer'], 0, ',', '.') ?> km</dd></div>
                        </dl>
                    </div>

                    <div class="card">
                        <h3>Activities</h3>
                        <?php if ($activities && $activities->num_rows > 0): ?>
                            <?php while ($act = $activities->fetch_assoc()): ?>
                                <div class="activity-row">
                                    <span>
                                        <div class="activity-name"><?= $act['activity_id'] ?>. <?= htmlspecialchars($act['activity_type_name']) ?></div>
                                        <div class="activity-meta">
                                            <?= htmlspecialchars($act['diagnostic_result']) ?> &middot; 
                                            <?= $act['repeat_fault'] ? 'repeat fault' : 'no repeat fault' ?> &middot; 
                                            <?= $act['warranty_applicable'] ? 'warranty' : 'no warranty' ?>
                                        </div>
                                    </span>
                                    <span class="badge badge-completed">DONE</span>
                                </div>
                            <?php endwhile; ?>
                        <?php endif; ?>
                    </div>

                    <div class="card">
                        <h3>Parts Used</h3>
                        <?php if ($parts && $parts->num_rows > 0): ?>
                            <?php while ($p = $parts->fetch_assoc()): ?>
                                <div class="activity-row">
                                    <span class="activity-name"><?= htmlspecialchars($p['description']) ?> (<?= htmlspecialchars($p['part_number']) ?>)</span>
                                    <span class="activity-meta"><?= $p['quantity_used'] ?> &times; <?= number_format($p['unit_cost_at_use'], 0, ',', '.') ?>₫</span>
                                </div>
                            <?php endwhile; ?>
                        <?php endif; ?>
                    </div>
                </div>

                <div>
                    <div class="card">
                        <h3>Mechanics Assigned</h3>
                        <?php if ($mechanics && $mechanics->num_rows > 0): ?>
                            <?php while ($m = $mechanics->fetch_assoc()): ?>
                                <div class="activity-row">
                                    <span>
                                        <div class="activity-name"><?= htmlspecialchars($m['full_name']) ?> (ME-<?= $m['mechanic_id'] ?>)</div>
                                        <div class="activity-meta">Activity <?= $m['activity_id'] ?> &middot; <?= $m['labour_hours'] ?>h</div>
                                    </span>
                                </div>
                            <?php endwhile; ?>
                        <?php endif; ?>
                    </div>

                    <div class="card">
                        <h3>Update Job</h3>
                        <form method="POST">
                            <div class="form-group">
                                <label for="jobstatus">Job status</label>
                                <select id="jobstatus" name="job_status">
                                    <option value="Not Started" <?= $job['job_status'] == 'Not Started' ? 'selected' : '' ?>>Not Started</option>
                                    <option value="In Progress" <?= $job['job_status'] == 'In Progress' ? 'selected' : '' ?>>In Progress</option>
                                    <option value="DONE" <?= $job['job_status'] == 'DONE' ? 'selected' : '' ?>>Done</option>
                                </select>
                            </div>
                            <div class="form-actions">
                                <button type="submit" class="btn-primary"><i class="fa-solid fa-check"></i>Save</button>
                                <a href="workshop-staff.php" class="btn-secondary">Cancel</a>
                            </div>
                        </form>
                    </div>
                </div>
            </div>
        </main>
    </body>
</html>