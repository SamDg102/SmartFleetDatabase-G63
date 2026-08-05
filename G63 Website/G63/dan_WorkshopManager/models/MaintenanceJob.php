<?php

class MaintenanceJob
{
    public function __construct(private mysqli $conn) {}
    public function getAll(): array
    {
        $sql = "
            SELECT
                mj.job_id,
                mj.VIN,
                v.registration_number,
                vc.category_name,
                m.manufacturer_name,
                mj.workshop_id,
                d.depot_name,
                d.depot_location,
                mj.date_opened,
                mj.date_closed,
                mj.downtime_hours,
                ROUND(TIME_TO_SEC(mj.downtime_hours) / 3600, 2)
                    AS downtime_hours_decimal,
                mj.job_status,

                (
                    SELECT COUNT(*)
                    FROM maintenance_activity AS ma
                    WHERE ma.job_id = mj.job_id
                ) AS activity_count,

                (
                    SELECT COUNT(*)
                    FROM predictive_alert AS pa
                    WHERE pa.job_id = mj.job_id
                ) AS linked_alert_count,

                (
                    SELECT COALESCE(
                        SUM(ap.quantity_used * p.primary_unit_cost),
                        0
                    )
                    FROM maintenance_activity AS ma2
                    INNER JOIN activity_part AS ap
                        ON ap.job_id = ma2.job_id
                       AND ap.activity_id = ma2.activity_id
                    INNER JOIN part AS p
                        ON p.part_id = ap.part_id
                    WHERE ma2.job_id = mj.job_id
                ) AS estimated_parts_cost

            FROM maintenance_job AS mj

            INNER JOIN vehicle AS v
                ON v.VIN = mj.VIN

            LEFT JOIN vehicle_category AS vc
                ON vc.category_id = v.category_id

            LEFT JOIN manufacturer AS m
                ON m.manufacturer_id = v.manufacturer_id

            INNER JOIN workshop AS w
                ON w.workshop_id = mj.workshop_id

            INNER JOIN depot AS d
                ON d.depot_id = w.depot_id

            ORDER BY mj.date_opened DESC, mj.job_id DESC
        ";

        $result = $this->conn->query($sql);

        if ($result === false) {
            throw new RuntimeException(
                'Unable to retrieve maintenance jobs: ' . $this->conn->error
            );
        }

        return $result->fetch_all(MYSQLI_ASSOC);
    }

    //Get one job by job_id.

    public function getById(int $jobId): ?array
    {
        $sql = "
            SELECT
                mj.job_id,
                mj.VIN,
                v.registration_number,
                v.year_of_manufacture,
                v.current_odometer,
                vc.category_name,
                m.manufacturer_name,
                mj.workshop_id,
                d.depot_name,
                d.depot_location,
                mj.date_opened,
                mj.date_closed,
                mj.downtime_hours,
                ROUND(TIME_TO_SEC(mj.downtime_hours) / 3600, 2)
                    AS downtime_hours_decimal,
                mj.job_status,

                (
                    SELECT COALESCE(
                        SUM(ap.quantity_used * p.primary_unit_cost),
                        0
                    )
                    FROM maintenance_activity AS ma2
                    INNER JOIN activity_part AS ap
                        ON ap.job_id = ma2.job_id
                       AND ap.activity_id = ma2.activity_id
                    INNER JOIN part AS p
                        ON p.part_id = ap.part_id
                    WHERE ma2.job_id = mj.job_id
                ) AS estimated_parts_cost

            FROM maintenance_job AS mj

            INNER JOIN vehicle AS v
                ON v.VIN = mj.VIN

            LEFT JOIN vehicle_category AS vc
                ON vc.category_id = v.category_id

            LEFT JOIN manufacturer AS m
                ON m.manufacturer_id = v.manufacturer_id

            INNER JOIN workshop AS w
                ON w.workshop_id = mj.workshop_id

            INNER JOIN depot AS d
                ON d.depot_id = w.depot_id

            WHERE mj.job_id = ?
        ";

        $stmt = $this->prepare($sql);
        $stmt->bind_param('i', $jobId);
        $stmt->execute();

        $job = $stmt->get_result()->fetch_assoc();

        return $job ?: null;
    }

    /**
     * Get activities belonging to a job.
     */
    public function getActivities(int $jobId): array
    {
        $sql = "
            SELECT
                ma.job_id,
                ma.activity_id,
                ma.activity_type_id,
                at.activity_type_name,
                ma.diagnostic_result,
                ma.repeat_fault,
                ma.warranty_applicable,

                (
                    SELECT COALESCE(
                        ROUND(SUM(TIME_TO_SEC(mact.labour_hours)) / 3600, 2),
                        0
                    )
                    FROM mechanic_activity AS mact
                    WHERE mact.job_id = ma.job_id
                      AND mact.activity_id = ma.activity_id
                ) AS total_labour_hours,

                (
                    SELECT COUNT(*)
                    FROM mechanic_activity AS mact2
                    WHERE mact2.job_id = ma.job_id
                      AND mact2.activity_id = ma.activity_id
                ) AS mechanic_count

            FROM maintenance_activity AS ma

            INNER JOIN activity_type AS at
                ON at.activity_type_id = ma.activity_type_id

            WHERE ma.job_id = ?

            ORDER BY ma.activity_id
        ";

        $stmt = $this->prepare($sql);
        $stmt->bind_param('i', $jobId);
        $stmt->execute();

        return $stmt->get_result()->fetch_all(MYSQLI_ASSOC);
    }

    public function getAssignedMechanics(int $jobId): array
    {
        $sql = "
            SELECT
                ma.job_id,
                ma.activity_id,
                at.activity_type_name,
                ma.mechanic_id,
                m.full_name,
                m.employment_status,
                ma.labour_hours,
                ROUND(TIME_TO_SEC(ma.labour_hours) / 3600, 2)
                    AS labour_hours_decimal

            FROM mechanic_activity AS ma

            INNER JOIN mechanic AS m
                ON m.mechanic_id = ma.mechanic_id

            INNER JOIN maintenance_activity AS act
                ON act.job_id = ma.job_id
               AND act.activity_id = ma.activity_id

            INNER JOIN activity_type AS at
                ON at.activity_type_id = act.activity_type_id

            WHERE ma.job_id = ?

            ORDER BY ma.activity_id, m.full_name
        ";

        $stmt = $this->prepare($sql);
        $stmt->bind_param('i', $jobId);
        $stmt->execute();

        return $stmt->get_result()->fetch_all(MYSQLI_ASSOC);
    }

    public function getLinkedAlerts(int $jobId): array
    {
        $sql = "
            SELECT
                pa.alert_id,
                pa.VIN,
                pa.alert_type_id,
                at.alert_name,
                at.description,
                pa.timestamp,
                pa.action_taken

            FROM predictive_alert AS pa

            INNER JOIN alert_type AS at
                ON at.alert_type_id = pa.alert_type_id

            WHERE pa.job_id = ?

            ORDER BY pa.timestamp DESC
        ";

        $stmt = $this->prepare($sql);
        $stmt->bind_param('i', $jobId);
        $stmt->execute();

        return $stmt->get_result()->fetch_all(MYSQLI_ASSOC);
    }

    public function create(array $data): bool
    {
        $sql = "
            INSERT INTO maintenance_job (
                VIN,
                workshop_id,
                date_opened,
                date_closed,
                downtime_hours,
                job_status
            )
            VALUES (?, ?, ?, ?, ?, ?)
        ";

        $stmt = $this->prepare($sql);

        $vin = trim((string) $data['VIN']);
        $workshopId = (int) $data['workshop_id'];
        $dateOpened = (string) $data['date_opened'];
        $dateClosed = (string) $data['date_closed'];
        $downtimeHours = (string) $data['downtime_hours'];
        $jobStatus = (string) $data['job_status'];

        $stmt->bind_param(
            'sissss',
            $vin,
            $workshopId,
            $dateOpened,
            $dateClosed,
            $downtimeHours,
            $jobStatus
        );

        return $stmt->execute();
    }

    public function update(int $jobId, array $data): bool
    {
        $sql = "
            UPDATE maintenance_job
            SET
                VIN = ?,
                workshop_id = ?,
                date_opened = ?,
                date_closed = ?,
                downtime_hours = ?,
                job_status = ?
            WHERE job_id = ?
        ";

        $stmt = $this->prepare($sql);

        $vin = trim((string) $data['VIN']);
        $workshopId = (int) $data['workshop_id'];
        $dateOpened = (string) $data['date_opened'];
        $dateClosed = (string) $data['date_closed'];
        $downtimeHours = (string) $data['downtime_hours'];
        $jobStatus = (string) $data['job_status'];

        $stmt->bind_param(
            'sissssi',
            $vin,
            $workshopId,
            $dateOpened,
            $dateClosed,
            $downtimeHours,
            $jobStatus,
            $jobId
        );

        return $stmt->execute();
    }

    public function delete(int $jobId): bool
    {
        $sql = "
            SELECT
                (
                    SELECT COUNT(*)
                    FROM maintenance_activity
                    WHERE job_id = ?
                ) AS activity_count,

                (
                    SELECT COUNT(*)
                    FROM predictive_alert
                    WHERE job_id = ?
                ) AS linked_alert_count
        ";

        $stmt = $this->prepare($sql);
        $stmt->bind_param('ii', $jobId, $jobId);
        $stmt->execute();

        $dependencies = $stmt->get_result()->fetch_assoc();

        if (
            (int) $dependencies['activity_count'] > 0 ||
            (int) $dependencies['linked_alert_count'] > 0
        ) {
            return false;
        }

        $stmt = $this->prepare(
            'DELETE FROM maintenance_job WHERE job_id = ?'
        );

        $stmt->bind_param('i', $jobId);

        return $stmt->execute();
    }

    private function prepare(string $sql): mysqli_stmt
    {
        $stmt = $this->conn->prepare($sql);

        if ($stmt === false) {
            throw new RuntimeException(
                'Unable to prepare statement: ' . $this->conn->error
            );
        }

        return $stmt;
    }
}
