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
                m.workshop_id,
                d.depot_name,
                d.depot_location,
                m.employment_status
            FROM mechanic AS m
            INNER JOIN workshop AS w
                ON w.workshop_id = m.workshop_id
            INNER JOIN depot AS d
                ON d.depot_id = w.depot_id
            ORDER BY m.full_name
        ";

        $result = $this->conn->query($sql);

        if ($result === false) {
            throw new RuntimeException('Unable to retrieve mechanics: ' . $this->conn->error);
        }

        return $result->fetch_all(MYSQLI_ASSOC);
    }

    public function getById(int $mechanicId): ?array
    {
        $stmt = $this->prepare(
            'SELECT
                m.mechanic_id, m.full_name, m.workshop_id,
                d.depot_name, d.depot_location, m.employment_status
             FROM mechanic AS m
             INNER JOIN workshop AS w ON w.workshop_id = m.workshop_id
             INNER JOIN depot AS d ON d.depot_id = w.depot_id
             WHERE m.mechanic_id = ?'
        );

        $stmt->bind_param('i', $mechanicId);
        $stmt->execute();

        return $stmt->get_result()->fetch_assoc() ?: null;
    }

    public function create(array $data): bool
    {
        $stmt = $this->prepare(
            'INSERT INTO mechanic (full_name, workshop_id, employment_status)
             VALUES (?, ?, ?)'
        );

        $fullName         = trim((string) $data['full_name']);
        $workshopId       = (int) $data['workshop_id'];
        $employmentStatus = (int) (bool) $data['employment_status'];

        $stmt->bind_param('sii', $fullName, $workshopId, $employmentStatus);

        return $stmt->execute();
    }

    public function update(int $mechanicId, array $data): bool
    {
        $stmt = $this->prepare(
            'UPDATE mechanic
             SET full_name = ?, workshop_id = ?, employment_status = ?
             WHERE mechanic_id = ?'
        );

        $fullName         = trim((string) $data['full_name']);
        $workshopId       = (int) $data['workshop_id'];
        $employmentStatus = (int) (bool) $data['employment_status'];

        $stmt->bind_param('siii', $fullName, $workshopId, $employmentStatus, $mechanicId);

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
                ma.job_id, ma.activity_id, ma.labour_hours AS labour_hours_decimal,
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
            'workshops' => $this->conn
                ->query('SELECT w.workshop_id, d.depot_name
                          FROM workshop AS w
                          INNER JOIN depot AS d ON d.depot_id = w.depot_id
                          ORDER BY d.depot_name')
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
