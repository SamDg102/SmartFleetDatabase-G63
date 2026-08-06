<?php

class PredictiveAlert
{
    public function __construct(private mysqli $conn) {}

    public function getAll(): array
    {
        $sql = "
            SELECT
                pa.alert_id, pa.VIN, v.registration_number,
                pa.alert_type_id, at.alert_name, at.description,
                pa.job_id, pa.timestamp, pa.action_taken
            FROM predictive_alert AS pa
            INNER JOIN vehicle AS v ON v.VIN = pa.VIN
            INNER JOIN alert_type AS at ON at.alert_type_id = pa.alert_type_id
            ORDER BY pa.timestamp DESC
        ";

        $result = $this->conn->query($sql);
        return $result->fetch_all(MYSQLI_ASSOC);
    }

    public function getById(int $alertId): ?array
    {
        $stmt = $this->prepare(
            'SELECT pa.alert_id, pa.VIN, v.registration_number,
                    pa.alert_type_id, at.alert_name, at.description,
                    pa.job_id, pa.timestamp, pa.action_taken
             FROM predictive_alert AS pa
             INNER JOIN vehicle AS v ON v.VIN = pa.VIN
             INNER JOIN alert_type AS at ON at.alert_type_id = pa.alert_type_id
             WHERE pa.alert_id = ?'
        );

        $stmt->bind_param('i', $alertId);
        $stmt->execute();

        return $stmt->get_result()->fetch_assoc() ?: null;
    }

    public function create(array $data): bool
    {
        $stmt = $this->prepare(
            'INSERT INTO predictive_alert (VIN, alert_type_id, job_id, timestamp, action_taken)
             VALUES (?, ?, ?, ?, ?)'
        );

        $vin         = trim((string) $data['VIN']);
        $alertTypeId = (int) $data['alert_type_id'];
        $jobId       = !empty($data['job_id']) ? (int) $data['job_id'] : null;
        $timestamp   = (string) $data['timestamp'];
        $actionTaken = trim((string) ($data['action_taken'] ?? ''));

        $stmt->bind_param('siiss', $vin, $alertTypeId, $jobId, $timestamp, $actionTaken);

        return $stmt->execute();
    }

    public function update(int $alertId, array $data): bool
    {
        $stmt = $this->prepare(
            'UPDATE predictive_alert
             SET job_id = ?, action_taken = ?
             WHERE alert_id = ?'
        );

        $jobId       = !empty($data['job_id']) ? (int) $data['job_id'] : null;
        $actionTaken = trim((string) ($data['action_taken'] ?? ''));

        $stmt->bind_param('isi', $jobId, $actionTaken, $alertId);

        return $stmt->execute();
    }

    public function delete(int $alertId): bool
    {
        $stmt = $this->prepare('DELETE FROM predictive_alert WHERE alert_id = ?');
        $stmt->bind_param('i', $alertId);

        return $stmt->execute();
    }

    public function getLookupOptions(): array
    {
        return [
            'alert_types' => $this->conn
                ->query('SELECT alert_type_id, alert_name FROM alert_type ORDER BY alert_name')
                ->fetch_all(MYSQLI_ASSOC),
        ];
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
