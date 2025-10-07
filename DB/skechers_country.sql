-- Adminer 4.8.1 MySQL 8.4.3 dump

SET NAMES utf8;
SET time_zone = '+00:00';
SET foreign_key_checks = 0;
SET sql_mode = 'NO_AUTO_VALUE_ON_ZERO';

SET NAMES utf8mb4;

DROP TABLE IF EXISTS `invoice_history_master`;
CREATE TABLE `invoice_history_master` (
  `id` int NOT NULL AUTO_INCREMENT,
  `invoice_id` mediumtext,
  `invoice_status_id` mediumtext,
  `created_by` int DEFAULT NULL,
  `created_at` datetime NOT NULL,
  `status` int NOT NULL DEFAULT '0',
  PRIMARY KEY (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci;


DROP TABLE IF EXISTS `packing_list_config_items`;
CREATE TABLE `packing_list_config_items` (
  `id` int NOT NULL AUTO_INCREMENT,
  `po_id` mediumtext,
  `config_id` mediumtext NOT NULL,
  `vendor_id` mediumtext CHARACTER SET utf8mb4 COLLATE utf8mb4_0900_ai_ci,
  `po_item_id` mediumtext CHARACTER SET utf8mb4 COLLATE utf8mb4_0900_ai_ci,
  `country` mediumtext CHARACTER SET utf8mb4 COLLATE utf8mb4_0900_ai_ci,
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


DROP TABLE IF EXISTS `packing_list_items`;
CREATE TABLE `packing_list_items` (
  `id` int NOT NULL AUTO_INCREMENT,
  `packing_list_id` mediumtext,
  `color` mediumtext CHARACTER SET utf8mb4 COLLATE utf8mb4_0900_ai_ci,
  `vendor_id` mediumtext CHARACTER SET utf8mb4 COLLATE utf8mb4_0900_ai_ci,
  `po_item_id` mediumtext,
  `country` mediumtext CHARACTER SET utf8mb4 COLLATE utf8mb4_0900_ai_ci,
  `carton_id` mediumtext,
  `carton_name` mediumtext CHARACTER SET utf8mb4 COLLATE utf8mb4_0900_ai_ci,
  `article_number` mediumtext,
  `size` mediumtext,
  `quantity` mediumtext,
  `net_weight` mediumtext CHARACTER SET utf8mb4 COLLATE utf8mb4_0900_ai_ci,
  `created_by` int DEFAULT NULL,
  `created_at` datetime DEFAULT NULL,
  `status` int NOT NULL DEFAULT '0',
  PRIMARY KEY (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci;


DROP TABLE IF EXISTS `packing_list_lp_numbers`;
CREATE TABLE `packing_list_lp_numbers` (
  `id` int unsigned NOT NULL AUTO_INCREMENT,
  `packing_list_id` mediumtext COLLATE utf8mb4_unicode_ci,
  `po_id` mediumtext COLLATE utf8mb4_unicode_ci,
  `article_number` mediumtext CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci,
  `color` mediumtext CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci,
  `carton_range` mediumtext CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci,
  `lp_no` mediumtext CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci,
  `created_by` int unsigned DEFAULT NULL,
  `created_at` datetime DEFAULT NULL,
  `status` int NOT NULL DEFAULT '0',
  PRIMARY KEY (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;


DROP TABLE IF EXISTS `packing_list_masters`;
CREATE TABLE `packing_list_masters` (
  `id` int NOT NULL AUTO_INCREMENT,
  `po_id` mediumtext,
  `pack_ref_no` mediumtext CHARACTER SET utf8mb4 COLLATE utf8mb4_0900_ai_ci,
  `vendor_id` mediumtext,
  `packing_po_num` mediumtext CHARACTER SET utf8mb4 COLLATE utf8mb4_0900_ai_ci,
  `packing_table_no` mediumtext CHARACTER SET utf8mb4 COLLATE utf8mb4_0900_ai_ci,
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


DROP TABLE IF EXISTS `po_items`;
CREATE TABLE `po_items` (
  `id` int NOT NULL AUTO_INCREMENT,
  `po_id` int NOT NULL,
  `sno` mediumtext,
  `article_number` mediumtext,
  `id_color` mediumtext,
  `gender` mediumtext CHARACTER SET utf8mb4 COLLATE utf8mb4_0900_ai_ci,
  `type` mediumtext,
  `content` mediumtext,
  `color` mediumtext,
  `color_code` mediumtext,
  `size_grp` mediumtext CHARACTER SET utf8mb4 COLLATE utf8mb4_0900_ai_ci,
  `fi_dates` mediumtext,
  `unit_price` mediumtext,
  `total_amount` mediumtext,
  `style_description` mediumtext,
  `product_character` mediumtext,
  `pack_factor` int DEFAULT NULL,
  `sku_line_no` mediumtext,
  `incoterm` mediumtext,
  `named_place` mediumtext,
  `part_description` mediumtext,
  `material_value` mediumtext,
  `total_value` mediumtext,
  `due_date` mediumtext,
  `size` mediumtext,
  `qty` mediumtext,
  `uom` mediumtext,
  `igst_taxable_value` mediumtext,
  `igst_per` mediumtext,
  `mrp` mediumtext,
  `ean_code` mediumtext,
  `hsn_code` mediumtext,
  `location` mediumtext CHARACTER SET utf8mb4 COLLATE utf8mb4_0900_ai_ci,
  `country` mediumtext CHARACTER SET utf8mb4 COLLATE utf8mb4_0900_ai_ci,
  `created_at` datetime NOT NULL,
  `created_by` int NOT NULL,
  `updated_at` datetime DEFAULT NULL,
  `deleted_at` datetime DEFAULT NULL,
  `status` tinyint NOT NULL DEFAULT '0',
  PRIMARY KEY (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci;


-- 2025-10-07 08:34:27
