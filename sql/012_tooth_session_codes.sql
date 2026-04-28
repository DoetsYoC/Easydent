-- Issue #61: per-tooth billing — add tooth_number to session_codes
ALTER TABLE session_codes
  ADD COLUMN tooth_number VARCHAR(3) NULL AFTER session_id;

ALTER TABLE session_codes
  ADD INDEX idx_session_tooth (session_id, tooth_number);
