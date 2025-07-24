-- Adminer 4.8.1 MySQL 8.4.3 dump

SET NAMES utf8;
SET time_zone = '+00:00';
SET foreign_key_checks = 0;
SET sql_mode = 'NO_AUTO_VALUE_ON_ZERO';

SET NAMES utf8mb4;

DROP TABLE IF EXISTS `business_setting_master`;
CREATE TABLE `business_setting_master` (
  `id` int NOT NULL AUTO_INCREMENT,
  `name` mediumtext,
  `value` mediumtext,
  `created_by` int DEFAULT NULL,
  `created_at` datetime DEFAULT NULL,
  `status` int NOT NULL DEFAULT '0',
  PRIMARY KEY (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci;

INSERT INTO `business_setting_master` (`id`, `name`, `value`, `created_by`, `created_at`, `status`) VALUES
(1,	'nsme_register_no',	'UDYAM-TN-03-0004047',	1,	'2025-07-24 11:50:05',	0),
(2,	'nsme_register_date',	'25-08-2020',	1,	'2025-07-24 11:50:05',	0),
(3,	'nsme_type',	'Medium',	1,	'2025-07-24 11:50:05',	0),
(4,	'nsme_sector',	'Manufacturing',	1,	'2025-07-24 11:50:05',	0),
(5,	'business_pan_no',	'AAHCC1371N',	1,	'2025-07-24 11:50:05',	0),
(6,	'business_gst_no',	'33AAHCC1371N1ZL',	1,	'2025-07-24 11:50:05',	0);

-- 2025-07-24 06:39:51

-- Adminer 4.8.1 MySQL 8.4.3 dump

SET NAMES utf8;
SET time_zone = '+00:00';
SET foreign_key_checks = 0;
SET sql_mode = 'NO_AUTO_VALUE_ON_ZERO';

SET NAMES utf8mb4;

DROP TABLE IF EXISTS `vendor_master`;
CREATE TABLE `vendor_master` (
  `id` int NOT NULL AUTO_INCREMENT,
  `name` mediumtext NOT NULL,
  `mobile` mediumtext CHARACTER SET utf8mb4 COLLATE utf8mb4_0900_ai_ci,
  `email` varchar(255) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `address` mediumtext CHARACTER SET utf8mb4 COLLATE utf8mb4_0900_ai_ci,
  `gst_no` mediumtext CHARACTER SET utf8mb4 COLLATE utf8mb4_0900_ai_ci,
  `pan_no` mediumtext CHARACTER SET utf8mb4 COLLATE utf8mb4_0900_ai_ci,
  `state_id` mediumtext CHARACTER SET utf8mb4 COLLATE utf8mb4_0900_ai_ci,
  `excess` mediumtext CHARACTER SET utf8mb4 COLLATE utf8mb4_0900_ai_ci,
  `discount` mediumtext CHARACTER SET utf8mb4 COLLATE utf8mb4_0900_ai_ci,
  `shortage` mediumtext CHARACTER SET utf8mb4 COLLATE utf8mb4_0900_ai_ci,
  `extraction_no` mediumtext CHARACTER SET utf8mb4 COLLATE utf8mb4_0900_ai_ci,
  `custom_field_no` mediumtext CHARACTER SET utf8mb4 COLLATE utf8mb4_0900_ai_ci,
  `notes` mediumtext,
  `created_by` int DEFAULT NULL,
  `created_at` datetime DEFAULT NULL,
  `status` int NOT NULL DEFAULT '0',
  PRIMARY KEY (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci;


-- 2025-07-24 06:40:21

