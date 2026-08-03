<?php
session_start();

require_once 'includes/conndb.php';
include 'includes/navbar.php';
include '../function.php';

checkin('driver');


?>

<!DOCTYPE html>
<html lang="en">
    <head>
        <meta charset="UTF-8">
        <meta name="description" content="Driver dashboard of G63 Fleet Managment System">
        <meta name="keywords" content="Driver, Fleet, Incident">
        <meta name="author" content="Dang Nguyen Xuan Truc">
        <meta name="viewport" content="width=device-width, initial-scale=1.0">
    
        <title>G63 Driver Dashboard</title>
    
        <link rel="preconnect" href="https://fonts.googleapis.com">
        <link rel="stylesheet" href="https://fonts.googleapis.com/css2?family=Ubuntu:wght@400;500;700&display=swap">
        <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.1/css/all.min.css">
        <link rel="stylesheet" href="styles/navbar.css">
        <link rel="stylesheet" href="styles/incidentpage.css">
    </head>

    <body>
        <?php $currentPage = 'incidentpage';
        include 'includes/navbar.php';
        ?>

        <main class="incidentpage">
            <header>
                <h1>Incident History</h1>
                <hr>
            </header>
            <!--section for miniboxes on the top of UI
            that show info about driver incidents-->
            <section class="miniboxes">
                <div class="totalincidents">
                    <h3>This Year</h3>
                    <hr>
                    <?php
                    $sql= "SELECT COALESCE(COUNT(event_id), 0) AS total FROM safety_event
                    WHERE driver_id=112 
                    AND YEAR(timestamp) = YEAR(CURDATE())";
                    $stmt= $conn->prepare($sql);
                    $stmt-> execute();
                    $result= $stmt ->get_result();
                    $total=$result ->fetch_assoc();
                    echo $total["total"];
                    ?>
                    <!--php total incidents value-->
                </div>

                <div class="thismonth">
                    <h3>This Month</h3>
                    <hr>
                    <?php 
                    $sql= "SELECT COALESCE(COUNT(event_id), 0) AS totalmonth FROM safety_event
                    WHERE driver_id=112
                    AND YEAR(timestamp) = YEAR(CURDATE())
                    AND MONTH(timestamp)= MONTH(CURDATE())";
                    $stmt=$conn->prepare($sql);
                    $stmt->execute();
                    $result=$stmt->get_result();
                    $total=$result->fetch_assoc();
                    echo $total['totalmonth'];
                    ?>
                    <!--php-->
                </div>

    

                <div class="pendingreview">
                    <h3>Pending Review</h3>
                    <hr>
                    <?php
                    $sql= "SELECT COALESCE(COUNT(review_status), 0) AS totalpend FROM safety_event
                    WHERE driver_id=112 AND review_status = 'PENDING'";
                    $stmt=$conn->prepare($sql);
                    $stmt->execute();
                    $result=$stmt->get_result();
                    $total=$result->fetch_assoc();
                    echo $total['totalpend'];
                    ?>
                    <!--php-->
                </div>
            </section>

            <!--section for incident list-->
            <section class="incidentlist">
                <h3>Incident List</h3>

                <table>
                    <thead>
                        <tr>
                            <th>TIME</th>
                            <th>VEHICLE</th>
                            <th>EVENT TYPE</th>
                            <th>SEVERITY</th>
                            <th>STATUS</th>
                        </tr>
                    </thead>

                    <tbody>
                        <?php
                        $sql="SELECT se.timestamp, se.VIN, et.event_type_name, sl.level_name, se.review_status 
                        FROM safety_event AS se JOIN event_type AS et ON se.event_type_id = et.event_type_id
                        JOIN severity_level AS sl ON sl.severity_id=se.severity_id
                        WHERE driver_id=112
                        ORDER BY se.timestamp DESC";
                        $result= $conn->query($sql);
                        while ($arow=$result->fetch_assoc()) {

                            echo "<tr>";
                            echo "<td>".$arow['timestamp']."</td>";
                            echo "<td>".$arow['VIN']."</td>";
                            echo "<td>".$arow['event_type_name']."</td>";
                            echo "<td>".$arow['level_name']."</td>";
                            echo "<td>".$arow['review_status']."</td>";
                            echo "</tr>"; }
                            ?>
                    </tbody>

                </table>

            </section>
            <footer>
                <p>&copy; 2026 G63 Fleet Management System. All rights reserved.</p>
            </footer>
        </main>

    </body>
