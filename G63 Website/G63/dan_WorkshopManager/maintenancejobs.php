<?php

require_once __DIR__ . '/config/db.php';

include '../function.php';

checkin('workshop manager');

require_once __DIR__ . '/models/MaintenanceJob.php';

$pageTitle = 'Maintenance Jobs';
$pageCss = 'maintenancejobs';
$currentPage = 'maintenancejobs';

$jobs = [];
$errorMessage = null;
$jobModel = new MaintenanceJob($conn);
$jobs = $jobModel->getAll();
include __DIR__ . '/includes/header.php';
include __DIR__ . '/includes/navbar.php';
?>

<main class="maintenancejobs">
    <header>
        <h1>Maintenance Jobs</h1>
        <p>
            Each record represents one vehicle visit to a workshop.
        </p>
        <hr>
    </header>

    <?php if ($errorMessage !== null): ?>
        <p role="alert">
            Unable to load maintenance jobs:
            <?= htmlspecialchars($errorMessage) ?>
        </p>
    <?php else: ?>
        <table>
            <thead>
                <tr>
                    <th>Job ID</th>
                    <th>Vehicle</th>
                    <th>Category</th>
                    <th>Depot</th>
                    <th>Date Opened</th>
                    <th>Date Closed</th>
                    <th>Downtime (hrs)</th>
                    <th>Status</th>
                    <th>Activities</th>
                    <th>Alerts</th>
                    <th>Estimated Parts Cost</th>
                    <th>Details</th>
                </tr>
            </thead>
            <tbody>
                <?php if ($jobs === []): ?>
                    <tr>
                        <td colspan="12">No maintenance jobs found.</td>
                    </tr>
                <?php else: ?>
                    <?php foreach ($jobs as $job): ?>
                        <tr>
                            <td>J-<?= (int) $job['job_id'] ?></td>
                            <td>
                                <a href="vehicle_view.php?VIN=<?= urlencode($job['VIN']) ?>">
                                    <?= htmlspecialchars(
                                        $job['registration_number']
                                    ) ?>
                                </a>
                            </td>
                            <td>
                                <?= htmlspecialchars(
                                    $job['category_name'] ?: 'N/A'
                                ) ?>
                            </td>
                            <td>
                                <?= htmlspecialchars($job['depot_name']) ?>
                            </td>
                            <td>
                                <?= htmlspecialchars($job['date_opened']) ?>
                            </td>
                            <td>
                                <?= htmlspecialchars(
                                    $job['date_closed'] ?: 'Not closed'
                                ) ?>
                            </td>
                            <td>
                                <?= htmlspecialchars(
                                    (string) $job['downtime_hours_decimal']
                                ) ?>
                            </td>
                            <td>
                                <?= htmlspecialchars($job['job_status']) ?>
                            </td>
                            <td><?= (int) $job['activity_count'] ?></td>
                            <td><?= (int) $job['linked_alert_count'] ?></td>
                            <td>
                                <?= number_format(
                                    (float) $job['estimated_parts_cost'],
                                    0
                                ) ?>
                                VND
                            </td>
                            <td>
                                <a href="job_view.php?job_id=<?= (int) $job['job_id'] ?>">
                                    View
                                </a>
                            </td>
                        </tr>
                    <?php endforeach; ?>
                <?php endif; ?>
            </tbody>
        </table>
    <?php endif; ?>
</main>

<?php
include __DIR__ . '/includes/footer.php';
$conn->close();
