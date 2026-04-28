-- Easydent migratie 016: Anästhesie-groep toevoegen aan Vulling-behandeltype
--
-- Probleem: het vulling-behandeltype (Füllung/Komposit) had geen anesthesie-
-- opties, terwijl verdoving standaard onderdeel is van een vulling-sessie.
--
-- Toegevoegd: groep "Anästhesie" (sort_order 15, tussen Befund=10 en
-- Vorbereitung=20) met drie items:
--   0080  Oberflächenanästhesie   — per Kieferhälfte (bill_per_tooth=0)
--   0090  Infiltrationsanästhesie — per tand         (bill_per_tooth=1)
--   0100  Leitungsanästhesie      — per Kieferhälfte (bill_per_tooth=0)
--
-- Prijsformule: fee_base = ROUND(Punktzahl × 0.0562421, 2) = 1-fach bedrag
-- Facturering:  ROUND(fee_base × factor, 2)
--
-- NB: goz_code '0080' bestaat al in de Voorbereitung-groep als 'Kofferdam'
-- (verkeerde GOZ-code — officieel is dat GOZ 2040). Die rij blijft
-- ongewijzigd voor achterwaartse compatibiliteit met bestaande afspraken.
-- ─────────────────────────────────────────────────────────────────────────────

SET @t_fil = (
    SELECT id FROM treatment_types
    WHERE name_de = 'Füllung (Komposit)'
    LIMIT 1
);

-- Voeg de Anästhesie-groep toe
INSERT INTO treatment_groups (treatment_type_id, name_de, name_nl, name_en, sort_order)
VALUES (@t_fil, 'Anästhesie', 'Anesthesie', 'Anaesthesia', 15);

SET @g_anes = LAST_INSERT_ID();

-- Voeg de drie anesthesie-items toe
INSERT INTO treatment_items
    (group_id, name_de, name_nl, name_en, goz_code,
     factor_min, factor_max, factor_default,
     fee_base,
     is_proposed, is_mandatory, motivation_required, bill_per_tooth, sort_order,
     suggestion_de, suggestion_nl, suggestion_en)
VALUES
    -- Oppervlakte-anesthesie: per Kieferhälfte, zelden apart gedeclareerd
    (@g_anes,
     'Intraorale Oberflächenanästhesie',
     'Intraorale oppervlakte-anesthesie',
     'Intraoral surface anaesthesia',
     '0080',
     1.00, 3.50, 2.30,
     ROUND(30 * 0.0562421, 2),   -- 30 Punkte
     0, 0, 0, 0, 10,
     'Wird vor Infiltrationsanästhesie aufgetragen.',
     'Wordt aangebracht vóór infiltratieanesthesie.',
     'Applied before infiltration anaesthesia.'),

    -- Infiltratie-anesthesie: per tand (bill_per_tooth=1), meest gebruikelijk
    (@g_anes,
     'Intraorale Infiltrationsanästhesie',
     'Intraorale infiltratieanesthesie',
     'Intraoral infiltration anaesthesia',
     '0090',
     1.00, 3.50, 2.30,
     ROUND(60 * 0.0562421, 2),   -- 60 Punkte
     1, 0, 0, 1, 20,
     NULL, NULL, NULL),

    -- Geleidingsanesthesie: per Kieferhälfte, bij grote ingrepen
    (@g_anes,
     'Intraorale Leitungsanästhesie',
     'Intraorale geleidingsanesthesie',
     'Intraoral conduction anaesthesia',
     '0100',
     1.00, 3.50, 2.30,
     ROUND(70 * 0.0562421, 2),   -- 70 Punkte
     0, 0, 0, 0, 30,
     'Alternativ zur Infiltrationsanästhesie bei Unterkiefermolaren.',
     'Alternatief voor infiltratieanesthesie bij onderkaakmolaren.',
     'Alternative to infiltration anaesthesia for mandibular molars.');

-- ─────────────────────────────────────────────────────────────────────────────
-- Verificatie
-- SELECT ti.goz_code, ti.name_nl, ti.bill_per_tooth, ti.fee_base,
--        ROUND(ti.fee_base * 2.3, 2) AS bedrag_2_3fach
-- FROM treatment_items ti
-- JOIN treatment_groups tg ON tg.id = ti.group_id
-- WHERE tg.treatment_type_id = @t_fil AND tg.name_de = 'Anästhesie'
-- ORDER BY ti.sort_order;
-- ─────────────────────────────────────────────────────────────────────────────
