<?php

class Part
{
    public function __construct(private mysqli $conn) {}

    public function getAll(): array
    {
        $sql = "SELECT
                    p.part_id,
                    p.primary_supplier_id,
                    sp.supplier_name AS primary_supplier_name,
                    p.primary_unit_cost,
                    p.backup_supplier_id,
                    sb.supplier_name AS backup_supplier_name,
                    p.backup_unit_cost
                FROM part AS p
                INNER JOIN supplier AS sp
                    ON sp.supplier_id = p.primary_supplier_id
                LEFT JOIN supplier AS sb
                    ON sb.supplier_id = p.backup_supplier_id
                ORDER BY p.part_id
                ";

        $result = $this->conn->query($sql);

        if ($result === false) {
            throw new RuntimeException('Unable to retrieve parts: ' . $this->conn->error);
        }

        return $result->fetch_all(MYSQLI_ASSOC);
    }

    public function getById(int $partId): ?array
    {
        $stmt = $this->prepare(
            'SELECT
                p.part_id,
                p.primary_supplier_id,
                sp.supplier_name AS primary_supplier_name,
                p.primary_unit_cost,
                p.backup_supplier_id,
                sb.supplier_name AS backup_supplier_name,
                p.backup_unit_cost
             FROM part AS p
             INNER JOIN supplier AS sp
                 ON sp.supplier_id = p.primary_supplier_id
             LEFT JOIN supplier AS sb
                 ON sb.supplier_id = p.backup_supplier_id
             WHERE p.part_id = ?'
        );

        $stmt->bind_param('i', $partId);
        $stmt->execute();

        return $stmt->get_result()->fetch_assoc() ?: null;
    }

    public function create(array $data): bool
    {
        $stmt = $this->prepare(
            'INSERT INTO part (
                primary_supplier_id, primary_unit_cost,
                backup_supplier_id, backup_unit_cost
             )
             VALUES (?, ?, ?, ?)'
        );

        $primarySupplierId = (int) $data['primary_supplier_id'];
        $primaryUnitCost   = (float) $data['primary_unit_cost'];
        $backupSupplierId  = $data['backup_supplier_id'] !== '' ? (int) $data['backup_supplier_id'] : null;
        $backupUnitCost    = $data['backup_unit_cost'] !== '' ? (float) $data['backup_unit_cost'] : null;

        $stmt->bind_param(
            'idid',
            $primarySupplierId,
            $primaryUnitCost,
            $backupSupplierId,
            $backupUnitCost
        );

        return $stmt->execute();
    }

    public function update(int $partId, array $data): bool
    {
        $stmt = $this->prepare(
            'UPDATE part
             SET primary_supplier_id = ?, primary_unit_cost = ?,
                 backup_supplier_id = ?, backup_unit_cost = ?
             WHERE part_id = ?'
        );

        $primarySupplierId = (int) $data['primary_supplier_id'];
        $primaryUnitCost   = (float) $data['primary_unit_cost'];
        $backupSupplierId  = $data['backup_supplier_id'] !== '' ? (int) $data['backup_supplier_id'] : null;
        $backupUnitCost    = $data['backup_unit_cost'] !== '' ? (float) $data['backup_unit_cost'] : null;

        $stmt->bind_param(
            'ididi',
            $primarySupplierId,
            $primaryUnitCost,
            $backupSupplierId,
            $backupUnitCost,
            $partId
        );

        return $stmt->execute();
    }

    public function delete(int $partId): bool
    {
        $stmt = $this->prepare(
            'SELECT COUNT(*) AS usage_count FROM activity_part WHERE part_id = ?'
        );

        $stmt->bind_param('i', $partId);
        $stmt->execute();

        $count = (int) $stmt->get_result()->fetch_assoc()['usage_count'];

        if ($count > 0) {
            return false;
        }

        $stmt = $this->prepare('DELETE FROM part WHERE part_id = ?');
        $stmt->bind_param('i', $partId);

        return $stmt->execute();
    }

    public function getLookupOptions(): array
    {
        return [
            'suppliers' => $this->conn
                ->query('SELECT supplier_id, supplier_name FROM supplier ORDER BY supplier_name')
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
