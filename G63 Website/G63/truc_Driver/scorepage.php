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
        <meta name="keywords" content="Driver, Fleet">
        <meta name="author" content="Dang Nguyen Xuan Truc">
        <meta name="viewport" content="width=device-width, initial-scale=1.0">
    
        <title>G63 Driver Dashboard</title>
    
        <link rel="preconnect" href="https://fonts.googleapis.com">
        <link rel="stylesheet" href="https://fonts.googleapis.com/css2?family=Ubuntu:wght@400;500;700&display=swap">
        <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.1/css/all.min.css">
        <link rel="stylesheet" href="styles/navbar.css">
        <link rel="stylesheet" href="styles/scorepage.css">
    </head>

    <body>
        <?php $currentPage = 'scorepage';
        include 'includes/navbar.php';
        ?>

        <main class="scorepage">
            <header>
                <h1>Monthly Safety Score</h1>
                <hr>
            </header>
            <!--section for miniboxes on the top of UI
            that show info about driver score-->
            <section class="miniboxes">
                <div class="monthscore">
                    <h3>Month</h3>
                    <hr>
                    <?php
                    $sql= "SELECT MONTHNAME(score_month) AS Cmonth
                    FROM monthly_safety_score 
                    WHERE driver_id=112
                    ORDER BY score_month DESC LIMIT 1";
                    $stmt= $conn->prepare($sql);
                    $stmt->execute();
                    $result=$stmt->get_result();
                    $month=$result->fetch_assoc();
                    echo $month["Cmonth"];
                    ?>
                    <!--php monthly score value-->
                </div>

                <div class="score">
                    <h3>Score</h3>
                    <hr>
                    <?php
                    $sql= "SELECT final_score FROM monthly_safety_score
                    WHERE driver_id=112 
                    ORDER BY score_month DESC LIMIT 1";
                    $stmt= $conn->prepare($sql);
                    $stmt->execute();
                    $result=$stmt->get_result();
                    $ascore=$result->fetch_assoc();
                    echo $ascore["final_score"]
                    ?>
                    <!--php-->
                </div>

                <div class="avg">
                    <h3>Average Score</h3>
                    <hr>
                    <?php
                    $sql= "SELECT COALESCE(ROUND(AVG(final_score), 2), 0) AS average_score
                    FROM monthly_safety_score
                    WHERE driver_id=112
                    AND YEAR(score_month) = YEAR(CURDATE())";
                    
                    $stmt= $conn->prepare($sql);
                    $stmt->execute();
                    $result=$stmt->get_result();
                    $avg=$result->fetch_assoc();
                    echo $avg['average_score'];
                    ?>
                    <!--php-->
                </div>

            </section>

            <!--section for monthly score history-->
            <section class="scorehistory">
                <h3>Score History</h3>
                
                <table>
                    <thead>
                        <tr>
                            <th>MONTH</th>
                            <th>FINAL SCORE</th>
                            <th>ACTION REQUIRED</th>
                        </tr>
                    </thead>

                    <tbody>
                        <?php
                        $sql="SELECT YEAR(score_month) AS score_year,
                        MONTHNAME(score_month) AS cmonth, final_score, action_required
                        FROM monthly_safety_score
                        WHERE driver_id=112
                        ORDER BY score_month DESC";
                        $result= $conn->query($sql);
                        $yearnow=null;
                        while ($arow=$result->fetch_assoc()) {
                            
                            if ($yearnow!=$arow['score_year']) {
                                echo "<tr>";
                                echo "<td><strong>".$arow['score_year']."</strong></td>";
                                echo "</tr>";
                                $yearnow= $arow['score_year'];
                            }

                            echo "<tr>";
                            echo "<td>".$arow['cmonth']."</td>";
                            echo "<td>".$arow['final_score']."</td>";
                            echo "<td>".($arow['action_required'] ?? "None")."</td>";
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
