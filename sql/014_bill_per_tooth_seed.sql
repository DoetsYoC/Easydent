-- Easydent migratie 014: bill_per_tooth instellen op basis van GOZ 2012 (Anlage 1)
--
-- Logica:
--   bill_per_tooth = 0  → éénmalig per sessie (globale sectie in behandelscherm)
--   bill_per_tooth = 1  → één declaratieregel PER geselecteerde tand
--
-- Voer uit NADAT sql/013_bill_per_tooth.sql is uitgevoerd.
-- Bestaande handmatige instellingen worden overschreven.
-- ─────────────────────────────────────────────────────────────────────────────

-- Stap 1: alles op 0 (per behandeling) als veilige basis
UPDATE treatment_items SET bill_per_tooth = 0;

-- Stap 2: per-tand codes op 1 zetten
UPDATE treatment_items SET bill_per_tooth = 1 WHERE goz_code IN (

    -- ── Behandelvoorbereiding per tand ─────────────────────────────────────
    '0080',   -- Kofferdam (per geïsoleerde tand)

    -- ── Profylaxe — per tand ───────────────────────────────────────────────
    '2000',   -- Fissuurlakken (Versiegelung kariesfreier Fissur) — per tand
    '4050',   -- Subgingivale reiniging (je Zahn, uitdrukkelijk in GOZ)

    -- ── Restauraties — composiet (per tand) ────────────────────────────────
    '2050',   -- Ondervulling / kaviteitsisolatie (per restauratie)
    '2060',   -- Composietvulling 1 vlak
    '2070',   -- Composietvulling 2 vlakken
    '2080',   -- Composietvulling 3 vlakken
    '2090',   -- Composietvulling 4+ vlakken
    '2100',   -- Adhesieftechniek: etsen, primer, bond (per restauratie)

    -- ── Inlays (per tand) ──────────────────────────────────────────────────
    '2120',   -- Inlay 1 vlak
    '2130',   -- Inlay 2 vlakken
    '2140',   -- Inlay 3 vlakken

    -- ── Kronen & prothetiek (per tand/eenheid) ─────────────────────────────
    '2180',   -- Provisorische kroon (per tand)
    '2200',   -- Teleskoop-/ankerconstructie (per tand)
    '2210',   -- Gegoten metalen kroon (per tand)
    '2240',   -- Gegoten metalen inlay (per tand)
    '2360',   -- Keramische kroon (per tand)
    '2380',   -- Metaalkeramische kroon (per tand)
    '2390',   -- Vollkeramische kroon (per tand)

    -- ── Brugwerk / prothetica (per brugdeel/tand) ──────────────────────────
    '3110',   -- Bruganker / kroonconstructie (per element)
    '3120',   -- Metaalkeramisch brugdeel (per element)
    '3130',   -- Vollkeramisch brugdeel (per element)
    '3140',   -- Uitgebreid brugwerk (per element)
    '3150',   -- Complex brugwerk (per element)

    -- ── Endodontie (per tand) ──────────────────────────────────────────────
    '2410',   -- Trepanatie pulpakamer
    '2420',   -- Verwijdering pulpaweefsel
    '2430',   -- Overig endodontisch (per tand)
    '2440',   -- Kanaalbereiding (je Kanal → per tand in dit systeem)
    '2450',   -- Verwijdering gefractureerd instrument
    '2460',   -- Medicamenteuze tussenbehandeling (je Kanal)
    '2470',   -- Wortelkanaalvulling (je Kanal)
    '2480'    -- Retrograde wortelkanaalvulling

);

-- ─────────────────────────────────────────────────────────────────────────────
-- Referentie: codes die PER BEHANDELING blijven (bill_per_tooth = 0):
--   0010  Uitgebreid onderzoek en advies
--   0030  Vitaliteitstest pulpa
--   0040  Aanvullend onderzoek
--   0050  Volledige tandstatus
--   0060  Parodontale screeningindex (PSI)
--   0070  Pijnanalyse
--   0100  Opstellen heil- en kostenplan
--   1000  Documentatie mondhygiënestatus
--   1010  Mondhygiëne-instructie
--   1020  Controle mondhygiëne
--   1040  Professionele reiniging alle tanden
--   1200  Lokale fluoridering
-- ─────────────────────────────────────────────────────────────────────────────
