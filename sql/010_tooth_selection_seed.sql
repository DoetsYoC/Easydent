-- Migration 010: set tooth_selection_mode for the four base treatment types
-- PZR: optional  (full dentition or partial; select-all default comes in a later issue)
-- Consult: optional (can be general or tooth-specific)
-- Filling: required_multiple (one or more teeth must be selected before completing)
-- Endo: required_single (one canal treatment per tooth; cleaner for billing and canal details)

UPDATE treatment_types SET tooth_selection_mode = 'optional'           WHERE name_de = 'Prophylaxe (PZR)';
UPDATE treatment_types SET tooth_selection_mode = 'optional'           WHERE name_de = 'Konsultation';
UPDATE treatment_types SET tooth_selection_mode = 'required_multiple'  WHERE name_de = 'Füllung (Komposit)';
UPDATE treatment_types SET tooth_selection_mode = 'required_single'    WHERE name_de = 'Endodontie';
