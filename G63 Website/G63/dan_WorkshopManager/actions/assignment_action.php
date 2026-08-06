<?php

declare(strict_types=1);

require_once '../db.php';

session_start();

include '../function.php';

checkin('workshop manager');

require_once __DIR__ . '/../models/MechanicActivity.php';

$action = $_POST['action'] ?? '';
$model  = new MechanicActivity($conn);
$jobId  = (int) ($_POST['job_id'] ?? 0);

match ($action) {
    'assign' => $model->assign(
        $jobId,
        (int) $_POST['activity_id'],
        (int) $_POST['mechanic_id'],
        (string) $_POST['labour_hours']
    ),

    'update' => $model->updateHours(
        $jobId,
        (int) $_POST['activity_id'],
        (int) $_POST['mechanic_id'],
        (string) $_POST['labour_hours']
    ),

    'unassign' => $model->unassign(
        $jobId,
        (int) $_POST['activity_id'],
        (int) $_POST['mechanic_id']
    ),

    default => null,
};

$conn->close();

header('Location: ../job_view.php?job_id=' . $jobId);
exit;
