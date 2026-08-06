<?php

require __DIR__ . '/config/db.php';
require __DIR__ . '/models/Supplier.php';

$pageTitle = 'Suppliers';
$pageCss = 'suppliers';
$currentPage = 'suppliers';

$suppliers = [];
$errorMessage = null;

$supplierModel = new Supplier($conn);
$suppliers = $supplierModel->getAll();

include __DIR__ . '/includes/header.php';
include __DIR__ . '/includes/navbar.php';
?>

<main class="suppliers">
    <header>
        <h1>Suppliers</h1>
        <p>
            Workshop part suppliers, lead times and order information.
        </p>
        <hr>
    </header>

    <?php if ($errorMessage !== null): ?>
        <p role="alert">
            Unable to load suppliers:
            <?= htmlspecialchars($errorMessage) ?>
        </p>
    <?php else: ?>
        <table>
            <thead>
                <tr>
                    <th>Supplier ID</th>
                    <th>Supplier Name</th>
                    <th>Contact Information</th>
                    <th>Delivery Lead Time</th>
                    <th>Primary Parts</th>
                    <th>Backup Parts</th>
                    <th>Orders</th>
                    <th>Total Order Value</th>
                </tr>
            </thead>
            <tbody>
                <?php if ($suppliers === []): ?>
                    <tr>
                        <td colspan="8">No suppliers found.</td>
                    </tr>
                <?php else: ?>
                    <?php foreach ($suppliers as $supplier): ?>
                        <tr>
                            <td>
                                S-<?= (int) $supplier['supplier_id'] ?>
                            </td>
                            <td>
                                <?= htmlspecialchars(
                                    $supplier['supplier_name']
                                ) ?>
                            </td>
                            <td>
                                <?= htmlspecialchars(
                                    $supplier['contact_info']
                                ) ?>
                            </td>
                            <td>
                                <?= htmlspecialchars(
                                    $supplier['delivery_lead_time']
                                        ?: 'Not specified'
                                ) ?>
                            </td>
                            <td>
                                <?= (int) $supplier['primary_part_count'] ?>
                            </td>
                            <td>
                                <?= (int) $supplier['backup_part_count'] ?>
                            </td>
                            <td>
                                <?= (int) $supplier['order_count'] ?>
                            </td>
                            <td>
                                <?= number_format(
                                    (float) $supplier['total_order_value'],
                                    0
                                ) ?>
                                VND
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
