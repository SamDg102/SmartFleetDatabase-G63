<?php
session_start();

require_once 'includes/conndb.php';
include '../function.php';

checkin('admin');
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
        <link rel="stylesheet" href="styles/userlist.css">
    </head>

    <body>
        <?php 
        $currentPage = 'userlist';
        include 'includes/navbar.php';
        ?>

        <main class="userlist">
            <header>
                <h1>Search Employees</h1>
                <hr>
            </header>
            <!--section for miniboxes on the top of UI
            that show info about the assigned vehicle-->
            <section class="miniboxes"> 
                <div class="registration">
                    <h3>Total Employees</h3>
                    <hr>
                    <?php
                    $sql= "SELECT COUNT(*) AS total 
                    FROM user_account";
                    $result=$conn->query($sql);
                    $total=$result->fetch_assoc();
                    echo $total['total'];
                    ?>
                    <!--php registration value-->
                </div>
            </section>

            <!--section for vehicle maintenance history-->
            <section class="userquery">
                <h3>Vehicle List</h3>

                <table>
                    <thead>
                        <tr>
                            <th>Email</th>
                            <th>Role</th>
                        </tr>
                    </thead>

                    <tbody>
                        <?php 
                        $sql = "SELECT ua.email, ua.role FROM user_account AS ua";
                        $result = $conn->query($sql);

                        if (!$result) {
                            die("Query failed: " . $conn->error);
                        }

                        while ($arow = $result->fetch_assoc()) {
                            echo "<tr>";
                            echo "<td>" . $arow['email'] . "</td>";
                            echo "<td>" . $arow['role'] . "</td>";
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
