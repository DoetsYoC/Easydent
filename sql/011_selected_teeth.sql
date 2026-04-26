-- Migration 011: store selected teeth on treatment sessions
-- JSON type allows future per-tooth extensions (surfaces, material, canal count, notes).
-- NULL = no selection recorded yet; [] = explicitly empty.

ALTER TABLE treatment_sessions
  ADD COLUMN selected_teeth JSON DEFAULT NULL AFTER treatment_type_id;
