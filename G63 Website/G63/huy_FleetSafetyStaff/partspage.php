<?php
session_start();
include '../db.php';
include '../function.php';

checkin('fleet safety staff');
$currentPage = 'workshop-parts';

$reorder_threshold = $_GET['threshold'] ?? 15;

// B7. Low stock query
$sqlParts = "SELECT p.part_id, p.part_number, p.description, p.stock_quantity,
                    s1.supplier_name AS primary_supplier, p.primary_unit_cost,
                    s2.supplier_name AS backup_supplier, p.backup_unit_cost
             FROM part p
             JOIN supplier s1 ON s1.supplier_id = p.primary_supplier_id
             LEFT JOIN supplier s2 ON s2.supplier_id = p.backup_supplier_id
             WHERE p.stock_quantity < '$reorder_threshold'
             ORDER BY p.stock_quantity ASC";
$resultParts = $conn->query($sqlParts);

// Orders query
$sqlOrders = "SELECT po.order_id, s.supplier_name, po.order_date, po.expected_delivery_date, po.order_status
              FROM part_order po
              JOIN supplier s ON s.supplier_id = po.supplier_id
              WHERE po.order_status != 'DELIVERED'
              ORDER BY po.expected_delivery_date ASC";
$resultOrders = $conn->query($sqlOrders);
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>Parts & Suppliers</title>
    <link rel="stylesheet" href="styles/navbar.css">
    <link rel="stylesheet" href="styles/staff.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.1/css/all.min.css">
</head>
<body>
    <?php include 'includes/navbar.php'; ?>
    <main class="scorepage">
        <div class="topbar">
            <div>
                <h1>Parts & Suppliers</h1>
                <p>Low Stock Alerts & Pending Orders</p>
            </div>
        </div>
        <hr>

        <h3>Low Stock Parts (Below threshold)</h3>
        <div class="table-wrap" style="margin-bottom: 30px;">
            <table>
                <thead>
                    <tr>
                        <th>Part ID</th>
                        <th>Part Number</th>
                        <th>Description</th>
                        <th>Stock Qty</th>
                        <th>Primary Supplier</th>
                        <th>Primary Cost</th>
                        <th>Backup Supplier</th>
                    </tr>
                </thead>
                <tbody>
                    <?php if ($resultParts && $resultParts->num_rows > 0): ?>
                        <?php while ($r = $resultParts->fetch_assoc()): ?>
                            <tr>
                                <td>P-<?= $r['part_id'] ?></td>
                                <td><?= htmlspecialchars($r['part_number']) ?></td>
                                <td><?= htmlspecialchars($r['description']) ?></td>
                                <td><span class="badge badge-critical"><?= $r['stock_quantity'] ?></span></td>
                                <td><?= htmlspecialchars($r['primary_supplier']) ?></td>
                                <td><?= number_format($r['primary_unit_cost'], 0, ',', '.') ?>₫</td>
                                <td><?= htmlspecialchars($r['backup_supplier'] ?? 'None') ?></td>
                            </tr>
                        <?php endwhile; ?>
                    <?php else: ?>
                        <tr><td colspan="7" style="text-align: center;">All parts are well stocked.</td></tr>
                    <?php endif; ?>
                </tbody>
            </table>
        </div>

        <h3>Pending Orders (Awaiting Delivery)</h3>
        <div class="table-wrap">
            <table>
                <thead>
                    <tr>
                        <th>Order ID</th>
                        <th>Supplier</th>
                        <th>Order Date</th>
                        <th>Expected Delivery</th>
                        <th>Status</th>
                    </tr>
                </thead>
                <tbody>
                    <?php if ($resultOrders && $resultOrders->num_rows > 0): ?>
                        <?php while ($o = $resultOrders->fetch_assoc()): ?>
                            <tr>
                                <td>PO-<?= $o['order_id'] ?></td>
                                <td><?= htmlspecialchars($o['supplier_name']) ?></td>
                                <td><?= $o['order_date'] ?></td>
                                <td><?= $o['expected_delivery_date'] ?></td>
                                <td><span class="badge badge-pending"><?= $o['order_status'] ?></span></td>
                            </tr>
                        <?php endwhile; ?>
                    <?php else: ?>
                        <tr><td colspan="5" style="text-align: center;">No pending part orders.</td></tr>
                    <?php endif; ?>
                </tbody>
            </table>
        </div>
    </main>
</body>
</html>