-- HAYYAK Database Schema
-- Run this file once on your Railway MySQL instance

SET NAMES utf8mb4;
SET time_zone = '+00:00';

-- --------------------------------------------------------
-- users
-- --------------------------------------------------------
CREATE TABLE IF NOT EXISTS `users` (
  `id`                    INT UNSIGNED NOT NULL AUTO_INCREMENT,
  `email`                 VARCHAR(255) NOT NULL,
  `password_hash`         VARCHAR(255) NOT NULL,
  `role`                  ENUM('user','place_owner','admin') NOT NULL DEFAULT 'user',
  `name_ar`               VARCHAR(150) DEFAULT NULL,
  `name_en`               VARCHAR(150) DEFAULT NULL,
  `dob`                   DATE DEFAULT NULL,
  `status`                VARCHAR(50) DEFAULT NULL,
  `emirate`               VARCHAR(100) DEFAULT NULL,
  `latitude`              DECIMAL(10,7) DEFAULT NULL,
  `longitude`             DECIMAL(10,7) DEFAULT NULL,
  `language`              VARCHAR(10) NOT NULL DEFAULT 'ar',
  `theme`                 VARCHAR(10) NOT NULL DEFAULT 'light',
  `is_healthy`            TINYINT(1) NOT NULL DEFAULT 0,
  `phobias`               JSON DEFAULT NULL,
  `allergies`             JSON DEFAULT NULL,
  `food_preferences`      JSON DEFAULT NULL,
  `medical_conditions`    JSON DEFAULT NULL,
  `interests`             JSON DEFAULT NULL,
  `unlocked_achievements` JSON DEFAULT NULL,
  `avatar_data`           TEXT DEFAULT NULL,
  `avatar_color`          VARCHAR(20) NOT NULL DEFAULT '#16243d',
  `bio`                   TEXT DEFAULT NULL,
  `created_at`            TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `updated_at`            TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  UNIQUE KEY `email` (`email`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------
-- places
-- --------------------------------------------------------
CREATE TABLE IF NOT EXISTS `places` (
  `id`                INT UNSIGNED NOT NULL AUTO_INCREMENT,
  `owner_id`          INT UNSIGNED NOT NULL,
  `category`          ENUM('Restaurant','Hotel','Tourism','Entertainment','Farm','Store','Health') NOT NULL,
  `name_ar`           VARCHAR(255) DEFAULT NULL,
  `name_en`           VARCHAR(255) DEFAULT NULL,
  `city`              VARCHAR(100) DEFAULT NULL,
  `emirate`           VARCHAR(100) DEFAULT NULL,
  `address_ar`        TEXT DEFAULT NULL,
  `address_en`        TEXT DEFAULT NULL,
  `latitude`          DECIMAL(10,7) DEFAULT NULL,
  `longitude`         DECIMAL(10,7) DEFAULT NULL,
  `description_ar`    TEXT DEFAULT NULL,
  `description_en`    TEXT DEFAULT NULL,
  `opening_hours`     VARCHAR(255) DEFAULT NULL,
  `price_range`       VARCHAR(50) DEFAULT NULL,
  `phobia_triggers`   JSON DEFAULT NULL,
  `medical_triggers`  JSON DEFAULT NULL,
  `interest_tags`     JSON DEFAULT NULL,
  `status`            ENUM('pending','approved','rejected') NOT NULL DEFAULT 'pending',
  `rejection_reason`  TEXT DEFAULT NULL,
  `created_at`        TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `updated_at`        TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  KEY `owner_id` (`owner_id`),
  KEY `category` (`category`),
  KEY `status` (`status`),
  CONSTRAINT `fk_places_owner` FOREIGN KEY (`owner_id`) REFERENCES `users` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------
-- menu_items
-- --------------------------------------------------------
CREATE TABLE IF NOT EXISTS `menu_items` (
  `id`                INT UNSIGNED NOT NULL AUTO_INCREMENT,
  `place_id`          INT UNSIGNED NOT NULL,
  `name_ar`           VARCHAR(255) DEFAULT NULL,
  `name_en`           VARCHAR(255) DEFAULT NULL,
  `description_ar`    TEXT DEFAULT NULL,
  `description_en`    TEXT DEFAULT NULL,
  `price`             DECIMAL(10,2) DEFAULT NULL,
  `category`          VARCHAR(100) DEFAULT NULL,
  `allergens`         JSON DEFAULT NULL,
  `phobia_triggers`   JSON DEFAULT NULL,
  `medical_triggers`  JSON DEFAULT NULL,
  `is_available`      TINYINT(1) NOT NULL DEFAULT 1,
  `created_at`        TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  KEY `place_id` (`place_id`),
  CONSTRAINT `fk_menu_place` FOREIGN KEY (`place_id`) REFERENCES `places` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------
-- friendships
-- --------------------------------------------------------
CREATE TABLE IF NOT EXISTS `friendships` (
  `id`           INT UNSIGNED NOT NULL AUTO_INCREMENT,
  `user1_id`     INT UNSIGNED NOT NULL,
  `user2_id`     INT UNSIGNED NOT NULL,
  `status`       ENUM('pending','accepted','declined','blocked') NOT NULL DEFAULT 'pending',
  `requested_by` INT UNSIGNED NOT NULL,
  `blocked_by`   INT UNSIGNED DEFAULT NULL,
  `created_at`   TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `updated_at`   TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  UNIQUE KEY `unique_pair` (`user1_id`,`user2_id`),
  KEY `user2_id` (`user2_id`),
  KEY `requested_by` (`requested_by`),
  CONSTRAINT `fk_f_user1` FOREIGN KEY (`user1_id`) REFERENCES `users` (`id`) ON DELETE CASCADE,
  CONSTRAINT `fk_f_user2` FOREIGN KEY (`user2_id`) REFERENCES `users` (`id`) ON DELETE CASCADE,
  CONSTRAINT `fk_f_requested` FOREIGN KEY (`requested_by`) REFERENCES `users` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------
-- privacy_settings
-- --------------------------------------------------------
CREATE TABLE IF NOT EXISTS `privacy_settings` (
  `id`                       INT UNSIGNED NOT NULL AUTO_INCREMENT,
  `owner_user_id`            INT UNSIGNED NOT NULL,
  `target_friend_id`         INT UNSIGNED DEFAULT NULL,
  `share_health_conditions`  TINYINT(1) NOT NULL DEFAULT 0,
  `share_food_preferences`   TINYINT(1) NOT NULL DEFAULT 0,
  `share_phobias`            TINYINT(1) NOT NULL DEFAULT 0,
  `share_allergies`          TINYINT(1) NOT NULL DEFAULT 0,
  `share_bio`                TINYINT(1) NOT NULL DEFAULT 1,
  `share_avatar`             TINYINT(1) NOT NULL DEFAULT 1,
  `share_emirate_location`   TINYINT(1) NOT NULL DEFAULT 1,
  `share_contact_info`       TINYINT(1) NOT NULL DEFAULT 0,
  PRIMARY KEY (`id`),
  UNIQUE KEY `unique_privacy` (`owner_user_id`,`target_friend_id`),
  KEY `target_friend_id` (`target_friend_id`),
  CONSTRAINT `fk_ps_owner` FOREIGN KEY (`owner_user_id`) REFERENCES `users` (`id`) ON DELETE CASCADE,
  CONSTRAINT `fk_ps_friend` FOREIGN KEY (`target_friend_id`) REFERENCES `users` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------
-- sensor_readings
-- --------------------------------------------------------
CREATE TABLE IF NOT EXISTS `sensor_readings` (
  `id`                  INT UNSIGNED NOT NULL AUTO_INCREMENT,
  `temperature`         DECIMAL(5,2) DEFAULT NULL,
  `humidity`            DECIMAL(5,2) DEFAULT NULL,
  `aqi`                 INT DEFAULT NULL,
  `air_quality_level`   VARCHAR(20) DEFAULT NULL,
  `overcrowding_percent` INT DEFAULT NULL,
  `overcrowding_level`  VARCHAR(20) DEFAULT NULL,
  `place_id`            INT UNSIGNED DEFAULT NULL,
  `recorded_at`         TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  KEY `recorded_at` (`recorded_at`),
  KEY `place_id` (`place_id`),
  CONSTRAINT `fk_sr_place` FOREIGN KEY (`place_id`) REFERENCES `places` (`id`) ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------
-- sessions (DB-backed sessions for Railway ephemeral filesystem)
-- --------------------------------------------------------
CREATE TABLE IF NOT EXISTS `sessions` (
  `id`         VARCHAR(128) NOT NULL,
  `data`       MEDIUMBLOB NOT NULL,
  `expires_at` INT UNSIGNED NOT NULL,
  PRIMARY KEY (`id`),
  KEY `expires_at` (`expires_at`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
