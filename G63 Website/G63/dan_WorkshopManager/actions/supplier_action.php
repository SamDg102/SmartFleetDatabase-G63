<?php

declare(strict_types=1);

require_once '../db.php';

session_start();

include '../function.php';

checkin('workshop manager');

require_once __DIR__ . '/../models/Supplier.php';

$action = $_POST['action'] ?? '';
$model  = new Supplier($conn);

match ($action) {
    'create' => $model->create($_POST),
    'update' => $model->update((int) $_POST['supplier_id'], $_POST),
    'delete' => $model->delete((int) $_POST['supplier_id']),
    default  => null,
};

$conn->close();

header('Location: ../suppliers.php');
exit;
