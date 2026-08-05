<?php

class MechanicActivity
{
    public function __construct(private mysqli $conn) {}

    public function hasRequiredCertification(int $mechanicId, int $jobId, int $activityId): bool
    {
        $stmt = $this->prepare(
            'SELECT COUNT(*) AS missing
             FROM activity_required_certification AS arc
             WHERE arc.job_id = ? AND arc.activity_id = ?
               AND NOT EXISTS (
                    SELECT 1 FROM mechanic_certification_record AS mcr
                    WHERE mcr.mechanic_id = ?
                      AND mcr.mechanic_certification_id = arc.mechanic_certification_id
                      AND mcr.expiration_date >= CURDATE()
               )'
        );

        $stmt->bind_param('iii', $jobId, $activityId, $mechanicId);
        $stmt->execute();

        $missing = (int) $stmt->get_result()->fetch_assoc()['missing'];

        return $missing === 0;
    }

    public function assign(int $jobId, int $activityId, int $mechanicId, string $labourHours): bool
    {
        if (!$this->hasRequiredCertification($mechanicId, $jobId, $activityId)) {
            return false;
        }

        $stmt = $this->prepare(
            'INSERT INTO mechanic_activity (mechanic_id, job_id, activity_id, labour_hours)
             VALUES (?, ?, ?, ?)
             ON DUPLICATE KEY UPDATE labour_hours = VALUES(labour_hours)'
        );

        $stmt->bind_param('iiis', $mechanicId, $jobId, $activityId, $labourHours);

        return $stmt->execute();
    }

    public function updateHours(int $jobId, int $activityId, int $mechanicId, string $labourHours): bool
    {
        $stmt = $this->prepare(
            'UPDATE mechanic_activity
             SET labour_hours = ?
             WHERE job_id = ? AND activity_id = ? AND mechanic_id = ?'
        );

        $stmt->bind_param('siii', $labourHours, $jobId, $activityId, $mechanicId);

        return $stmt->execute();
    }

    public function unassign(int $jobId, int $activityId, int $mechanicId): bool
    {
        $stmt = $this->prepare(
            'DELETE FROM mechanic_activity
             WHERE job_id = ? AND activity_id = ? AND mechanic_id = ?'
        );

        $stmt->bind_param('iii', $jobId, $activityId, $mechanicId);

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
