<?php
session_start();
include  '../db.php';

// Xử lý bộ lọc
$depot_id = $_GET['depot_id'] ?? '';
$severity = $_GET['severity'] ?? '';
$review_status = $_GET['review_status'] ?? '';
$date_from = $_GET['date_from'] ?? '';
$date_to = $_GET['date_to'] ?? '';

// Build Query lọc dữ liệu
$where = ["1=1"];
if (!empty($depot_id)) $where[] = "dp.depot_id = '" . $conn->real_escape_string($depot_id) . "'";
if (!empty($severity)) $where[] = "sl.level_name = '" . $conn->real_escape_string($severity) . "'";
if (!empty($review_status)) $where[] = "se.review_status = '" . $conn->real_escape_string($review_status) . "'";
if (!empty($date_from)) $where[] = "se.timestamp >= '" . $conn->real_escape_string($date_from) . "'";
if (!empty($date_to)) $where[] = "se.timestamp <= '" . $conn->real_escape_string($date_to) . " 23:59:59'";

$whereSql = implode(' AND ', $where);

// Lấy danh sách sự kiện
$sqlEvents = "SELECT 
                se.event_id, se.timestamp, d.full_name AS driver_name, d.driver_id,
                se.VIN, dp.depot_name, et.event_type_name, sl.level_name AS severity, se.review_status
            FROM safety_event se
            JOIN driver d           ON d.driver_id = se.driver_id
            JOIN depot dp           ON dp.depot_id = se.depot_id
            JOIN event_type et      ON et.event_type_id = se.event_type_id
            JOIN severity_level sl  ON sl.severity_id = se.severity_id
            WHERE $whereSql
            ORDER BY se.timestamp DESC";
$resultEvents = $conn->query($sqlEvents);

// Lấy chỉ số Thống kê Dashboard
$currentMonth = date('Y-m');
$statEvents = $conn->query("SELECT COUNT(*) AS total FROM safety_event WHERE DATE_FORMAT(timestamp, '%Y-%m') = '$currentMonth'")->fetch_assoc()['total'] ?? 0;
$statPending = $conn->query("SELECT COUNT(*) AS total FROM safety_event WHERE review_status = 'PENDING'")->fetch_assoc()['total'] ?? 0;
$statHold = $conn->query("SELECT COUNT(*) AS total FROM driver WHERE safety_hold = TRUE")->fetch_assoc()['total'] ?? 0;
$statCoaching = $conn->query("SELECT COUNT(*) AS total FROM coaching_record WHERE status = 'PENDING'")->fetch_assoc()['total'] ?? 0;

// Lấy danh sách Depot cho Dropdown Filter
$depots = $conn->query("SELECT depot_id, depot_name FROM depot ORDER BY depot_name ASC");
?>
<!DOCTYPE html>
<html lang="en">
    <head>
        <meta charset="UTF-8">
        <meta name="viewport" content="width=device-width, initial-scale=1.0">
        <title>G63 Safety Staff Dashboard</title>
        <link rel="preconnect" href="https://fonts.googleapis.com">
        <link rel="stylesheet" href="https://fonts.googleapis.com/css2?family=Ubuntu:wght@400;500;700&display=swap">
        <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.1/css/all.min.css">
    
         <link rel="stylesheet" href="styles/navbar.css">
         <link rel="stylesheet" href="styles/staff.css">
</head>

    <body>
        <?php $currentPage = 'safety-staff'; include 'includes/navbar.php'; ?>

        <main class="scorepage">
            <div class="topbar">
                <div>
                    <h1>Safety Event Log</h1>
                    <p>Month: <?= date('m/Y') ?></p>
                </div>
                <button class="btn-primary"><i class="fa-solid fa-plus"></i>Record Manual Event</button>
            </div>
            <hr>

            <section class="stat-grid">
                <div class="stat-card">
                    <div class="stat-icon"><i class="fa-solid fa-list"></i></div>
                    <div class="stat-value"><?= $statEvents ?></div>
                    <div class="stat-label">Total Events This Month</div>
                </div>
                <div class="stat-card alert">
                    <div class="stat-icon"><i class="fa-solid fa-triangle-exclamation"></i></div>
                    <div class="stat-value"><?= $statPending ?></div>
                    <div class="stat-label">Pending Review</div>
                </div>
                <div class="stat-card alert">
                    <div class="stat-icon"><i class="fa-solid fa-hand"></i></div>
                    <div class="stat-value"><?= $statHold ?></div>
                    <div class="stat-label">Drivers on Safety Hold</div>
                </div>
                <div class="stat-card warn">
                    <div class="stat-icon"><i class="fa-solid fa-chalkboard-user"></i></div>
                    <div class="stat-value"><?= $statCoaching ?></div>
                    <div class="stat-label">Coaching Pending</div>
                </div>
            </section>

            <form method="GET" class="filter-bar">
                <select name="depot_id" onchange="this.form.submit()">
                    <option value="">All Depots</option>
                    <?php while ($d = $depots->fetch_assoc()): ?>
                        <option value="<?= $d['depot_id'] ?>" <?= $depot_id == $d['depot_id'] ? 'selected' : '' ?>><?= htmlspecialchars($d['depot_name']) ?></option>
                    <?php endwhile; ?>
                </select>

                <select name="severity" onchange="this.form.submit()">
                    <option value="">All Severities</option>
                    <option value="LOW" <?= $severity == 'LOW' ? 'selected' : '' ?>>Low</option>
                    <option value="MEDIUM" <?= $severity == 'MEDIUM' ? 'selected' : '' ?>>Medium</option>
                    <option value="HIGH" <?= $severity == 'HIGH' ? 'selected' : '' ?>>High</option>
                    <option value="CRITICAL" <?= $severity == 'CRITICAL' ? 'selected' : '' ?>>Critical</option>
                </select>

                <select name="review_status" onchange="this.form.submit()">
                    <option value="">All Review Status</option>
                    <option value="PENDING" <?= $review_status == 'PENDING' ? 'selected' : '' ?>>Pending</option>
                    <option value="COMPLETED" <?= $review_status == 'COMPLETED' ? 'selected' : '' ?>>Completed</option>
                </select>

                <input type="date" name="date_from" value="<?= htmlspecialchars($date_from) ?>" onchange="this.form.submit()">
                <input type="date" name="date_to" value="<?= htmlspecialchars($date_to) ?>" onchange="this.form.submit()">
            </form>

            <div class="table-wrap">
                <table>
                    <thead>
                        <tr>
                            <th>Event ID</th>
                            <th>Timestamp</th>
                            <th>Driver</th>
                            <th>Vehicle</th>
                            <th>Depot</th>
                            <th>Event Type</th>
                            <th>Severity</th>
                            <th>Review Status</th>
                            <th></th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php if ($resultEvents && $resultEvents->num_rows > 0): ?>
                            <?php while ($row = $resultEvents->fetch_assoc()): ?>
                                <tr>
                                    <td>E<?= str_pad($row['event_id'], 3, '0', STR_PAD_LEFT) ?></td>
                                    <td><?= date('Y-m-d H:i', strtotime($row['timestamp'])) ?></td>
                                    <td><?= htmlspecialchars($row['driver_name']) ?> (D-<?= $row['driver_id'] ?>)</td>
                                    <td><?= htmlspecialchars($row['VIN']) ?></td>
                                    <td><?= htmlspecialchars($row['depot_name']) ?></td>
                                    <td><?= htmlspecialchars($row['event_type_name']) ?></td>
                                    <td><span class="badge badge-<?= strtolower($row['severity']) ?>"><?= strtoupper($row['severity']) ?></span></td>
                                    <td><span class="badge badge-<?= strtolower($row['review_status']) ?>"><?= strtoupper($row['review_status']) ?></span></td>
                                    <td>
                                        <a href="safety-event-review.php?event_id=<?= $row['event_id'] ?>" class="link-action">
                                            <?= $row['review_status'] == 'PENDING' ? 'Review' : 'View' ?>
                                        </a>
                                    </td>
                                </tr>
                            <?php endwhile; ?>
                        <?php else: ?>
                            <tr><td colspan="9" style="text-align: center;">No safety events found.</td></tr>
                        <?php endif; ?>
                    </tbody>
                </table>
            </div>
        </main>
    </body>
</html>