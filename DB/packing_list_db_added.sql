-- Adminer 4.8.1 MySQL 8.4.3 dump

SET NAMES utf8;
SET time_zone = '+00:00';
SET foreign_key_checks = 0;
SET sql_mode = 'NO_AUTO_VALUE_ON_ZERO';

SET NAMES utf8mb4;

DROP TABLE IF EXISTS `carton_master`;
CREATE TABLE `carton_master` (
  `id` int NOT NULL AUTO_INCREMENT,
  `vendor_id` mediumtext,
  `name` mediumtext,
  `length` mediumtext,
  `breadth` mediumtext,
  `height` mediumtext,
  `weight` mediumtext,
  `created_by` mediumtext,
  `created_at` datetime DEFAULT NULL,
  `status` int NOT NULL DEFAULT '0',
  PRIMARY KEY (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci;


DROP TABLE IF EXISTS `packing_list_items`;
CREATE TABLE `packing_list_items` (
  `id` int NOT NULL AUTO_INCREMENT,
  `packing_list_id` mediumtext,
  `carton_id` mediumtext,
  `article_number` mediumtext,
  `size` mediumtext,
  `quantity` mediumtext,
  `created_by` int DEFAULT NULL,
  `created_at` datetime DEFAULT NULL,
  `status` int NOT NULL DEFAULT '0',
  PRIMARY KEY (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci;


DROP TABLE IF EXISTS `packing_list_masters`;
CREATE TABLE `packing_list_masters` (
  `id` int NOT NULL AUTO_INCREMENT,
  `po_id` mediumtext,
  `vendor_id` mediumtext,
  `po_no` mediumtext,
  `po_date` mediumtext,
  `created_by` int DEFAULT NULL,
  `created_at` datetime DEFAULT NULL,
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
  `created_at` datetime NOT NULL,
  `created_by` int NOT NULL,
  `updated_at` datetime DEFAULT NULL,
  `deleted_at` datetime DEFAULT NULL,
  `status` tinyint NOT NULL DEFAULT '0',
  PRIMARY KEY (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci;


DROP TABLE IF EXISTS `po_masters`;
CREATE TABLE `po_masters` (
  `id` int NOT NULL AUTO_INCREMENT,
  `vendor_id` mediumtext,
  `po_ref_num` mediumtext NOT NULL,
  `po_num` mediumtext,
  `po_date` mediumtext,
  `goods_ready_date` mediumtext,
  `mrp` mediumtext,
  `vcp` mediumtext,
  `colors` mediumtext,
  `vendor_del_adr` mediumtext,
  `vendor_com_adr` mediumtext,
  `vendor_gst` mediumtext,
  `vendor_cin` mediumtext,
  `article_info` mediumtext,
  `po_unit_price` mediumtext,
  `po_qty` mediumtext,
  `created_at` datetime NOT NULL,
  `created_by` int NOT NULL,
  `status` tinyint NOT NULL DEFAULT '0',
  `updated_at` datetime DEFAULT NULL,
  `deleted_at` datetime DEFAULT NULL,
  PRIMARY KEY (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci;


DROP TABLE IF EXISTS `vendor_master`;
CREATE TABLE `vendor_master` (
  `id` int NOT NULL AUTO_INCREMENT,
  `name` mediumtext NOT NULL,
  `mobile` mediumtext CHARACTER SET utf8mb4 COLLATE utf8mb4_0900_ai_ci,
  `email` varchar(255) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `address` mediumtext CHARACTER SET utf8mb4 COLLATE utf8mb4_0900_ai_ci,
  `gst_no` mediumtext CHARACTER SET utf8mb4 COLLATE utf8mb4_0900_ai_ci,
  `state_id` mediumtext CHARACTER SET utf8mb4 COLLATE utf8mb4_0900_ai_ci,
  `notes` mediumtext,
  `created_by` int DEFAULT NULL,
  `created_at` datetime DEFAULT NULL,
  `status` int NOT NULL DEFAULT '0',
  PRIMARY KEY (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci;

INSERT INTO `vendor_master` (`id`, `name`, `mobile`, `email`, `address`, `gst_no`, `state_id`, `notes`, `created_by`, `created_at`, `status`) VALUES
(1,	'Jack Jones',	NULL,	NULL,	NULL,	NULL,	'33',	NULL,	1,	'2025-05-25 21:41:35',	0),
(2,	'Skecher',	NULL,	NULL,	NULL,	NULL,	'33',	NULL,	1,	'2025-05-25 21:41:35',	0),
(3,	'Puma',	NULL,	NULL,	NULL,	NULL,	'33',	NULL,	1,	'2025-05-25 21:41:35',	0),
(4,	'Benetton',	NULL,	NULL,	NULL,	NULL,	'33',	NULL,	1,	'2025-05-25 21:41:35',	0),
(5,	'Selected',	NULL,	NULL,	NULL,	NULL,	'33',	NULL,	1,	'2025-05-25 21:41:35',	0),
(6,	'Vero Modo',	NULL,	NULL,	NULL,	NULL,	'33',	NULL,	1,	'2025-05-25 21:41:35',	0);

-- 2025-05-25 23:10:49
