<?php

require_once __DIR__ . '/config/db.php';
require_once __DIR__ . '/models/Vehicle.php';
require '../function.php';

checkin('workshop manager');

$pageTitle = 'Vehicle Detail';
$pageCss = 'vehicles';
$currentPage = 'vehicles';

$vin = isset($_GET['VIN']) ? trim((string) $_GET['VIN']) : '';
$vehicle = null;
$jobs = [];
$alerts = [];
$assignments = [];
$summary = [
    'total_jobs' => 0,
    'total_downtime_hours' => 0,
    'estimated_repair_cost' => 0
];
$errorMessage = null;

if ($vin === '') {
    $errorMessage = 'A VIN is required.';
} else {
    try {
        $vehicleModel = new Vehicle($conn);
        $vehicle = $vehicleModel->getByVin($vin);

        if ($vehicle === null) {
            $errorMessage = 'Vehicle not found.';
        } else {
            $jobs = $vehicleModel->getMaintenanceJobs($vin);
            $alerts = $vehicleModel->getAlerts($vin);
            $assignments = $vehicleModel->getAlerts($vin);
            $summary = $vehicleModel->getMaintenanceSummary($vin);
        }
    } catch (Throwable $exception) {
        $errorMessage = $exception->getMessage();
    }
}

include __DIR__ . '/includes/header.php';
include __DIR__ . '/includes/navbar.php';
?>

<main class="vehicle-detail">
    <p>
        <a href="vehicles.php">&laquo; Back to vehicles</a>
    </p>

    <?php if ($errorMessage !== null): ?>
        <p role="alert">
            Unable to load vehicle:
            <?= htmlspecialchars($errorMessage) ?>
        </p>
    <?php else: ?>
        <header>
            <h1>
                <?= htmlspecialchars($vehicle['registration_number']) ?>
            </h1>
            <p>VIN: <?= htmlspecialchars($vehicle['VIN']) ?></p>
            <hr>
        </header>

        <section>
            <h2>Vehicle Information</h2>
            <table>
                <tbody>
                    <tr>
                        <th>Category</th>
                        <td>
                            <?= htmlspecialchars(
                                $vehicle['category_name']
                            ) ?>
                        </td>
                    </tr>
                    <tr>
                        <th>Manufacturer</th>
                        <td>
                            <?= htmlspecialchars(
                                $vehicle['manufacturer_name']
                            ) ?>
                        </td>
                    </tr>
                    <tr>
                        <th>Manufacturer Contact</th>
                        <td>
                            <?= htmlspecialchars(
                                $vehicle['manufacturer_contact']
                            ) ?>
                        </td>
                    </tr>
                    <tr>
                        <th>Year of Manufacture</th>
                        <td>
                            <?= (int) $vehicle['year_of_manufacture'] ?>
                        </td>
                    </tr>
                    <tr>
                        <th>Current Odometer</th>
                        <td>
                            <?= number_format(
                                (float) $vehicle['current_odometer'],
                                1
                            ) ?>
                        </td>
                    </tr>
                    <tr>
                        <th>Depot Location</th>
                        <td>
                            <?= htmlspecialchars(
                                $vehicle['depot_location']
                            ) ?>
                        </td>
                    </tr>
                    <tr>
                        <th>Status</th>
                        <td>
                            <?= htmlspecialchars(
                                $vehicle['vehicle_status_name']
                            ) ?>
                        </td>
                    </tr>
                    <tr>
                        <th>Assignability</th>
                        <td>
                            <?= htmlspecialchars(
                                $vehicle['assignability']
                            ) ?>
                        </td>
                    </tr>
                    <tr>
                        <th>Total Maintenance Jobs</th>
                        <td><?= (int) $summary['total_jobs'] ?></td>
                    </tr>
                    <tr>
                        <th>Total Downtime</th>
                        <td>
                            <?= htmlspecialchars(
                                (string) $summary['total_downtime_hours']
                            ) ?>
                            hours
                        </td>
                    </tr>
                    <tr>
                        <th>Estimated Repair Cost</th>
                        <td>
                            <?= number_format(
                                (float) $summary['estimated_repair_cost'],
                                0
                            ) ?>
                            VND
                        </td>
                    </tr>
                </tbody>
            </table>
        </section>

        <section>
            <h2>Maintenance History</h2>
            <table>
                <thead>
                    <tr>
                        <th>Job ID</th>
                        <th>Workshop / Depot</th>
                        <th>Opened</th>
                        <th>Closed</th>
                        <th>Downtime (hrs)</th>
                        <th>Status</th>
                        <th>Estimated Parts Cost</th>
                    </tr>
                </thead>
                <tbody>
                    <?php if ($jobs === []): ?>
                        <tr>
                            <td colspan="7">
                                No maintenance jobs found.
                            </td>
                        </tr>
                    <?php else: ?>
                        <?php foreach ($jobs as $job): ?>
                            <tr>
                                <td>
                                    <a href="job_view.php?job_id=<?= (int) $job['job_id'] ?>">
                                        J-<?= (int) $job['job_id'] ?>
                                    </a>
                                </td>
                                <td>
                                    <?= htmlspecialchars(
                                        $job['depot_name']
                                    ) ?>
                                    —
                                    <?= htmlspecialchars(
                                        $job['depot_location']
                                    ) ?>
                                </td>
                                <td>
                                    <?= htmlspecialchars(
                                        $job['date_opened']
                                    ) ?>
                                </td>
                                <td>
                                    <?= htmlspecialchars(
                                        $job['date_closed']
                                            ?: 'Not closed'
                                    ) ?>
                                </td>
                                <td>
                                    <?= htmlspecialchars(
                                        (string) $job['downtime_hours_decimal']
                                    ) ?>
                                </td>
                                <td>
                                    <?= htmlspecialchars(
                                        $job['job_status']
                                    ) ?>
                                </td>
                                <td>
                                    <?= number_format(
                                        (float) $job['estimated_parts_cost'],
                                        0
                                    ) ?>
                                    VND
                                </td>
                            </tr>
                        <?php endforeach; ?>
                    <?php endif; ?>
                </tbody>
            </table>
        </section>

        <section>
            <h2>Predictive Alerts</h2>
            <table>
                <thead>
                    <tr>
                        <th>Alert ID</th>
                        <th>Type</th>
                        <th>Description</th>
                        <th>Linked Job</th>
                        <th>Timestamp</th>
                        <th>Status</th>
                        <th>Action Taken</th>
                    </tr>
                </thead>
                <tbody>
                    <?php if ($alerts === []): ?>
                        <tr>
                            <td colspan="7">
                                No predictive alerts found.
                            </td>
                        </tr>
                    <?php else: ?>
                        <?php foreach ($alerts as $alert): ?>
                            <tr>
                                <td>A-<?= (int) $alert['alert_id'] ?></td>
                                <td>
                                    <?= htmlspecialchars(
                                        $alert['alert_name']
                                    ) ?>
                                </td>
                                <td>
                                    <?= htmlspecialchars(
                                        $alert['description']
                                    ) ?>
                                </td>
                                <td>
                                    <?php if ($alert['job_id'] !== null): ?>
                                        <a href="job_view.php?job_id=<?= (int) $alert['job_id'] ?>">
                                            J-<?= (int) $alert['job_id'] ?>
                                        </a>
                                    <?php else: ?>
                                        Not linked
                                    <?php endif; ?>
                                </td>
                                <td>
                                    <?= htmlspecialchars(
                                        $alert['timestamp']
                                    ) ?>
                                </td>
                                <td>
                                    <?= htmlspecialchars(
                                        $alert['alert_status']
                                    ) ?>
                                </td>
                                <td>
                                    <?= htmlspecialchars(
                                        $alert['action_taken']
                                            ?: 'No action recorded'
                                    ) ?>
                                </td>
                            </tr>
                        <?php endforeach; ?>
                    <?php endif; ?>
                </tbody>
            </table>
        </section>

        <section>
            <h2>Driver Assignments</h2>
            <table>
                <thead>
                    <tr>
                        <th>Driver ID</th>
                        <th>Name</th>
                        <th>Contact</th>
                        <th>Start Date</th>
                        <th>End Date</th>
                    </tr>
                </thead>
                <tbody>
                    <?php if ($assignments === []): ?>
                        <tr>
                            <td colspan="5">
                                No driver assignments found.
                            </td>
                        </tr>
                    <?php else: ?>
                        <?php foreach ($assignments as $assignment): ?>
                            <tr>
                                <td>
                                    D-<?= (int) $assignment['driver_id'] ?>
                                </td>
                                <td>
                                    <?= htmlspecialchars(
                                        $assignment['full_name']
                                    ) ?>
                                </td>
                                <td>
                                    <?= htmlspecialchars(
                                        $assignment['contact_info']
                                    ) ?>
                                </td>
                                <td>
                                    <?= htmlspecialchars(
                                        $assignment['start_date']
                                    ) ?>
                                </td>
                                <td>
                                    <?= htmlspecialchars(
                                        $assignment['end_date']
                                    ) ?>
                                </td>
                            </tr>
                        <?php endforeach; ?>
                    <?php endif; ?>
                </tbody>
            </table>
        </section>
    <?php endif; ?>
</main>

<?php
include __DIR__ . '/includes/footer.php';
$conn->close();
