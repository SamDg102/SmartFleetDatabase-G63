<?php

require_once __DIR__ . '/config/db.php';
require_once __DIR__ . '/models/Mechanic.php';

$pageTitle = 'Mechanics';
$pageCss = 'mechanics';
$currentPage = 'mechanics';

$mechanics = [];
$errorMessage = null;

$mechanicModel = new Mechanic($conn);
$mechanics = $mechanicModel->getAll();
include __DIR__ . '/includes/header.php';
include __DIR__ . '/includes/navbar.php';
?>

<main class="mechanics">
    <header>
        <h1>Mechanics</h1>
        <p>
            Mechanics, certifications, assigned activities and labour hours.
        </p>
        <hr>
    </header>

    <?php if ($errorMessage !== null): ?>
        <p role="alert">
            Unable to load mechanics:
            <?= htmlspecialchars($errorMessage) ?>
        </p>
    <?php else: ?>
        <table>
            <thead>
                <tr>
                    <th>Mechanic ID</th>
                    <th>Name</th>
                    <th>Workshop / Depot</th>
                    <th>Employment Status</th>
                    <th>Certifications</th>
                    <th>Valid Certifications</th>
                    <th>Assigned Activities</th>
                    <th>Total Labour Hours</th>
                    <th>Details</th>
                </tr>
            </thead>
            <tbody>
                <?php if ($mechanics === []): ?>
                    <tr>
                        <td colspan="9">No mechanics found.</td>
                    </tr>
                <?php else: ?>
                    <?php foreach ($mechanics as $mechanic): ?>
                        <tr>
                            <td>ME-<?= (int) $mechanic['mechanic_id'] ?></td>
                            <td>
                                <?= htmlspecialchars($mechanic['full_name']) ?>
                            </td>
                            <td>
                                <?= htmlspecialchars(
                                    $mechanic['depot_name']
                                ) ?>
                                —
                                <?= htmlspecialchars(
                                    $mechanic['depot_location']
                                ) ?>
                            </td>
                            <td>
                                <?= (int) $mechanic['employment_status'] === 1
                                    ? 'Active'
                                    : 'Inactive' ?>
                            </td>
                            <td>
                                <?= (int) $mechanic['certification_count'] ?>
                            </td>
                            <td>
                                <?= (int) $mechanic['valid_certification_count'] ?>
                            </td>
                            <td>
                                <?= (int) $mechanic['assigned_activity_count'] ?>
                            </td>
                            <td>
                                <?= htmlspecialchars(
                                    (string) $mechanic['total_labour_hours']
                                ) ?>
                            </td>
                            <td>
                                <a href="mechanic_view.php?mechanic_id=<?= (int) $mechanic['mechanic_id'] ?>">
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
