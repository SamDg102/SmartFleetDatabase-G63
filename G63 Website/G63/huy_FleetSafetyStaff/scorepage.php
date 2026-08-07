<?php
session_start();
include '../db.php';
include '../function.php';

checkin('fleet safety staff');
$currentPage = 'safety-scores';

$score_month = $_GET['month'] ?? date('Y-m');

$sqlScores = "SELECT d.driver_id, d.full_name, dp.depot_name, mss.score_month, mss.final_score, mss.action_required
              FROM monthly_safety_score mss
              JOIN driver d ON d.driver_id = mss.driver_id
              JOIN depot dp ON dp.depot_id = d.depot_id
              WHERE DATE_FORMAT(mss.score_month, '%Y-%m') = '$score_month'
              ORDER BY mss.final_score ASC";
$resultScores = $conn->query($sqlScores);
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>Monthly Safety Scores</title>
    <link rel="stylesheet" href="styles/navbar.css">
    <link rel="stylesheet" href="styles/staff.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.1/css/all.min.css">
</head>
<body>
    <?php include 'includes/navbar.php'; ?>
    <main class="scorepage">
        <div class="topbar">
            <div>
                <h1>Monthly Safety Scores</h1>
                <p>Monthly Safety Scores for Drivers</p>
            </div>
            <form method="GET">
                <input type="month" name="month" value="<?= htmlspecialchars($score_month) ?>" onchange="this.form.submit()">
            </form>
        </div>
        <hr>
        <div class="table-wrap">
            <table>
                <thead>
                    <tr>
                        <th>Driver ID</th>
                        <th>Full Name</th>
                        <th>Depot</th>
                        <th>Score Month</th>
                        <th>Final Score</th>
                        <th>Action Required</th>
                    </tr>
                </thead>
                <tbody>
                    <?php if ($resultScores && $resultScores->num_rows > 0): ?>
                        <?php while ($r = $resultScores->fetch_assoc()): ?>
                            <tr>
                                <td>D-<?= $r['driver_id'] ?></td>
                                <td><?= htmlspecialchars($r['full_name']) ?></td>
                                <td><?= htmlspecialchars($r['depot_name']) ?></td>
                                <td><?= $r['score_month'] ?></td>
                                <td><strong><?= $r['final_score'] ?></strong> / 100</td>
                                <td><span class="badge badge-<?= $r['final_score'] < 75 ? 'critical' : 'completed' ?>"><?= htmlspecialchars($r['action_required']) ?></span></td>
                            </tr>
                        <?php endwhile; ?>
                    <?php else: ?>
                        <tr><td colspan="6" style="text-align: center;">No safety scores found for <?= htmlspecialchars($score_month) ?>.</td></tr>
                    <?php endif; ?>
                </tbody>
            </table>
        </div>
    </main>
</body>
</html>