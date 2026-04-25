-- Easydent migratie 002: Behandeltypes structuur
-- Voer uit via phpMyAdmin op de server

SET FOREIGN_KEY_CHECKS = 0;

-- Behandeltypes (bijv. PZR, Controle, Vulling)
CREATE TABLE IF NOT EXISTS `treatment_types` (
    `id`         INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    `name_de`    VARCHAR(100) NOT NULL,
    `name_nl`    VARCHAR(100) NOT NULL,
    `name_en`    VARCHAR(100) NOT NULL,
    `sort_order` SMALLINT UNSIGNED NOT NULL DEFAULT 0,
    `active`     TINYINT(1) NOT NULL DEFAULT 1,
    `created_at` DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
    `updated_at` DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Prestatiegroepen binnen een behandeltype
CREATE TABLE IF NOT EXISTS `treatment_groups` (
    `id`                  INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    `treatment_type_id`   INT UNSIGNED NOT NULL,
    `name_de`             VARCHAR(100) NOT NULL,
    `name_nl`             VARCHAR(100) NOT NULL,
    `name_en`             VARCHAR(100) NOT NULL,
    `sort_order`          SMALLINT UNSIGNED NOT NULL DEFAULT 0,
    `active`              TINYINT(1) NOT NULL DEFAULT 1,
    `created_at`          DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
    KEY `idx_group_type` (`treatment_type_id`),
    CONSTRAINT `fk_group_type` FOREIGN KEY (`treatment_type_id`)
        REFERENCES `treatment_types`(`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Prestaties (GOZ-codes) binnen een groep
CREATE TABLE IF NOT EXISTS `treatment_items` (
    `id`                   INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    `group_id`             INT UNSIGNED NOT NULL,
    `name_de`              VARCHAR(150) NOT NULL,
    `name_nl`              VARCHAR(150) NOT NULL,
    `name_en`              VARCHAR(150) NOT NULL,
    `goz_code`             VARCHAR(20)  NOT NULL,
    `factor_min`           DECIMAL(4,2) NOT NULL DEFAULT 1.00,
    `factor_max`           DECIMAL(4,2) NOT NULL DEFAULT 3.50,
    `factor_default`       DECIMAL(4,2) NOT NULL DEFAULT 2.30,
    `is_proposed`          TINYINT(1)   NOT NULL DEFAULT 1,
    `is_mandatory`         TINYINT(1)   NOT NULL DEFAULT 0,
    `motivation_required`  TINYINT(1)   NOT NULL DEFAULT 0,
    `suggestion_de`        TEXT         DEFAULT NULL,
    `suggestion_nl`        TEXT         DEFAULT NULL,
    `suggestion_en`        TEXT         DEFAULT NULL,
    `sort_order`           SMALLINT UNSIGNED NOT NULL DEFAULT 0,
    `active`               TINYINT(1)   NOT NULL DEFAULT 1,
    `created_at`           DATETIME     NOT NULL DEFAULT CURRENT_TIMESTAMP,
    `updated_at`           DATETIME     NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    KEY `idx_item_group` (`group_id`),
    CONSTRAINT `fk_item_group` FOREIGN KEY (`group_id`)
        REFERENCES `treatment_groups`(`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Uitsluitingsregels tussen prestaties
CREATE TABLE IF NOT EXISTS `treatment_exclusions` (
    `item_id`         INT UNSIGNED NOT NULL,
    `excludes_item_id` INT UNSIGNED NOT NULL,
    PRIMARY KEY (`item_id`, `excludes_item_id`),
    CONSTRAINT `fk_excl_item`   FOREIGN KEY (`item_id`)          REFERENCES `treatment_items`(`id`) ON DELETE CASCADE,
    CONSTRAINT `fk_excl_target` FOREIGN KEY (`excludes_item_id`) REFERENCES `treatment_items`(`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

SET FOREIGN_KEY_CHECKS = 1;
