<?php
session_start();
include '../db.php';
include '../function.php';

checkin('fleet safety staff');
$currentPage = 'safety-coaching';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $driver_id = $_POST['driver_id'];
    $trigger_event_id = $_POST['trigger_event_id'];
    $completion_date = $_POST['completion_date'];
    $outcome = $_POST['outcome'];

    $stmt = $conn->prepare("UPDATE coaching_record SET completion_date = ?, outcome = ?, status = 'COMPLETED' WHERE driver_id = ? AND trigger_event_id = ?");
    $stmt->bind_param("ssii", $completion_date, $outcome, $driver_id, $trigger_event_id);
    $stmt->execute();
}

$status = $_GET['status'] ?? null;
$whereSql = !empty($status) ? "WHERE cr.status = '" . $conn->real_escape_string($status) . "'" : "WHERE 1=1";

$sqlCoaching = "SELECT cr.driver_id, d.full_name, cr.trigger_event_id, cr.score_month,
                       cr.coaching_type, cr.assigned_date, cr.completion_date, cr.outcome, cr.status
                FROM coaching_record cr
                JOIN driver d ON d.driver_id = cr.driver_id
                $whereSql
                ORDER BY cr.assigned_date DESC";
$resultCoaching = $conn->query($sqlCoaching);
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>Coaching & Retraining</title>
    <link rel="stylesheet" href="styles/navbar.css">
    <link rel="stylesheet" href="styles/staff.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.1/css/all.min.css">
</head>
<body>
    <?php include 'includes/navbar.php'; ?>
    <main class="scorepage">
        <div class="topbar">
            <div>
                <h1>Coaching & Retraining</h1>
                <p>List of coaching and retraining records for drivers</p>
            </div>
            <form method="GET" class="filter-bar">
                <select name="status" onchange="this.form.submit()">
                    <option value="">All Statuses</option>
                    <option value="PENDING" <?= $status == 'PENDING' ? 'selected' : '' ?>>Pending</option>
                    <option value="COMPLETED" <?= $status == 'COMPLETED' ? 'selected' : '' ?>>Completed</option>
                </select>
            </form>
        </div>
        <hr>
        <div class="table-wrap">
            <table>
                <thead>
                    <tr>
                        <th>Driver</th>
                        <th>Trigger Event</th>
                        <th>Coaching Type</th>
                        <th>Assigned Date</th>
                        <th>Completion Date</th>
                        <th>Outcome</th>
                        <th>Status</th>
                        <th>Action</th>
                    </tr>
                </thead>
                <tbody>
                    <?php if ($resultCoaching && $resultCoaching->num_rows > 0): ?>
                        <?php while ($r = $resultCoaching->fetch_assoc()): ?>
                            <tr>
                                <td><?= htmlspecialchars($r['full_name']) ?> (D-<?= $r['driver_id'] ?>)</td>
                                <td>E<?= str_pad($r['trigger_event_id'], 3, '0', STR_PAD_LEFT) ?></td>
                                <td><?= htmlspecialchars($r['coaching_type']) ?></td>
                                <td><?= $r['assigned_date'] ?></td>
                                <td><?= $r['completion_date'] ?? '—' ?></td>
                                <td><?= htmlspecialchars($r['outcome'] ?? 'Pending') ?></td>
                                <td><span class="badge badge-<?= strtolower($r['status']) ?>"><?= $r['status'] ?></span></td>
                                <td>
                                    <?php if ($r['status'] === 'PENDING'): ?>
                                        <button class="btn-primary" onclick="openUpdateModal(<?= $r['driver_id'] ?>, <?= $r['trigger_event_id'] ?>)">Update</button>
                                    <?php endif; ?>
                                </td>
                            </tr>
                        <?php endwhile; ?>
                    <?php else: ?>
                        <tr><td colspan="8" style="text-align: center;">No coaching records found.</td></tr>
                    <?php endif; ?>
                </tbody>
            </table>
        </div>
    </main>

    <div id="modalUpdate" class="card" style="display:none; position:fixed; top:20%; left:35%; width:400px; z-index:9999; box-shadow:0 0 20px rgba(0,0,0,0.5);">
        <h3>Update Coaching Outcome</h3>
        <form method="POST">
            <input type="hidden" name="driver_id" id="modal_driver_id">
            <input type="hidden" name="trigger_event_id" id="modal_event_id">
            <div class="form-group">
                <label>Completion Date</label>
                <input type="date" name="completion_date" required value="<?= date('Y-m-d') ?>">
            </div>
            <div class="form-group">
                <label>Outcome</label>
                <textarea name="outcome" required placeholder="Passed retraining assessment"></textarea>
            </div>
            <div class="form-actions">
                <button type="submit" class="btn-primary">Save Outcome</button>
                <button type="button" class="btn-secondary" onclick="document.getElementById('modalUpdate').style.display='none'">Cancel</button>
            </div>
        </form>
    </div>

    <script>
    function openUpdateModal(driverId, eventId) {
        document.getElementById('modal_driver_id').value = driverId;
        document.getElementById('modal_event_id').value = eventId;
        document.getElementById('modalUpdate').style.display = 'block';
    }
    </script>
</body>
</html>