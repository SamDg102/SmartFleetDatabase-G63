<?php

require_once __DIR__ . '/config/db.php';
require '../function.php';

checkin('workshop manager');

$pageTitle = 'Workshop Reports';
$pageCss = 'reports';
$currentPage = 'reports';

$jobsByStatus = [];
$vehicleCosts = [];
$repeatFaults = [];
$mechanicHours = [];
$errorMessage = null;

try {
    $result = $conn->query("
        SELECT
            job_status,
            COUNT(*) AS job_count,
            COALESCE(
                ROUND(SUM(TIME_TO_SEC(downtime_hours)) / 3600, 2),
                0
            ) AS total_downtime_hours
        FROM maintenance_job
        GROUP BY job_status
        ORDER BY FIELD(
            job_status,
            'NOT STARTED',
            'IN PROGRESS',
            'DONE'
        )
    ");

    if ($result === false) {
        throw new RuntimeException($conn->error);
    }

    $jobsByStatus = $result->fetch_all(MYSQLI_ASSOC);

    $result = $conn->query("
        SELECT
            v.VIN,
            v.registration_number,

            (
                SELECT COUNT(*)
                FROM maintenance_job AS mj
                WHERE mj.VIN = v.VIN
            ) AS total_jobs,

            (
                SELECT COALESCE(
                    ROUND(
                        SUM(TIME_TO_SEC(mj2.downtime_hours)) / 3600,
                        2
                    ),
                    0
                )
                FROM maintenance_job AS mj2
                WHERE mj2.VIN = v.VIN
            ) AS total_downtime_hours,

            (
                SELECT COALESCE(
                    SUM(ap.quantity_used * p.primary_unit_cost),
                    0
                )
                FROM maintenance_job AS mj3
                INNER JOIN maintenance_activity AS ma
                    ON ma.job_id = mj3.job_id
                INNER JOIN activity_part AS ap
                    ON ap.job_id = ma.job_id
                   AND ap.activity_id = ma.activity_id
                INNER JOIN part AS p
                    ON p.part_id = ap.part_id
                WHERE mj3.VIN = v.VIN
            ) AS estimated_parts_cost

        FROM vehicle AS v
        ORDER BY estimated_parts_cost DESC, total_jobs DESC
    ");

    if ($result === false) {
        throw new RuntimeException($conn->error);
    }

    $vehicleCosts = $result->fetch_all(MYSQLI_ASSOC);

    $result = $conn->query("
        SELECT
            at.activity_type_name,
            COUNT(*) AS repeat_fault_count
        FROM maintenance_activity AS ma
        INNER JOIN activity_type AS at
            ON at.activity_type_id = ma.activity_type_id
        WHERE ma.repeat_fault = 1
        GROUP BY at.activity_type_id, at.activity_type_name
        ORDER BY repeat_fault_count DESC, at.activity_type_name
    ");

    if ($result === false) {
        throw new RuntimeException($conn->error);
    }

    $repeatFaults = $result->fetch_all(MYSQLI_ASSOC);

    $result = $conn->query("
        SELECT
            m.mechanic_id,
            m.full_name,
            COUNT(*) AS assigned_activity_count,
            COALESCE(
                ROUND(SUM(TIME_TO_SEC(ma.labour_hours)) / 3600, 2),
                0
            ) AS total_labour_hours
        FROM mechanic AS m
        LEFT JOIN mechanic_activity AS ma
            ON ma.mechanic_id = m.mechanic_id
        GROUP BY m.mechanic_id, m.full_name
        ORDER BY total_labour_hours DESC, m.full_name
    ");

    if ($result === false) {
        throw new RuntimeException($conn->error);
    }

    $mechanicHours = $result->fetch_all(MYSQLI_ASSOC);
} catch (Throwable $exception) {
    $errorMessage = $exception->getMessage();
}

$statusLabels = array_column($jobsByStatus, 'job_status');
$statusCounts = array_map(
    static fn(array $row): int => (int) $row['job_count'],
    $jobsByStatus
);

include __DIR__ . '/includes/header.php';
include __DIR__ . '/includes/navbar.php';
?>

<main class="reports">
    <header>
        <h1>Workshop Reports</h1>
        <p>
            Maintenance workload, downtime, costs, repeat faults and
            mechanic labour.
        </p>
        <hr>
    </header>

    <?php if ($errorMessage !== null): ?>
        <p role="alert">
            Unable to load reports:
            <?= htmlspecialchars($errorMessage) ?>
        </p>
    <?php else: ?>
        <section>
            <h2>Jobs by Status</h2>

            <canvas
                id="jobsByStatusChart"
                width="600"
                height="250"
                aria-label="Maintenance jobs by status"></canvas>

            <table>
                <thead>
                    <tr>
                        <th>Status</th>
                        <th>Jobs</th>
                        <th>Total Downtime (hrs)</th>
                    </tr>
                </thead>
                <tbody>
                    <?php if ($jobsByStatus === []): ?>
                        <tr>
                            <td colspan="3">No job data found.</td>
                        </tr>
                    <?php else: ?>
                        <?php foreach ($jobsByStatus as $row): ?>
                            <tr>
                                <td>
                                    <?= htmlspecialchars(
                                        $row['job_status']
                                    ) ?>
                                </td>
                                <td><?= (int) $row['job_count'] ?></td>
                                <td>
                                    <?= htmlspecialchars(
                                        (string) $row['total_downtime_hours']
                                    ) ?>
                                </td>
                            </tr>
                        <?php endforeach; ?>
                    <?php endif; ?>
                </tbody>
            </table>
        </section>

        <section>
            <h2>Vehicle Maintenance Summary</h2>
            <p>
                Cost is estimated from primary part unit cost only because
                the current database does not store labour cost or a
                job-level total cost.
            </p>

            <table>
                <thead>
                    <tr>
                        <th>Vehicle</th>
                        <th>VIN</th>
                        <th>Total Jobs</th>
                        <th>Total Downtime (hrs)</th>
                        <th>Estimated Parts Cost</th>
                    </tr>
                </thead>
                <tbody>
                    <?php if ($vehicleCosts === []): ?>
                        <tr>
                            <td colspan="5">No vehicle data found.</td>
                        </tr>
                    <?php else: ?>
                        <?php foreach ($vehicleCosts as $row): ?>
                            <tr>
                                <td>
                                    <a href="vehicle_view.php?VIN=<?= urlencode($row['VIN']) ?>">
                                        <?= htmlspecialchars(
                                            $row['registration_number']
                                        ) ?>
                                    </a>
                                </td>
                                <td><?= htmlspecialchars($row['VIN']) ?></td>
                                <td><?= (int) $row['total_jobs'] ?></td>
                                <td>
                                    <?= htmlspecialchars(
                                        (string) $row['total_downtime_hours']
                                    ) ?>
                                </td>
                                <td>
                                    <?= number_format(
                                        (float) $row['estimated_parts_cost'],
                                        0
                                    ) ?>
                                    VND
                                </td>
                            </tr>
                        <?php endforeach; ?>
                    <?php endif; ?>
                </tbody>
            </table>
        </section>

        <section>
            <h2>Repeat Faults by Activity Type</h2>
            <table>
                <thead>
                    <tr>
                        <th>Activity Type</th>
                        <th>Repeat Fault Count</th>
                    </tr>
                </thead>
                <tbody>
                    <?php if ($repeatFaults === []): ?>
                        <tr>
                            <td colspan="2">
                                No repeat faults recorded.
                            </td>
                        </tr>
                    <?php else: ?>
                        <?php foreach ($repeatFaults as $row): ?>
                            <tr>
                                <td>
                                    <?= htmlspecialchars(
                                        $row['activity_type_name']
                                    ) ?>
                                </td>
                                <td>
                                    <?= (int) $row['repeat_fault_count'] ?>
                                </td>
                            </tr>
                        <?php endforeach; ?>
                    <?php endif; ?>
                </tbody>
            </table>
        </section>

        <section>
            <h2>Mechanic Labour Hours</h2>
            <table>
                <thead>
                    <tr>
                        <th>Mechanic</th>
                        <th>Assigned Activities</th>
                        <th>Total Labour Hours</th>
                    </tr>
                </thead>
                <tbody>
                    <?php if ($mechanicHours === []): ?>
                        <tr>
                            <td colspan="3">No mechanic data found.</td>
                        </tr>
                    <?php else: ?>
                        <?php foreach ($mechanicHours as $row): ?>
                            <tr>
                                <td>
                                    <a href="mechanic_view.php?mechanic_id=<?= (int) $row['mechanic_id'] ?>">
                                        <?= htmlspecialchars(
                                            $row['full_name']
                                        ) ?>
                                    </a>
                                </td>
                                <td>
                                    <?= (int) $row['assigned_activity_count'] ?>
                                </td>
                                <td>
                                    <?= htmlspecialchars(
                                        (string) $row['total_labour_hours']
                                    ) ?>
                                </td>
                            </tr>
                        <?php endforeach; ?>
                    <?php endif; ?>
                </tbody>
            </table>
        </section>
    <?php endif; ?>
</main>

<?php if ($errorMessage === null): ?>
    <script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
    <script>
        const chartElement = document.getElementById('jobsByStatusChart');

        if (chartElement && typeof Chart !== 'undefined') {
            new Chart(chartElement, {
                type: 'bar',
                data: {
                    labels: <?= json_encode(
                                $statusLabels,
                                JSON_UNESCAPED_UNICODE
                            ) ?>,
                    datasets: [{
                        label: 'Maintenance jobs',
                        data: <?= json_encode($statusCounts) ?>
                    }]
                },
                options: {
                    responsive: true,
                    plugins: {
                        legend: {
                            display: false
                        }
                    },
                    scales: {
                        y: {
                            beginAtZero: true,
                            ticks: {
                                precision: 0
                            }
                        }
                    }
                }
            });
        }
    </script>
<?php endif; ?>

<?php
include __DIR__ . '/includes/footer.php';
$conn->close();
