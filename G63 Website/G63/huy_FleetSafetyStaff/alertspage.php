<?php
session_start();
include '../db.php';
include '../function.php';

checkin('fleet safety staff');
$currentPage = 'workshop-alerts';

// B5 Update
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $vin = $_POST['vin'];
    $timestamp = $_POST['timestamp'];
    $action_taken = $_POST['action_taken'];

    $stmt = $conn->prepare("UPDATE predictive_alert SET action_taken = ? WHERE VIN = ? AND timestamp = ?");
    $stmt->bind_param("sss", $action_taken, $vin, $timestamp);
    $stmt->execute();
}

$action_taken_filter = $_GET['action_taken'] ?? null;
$whereSql = !empty($action_taken_filter) ? "WHERE pa.action_taken = '" . $conn->real_escape_string($action_taken_filter) . "'" : "WHERE 1=1";

$sqlAlerts = "SELECT pa.VIN, v.registration_number, at2.alert_name, pa.timestamp, pa.action_taken, pa.job_id
              FROM predictive_alert pa
              JOIN vehicle v ON v.VIN = pa.VIN
              JOIN alert_type at2 ON at2.alert_type_id = pa.alert_type_id
              $whereSql
              ORDER BY pa.timestamp DESC";
$resultAlerts = $conn->query($sqlAlerts);
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>Predictive Alerts</title>
    <link rel="stylesheet" href="styles/navbar.css">
    <link rel="stylesheet" href="styles/staff.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.1/css/all.min.css">
</head>
<body>
    <?php include 'includes/navbar.php'; ?>
    <main class="scorepage">
        <div class="topbar">
            <div>
                <h1>Predictive Alerts</h1>
                <p>List of predictive alerts from Telematics</p>
            </div>
            <form method="GET" class="filter-bar">
                <select name="action_taken" onchange="this.form.submit()">
                    <option value="">All Actions</option>
                    <option value="ACKNOWLEDGED" <?= $action_taken_filter == 'ACKNOWLEDGED' ? 'selected' : '' ?>>Acknowledged</option>
                    <option value="RESOLVED" <?= $action_taken_filter == 'RESOLVED' ? 'selected' : '' ?>>Resolved</option>
                    <option value="IGNORED" <?= $action_taken_filter == 'IGNORED' ? 'selected' : '' ?>>Ignored</option>
                </select>
            </form>
        </div>
        <hr>

        <div class="table-wrap">
            <table>
                <thead>
                    <tr>
                        <th>Vehicle</th>
                        <th>Alert Name</th>
                        <th>Timestamp</th>
                        <th>Linked Job</th>
                        <th>Action Taken</th>
                        <th>Update Action</th>
                    </tr>
                </thead>
                <tbody>
                    <?php if ($resultAlerts && $resultAlerts->num_rows > 0): ?>
                        <?php while ($r = $resultAlerts->fetch_assoc()): ?>
                            <tr>
                                <td><?= htmlspecialchars($r['registration_number']) ?> (<?= $r['VIN'] ?>)</td>
                                <td><?= htmlspecialchars($r['alert_name']) ?></td>
                                <td><?= $r['timestamp'] ?></td>
                                <td><?= $r['job_id'] ? 'M'.$r['job_id'] : 'None' ?></td>
                                <td><span class="badge badge-completed"><?= $r['action_taken'] ?? 'PENDING' ?></span></td>
                                <td>
                                    <form method="POST" style="display:flex; gap:5px;">
                                        <input type="hidden" name="vin" value="<?= $r['VIN'] ?>">
                                        <input type="hidden" name="timestamp" value="<?= $r['timestamp'] ?>">
                                        <select name="action_taken" style="padding:4px;">
                                            <option value="ACKNOWLEDGED">ACKNOWLEDGED</option>
                                            <option value="RESOLVED">RESOLVED</option>
                                            <option value="IGNORED">IGNORED</option>
                                        </select>
                                        <button type="submit" class="btn-primary" style="padding:4px 8px;">Save</button>
                                    </form>
                                </td>
                            </tr>
                        <?php endwhile; ?>
                    <?php else: ?>
                        <tr><td colspan="6" style="text-align: center;">No predictive alerts found.</td></tr>
                    <?php endif; ?>
                </tbody>
            </table>
        </div>
    </main>
</body>
</html>