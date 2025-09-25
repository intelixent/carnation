-- Adminer 4.8.1 MySQL 8.4.3 dump

SET NAMES utf8;
SET time_zone = '+00:00';
SET foreign_key_checks = 0;
SET sql_mode = 'NO_AUTO_VALUE_ON_ZERO';

SET NAMES utf8mb4;

DROP TABLE IF EXISTS `packing_list_config_items`;
CREATE TABLE `packing_list_config_items` (
  `id` int NOT NULL AUTO_INCREMENT,
  `po_id` mediumtext,
  `config_id` mediumtext NOT NULL,
  `vendor_id` mediumtext CHARACTER SET utf8mb4 COLLATE utf8mb4_0900_ai_ci,
  `po_item_id` mediumtext CHARACTER SET utf8mb4 COLLATE utf8mb4_0900_ai_ci,
  `color` mediumtext NOT NULL,
  `size` mediumtext NOT NULL,
  `po_qty` mediumtext NOT NULL,
  `pack_qty` mediumtext NOT NULL,
  `position` mediumtext CHARACTER SET utf8mb4 COLLATE utf8mb4_0900_ai_ci,
  `per_carton_qty` mediumtext CHARACTER SET utf8mb4 COLLATE utf8mb4_0900_ai_ci,
  `weight_per_piece` mediumtext CHARACTER SET utf8mb4 COLLATE utf8mb4_0900_ai_ci,
  `created_by` int NOT NULL,
  `created_at` datetime NOT NULL,
  `status` int NOT NULL DEFAULT '0',
  PRIMARY KEY (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci;


DROP TABLE IF EXISTS `packing_list_masters`;
CREATE TABLE `packing_list_masters` (
  `id` int NOT NULL AUTO_INCREMENT,
  `po_id` mediumtext,
  `pack_ref_no` mediumtext CHARACTER SET utf8mb4 COLLATE utf8mb4_0900_ai_ci,
  `vendor_id` mediumtext,
  `packing_po_num` mediumtext CHARACTER SET utf8mb4 COLLATE utf8mb4_0900_ai_ci,
  `po_no` mediumtext,
  `color` mediumtext CHARACTER SET utf8mb4 COLLATE utf8mb4_0900_ai_ci,
  `po_date` mediumtext,
  `location` mediumtext CHARACTER SET utf8mb4 COLLATE utf8mb4_0900_ai_ci,
  `created_by` int DEFAULT NULL,
  `created_at` datetime DEFAULT NULL ON UPDATE CURRENT_TIMESTAMP,
  `pack_status` tinyint DEFAULT '0' COMMENT '0-In Pack,1-Complete,2-invoice',
  `status` int NOT NULL DEFAULT '0',
  PRIMARY KEY (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci;


-- 2025-09-25 11:23:49
