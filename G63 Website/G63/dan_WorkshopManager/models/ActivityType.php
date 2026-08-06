<?php

class ActivityType
{
    public function __construct(private mysqli $conn) {}

    public function getAll(): array
    {
        $sql = "SELECT activity_type_id, activity_type_name
                FROM activity_type
                ORDER BY activity_type_name";

        $result = $this->conn->query($sql);
        return $result->fetch_all(MYSQLI_ASSOC);
    }

    public function create(array $data): bool
    {
        $stmt = $this->prepare('INSERT INTO activity_type (activity_type_name) VALUES (?)');

        $name = trim((string) $data['activity_type_name']);
        $stmt->bind_param('s', $name);

        return $stmt->execute();
    }

    public function update(int $activityTypeId, array $data): bool
    {
        $stmt = $this->prepare(
            'UPDATE activity_type SET activity_type_name = ? WHERE activity_type_id = ?'
        );

        $name = trim((string) $data['activity_type_name']);
        $stmt->bind_param('si', $name, $activityTypeId);

        return $stmt->execute();
    }

    public function delete(int $activityTypeId): bool
    {
        $stmt = $this->prepare(
            'SELECT COUNT(*) AS usage_count FROM maintenance_activity WHERE activity_type_id = ?'
        );

        $stmt->bind_param('i', $activityTypeId);
        $stmt->execute();

        $count = (int) $stmt->get_result()->fetch_assoc()['usage_count'];

        if ($count > 0) {
            return false;
        }

        $stmt = $this->prepare('DELETE FROM activity_type WHERE activity_type_id = ?');
        $stmt->bind_param('i', $activityTypeId);

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
