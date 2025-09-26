-- Adminer 4.8.1 MySQL 8.4.3 dump

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
  `gst` mediumtext CHARACTER SET utf8mb4 COLLATE utf8mb4_0900_ai_ci NOT NULL,
  `bill_to_details` mediumtext CHARACTER SET utf8mb4 COLLATE utf8mb4_0900_ai_ci,
  `ship_to_details` mediumtext CHARACTER SET utf8mb4 COLLATE utf8mb4_0900_ai_ci,
  `po_id` int NOT NULL,
  `pack_ids` mediumtext NOT NULL,
  `irn_details` mediumtext CHARACTER SET utf8mb4 COLLATE utf8mb4_0900_ai_ci,
  `transporter_details` mediumtext CHARACTER SET utf8mb4 COLLATE utf8mb4_0900_ai_ci,
  `grn_details` mediumtext CHARACTER SET utf8mb4 COLLATE utf8mb4_0900_ai_ci,
  `vendor_id` int NOT NULL,
  `invoice_status_id` int DEFAULT NULL,
  `created_at` datetime NOT NULL,
  `created_by` int NOT NULL,
  `status` tinyint NOT NULL DEFAULT '0',
  PRIMARY KEY (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci;


DROP TABLE IF EXISTS `invoice_status_master`;
CREATE TABLE `invoice_status_master` (
  `id` int NOT NULL AUTO_INCREMENT,
  `name` mediumtext CHARACTER SET utf8mb4 COLLATE utf8mb4_0900_ai_ci NOT NULL,
  `status` int NOT NULL DEFAULT '0',
  PRIMARY KEY (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci;

INSERT INTO `invoice_status_master` (`id`, `name`, `status`) VALUES
(1,	'Invoiced',	0),
(2,	'In Transit',	0),
(3,	'GRN Pending',	0),
(4,	'GRN Done',	0),
(5,	'Payment Pending',	0),
(6,	'Payment Received',	0),
(7,	'Invoice Not Disposed',	0),
(8,	'Cancelled',	0);

-- 2025-09-26 19:52:16
