<?php

require_once __DIR__ . '/config/db.php';
require_once __DIR__ . '/models/Part.php';
require '../function.php';

checkin('workshop manager');

$pageTitle = 'Parts';
$pageCss = 'parts';
$currentPage = 'parts';

$parts = [];
$errorMessage = null;
$partModel = new Part($conn);
$parts = $partModel->getAll();

include __DIR__ . '/includes/header.php';
include __DIR__ . '/includes/navbar.php';
?>

<main class="parts">
    <header>
        <h1>Parts</h1>
        <p>
            Supplier, cost, ordering and usage information for workshop parts.
        </p>
        <hr>
    </header>

    <?php if ($errorMessage !== null): ?>
        <p role="alert">
            Unable to load parts:
            <?= htmlspecialchars($errorMessage) ?>
        </p>
    <?php else: ?>
        <table>
            <thead>
                <tr>
                    <th>Part ID</th>
                    <th>Primary Supplier</th>
                    <th>Primary Unit Cost</th>
                    <th>Backup Supplier</th>
                    <th>Backup Unit Cost</th>
                    <th>Quantity Used</th>
                    <th>Jobs Used In</th>
                    <th>Quantity Ordered</th>
                </tr>
            </thead>
            <tbody>
                <?php if ($parts === []): ?>
                    <tr>
                        <td colspan="8">No parts found.</td>
                    </tr>
                <?php else: ?>
                    <?php foreach ($parts as $part): ?>
                        <tr>
                            <td>P-<?= (int) $part['part_id'] ?></td>
                            <td>
                                <?= htmlspecialchars(
                                    $part['primary_supplier_name']
                                ) ?>
                            </td>
                            <td>
                                <?= number_format(
                                    (float) $part['primary_unit_cost'],
                                    0
                                ) ?>
                                VND
                            </td>
                            <td>
                                <?= htmlspecialchars(
                                    $part['backup_supplier_name']
                                        ?: 'Not assigned'
                                ) ?>
                            </td>
                            <td>
                                <?php if ($part['backup_unit_cost'] === null): ?>
                                    N/A
                                <?php else: ?>
                                    <?= number_format(
                                        (float) $part['backup_unit_cost'],
                                        0
                                    ) ?>
                                    VND
                                <?php endif; ?>
                            </td>
                            <td>
                                <?= (int) $part['total_quantity_used'] ?>
                            </td>
                            <td><?= (int) $part['jobs_used_in'] ?></td>
                            <td>
                                <?= (int) $part['total_quantity_ordered'] ?>
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
