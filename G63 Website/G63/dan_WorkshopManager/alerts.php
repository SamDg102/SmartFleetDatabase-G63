<?php
require '../db.php';

session_start();

include '../function.php';

checkin('workshop manager');

$pageTitle = 'Predictive Alerts';
$pageCss = 'alerts';
$currentPage = 'alerts';
$alerts = [];
$errorMessage = null;
$sql =
    "SELECT pa.alert_id,pa.VIN,v.registration_number,at.alert_name,at.description,pa.job_id,pa.timestamp,pa.action_taken 
FROM predictive_alert pa 
JOIN vehicle v 
ON v.VIN=pa.VIN 
JOIN alert_type 
at ON at.alert_type_id=pa.alert_type_id 
ORDER BY pa.timestamp DESC";
$r = $conn->query($sql);
if (!$r) throw new RuntimeException($conn->error);
$alerts = $r->fetch_all(MYSQLI_ASSOC);
include __DIR__ . '/includes/header.php';
include __DIR__ . '/includes/navbar.php'; ?>
<main class="alerts">
    <header>
        <h1>Predictive Alerts</h1>
        <p>Monitor abnormal vehicle conditions and the action taken by workshop staff.</p>
        <hr>
    </header><?php if ($errorMessage): ?><p role="alert"><?= htmlspecialchars($errorMessage) ?></p><?php else: ?><table>
            <thead>
                <tr>
                    <th>ID</th>
                    <th>Vehicle</th>
                    <th>Alert</th>
                    <th>Timestamp</th>
                    <th>Linked Job</th>
                    <th>Action Taken</th>
                </tr>
            </thead>
            <tbody><?php if (!$alerts): ?><tr>
                        <td colspan="6">No alerts found.</td>
                    </tr><?php else: foreach ($alerts as $a): ?><tr>
                            <td>A-<?= (int)$a['alert_id'] ?></td>
                            <td><?= htmlspecialchars($a['registration_number']) ?> (<?= htmlspecialchars($a['VIN']) ?>)</td>
                            <td><strong><?= htmlspecialchars($a['alert_name']) ?></strong><br><small><?= htmlspecialchars($a['description']) ?></small></td>
                            <td><?= htmlspecialchars($a['timestamp']) ?></td>
                            <td><?= $a['job_id'] ? '<a href="job_view.php?job_id=' . (int)$a['job_id'] . '">J-' . (int)$a['job_id'] . '</a>' : 'Not linked' ?></td>
                            <td><?= htmlspecialchars($a['action_taken'] ?: 'Pending') ?></td>
                        </tr><?php endforeach;
                                                                                                    endif; ?></tbody>
        </table><?php endif; ?>
</main><?php include __DIR__ . '/includes/footer.php';
        $conn->close(); ?>