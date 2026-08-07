<?php
session_start();
include '../db.php';
include '../function.php';

checkin('fleet safety staff');
$currentPage = 'workshop-mechanics';

$message = '';
$error = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $mechanic_id = $_POST['mechanic_id'];
    $job_id = $_POST['job_id'];
    $activity_id = $_POST['activity_id'];
    $labour_hours = $_POST['labour_hours'];
    $activity_type_id = $_POST['activity_type_id'];

    // B6 Query check cert
    $sqlCheck = "SELECT m.mechanic_id FROM mechanic m
                 WHERE m.mechanic_id = '$mechanic_id' AND m.employment_status = 'ACTIVE'
                   AND EXISTS (
                       SELECT 1 FROM mechanic_certification_record mcr
                       JOIN activity_required_certification arc ON arc.mechanic_certification_id = mcr.mechanic_certification_id
                       WHERE mcr.mechanic_id = m.mechanic_id AND arc.activity_type_id = '$activity_type_id' AND mcr.expiration_date >= CURDATE()
                   )";
    $eligible = $conn->query($sqlCheck)->num_rows > 0;

    if ($eligible) {
        $stmtInsert = $conn->prepare("INSERT INTO mechanic_activity (mechanic_id, job_id, activity_id, labour_hours) VALUES (?, ?, ?, ?)");
        $stmtInsert->bind_param("iiid", $mechanic_id, $job_id, $activity_id, $labour_hours);
        $stmtInsert->execute();
        $message = "Mechanic successfully assigned!";
    } else {
        $error = "Mechanic does NOT hold required active certification for this activity type!";
    }
}

$mechanics = $conn->query("SELECT mechanic_id, full_name FROM mechanic WHERE employment_status = 'ACTIVE'");
// Lấy danh sách tất cả các Job kèm biển số xe để hiển thị rõ ràng hơn
$jobs = $conn->query("SELECT mj.job_id, mj.job_status, v.registration_number 
                      FROM maintenance_job mj 
                      JOIN vehicle v ON v.VIN = mj.VIN 
                      ORDER BY mj.job_id DESC");
$activityTypes = $conn->query("SELECT activity_type_id, activity_type_name FROM activity_type");
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>Mechanics & Activities</title>
    <link rel="stylesheet" href="styles/navbar.css">
    <link rel="stylesheet" href="styles/staff.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.1/css/all.min.css">
</head>
<body>
    <?php include 'includes/navbar.php'; ?>
    <main class="scorepage">
        <div class="topbar">
            <div>
                <h1>Mechanics & Activities</h1>
                <p>Assign Mechanic to Activity (Automatically checks professional certification requirements)</p>
            </div>
        </div>
        <hr>

        <?php if ($message): ?><p style="color: green; font-weight: bold;"><?= $message ?></p><?php endif; ?>
        <?php if ($error): ?><p style="color: red; font-weight: bold;"><?= $error ?></p><?php endif; ?>

        <div class="card" style="max-width: 600px;">
            <h3>Assign Mechanic to Activity</h3>
            <form method="POST">
                <div class="form-group">
                    <label>Select Mechanic</label>
                    <select name="mechanic_id" required>
                        <?php while ($m = $mechanics->fetch_assoc()): ?>
                            <option value="<?= $m['mechanic_id'] ?>"><?= htmlspecialchars($m['full_name']) ?> (ME-<?= $m['mechanic_id'] ?>)</option>
                        <?php endwhile; ?>
                    </select>
                </div>
                <div class="form-group">
                    <label>Select Maintenance Job</label>
                    <select name="job_id" required>
                        <?php while ($j = $jobs->fetch_assoc()): ?>
                            <option value="<?= $j['job_id'] ?>">Job M<?= $j['job_id'] ?></option>
                        <?php endwhile; ?>
                    </select>
                </div>
                <div class="form-group">
                    <label>Activity Sequence ID</label>
                    <input type="number" name="activity_id" value="1" required>
                </div>
                <div class="form-group">
                    <label>Activity Type Required Cert</label>
                    <select name="activity_type_id" required>
                        <?php while ($at = $activityTypes->fetch_assoc()): ?>
                            <option value="<?= $at['activity_type_id'] ?>"><?= htmlspecialchars($at['activity_type_name']) ?></option>
                        <?php endwhile; ?>
                    </select>
                </div>
                <div class="form-group">
                    <label>Labour Hours</label>
                    <input type="number" step="0.5" name="labour_hours" value="2.0" required>
                </div>
                <div class="form-actions">
                    <button type="submit" class="btn-primary">Assign Mechanic</button>
                </div>
            </form>
        </div>
    </main>
</body>
</html>