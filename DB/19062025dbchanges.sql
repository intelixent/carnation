-- Adminer 4.8.1 MySQL 8.0.25 dump

SET NAMES utf8;
SET time_zone = '+00:00';
SET foreign_key_checks = 0;
SET sql_mode = 'NO_AUTO_VALUE_ON_ZERO';

SET NAMES utf8mb4;

DROP TABLE IF EXISTS `invoice_masters`;
CREATE TABLE `invoice_masters` (
  `id` int NOT NULL AUTO_INCREMENT,
  `ref_no` mediumtext NOT NULL,
  `inv_date` mediumtext NOT NULL,
  `bill_to_details` mediumtext CHARACTER SET utf8mb4 COLLATE utf8mb4_0900_ai_ci,
  `ship_to_details` mediumtext CHARACTER SET utf8mb4 COLLATE utf8mb4_0900_ai_ci,
  `po_id` int NOT NULL,
  `pack_ids` mediumtext NOT NULL,
  `irn_details` mediumtext CHARACTER SET utf8mb4 COLLATE utf8mb4_0900_ai_ci,
  `transporter_details` mediumtext CHARACTER SET utf8mb4 COLLATE utf8mb4_0900_ai_ci,
  `vendor_id` int NOT NULL,
  `created_at` datetime NOT NULL,
  `created_by` int NOT NULL,
  `status` tinyint NOT NULL DEFAULT '0',
  PRIMARY KEY (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci;

INSERT INTO `invoice_masters` (`id`, `ref_no`, `inv_date`, `bill_to_details`, `ship_to_details`, `po_id`, `pack_ids`, `irn_details`, `transporter_details`, `vendor_id`, `created_at`, `created_by`, `status`) VALUES
(1,	'CCPL041419/24-25',	'2025-06-19',	'',	'',	1,	'1',	NULL,	NULL,	1,	'2025-06-19 08:40:32',	1,	0);

DROP TABLE IF EXISTS `packing_list_masters`;
CREATE TABLE `packing_list_masters` (
  `id` int NOT NULL AUTO_INCREMENT,
  `po_id` mediumtext,
  `pack_ref_no` mediumtext CHARACTER SET utf8mb4 COLLATE utf8mb4_0900_ai_ci,
  `vendor_id` mediumtext,
  `po_no` mediumtext,
  `color` mediumtext CHARACTER SET utf8mb4 COLLATE utf8mb4_0900_ai_ci,
  `po_date` mediumtext,
  `created_by` int DEFAULT NULL,
  `created_at` datetime DEFAULT NULL ON UPDATE CURRENT_TIMESTAMP,
  `pack_status` tinyint DEFAULT '0' COMMENT '0-In Pack,1-Complete,2-invoice',
  `status` int NOT NULL DEFAULT '0',
  PRIMARY KEY (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci;

INSERT INTO `packing_list_masters` (`id`, `po_id`, `pack_ref_no`, `vendor_id`, `po_no`, `color`, `po_date`, `created_by`, `created_at`, `pack_status`, `status`) VALUES
(1,	'1',	'123456/1',	'1',	'4500149484',	'Brilliant white',	'12.02.2025',	1,	'2025-06-19 08:40:32',	2,	0),
(2,	'1',	'123456/2',	'1',	'4500149484',	NULL,	'12.02.2025',	1,	'2025-06-18 12:28:31',	0,	0);

DROP TABLE IF EXISTS `prefix_setting`;
CREATE TABLE `prefix_setting` (
  `id` int NOT NULL AUTO_INCREMENT,
  `name` mediumtext CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NOT NULL,
  `format` mediumtext CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NOT NULL,
  `number` int NOT NULL,
  `suffix` mediumtext COLLATE utf8mb4_unicode_ci,
  `created_at` datetime NOT NULL,
  `status` tinyint NOT NULL DEFAULT '0',
  PRIMARY KEY (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

INSERT INTO `prefix_setting` (`id`, `name`, `format`, `number`, `suffix`, `created_at`, `status`) VALUES
(1,	'PO',	'PO/',	3,	NULL,	'2025-06-09 11:31:39',	0),
(2,	'Invoice',	'CCPL',	41419,	'24-25',	'2025-06-19 08:07:30',	0);

-- 2025-06-19 05:15:33