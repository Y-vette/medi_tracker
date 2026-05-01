-- ============================================================
-- Meditracker — AI-Powered Hospital Ward Monitoring System
-- Database: meditracker_db
-- Import this in phpMyAdmin after setting up XAMPP
-- Default admin login: admin / Admin@123
-- ============================================================

SET SQL_MODE = "NO_AUTO_VALUE_ON_ZERO";
START TRANSACTION;
SET time_zone = "+00:00";
SET NAMES utf8mb4;

CREATE DATABASE IF NOT EXISTS `meditracker_db`
  CHARACTER SET utf8mb4
  COLLATE utf8mb4_unicode_ci;
USE `meditracker_db`;

-- ── users ───────────────────────────────────────────────────
CREATE TABLE `users` (
  `id`         INT AUTO_INCREMENT PRIMARY KEY,
  `username`   VARCHAR(100) NOT NULL UNIQUE,
  `password`   VARCHAR(255) NOT NULL,
  `role`       ENUM('admin','doctor','nurse','hio','receptionist') NOT NULL,
  `full_name`  VARCHAR(150),
  `email`      VARCHAR(150),
  `phone`      VARCHAR(20),
  `created_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ── wards ───────────────────────────────────────────────────
CREATE TABLE `wards` (
  `id`            INT AUTO_INCREMENT PRIMARY KEY,
  `ward_name`     VARCHAR(100) NOT NULL,
  `total_beds`    INT DEFAULT 0,
  `occupied_beds` INT DEFAULT 0,
  `ward_type`     ENUM('general','icu','maternity','paediatric','surgical','tb','other') DEFAULT 'general',
  `created_at`    TIMESTAMP DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ── ward_daily_reports ──────────────────────────────────────
CREATE TABLE `ward_daily_reports` (
  `id`               INT AUTO_INCREMENT PRIMARY KEY,
  `ward_id`          INT NOT NULL,
  `report_date`      DATE NOT NULL,
  `day_admissions`   INT DEFAULT 0,
  `night_admissions` INT DEFAULT 0,
  `critical_cases`   INT DEFAULT 0,
  `on_oxygen`        INT DEFAULT 0,
  `discharges`       INT DEFAULT 0,
  `deaths`           INT DEFAULT 0,
  `notes`            TEXT,
  `submitted_by`     INT,
  `created_at`       TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
  FOREIGN KEY (`ward_id`)      REFERENCES `wards`(`id`)      ON DELETE CASCADE,
  FOREIGN KEY (`submitted_by`) REFERENCES `users`(`id`)      ON DELETE SET NULL,
  UNIQUE KEY `unique_ward_date` (`ward_id`, `report_date`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ── patients ────────────────────────────────────────────────
CREATE TABLE `patients` (
  `id`              INT AUTO_INCREMENT PRIMARY KEY,
  `full_name`       VARCHAR(150) NOT NULL,
  `id_number`       VARCHAR(50),
  `date_of_birth`   DATE,
  `gender`          ENUM('Male','Female','Other'),
  `phone`           VARCHAR(20),
  `address`         TEXT,
  `ward_id`         INT,
  `admission_date`  DATE,
  `status`          ENUM('Admitted','Discharged','Critical','Deceased') DEFAULT 'Admitted',
  `on_oxygen`       TINYINT(1) DEFAULT 0,
  `diagnosis`       TEXT,
  `created_at`      TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
  FOREIGN KEY (`ward_id`) REFERENCES `wards`(`id`) ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ── ai_insights ─────────────────────────────────────────────
CREATE TABLE `ai_insights` (
  `id`          INT AUTO_INCREMENT PRIMARY KEY,
  `insight`     TEXT NOT NULL,
  `severity`    ENUM('info','warning','critical') DEFAULT 'info',
  `ward_id`     INT,
  `generated_at`TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
  `is_read`     TINYINT(1) DEFAULT 0,
  FOREIGN KEY (`ward_id`) REFERENCES `wards`(`id`) ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ── Seed: users ─────────────────────────────────────────────
-- password = Admin@123
INSERT INTO `users` (`username`,`password`,`role`,`full_name`,`email`) VALUES
('admin',    '$2y$10$J9iZeD9vXBnWso4ulpsYaOq700XLJdcd6G0iBBgCSniydRLGUWJsa','admin','System Administrator','admin@meditracker.na'),
('nurse_joy','$2y$10$J9iZeD9vXBnWso4ulpsYaOq700XLJdcd6G0iBBgCSniydRLGUWJsa','nurse','Joy Nangula','joy@meditracker.na'),
('hio_sam',  '$2y$10$J9iZeD9vXBnWso4ulpsYaOq700XLJdcd6G0iBBgCSniydRLGUWJsa','hio','Samuel Hamutenya','sam@meditracker.na');

-- ── Seed: wards ─────────────────────────────────────────────
INSERT INTO `wards` (`ward_name`,`total_beds`,`occupied_beds`,`ward_type`) VALUES
('Male Ward',    40, 28, 'general'),
('Female Ward',  35, 31, 'general'),
('Paediatric',   30, 19, 'paediatric'),
('ICU',          15, 12, 'icu'),
('TB Ward',      25, 16, 'tb');

-- ── Seed: ward reports (last 7 days) ────────────────────────
INSERT INTO `ward_daily_reports`(`ward_id`,`report_date`,`day_admissions`,`night_admissions`,`critical_cases`,`on_oxygen`,`discharges`,`deaths`,`submitted_by`) VALUES
(1, CURDATE()-6, 10,6,1,2,5,0,2),(2,CURDATE()-6,9,5,2,3,4,1,2),(3,CURDATE()-6,8,7,0,1,6,0,2),(4,CURDATE()-6,5,4,4,5,3,0,2),(5,CURDATE()-6,7,2,1,1,5,0,2),
(1, CURDATE()-5, 11,7,1,2,6,0,2),(2,CURDATE()-5,10,6,2,4,5,1,2),(3,CURDATE()-5,9,8,0,2,7,0,2),(4,CURDATE()-5,6,5,5,6,4,1,2),(5,CURDATE()-5,8,3,1,2,6,0,2),
(1, CURDATE()-4, 10,6,1,2,5,0,2),(2,CURDATE()-4,11,7,3,4,5,0,2),(3,CURDATE()-4,8,6,0,1,6,0,2),(4,CURDATE()-4,5,5,5,5,3,0,2),(5,CURDATE()-4,7,2,1,1,5,0,2),
(1, CURDATE()-3, 13,8,2,3,7,0,2),(2,CURDATE()-3,10,7,2,3,6,1,2),(3,CURDATE()-3,9,9,1,2,7,0,2),(4,CURDATE()-3,7,6,5,7,5,1,2),(5,CURDATE()-3,9,3,2,2,7,0,2),
(1, CURDATE()-2, 12,7,1,2,6,0,2),(2,CURDATE()-2,10,7,2,4,5,0,2),(3,CURDATE()-2,9,8,0,1,7,0,2),(4,CURDATE()-2,6,5,5,6,4,0,2),(5,CURDATE()-2,8,3,1,1,6,0,2),
(1, CURDATE()-1, 13,8,1,3,7,0,2),(2,CURDATE()-1,11,8,2,4,6,1,2),(3,CURDATE()-1,10,9,0,2,8,0,2),(4,CURDATE()-1,7,6,5,7,5,1,2),(5,CURDATE()-1,9,3,1,2,7,0,2),
(1, CURDATE(),   12,7,1,2,6,0,2),(2,CURDATE(),   10,7,2,4,5,1,2),(3,CURDATE(),   9,8,0,1,7,0,2),(4,CURDATE(),   6,5,5,6,4,0,2),(5,CURDATE(),   8,3,1,1,6,0,2);

-- ── Seed: patients ──────────────────────────────────────────
INSERT INTO `patients`(`full_name`,`id_number`,`gender`,`ward_id`,`admission_date`,`status`,`on_oxygen`,`diagnosis`) VALUES
('Johannes Nghipundeka','98030412345','Male',  1, CURDATE()-3,'Admitted', 0,'Malaria'),
('Maria Shilongo',      '00051567890','Female',2, CURDATE()-1,'Critical', 1,'Pneumonia'),
('Petrus Iipinge',      '85112233445','Male',  4, CURDATE()-5,'Critical', 1,'Sepsis'),
('Anna Hamunyela',      '03040978654','Female',2, CURDATE()-2,'Admitted', 0,'Hypertension'),
('Samuel Kamati',       '92061544321','Male',  4, CURDATE()-4,'Critical', 1,'Multi-organ failure'),
('Lahja Nangula',       '10020298765','Female',3, CURDATE()-1,'Admitted', 0,'Bronchiolitis');

-- ── Seed: AI insights ───────────────────────────────────────
INSERT INTO `ai_insights`(`insight`,`severity`,`ward_id`) VALUES
('Patient admissions expected to increase tomorrow based on 7-day trend analysis.','warning',NULL),
('Female Ward is at 87% occupancy — approaching critical threshold. Consider patient transfers.','critical',2),
('Oxygen supply is stable across all wards. Current stock adequate for approximately 4 days.','info',NULL),
('ICU has 5 critical patients tonight — staffing review recommended for the night shift.','warning',4),
('TB Ward discharge rate is above average this week — positive patient flow indicator.','info',5);

COMMIT;
