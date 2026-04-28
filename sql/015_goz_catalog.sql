-- Easydent migratie 015: GOZ 2012 Anlage 1 catalogus
--
-- Bron: Gesetze im Internet — GOZ 1987/Anlage 1 (GOZ 2012-versie)
--   https://www.gesetze-im-internet.de/goz_1987/anlage_1.html
--
-- Prijsformule: fee = ROUND(punktzahl * 0.0562421 * factor, 2)
-- fee_1fach    = ROUND(punktzahl * 0.0562421, 4)  — opgeslagen als basis
-- Nooit 1,0/2,3/3,5-fach bedragen hardcoderen; altijd dynamisch berekenen.
--
-- bill_per_tooth:
--   0 = één keer per sessie/kaak/behandeling
--   1 = één declaratieregel per geselecteerde tand/implantaat/kanaal
--
-- LET OP — bestaande codes in treatment_items (003_treatment_seed.sql) gebruiken
-- soms andere GOZ-nummers dan de officiële GOZ 2012. Bekende afwijkingen:
--   seed 0060 = PSI-screening    → officieel: Abformung beider Kiefer
--   seed 0080 = Kofferdam        → officieel: Oberflächenanästhesie
--   seed 4050 = subgingivale PZR → officieel: Entfernung Beläge einwurzeliger Zahn
-- Deze catalogus bevat uitsluitend de officiële GOZ 2012-omschrijvingen.
-- ─────────────────────────────────────────────────────────────────────────────

CREATE TABLE IF NOT EXISTS goz_catalog (
    id             INT AUTO_INCREMENT PRIMARY KEY,
    goz_code       VARCHAR(10)   NOT NULL,
    description_de TEXT          NOT NULL,
    punktzahl      DECIMAL(8,2)  NULL     COMMENT 'NULL = variabel of analoog',
    fee_1fach      DECIMAL(10,4) NULL     COMMENT 'ROUND(punktzahl * 0.0562421, 4)',
    bill_per_tooth TINYINT(1)    NOT NULL DEFAULT 0,
    goz_source     VARCHAR(50)   NOT NULL DEFAULT 'GOZ 2012 / Anlage 1',
    active         TINYINT(1)    NOT NULL DEFAULT 1,
    UNIQUE KEY uq_goz_code (goz_code),
    KEY idx_active (active)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

INSERT INTO goz_catalog (goz_code, description_de, punktzahl, fee_1fach, bill_per_tooth) VALUES

-- ── Allgemeine zahnärztliche Leistungen ──────────────────────────────────────
('0010', 'Eingehende Untersuchung zur Feststellung von Zahn-, Mund- und Kiefererkrankungen einschließlich Erhebung des Parodontalbefundes sowie Aufzeichnung des Befundes', 100, ROUND(100 * 0.0562421, 4), 0),
('0030', 'Aufstellung eines schriftlichen Heil- und Kostenplans nach Befundaufnahme und gegebenenfalls Auswertung von Modellen', 200, ROUND(200 * 0.0562421, 4), 0),
('0040', 'Aufstellung eines schriftlichen Heil- und Kostenplans bei kieferorthopädischer Behandlung oder bei funktionsanalytischen und funktionstherapeutischen Maßnahmen', 250, ROUND(250 * 0.0562421, 4), 0),
('0050', 'Abformung oder Teilabformung eines Kiefers für ein Situationsmodell einschließlich Auswertung zur Diagnose oder Planung', 120, ROUND(120 * 0.0562421, 4), 0),
('0060', 'Abformung beider Kiefer für Situationsmodelle und einfache Bissfixierung einschließlich Auswertung zur Diagnose oder Planung', 260, ROUND(260 * 0.0562421, 4), 0),
('0065', 'Optisch-elektronische Abformung einschließlich vorbereitender Maßnahmen, einfache digitale Bissregistrierung und Archivierung, je Kieferhälfte oder Frontzahnbereich', 80, ROUND(80 * 0.0562421, 4), 0),
('0070', 'Vitalitätsprüfung eines Zahnes oder mehrerer Zähne einschließlich Vergleichstest, je Sitzung', 50, ROUND(50 * 0.0562421, 4), 0),

-- ── Anästhesie ───────────────────────────────────────────────────────────────
('0080', 'Intraorale Oberflächenanästhesie, je Kieferhälfte oder Frontzahnbereich', 30, ROUND(30 * 0.0562421, 4), 0),
('0090', 'Intraorale Infiltrationsanästhesie', 60, ROUND(60 * 0.0562421, 4), 1),
('0100', 'Intraorale Leitungsanästhesie', 70, ROUND(70 * 0.0562421, 4), 0),

-- ── Zuschläge ────────────────────────────────────────────────────────────────
('0110', 'Zuschlag für die Anwendung eines Operationsmikroskops', 400, ROUND(400 * 0.0562421, 4), 0),
('0120', 'Zuschlag für die Anwendung eines Lasers', NULL, NULL, 0),

-- ── Nichtstationäre chirurgische Zuschläge ───────────────────────────────────
('0500', 'Zuschlag bei nichtstationärer Durchführung von zahnärztlich-chirurgischen Leistungen (250–499 Punkte)', 400, ROUND(400 * 0.0562421, 4), 0),
('0510', 'Zuschlag bei nichtstationärer Durchführung von zahnärztlich-chirurgischen Leistungen (500–799 Punkte)', 750, ROUND(750 * 0.0562421, 4), 0),
('0520', 'Zuschlag bei nichtstationärer Durchführung von zahnärztlich-chirurgischen Leistungen (800–1199 Punkte)', 1300, ROUND(1300 * 0.0562421, 4), 0),
('0530', 'Zuschlag bei nichtstationärer Durchführung von zahnärztlich-chirurgischen Leistungen (ab 1200 Punkte)', 2200, ROUND(2200 * 0.0562421, 4), 0),

-- ── Prophylaxe ───────────────────────────────────────────────────────────────
('1000', 'Erstellung eines Mundhygienestatus und eingehende Unterweisung zur Vorbeugung gegen Karies und parodontale Erkrankungen, Dauer mindestens 25 Minuten', 200, ROUND(200 * 0.0562421, 4), 0),
('1010', 'Kontrolle des Übungserfolges einschließlich weiterer Unterweisung, Dauer mindestens 15 Minuten', 100, ROUND(100 * 0.0562421, 4), 0),
('1020', 'Lokale Fluoridierung zur Verbesserung der Zahnhartsubstanz, zur Kariesvorbeugung und -behandlung, mit Lack oder Gel, je Sitzung', 50, ROUND(50 * 0.0562421, 4), 0),
('1030', 'Lokale Anwendung von Medikamenten zur Kariesvorbeugung mit individuell gefertigter Schiene als Medikamententräger, je Kiefer', 90, ROUND(90 * 0.0562421, 4), 0),
('1040', 'Professionelle Zahnreinigung', 28, ROUND(28 * 0.0562421, 4), 0),

-- ── Kariesprophylaxe / Restauration — per tand ───────────────────────────────
('2000', 'Versiegelung von kariesfreien Zahnfissuren mit aushärtenden Kunststoffen, je Zahn', 90, ROUND(90 * 0.0562421, 4), 1),
('2010', 'Behandlung überempfindlicher Zahnflächen, je Kiefer', 50, ROUND(50 * 0.0562421, 4), 0),
('2020', 'Temporärer speicheldichter Verschluss einer Kavität', 98, ROUND(98 * 0.0562421, 4), 1),
('2030', 'Besondere Maßnahmen beim Präparieren oder Füllen von Kavitäten, je Kieferhälfte oder Frontzahnbereich', 65, ROUND(65 * 0.0562421, 4), 0),
('2040', 'Anlegen von Spanngummi, je Kieferhälfte oder Frontzahnbereich', 65, ROUND(65 * 0.0562421, 4), 0),

-- ── Plastische Füllungen — per tand ─────────────────────────────────────────
('2050', 'Präparieren einer Kavität und Restauration mit plastischem Füllungsmaterial, einflächig', 213, ROUND(213 * 0.0562421, 4), 1),
('2060', 'Präparieren einer Kavität und Restauration mit Kompositmaterialien, in Adhäsivtechnik, einflächig', 527, ROUND(527 * 0.0562421, 4), 1),
('2070', 'Präparieren einer Kavität und Restauration mit plastischem Füllungsmaterial, zweiflächig', 242, ROUND(242 * 0.0562421, 4), 1),
('2080', 'Präparieren einer Kavität und Restauration mit Kompositmaterialien, in Adhäsivtechnik, zweiflächig', 556, ROUND(556 * 0.0562421, 4), 1),
('2090', 'Präparieren einer Kavität und Restauration mit plastischem Füllungsmaterial, dreiflächig', 297, ROUND(297 * 0.0562421, 4), 1),
('2100', 'Präparieren einer Kavität und Restauration mit Kompositmaterialien, in Adhäsivtechnik, dreiflächig', 642, ROUND(642 * 0.0562421, 4), 1),
('2110', 'Präparieren einer Kavität und Restauration mit plastischem Füllungsmaterial, mehr als dreiflächig', 319, ROUND(319 * 0.0562421, 4), 1),
('2120', 'Präparieren einer Kavität und Restauration mit Kompositmaterialien, in Adhäsivtechnik, mehr als dreiflächig', 770, ROUND(770 * 0.0562421, 4), 1),
('2130', 'Kontrolle, Finieren/Polieren einer Restauration in separater Sitzung', 104, ROUND(104 * 0.0562421, 4), 1),

-- ── Einlagefüllungen — per tand ──────────────────────────────────────────────
('2150', 'Einlagefüllung, einflächig', 1141, ROUND(1141 * 0.0562421, 4), 1),
('2160', 'Einlagefüllung, zweiflächig', 1356, ROUND(1356 * 0.0562421, 4), 1),
('2170', 'Einlagefüllung, mehr als zweiflächig', 1709, ROUND(1709 * 0.0562421, 4), 1),

-- ── Kronenaufbau / Kronenversorgung — per tand ───────────────────────────────
('2180', 'Vorbereitung eines zerstörten Zahnes mit plastischem Aufbaumaterial zur Aufnahme einer Krone', 150, ROUND(150 * 0.0562421, 4), 1),
('2190', 'Vorbereitung eines zerstörten Zahnes durch gegossenen Aufbau mit Stiftverankerung zur Aufnahme einer Krone', 450, ROUND(450 * 0.0562421, 4), 1),
('2195', 'Vorbereitung eines zerstörten Zahnes durch einen Schraubenaufbau oder Glasfaserstift zur Aufnahme einer Krone', 300, ROUND(300 * 0.0562421, 4), 1),
('2197', 'Adhäsive Befestigung', 130, ROUND(130 * 0.0562421, 4), 1),
('2200', 'Versorgung eines Zahnes oder Implantats durch eine Vollkrone (Tangentialpräparation)', 1322, ROUND(1322 * 0.0562421, 4), 1),
('2210', 'Versorgung eines Zahnes durch eine Vollkrone (Hohlkehl- oder Stufenpräparation)', 1678, ROUND(1678 * 0.0562421, 4), 1),
('2220', 'Versorgung eines Zahnes durch eine Teilkrone mit Retentionsrillen oder Pinledges oder Veneer', 2067, ROUND(2067 * 0.0562421, 4), 1),
('2230', 'Teilleistung: Enden mit Präparation oder Abdrucknahme beim Implantat', NULL, NULL, 1),
('2240', 'Teilleistung: Weitere Maßnahmen beim Implantat', NULL, NULL, 1),
('2250', 'Eingliederung einer konfektionierten Krone in der pädiatrischen Zahnheilkunde', 210, ROUND(210 * 0.0562421, 4), 1),
('2260', 'Provisorium im direkten Verfahren ohne Abformung, je Zahn oder Implantat', 100, ROUND(100 * 0.0562421, 4), 1),
('2270', 'Provisorium im direkten Verfahren mit Abformung, je Zahn oder Implantat', 270, ROUND(270 * 0.0562421, 4), 1),
('2290', 'Entfernung einer Einlagefüllung, einer Krone, eines Brückenankers', 180, ROUND(180 * 0.0562421, 4), 1),
('2300', 'Entfernung eines Wurzelstiftes', 270, ROUND(270 * 0.0562421, 4), 1),
('2310', 'Wiedereingliederung einer Einlagefüllung, Teilkrone, Veneer oder Krone', 145, ROUND(145 * 0.0562421, 4), 1),
('2320', 'Wiederherstellung einer Krone, Teilkrone, Veneer, Brückenankers', 350, ROUND(350 * 0.0562421, 4), 1),

-- ── Endodontie — per tand / per kanaal ───────────────────────────────────────
('2330', 'Maßnahmen zur Erhaltung der vitalen Pulpa bei Caries profunda', 110, ROUND(110 * 0.0562421, 4), 1),
('2340', 'Maßnahmen zur Erhaltung der freiliegenden vitalen Pulpa', 200, ROUND(200 * 0.0562421, 4), 1),
('2350', 'Amputation und Versorgung der vitalen Pulpa einschließlich Exkavieren', 290, ROUND(290 * 0.0562421, 4), 1),
('2360', 'Exstirpation der vitalen Pulpa einschließlich Exkavieren, je Kanal', 110, ROUND(110 * 0.0562421, 4), 1),
('2380', 'Amputation und endgültige Versorgung der avitalen Milchzahnpulpa', 160, ROUND(160 * 0.0562421, 4), 1),
('2390', 'Trepanation eines Zahnes, als selbstständige Leistung', 65, ROUND(65 * 0.0562421, 4), 1),
('2400', 'Elektrometrische Längenbestimmung eines Wurzelkanals', 70, ROUND(70 * 0.0562421, 4), 1),
('2410', 'Aufbereitung eines Wurzelkanals auch retrograd, je Kanal', 392, ROUND(392 * 0.0562421, 4), 1),
('2420', 'Zusätzliche Anwendung elektrophysikalisch-chemischer Methoden, je Kanal', 70, ROUND(70 * 0.0562421, 4), 1),
('2430', 'Medikamentöse Einlage in Verbindung mit Maßnahmen nach den Nummern 2360, 2380 und 2410', 204, ROUND(204 * 0.0562421, 4), 1),
('2440', 'Füllung eines Wurzelkanals', 258, ROUND(258 * 0.0562421, 4), 1),

-- ── Chirurgie — extracties per tand ─────────────────────────────────────────
('3000', 'Entfernung eines einwurzeligen Zahnes oder eines enossalen Implantats', 70, ROUND(70 * 0.0562421, 4), 1),
('3010', 'Entfernung eines mehrwurzeligen Zahnes', 110, ROUND(110 * 0.0562421, 4), 1),
('3020', 'Entfernung eines tief frakturierten oder tief zerstörten Zahnes', 270, ROUND(270 * 0.0562421, 4), 1),
('3030', 'Entfernung eines Zahnes oder Implantats durch Osteotomie', 350, ROUND(350 * 0.0562421, 4), 1),
('3040', 'Entfernung eines retinierten, impaktierten oder verlagerten Zahnes durch Osteotomie', 540, ROUND(540 * 0.0562421, 4), 1),
('3045', 'Entfernen eines extrem verlagerten und/oder extrem retinierten Zahnes durch umfangreiche Osteotomie', 767, ROUND(767 * 0.0562421, 4), 1),

-- ── Chirurgie — per behandeling ──────────────────────────────────────────────
('3050', 'Stillung einer übermäßigen Blutung im Mund- und/oder Kieferbereich', 110, ROUND(110 * 0.0562421, 4), 0),
('3060', 'Stillung einer Blutung durch Abbinden oder Umstechen des Gefäßes', 140, ROUND(140 * 0.0562421, 4), 0),
('3070', 'Exzision von Schleimhaut oder Granulationsgewebe, als selbstständige Leistung', 45, ROUND(45 * 0.0562421, 4), 0),
('3080', 'Exzision einer Schleimhautwucherung größeren Umfangs', 150, ROUND(150 * 0.0562421, 4), 0),
('3090', 'Plastischer Verschluss einer eröffneten Kieferhöhle', 370, ROUND(370 * 0.0562421, 4), 0),
('3100', 'Plastische Deckung im Rahmen einer Wundversorgung', 270, ROUND(270 * 0.0562421, 4), 0),

-- ── Chirurgie — per tand (wortelspitsbewerkingen / reimplantatie) ────────────
('3110', 'Resektion einer Wurzelspitze an einem Frontzahn', 460, ROUND(460 * 0.0562421, 4), 1),
('3120', 'Resektion einer Wurzelspitze an einem Seitenzahn', 580, ROUND(580 * 0.0562421, 4), 1),
('3130', 'Hemisektion und Teilextraktion eines mehrwurzeligen Zahnes', 280, ROUND(280 * 0.0562421, 4), 1),
('3140', 'Reimplantation eines Zahnes einschließlich einfacher Fixation', 550, ROUND(550 * 0.0562421, 4), 1),
('3160', 'Transplantation eines Zahnes einschließlich operativer Schaffung des Knochenbettes', 650, ROUND(650 * 0.0562421, 4), 1),

-- ── Chirurgie — overige ingrepen per behandeling ─────────────────────────────
('3190', 'Operation einer Zyste durch Zystektomie in Verbindung mit Osteotomie oder Wurzelspitzenresektion', 270, ROUND(270 * 0.0562421, 4), 0),
('3200', 'Operation einer Zyste durch Zystektomie, als selbstständige Leistung', 500, ROUND(500 * 0.0562421, 4), 0),
('3210', 'Beseitigung störender Schleimhautbänder, je Kieferhälfte oder Frontzahnbereich', 140, ROUND(140 * 0.0562421, 4), 0),
('3230', 'Knochenresektion am Alveolarfortsatz zur Formung des Prothesenlagers', 440, ROUND(440 * 0.0562421, 4), 0),
('3240', 'Vestibulumplastik oder Mundbodenplastik kleineren Umfangs', 550, ROUND(550 * 0.0562421, 4), 0),
('3250', 'Tuberplastik, einseitig', 270, ROUND(270 * 0.0562421, 4), 0),
('3260', 'Freilegen eines retinierten oder verlagerten Zahnes zur orthopädischen Einstellung', 550, ROUND(550 * 0.0562421, 4), 0),
('3270', 'Germektomie', 590, ROUND(590 * 0.0562421, 4), 0),
('3280', 'Lösen, Verlegen und Fixieren des Lippenbändchens und Durchtrennen des Septums bei echtem Diastema', 270, ROUND(270 * 0.0562421, 4), 0),
('3290', 'Kontrolle nach chirurgischem Eingriff, als selbstständige Leistung', 55, ROUND(55 * 0.0562421, 4), 0),
('3300', 'Nachbehandlung nach chirurgischem Eingriff', 65, ROUND(65 * 0.0562421, 4), 0),
('3310', 'Chirurgische Wundrevision', 100, ROUND(100 * 0.0562421, 4), 0),

-- ── Parodontologie — per behandeling ────────────────────────────────────────
('4000', 'Erstellen und Dokumentieren eines Parodontalstatus', 160, ROUND(160 * 0.0562421, 4), 0),
('4005', 'Erhebung mindestens eines Gingivalindex und/oder eines Parodontalindex', 80, ROUND(80 * 0.0562421, 4), 0),
('4020', 'Lokalbehandlung von Mundschleimhauterkrankungen, je Sitzung', 45, ROUND(45 * 0.0562421, 4), 0),
('4030', 'Beseitigung von scharfen Zahnkanten, störenden Prothesenrändern', 35, ROUND(35 * 0.0562421, 4), 0),
('4040', 'Beseitigung grober Vorkontakte der Okklusion und Artikulation', 45, ROUND(45 * 0.0562421, 4), 0),

-- ── Parodontologie — per tand ────────────────────────────────────────────────
('4025', 'Subgingivale medikamentöse antibakterielle Lokalapplikation, je Zahn', 15, ROUND(15 * 0.0562421, 4), 1),
('4050', 'Entfernung harter und weicher Zahnbeläge an einem einwurzeligen Zahn oder Implantat', 10, ROUND(10 * 0.0562421, 4), 1),
('4055', 'Entfernung harter und weicher Zahnbeläge an einem mehrwurzeligen Zahn', 13, ROUND(13 * 0.0562421, 4), 1),
('4060', 'Kontrolle nach Entfernung harter und weicher Zahnbeläge', 7, ROUND(7 * 0.0562421, 4), 1),
('4070', 'Parodontalchirurgische Therapie an einem einwurzeligen Zahn, geschlossenes Vorgehen', 100, ROUND(100 * 0.0562421, 4), 1),
('4075', 'Parodontalchirurgische Therapie an einem mehrwurzeligen Zahn, geschlossenes Vorgehen', 130, ROUND(130 * 0.0562421, 4), 1),
('4080', 'Gingivektomie, Gingivoplastik, je Parodontium', 45, ROUND(45 * 0.0562421, 4), 1),
('4090', 'Lappenoperation, offene Kürettage an einem Frontzahn, je Parodontium', 180, ROUND(180 * 0.0562421, 4), 1),
('4100', 'Lappenoperation, offene Kürettage an einem Seitenzahn, je Parodontium', 275, ROUND(275 * 0.0562421, 4), 1),
('4110', 'Auffüllen von parodontalen Knochendefekten mit Aufbaumaterial', 180, ROUND(180 * 0.0562421, 4), 1),
('4133', 'Gewinnung und Transplantation von Bindegewebe, je Zahnzwischenraum', 880, ROUND(880 * 0.0562421, 4), 1),
('4138', 'Verwendung einer Membran zur Behandlung eines Knochendefektes', 220, ROUND(220 * 0.0562421, 4), 1),

-- ── Parodontologie — per behandeling (overig) ────────────────────────────────
('4120', 'Verlegen eines gestielten Schleimhautlappens, je Kieferhälfte oder Frontzahnbereich', 275, ROUND(275 * 0.0562421, 4), 0),
('4130', 'Gewinnung und Transplantation von Schleimhaut, je Transplantat', 180, ROUND(180 * 0.0562421, 4), 0),
('4136', 'Osteoplastik auch Kronenverlängerung, Tunnelierung', 200, ROUND(200 * 0.0562421, 4), 0),
('4150', 'Kontrolle/Nachbehandlung nach parodontalchirurgischen Maßnahmen', 7, ROUND(7 * 0.0562421, 4), 0),

-- ── Brugwerk — per pijlertand / element ──────────────────────────────────────
('5000', 'Brücke/Prothese: je Pfeilerzahn als Anker mit Vollkrone (Tangentialpräparation)', 1016, ROUND(1016 * 0.0562421, 4), 1),
('5010', 'Brücke/Prothese: je Pfeilerzahn als Anker mit Vollkrone (Hohlkehl- oder Stufenpräparation) oder Einlagefüllung', 1483, ROUND(1483 * 0.0562421, 4), 1),
('5020', 'Brücke/Prothese: je Pfeilerzahn als Anker mit Teilkrone mit Retentionsrillen', 1997, ROUND(1997 * 0.0562421, 4), 1),
('5030', 'Brücke/Prothese: je Pfeilerzahn/Implantat als Anker mit Wurzelkappe mit Stift', 1483, ROUND(1483 * 0.0562421, 4), 1),
('5040', 'Brücke/Prothese: je Pfeilerzahn/Implantat als Anker mit Teleskopkrone/Konuskrone', 2605, ROUND(2605 * 0.0562421, 4), 1),
('5050', 'Teilleistung: Enden mit Präparation oder Abdrucknahme', NULL, NULL, 1),
('5060', 'Teilleistung: Weitere Maßnahmen im Brückenbereich', NULL, NULL, 1),
('5070', 'Brücke/Prothese: Verbindung durch Brückenglieder, Spanne mit Stegen', 400, ROUND(400 * 0.0562421, 4), 1),
('5080', 'Brücke/Prothese: zusammengesetzte Brücke, je Verbindungselement', 230, ROUND(230 * 0.0562421, 4), 1),
('5100', 'Erneuern des Sekundärteils einer Teleskopkrone einschließlich Abformung', 450, ROUND(450 * 0.0562421, 4), 1),
('5120', 'Provisorische Brücke im direkten Verfahren mit Abformung, je Zahn/Implantat', 240, ROUND(240 * 0.0562421, 4), 1),
('5160', 'Adhäsivtechnik befestigte Brücke, weitere zu überbrückende Spanne', 360, ROUND(360 * 0.0562421, 4), 1),

-- ── Brugwerk — per behandeling / kaak ────────────────────────────────────────
('5090', 'Wiederherstellung der Funktion eines Verbindungselements', 110, ROUND(110 * 0.0562421, 4), 0),
('5110', 'Wiedereingliederung einer endgültigen Brücke', 360, ROUND(360 * 0.0562421, 4), 0),
('5140', 'Provisorische Brücke im direkten Verfahren mit Abformung, je Brückenspanne/Freiendsattel', 80, ROUND(80 * 0.0562421, 4), 0),
('5150', 'Adhäsivtechnik befestigte Brücke, erste zu überbrückende Spanne', 730, ROUND(730 * 0.0562421, 4), 0),
('5170', 'Anatomische Abformung mit individuellem Löffel', 250, ROUND(250 * 0.0562421, 4), 0),
('5180', 'Funktionelle Abformung des Oberkiefers mit individuellem Löffel', 450, ROUND(450 * 0.0562421, 4), 0),
('5190', 'Funktionelle Abformung des Unterkiefers mit individuellem Löffel', 540, ROUND(540 * 0.0562421, 4), 0),

-- ── Prothese — per behandeling ────────────────────────────────────────────────
('5200', 'Teilprothese mit einfachen, gebogenen Haftelementen', 700, ROUND(700 * 0.0562421, 4), 0),
('5210', 'Modellgussprothese mit gegossenen Halte- und Stützelementen', 1400, ROUND(1400 * 0.0562421, 4), 0),
('5220', 'Totale Prothese oder Deckprothese, im Oberkiefer', 1850, ROUND(1850 * 0.0562421, 4), 0),
('5230', 'Totale Prothese oder Deckprothese, im Unterkiefer', 2200, ROUND(2200 * 0.0562421, 4), 0),
('5240', 'Teilleistungen nach Nummern 5200 und 5230', NULL, NULL, 0),
('5250', 'Wiederherstellung der Funktion oder Erweiterung einer abnehmbaren Prothese ohne Abformung', 140, ROUND(140 * 0.0562421, 4), 0),
('5260', 'Wiederherstellung der Funktion oder Erweiterung einer abnehmbaren Prothese mit Abformung', 270, ROUND(270 * 0.0562421, 4), 0),
('5270', 'Teilunterfütterung einer Prothese', 180, ROUND(180 * 0.0562421, 4), 0),
('5280', 'Vollständige Unterfütterung einer Prothese', 270, ROUND(270 * 0.0562421, 4), 0),
('5290', 'Vollständige Unterfütterung einer Prothese mit funktioneller Randgestaltung, Oberkiefer', 450, ROUND(450 * 0.0562421, 4), 0),
('5300', 'Vollständige Unterfütterung einer Prothese mit funktioneller Randgestaltung, Unterkiefer', 540, ROUND(540 * 0.0562421, 4), 0),
('5310', 'Vollständige Unterfütterung einer Defektprothese mit funktioneller Randgestaltung', 730, ROUND(730 * 0.0562421, 4), 0),
('5320', 'Eingliederung eines Obturators', 2200, ROUND(2200 * 0.0562421, 4), 0),
('5330', 'Eingliederung einer Resektionsprothese', 2800, ROUND(2800 * 0.0562421, 4), 0),
('5340', 'Eingliederung einer Prothese oder Epithese', 7300, ROUND(7300 * 0.0562421, 4), 0),

-- ── Kieferorthopädie ─────────────────────────────────────────────────────────
('6000', 'Profil- oder Enfacefotografie mit kieferorthopädischer Auswertung', 80, ROUND(80 * 0.0562421, 4), 0),
('6010', 'Anwendung von Methoden zur Analyse von Kiefermodellen', 180, ROUND(180 * 0.0562421, 4), 0),
('6020', 'Anwendung von Methoden zur Untersuchung des Gesichtsschädels', 360, ROUND(360 * 0.0562421, 4), 0),
('6030', 'Maßnahmen zur Umformung eines Kiefers, geringer Umfang', 1350, ROUND(1350 * 0.0562421, 4), 0),
('6040', 'Maßnahmen zur Umformung eines Kiefers, mittlerer Umfang', 2100, ROUND(2100 * 0.0562421, 4), 0),
('6050', 'Maßnahmen zur Umformung eines Kiefers, hoher Umfang', 3600, ROUND(3600 * 0.0562421, 4), 0),
('6060', 'Maßnahmen zur Einstellung in Regelbiss während Wachstumsphase, geringer Umfang', 1800, ROUND(1800 * 0.0562421, 4), 0),
('6070', 'Maßnahmen zur Einstellung in Regelbiss während Wachstumsphase, mittlerer Umfang', 2600, ROUND(2600 * 0.0562421, 4), 0),
('6080', 'Maßnahmen zur Einstellung in Regelbiss während Wachstumsphase, hoher Umfang', 3600, ROUND(3600 * 0.0562421, 4), 0),
('6090', 'Maßnahmen zur Einstellung der Okklusion durch alveolären Ausgleich', 700, ROUND(700 * 0.0562421, 4), 0),

-- ── Kieferorthopädie — per element (bracket/band) ────────────────────────────
('6100', 'Eingliederung eines Klebebrackets', 165, ROUND(165 * 0.0562421, 4), 1),
('6110', 'Entfernung eines Klebebrackets einschließlich Polieren', 70, ROUND(70 * 0.0562421, 4), 1),
('6120', 'Eingliederung eines Bandes', 230, ROUND(230 * 0.0562421, 4), 1),
('6130', 'Entfernung eines Bandes einschließlich Polieren', 20, ROUND(20 * 0.0562421, 4), 1),

-- ── Kieferorthopädie — per behandeling (overig) ──────────────────────────────
('6140', 'Eingliederung eines Teilbogens', 210, ROUND(210 * 0.0562421, 4), 0),
('6150', 'Eingliederung eines ungeteilten Bogens, je Kiefer', 500, ROUND(500 * 0.0562421, 4), 0),
('6160', 'Eingliederung einer intra-/extraoralen Verankerung', 370, ROUND(370 * 0.0562421, 4), 0),
('6170', 'Eingliederung einer Kopf-Kinn-Kappe', 500, ROUND(500 * 0.0562421, 4), 0),
('6180', 'Maßnahmen zur Wiederherstellung von herausnehmbaren Behandlungsgeräten', 270, ROUND(270 * 0.0562421, 4), 0),
('6190', 'Beratendes und belehrendes Gespräch mit Anweisungen', 140, ROUND(140 * 0.0562421, 4), 0),
('6200', 'Eingliedern von Hilfsmitteln zur Beseitigung von Funktionsstörungen', 450, ROUND(450 * 0.0562421, 4), 0),
('6210', 'Kontrolle des Behandlungsverlaufs oder Weiterführung der Retention', 90, ROUND(90 * 0.0562421, 4), 0),
('6220', 'Vorbereitende Maßnahmen zur Herstellung von kieferorthopädischen Behandlungsmitteln', 180, ROUND(180 * 0.0562421, 4), 0),
('6230', 'Eingliederung von kieferorthopädischen Behandlungsmitteln, je Kiefer', 180, ROUND(180 * 0.0562421, 4), 0),
('6240', 'Maßnahmen zur Verhütung von Folgen vorzeitigen Zahnverlustes', 270, ROUND(270 * 0.0562421, 4), 0),
('6250', 'Beseitigung des Diastemas, als selbstständige Leistung', 450, ROUND(450 * 0.0562421, 4), 0),
('6260', 'Maßnahmen zur Einordnung eines verlagerten Zahnes', 1100, ROUND(1100 * 0.0562421, 4), 0),

-- ── Aufbissbehelfe / Schienen ─────────────────────────────────────────────────
('7000', 'Eingliederung eines Aufbissbehelfs ohne adjustierte Oberfläche', 270, ROUND(270 * 0.0562421, 4), 0),
('7010', 'Eingliederung eines Aufbissbehelfs mit adjustierter Oberfläche', 800, ROUND(800 * 0.0562421, 4), 0),
('7020', 'Umarbeitung einer vorhandenen Prothese zum Aufbissbehelf', 450, ROUND(450 * 0.0562421, 4), 0),
('7030', 'Wiederherstellung der Funktion eines Aufbissbehelfs', 370, ROUND(370 * 0.0562421, 4), 0),
('7040', 'Kontrolle eines Aufbissbehelfs', 65, ROUND(65 * 0.0562421, 4), 0),
('7050', 'Kontrolle eines Aufbissbehelfs mit adjustierter Oberfläche: subtraktive Maßnahmen', 180, ROUND(180 * 0.0562421, 4), 0),
('7060', 'Kontrolle eines Aufbissbehelfs mit adjustierter Oberfläche: additive Maßnahmen', 410, ROUND(410 * 0.0562421, 4), 0),
('7070', 'Semipermanente Schiene unter Anwendung der Ätztechnik', 90, ROUND(90 * 0.0562421, 4), 0),
('7080', 'Festsitzender laborgefertigter Prothese im indirekten Verfahren, je Zahn/Implantat', 600, ROUND(600 * 0.0562421, 4), 0),
('7090', 'Laborgefertigtes Provisorium im indirekten Verfahren, je Brückenglied', 270, ROUND(270 * 0.0562421, 4), 0),
('7100', 'Wiederherstellung der Funktion eines Langzeitprovisoriums', 200, ROUND(200 * 0.0562421, 4), 0),

-- ── Funktionsdiagnostik ───────────────────────────────────────────────────────
('8000', 'Klinische Funktionsanalyse einschließlich Dokumentation', 500, ROUND(500 * 0.0562421, 4), 0),
('8010', 'Registrieren der gelenkbezüglichen Zentrallage des Unterkiefers', 180, ROUND(180 * 0.0562421, 4), 0),
('8020', 'Arbiträre Scharnierachsenbestimmung', 300, ROUND(300 * 0.0562421, 4), 0),
('8030', 'Kinematische Scharnierachsenbestimmung', 550, ROUND(550 * 0.0562421, 4), 0),
('8035', 'Kinematische Scharnierachsenbestimmung mittels elektronischer Aufzeichnung', 550, ROUND(550 * 0.0562421, 4), 0),
('8050', 'Registrieren von Unterkieferbewegungen zur Einstellung halbindividueller Artikulatoren', 500, ROUND(500 * 0.0562421, 4), 0),
('8060', 'Registrieren von Unterkieferbewegungen zur Einstellung voll adjustierbarer Artikulatoren', 750, ROUND(750 * 0.0562421, 4), 0),
('8065', 'Registrieren von Unterkieferbewegungen mittels elektronischer Aufzeichnung', 850, ROUND(850 * 0.0562421, 4), 0),
('8080', 'Diagnostische Maßnahmen an Modellen im Artikulator', 250, ROUND(250 * 0.0562421, 4), 0),
('8090', 'Diagnostischer Aufbau von Funktionsflächen', 250, ROUND(250 * 0.0562421, 4), 0),
('8100', 'Systematische subtraktive Maßnahmen am natürlichen Gebiss', 20, ROUND(20 * 0.0562421, 4), 0),

-- ── Implantologie — per implantaat ───────────────────────────────────────────
('9000', 'Implantatbezogene Analyse und Vermessung', 884, ROUND(884 * 0.0562421, 4), 0),
('9003', 'Verwenden einer Orientierungsschablone/Positionierungsschablone', 100, ROUND(100 * 0.0562421, 4), 0),
('9005', 'Verwenden einer Navigationsschablone/chirurgischen Führungsschablone', 300, ROUND(300 * 0.0562421, 4), 0),
('9010', 'Implantatinsertion, je Implantat', 1545, ROUND(1545 * 0.0562421, 4), 1),
('9020', 'Insertion eines Implantats zum temporären Verbleib', 515, ROUND(515 * 0.0562421, 4), 1),
('9040', 'Freilegen eines Implantats und Einfügen von Aufbauelementen', 626, ROUND(626 * 0.0562421, 4), 1),
('9050', 'Entfernen und Wiedereinsetzen von Aufbauelementen', 313, ROUND(313 * 0.0562421, 4), 1),
('9060', 'Auswechseln von Aufbauelementen im Reparaturfall', 313, ROUND(313 * 0.0562421, 4), 1),

-- ── Implantologie — per behandeling ─────────────────────────────────────────
('9090', 'Knochengewinnung, Knochenaufbereitung und -implantation', 400, ROUND(400 * 0.0562421, 4), 0),
('9100', 'Aufbau des Alveolarfortsatzes durch Augmentation', 2694, ROUND(2694 * 0.0562421, 4), 0),
('9110', 'Geschlossene Sinusbodenelevation', 1500, ROUND(1500 * 0.0562421, 4), 0),
('9120', 'Sinusbodenelevation durch externe Knochenfensterung', 3000, ROUND(3000 * 0.0562421, 4), 0),
('9130', 'Spaltung und Spreizung von Knochensegmenten oder vertikale Distraktion', 1540, ROUND(1540 * 0.0562421, 4), 0),
('9140', 'Intraorale Entnahme von Knochen außerhalb des Aufbaugebietes', 650, ROUND(650 * 0.0562421, 4), 0),
('9150', 'Fixation oder Stabilisierung des Augmentates', 675, ROUND(675 * 0.0562421, 4), 0),
('9160', 'Entfernung unter der Schleimhaut liegender Materialien', 330, ROUND(330 * 0.0562421, 4), 0),
('9170', 'Entfernung im Knochen liegender Materialien', 500, ROUND(500 * 0.0562421, 4), 0);

-- ─────────────────────────────────────────────────────────────────────────────
-- Verificatie: toon het aantal geïmporteerde codes en een steekproef
-- SELECT COUNT(*) AS totaal_codes FROM goz_catalog;
-- SELECT goz_code, punktzahl, fee_1fach, ROUND(fee_1fach * 2.3, 2) AS fee_2_3fach
--   FROM goz_catalog WHERE goz_code IN ('0090','2060','2440','9010') ORDER BY goz_code;
-- ─────────────────────────────────────────────────────────────────────────────
