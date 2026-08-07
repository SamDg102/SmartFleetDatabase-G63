<?php
session_start();
include '../db.php';
include '../function.php';

checkin('fleet safety staff');
$currentPage = 'safety-staff';

if (!isset($_GET['event_id']) || empty($_GET['event_id'])) {
    header("Location: safety-staff.php");
    exit();
}

$eventId = (int)$_GET['event_id'];

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $review_status = $_POST['review_status'];
    $comments = $_POST['comments'];
    $driver_id = (int)$_POST['driver_id'];
    $severity = $_POST['severity'];

    $conn->begin_transaction();
    try {
        $stmt = $conn->prepare("UPDATE safety_event SET review_status = ?, reviewer_comments = ? WHERE event_id = ?");
        $stmt->bind_param("ssi", $review_status, $comments, $eventId);
        $stmt->execute();

        if ($review_status === 'COMPLETED' && $severity === 'CRITICAL') {
            $stmtRelease = $conn->prepare("UPDATE driver SET safety_hold = FALSE WHERE driver_id = ?");
            $stmtRelease->bind_param("i", $driver_id);
            $stmtRelease->execute();
        }
        $conn->commit();
    } catch (Exception $e) {
        $conn->rollback();
    }
}

$sqlDetail = "SELECT se.event_id, se.timestamp, se.review_status, se.reviewer_comments,
                     d.driver_id, d.full_name AS driver_name, d.safety_hold,
                     v.VIN, vc.category_name, dp.depot_name, et.event_type_name,
                     sl.level_name AS severity, sl.points_deducted
              FROM safety_event se
              JOIN driver d ON d.driver_id = se.driver_id
              JOIN vehicle v ON v.VIN = se.VIN
              JOIN vehicle_category vc ON vc.category_id = v.category_id
              JOIN depot dp ON dp.depot_id = se.depot_id
              JOIN event_type et ON et.event_type_id = se.event_type_id
              JOIN severity_level sl ON sl.severity_id = se.severity_id
              WHERE se.event_id = '$eventId'";
$event = $conn->query($sqlDetail)->fetch_assoc();

if (!$event) {
    echo "<script>alert('Event not found!'); window.location.href='safety-staff.php';</script>";
    exit();
}

$scoreMonthStr = date('Y-m', strtotime($event['timestamp']));

$sqlScore = "SELECT final_score, action_required 
             FROM monthly_safety_score 
             WHERE driver_id = '{$event['driver_id']}' 
               AND DATE_FORMAT(score_month, '%Y-%m') = '$scoreMonthStr'";
$driverScore = $conn->query($sqlScore)->fetch_assoc();

$sqlOther = "SELECT se.event_id, et.event_type_name, sl.level_name AS severity
             FROM safety_event se
             JOIN event_type et ON et.event_type_id = se.event_type_id
             JOIN severity_level sl ON sl.severity_id = se.severity_id
             WHERE se.driver_id = '{$event['driver_id']}' 
               AND se.event_id != '$eventId' 
               AND DATE_FORMAT(se.timestamp, '%Y-%m') = '$scoreMonthStr'";
$otherEvents = $conn->query($sqlOther);
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>Review Event E<?= str_pad($event['event_id'], 3, '0', STR_PAD_LEFT) ?></title>
    <link rel="stylesheet" href="styles/navbar.css">
    <link rel="stylesheet" href="styles/staff.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.1/css/all.min.css">
</head>
<body>
    <?php include 'includes/navbar.php'; ?>
    <main class="scorepage">
        <a href="safety-staff.php" class="back-link"><i class="fa-solid fa-arrow-left"></i>Back to Safety Event Log</a>
        <div class="topbar">
            <div>
                <h1>Event E<?= str_pad($event['event_id'], 3, '0', STR_PAD_LEFT) ?> — <?= htmlspecialchars($event['event_type_name']) ?></h1>
                <p><?= htmlspecialchars($event['depot_name']) ?> &middot; <?= htmlspecialchars($event['VIN']) ?> &middot; <?= $event['timestamp'] ?></p>
            </div>
            <span class="badge badge-<?= strtolower($event['severity']) ?>"><?= strtoupper($event['severity']) ?></span>
        </div>
        <hr>

        <div class="detail-grid">
            <div>
                <div class="card">
                    <h3>Event Details</h3>
                    <dl class="info-list">
                        <div><dt>Driver</dt><dd><?= htmlspecialchars($event['driver_name']) ?> (D-<?= $event['driver_id'] ?>)</dd></div>
                        <div><dt>Vehicle</dt><dd><?= htmlspecialchars($event['VIN']) ?> (<?= htmlspecialchars($event['category_name']) ?>)</dd></div>
                        <div><dt>Depot</dt><dd><?= htmlspecialchars($event['depot_name']) ?></dd></div>
                        <div><dt>Timestamp</dt><dd><?= $event['timestamp'] ?></dd></div>
                        <div><dt>Event Type</dt><dd><?= htmlspecialchars($event['event_type_name']) ?></dd></div>
                        <div><dt>Severity</dt><dd><?= ucfirst(strtolower($event['severity'])) ?> (-<?= $event['points_deducted'] ?> pts)</dd></div>
                    </dl>
                </div>

                <div class="card">
                    <h3>Review &amp; Decision</h3>
                    <form method="POST">
                        <input type="hidden" name="driver_id" value="<?= $event['driver_id'] ?>">
                        <input type="hidden" name="severity" value="<?= $event['severity'] ?>">
                        <div class="form-group">
                            <label>Review status</label>
                            <select name="review_status">
                                <option value="PENDING" <?= $event['review_status'] == 'PENDING' ? 'selected' : '' ?>>Pending</option>
                                <option value="COMPLETED" <?= $event['review_status'] == 'COMPLETED' ? 'selected' : '' ?>>Completed</option>
                            </select>
                        </div>
                        <div class="form-group">
                            <label>Reviewer comments</label>
                            <textarea name="comments"><?= htmlspecialchars($event['reviewer_comments'] ?? '') ?></textarea>
                        </div>
                        <div class="form-actions">
                            <button type="submit" class="btn-primary"><i class="fa-solid fa-check"></i>Save Review</button>
                            <a href="safety-staff.php" class="btn-secondary">Cancel</a>
                        </div>
                    </form>
                </div>
            </div>

            <div>
                <div class="card">
                    <h3>Driver Status</h3>
                    <dl class="info-list" style="grid-template-columns: 1fr;">
                        <div><dt>Safety hold</dt><dd><span class="badge badge-<?= $event['safety_hold'] ? 'critical' : 'completed' ?>"><?= $event['safety_hold'] ? 'ACTIVE' : 'INACTIVE' ?></span></dd></div>
                        <div><dt><?= date('M Y', strtotime($event['timestamp'])) ?> score</dt><dd><?= $driverScore['final_score'] ?? 'N/A' ?> / 100</dd></div>
                        <div><dt>Action required</dt><dd><?= htmlspecialchars($driverScore['action_required'] ?? 'None') ?></dd></div>
                    </dl>
                </div>

                <div class="card">
                    <h3>Other Events (<?= date('M Y', strtotime($event['timestamp'])) ?>)</h3>
                    <?php if ($otherEvents && $otherEvents->num_rows > 0): ?>
                        <?php while ($oe = $otherEvents->fetch_assoc()): ?>
                            <div class="activity-row">
                                <span class="activity-name">E<?= str_pad($oe['event_id'], 3, '0', STR_PAD_LEFT) ?> &middot; <?= htmlspecialchars($oe['event_type_name']) ?></span>
                                <span class="badge badge-<?= strtolower($oe['severity']) ?>"><?= strtoupper($oe['severity']) ?></span>
                            </div>
                        <?php endwhile; ?>
                    <?php else: ?>
                        <p style="font-size: 13px; color: #666;">No other events in this month.</p>
                    <?php endif; ?>
                </div>
            </div>
        </div>
    </main>
</body>
</html>