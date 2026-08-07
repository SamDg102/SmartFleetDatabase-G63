USE g63system;

CREATE INDEX ind_scorepage ON `monthly_safety_score` (`score_month`, `final_score`, `action_required`);

CREATE INDEX ind_vehiclepage ON `vehicle_driver_assignment` (`start_date`, `end_date`, `VIN`)
