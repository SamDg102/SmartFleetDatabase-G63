<?php
include "includes/includes.php";

$sql = "SELECT * FROM maintenance_job";
$result = mysqli_query($conn, $sql);
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
    <link rel="stylesheet" href="styles/mechanic.css">
    <link rel="stylesheet" href="styles/navbar.css">
</head>

<body>
    <?php
    $currentPage = 'jobs';
    include 'includes/navbar.php';
    ?>


    <main class="jobs">
        <header>
            <h1>Assigned Jobs</h1>
            <hr>
        </header>


        <table>
            <tr>
                <th>ID</th>
                <th>Vehicle</th>
                <th>Issue</th>
                <th>Status</th>
            </tr>
        </table>

    </main>


</body>
</html>