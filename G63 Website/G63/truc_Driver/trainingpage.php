<?php
session_start();

require_once 'includes/conndb.php';
include '../function.php';

checkin('driver');
?>

<!DOCTYPE html>
<html lang="en">
    <head>
        <meta charset="UTF-8">
        <meta name="description" content="Driver dashboard of G63 Fleet Managment System">
        <meta name="keywords" content="Driver, Fleet, Training">
        <meta name="author" content="Dang Nguyen Xuan Truc">
        <meta name="viewport" content="width=device-width, initial-scale=1.0">
    
        <title>G63 Driver Dashboard</title>
    
        <link rel="preconnect" href="https://fonts.googleapis.com">
        <link rel="stylesheet" href="https://fonts.googleapis.com/css2?family=Ubuntu:wght@400;500;700&display=swap">
        <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.1/css/all.min.css">
        <link rel="stylesheet" href="styles/navbar.css">
        <link rel="stylesheet" href="styles/trainingpage.css">
    </head>

    <body>
        <?php $currentPage = 'trainingpage';
        include 'includes/navbar.php';
        ?>

        <main class="trainingpage">
            <header>
                <h1>Training Course</h1>
                <hr>
            </header>
            <!--section for miniboxes on the top of UI
            that show info about driver training-->
            <section class="miniboxes">
                <div class="completed">
                    <h3>Completed Courses</h3>
                    <hr>
                    <?php 
                    $sql= "SELECT COALESCE(COUNT(coaching_id),0) AS total FROM coaching_record
                    WHERE driver_id=112
                    AND status='COMPLETED'";
                    $stmt= $conn->prepare($sql);
                    $stmt-> execute();
                    $result= $stmt ->get_result();
                    $total=$result ->fetch_assoc();
                    echo $total["total"];
                    ?>
                    <!--php completed courses value-->
                </div>

                <div class="upcoming">
                    <h3>Upcoming Courses</h3>
                    <hr>
                    <?php  
                    $sql="SELECT assigned_date FROM coaching_record
                    WHERE driver_id=112
                    AND status='PENDING'";
                    $result= $conn->query($sql);
                    if ($result->num_rows>0){
                        $date=$result->fetch_assoc();
                        echo $date['assigned_date'];} else {
                        echo "None";
                        };
                    ?>
                    <!--php-->
                </div>
            </section>

            <!--section for training history-->
            <section class="traininghistory">
                <h3>Training History</h3>

                <table>
                    <thead>
                        <tr>
                            <th>DATE</th>
                            <th>TYPE</th>
                            <th>STATUS</th>
                        </tr>
                    </thead>

                    <tbody>
                        <?php 
                        $sql= "SELECT assigned_date, coaching_type, status
                        FROM coaching_record
                        WHERE driver_id=112";
                        $result=$conn->query($sql);
                        if ($result->num_rows==0){
                                echo "<tr>";
                                echo "<td>None</td>";
                                echo "</tr>";
                            } else {
                        while ($arow=$result->fetch_assoc()){

                            echo "<tr>";
                            echo "<td>".$arow["assigned_date"]."</td>";
                            echo "<td>".$arow["coaching_type"]."</td>";
                            echo "<td>".$arow["status"]."</td>";
                            echo "</tr>";}
                        }
                        ?>
                    </tbody>

                </table>

            </section>
            <footer>
                <p>&copy; 2026 G63 Fleet Management System. All rights reserved.</p>
            </footer>
        </main>

    </body>
