<?php

declare(strict_types=1);

require_once __DIR__ . '/../config/db.php';

include '../function.php';

checkin('workshop manager');

require_once __DIR__ . '/../models/MaintenanceActivity.php';

$action = $_POST['action'] ?? '';
$model  = new MaintenanceActivity($conn);
$jobId  = (int) ($_POST['job_id'] ?? 0);

match ($action) {
    'create' => $model->create($jobId, $_POST),

    'update' => $model->update(
        $jobId,
        (int) $_POST['activity_id'],
        $_POST
    ),

    'delete' => $model->delete(
        $jobId,
        (int) $_POST['activity_id']
    ),

    'add_part' => $model->addPart(
        $jobId,
        (int) $_POST['activity_id'],
        (int) $_POST['part_id'],
        (int) $_POST['quantity_used']
    ),

    'remove_part' => $model->removePart(
        $jobId,
        (int) $_POST['activity_id'],
        (int) $_POST['part_id']
    ),

    default => null,
};

$conn->close();

header('Location: ../job_view.php?job_id=' . $jobId);
exit;
