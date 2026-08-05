<?php

require_once __DIR__ . '/config/db.php';
require_once __DIR__ . '/models/Mechanic.php';
require '../function.php';

checkin('workshop manager');

$pageTitle = 'Mechanic Detail';
$pageCss = 'mechanics';
$currentPage = 'mechanics';

$mechanicId = filter_input(
    INPUT_GET,
    'mechanic_id',
    FILTER_VALIDATE_INT
);

$mechanic = null;
$certifications = [];
$assignments = [];
$errorMessage = null;

if ($mechanicId === false || $mechanicId === null) {
    $errorMessage = 'A valid mechanic_id is required.';
} else {
    try {
        $mechanicModel = new Mechanic($conn);
        $mechanic = $mechanicModel->getById($mechanicId);

        if ($mechanic === null) {
            $errorMessage = 'Mechanic not found.';
        } else {
            $certifications =
                $mechanicModel->getCertifications($mechanicId);
            $assignments =
                $mechanicModel->getAssignments($mechanicId);
        }
    } catch (Throwable $exception) {
        $errorMessage = $exception->getMessage();
    }
}

include __DIR__ . '/includes/header.php';
include __DIR__ . '/includes/navbar.php';
?>

<main class="mechanic-detail">
    <p>
        <a href="mechanics.php">&laquo; Back to mechanics</a>
    </p>

    <?php if ($errorMessage !== null): ?>
        <p role="alert">
            Unable to load mechanic:
            <?= htmlspecialchars($errorMessage) ?>
        </p>
    <?php else: ?>
        <header>
            <h1><?= htmlspecialchars($mechanic['full_name']) ?></h1>
            <p>Mechanic ID: ME-<?= (int) $mechanic['mechanic_id'] ?></p>
            <hr>
        </header>

        <section>
            <h2>Mechanic Information</h2>
            <table>
                <tbody>
                    <tr>
                        <th>Workshop ID</th>
                        <td><?= (int) $mechanic['workshop_id'] ?></td>
                    </tr>
                    <tr>
                        <th>Depot</th>
                        <td>
                            <?= htmlspecialchars($mechanic['depot_name']) ?>
                            —
                            <?= htmlspecialchars(
                                $mechanic['depot_location']
                            ) ?>
                        </td>
                    </tr>
                    <tr>
                        <th>Employment Status</th>
                        <td>
                            <?= (int) $mechanic['employment_status'] === 1
                                ? 'Active'
                                : 'Inactive' ?>
                        </td>
                    </tr>
                </tbody>
            </table>
        </section>

        <section>
            <h2>Certification Records</h2>
            <table>
                <thead>
                    <tr>
                        <th>Certification ID</th>
                        <th>Certification</th>
                        <th>Expiration Date</th>
                        <th>Status</th>
                    </tr>
                </thead>
                <tbody>
                    <?php if ($certifications === []): ?>
                        <tr>
                            <td colspan="4">
                                No certification records found.
                            </td>
                        </tr>
                    <?php else: ?>
                        <?php foreach ($certifications as $certification): ?>
                            <tr>
                                <td>
                                    <?= (int) $certification['mechanic_certification_id'] ?>
                                </td>
                                <td>
                                    <?= htmlspecialchars(
                                        $certification['mechanic_certification_name']
                                    ) ?>
                                </td>
                                <td>
                                    <?= htmlspecialchars(
                                        $certification['expiration_date']
                                    ) ?>
                                </td>
                                <td>
                                    <?= htmlspecialchars(
                                        $certification['certification_status']
                                    ) ?>
                                </td>
                            </tr>
                        <?php endforeach; ?>
                    <?php endif; ?>
                </tbody>
            </table>
        </section>

        <section>
            <h2>Assigned Jobs and Activities</h2>
            <table>
                <thead>
                    <tr>
                        <th>Job</th>
                        <th>Vehicle</th>
                        <th>Activity</th>
                        <th>Date Opened</th>
                        <th>Job Status</th>
                        <th>Labour Hours</th>
                    </tr>
                </thead>
                <tbody>
                    <?php if ($assignments === []): ?>
                        <tr>
                            <td colspan="6">
                                No activity assignments found.
                            </td>
                        </tr>
                    <?php else: ?>
                        <?php foreach ($assignments as $assignment): ?>
                            <tr>
                                <td>
                                    <a href="job_view.php?job_id=<?= (int) $assignment['job_id'] ?>">
                                        J-<?= (int) $assignment['job_id'] ?>
                                    </a>
                                </td>
                                <td>
                                    <a href="vehicle_view.php?VIN=<?= urlencode($assignment['VIN']) ?>">
                                        <?= htmlspecialchars(
                                            $assignment['registration_number']
                                        ) ?>
                                    </a>
                                </td>
                                <td>
                                    #<?= (int) $assignment['activity_id'] ?>
                                    —
                                    <?= htmlspecialchars(
                                        $assignment['activity_type_name']
                                    ) ?>
                                </td>
                                <td>
                                    <?= htmlspecialchars(
                                        $assignment['date_opened']
                                    ) ?>
                                </td>
                                <td>
                                    <?= htmlspecialchars(
                                        $assignment['job_status']
                                    ) ?>
                                </td>
                                <td>
                                    <?= htmlspecialchars(
                                        (string) $assignment['labour_hours_decimal']
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
