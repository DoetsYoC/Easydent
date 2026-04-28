-- Easydent migratie 018: fee_base aanvullen vanuit goz_catalog
--
-- sql/007_fee_base.sql heeft een aantal GOZ-codes gemist (o.a. de composiet-
-- vullingscodes 2060–2090 en de nieuwe anesthesiecodes 0090/0100).
-- Deze migratie vult fee_base in voor alle treatment_items die nog op 0 staan,
-- door te joinen met de goz_catalog (migratie 015).
--
-- fee_base = fee_1fach = ROUND(Punktzahl × 0.0562421, 4)
-- Facturering: ROUND(fee_base × factor, 2)
--
-- Vereiste: sql/015_goz_catalog.sql moet al uitgevoerd zijn.
-- ─────────────────────────────────────────────────────────────────────────────

UPDATE treatment_items ti
  JOIN goz_catalog gc ON gc.goz_code = ti.goz_code
SET ti.fee_base = gc.fee_1fach
WHERE ti.fee_base = 0
  AND gc.fee_1fach IS NOT NULL;

-- ─────────────────────────────────────────────────────────────────────────────
-- Verificatie: toon items die nog steeds fee_base = 0 hebben
-- (dit zijn items met variabele/analoge Punktzahl of ontbrekende GOZ-code)
-- SELECT ti.id, ti.goz_code, ti.name_nl, ti.fee_base
-- FROM treatment_items ti
-- WHERE ti.fee_base = 0
-- ORDER BY ti.goz_code;
-- ─────────────────────────────────────────────────────────────────────────────
