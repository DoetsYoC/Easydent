-- Migration 009: add tooth_selection_mode to treatment_types
-- Default 'not_applicable' ensures all existing types keep working unchanged.

ALTER TABLE treatment_types
  ADD COLUMN tooth_selection_mode
    ENUM('not_applicable','optional','required_single','required_multiple')
    NOT NULL DEFAULT 'not_applicable'
  AFTER sort_order;
