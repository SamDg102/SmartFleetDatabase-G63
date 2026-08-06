<?php
require __DIR__ . '/config/db.php';
require __DIR__ . '/models/MaintenanceJob.php';
$pageTitle = 'Maintenance Job';
$pageCss = 'maintenancejobs';
$currentPage = 'maintenancejobs';
$mode = $_GET['mode'] ?? 'view';
$jobId = filter_input(INPUT_GET, 'job_id', FILTER_VALIDATE_INT);
$job = null;
$errorMessage = null;

$jobModel = new MaintenanceJob($conn);
if ($mode !== 'create') {
    if (!$jobId) throw new InvalidArgumentException('Missing job ID.');
    $job = $jobModel->getById($jobId);
    if (!$job) throw new RuntimeException('Maintenance job not found.');
}
include __DIR__ . '/includes/header.php';
include __DIR__ . '/includes/navbar.php';
$isCreate = $mode === 'create';
?>
<main class="maintenancejobs">
    <header>
        <h1><?= $isCreate ? 'Add Maintenance Job' : 'Maintenance Job J-' . (int)$jobId ?></h1><a href="maintenancejobs.php">← Back to Jobs</a>
        <hr>
    </header>
    <?php if ($errorMessage): ?><p role="alert"><?= htmlspecialchars($errorMessage) ?></p><?php else: ?>
        <form method="post" action="actions/job_action.php"><input type="hidden" name="action" value="<?= $isCreate ? 'create' : 'update' ?>"><?php if (!$isCreate): ?><input type="hidden" name="job_id" value="<?= (int)$jobId ?>"><?php endif; ?>
            <label>VIN<input name="VIN" maxlength="17" required value="<?= htmlspecialchars($job['VIN'] ?? '') ?>"></label>
            <label>Service Bay ID<input type="number" min="1" name="service_bay_id" required value="<?= htmlspecialchars((string)($job['service_bay_id'] ?? '')) ?>"></label>
            <label>Date Opened<input type="date" name="date_opened" required value="<?= htmlspecialchars($job['date_opened'] ?? '') ?>"></label>
            <label>Date Closed<input type="date" name="date_closed" value="<?= htmlspecialchars($job['date_closed'] ?? '') ?>"></label>
            <label>Downtime Hours<input type="time" step="1" name="downtime_hours" required value="<?= htmlspecialchars($job['downtime_hours'] ?? '00:00:00') ?>"></label>
            <label>Status<select name="job_status" required><?php foreach (['NOT STARTED', 'IN PROGRESS', 'DONE'] as $status): ?><option value="<?= $status ?>" <?= ($job['job_status'] ?? '') === $status ? 'selected' : '' ?>><?= $status ?></option><?php endforeach; ?></select></label>
            <button type="submit"><?= $isCreate ? 'Create Job' : 'Save Changes' ?></button>
        </form>
        <?php if (!$isCreate && !empty($job['activities'])): ?><h2>Activities</h2>
            <table>
                <thead>
                    <tr>
                        <th>ID</th>
                        <th>Type</th>
                        <th>Diagnostic Result</th>
                        <th>Repeat Fault</th>
                        <th>Warranty</th>
                    </tr>
                </thead>
                <tbody><?php foreach ($job['activities'] as $a): ?><tr>
                            <td><?= htmlspecialchars((string)$a['activity_id']) ?></td>
                            <td><?= htmlspecialchars($a['activity_type_name'] ?? '') ?></td>
                            <td><?= htmlspecialchars($a['diagnostic_result'] ?? '') ?></td>
                            <td><?= !empty($a['repeat_fault']) ? 'Yes' : 'No' ?></td>
                            <td><?= htmlspecialchars($a['warranty_applicable'] ?? '') ?></td>
                        </tr><?php endforeach; ?></tbody>
            </table><?php endif; ?>
    <?php endif; ?>
</main><?php include __DIR__ . '/includes/footer.php';
        $conn->close(); ?>
