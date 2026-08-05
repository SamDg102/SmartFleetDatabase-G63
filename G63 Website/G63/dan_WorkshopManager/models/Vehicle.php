<?php

class Vehicle
{
    public function __construct(private mysqli $conn) {}

    public function getAll(): array
    {
        $sql = "
            SELECT
                v.VIN,
                v.registration_number,
                v.year_of_manufacture,
                v.current_odometer,

                m.manufacturer_name,
                vc.category_name,
                v.vehicle_status AS status_name,
                d.depot_name

            FROM vehicle AS v

            LEFT JOIN manufacturer AS m
                ON v.manufacturer_id = m.manufacturer_id

            LEFT JOIN vehicle_category AS vc
                ON v.category_id = vc.category_id

            LEFT JOIN depot AS d
                ON v.depot_id = d.depot_id

            ORDER BY v.registration_number
        ";

        $result = $this->conn->query($sql);

        if ($result === false) {
            throw new RuntimeException(
                'Unable to retrieve vehicles: ' . $this->conn->error
            );
        }

        return $result->fetch_all(MYSQLI_ASSOC);
    }

    public function getByVin(string $vin): ?array
    {
        $sql = "
            SELECT
                v.*,
                m.manufacturer_name,
                vc.category_name,
                vehicle_status_name AS status_name,
                v.depot_location AS depot_name

            FROM vehicle AS v

            LEFT JOIN manufacturer AS m
                ON v.manufacturer_id = m.manufacturer_id

            LEFT JOIN vehicle_category AS vc
                ON v.category_id = vc.category_id

            WHERE v.VIN = ?
        ";

        $stmt = $this->conn->prepare($sql);

        if ($stmt === false) {
            throw new RuntimeException(
                'Unable to prepare vehicle query: ' . $this->conn->error
            );
        }

        $stmt->bind_param('s', $vin);
        $stmt->execute();

        $result = $stmt->get_result();
        $vehicle = $result->fetch_assoc();

        return $vehicle ?: null;
    }

    public function getMaintenanceJobs(string $vin): array
    {
        $sql = "
            SELECT
                job_id,
                date_opened,
                date_closed,
                downtime_hours,
                job_status,
                0 AS cost_total

            FROM maintenance_job

            WHERE VIN = ?

            ORDER BY date_opened DESC
        ";

        $stmt = $this->conn->prepare($sql);
        $stmt->bind_param('s', $vin);
        $stmt->execute();

        return $stmt
            ->get_result()
            ->fetch_all(MYSQLI_ASSOC);
    }

    public function getAlerts(string $vin): array
    {
        $sql = "
            SELECT
                alert_id,
                alert_type_id,
                job_id,
                timestamp,
                action_taken

            FROM predictive_alert

            WHERE VIN = ?

            ORDER BY timestamp DESC
        ";

        $stmt = $this->conn->prepare($sql);
        $stmt->bind_param('s', $vin);
        $stmt->execute();

        return $stmt
            ->get_result()
            ->fetch_all(MYSQLI_ASSOC);
    }

    public function getMaintenanceSummary(string $vin): array
    {
        $sql = "
            SELECT
                COUNT(*) AS total_jobs,
                COALESCE(SUM(downtime_hours), 0) AS total_downtime_hours,
                0 AS total_repair_cost

            FROM maintenance_job

            WHERE VIN = ?
        ";

        $stmt = $this->conn->prepare($sql);
        $stmt->bind_param('s', $vin);
        $stmt->execute();

        return $stmt->get_result()->fetch_assoc();
    }

    public function getLookupOptions(): array
    {
        return [
            'manufacturers' => $this->fetchLookup(
                'SELECT manufacturer_id, manufacturer_name
                 FROM manufacturer
                 ORDER BY manufacturer_name'
            ),

            'categories' => $this->fetchLookup(
                'SELECT category_id, category_name
                 FROM vehicle_category
                 ORDER BY category_name'
            ),

            'statuses' => $this->fetchLookup(
                'SELECT vehicle_status_id, vehicle_status_name AS status_name
                 FROM vehicle_status
                 ORDER BY vehicle_status_name'
            ),

            'depots' => $this->fetchLookup(
                'SELECT depot_id, depot_name
                 FROM depot
                 ORDER BY depot_name'
            )
        ];
    }

    private function fetchLookup(string $sql): array
    {
        $result = $this->conn->query($sql);

        if ($result === false) {
            throw new RuntimeException(
                'Unable to retrieve lookup values: ' . $this->conn->error
            );
        }

        return $result->fetch_all(MYSQLI_ASSOC);
    }
}
