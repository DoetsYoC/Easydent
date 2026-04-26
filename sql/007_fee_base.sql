-- Easydent migratie 007: fee_base (GOZ basisbedrag) toevoegen aan treatment_items
-- Voer uit via phpMyAdmin op de server

ALTER TABLE `treatment_items`
    ADD COLUMN `fee_base` DECIMAL(8,2) NOT NULL DEFAULT 0.00
    AFTER `factor_default`;

-- GOZ 2012 basisbedragen (1-fach, Anlage 1)
-- Berekening: punten × €0.0562421 (GOZ-Punktwert)
UPDATE treatment_items SET fee_base = 15.75 WHERE goz_code = '0010';
UPDATE treatment_items SET fee_base =  7.28 WHERE goz_code = '0060';
UPDATE treatment_items SET fee_base =  5.82 WHERE goz_code = '1000';
UPDATE treatment_items SET fee_base = 10.87 WHERE goz_code = '1010';
UPDATE treatment_items SET fee_base =  4.66 WHERE goz_code = '1020';
UPDATE treatment_items SET fee_base = 19.13 WHERE goz_code = '1040';
UPDATE treatment_items SET fee_base =  5.17 WHERE goz_code = '4050';
UPDATE treatment_items SET fee_base =  4.66 WHERE goz_code = '1200';
UPDATE treatment_items SET fee_base =  8.96 WHERE goz_code = '4010';
UPDATE treatment_items SET fee_base = 10.87 WHERE goz_code = '4020';
UPDATE treatment_items SET fee_base = 13.70 WHERE goz_code = '4030';
UPDATE treatment_items SET fee_base = 39.68 WHERE goz_code = '2100';
UPDATE treatment_items SET fee_base = 17.21 WHERE goz_code = '2120';
UPDATE treatment_items SET fee_base = 28.69 WHERE goz_code = '2130';
UPDATE treatment_items SET fee_base = 39.68 WHERE goz_code = '2140';
UPDATE treatment_items SET fee_base = 10.87 WHERE goz_code = '2180';
UPDATE treatment_items SET fee_base = 22.50 WHERE goz_code = '2200';
UPDATE treatment_items SET fee_base = 15.75 WHERE goz_code = '2210';
UPDATE treatment_items SET fee_base = 10.87 WHERE goz_code = '2240';
UPDATE treatment_items SET fee_base = 73.92 WHERE goz_code = '2360';
UPDATE treatment_items SET fee_base = 25.47 WHERE goz_code = '2380';
UPDATE treatment_items SET fee_base = 32.20 WHERE goz_code = '2390';
UPDATE treatment_items SET fee_base = 82.22 WHERE goz_code = '2410';
UPDATE treatment_items SET fee_base = 57.84 WHERE goz_code = '2420';
UPDATE treatment_items SET fee_base = 25.47 WHERE goz_code = '2430';
UPDATE treatment_items SET fee_base = 21.10 WHERE goz_code = '3110';
UPDATE treatment_items SET fee_base = 43.61 WHERE goz_code = '3120';
UPDATE treatment_items SET fee_base = 56.61 WHERE goz_code = '3130';
UPDATE treatment_items SET fee_base = 73.92 WHERE goz_code = '3140';
UPDATE treatment_items SET fee_base = 89.87 WHERE goz_code = '3150';
UPDATE treatment_items SET fee_base = 10.87 WHERE goz_code = '0040';
UPDATE treatment_items SET fee_base =  5.82 WHERE goz_code = '0070';
UPDATE treatment_items SET fee_base = 10.87 WHERE goz_code = '0080';
