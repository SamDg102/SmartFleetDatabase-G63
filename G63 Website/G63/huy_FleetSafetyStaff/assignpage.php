<?php
session_start();
include '../db.php';
include '../function.php';

checkin('fleet safety staff');
$currentPage = 'safety-assign';

$message = '';
$error = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $driver_id = $_POST['driver_id'];
    $vin = $_POST['vin'];

    $stmtCat = $conn->prepare("SELECT category_id FROM vehicle WHERE VIN = ?");
    $stmtCat->bind_param("s", $vin);
    $stmtCat->execute();
    $catId = $stmtCat->get_result()->fetch_assoc()['category_id'] ?? 0;

    $sqlCheck = "SELECT COUNT(*) AS missing_certs
                 FROM category_required_certification crc
                 WHERE crc.category_id = '$catId'
                   AND crc.driver_certification_id NOT IN (
                       SELECT driver_certification_id
                       FROM driver_certification_record
                       WHERE driver_id = '$driver_id' AND expiration_date >= CURDATE()
                   )";
    $missingCerts = $conn->query($sqlCheck)->fetch_assoc()['missing_certs'] ?? 0;

    if ($missingCerts == 0) {
        $stmtAssign = $conn->prepare("INSERT INTO vehicle_driver_assignment (driver_id, VIN, start_date) VALUES (?, ?, CURDATE())");
        $stmtAssign->bind_param("is", $driver_id, $vin);
        $stmtAssign->execute();
        $message = "Driver successfully assigned to vehicle!";
    } else {
        $error = "Driver is missing $missingCerts required active certification(s) for this vehicle category!";
    }
}

$drivers = $conn->query("SELECT driver_id, full_name FROM driver WHERE safety_hold = FALSE");
$vehicles = $conn->query("SELECT v.VIN, v.registration_number, vc.category_name FROM vehicle v JOIN vehicle_category vc ON vc.category_id = v.category_id");
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>Driver & Vehicle Assignment</title>
    <link rel="stylesheet" href="styles/navbar.css">
    <link rel="stylesheet" href="styles/staff.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.1/css/all.min.css">
</head>
<body>
    <?php include 'includes/navbar.php'; ?>
    <main class="scorepage">
        <div class="topbar">
            <div>
                <h1>Driver & Vehicle Assignment</h1>
                <p>Assign Driver to Vehicle (Automatically checks certification requirements)</p>
            </div>
        </div>
        <hr>

        <?php if ($message): ?><p style="color: green; font-weight: bold;"><?= $message ?></p><?php endif; ?>
        <?php if ($error): ?><p style="color: red; font-weight: bold;"><?= $error ?></p><?php endif; ?>

        <div class="card" style="max-width: 600px;">
            <h3>Assign Driver to Vehicle</h3>
            <form method="POST">
                <div class="form-group">
                    <label>Select Driver (Active only)</label>
                    <select name="driver_id" required>
                        <option value="">-- Choose Driver --</option>
                        <?php while ($d = $drivers->fetch_assoc()): ?>
                            <option value="<?= $d['driver_id'] ?>"><?= htmlspecialchars($d['full_name']) ?> (D-<?= $d['driver_id'] ?>)</option>
                        <?php endwhile; ?>
                    </select>
                </div>

                <div class="form-group">
                    <label>Select Vehicle</label>
                    <select name="vin" required>
                        <option value="">-- Choose Vehicle --</option>
                        <?php while ($v = $vehicles->fetch_assoc()): ?>
                            <option value="<?= $v['VIN'] ?>"><?= htmlspecialchars($v['registration_number']) ?> - <?= htmlspecialchars($v['category_name']) ?> (<?= $v['VIN'] ?>)</option>
                        <?php endwhile; ?>
                    </select>
                </div>

                <div class="form-actions">
                    <button type="submit" class="btn-primary"><i class="fa-solid fa-check"></i>Assign Now</button>
                </div>
            </form>
        </div>
    </main>
</body>
</html>