-- Easydent migratie 020: fee_base aanvullen voor endodontie codes 2450/2460/2470/2480
--
-- sql/015_goz_catalog.sql bevat alleen GOZ 2012 Anlage 1 officiele codes.
-- De seed (003) gebruikt 2450/2460/2470/2480 als praktijkcodes die buiten
-- de officiele nummering vallen. Deze migratie voegt ze toe aan goz_catalog
-- met een analoge Punktzahl (GOZ 2012 referentie in commentaar) en vult
-- vervolgens fee_base bij voor alle items die nog 0 hebben.
--
-- Analogen:
--   2450 (Instrumentenentfernung)    ≈ 250 Punkte (gangbaar bij analoge verrekening)
--   2460 (Med. Einlage je Kanal)     ≈ GOZ 2430 = 204 Punkte (per Sitzung → hier per Kanal)
--   2470 (Füllung je Kanal)          ≈ GOZ 2440 = 258 Punkte
--   2480 (Retrograde Füllung je Kan) ≈ GOZ 2440 = 258 Punkte (analoog)
--
-- Vereiste: sql/015_goz_catalog.sql + sql/019_endo_per_canal.sql
-- ─────────────────────────────────────────────────────────────────────────────

INSERT INTO goz_catalog (goz_code, description_de, punktzahl, fee_1fach, bill_per_tooth, goz_source)
VALUES
  ('2450', 'Entfernung eines Fremdkörpers (Instrumentenbruchstücks) aus einem Wurzelkanal', 250, ROUND(250 * 0.0562421, 4), 1, 'GOZ 2012 / analog'),
  ('2460', 'Medikamentöse Einlage in Wurzelkanal, je Kanal',                               204, ROUND(204 * 0.0562421, 4), 1, 'GOZ 2012 / analog 2430'),
  ('2470', 'Füllung eines Wurzelkanals, je Kanal',                                         258, ROUND(258 * 0.0562421, 4), 1, 'GOZ 2012 / analog 2440'),
  ('2480', 'Retrograde Wurzelkanalfüllung, je Kanal',                                      258, ROUND(258 * 0.0562421, 4), 1, 'GOZ 2012 / analog 2440')
ON DUPLICATE KEY UPDATE
  description_de = VALUES(description_de),
  punktzahl      = VALUES(punktzahl),
  fee_1fach      = VALUES(fee_1fach);

-- fee_base bijwerken voor items die nog 0 hebben
UPDATE treatment_items ti
  JOIN goz_catalog gc ON gc.goz_code = ti.goz_code
SET ti.fee_base = gc.fee_1fach
WHERE ti.fee_base = 0
  AND gc.fee_1fach IS NOT NULL;

-- ─────────────────────────────────────────────────────────────────────────────
-- Verificatie
-- SELECT ti.goz_code, ti.name_nl, ti.fee_base, ti.is_per_canal
-- FROM treatment_items ti
--   JOIN treatment_groups tg ON tg.id = ti.group_id
--   JOIN treatment_types  tt ON tt.id = tg.treatment_type_id
-- WHERE tt.name_en = 'Endodontics'
-- ORDER BY ti.sort_order;
-- ─────────────────────────────────────────────────────────────────────────────
