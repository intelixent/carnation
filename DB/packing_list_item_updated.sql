-- Adminer 4.8.1 MySQL 8.4.3 dump

SET NAMES utf8;
SET time_zone = '+00:00';
SET foreign_key_checks = 0;
SET sql_mode = 'NO_AUTO_VALUE_ON_ZERO';

SET NAMES utf8mb4;

DROP TABLE IF EXISTS `packing_list_items`;
CREATE TABLE `packing_list_items` (
  `id` int NOT NULL AUTO_INCREMENT,
  `packing_list_id` mediumtext,
  `po_item_id` mediumtext,
  `carton_id` mediumtext,
  `article_number` mediumtext,
  `size` mediumtext,
  `quantity` mediumtext,
  `created_by` int DEFAULT NULL,
  `created_at` datetime DEFAULT NULL,
  `status` int NOT NULL DEFAULT '0',
  PRIMARY KEY (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci;


-- 2025-05-29 17:26:30
