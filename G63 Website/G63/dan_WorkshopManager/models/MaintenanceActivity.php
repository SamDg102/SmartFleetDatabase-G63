<?php

class MaintenanceActivity
{
    public function __construct(private mysqli $conn) {}

    public function getOne(int $jobId, int $activityId): ?array
    {
        $stmt = $this->prepare(
            'SELECT ma.job_id, ma.activity_id, ma.activity_type_id,
                    at.activity_type_name, ma.diagnostic_result,
                    ma.repeat_fault, ma.warranty_applicable
             FROM maintenance_activity AS ma
             INNER JOIN activity_type AS at
                 ON at.activity_type_id = ma.activity_type_id
             WHERE ma.job_id = ? AND ma.activity_id = ?'
        );

        $stmt->bind_param('ii', $jobId, $activityId);
        $stmt->execute();

        return $stmt->get_result()->fetch_assoc() ?: null;
    }

    public function create(int $jobId, array $data): bool
    {
        $stmt = $this->prepare(
            'SELECT COALESCE(MAX(activity_id), 0) + 1 AS next_id
             FROM maintenance_activity
             WHERE job_id = ?'
        );
        $stmt->bind_param('i', $jobId);
        $stmt->execute();
        $nextActivityId = (int) $stmt->get_result()->fetch_assoc()['next_id'];

        $stmt = $this->prepare(
            'INSERT INTO maintenance_activity (
                job_id, activity_id, activity_type_id,
                diagnostic_result, repeat_fault, warranty_applicable
             )
             VALUES (?, ?, ?, ?, ?, ?)'
        );

        $activityTypeId     = (int) $data['activity_type_id'];
        $diagnosticResult   = trim((string) $data['diagnostic_result']);
        $repeatFault        = (int) (bool) $data['repeat_fault'];
        $warrantyApplicable = trim((string) $data['warranty_applicable']);

        $stmt->bind_param(
            'iiisis',
            $jobId,
            $nextActivityId,
            $activityTypeId,
            $diagnosticResult,
            $repeatFault,
            $warrantyApplicable
        );

        return $stmt->execute();
    }

    public function update(int $jobId, int $activityId, array $data): bool
    {
        $stmt = $this->prepare(
            'UPDATE maintenance_activity
             SET activity_type_id = ?, diagnostic_result = ?,
                 repeat_fault = ?, warranty_applicable = ?
             WHERE job_id = ? AND activity_id = ?'
        );

        $activityTypeId     = (int) $data['activity_type_id'];
        $diagnosticResult   = trim((string) $data['diagnostic_result']);
        $repeatFault        = (int) (bool) $data['repeat_fault'];
        $warrantyApplicable = trim((string) $data['warranty_applicable']);

        $stmt->bind_param(
            'isisii',
            $activityTypeId,
            $diagnosticResult,
            $repeatFault,
            $warrantyApplicable,
            $jobId,
            $activityId
        );

        return $stmt->execute();
    }

    public function delete(int $jobId, int $activityId): bool
    {
        $stmt = $this->prepare(
            'SELECT
                (SELECT COUNT(*) FROM mechanic_activity
                 WHERE job_id = ? AND activity_id = ?) AS mechanic_count,
                (SELECT COUNT(*) FROM activity_part
                 WHERE job_id = ? AND activity_id = ?) AS part_count'
        );

        $stmt->bind_param('iiii', $jobId, $activityId, $jobId, $activityId);
        $stmt->execute();

        $dependencies = $stmt->get_result()->fetch_assoc();

        if ((int) $dependencies['mechanic_count'] > 0 || (int) $dependencies['part_count'] > 0) {
            return false;
        }

        $stmt = $this->prepare(
            'DELETE FROM maintenance_activity WHERE job_id = ? AND activity_id = ?'
        );
        $stmt->bind_param('ii', $jobId, $activityId);

        return $stmt->execute();
    }

    /**
     * Parts consumed by this activity.
     */
    public function getPartsUsed(int $jobId, int $activityId): array
    {
        $sql = "
            SELECT ap.part_id, ap.quantity_used, p.primary_unit_cost,
                   (ap.quantity_used * p.primary_unit_cost) AS line_cost
            FROM activity_part AS ap
            INNER JOIN part AS p ON p.part_id = ap.part_id
            WHERE ap.job_id = ? AND ap.activity_id = ?
        ";

        $stmt = $this->prepare($sql);
        $stmt->bind_param('ii', $jobId, $activityId);
        $stmt->execute();

        return $stmt->get_result()->fetch_all(MYSQLI_ASSOC);
    }

    public function addPart(int $jobId, int $activityId, int $partId, int $quantityUsed): bool
    {
        $stmt = $this->prepare(
            'INSERT INTO activity_part (job_id, activity_id, part_id, quantity_used)
             VALUES (?, ?, ?, ?)
             ON DUPLICATE KEY UPDATE quantity_used = quantity_used + VALUES(quantity_used)'
        );

        $stmt->bind_param('iiii', $jobId, $activityId, $partId, $quantityUsed);

        return $stmt->execute();
    }

    public function removePart(int $jobId, int $activityId, int $partId): bool
    {
        $stmt = $this->prepare(
            'DELETE FROM activity_part WHERE job_id = ? AND activity_id = ? AND part_id = ?'
        );

        $stmt->bind_param('iii', $jobId, $activityId, $partId);

        return $stmt->execute();
    }

    private function prepare(string $sql): mysqli_stmt
    {
        $stmt = $this->conn->prepare($sql);

        if ($stmt === false) {
            throw new RuntimeException('Unable to prepare statement: ' . $this->conn->error);
        }

        return $stmt;
    }
}
