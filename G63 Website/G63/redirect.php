<?php
session_start();

if (!isset($_SESSION['email'])) {
    header("Location: ../");
    exit();
}

$role = '';

if (isset($_SESSION['role']) && !empty($_SESSION['role'])) {
    $role = strtolower(trim($_SESSION['role']));
} elseif (isset($_GET['role']) && !empty($_GET['role'])) {
    $role = strtolower(trim($_GET['role']));
}

switch ($role) {
    case 'fleet safety staff':
        header('Location: huy_FleetSafetyStaff/workshop-staff.php');
        exit;
    case 'driver':
        header('Location: truc_Driver/scorepage.php');
        exit;
    case 'workshop manager':
        header('Location: dan_WorkshopManager/dashboard.php');
        exit;
    case 'mechanic':
        header('Location: hoang_Mechanic/mechanic.php');
        exit;
    case 'admin':
        header('Location: vu_admin/userlist.php');
        exit;
    default:
        header('Location: logout.php');
        exit;
}
?>
