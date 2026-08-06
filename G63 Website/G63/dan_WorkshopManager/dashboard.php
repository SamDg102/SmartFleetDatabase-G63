<?php

require __DIR__ . '/config/db.php';

$pageTitle = 'Workshop Dashboard';
$pageCss = 'dashboard';
$currentPage = 'dashboard';

$stats = [
    'open_jobs' => 0,
    'vehicles_in_repair' => 0,
    'active_mechanics' => 0,
    'pending_alerts' => 0,
    'estimated_parts_cost' => 0
];
$recentJobs = [];
$errorMessage = null;
$statsSql = "
        SELECT
            (
                SELECT COUNT(*)
                FROM maintenance_job
                WHERE job_status <> 'DONE'
            ) AS open_jobs,

            (
                SELECT COUNT(DISTINCT VIN)
                FROM maintenance_job
                WHERE job_status <> 'DONE'
            ) AS vehicles_in_repair,

            (
                SELECT COUNT(*)
                FROM mechanic
                WHERE employment_status = 1
            ) AS active_mechanics,

            (
                SELECT COUNT(*)
                FROM predictive_alert
                WHERE action_taken IS NULL
                   OR TRIM(action_taken) = ''
            ) AS pending_alerts,

            (
                SELECT COALESCE(
                    SUM(ap.quantity_used * p.primary_unit_cost),
                    0
                )
                FROM activity_part AS ap
                INNER JOIN part AS p
                    ON p.part_id = ap.part_id
            ) AS estimated_parts_cost
    ";

$statsResult = $conn->query($statsSql);
$stats = $statsResult->fetch_assoc();

$recentSql = "
        SELECT
            mj.job_id,
            mj.VIN,
            v.registration_number,
            d.depot_name,
            mj.job_status,
            mj.date_opened,
            GROUP_CONCAT(
                DISTINCT m.full_name
                ORDER BY m.full_name
                SEPARATOR ', '
            ) AS mechanic_names
        FROM maintenance_job AS mj
        INNER JOIN vehicle AS v
            ON v.VIN = mj.VIN
        INNER JOIN workshop AS w
            ON w.workshop_id = mj.workshop_id
        INNER JOIN depot AS d
            ON d.depot_id = w.depot_id
        LEFT JOIN maintenance_activity AS act
            ON act.job_id = mj.job_id
        LEFT JOIN mechanic_activity AS ma
            ON ma.job_id = act.job_id
           AND ma.activity_id = act.activity_id
        LEFT JOIN mechanic AS m
            ON m.mechanic_id = ma.mechanic_id
        GROUP BY
            mj.job_id,
            mj.VIN,
            v.registration_number,
            d.depot_name,
            mj.job_status,
            mj.date_opened
        ORDER BY mj.date_opened DESC, mj.job_id DESC
        LIMIT 8
    ";

$recentResult = $conn->query($recentSql);
$recentJobs = $recentResult->fetch_all(MYSQLI_ASSOC);
include __DIR__ . '/includes/header.php';
include __DIR__ . '/includes/navbar.php';
?>

<main class="dashboard">
    <header>
        <h1>Workshop Dashboard</h1>
        <p>Overview of maintenance work, mechanics and predictive alerts.</p>
        <hr>
    </header>

    <?php if ($errorMessage !== null): ?>
        <p role="alert">
            Unable to load dashboard data:
            <?= htmlspecialchars($errorMessage) ?>
        </p>
    <?php else: ?>
        <section class="miniboxes" aria-label="Workshop summary">
            <div class="openjobs">
                <h3><?= (int) $stats['open_jobs'] ?></h3>
                <hr>
                <p>Open Jobs</p>
            </div>

            <div class="vehiclesinrepair">
                <h3><?= (int) $stats['vehicles_in_repair'] ?></h3>
                <hr>
                <p>Vehicles in Repair</p>
            </div>

            <div class="mechanics">
                <h3><?= (int) $stats['active_mechanics'] ?></h3>
                <hr>
                <p>Active Mechanics</p>
            </div>

            <div class="pendingalerts">
                <h3><?= (int) $stats['pending_alerts'] ?></h3>
                <hr>
                <p>Pending Alerts</p>
            </div>

            <div>
                <h3>
                    <?= number_format(
                        (float) $stats['estimated_parts_cost'],
                        0
                    ) ?>
                </h3>
                <hr>
                <p>Estimated Parts Cost (VND)</p>
            </div>
        </section>

        <section class="recentjobs">
            <h2>Recent Maintenance Jobs</h2>

            <table>
                <thead>
                    <tr>
                        <th>Job ID</th>
                        <th>Vehicle</th>
                        <th>Depot</th>
                        <th>Mechanics</th>
                        <th>Status</th>
                        <th>Start Date</th>
                        <th>Details</th>
                    </tr>
                </thead>
                <tbody>
                    <?php if ($recentJobs === []): ?>
                        <tr>
                            <td colspan="7">No maintenance jobs found.</td>
                        </tr>
                    <?php else: ?>
                        <?php foreach ($recentJobs as $job): ?>
                            <tr>
                                <td>J-<?= (int) $job['job_id'] ?></td>
                                <td>
                                    <?= htmlspecialchars(
                                        $job['registration_number']
                                    ) ?>
                                </td>
                                <td>
                                    <?= htmlspecialchars($job['depot_name']) ?>
                                </td>
                                <td>
                                    <?= htmlspecialchars(
                                        $job['mechanic_names']
                                            ?: 'Unassigned'
                                    ) ?>
                                </td>
                                <td>
                                    <?= htmlspecialchars($job['job_status']) ?>
                                </td>
                                <td>
                                    <?= htmlspecialchars($job['date_opened']) ?>
                                </td>
                                <td>
                                    <a href="job_view.php?job_id=<?= (int) $job['job_id'] ?>">
                                        View
                                    </a>
                                </td>
                            </tr>
                        <?php endforeach; ?>
                    <?php endif; ?>
                </tbody>
            </table>
        </section>
    <?php endif; ?>
</main>

<?php
include __DIR__ . '/includes/footer.php';
$conn->close();
