-- Easydent migratie 005: Behandeltype koppeling op afspraken
-- Voer uit via phpMyAdmin

ALTER TABLE `appointments`
  MODIFY COLUMN `treatment_type` VARCHAR(60) NULL DEFAULT NULL,
  ADD COLUMN `treatment_type_id` INT UNSIGNED NULL AFTER `treatment_type`,
  ADD KEY `idx_appt_type_id` (`treatment_type_id`),
  ADD CONSTRAINT `fk_appt_type_id` FOREIGN KEY (`treatment_type_id`)
    REFERENCES `treatment_types`(`id`) ON DELETE SET NULL;

ALTER TABLE `treatment_sessions`
  MODIFY COLUMN `treatment_type` VARCHAR(60) NULL DEFAULT NULL,
  ADD COLUMN `treatment_type_id` INT UNSIGNED NULL AFTER `treatment_type`,
  ADD CONSTRAINT `fk_session_type_id` FOREIGN KEY (`treatment_type_id`)
    REFERENCES `treatment_types`(`id`) ON DELETE SET NULL;
