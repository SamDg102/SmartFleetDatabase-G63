<?php

declare(strict_types=1);

require_once __DIR__ . '/../config/db.php';
require_once __DIR__ . '/../models/MaintenanceJob.php';

$jobModel = new MaintenanceJob($conn);
$action   = $_POST['action'] ?? '';

switch ($action) {
    case 'create':
        $jobModel->create([
            'VIN'            => $_POST['VIN'] ?? '',
            'workshop_id'    => $_POST['workshop_id'] ?? 0,
            'date_opened'    => $_POST['date_opened'] ?? '',
            'date_closed'    => $_POST['date_closed'] ?? '',
            'downtime_hours' => $_POST['downtime_hours'] ?? '00:00:00',
            'job_status'     => $_POST['job_status'] ?? '',
        ]);
        break;

    case 'update':
        $jobId = (int) ($_POST['job_id'] ?? 0);

        $jobModel->update($jobId, [
            'VIN'            => $_POST['VIN'] ?? '',
            'workshop_id'    => $_POST['workshop_id'] ?? 0,
            'date_opened'    => $_POST['date_opened'] ?? '',
            'date_closed'    => $_POST['date_closed'] ?? '',
            'downtime_hours' => $_POST['downtime_hours'] ?? '00:00:00',
            'job_status'     => $_POST['job_status'] ?? '',
        ]);
        break;

    case 'delete':
        $jobId = (int) ($_POST['job_id'] ?? 0);
        $jobModel->delete($jobId);
        break;
}

$conn->close();

header('Location: ../maintenancejobs.php');
exit;
