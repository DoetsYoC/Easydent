-- Easydent migratie 017: Segment-weergave voor behandelgroepen
--
-- Voegt is_segment-vlag en vertalingen toe aan treatment_groups zodat
-- items in een groep als horizontale knoppenbalk worden getoond (i.p.v.
-- losse kaarten). Handig voor onderling uitsluitende opties zoals
-- het aantal vlakken bij een composietvulling.
--
-- Voegt button_label_* toe aan treatment_items voor korte knoopteksten.
-- Volgorde: na sql/016_vulling_anesthesie.sql
-- ─────────────────────────────────────────────────────────────────────────────

ALTER TABLE treatment_groups
  ADD COLUMN is_segment        TINYINT(1)    NOT NULL DEFAULT 0  AFTER sort_order,
  ADD COLUMN segment_label_de  VARCHAR(120)  NULL                AFTER is_segment,
  ADD COLUMN segment_label_nl  VARCHAR(120)  NULL                AFTER segment_label_de,
  ADD COLUMN segment_label_en  VARCHAR(120)  NULL                AFTER segment_label_nl;

ALTER TABLE treatment_items
  ADD COLUMN button_label_de   VARCHAR(60)   NULL                AFTER sort_order,
  ADD COLUMN button_label_nl   VARCHAR(60)   NULL                AFTER button_label_de,
  ADD COLUMN button_label_en   VARCHAR(60)   NULL                AFTER button_label_nl;

-- Restauratiegroep van Füllung als segment-groep markeren
UPDATE treatment_groups
SET is_segment       = 1,
    segment_label_de = 'Anzahl Flächen',
    segment_label_nl = 'Aantal vlakken',
    segment_label_en = 'Number of surfaces'
WHERE name_nl = 'Restauratie (kies vulling)';

-- Korte knoopteksten voor de 4 vulling-items
UPDATE treatment_items ti
  JOIN treatment_groups tg ON tg.id = ti.group_id
SET ti.button_label_de = '1-flächig',
    ti.button_label_nl = '1 vlak',
    ti.button_label_en = '1 surface'
WHERE tg.name_nl = 'Restauratie (kies vulling)' AND ti.goz_code = '2060';

UPDATE treatment_items ti
  JOIN treatment_groups tg ON tg.id = ti.group_id
SET ti.button_label_de = '2-flächig',
    ti.button_label_nl = '2 vlakken',
    ti.button_label_en = '2 surfaces'
WHERE tg.name_nl = 'Restauratie (kies vulling)' AND ti.goz_code = '2070';

UPDATE treatment_items ti
  JOIN treatment_groups tg ON tg.id = ti.group_id
SET ti.button_label_de = '3-flächig',
    ti.button_label_nl = '3 vlakken',
    ti.button_label_en = '3 surfaces'
WHERE tg.name_nl = 'Restauratie (kies vulling)' AND ti.goz_code = '2080';

UPDATE treatment_items ti
  JOIN treatment_groups tg ON tg.id = ti.group_id
SET ti.button_label_de = '4+-flächig',
    ti.button_label_nl = '4+ vlakken',
    ti.button_label_en = '4+ surfaces'
WHERE tg.name_nl = 'Restauratie (kies vulling)' AND ti.goz_code = '2090';

-- ─────────────────────────────────────────────────────────────────────────────
-- Verificatie
-- SELECT tg.name_nl, ti.goz_code, ti.button_label_nl
-- FROM treatment_items ti JOIN treatment_groups tg ON tg.id = ti.group_id
-- WHERE tg.is_segment = 1 ORDER BY ti.sort_order;
-- ─────────────────────────────────────────────────────────────────────────────
