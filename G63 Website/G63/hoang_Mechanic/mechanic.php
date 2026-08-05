<?php
include "includes/includes.php";

// Total Jobs
$sql = "SELECT COUNT(*) AS total FROM maintenance_job";
$result = mysqli_query($conn, $sql);
$totalJobs = mysqli_fetch_assoc($result)['total'];

// In Progress Jobs
$sql = "SELECT COUNT(*) AS total FROM maintenance_job WHERE job_status='IN PROGRESS'";
$result = mysqli_query($conn, $sql);
$inProgress = mysqli_fetch_assoc($result)['total'];

// Completed Jobs
$sql = "SELECT COUNT(*) AS total FROM maintenance_job WHERE job_status='DONE'";
$result = mysqli_query($conn, $sql);
$completed = mysqli_fetch_assoc($result)['total'];
?>

<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Mechanic Dashboard</title>
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="stylesheet" href="https://fonts.googleapis.com/css2?family=Ubuntu:wght@400;500;700&display=swap">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.1/css/all.min.css">
    <link rel="stylesheet" href="styles/navbar.css">
    <link rel="stylesheet" href="styles/mechanic.css">
</head>

<body>
    <?php
    $currentPage = 'mechanic';
    include 'includes/navbar.php';
    ?>


    <main class="mechanic">
        <header>
            <h1>Mechanic Dashboard</h1>
            <hr>
        </header>

        <section class="miniboxes">
            <div class="card">
                <h2>Assigned Jobs</h2>
                <hr>
                <p><?php echo $totalJobs; ?></p>
            </div>

            <div class="card">
                <h2>In Progress</h2>
                <hr>
                <p><?php echo $inProgress; ?></p>
            </div>

            <div class="card">
                <h2>Completed</h2>
                <hr>
                <p><?php echo $completed; ?></p>
            </div>
        </section>
    </main>
</body>
</html>