-- Easydent migratie 019: Endodontie — declaratie per kanaal + klinische documentatie
--
-- Voegt is_per_canal toe aan treatment_items. Codes 2440/2460/2470/2480
-- worden gefactureerd per wortelkanaal; quantity = canal_count van de tand.
--
-- Vereiste: sql/003_treatment_seed.sql + sql/014_bill_per_tooth_seed.sql
-- ─────────────────────────────────────────────────────────────────────────────

ALTER TABLE treatment_items
  ADD COLUMN is_per_canal TINYINT(1) NOT NULL DEFAULT 0 AFTER bill_per_tooth;

UPDATE treatment_items ti
  JOIN treatment_groups tg ON tg.id = ti.group_id
  JOIN treatment_types  tt ON tt.id = tg.treatment_type_id
SET ti.is_per_canal = 1
WHERE tt.name_en = 'Endodontics'
  AND ti.goz_code IN ('2440','2460','2470','2480');

-- ─────────────────────────────────────────────────────────────────────────────
-- Verificatie
-- SELECT ti.goz_code, ti.name_de, ti.is_per_canal
-- FROM treatment_items ti
--   JOIN treatment_groups tg ON tg.id = ti.group_id
--   JOIN treatment_types tt ON tt.id = tg.treatment_type_id
-- WHERE tt.name_en = 'Endodontics'
-- ORDER BY ti.sort_order;
-- ─────────────────────────────────────────────────────────────────────────────
