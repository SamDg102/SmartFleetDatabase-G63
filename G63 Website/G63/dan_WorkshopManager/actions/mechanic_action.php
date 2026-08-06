<?php

declare(strict_types=1);

require_once '../db.php';

session_start();

include '../function.php';

checkin('workshop manager');

require_once __DIR__ . '/../models/Mechanic.php';
require_once __DIR__ . '/../models/Certification.php';

$action         = $_POST['action'] ?? '';
$mechanicModel  = new Mechanic($conn);
$certModel      = new Certification($conn);

match ($action) {
    'create' => $mechanicModel->create($_POST),
    'update' => $mechanicModel->update((int) $_POST['mechanic_id'], $_POST),
    'delete' => $mechanicModel->delete((int) $_POST['mechanic_id']),

    'assign_certification' => $certModel->assignToMechanic(
        (int) $_POST['mechanic_id'],
        (int) $_POST['mechanic_certification_id'],
        (string) $_POST['expiration_date']
    ),

    'remove_certification' => $certModel->removeFromMechanic(
        (int) $_POST['mechanic_id'],
        (int) $_POST['mechanic_certification_id']
    ),

    default => null,
};

$conn->close();

header('Location: ../mechanics.php');
exit;
