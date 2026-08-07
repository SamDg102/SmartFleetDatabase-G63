<?php
session_start();
include '../db.php';
include '../function.php';

checkin('fleet safety staff');
$currentPage = 'workshop-staff';

$month = date('Y-m');

// B1. Stat Cards
$statJobs = $conn->query("SELECT COUNT(*) FROM maintenance_job WHERE DATE_FORMAT(date_opened, '%Y-%m') = '$month'")->fetch_row()[0] ?? 0;
$statDowntime = $conn->query("SELECT ROUND(AVG(downtime_hours), 1) FROM maintenance_job WHERE downtime_hours IS NOT NULL AND DATE_FORMAT(date_opened, '%Y-%m') = '$month'")->fetch_row()[0] ?? 0;
$statAlerts = $conn->query("SELECT COUNT(*) FROM predictive_alert WHERE action_taken NOT IN ('RESOLVED', 'ACKNOWLEDGED') OR action_taken IS NULL")->fetch_row()[0] ?? 0;
$statCost = $conn->query("SELECT SUM(total_cost) FROM maintenance_job WHERE DATE_FORMAT(date_opened, '%Y-%m') = '$month'")->fetch_row()[0] ?? 0;

// B2. Filters
$depot_id = $_GET['depot_id'] ?? null;
$status = $_GET['status'] ?? null;
$date_from = $_GET['date_from'] ?? null;
$date_to = $_GET['date_to'] ?? null;

$where = ["1=1"];
if (!empty($depot_id)) $where[] = "dp.depot_id = '" . $conn->real_escape_string($depot_id) . "'";
if (!empty($status)) $where[] = "mj.job_status = '" . $conn->real_escape_string($status) . "'";
if (!empty($date_from)) $where[] = "mj.date_opened >= '" . $conn->real_escape_string($date_from) . "'";
if (!empty($date_to)) $where[] = "mj.date_opened <= '" . $conn->real_escape_string($date_to) . " 23:59:59'";
$whereSql = implode(' AND ', $where);

$sqlJobs = "SELECT mj.job_id, v.VIN, v.registration_number, sb.bay_name, dp.depot_name,
                   mj.date_opened, mj.date_closed, mj.downtime_hours, mj.job_status, mj.total_cost
            FROM maintenance_job mj
            JOIN vehicle v ON v.VIN = mj.VIN
            JOIN service_bay sb ON sb.service_bay_id = mj.service_bay_id
            JOIN depot dp ON dp.depot_id = sb.depot_id
            WHERE $whereSql
            ORDER BY mj.date_opened DESC";
$resultJobs = $conn->query($sqlJobs);
$depots = $conn->query("SELECT depot_id, depot_name FROM depot ORDER BY depot_name ASC");
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>G63 Workshop Staff Dashboard</title>
    <link rel="stylesheet" href="styles/navbar.css">
    <link rel="stylesheet" href="styles/staff.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.1/css/all.min.css">
</head>
<body>
    <?php include 'includes/navbar.php'; ?>
    <main class="scorepage">
        <div class="topbar">
            <div>
                <h1>Maintenance Jobs</h1>
                <p>Lịch sử công việc bảo trì tại các xưởng — Tháng <?= date('m/Y') ?></p>
            </div>
        </div>
        <hr>

        <section class="stat-grid">
            <div class="stat-card">
                <div class="stat-icon"><i class="fa-solid fa-clipboard-list"></i></div>
                <div class="stat-value"><?= $statJobs ?></div>
                <div class="stat-label">Jobs This Month</div>
            </div>
            <div class="stat-card warn">
                <div class="stat-icon"><i class="fa-solid fa-hourglass-half"></i></div>
                <div class="stat-value"><?= $statDowntime ?>h</div>
                <div class="stat-label">Avg. Downtime / Job</div>
            </div>
            <div class="stat-card alert">
                <div class="stat-icon"><i class="fa-solid fa-bell"></i></div>
                <div class="stat-value"><?= $statAlerts ?></div>
                <div class="stat-label">Unresolved Predictive Alerts</div>
            </div>
            <div class="stat-card">
                <div class="stat-icon"><i class="fa-solid fa-sack-dollar"></i></div>
                <div class="stat-value"><?= number_format($statCost, 0, ',', '.') ?>₫</div>
                <div class="stat-label">Total Maintenance Cost</div>
            </div>
        </section>

        <form method="GET" class="filter-bar">
            <select name="depot_id" onchange="this.form.submit()">
                <option value="">All Depots</option>
                <?php while ($d = $depots->fetch_assoc()): ?>
                    <option value="<?= $d['depot_id'] ?>" <?= $depot_id == $d['depot_id'] ? 'selected' : '' ?>><?= htmlspecialchars($d['depot_name']) ?></option>
                <?php endwhile; ?>
            </select>
            <select name="status" onchange="this.form.submit()">
                <option value="">All Statuses</option>
                <option value="Not Started" <?= $status == 'Not Started' ? 'selected' : '' ?>>Not Started</option>
                <option value="In Progress" <?= $status == 'In Progress' ? 'selected' : '' ?>>In Progress</option>
                <option value="DONE" <?= $status == 'DONE' ? 'selected' : '' ?>>Done</option>
            </select>
            <input type="date" name="date_from" value="<?= htmlspecialchars($date_from ?? '') ?>" onchange="this.form.submit()">
            <input type="date" name="date_to" value="<?= htmlspecialchars($date_to ?? '') ?>" onchange="this.form.submit()">
        </form>

        <div class="table-wrap">
            <table>
                <thead>
                    <tr>
                        <th>Job ID</th>
                        <th>Vehicle</th>
                        <th>Bay</th>
                        <th>Opened</th>
                        <th>Closed</th>
                        <th>Downtime</th>
                        <th>Status</th>
                        <th>Total Cost</th>
                        <th></th>
                    </tr>
                </thead>
                <tbody>
                    <?php if ($resultJobs && $resultJobs->num_rows > 0): ?>
                        <?php while ($j = $resultJobs->fetch_assoc()): ?>
                            <tr>
                                <td>M<?= $j['job_id'] ?></td>
                                <td><?= htmlspecialchars($j['registration_number']) ?> (<?= htmlspecialchars($j['VIN']) ?>)</td>
                                <td><?= htmlspecialchars($j['bay_name']) ?></td>
                                <td><?= date('Y-m-d H:i', strtotime($j['date_opened'])) ?></td>
                                <td><?= $j['date_closed'] ? date('Y-m-d H:i', strtotime($j['date_closed'])) : '—' ?></td>
                                <td><?= $j['downtime_hours'] ? $j['downtime_hours'].'h' : '—' ?></td>
                                <td><span class="badge badge-<?= strtolower(str_replace(' ', '', $j['job_status'])) ?>"><?= strtoupper($j['job_status']) ?></span></td>
                                <td><?= number_format($j['total_cost'], 0, ',', '.') ?>₫</td>
                                <td><a href="job-detail.php?job_id=<?= $j['job_id'] ?>" class="link-action">View</a></td>
                            </tr>
                        <?php endwhile; ?>
                    <?php else: ?>
                        <tr><td colspan="9" style="text-align: center;">No maintenance jobs found.</td></tr>
                    <?php endif; ?>
                </tbody>
            </table>
        </div>
    </main>
</body>
</html>