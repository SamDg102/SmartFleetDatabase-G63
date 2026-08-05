<?php

class Supplier
{
    public function __construct(private mysqli $conn) {}

    public function getAll(): array
    {
        $sql = "SELECT supplier_id, supplier_name, contact_info, delivery_lead_time
                FROM supplier
                ORDER BY supplier_name";

        $result = $this->conn->query($sql);

        if ($result === false) {
            throw new RuntimeException('Unable to retrieve suppliers: ' . $this->conn->error);
        }

        return $result->fetch_all(MYSQLI_ASSOC);
    }

    public function getById(int $supplierId): ?array
    {
        $stmt = $this->prepare(
            'SELECT supplier_id, supplier_name, contact_info, delivery_lead_time
             FROM supplier
             WHERE supplier_id = ?'
        );

        $stmt->bind_param('i', $supplierId);
        $stmt->execute();

        return $stmt->get_result()->fetch_assoc() ?: null;
    }

    public function create(array $data): bool
    {
        $stmt = $this->prepare(
            'INSERT INTO supplier (supplier_name, contact_info, delivery_lead_time)
             VALUES (?, ?, ?)'
        );

        $name        = trim((string) $data['supplier_name']);
        $contactInfo = trim((string) $data['contact_info']);
        $leadTime    = trim((string) ($data['delivery_lead_time'] ?? ''));

        $stmt->bind_param('sss', $name, $contactInfo, $leadTime);

        return $stmt->execute();
    }

    public function update(int $supplierId, array $data): bool
    {
        $stmt = $this->prepare(
            'UPDATE supplier
             SET supplier_name = ?, contact_info = ?, delivery_lead_time = ?
             WHERE supplier_id = ?'
        );

        $name        = trim((string) $data['supplier_name']);
        $contactInfo = trim((string) $data['contact_info']);
        $leadTime    = trim((string) ($data['delivery_lead_time'] ?? ''));

        $stmt->bind_param('sssi', $name, $contactInfo, $leadTime, $supplierId);

        return $stmt->execute();
    }

    public function delete(int $supplierId): bool
    {
        $stmt = $this->prepare(
            'SELECT COUNT(*) AS part_count
             FROM part
             WHERE primary_supplier_id = ? OR backup_supplier_id = ?'
        );

        $stmt->bind_param('ii', $supplierId, $supplierId);
        $stmt->execute();

        $count = (int) $stmt->get_result()->fetch_assoc()['part_count'];

        if ($count > 0) {
            return false;
        }

        $stmt = $this->prepare('DELETE FROM supplier WHERE supplier_id = ?');
        $stmt->bind_param('i', $supplierId);

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
