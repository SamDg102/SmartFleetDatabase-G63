<?php
session_start();
include '../db.php';
include '../function.php';

checkin('fleet safety staff');
$currentPage = 'workshop-warranty';

$sqlClaims = "SELECT wc.claim_id, wc.claim_type, wc.claim_date, wc.claim_status,
                     m.manufacturer_name, s.supplier_name,
                     wcp.job_id, wcp.activity_id, p.part_number
              FROM warranty_claim wc
              LEFT JOIN manufacturer m ON m.manufacturer_id = wc.manufacturer_id
              LEFT JOIN supplier s ON s.supplier_id = wc.supplier_id
              JOIN warranty_claim_part wcp ON wcp.claim_id = wc.claim_id
              JOIN part p ON p.part_id = wcp.part_id
              ORDER BY wc.claim_date DESC";
$resultClaims = $conn->query($sqlClaims);
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>Warranty Claims</title>
    <link rel="stylesheet" href="styles/navbar.css">
    <link rel="stylesheet" href="styles/staff.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.1/css/all.min.css">
</head>
<body>
    <?php include 'includes/navbar.php'; ?>
    <main class="scorepage">
        <div class="topbar">
            <div>
                <h1>Warranty Claims</h1>
                <p>Track warranty claims for vehicle parts</p>
            </div>
        </div>
        <hr>

        <div class="table-wrap">
            <table>
                <thead>
                    <tr>
                        <th>Claim ID</th>
                        <th>Type</th>
                        <th>Date</th>
                        <th>Part Number</th>
                        <th>Job ID</th>
                        <th>Manufacturer / Supplier</th>
                        <th>Status</th>
                    </tr>
                </thead>
                <tbody>
                    <?php if ($resultClaims && $resultClaims->num_rows > 0): ?>
                        <?php while ($r = $resultClaims->fetch_assoc()): ?>
                            <tr>
                                <td>WC-<?= $r['claim_id'] ?></td>
                                <td><?= htmlspecialchars($r['claim_type']) ?></td>
                                <td><?= $r['claim_date'] ?></td>
                                <td><?= htmlspecialchars($r['part_number']) ?></td>
                                <td>M<?= $r['job_id'] ?></td>
                                <td><?= htmlspecialchars($r['manufacturer_name'] ?? $r['supplier_name'] ?? 'N/A') ?></td>
                                <td><span class="badge badge-pending"><?= strtoupper($r['claim_status']) ?></span></td>
                            </tr>
                        <?php endwhile; ?>
                    <?php else: ?>
                        <tr><td colspan="7" style="text-align: center;">No warranty claims found.</td></tr>
                    <?php endif; ?>
                </tbody>
            </table>
        </div>
    </main>
</body>
</html>