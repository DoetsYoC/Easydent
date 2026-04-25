-- ============================================================
-- 006_testdata.sql
-- Fictieve patiënten & afspraken, 2026-04-27 t/m 2026-05-22
-- Vereist: migraties 001 t/m 005 uitgevoerd
-- LET OP: kan veilig opnieuw worden uitgevoerd (cleanup bovenaan)
-- ============================================================

-- Ruim eerder ingevoerde testdata op (bij heruitvoering)
DELETE FROM patients WHERE charly_id BETWEEN '10001' AND '10025';

-- ============================================================
-- Context: zoek eerst een practitioner, gebruik diens praktijk
-- (zo werkt het ook als de eerste praktijk geen practitioners heeft)
-- ============================================================
SET @pr1 = (SELECT id FROM users WHERE role = 'practitioner' AND active = 1 ORDER BY id LIMIT 1);
SET @pid = (SELECT practice_id FROM users WHERE id = @pr1);
SET @pr2 = (SELECT id FROM users WHERE role = 'practitioner' AND practice_id = @pid AND active = 1 AND id != @pr1 ORDER BY id LIMIT 1);
SET @pr2 = IF(@pr2 IS NULL OR @pr2 = 0, @pr1, @pr2);

-- Behandeltypen ophalen (NULL als seed nog niet is uitgevoerd — geen probleem)
SET @tt_pzr = (SELECT id FROM treatment_types WHERE name_de LIKE '%Prophylaxe%' LIMIT 1);
SET @tt_kon = (SELECT id FROM treatment_types WHERE name_de LIKE '%Konsultation%' LIMIT 1);
SET @tt_ful = (SELECT id FROM treatment_types WHERE name_de LIKE '%Füllung%' LIMIT 1);
SET @tt_end = (SELECT id FROM treatment_types WHERE name_de LIKE '%Endodontie%' LIMIT 1);

-- ============================================================
-- 25 fictieve patiënten
-- ============================================================
INSERT INTO patients (practice_id, first_name, last_name, birth_date, charly_id, active)
VALUES (@pid, 'Thomas', 'Müller', '1985-03-15', '10001', 1);
SET @p1 = LAST_INSERT_ID();

INSERT INTO patients (practice_id, first_name, last_name, birth_date, charly_id, active)
VALUES (@pid, 'Anna', 'Schmidt', '1990-07-22', '10002', 1);
SET @p2 = LAST_INSERT_ID();

INSERT INTO patients (practice_id, first_name, last_name, birth_date, charly_id, active)
VALUES (@pid, 'Klaus', 'Weber', '1978-11-08', '10003', 1);
SET @p3 = LAST_INSERT_ID();

INSERT INTO patients (practice_id, first_name, last_name, birth_date, charly_id, active)
VALUES (@pid, 'Maria', 'Fischer', '1995-04-30', '10004', 1);
SET @p4 = LAST_INSERT_ID();

INSERT INTO patients (practice_id, first_name, last_name, birth_date, charly_id, active)
VALUES (@pid, 'Stefan', 'Bauer', '1982-09-12', '10005', 1);
SET @p5 = LAST_INSERT_ID();

INSERT INTO patients (practice_id, first_name, last_name, birth_date, charly_id, active)
VALUES (@pid, 'Laura', 'Schneider', '1988-06-05', '10006', 1);
SET @p6 = LAST_INSERT_ID();

INSERT INTO patients (practice_id, first_name, last_name, birth_date, charly_id, active)
VALUES (@pid, 'Michael', 'Wagner', '1973-02-28', '10007', 1);
SET @p7 = LAST_INSERT_ID();

INSERT INTO patients (practice_id, first_name, last_name, birth_date, charly_id, active)
VALUES (@pid, 'Petra', 'Hoffmann', '1992-12-19', '10008', 1);
SET @p8 = LAST_INSERT_ID();

INSERT INTO patients (practice_id, first_name, last_name, birth_date, charly_id, active)
VALUES (@pid, 'Andreas', 'Richter', '1968-05-14', '10009', 1);
SET @p9 = LAST_INSERT_ID();

INSERT INTO patients (practice_id, first_name, last_name, birth_date, charly_id, active)
VALUES (@pid, 'Sabine', 'Koch', '1986-08-03', '10010', 1);
SET @p10 = LAST_INSERT_ID();

INSERT INTO patients (practice_id, first_name, last_name, birth_date, charly_id, active)
VALUES (@pid, 'Christian', 'Becker', '1995-01-25', '10011', 1);
SET @p11 = LAST_INSERT_ID();

INSERT INTO patients (practice_id, first_name, last_name, birth_date, charly_id, active)
VALUES (@pid, 'Sandra', 'Schäfer', '1979-10-17', '10012', 1);
SET @p12 = LAST_INSERT_ID();

INSERT INTO patients (practice_id, first_name, last_name, birth_date, charly_id, active)
VALUES (@pid, 'Frank', 'Klein', '1983-04-09', '10013', 1);
SET @p13 = LAST_INSERT_ID();

INSERT INTO patients (practice_id, first_name, last_name, birth_date, charly_id, active)
VALUES (@pid, 'Julia', 'Wolf', '1997-07-31', '10014', 1);
SET @p14 = LAST_INSERT_ID();

INSERT INTO patients (practice_id, first_name, last_name, birth_date, charly_id, active)
VALUES (@pid, 'Markus', 'Schröder', '1971-03-22', '10015', 1);
SET @p15 = LAST_INSERT_ID();

INSERT INTO patients (practice_id, first_name, last_name, birth_date, charly_id, active)
VALUES (@pid, 'Monika', 'Neumann', '1989-11-14', '10016', 1);
SET @p16 = LAST_INSERT_ID();

INSERT INTO patients (practice_id, first_name, last_name, birth_date, charly_id, active)
VALUES (@pid, 'Jens', 'Braun', '1976-08-27', '10017', 1);
SET @p17 = LAST_INSERT_ID();

INSERT INTO patients (practice_id, first_name, last_name, birth_date, charly_id, active)
VALUES (@pid, 'Christine', 'Zimmermann', '1993-05-06', '10018', 1);
SET @p18 = LAST_INSERT_ID();

INSERT INTO patients (practice_id, first_name, last_name, birth_date, charly_id, active)
VALUES (@pid, 'Ralf', 'Krüger', '1981-02-18', '10019', 1);
SET @p19 = LAST_INSERT_ID();

INSERT INTO patients (practice_id, first_name, last_name, birth_date, charly_id, active)
VALUES (@pid, 'Heike', 'Hartmann', '1987-09-29', '10020', 1);
SET @p20 = LAST_INSERT_ID();

INSERT INTO patients (practice_id, first_name, last_name, birth_date, charly_id, active)
VALUES (@pid, 'Dirk', 'Lange', '1969-12-11', '10021', 1);
SET @p21 = LAST_INSERT_ID();

INSERT INTO patients (practice_id, first_name, last_name, birth_date, charly_id, active)
VALUES (@pid, 'Anja', 'Schwarz', '1994-06-23', '10022', 1);
SET @p22 = LAST_INSERT_ID();

INSERT INTO patients (practice_id, first_name, last_name, birth_date, charly_id, active)
VALUES (@pid, 'Thorsten', 'Werner', '1980-01-07', '10023', 1);
SET @p23 = LAST_INSERT_ID();

INSERT INTO patients (practice_id, first_name, last_name, birth_date, charly_id, active)
VALUES (@pid, 'Katrin', 'Lehmann', '1991-04-16', '10024', 1);
SET @p24 = LAST_INSERT_ID();

INSERT INTO patients (practice_id, first_name, last_name, birth_date, charly_id, active)
VALUES (@pid, 'Oliver', 'Köhler', '1975-07-04', '10025', 1);
SET @p25 = LAST_INSERT_ID();

-- ============================================================
-- Afspraken (86 stuks over 20 werkdagen)
-- Behandeltypen: PZR=60min, Kontrolle=30min, Füllung=45min, Endodontie=90min
-- ============================================================

-- ── Week 1: 27 april – 1 mei ─────────────────────────────

-- Maandag 27 april
INSERT INTO appointments (practice_id, patient_id, practitioner_id, treatment_type, treatment_type_id, scheduled_at, duration_min, status) VALUES (@pid, @p1,  @pr1, 'PZR',              @tt_pzr, '2026-04-27 08:00:00', 60, 'planned');
INSERT INTO appointments (practice_id, patient_id, practitioner_id, treatment_type, treatment_type_id, scheduled_at, duration_min, status) VALUES (@pid, @p2,  @pr2, 'Kontrolle',         @tt_kon, '2026-04-27 08:00:00', 30, 'planned');
INSERT INTO appointments (practice_id, patient_id, practitioner_id, treatment_type, treatment_type_id, scheduled_at, duration_min, status) VALUES (@pid, @p3,  @pr1, 'Fuellungstherapie', @tt_ful, '2026-04-27 10:00:00', 45, 'planned');
INSERT INTO appointments (practice_id, patient_id, practitioner_id, treatment_type, treatment_type_id, scheduled_at, duration_min, status) VALUES (@pid, @p4,  @pr2, 'Endodontie',        @tt_end, '2026-04-27 09:30:00', 90, 'planned');
INSERT INTO appointments (practice_id, patient_id, practitioner_id, treatment_type, treatment_type_id, scheduled_at, duration_min, status) VALUES (@pid, @p5,  @pr1, 'Kontrolle',         @tt_kon, '2026-04-27 13:00:00', 30, 'planned');
INSERT INTO appointments (practice_id, patient_id, practitioner_id, treatment_type, treatment_type_id, scheduled_at, duration_min, status) VALUES (@pid, @p6,  @pr2, 'PZR',              @tt_pzr, '2026-04-27 13:00:00', 60, 'planned');

-- Dinsdag 28 april
INSERT INTO appointments (practice_id, patient_id, practitioner_id, treatment_type, treatment_type_id, scheduled_at, duration_min, status) VALUES (@pid, @p7,  @pr1, 'Endodontie',        @tt_end, '2026-04-28 08:00:00', 90, 'planned');
INSERT INTO appointments (practice_id, patient_id, practitioner_id, treatment_type, treatment_type_id, scheduled_at, duration_min, status) VALUES (@pid, @p8,  @pr2, 'PZR',              @tt_pzr, '2026-04-28 08:00:00', 60, 'planned');
INSERT INTO appointments (practice_id, patient_id, practitioner_id, treatment_type, treatment_type_id, scheduled_at, duration_min, status) VALUES (@pid, @p9,  @pr1, 'Kontrolle',         @tt_kon, '2026-04-28 10:15:00', 30, 'planned');
INSERT INTO appointments (practice_id, patient_id, practitioner_id, treatment_type, treatment_type_id, scheduled_at, duration_min, status) VALUES (@pid, @p10, @pr2, 'Fuellungstherapie', @tt_ful, '2026-04-28 10:00:00', 45, 'planned');
INSERT INTO appointments (practice_id, patient_id, practitioner_id, treatment_type, treatment_type_id, scheduled_at, duration_min, status) VALUES (@pid, @p11, @pr1, 'Fuellungstherapie', @tt_ful, '2026-04-28 13:00:00', 45, 'planned');
INSERT INTO appointments (practice_id, patient_id, practitioner_id, treatment_type, treatment_type_id, scheduled_at, duration_min, status) VALUES (@pid, @p12, @pr2, 'Kontrolle',         @tt_kon, '2026-04-28 14:00:00', 30, 'planned');

-- Woensdag 29 april
INSERT INTO appointments (practice_id, patient_id, practitioner_id, treatment_type, treatment_type_id, scheduled_at, duration_min, status) VALUES (@pid, @p13, @pr1, 'PZR',              @tt_pzr, '2026-04-29 08:00:00', 60, 'planned');
INSERT INTO appointments (practice_id, patient_id, practitioner_id, treatment_type, treatment_type_id, scheduled_at, duration_min, status) VALUES (@pid, @p14, @pr2, 'Kontrolle',         @tt_kon, '2026-04-29 08:00:00', 30, 'planned');
INSERT INTO appointments (practice_id, patient_id, practitioner_id, treatment_type, treatment_type_id, scheduled_at, duration_min, status) VALUES (@pid, @p15, @pr1, 'Fuellungstherapie', @tt_ful, '2026-04-29 09:30:00', 45, 'planned');
INSERT INTO appointments (practice_id, patient_id, practitioner_id, treatment_type, treatment_type_id, scheduled_at, duration_min, status) VALUES (@pid, @p16, @pr2, 'Endodontie',        @tt_end, '2026-04-29 09:00:00', 90, 'planned');
INSERT INTO appointments (practice_id, patient_id, practitioner_id, treatment_type, treatment_type_id, scheduled_at, duration_min, status) VALUES (@pid, @p17, @pr1, 'Kontrolle',         @tt_kon, '2026-04-29 13:00:00', 30, 'planned');
INSERT INTO appointments (practice_id, patient_id, practitioner_id, treatment_type, treatment_type_id, scheduled_at, duration_min, status) VALUES (@pid, @p18, @pr2, 'PZR',              @tt_pzr, '2026-04-29 13:00:00', 60, 'planned');

-- Donderdag 30 april
INSERT INTO appointments (practice_id, patient_id, practitioner_id, treatment_type, treatment_type_id, scheduled_at, duration_min, status) VALUES (@pid, @p19, @pr1, 'Kontrolle',         @tt_kon, '2026-04-30 08:00:00', 30, 'planned');
INSERT INTO appointments (practice_id, patient_id, practitioner_id, treatment_type, treatment_type_id, scheduled_at, duration_min, status) VALUES (@pid, @p20, @pr2, 'Fuellungstherapie', @tt_ful, '2026-04-30 08:00:00', 45, 'planned');
INSERT INTO appointments (practice_id, patient_id, practitioner_id, treatment_type, treatment_type_id, scheduled_at, duration_min, status) VALUES (@pid, @p21, @pr1, 'PZR',              @tt_pzr, '2026-04-30 09:00:00', 60, 'planned');
INSERT INTO appointments (practice_id, patient_id, practitioner_id, treatment_type, treatment_type_id, scheduled_at, duration_min, status) VALUES (@pid, @p22, @pr2, 'Endodontie',        @tt_end, '2026-04-30 09:30:00', 90, 'planned');
INSERT INTO appointments (practice_id, patient_id, practitioner_id, treatment_type, treatment_type_id, scheduled_at, duration_min, status) VALUES (@pid, @p23, @pr1, 'Fuellungstherapie', @tt_ful, '2026-04-30 13:00:00', 45, 'planned');
INSERT INTO appointments (practice_id, patient_id, practitioner_id, treatment_type, treatment_type_id, scheduled_at, duration_min, status) VALUES (@pid, @p24, @pr2, 'Kontrolle',         @tt_kon, '2026-04-30 13:30:00', 30, 'planned');

-- Vrijdag 1 mei (Tag der Arbeit — beperkt spreekuur)
INSERT INTO appointments (practice_id, patient_id, practitioner_id, treatment_type, treatment_type_id, scheduled_at, duration_min, status) VALUES (@pid, @p25, @pr1, 'PZR',              @tt_pzr, '2026-05-01 09:00:00', 60, 'planned');
INSERT INTO appointments (practice_id, patient_id, practitioner_id, treatment_type, treatment_type_id, scheduled_at, duration_min, status) VALUES (@pid, @p1,  @pr2, 'Kontrolle',         @tt_kon, '2026-05-01 14:00:00', 30, 'planned');

-- ── Week 2: 4 mei – 8 mei ────────────────────────────────

-- Maandag 4 mei
INSERT INTO appointments (practice_id, patient_id, practitioner_id, treatment_type, treatment_type_id, scheduled_at, duration_min, status) VALUES (@pid, @p2,  @pr1, 'Endodontie',        @tt_end, '2026-05-04 08:00:00', 90, 'planned');
INSERT INTO appointments (practice_id, patient_id, practitioner_id, treatment_type, treatment_type_id, scheduled_at, duration_min, status) VALUES (@pid, @p3,  @pr2, 'PZR',              @tt_pzr, '2026-05-04 08:00:00', 60, 'planned');
INSERT INTO appointments (practice_id, patient_id, practitioner_id, treatment_type, treatment_type_id, scheduled_at, duration_min, status) VALUES (@pid, @p4,  @pr1, 'Kontrolle',         @tt_kon, '2026-05-04 10:15:00', 30, 'planned');
INSERT INTO appointments (practice_id, patient_id, practitioner_id, treatment_type, treatment_type_id, scheduled_at, duration_min, status) VALUES (@pid, @p5,  @pr2, 'Fuellungstherapie', @tt_ful, '2026-05-04 10:00:00', 45, 'planned');
INSERT INTO appointments (practice_id, patient_id, practitioner_id, treatment_type, treatment_type_id, scheduled_at, duration_min, status) VALUES (@pid, @p6,  @pr1, 'PZR',              @tt_pzr, '2026-05-04 13:00:00', 60, 'planned');
INSERT INTO appointments (practice_id, patient_id, practitioner_id, treatment_type, treatment_type_id, scheduled_at, duration_min, status) VALUES (@pid, @p7,  @pr2, 'Kontrolle',         @tt_kon, '2026-05-04 13:30:00', 30, 'planned');

-- Dinsdag 5 mei
INSERT INTO appointments (practice_id, patient_id, practitioner_id, treatment_type, treatment_type_id, scheduled_at, duration_min, status) VALUES (@pid, @p8,  @pr1, 'Fuellungstherapie', @tt_ful, '2026-05-05 08:00:00', 45, 'planned');
INSERT INTO appointments (practice_id, patient_id, practitioner_id, treatment_type, treatment_type_id, scheduled_at, duration_min, status) VALUES (@pid, @p9,  @pr2, 'Kontrolle',         @tt_kon, '2026-05-05 08:00:00', 30, 'planned');
INSERT INTO appointments (practice_id, patient_id, practitioner_id, treatment_type, treatment_type_id, scheduled_at, duration_min, status) VALUES (@pid, @p10, @pr1, 'Endodontie',        @tt_end, '2026-05-05 13:00:00', 90, 'planned');
INSERT INTO appointments (practice_id, patient_id, practitioner_id, treatment_type, treatment_type_id, scheduled_at, duration_min, status) VALUES (@pid, @p11, @pr2, 'PZR',              @tt_pzr, '2026-05-05 14:00:00', 60, 'planned');

-- Woensdag 6 mei
INSERT INTO appointments (practice_id, patient_id, practitioner_id, treatment_type, treatment_type_id, scheduled_at, duration_min, status) VALUES (@pid, @p12, @pr1, 'PZR',              @tt_pzr, '2026-05-06 08:00:00', 60, 'planned');
INSERT INTO appointments (practice_id, patient_id, practitioner_id, treatment_type, treatment_type_id, scheduled_at, duration_min, status) VALUES (@pid, @p13, @pr2, 'Fuellungstherapie', @tt_ful, '2026-05-06 08:00:00', 45, 'planned');
INSERT INTO appointments (practice_id, patient_id, practitioner_id, treatment_type, treatment_type_id, scheduled_at, duration_min, status) VALUES (@pid, @p14, @pr1, 'Kontrolle',         @tt_kon, '2026-05-06 13:00:00', 30, 'planned');
INSERT INTO appointments (practice_id, patient_id, practitioner_id, treatment_type, treatment_type_id, scheduled_at, duration_min, status) VALUES (@pid, @p15, @pr2, 'Endodontie',        @tt_end, '2026-05-06 14:30:00', 90, 'planned');

-- Donderdag 7 mei
INSERT INTO appointments (practice_id, patient_id, practitioner_id, treatment_type, treatment_type_id, scheduled_at, duration_min, status) VALUES (@pid, @p16, @pr1, 'Kontrolle',         @tt_kon, '2026-05-07 08:00:00', 30, 'planned');
INSERT INTO appointments (practice_id, patient_id, practitioner_id, treatment_type, treatment_type_id, scheduled_at, duration_min, status) VALUES (@pid, @p17, @pr2, 'PZR',              @tt_pzr, '2026-05-07 08:00:00', 60, 'planned');
INSERT INTO appointments (practice_id, patient_id, practitioner_id, treatment_type, treatment_type_id, scheduled_at, duration_min, status) VALUES (@pid, @p18, @pr1, 'Fuellungstherapie', @tt_ful, '2026-05-07 13:00:00', 45, 'planned');
INSERT INTO appointments (practice_id, patient_id, practitioner_id, treatment_type, treatment_type_id, scheduled_at, duration_min, status) VALUES (@pid, @p19, @pr2, 'Endodontie',        @tt_end, '2026-05-07 14:30:00', 90, 'planned');

-- Vrijdag 8 mei
INSERT INTO appointments (practice_id, patient_id, practitioner_id, treatment_type, treatment_type_id, scheduled_at, duration_min, status) VALUES (@pid, @p20, @pr1, 'Endodontie',        @tt_end, '2026-05-08 08:00:00', 90, 'planned');
INSERT INTO appointments (practice_id, patient_id, practitioner_id, treatment_type, treatment_type_id, scheduled_at, duration_min, status) VALUES (@pid, @p21, @pr2, 'PZR',              @tt_pzr, '2026-05-08 09:30:00', 60, 'planned');
INSERT INTO appointments (practice_id, patient_id, practitioner_id, treatment_type, treatment_type_id, scheduled_at, duration_min, status) VALUES (@pid, @p22, @pr1, 'Kontrolle',         @tt_kon, '2026-05-08 13:00:00', 30, 'planned');
INSERT INTO appointments (practice_id, patient_id, practitioner_id, treatment_type, treatment_type_id, scheduled_at, duration_min, status) VALUES (@pid, @p23, @pr2, 'Fuellungstherapie', @tt_ful, '2026-05-08 14:30:00', 45, 'planned');

-- ── Week 3: 11 mei – 15 mei ──────────────────────────────

-- Maandag 11 mei
INSERT INTO appointments (practice_id, patient_id, practitioner_id, treatment_type, treatment_type_id, scheduled_at, duration_min, status) VALUES (@pid, @p24, @pr1, 'PZR',              @tt_pzr, '2026-05-11 08:00:00', 60, 'planned');
INSERT INTO appointments (practice_id, patient_id, practitioner_id, treatment_type, treatment_type_id, scheduled_at, duration_min, status) VALUES (@pid, @p25, @pr2, 'Endodontie',        @tt_end, '2026-05-11 09:30:00', 90, 'planned');
INSERT INTO appointments (practice_id, patient_id, practitioner_id, treatment_type, treatment_type_id, scheduled_at, duration_min, status) VALUES (@pid, @p1,  @pr1, 'Fuellungstherapie', @tt_ful, '2026-05-11 13:00:00', 45, 'planned');
INSERT INTO appointments (practice_id, patient_id, practitioner_id, treatment_type, treatment_type_id, scheduled_at, duration_min, status) VALUES (@pid, @p2,  @pr2, 'Kontrolle',         @tt_kon, '2026-05-11 14:30:00', 30, 'planned');

-- Dinsdag 12 mei
INSERT INTO appointments (practice_id, patient_id, practitioner_id, treatment_type, treatment_type_id, scheduled_at, duration_min, status) VALUES (@pid, @p3,  @pr1, 'Kontrolle',         @tt_kon, '2026-05-12 08:00:00', 30, 'planned');
INSERT INTO appointments (practice_id, patient_id, practitioner_id, treatment_type, treatment_type_id, scheduled_at, duration_min, status) VALUES (@pid, @p4,  @pr2, 'PZR',              @tt_pzr, '2026-05-12 09:00:00', 60, 'planned');
INSERT INTO appointments (practice_id, patient_id, practitioner_id, treatment_type, treatment_type_id, scheduled_at, duration_min, status) VALUES (@pid, @p5,  @pr1, 'Endodontie',        @tt_end, '2026-05-12 13:00:00', 90, 'planned');
INSERT INTO appointments (practice_id, patient_id, practitioner_id, treatment_type, treatment_type_id, scheduled_at, duration_min, status) VALUES (@pid, @p6,  @pr2, 'Fuellungstherapie', @tt_ful, '2026-05-12 14:30:00', 45, 'planned');

-- Woensdag 13 mei
INSERT INTO appointments (practice_id, patient_id, practitioner_id, treatment_type, treatment_type_id, scheduled_at, duration_min, status) VALUES (@pid, @p7,  @pr1, 'Fuellungstherapie', @tt_ful, '2026-05-13 08:00:00', 45, 'planned');
INSERT INTO appointments (practice_id, patient_id, practitioner_id, treatment_type, treatment_type_id, scheduled_at, duration_min, status) VALUES (@pid, @p8,  @pr2, 'Kontrolle',         @tt_kon, '2026-05-13 08:00:00', 30, 'planned');
INSERT INTO appointments (practice_id, patient_id, practitioner_id, treatment_type, treatment_type_id, scheduled_at, duration_min, status) VALUES (@pid, @p9,  @pr1, 'PZR',              @tt_pzr, '2026-05-13 13:00:00', 60, 'planned');
INSERT INTO appointments (practice_id, patient_id, practitioner_id, treatment_type, treatment_type_id, scheduled_at, duration_min, status) VALUES (@pid, @p10, @pr2, 'Endodontie',        @tt_end, '2026-05-13 14:30:00', 90, 'planned');

-- Donderdag 14 mei (Christi Himmelfahrt — beperkt spreekuur)
INSERT INTO appointments (practice_id, patient_id, practitioner_id, treatment_type, treatment_type_id, scheduled_at, duration_min, status) VALUES (@pid, @p11, @pr1, 'Kontrolle',         @tt_kon, '2026-05-14 10:00:00', 30, 'planned');
INSERT INTO appointments (practice_id, patient_id, practitioner_id, treatment_type, treatment_type_id, scheduled_at, duration_min, status) VALUES (@pid, @p12, @pr2, 'PZR',              @tt_pzr, '2026-05-14 10:30:00', 60, 'planned');

-- Vrijdag 15 mei
INSERT INTO appointments (practice_id, patient_id, practitioner_id, treatment_type, treatment_type_id, scheduled_at, duration_min, status) VALUES (@pid, @p13, @pr1, 'PZR',              @tt_pzr, '2026-05-15 08:00:00', 60, 'planned');
INSERT INTO appointments (practice_id, patient_id, practitioner_id, treatment_type, treatment_type_id, scheduled_at, duration_min, status) VALUES (@pid, @p14, @pr2, 'Fuellungstherapie', @tt_ful, '2026-05-15 09:30:00', 45, 'planned');
INSERT INTO appointments (practice_id, patient_id, practitioner_id, treatment_type, treatment_type_id, scheduled_at, duration_min, status) VALUES (@pid, @p15, @pr1, 'Kontrolle',         @tt_kon, '2026-05-15 13:00:00', 30, 'planned');
INSERT INTO appointments (practice_id, patient_id, practitioner_id, treatment_type, treatment_type_id, scheduled_at, duration_min, status) VALUES (@pid, @p16, @pr2, 'Endodontie',        @tt_end, '2026-05-15 14:00:00', 90, 'planned');

-- ── Week 4: 18 mei – 22 mei ──────────────────────────────

-- Maandag 18 mei
INSERT INTO appointments (practice_id, patient_id, practitioner_id, treatment_type, treatment_type_id, scheduled_at, duration_min, status) VALUES (@pid, @p17, @pr1, 'Endodontie',        @tt_end, '2026-05-18 08:00:00', 90, 'planned');
INSERT INTO appointments (practice_id, patient_id, practitioner_id, treatment_type, treatment_type_id, scheduled_at, duration_min, status) VALUES (@pid, @p18, @pr2, 'PZR',              @tt_pzr, '2026-05-18 09:30:00', 60, 'planned');
INSERT INTO appointments (practice_id, patient_id, practitioner_id, treatment_type, treatment_type_id, scheduled_at, duration_min, status) VALUES (@pid, @p19, @pr1, 'Fuellungstherapie', @tt_ful, '2026-05-18 13:00:00', 45, 'planned');
INSERT INTO appointments (practice_id, patient_id, practitioner_id, treatment_type, treatment_type_id, scheduled_at, duration_min, status) VALUES (@pid, @p20, @pr2, 'Kontrolle',         @tt_kon, '2026-05-18 14:30:00', 30, 'planned');

-- Dinsdag 19 mei
INSERT INTO appointments (practice_id, patient_id, practitioner_id, treatment_type, treatment_type_id, scheduled_at, duration_min, status) VALUES (@pid, @p21, @pr1, 'PZR',              @tt_pzr, '2026-05-19 08:00:00', 60, 'planned');
INSERT INTO appointments (practice_id, patient_id, practitioner_id, treatment_type, treatment_type_id, scheduled_at, duration_min, status) VALUES (@pid, @p22, @pr2, 'Endodontie',        @tt_end, '2026-05-19 09:00:00', 90, 'planned');
INSERT INTO appointments (practice_id, patient_id, practitioner_id, treatment_type, treatment_type_id, scheduled_at, duration_min, status) VALUES (@pid, @p23, @pr1, 'Kontrolle',         @tt_kon, '2026-05-19 13:00:00', 30, 'planned');
INSERT INTO appointments (practice_id, patient_id, practitioner_id, treatment_type, treatment_type_id, scheduled_at, duration_min, status) VALUES (@pid, @p24, @pr2, 'Fuellungstherapie', @tt_ful, '2026-05-19 14:30:00', 45, 'planned');

-- Woensdag 20 mei
INSERT INTO appointments (practice_id, patient_id, practitioner_id, treatment_type, treatment_type_id, scheduled_at, duration_min, status) VALUES (@pid, @p25, @pr1, 'Fuellungstherapie', @tt_ful, '2026-05-20 08:00:00', 45, 'planned');
INSERT INTO appointments (practice_id, patient_id, practitioner_id, treatment_type, treatment_type_id, scheduled_at, duration_min, status) VALUES (@pid, @p1,  @pr2, 'PZR',              @tt_pzr, '2026-05-20 09:30:00', 60, 'planned');
INSERT INTO appointments (practice_id, patient_id, practitioner_id, treatment_type, treatment_type_id, scheduled_at, duration_min, status) VALUES (@pid, @p2,  @pr1, 'Endodontie',        @tt_end, '2026-05-20 13:00:00', 90, 'planned');
INSERT INTO appointments (practice_id, patient_id, practitioner_id, treatment_type, treatment_type_id, scheduled_at, duration_min, status) VALUES (@pid, @p3,  @pr2, 'Kontrolle',         @tt_kon, '2026-05-20 14:30:00', 30, 'planned');

-- Donderdag 21 mei
INSERT INTO appointments (practice_id, patient_id, practitioner_id, treatment_type, treatment_type_id, scheduled_at, duration_min, status) VALUES (@pid, @p4,  @pr1, 'Kontrolle',         @tt_kon, '2026-05-21 08:00:00', 30, 'planned');
INSERT INTO appointments (practice_id, patient_id, practitioner_id, treatment_type, treatment_type_id, scheduled_at, duration_min, status) VALUES (@pid, @p5,  @pr2, 'PZR',              @tt_pzr, '2026-05-21 09:00:00', 60, 'planned');
INSERT INTO appointments (practice_id, patient_id, practitioner_id, treatment_type, treatment_type_id, scheduled_at, duration_min, status) VALUES (@pid, @p6,  @pr1, 'Fuellungstherapie', @tt_ful, '2026-05-21 13:00:00', 45, 'planned');
INSERT INTO appointments (practice_id, patient_id, practitioner_id, treatment_type, treatment_type_id, scheduled_at, duration_min, status) VALUES (@pid, @p7,  @pr2, 'Endodontie',        @tt_end, '2026-05-21 14:30:00', 90, 'planned');

-- Vrijdag 22 mei
INSERT INTO appointments (practice_id, patient_id, practitioner_id, treatment_type, treatment_type_id, scheduled_at, duration_min, status) VALUES (@pid, @p8,  @pr1, 'PZR',              @tt_pzr, '2026-05-22 08:00:00', 60, 'planned');
INSERT INTO appointments (practice_id, patient_id, practitioner_id, treatment_type, treatment_type_id, scheduled_at, duration_min, status) VALUES (@pid, @p9,  @pr2, 'Kontrolle',         @tt_kon, '2026-05-22 09:30:00', 30, 'planned');
INSERT INTO appointments (practice_id, patient_id, practitioner_id, treatment_type, treatment_type_id, scheduled_at, duration_min, status) VALUES (@pid, @p10, @pr1, 'Fuellungstherapie', @tt_ful, '2026-05-22 13:00:00', 45, 'planned');
INSERT INTO appointments (practice_id, patient_id, practitioner_id, treatment_type, treatment_type_id, scheduled_at, duration_min, status) VALUES (@pid, @p11, @pr2, 'Endodontie',        @tt_end, '2026-05-22 14:30:00', 90, 'planned');

-- Klaar: 25 patiënten, 86 afspraken ingevoerd.
