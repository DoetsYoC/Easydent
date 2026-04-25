-- Easydent migratie 003: Startdata behandeltypes (PZR, Consult, Filling, Endo)
-- Gebaseerd op GOZ 2012 (Gebührenordnung für Zahnärzte, Anlage 1)
-- Voer uit via phpMyAdmin NADAT 002_treatment_types.sql is uitgevoerd

SET FOREIGN_KEY_CHECKS = 0;

-- =============================================
-- BEHANDELTYPE 1: Prophylaxe (PZR)
-- =============================================
INSERT INTO treatment_types (name_de, name_nl, name_en, sort_order) VALUES
    ('Prophylaxe (PZR)', 'PZR', 'Prophylaxis (PZR)', 10);
SET @t_pzr = LAST_INSERT_ID();

-- Groepen
INSERT INTO treatment_groups (treatment_type_id, name_de, name_nl, name_en, sort_order) VALUES
    (@t_pzr, 'Befund & Diagnose', 'Bevinding & Diagnose', 'Assessment & Diagnosis', 10);
SET @g_pzr1 = LAST_INSERT_ID();

INSERT INTO treatment_groups (treatment_type_id, name_de, name_nl, name_en, sort_order) VALUES
    (@t_pzr, 'Hygieneinstruktion', 'Hygiëne-instructie', 'Hygiene Instruction', 20);
SET @g_pzr2 = LAST_INSERT_ID();

INSERT INTO treatment_groups (treatment_type_id, name_de, name_nl, name_en, sort_order) VALUES
    (@t_pzr, 'Professionelle Reinigung', 'Professionele reiniging', 'Professional Cleaning', 30);
SET @g_pzr3 = LAST_INSERT_ID();

INSERT INTO treatment_groups (treatment_type_id, name_de, name_nl, name_en, sort_order) VALUES
    (@t_pzr, 'Fluoridierung & Remineralisation', 'Fluoridering & Remineralisatie', 'Fluoride & Remineralisation', 40);
SET @g_pzr4 = LAST_INSERT_ID();

-- Prestaties Groep 1: Befund
INSERT INTO treatment_items
    (group_id, name_de, name_nl, name_en, goz_code, factor_min, factor_max, factor_default, is_proposed, is_mandatory, motivation_required, sort_order)
VALUES
    (@g_pzr1, 'Eingehende Untersuchung und Beratung', 'Uitgebreid onderzoek en advies', 'Comprehensive examination and consultation', '0010', 1.00, 3.50, 2.30, 1, 1, 0, 10),
    (@g_pzr1, 'Erhebung des Parodontalbefundes (PSI)', 'Parodontale screeningindex (PSI)', 'Periodontal Screening Index (PSI)', '0060', 1.00, 3.50, 2.30, 1, 0, 0, 20),
    (@g_pzr1, 'Dokumentation des Mundhygienestatus', 'Documentatie mondhygiënestatus', 'Documentation of oral hygiene status', '1000', 1.00, 3.50, 2.30, 1, 0, 0, 30);

-- Prestaties Groep 2: Hygieneinstruktion
INSERT INTO treatment_items
    (group_id, name_de, name_nl, name_en, goz_code, factor_min, factor_max, factor_default, is_proposed, is_mandatory, motivation_required, sort_order)
VALUES
    (@g_pzr2, 'Mundhygieneinstruktion und -motivation', 'Mondhygiëne-instructie en -motivatie', 'Oral hygiene instruction and motivation', '1010', 1.00, 3.50, 2.30, 1, 1, 0, 10),
    (@g_pzr2, 'Kontrolle der Mundhygiene nach Instruktion', 'Controle mondhygiëne na instructie', 'Oral hygiene check after instruction', '1020', 1.00, 3.50, 2.30, 0, 0, 0, 20);

-- Prestaties Groep 3: Reinigung
INSERT INTO treatment_items
    (group_id, name_de, name_nl, name_en, goz_code, factor_min, factor_max, factor_default, is_proposed, is_mandatory, motivation_required, sort_order,
     suggestion_de, suggestion_nl, suggestion_en)
VALUES
    (@g_pzr3, 'Entfernung harter und weicher Beläge, Polieren aller Zähne', 'Verwijdering harde en zachte aanslag, polijsten alle tanden', 'Removal of deposits and polishing of all teeth', '1040', 1.00, 3.50, 2.30, 1, 1, 0, 10,
     NULL, NULL, NULL),
    (@g_pzr3, 'Entfernung von subgingivalen Belägen (je Zahn)', 'Verwijdering subgingivale aanslag (per tand)', 'Removal of subgingival deposits (per tooth)', '4050', 1.00, 3.50, 2.30, 0, 0, 1, 20,
     'Nur bei parodontaler Erkrankung abrechenbar.', 'Alleen declarabel bij parodontale aandoening.', 'Only billable in case of periodontal disease.');

-- Prestaties Groep 4: Fluoridierung
INSERT INTO treatment_items
    (group_id, name_de, name_nl, name_en, goz_code, factor_min, factor_max, factor_default, is_proposed, is_mandatory, motivation_required, sort_order)
VALUES
    (@g_pzr4, 'Lokale Fluoridierung', 'Lokale fluoridering', 'Local fluoride application', '1200', 1.00, 3.50, 2.30, 1, 0, 0, 10),
    (@g_pzr4, 'Versiegelung einer kariesfreien Fissur', 'Lakken van een cariësvrije fissuur', 'Sealing of a caries-free fissure', '2000', 1.00, 3.50, 2.30, 0, 0, 0, 20);


-- =============================================
-- BEHANDELTYPE 2: Konsultation (Consult)
-- =============================================
INSERT INTO treatment_types (name_de, name_nl, name_en, sort_order) VALUES
    ('Konsultation', 'Consult', 'Consultation', 20);
SET @t_con = LAST_INSERT_ID();

-- Groepen
INSERT INTO treatment_groups (treatment_type_id, name_de, name_nl, name_en, sort_order) VALUES
    (@t_con, 'Anamnese & Untersuchung', 'Anamnese & Onderzoek', 'Anamnesis & Examination', 10);
SET @g_con1 = LAST_INSERT_ID();

INSERT INTO treatment_groups (treatment_type_id, name_de, name_nl, name_en, sort_order) VALUES
    (@t_con, 'Diagnostik & Planung', 'Diagnostiek & Planning', 'Diagnostics & Planning', 20);
SET @g_con2 = LAST_INSERT_ID();

INSERT INTO treatment_groups (treatment_type_id, name_de, name_nl, name_en, sort_order) VALUES
    (@t_con, 'Beratung & Aufklärung', 'Advies & Voorlichting', 'Consultation & Counselling', 30);
SET @g_con3 = LAST_INSERT_ID();

-- Prestaties Groep 1: Anamnese & Untersuchung
INSERT INTO treatment_items
    (group_id, name_de, name_nl, name_en, goz_code, factor_min, factor_max, factor_default, is_proposed, is_mandatory, motivation_required, sort_order)
VALUES
    (@g_con1, 'Eingehende Untersuchung und Beratung', 'Uitgebreid onderzoek en advies', 'Comprehensive examination and consultation', '0010', 1.00, 3.50, 2.30, 1, 1, 0, 10),
    (@g_con1, 'Vollständige Erhebung des Zahnstatus', 'Volledige tandstatus', 'Complete dental status examination', '0050', 1.00, 3.50, 2.30, 1, 0, 0, 20);

-- Prestaties Groep 2: Diagnostik
INSERT INTO treatment_items
    (group_id, name_de, name_nl, name_en, goz_code, factor_min, factor_max, factor_default, is_proposed, is_mandatory, motivation_required, sort_order,
     suggestion_de, suggestion_nl, suggestion_en)
VALUES
    (@g_con2, 'Erhebung des Parodontalbefundes (PSI)', 'Parodontale screeningindex (PSI)', 'Periodontal Screening Index (PSI)', '0060', 1.00, 3.50, 2.30, 0, 0, 0, 10,
     'Nur bei Verdacht auf parodontale Erkrankung.', 'Alleen bij vermoeden van parodontale aandoening.', 'Only when periodontal disease is suspected.'),
    (@g_con2, 'Anfertigung eines Heil- und Kostenplans', 'Opstellen heil- en kostenplan', 'Preparation of treatment and cost plan', '0100', 1.00, 2.30, 1.00, 0, 0, 0, 20,
     NULL, NULL, NULL);

-- Prestaties Groep 3: Beratung
INSERT INTO treatment_items
    (group_id, name_de, name_nl, name_en, goz_code, factor_min, factor_max, factor_default, is_proposed, is_mandatory, motivation_required, sort_order)
VALUES
    (@g_con3, 'Eingehende Beratung, auch telefonisch', 'Uitgebreid advies, ook telefonisch', 'In-depth consultation, also by phone', '0060', 1.00, 3.50, 2.30, 0, 0, 0, 10);


-- =============================================
-- BEHANDELTYPE 3: Füllung (Filling)
-- =============================================
INSERT INTO treatment_types (name_de, name_nl, name_en, sort_order) VALUES
    ('Füllung (Komposit)', 'Vulling (Composiet)', 'Filling (Composite)', 30);
SET @t_fil = LAST_INSERT_ID();

-- Groepen
INSERT INTO treatment_groups (treatment_type_id, name_de, name_nl, name_en, sort_order) VALUES
    (@t_fil, 'Befund & Diagnose', 'Bevinding & Diagnose', 'Assessment & Diagnosis', 10);
SET @g_fil1 = LAST_INSERT_ID();

INSERT INTO treatment_groups (treatment_type_id, name_de, name_nl, name_en, sort_order) VALUES
    (@t_fil, 'Vorbereitung', 'Voorbereiding', 'Preparation', 20);
SET @g_fil2 = LAST_INSERT_ID();

INSERT INTO treatment_groups (treatment_type_id, name_de, name_nl, name_en, sort_order) VALUES
    (@t_fil, 'Restauration (Füllung wählen)', 'Restauratie (kies vulling)', 'Restoration (select filling)', 30);
SET @g_fil3 = LAST_INSERT_ID();

INSERT INTO treatment_groups (treatment_type_id, name_de, name_nl, name_en, sort_order) VALUES
    (@t_fil, 'Adhäsivtechnik', 'Adhesieftechniek', 'Adhesive technique', 40);
SET @g_fil4 = LAST_INSERT_ID();

-- Prestaties Groep 1: Befund
INSERT INTO treatment_items
    (group_id, name_de, name_nl, name_en, goz_code, factor_min, factor_max, factor_default, is_proposed, is_mandatory, motivation_required, sort_order)
VALUES
    (@g_fil1, 'Eingehende Untersuchung und Beratung', 'Uitgebreid onderzoek en advies', 'Comprehensive examination and consultation', '0010', 1.00, 3.50, 2.30, 1, 1, 0, 10);

-- Prestaties Groep 2: Vorbereitung
INSERT INTO treatment_items
    (group_id, name_de, name_nl, name_en, goz_code, factor_min, factor_max, factor_default, is_proposed, is_mandatory, motivation_required, sort_order,
     suggestion_de, suggestion_nl, suggestion_en)
VALUES
    (@g_fil2, 'Anlegen eines Kofferdams', 'Aanbrengen kofferdam', 'Rubber dam application', '0080', 1.00, 3.50, 2.30, 0, 0, 0, 10,
     'Empfohlen für beste Adhäsivresultate.', 'Aanbevolen voor optimaal adhesiefresultaat.', 'Recommended for best adhesive results.'),
    (@g_fil2, 'Legen einer Unterfüllung / Kavitätenisolierung', 'Aanbrengen ondervulling / caviteitsisolatie', 'Placement of liner / cavity isolation', '2050', 1.00, 3.50, 2.30, 0, 0, 0, 20,
     NULL, NULL, NULL);

-- Prestaties Groep 3: Restauration (onderling uitsluitend)
INSERT INTO treatment_items
    (group_id, name_de, name_nl, name_en, goz_code, factor_min, factor_max, factor_default, is_proposed, is_mandatory, motivation_required, sort_order)
VALUES
    (@g_fil3, 'Kompositfüllung, einflächig', 'Composietvulling, één vlak', 'Composite filling, one surface', '2060', 1.00, 3.50, 2.30, 1, 0, 0, 10),
    (@g_fil3, 'Kompositfüllung, zweiflächig', 'Composietvulling, twee vlakken', 'Composite filling, two surfaces', '2070', 1.00, 3.50, 2.30, 0, 0, 0, 20),
    (@g_fil3, 'Kompositfüllung, dreiflächig', 'Composietvulling, drie vlakken', 'Composite filling, three surfaces', '2080', 1.00, 3.50, 2.30, 0, 0, 0, 30),
    (@g_fil3, 'Kompositfüllung, vier- und mehrflächig', 'Composietvulling, vier of meer vlakken', 'Composite filling, four or more surfaces', '2090', 1.00, 3.50, 2.30, 0, 0, 0, 40);

-- Uitsluitingen instellen voor vulling-items (één per behandeling)
-- Item-IDs ophalen via variabelen
SET @i_fil1f = (SELECT id FROM treatment_items WHERE group_id = @g_fil3 AND goz_code = '2060' LIMIT 1);
SET @i_fil2f = (SELECT id FROM treatment_items WHERE group_id = @g_fil3 AND goz_code = '2070' LIMIT 1);
SET @i_fil3f = (SELECT id FROM treatment_items WHERE group_id = @g_fil3 AND goz_code = '2080' LIMIT 1);
SET @i_fil4f = (SELECT id FROM treatment_items WHERE group_id = @g_fil3 AND goz_code = '2090' LIMIT 1);

-- Elk vulling-item sluit de andere drie uit
INSERT INTO treatment_exclusions (item_id, excludes_item_id) VALUES
    (@i_fil1f, @i_fil2f), (@i_fil1f, @i_fil3f), (@i_fil1f, @i_fil4f),
    (@i_fil2f, @i_fil1f), (@i_fil2f, @i_fil3f), (@i_fil2f, @i_fil4f),
    (@i_fil3f, @i_fil1f), (@i_fil3f, @i_fil2f), (@i_fil3f, @i_fil4f),
    (@i_fil4f, @i_fil1f), (@i_fil4f, @i_fil2f), (@i_fil4f, @i_fil3f);

-- Prestaties Groep 4: Adhäsivtechnik
INSERT INTO treatment_items
    (group_id, name_de, name_nl, name_en, goz_code, factor_min, factor_max, factor_default, is_proposed, is_mandatory, motivation_required, sort_order)
VALUES
    (@g_fil4, 'Ätzung, Grundierung und Adhäsivauftrag (Schmelz + Dentin)', 'Etsen, primer en adhesiefaanbrenging (email + dentine)', 'Etching, priming and adhesive application (enamel + dentine)', '2100', 1.00, 3.50, 2.30, 1, 0, 0, 10);


-- =============================================
-- BEHANDELTYPE 4: Endodontie (Endo)
-- =============================================
INSERT INTO treatment_types (name_de, name_nl, name_en, sort_order) VALUES
    ('Endodontie', 'Endodontie', 'Endodontics', 40);
SET @t_endo = LAST_INSERT_ID();

-- Groepen
INSERT INTO treatment_groups (treatment_type_id, name_de, name_nl, name_en, sort_order) VALUES
    (@t_endo, 'Befund & Diagnose', 'Bevinding & Diagnose', 'Assessment & Diagnosis', 10);
SET @g_endo1 = LAST_INSERT_ID();

INSERT INTO treatment_groups (treatment_type_id, name_de, name_nl, name_en, sort_order) VALUES
    (@t_endo, 'Trepanation & Pulpaentfernung', 'Trepanatie & Pulpaverwijdering', 'Trepanation & Pulp Removal', 20);
SET @g_endo2 = LAST_INSERT_ID();

INSERT INTO treatment_groups (treatment_type_id, name_de, name_nl, name_en, sort_order) VALUES
    (@t_endo, 'Kanalaufbereitung', 'Kanaalbereiding', 'Canal Preparation', 30);
SET @g_endo3 = LAST_INSERT_ID();

INSERT INTO treatment_groups (treatment_type_id, name_de, name_nl, name_en, sort_order) VALUES
    (@t_endo, 'Medikamentöse Einlage', 'Medicamenteuze tussenbehandeling', 'Intracanal Medication', 40);
SET @g_endo4 = LAST_INSERT_ID();

INSERT INTO treatment_groups (treatment_type_id, name_de, name_nl, name_en, sort_order) VALUES
    (@t_endo, 'Wurzelkanalfüllung', 'Wortelkanaalvulling', 'Root Canal Filling', 50);
SET @g_endo5 = LAST_INSERT_ID();

-- Prestaties Groep 1: Befund
INSERT INTO treatment_items
    (group_id, name_de, name_nl, name_en, goz_code, factor_min, factor_max, factor_default, is_proposed, is_mandatory, motivation_required, sort_order)
VALUES
    (@g_endo1, 'Eingehende Untersuchung und Beratung', 'Uitgebreid onderzoek en advies', 'Comprehensive examination and consultation', '0010', 1.00, 3.50, 2.30, 1, 1, 0, 10),
    (@g_endo1, 'Vitalitätsprüfung der Pulpa', 'Vitaliteitstest pulpa', 'Pulp vitality testing', '0030', 1.00, 3.50, 2.30, 1, 0, 0, 20);

-- Prestaties Groep 2: Trepanation
INSERT INTO treatment_items
    (group_id, name_de, name_nl, name_en, goz_code, factor_min, factor_max, factor_default, is_proposed, is_mandatory, motivation_required, sort_order)
VALUES
    (@g_endo2, 'Trepanation der Pulpakammer', 'Trepanatie pulpakamer', 'Trepanation of pulp chamber', '2410', 1.00, 3.50, 2.30, 1, 1, 0, 10),
    (@g_endo2, 'Entfernung des erkrankten Pulpagewebes', 'Verwijdering aangetast pulpaweefsel', 'Removal of diseased pulp tissue', '2420', 1.00, 3.50, 2.30, 1, 0, 0, 20);

-- Prestaties Groep 3: Kanalaufbereitung (per Kanal)
INSERT INTO treatment_items
    (group_id, name_de, name_nl, name_en, goz_code, factor_min, factor_max, factor_default, is_proposed, is_mandatory, motivation_required, sort_order,
     suggestion_de, suggestion_nl, suggestion_en)
VALUES
    (@g_endo3, 'Aufbereitung eines Wurzelkanals (je Kanal)', 'Bereiding van een wortelkanaal (per kanaal)', 'Preparation of a root canal (per canal)', '2440', 1.00, 3.50, 2.30, 1, 1, 0, 10,
     'Mehrfach ansetzbar: einmal je aufbereiteten Kanal.', 'Meerdere keren declarabel: eenmaal per bewerkt kanaal.', 'Billable multiple times: once per prepared canal.'),
    (@g_endo3, 'Instrumentenentfernung aus Wurzelkanal', 'Verwijdering instrument uit wortelkanaal', 'Removal of instrument from root canal', '2450', 1.00, 3.50, 2.30, 0, 0, 1, 20,
     'Nur bei frakturiertem Instrument ansetzbar.', 'Alleen declarabel bij gefractureerd instrument.', 'Only billable for fractured instrument.');

-- Prestaties Groep 4: Medikamentöse Einlage
INSERT INTO treatment_items
    (group_id, name_de, name_nl, name_en, goz_code, factor_min, factor_max, factor_default, is_proposed, is_mandatory, motivation_required, sort_order)
VALUES
    (@g_endo4, 'Medikamentöse Einlage in Wurzelkanal (je Kanal)', 'Medicamenteuze inleg wortelkanaal (per kanaal)', 'Intracanal medicament placement (per canal)', '2460', 1.00, 3.50, 2.30, 1, 0, 0, 10);

-- Prestaties Groep 5: Wurzelkanalfüllung
INSERT INTO treatment_items
    (group_id, name_de, name_nl, name_en, goz_code, factor_min, factor_max, factor_default, is_proposed, is_mandatory, motivation_required, sort_order,
     suggestion_de, suggestion_nl, suggestion_en)
VALUES
    (@g_endo5, 'Füllung eines Wurzelkanals (je Kanal)', 'Vulling van een wortelkanaal (per kanaal)', 'Filling of a root canal (per canal)', '2470', 1.00, 3.50, 2.30, 1, 1, 0, 10,
     'Mehrfach ansetzbar: einmal je gefülltem Kanal.', 'Meerdere keren declarabel: eenmaal per gevuld kanaal.', 'Billable multiple times: once per filled canal.'),
    (@g_endo5, 'Retrograde Wurzelkanalfüllung (je Kanal)', 'Retrograde wortelkanaalvulling (per kanaal)', 'Retrograde root canal filling (per canal)', '2480', 1.00, 3.50, 2.30, 0, 0, 1, 20,
     'Nur in Kombination mit Wurzelspitzenresektion ansetzbar.', 'Alleen declarabel in combinatie met apexresectie.', 'Only billable in combination with apicectomy.');

SET FOREIGN_KEY_CHECKS = 1;
