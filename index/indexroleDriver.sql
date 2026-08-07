USE g63system;

CREATE INDEX ind_scorepage ON `monthly_safety_score` (`score_month`, `final_score`, `action_required`);
