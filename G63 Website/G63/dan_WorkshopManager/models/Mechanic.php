<?php

class Mechanic
{
    public function __construct(private mysqli $conn) {}

    public function getAll(): array
    {
        $sql = "
            SELECT
                m.mechanic_id,
                m.full_name,
                m.depot_id,
                d.depot_name,
                d.depot_location,
                m.employment_status,
                (SELECT COUNT(*)
                 FROM mechanic_certification_record AS mcr
                 WHERE mcr.mechanic_id = m.mechanic_id) AS certification_count,
                (SELECT COUNT(*)
                 FROM mechanic_certification_record AS mcr
                 WHERE mcr.mechanic_id = m.mechanic_id
                   AND mcr.expiration_date >= CURDATE()) AS valid_certification_count,
                (SELECT COUNT(*)
                 FROM mechanic_activity AS ma
                 WHERE ma.mechanic_id = m.mechanic_id) AS assigned_activity_count,
                (SELECT COALESCE(ROUND(SUM(TIME_TO_SEC(ma.labour_hours)) / 3600, 2), 0)
                 FROM mechanic_activity AS ma
                 WHERE ma.mechanic_id = m.mechanic_id) AS total_labour_hours
            FROM mechanic AS m
            INNER JOIN depot AS d
                ON d.depot_id = m.depot_id
            ORDER BY m.full_name
        ";

        $result = $this->conn->query($sql);
        if ($result === false) {
            throw new RuntimeException('Database query failed: ' . $this->conn->error);
        }
        return $result->fetch_all(MYSQLI_ASSOC);
    }

    public function getById(int $mechanicId): ?array
    {
        $stmt = $this->prepare(
            'SELECT
                m.mechanic_id, m.full_name, m.depot_id,
                d.depot_name, d.depot_location, m.employment_status
             FROM mechanic AS m
             INNER JOIN depot AS d ON d.depot_id = m.depot_id
             WHERE m.mechanic_id = ?'
        );

        $stmt->bind_param('i', $mechanicId);
        $stmt->execute();

        return $stmt->get_result()->fetch_assoc() ?: null;
    }

    public function create(array $data): bool
    {
        $stmt = $this->prepare(
            'INSERT INTO mechanic (full_name, depot_id, employment_status)
             VALUES (?, ?, ?)'
        );

        $fullName         = trim((string) $data['full_name']);
        $depotId          = (int) $data['depot_id'];
        $employmentStatus = (int) (bool) $data['employment_status'];

        $stmt->bind_param('sii', $fullName, $depotId, $employmentStatus);

        return $stmt->execute();
    }

    public function update(int $mechanicId, array $data): bool
    {
        $stmt = $this->prepare(
            'UPDATE mechanic
             SET full_name = ?, depot_id = ?, employment_status = ?
             WHERE mechanic_id = ?'
        );

        $fullName         = trim((string) $data['full_name']);
        $depotId          = (int) $data['depot_id'];
        $employmentStatus = (int) (bool) $data['employment_status'];

        $stmt->bind_param('siii', $fullName, $depotId, $employmentStatus, $mechanicId);

        return $stmt->execute();
    }

    public function delete(int $mechanicId): bool
    {
        $stmt = $this->prepare(
            'SELECT COUNT(*) AS job_count FROM mechanic_activity WHERE mechanic_id = ?'
        );

        $stmt->bind_param('i', $mechanicId);
        $stmt->execute();

        $count = (int) $stmt->get_result()->fetch_assoc()['job_count'];

        if ($count > 0) {
            return false;
        }

        $stmt = $this->prepare('DELETE FROM mechanic_certification_record WHERE mechanic_id = ?');
        $stmt->bind_param('i', $mechanicId);
        $stmt->execute();

        $stmt = $this->prepare('DELETE FROM mechanic WHERE mechanic_id = ?');
        $stmt->bind_param('i', $mechanicId);

        return $stmt->execute();
    }

    public function getCertifications(int $mechanicId): array
    {
        $sql = "
            SELECT
                mcr.mechanic_certification_id,
                mc.mechanic_certification_name,
                mcr.expiration_date
            FROM mechanic_certification_record AS mcr
            INNER JOIN mechanic_certification AS mc
                ON mc.mechanic_certification_id = mcr.mechanic_certification_id
            WHERE mcr.mechanic_id = ?
            ORDER BY mcr.expiration_date DESC
        ";

        $stmt = $this->prepare($sql);
        $stmt->bind_param('i', $mechanicId);
        $stmt->execute();

        return $stmt->get_result()->fetch_all(MYSQLI_ASSOC);
    }

    public function getAssignments(int $mechanicId): array
    {
        $stmt = $this->prepare(
            'SELECT
                ma.job_id, ma.activity_id,
                ROUND(TIME_TO_SEC(ma.labour_hours) / 3600, 2) AS labour_hours_decimal,
                mj.VIN, v.registration_number, mj.date_opened, mj.job_status,
                at.activity_type_name
             FROM mechanic_activity AS ma
             INNER JOIN maintenance_job AS mj ON mj.job_id = ma.job_id
             INNER JOIN vehicle AS v ON v.VIN = mj.VIN
             INNER JOIN maintenance_activity AS a
                ON a.job_id = ma.job_id AND a.activity_id = ma.activity_id
             INNER JOIN activity_type AS at
                ON at.activity_type_id = a.activity_type_id
             WHERE ma.mechanic_id = ?
             ORDER BY mj.date_opened DESC'
        );

        $stmt->bind_param('i', $mechanicId);
        $stmt->execute();

        return $stmt->get_result()->fetch_all(MYSQLI_ASSOC);
    }

    public function getLookupOptions(): array
    {
        return [
            'depots' => $this->conn
                ->query('SELECT depot_id, depot_name
                          FROM depot
                          ORDER BY depot_name')
                ->fetch_all(MYSQLI_ASSOC),

            'certifications' => $this->conn
                ->query('SELECT mechanic_certification_id, mechanic_certification_name
                          FROM mechanic_certification
                          ORDER BY mechanic_certification_name')
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
