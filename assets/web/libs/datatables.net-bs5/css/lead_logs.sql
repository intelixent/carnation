-- Adminer 4.8.4 MySQL 8.0.25 dump

SET NAMES utf8;
SET time_zone = '+00:00';
SET foreign_key_checks = 0;
SET sql_mode = 'NO_AUTO_VALUE_ON_ZERO';

SET NAMES utf8mb4;

DROP TABLE IF EXISTS `lead_logs`;
CREATE TABLE `lead_logs` (
  `id` int NOT NULL AUTO_INCREMENT,
  `lead_id` int NOT NULL,
  `lead_status` int NOT NULL,
  `follow_up_type` int DEFAULT NULL COMMENT '0-CALL | 1 - Meeting | 2 - Site Visit',
  `follow_up_time` mediumtext,
  `follow_up_date` date DEFAULT NULL,
  `remarks` mediumtext,
  `created_at` datetime NOT NULL,
  `created_by` int DEFAULT NULL,
  `updated_at` datetime DEFAULT NULL,
  `deleted_at` datetime DEFAULT NULL,
  PRIMARY KEY (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci;


-- 2024-10-28 09:43:31
