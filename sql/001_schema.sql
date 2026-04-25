-- Easydent Database Schema
-- Voer dit uit via phpMyAdmin of MySQL CLI op de server
-- Database: europeaneu10990_Easydent

SET FOREIGN_KEY_CHECKS = 0;
SET sql_mode = 'STRICT_TRANS_TABLES,NO_ZERO_IN_DATE,NO_ZERO_DATE,ERROR_FOR_DIVISION_BY_ZERO,NO_ENGINE_SUBSTITUTION';

-- ============================================================
-- PRACTICES (multi-tenant root)
-- ============================================================
CREATE TABLE IF NOT EXISTS `practices` (
    `id`         INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    `name`       VARCHAR(120) NOT NULL,
    `address`    VARCHAR(255) DEFAULT NULL,
    `city`       VARCHAR(80)  DEFAULT NULL,
    `country`    CHAR(2)      NOT NULL DEFAULT 'DE',
    `phone`      VARCHAR(30)  DEFAULT NULL,
    `email`      VARCHAR(120) DEFAULT NULL,
    `language`   CHAR(2)      NOT NULL DEFAULT 'DE',
    `active`     TINYINT(1)   NOT NULL DEFAULT 1,
    `created_at` DATETIME     NOT NULL DEFAULT CURRENT_TIMESTAMP,
    `updated_at` DATETIME     NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ============================================================
-- USERS (super_admin, practice_manager, practitioner)
-- ============================================================
CREATE TABLE IF NOT EXISTS `users` (
    `id`              INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    `practice_id`     INT UNSIGNED     DEFAULT NULL,         -- NULL = super_admin (geen praktijk)
    `role`            ENUM('super_admin','practice_manager','practitioner') NOT NULL,
    `username`        VARCHAR(60)      DEFAULT NULL,         -- alleen voor manager/admin
    `display_name`    VARCHAR(100)     NOT NULL,
    `email`           VARCHAR(120)     DEFAULT NULL,
    `password_hash`   VARCHAR(255)     DEFAULT NULL,         -- bcrypt, alleen manager/admin
    `pin_hash`        VARCHAR(255)     DEFAULT NULL,         -- bcrypt, alleen practitioner
    `active`          TINYINT(1)       NOT NULL DEFAULT 1,
    `failed_attempts` TINYINT UNSIGNED NOT NULL DEFAULT 0,
    `locked_until`    DATETIME         DEFAULT NULL,
    `last_login`      DATETIME         DEFAULT NULL,
    `created_at`      DATETIME         NOT NULL DEFAULT CURRENT_TIMESTAMP,
    `updated_at`      DATETIME         NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    UNIQUE KEY `uq_username` (`username`),
    CONSTRAINT `fk_users_practice` FOREIGN KEY (`practice_id`) REFERENCES `practices`(`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ============================================================
-- PATIENTS
-- ============================================================
CREATE TABLE IF NOT EXISTS `patients` (
    `id`           INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    `practice_id`  INT UNSIGNED NOT NULL,
    `charly_id`    VARCHAR(30)  DEFAULT NULL,                -- extern ID uit Charly
    `first_name`   VARCHAR(60)  NOT NULL,
    `last_name`    VARCHAR(60)  NOT NULL,
    `birth_date`   DATE         DEFAULT NULL,
    `email`        VARCHAR(120) DEFAULT NULL,
    `phone`        VARCHAR(30)  DEFAULT NULL,
    `notes`        TEXT         DEFAULT NULL,
    `active`       TINYINT(1)   NOT NULL DEFAULT 1,
    `created_at`   DATETIME     NOT NULL DEFAULT CURRENT_TIMESTAMP,
    `updated_at`   DATETIME     NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    KEY `idx_patients_practice` (`practice_id`),
    KEY `idx_patients_charly`   (`charly_id`),
    CONSTRAINT `fk_patients_practice` FOREIGN KEY (`practice_id`) REFERENCES `practices`(`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ============================================================
-- APPOINTMENTS
-- ============================================================
CREATE TABLE IF NOT EXISTS `appointments` (
    `id`             INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    `practice_id`    INT UNSIGNED NOT NULL,
    `patient_id`     INT UNSIGNED NOT NULL,
    `practitioner_id` INT UNSIGNED NOT NULL,                 -- users.id met role=practitioner
    `treatment_type` ENUM('PZR','Kontrolle','Fuellungstherapie','Endodontie') NOT NULL DEFAULT 'PZR',
    `scheduled_at`   DATETIME     NOT NULL,
    `duration_min`   SMALLINT UNSIGNED NOT NULL DEFAULT 60,
    `status`         ENUM('planned','in_progress','completed','cancelled') NOT NULL DEFAULT 'planned',
    `charly_id`      VARCHAR(30)  DEFAULT NULL,
    `notes`          TEXT         DEFAULT NULL,
    `created_at`     DATETIME     NOT NULL DEFAULT CURRENT_TIMESTAMP,
    `updated_at`     DATETIME     NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    KEY `idx_appt_practice`    (`practice_id`),
    KEY `idx_appt_patient`     (`patient_id`),
    KEY `idx_appt_practitioner`(`practitioner_id`),
    KEY `idx_appt_scheduled`   (`scheduled_at`),
    CONSTRAINT `fk_appt_practice`     FOREIGN KEY (`practice_id`)     REFERENCES `practices`(`id`)    ON DELETE CASCADE,
    CONSTRAINT `fk_appt_patient`      FOREIGN KEY (`patient_id`)      REFERENCES `patients`(`id`)     ON DELETE CASCADE,
    CONSTRAINT `fk_appt_practitioner` FOREIGN KEY (`practitioner_id`) REFERENCES `users`(`id`)        ON DELETE RESTRICT
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ============================================================
-- TREATMENT SESSIONS (één per afspraak)
-- ============================================================
CREATE TABLE IF NOT EXISTS `treatment_sessions` (
    `id`              INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    `appointment_id`  INT UNSIGNED NOT NULL,
    `practice_id`     INT UNSIGNED NOT NULL,
    `practitioner_id` INT UNSIGNED NOT NULL,
    `treatment_type`  ENUM('PZR','Kontrolle','Fuellungstherapie','Endodontie') NOT NULL,
    `num_teeth`       TINYINT UNSIGNED DEFAULT NULL,
    `num_surfaces`    TINYINT UNSIGNED DEFAULT NULL,
    `anesthesia`      TINYINT(1) NOT NULL DEFAULT 0,
    `notes`           TEXT       DEFAULT NULL,
    `consent_signed`  TINYINT(1) NOT NULL DEFAULT 0,
    `consent_at`      DATETIME   DEFAULT NULL,
    `consent_signature` MEDIUMTEXT DEFAULT NULL,             -- base64 handtekening
    `status`          ENUM('draft','completed','exported') NOT NULL DEFAULT 'draft',
    `exported_at`     DATETIME   DEFAULT NULL,
    `created_at`      DATETIME   NOT NULL DEFAULT CURRENT_TIMESTAMP,
    `updated_at`      DATETIME   NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    UNIQUE KEY `uq_session_appointment` (`appointment_id`),
    KEY `idx_session_practice`     (`practice_id`),
    KEY `idx_session_practitioner` (`practitioner_id`),
    CONSTRAINT `fk_session_appointment`  FOREIGN KEY (`appointment_id`)  REFERENCES `appointments`(`id`) ON DELETE CASCADE,
    CONSTRAINT `fk_session_practice`     FOREIGN KEY (`practice_id`)     REFERENCES `practices`(`id`)   ON DELETE CASCADE,
    CONSTRAINT `fk_session_practitioner` FOREIGN KEY (`practitioner_id`) REFERENCES `users`(`id`)       ON DELETE RESTRICT
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ============================================================
-- SESSION CODES (GOZ codes per behandelsessie)
-- ============================================================
CREATE TABLE IF NOT EXISTS `session_codes` (
    `id`           INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    `session_id`   INT UNSIGNED NOT NULL,
    `practice_id`  INT UNSIGNED NOT NULL,
    `goz_code`     VARCHAR(10)  NOT NULL,                   -- bijv. "1040", "4050a"
    `description`  VARCHAR(255) DEFAULT NULL,
    `quantity`     DECIMAL(5,2) NOT NULL DEFAULT 1.00,
    `factor`       DECIMAL(4,2) NOT NULL DEFAULT 2.30,      -- GOZ factor
    `fee_base`     DECIMAL(8,2) NOT NULL DEFAULT 0.00,      -- basisbedrag in euro
    `fee_total`    DECIMAL(8,2) NOT NULL DEFAULT 0.00,      -- totaalbedrag (fee_base * factor * quantity)
    `sort_order`   SMALLINT UNSIGNED NOT NULL DEFAULT 0,
    `created_at`   DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
    KEY `idx_codes_session`  (`session_id`),
    KEY `idx_codes_practice` (`practice_id`),
    CONSTRAINT `fk_codes_session`  FOREIGN KEY (`session_id`)  REFERENCES `treatment_sessions`(`id`) ON DELETE CASCADE,
    CONSTRAINT `fk_codes_practice` FOREIGN KEY (`practice_id`) REFERENCES `practices`(`id`)          ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ============================================================
-- AUDIT LOG
-- ============================================================
CREATE TABLE IF NOT EXISTS `audit_log` (
    `id`          BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    `practice_id` INT UNSIGNED DEFAULT NULL,
    `user_id`     INT UNSIGNED DEFAULT NULL,
    `action`      VARCHAR(60)  NOT NULL,                    -- bijv. 'login', 'logout', 'create_session'
    `entity_type` VARCHAR(40)  DEFAULT NULL,                -- bijv. 'appointment', 'user'
    `entity_id`   INT UNSIGNED DEFAULT NULL,
    `ip_address`  VARCHAR(45)  DEFAULT NULL,
    `user_agent`  VARCHAR(255) DEFAULT NULL,
    `details`     JSON         DEFAULT NULL,
    `created_at`  DATETIME     NOT NULL DEFAULT CURRENT_TIMESTAMP,
    KEY `idx_audit_practice` (`practice_id`),
    KEY `idx_audit_user`     (`user_id`),
    KEY `idx_audit_action`   (`action`),
    KEY `idx_audit_created`  (`created_at`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

SET FOREIGN_KEY_CHECKS = 1;
