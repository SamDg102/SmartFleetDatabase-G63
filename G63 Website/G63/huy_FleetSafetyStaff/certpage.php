<?php
session_start();
include '../db.php';
include '../function.php';

checkin('fleet safety staff');
$currentPage = 'safety-certs';

$sqlCerts = "SELECT d.driver_id, d.full_name, dc.driver_certification_name, dcr.issue_date, dcr.expiration_date,
                    CASE WHEN dcr.expiration_date < CURDATE() THEN 'EXPIRED'
                         WHEN dcr.expiration_date <= DATE_ADD(CURDATE(), INTERVAL 30 DAY) THEN 'EXPIRING SOON'
                         ELSE 'VALID' END AS status
             FROM driver_certification_record dcr
             JOIN driver d ON d.driver_id = dcr.driver_id
             JOIN driver_certification dc ON dc.driver_certification_id = dcr.driver_certification_id
             WHERE dcr.expiration_date <= DATE_ADD(CURDATE(), INTERVAL 30 DAY)
             ORDER BY dcr.expiration_date ASC";
$resultCerts = $conn->query($sqlCerts);
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>Driver Certifications</title>
    <link rel="stylesheet" href="styles/navbar.css">
    <link rel="stylesheet" href="styles/staff.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.1/css/all.min.css">
</head>
<body>
    <?php include 'includes/navbar.php'; ?>
    <main class="scorepage">
        <div class="topbar">
            <div>
                <h1>Driver Certifications</h1>
                <p>Expired or Expiring Certifications (within 30 days)</p>
            </div>
        </div>
        <hr>
        <div class="table-wrap">
            <table>
                <thead>
                    <tr>
                        <th>Driver ID</th>
                        <th>Full Name</th>
                        <th>Certification Name</th>
                        <th>Issue Date</th>
                        <th>Expiration Date</th>
                        <th>Status</th>
                    </tr>
                </thead>
                <tbody>
                    <?php if ($resultCerts && $resultCerts->num_rows > 0): ?>
                        <?php while ($r = $resultCerts->fetch_assoc()): ?>
                            <tr>
                                <td>D-<?= $r['driver_id'] ?></td>
                                <td><?= htmlspecialchars($r['full_name']) ?></td>
                                <td><?= htmlspecialchars($r['driver_certification_name']) ?></td>
                                <td><?= $r['issue_date'] ?></td>
                                <td><?= $r['expiration_date'] ?></td>
                                <td>
                                    <span class="badge badge-<?= $r['status'] == 'EXPIRED' ? 'critical' : 'medium' ?>">
                                        <?= $r['status'] ?>
                                    </span>
                                </td>
                            </tr>
                        <?php endwhile; ?>
                    <?php else: ?>
                        <tr><td colspan="6" style="text-align: center;">No expiring certifications found.</td></tr>
                    <?php endif; ?>
                </tbody>
            </table>
        </div>
    </main>
</body>
</html>