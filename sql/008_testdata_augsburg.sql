-- ============================================================
-- 008_testdata_augsburg.sql
-- Fictieve patiënten & afspraken voor Augsburg — Mendy de Jonghe
-- Periode: 2026-04-27 t/m 2026-05-22
-- Vereist: migraties 001 t/m 007 uitgevoerd
-- LET OP: kan veilig opnieuw worden uitgevoerd (cleanup bovenaan)
-- ============================================================

-- Ruim eerder ingevoerde testdata op (bij heruitvoering)
DELETE FROM patients WHERE charly_id BETWEEN '20001' AND '20015';

-- ============================================================
-- Context: zoek Augsburg-praktijk en Mendy de Jonghe
-- ============================================================
SET @pid  = (SELECT id FROM practices WHERE name = 'Augsburg' LIMIT 1);
SET @pr1  = (SELECT id FROM users WHERE display_name = 'Mendy de Jonghe' AND role = 'practitioner' AND active = 1 LIMIT 1);

-- Fallback: zoek op elke actieve practitioner bij Augsburg
SET @pr1  = IF(@pr1 IS NULL OR @pr1 = 0,
               (SELECT id FROM users WHERE practice_id = @pid AND role = 'practitioner' AND active = 1 LIMIT 1),
               @pr1);

-- Behandeltypen
SET @tt_pzr = (SELECT id FROM treatment_types WHERE name_de LIKE '%Prophylaxe%' LIMIT 1);
SET @tt_kon = (SELECT id FROM treatment_types WHERE name_de LIKE '%Konsultation%' LIMIT 1);
SET @tt_ful = (SELECT id FROM treatment_types WHERE name_de LIKE '%Füllung%' LIMIT 1);
SET @tt_end = (SELECT id FROM treatment_types WHERE name_de LIKE '%Endodontie%' LIMIT 1);

-- ============================================================
-- 15 fictieve patiënten bij Augsburg
-- ============================================================
INSERT INTO patients (practice_id, first_name, last_name, birth_date, charly_id, active) VALUES (@pid, 'Lukas',    'Meier',      '1988-02-14', '20001', 1); SET @p1  = LAST_INSERT_ID();
INSERT INTO patients (practice_id, first_name, last_name, birth_date, charly_id, active) VALUES (@pid, 'Sophie',   'Huber',      '1993-07-08', '20002', 1); SET @p2  = LAST_INSERT_ID();
INSERT INTO patients (practice_id, first_name, last_name, birth_date, charly_id, active) VALUES (@pid, 'Felix',    'Gruber',     '1979-11-25', '20003', 1); SET @p3  = LAST_INSERT_ID();
INSERT INTO patients (practice_id, first_name, last_name, birth_date, charly_id, active) VALUES (@pid, 'Emma',     'Bauer',      '1996-04-03', '20004', 1); SET @p4  = LAST_INSERT_ID();
INSERT INTO patients (practice_id, first_name, last_name, birth_date, charly_id, active) VALUES (@pid, 'Jonas',    'Fuchs',      '1984-09-19', '20005', 1); SET @p5  = LAST_INSERT_ID();
INSERT INTO patients (practice_id, first_name, last_name, birth_date, charly_id, active) VALUES (@pid, 'Lena',     'Maier',      '1991-06-11', '20006', 1); SET @p6  = LAST_INSERT_ID();
INSERT INTO patients (practice_id, first_name, last_name, birth_date, charly_id, active) VALUES (@pid, 'Tobias',   'Schmid',     '1975-01-30', '20007', 1); SET @p7  = LAST_INSERT_ID();
INSERT INTO patients (practice_id, first_name, last_name, birth_date, charly_id, active) VALUES (@pid, 'Hannah',   'Keller',     '1989-08-22', '20008', 1); SET @p8  = LAST_INSERT_ID();
INSERT INTO patients (practice_id, first_name, last_name, birth_date, charly_id, active) VALUES (@pid, 'Markus',   'Fischer',    '1982-03-16', '20009', 1); SET @p9  = LAST_INSERT_ID();
INSERT INTO patients (practice_id, first_name, last_name, birth_date, charly_id, active) VALUES (@pid, 'Lisa',     'Schneider',  '1998-12-04', '20010', 1); SET @p10 = LAST_INSERT_ID();
INSERT INTO patients (practice_id, first_name, last_name, birth_date, charly_id, active) VALUES (@pid, 'Stefan',   'Zimmermann', '1971-05-27', '20011', 1); SET @p11 = LAST_INSERT_ID();
INSERT INTO patients (practice_id, first_name, last_name, birth_date, charly_id, active) VALUES (@pid, 'Julia',    'Hofmann',    '1986-10-13', '20012', 1); SET @p12 = LAST_INSERT_ID();
INSERT INTO patients (practice_id, first_name, last_name, birth_date, charly_id, active) VALUES (@pid, 'Andreas',  'Lang',       '1994-02-07', '20013', 1); SET @p13 = LAST_INSERT_ID();
INSERT INTO patients (practice_id, first_name, last_name, birth_date, charly_id, active) VALUES (@pid, 'Nicole',   'Schwarz',    '1980-07-18', '20014', 1); SET @p14 = LAST_INSERT_ID();
INSERT INTO patients (practice_id, first_name, last_name, birth_date, charly_id, active) VALUES (@pid, 'Christian','Wagner',     '1973-09-01', '20015', 1); SET @p15 = LAST_INSERT_ID();

-- ============================================================
-- Afspraken voor Mendy de Jonghe — week 1: 27 april – 1 mei
-- ============================================================

-- Maandag 27 april
INSERT INTO appointments (practice_id, patient_id, practitioner_id, treatment_type_id, scheduled_at, duration_min, status) VALUES (@pid, @p1,  @pr1, @tt_pzr, '2026-04-27 08:00:00', 60, 'planned');
INSERT INTO appointments (practice_id, patient_id, practitioner_id, treatment_type_id, scheduled_at, duration_min, status) VALUES (@pid, @p2,  @pr1, @tt_kon, '2026-04-27 09:30:00', 30, 'planned');
INSERT INTO appointments (practice_id, patient_id, practitioner_id, treatment_type_id, scheduled_at, duration_min, status) VALUES (@pid, @p3,  @pr1, @tt_ful, '2026-04-27 13:00:00', 45, 'planned');

-- Dinsdag 28 april
INSERT INTO appointments (practice_id, patient_id, practitioner_id, treatment_type_id, scheduled_at, duration_min, status) VALUES (@pid, @p4,  @pr1, @tt_end, '2026-04-28 08:00:00', 90, 'planned');
INSERT INTO appointments (practice_id, patient_id, practitioner_id, treatment_type_id, scheduled_at, duration_min, status) VALUES (@pid, @p5,  @pr1, @tt_kon, '2026-04-28 10:00:00', 30, 'planned');
INSERT INTO appointments (practice_id, patient_id, practitioner_id, treatment_type_id, scheduled_at, duration_min, status) VALUES (@pid, @p6,  @pr1, @tt_pzr, '2026-04-28 13:00:00', 60, 'planned');

-- Woensdag 29 april
INSERT INTO appointments (practice_id, patient_id, practitioner_id, treatment_type_id, scheduled_at, duration_min, status) VALUES (@pid, @p7,  @pr1, @tt_ful, '2026-04-29 08:00:00', 45, 'planned');
INSERT INTO appointments (practice_id, patient_id, practitioner_id, treatment_type_id, scheduled_at, duration_min, status) VALUES (@pid, @p8,  @pr1, @tt_kon, '2026-04-29 09:00:00', 30, 'planned');
INSERT INTO appointments (practice_id, patient_id, practitioner_id, treatment_type_id, scheduled_at, duration_min, status) VALUES (@pid, @p9,  @pr1, @tt_pzr, '2026-04-29 13:00:00', 60, 'planned');

-- Donderdag 30 april
INSERT INTO appointments (practice_id, patient_id, practitioner_id, treatment_type_id, scheduled_at, duration_min, status) VALUES (@pid, @p10, @pr1, @tt_end, '2026-04-30 08:00:00', 90, 'planned');
INSERT INTO appointments (practice_id, patient_id, practitioner_id, treatment_type_id, scheduled_at, duration_min, status) VALUES (@pid, @p11, @pr1, @tt_kon, '2026-04-30 10:30:00', 30, 'planned');

-- Vrijdag 1 mei
INSERT INTO appointments (practice_id, patient_id, practitioner_id, treatment_type_id, scheduled_at, duration_min, status) VALUES (@pid, @p12, @pr1, @tt_pzr, '2026-05-01 09:00:00', 60, 'planned');

-- ============================================================
-- Week 2: 4 mei – 8 mei
-- ============================================================

-- Maandag 4 mei
INSERT INTO appointments (practice_id, patient_id, practitioner_id, treatment_type_id, scheduled_at, duration_min, status) VALUES (@pid, @p13, @pr1, @tt_ful, '2026-05-04 08:00:00', 45, 'planned');
INSERT INTO appointments (practice_id, patient_id, practitioner_id, treatment_type_id, scheduled_at, duration_min, status) VALUES (@pid, @p14, @pr1, @tt_pzr, '2026-05-04 09:00:00', 60, 'planned');
INSERT INTO appointments (practice_id, patient_id, practitioner_id, treatment_type_id, scheduled_at, duration_min, status) VALUES (@pid, @p15, @pr1, @tt_kon, '2026-05-04 13:00:00', 30, 'planned');

-- Dinsdag 5 mei
INSERT INTO appointments (practice_id, patient_id, practitioner_id, treatment_type_id, scheduled_at, duration_min, status) VALUES (@pid, @p1,  @pr1, @tt_end, '2026-05-05 08:00:00', 90, 'planned');
INSERT INTO appointments (practice_id, patient_id, practitioner_id, treatment_type_id, scheduled_at, duration_min, status) VALUES (@pid, @p2,  @pr1, @tt_kon, '2026-05-05 10:30:00', 30, 'planned');

-- Woensdag 6 mei
INSERT INTO appointments (practice_id, patient_id, practitioner_id, treatment_type_id, scheduled_at, duration_min, status) VALUES (@pid, @p3,  @pr1, @tt_pzr, '2026-05-06 08:00:00', 60, 'planned');
INSERT INTO appointments (practice_id, patient_id, practitioner_id, treatment_type_id, scheduled_at, duration_min, status) VALUES (@pid, @p4,  @pr1, @tt_ful, '2026-05-06 13:00:00', 45, 'planned');

-- Donderdag 7 mei
INSERT INTO appointments (practice_id, patient_id, practitioner_id, treatment_type_id, scheduled_at, duration_min, status) VALUES (@pid, @p5,  @pr1, @tt_kon, '2026-05-07 08:00:00', 30, 'planned');
INSERT INTO appointments (practice_id, patient_id, practitioner_id, treatment_type_id, scheduled_at, duration_min, status) VALUES (@pid, @p6,  @pr1, @tt_end, '2026-05-07 09:00:00', 90, 'planned');

-- Vrijdag 8 mei
INSERT INTO appointments (practice_id, patient_id, practitioner_id, treatment_type_id, scheduled_at, duration_min, status) VALUES (@pid, @p7,  @pr1, @tt_pzr, '2026-05-08 08:00:00', 60, 'planned');
INSERT INTO appointments (practice_id, patient_id, practitioner_id, treatment_type_id, scheduled_at, duration_min, status) VALUES (@pid, @p8,  @pr1, @tt_kon, '2026-05-08 09:30:00', 30, 'planned');

-- ============================================================
-- Week 3: 11 mei – 15 mei
-- ============================================================

-- Maandag 11 mei
INSERT INTO appointments (practice_id, patient_id, practitioner_id, treatment_type_id, scheduled_at, duration_min, status) VALUES (@pid, @p9,  @pr1, @tt_ful, '2026-05-11 08:00:00', 45, 'planned');
INSERT INTO appointments (practice_id, patient_id, practitioner_id, treatment_type_id, scheduled_at, duration_min, status) VALUES (@pid, @p10, @pr1, @tt_pzr, '2026-05-11 09:00:00', 60, 'planned');
INSERT INTO appointments (practice_id, patient_id, practitioner_id, treatment_type_id, scheduled_at, duration_min, status) VALUES (@pid, @p11, @pr1, @tt_kon, '2026-05-11 13:30:00', 30, 'planned');

-- Dinsdag 12 mei
INSERT INTO appointments (practice_id, patient_id, practitioner_id, treatment_type_id, scheduled_at, duration_min, status) VALUES (@pid, @p12, @pr1, @tt_end, '2026-05-12 08:00:00', 90, 'planned');
INSERT INTO appointments (practice_id, patient_id, practitioner_id, treatment_type_id, scheduled_at, duration_min, status) VALUES (@pid, @p13, @pr1, @tt_kon, '2026-05-12 10:30:00', 30, 'planned');

-- Woensdag 13 mei
INSERT INTO appointments (practice_id, patient_id, practitioner_id, treatment_type_id, scheduled_at, duration_min, status) VALUES (@pid, @p14, @pr1, @tt_pzr, '2026-05-13 08:00:00', 60, 'planned');
INSERT INTO appointments (practice_id, patient_id, practitioner_id, treatment_type_id, scheduled_at, duration_min, status) VALUES (@pid, @p15, @pr1, @tt_ful, '2026-05-13 13:00:00', 45, 'planned');

-- Donderdag 14 mei (Christi Himmelfahrt — beperkt spreekuur)
INSERT INTO appointments (practice_id, patient_id, practitioner_id, treatment_type_id, scheduled_at, duration_min, status) VALUES (@pid, @p1,  @pr1, @tt_kon, '2026-05-14 10:00:00', 30, 'planned');

-- Vrijdag 15 mei
INSERT INTO appointments (practice_id, patient_id, practitioner_id, treatment_type_id, scheduled_at, duration_min, status) VALUES (@pid, @p2,  @pr1, @tt_pzr, '2026-05-15 08:00:00', 60, 'planned');
INSERT INTO appointments (practice_id, patient_id, practitioner_id, treatment_type_id, scheduled_at, duration_min, status) VALUES (@pid, @p3,  @pr1, @tt_end, '2026-05-15 09:30:00', 90, 'planned');

-- ============================================================
-- Week 4: 18 mei – 22 mei
-- ============================================================

-- Maandag 18 mei
INSERT INTO appointments (practice_id, patient_id, practitioner_id, treatment_type_id, scheduled_at, duration_min, status) VALUES (@pid, @p4,  @pr1, @tt_kon, '2026-05-18 08:00:00', 30, 'planned');
INSERT INTO appointments (practice_id, patient_id, practitioner_id, treatment_type_id, scheduled_at, duration_min, status) VALUES (@pid, @p5,  @pr1, @tt_pzr, '2026-05-18 09:00:00', 60, 'planned');
INSERT INTO appointments (practice_id, patient_id, practitioner_id, treatment_type_id, scheduled_at, duration_min, status) VALUES (@pid, @p6,  @pr1, @tt_ful, '2026-05-18 13:00:00', 45, 'planned');

-- Dinsdag 19 mei
INSERT INTO appointments (practice_id, patient_id, practitioner_id, treatment_type_id, scheduled_at, duration_min, status) VALUES (@pid, @p7,  @pr1, @tt_end, '2026-05-19 08:00:00', 90, 'planned');
INSERT INTO appointments (practice_id, patient_id, practitioner_id, treatment_type_id, scheduled_at, duration_min, status) VALUES (@pid, @p8,  @pr1, @tt_kon, '2026-05-19 13:30:00', 30, 'planned');

-- Woensdag 20 mei
INSERT INTO appointments (practice_id, patient_id, practitioner_id, treatment_type_id, scheduled_at, duration_min, status) VALUES (@pid, @p9,  @pr1, @tt_pzr, '2026-05-20 08:00:00', 60, 'planned');
INSERT INTO appointments (practice_id, patient_id, practitioner_id, treatment_type_id, scheduled_at, duration_min, status) VALUES (@pid, @p10, @pr1, @tt_ful, '2026-05-20 13:00:00', 45, 'planned');

-- Donderdag 21 mei
INSERT INTO appointments (practice_id, patient_id, practitioner_id, treatment_type_id, scheduled_at, duration_min, status) VALUES (@pid, @p11, @pr1, @tt_kon, '2026-05-21 08:00:00', 30, 'planned');
INSERT INTO appointments (practice_id, patient_id, practitioner_id, treatment_type_id, scheduled_at, duration_min, status) VALUES (@pid, @p12, @pr1, @tt_end, '2026-05-21 09:30:00', 90, 'planned');

-- Vrijdag 22 mei
INSERT INTO appointments (practice_id, patient_id, practitioner_id, treatment_type_id, scheduled_at, duration_min, status) VALUES (@pid, @p13, @pr1, @tt_pzr, '2026-05-22 08:00:00', 60, 'planned');
INSERT INTO appointments (practice_id, patient_id, practitioner_id, treatment_type_id, scheduled_at, duration_min, status) VALUES (@pid, @p14, @pr1, @tt_kon, '2026-05-22 09:30:00', 30, 'planned');

-- Klaar: 15 patiënten, 42 afspraken voor Augsburg/Mendy de Jonghe.
