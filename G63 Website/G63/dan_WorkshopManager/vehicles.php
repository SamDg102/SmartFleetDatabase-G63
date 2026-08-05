<?php

require_once __DIR__ . '/config/db.php';
require_once __DIR__ . '/models/Vehicle.php';

$pageTitle = 'Vehicles';
$pageCss = 'vehicles';
$currentPage = 'vehicles';

$vehicles = [];
$errorMessage = null;

$vehicleModel = new Vehicle($conn);
$vehicles = $vehicleModel->getAll();

include __DIR__ . '/includes/header.php';
include __DIR__ . '/includes/navbar.php';
?>

<main class="vehicles">
    <header>
        <h1>Vehicles</h1>
        <p>
            Vehicle information with links to maintenance history,
            alerts, assignments, downtime and repair costs.
        </p>
        <hr>
    </header>

    <?php if ($errorMessage !== null): ?>
        <p role="alert">
            Unable to load vehicles:
            <?= htmlspecialchars($errorMessage) ?>
        </p>
    <?php else: ?>
        <table>
            <thead>
                <tr>
                    <th>VIN</th>
                    <th>Registration</th>
                    <th>Category</th>
                    <th>Manufacturer</th>
                    <th>Year</th>
                    <th>Odometer</th>
                    <th>Depot Location</th>
                    <th>Status</th>
                    <th>Assignability</th>
                    <th>Details</th>
                </tr>
            </thead>
            <tbody>
                <?php if ($vehicles === []): ?>
                    <tr>
                        <td colspan="10">No vehicles found.</td>
                    </tr>
                <?php else: ?>
                    <?php foreach ($vehicles as $vehicle): ?>
                        <tr>
                            <td>
                                <?= htmlspecialchars($vehicle['VIN']) ?>
                            </td>
                            <td>
                                <?= htmlspecialchars(
                                    $vehicle['registration_number']
                                ) ?>
                            </td>
                            <td>
                                <?= htmlspecialchars(
                                    $vehicle['category_name']
                                ) ?>
                            </td>
                            <td>
                                <?= htmlspecialchars(
                                    $vehicle['manufacturer_name']
                                ) ?>
                            </td>
                            <td>
                                <?= (int) $vehicle['year_of_manufacture'] ?>
                            </td>
                            <td>
                                <?= number_format(
                                    (float) $vehicle['current_odometer'],
                                    1
                                ) ?>
                            </td>
                            <td>
                                <?= htmlspecialchars(
                                    $vehicle['depot_location']
                                ) ?>
                            </td>
                            <td>
                                <?= htmlspecialchars(
                                    $vehicle['vehicle_status_name']
                                ) ?>
                            </td>
                            <td>
                                <?= htmlspecialchars(
                                    $vehicle['assignability']
                                ) ?>
                            </td>
                            <td>
                                <a href="vehicle_view.php?VIN=<?= urlencode($vehicle['VIN']) ?>">
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
