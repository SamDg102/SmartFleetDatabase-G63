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
                v.vehicle_status,
                d.depot_name,
                d.depot_location

            FROM vehicle AS v

            LEFT JOIN manufacturer AS m
                ON v.manufacturer_id = m.manufacturer_id

            LEFT JOIN vehicle_category AS vc
                ON v.category_id = vc.category_id
                
            LEFT JOIN depot as d
            	on d.depot_id = v.depot_id

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
                m.contact_info AS manufacturer_contact,
                vc.category_name,
                v.vehicle_status

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
                mj.job_id,
                d.depot_name,
                d.depot_location,
                mj.date_opened,
                mj.date_closed,
                ROUND(TIME_TO_SEC(mj.downtime_hours) / 3600, 2)
                    AS downtime_hours_decimal,
                mj.job_status,

                (SELECT COALESCE(SUM(ap.quantity_used * p.primary_unit_cost), 0)
                 FROM maintenance_activity AS ma
                 INNER JOIN activity_part AS ap
                    ON ap.job_id = ma.job_id AND ap.activity_id = ma.activity_id
                 INNER JOIN part AS p 
                 	ON p.part_id = ap.part_id
                 WHERE ma.job_id = mj.job_id) AS estimated_parts_cost

            FROM maintenance_job AS mj
            INNER JOIN service_bay AS sb 
            	ON sb.service_bay_id = mj.service_bay_id
                INNER JOIN depot AS d 
                    ON sb.depot_id = d.depot_id
            WHERE mj.VIN =  ?
            ORDER BY mj.date_opened DESC
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
                pa.alert_id,
                pa.alert_type_id,
                at.alert_name,
                at.description,
                pa.job_id,
                pa.timestamp,
                IF(pa.action_taken IS NULL OR TRIM(pa.action_taken) = '',
                   'Pending', 'Actioned') AS alert_status,
                pa.action_taken
            FROM predictive_alert AS pa
            INNER JOIN alert_type AS at ON at.alert_type_id = pa.alert_type_id
            WHERE pa.VIN = ?
            ORDER BY pa.timestamp DESC
        ";

        $stmt = $this->conn->prepare($sql);
        $stmt->bind_param('s', $vin);
        $stmt->execute();

        return $stmt
            ->get_result()
            ->fetch_all(MYSQLI_ASSOC);
    }

    public function getDriverAssignments(string $vin): array
    {
        $stmt = $this->conn->prepare(
            'SELECT d.driver_id, d.full_name, d.contact_info,
                    vda.start_date, vda.end_date
             FROM vehicle_driver_assignment AS vda
             INNER JOIN driver AS d ON d.driver_id = vda.driver_id
             WHERE vda.VIN = ?
             ORDER BY vda.start_date DESC'
        );
        $stmt->bind_param('s', $vin);
        $stmt->execute();

        return $stmt->get_result()->fetch_all(MYSQLI_ASSOC);
    }

    public function getMaintenanceSummary(string $vin): array
    {
        $sql = "
            SELECT
                COUNT(*) AS total_jobs,
                COALESCE(ROUND(SUM(TIME_TO_SEC(downtime_hours)) / 3600, 2), 0)
                    AS total_downtime_hours,
                (SELECT COALESCE(SUM(ap.quantity_used * p.primary_unit_cost), 0)
                 FROM maintenance_job AS mj
                 INNER JOIN maintenance_activity AS ma ON ma.job_id = mj.job_id
                 INNER JOIN activity_part AS ap
                    ON ap.job_id = ma.job_id AND ap.activity_id = ma.activity_id
                 INNER JOIN part AS p ON p.part_id = ap.part_id
                 WHERE mj.VIN = ?) AS estimated_repair_cost

            FROM maintenance_job

            WHERE VIN = ?
        ";

        $stmt = $this->conn->prepare($sql);
        $stmt->bind_param('ss', $vin, $vin);
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
