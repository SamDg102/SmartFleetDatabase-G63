<?php

declare(strict_types=1);

require_once __DIR__ . '/../config/db.php';

include '../function.php';

checkin('workshop manager');

require_once __DIR__ . '/../models/Part.php';

$action = $_POST['action'] ?? '';
$model  = new Part($conn);

match ($action) {
    'create' => $model->create($_POST),
    'update' => $model->update((int) $_POST['part_id'], $_POST),
    'delete' => $model->delete((int) $_POST['part_id']),
    default  => null,
};

$conn->close();

header('Location: ../parts.php');
exit;
