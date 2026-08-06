<?php
session_start();
include "../db.php";
include '../function.php';

checkin('mechanic');

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

        <section class="joblist">

            <h2>Maintenance Jobs</h2>

            <table>
                <thead>
                    <tr>
                        <th>Job ID</th>
                        <th>VIN</th>
                        <th>Service Bay</th>
                        <th>Status</th>
                        <th>Date Opened</th>
                        <th>Date Closed</th>
                        <th>Total Cost</th>
                    </tr>
                </thead>

                <tbody>

                    <?php

                    $sql = "SELECT
                        mj.job_id,
                        mj.VIN,
                        sb.bay_name,
                        mj.job_status,
                        mj.date_opened,
                        mj.date_closed,
                        mj.total_cost
                    FROM maintenance_job mj
                    JOIN service_bay sb
                    ON mj.service_bay_id = sb.service_bay_id
                    ORDER BY mj.date_opened DESC";

                    $result = mysqli_query($conn, $sql);

                    while ($row = mysqli_fetch_assoc($result)) {
                        echo "<tr>";

                        echo "<td>" . $row['job_id'] . "</td>";
                        echo "<td>" . $row['VIN'] . "</td>";
                        echo "<td>" . $row['bay_name'] . "</td>";
                        echo "<td>" . $row['job_status'] . "</td>";
                        echo "<td>" . $row['date_opened'] . "</td>";

                        if ($row['date_closed'] == NULL)
                            echo "<td>-</td>";
                        else
                            echo "<td>" . $row['date_closed'] . "</td>";

                        if ($row['total_cost'] == NULL)
                            echo "<td>-</td>";
                        else
                            echo "<td>$" . $row['total_cost'] . "</td>";

                        echo "</tr>";
                    }

                    ?>

                </tbody>

            </table>

        </section>

    </main>

</body>

</html>