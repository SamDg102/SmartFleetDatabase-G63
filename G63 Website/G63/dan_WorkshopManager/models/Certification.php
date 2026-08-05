<?php

class Certification
{
    public function __construct(private mysqli $conn) {}

    public function getAll(): array
    {
        $sql = "SELECT mechanic_certification_id, mechanic_certification_name
                FROM mechanic_certification
                ORDER BY mechanic_certification_name";

        $result = $this->conn->query($sql);

        if ($result === false) {
            throw new RuntimeException('Unable to retrieve certifications: ' . $this->conn->error);
        }

        return $result->fetch_all(MYSQLI_ASSOC);
    }

    public function create(array $data): bool
    {
        $stmt = $this->prepare(
            'INSERT INTO mechanic_certification (mechanic_certification_name) VALUES (?)'
        );

        $name = trim((string) $data['mechanic_certification_name']);
        $stmt->bind_param('s', $name);

        return $stmt->execute();
    }

    public function update(int $certId, array $data): bool
    {
        $stmt = $this->prepare(
            'UPDATE mechanic_certification SET mechanic_certification_name = ?
             WHERE mechanic_certification_id = ?'
        );

        $name = trim((string) $data['mechanic_certification_name']);
        $stmt->bind_param('si', $name, $certId);

        return $stmt->execute();
    }

    public function delete(int $certId): bool
    {
        $stmt = $this->prepare(
            'SELECT COUNT(*) AS record_count FROM mechanic_certification_record
             WHERE mechanic_certification_id = ?'
        );

        $stmt->bind_param('i', $certId);
        $stmt->execute();

        $count = (int) $stmt->get_result()->fetch_assoc()['record_count'];

        if ($count > 0) {
            return false;
        }

        $stmt = $this->prepare(
            'DELETE FROM mechanic_certification WHERE mechanic_certification_id = ?'
        );
        $stmt->bind_param('i', $certId);

        return $stmt->execute();
    }

    public function assignToMechanic(int $mechanicId, int $certId, string $expirationDate): bool
    {
        $stmt = $this->prepare(
            'INSERT INTO mechanic_certification_record
                (mechanic_certification_id, mechanic_id, expiration_date)
             VALUES (?, ?, ?)
             ON DUPLICATE KEY UPDATE expiration_date = VALUES(expiration_date)'
        );

        $stmt->bind_param('iis', $certId, $mechanicId, $expirationDate);

        return $stmt->execute();
    }

    public function removeFromMechanic(int $mechanicId, int $certId): bool
    {
        $stmt = $this->prepare(
            'DELETE FROM mechanic_certification_record
             WHERE mechanic_id = ? AND mechanic_certification_id = ?'
        );

        $stmt->bind_param('ii', $mechanicId, $certId);

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
