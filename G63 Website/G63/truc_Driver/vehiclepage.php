<?php
session_start();

require_once  '../db.php';
include '../function.php';

checkin('driver');
?>

<!DOCTYPE html>
<html lang="en">
    <head>
        <meta charset="UTF-8">
        <meta name="description" content="Driver dashboard of G63 Fleet Managment System">
        <meta name="keywords" content="Driver, Fleet, Vehicle">
        <meta name="author" content="Dang Nguyen Xuan Truc">
        <meta name="viewport" content="width=device-width, initial-scale=1.0">
    
        <title>G63 Driver Dashboard</title>
    
        <link rel="preconnect" href="https://fonts.googleapis.com">
        <link rel="stylesheet" href="https://fonts.googleapis.com/css2?family=Ubuntu:wght@400;500;700&display=swap">
        <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.1/css/all.min.css">
        <link rel="stylesheet" href="styles/navbar.css">
        <link rel="stylesheet" href="styles/vehiclepage.css">
    </head>

    <body>
        <?php $currentPage = 'vehiclepage';
        include 'includes/navbar.php';
        ?>

        <main class="vehiclepage">
            <header>
                <h1>Assigned Vehicle</h1>
                <hr>
            </header>
            <!--section for miniboxes on the top of UI
            that show info about the assigned vehicle-->
            <section class="miniboxes">
                <div class="registration">
                    <h3>Total Vehicle</h3>
                    <hr>
                    <?php
                    $sql= "SELECT COUNT(*) AS total 
                    FROM vehicle_driver_assignment
                    WHERE driver_id=112
                    AND end_date IS NULL";
                    $result=$conn->query($sql);
                    $total=$result->fetch_assoc();
                    echo $total['total'];
                    ?>
                    <!--php registration value-->
                </div>

            </section>

            <!--section for vehicle maintenance history-->
            <section class="maintenancelog">
                <h3>Vehicle List</h3>

                <table>
                    <thead>
                        <tr>
                            <th>START DATE</th>
                            <th>END DATE</th>
                            <th>VEHICLE</th>
                            <th>CATEGORY</th>
                        </tr>
                    </thead>

                    <tbody>
                        <?php 
                        $sql= "SELECT vda.start_date, vda.end_date, vda.VIN, vc.category_name 
                        FROM vehicle_driver_assignment AS vda
                        JOIN vehicle as v ON v.VIN = vda.VIN
                        JOIN vehicle_category as vc ON vc.category_id=v.category_id
                        WHERE vda.driver_id=112
                        ORDER BY vda.start_date DESC";
                        $result=$conn->query($sql);
                        while ($arow=$result->fetch_assoc()) {
                            echo "<tr>";
                            echo "<td>".$arow['start_date']."</td>";
                            echo "<td>".$arow['end_date']."</td>";
                            echo "<td>".$arow['VIN']."</td>";
                            echo "<td>".$arow['category_name']."</td>";
                            echo "</tr>";
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
