ALTER TABLE treatment_items
  ADD COLUMN bill_per_tooth TINYINT(1) NOT NULL DEFAULT 0
  AFTER motivation_required;
