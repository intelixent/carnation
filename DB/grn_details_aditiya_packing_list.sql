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
  `created_at` datetime NOT NULL,
  `created_by` int NOT NULL,
  `status` tinyint NOT NULL DEFAULT '0',
  PRIMARY KEY (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci;


DROP TABLE IF EXISTS `packing_list_masters`;
CREATE TABLE `packing_list_masters` (
  `id` int NOT NULL AUTO_INCREMENT,
  `po_id` mediumtext,
  `pack_ref_no` mediumtext CHARACTER SET utf8mb4 COLLATE utf8mb4_0900_ai_ci,
  `vendor_id` mediumtext,
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
  `created_at` datetime NOT NULL,
  `created_by` int NOT NULL,
  `updated_at` datetime DEFAULT NULL,
  `deleted_at` datetime DEFAULT NULL,
  `status` tinyint NOT NULL DEFAULT '0',
  PRIMARY KEY (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci;


-- 2025-09-07 15:17:29

SET NAMES utf8mb4;

INSERT INTO `vendor_master` (`id`, `name`, `mobile`, `email`, `billing_legal_name`, `billing_address_1`, `billing_address_2`, `billing_city_town_village`, `billing_pincode`, `billing_gst_no`, `billing_pan_no`, `billing_gst_type`, `billing_state_id`, `shipping_legal_name`, `shipping_address_1`, `shipping_address_2`, `shipping_city_town_village`, `shipping_pincode`, `shipping_gst_no`, `shipping_pan_no`, `shipping_place_supply`, `shipping_state_id`, `shipping_distance`, `excess`, `shortage`, `discount`, `payment_terms`, `extraction_no`, `custom_field_no`, `notes`, `created_by`, `created_at`, `status`) VALUES
(7,	'Aditiya',	'9360041255',	'ac01@carnationworld.com',	NULL,	NULL,	NULL,	NULL,	NULL,	NULL,	NULL,	NULL,	NULL,	NULL,	NULL,	NULL,	NULL,	NULL,	NULL,	NULL,	NULL,	NULL,	NULL,	'2.9',	'2',	NULL,	NULL,	'5',	NULL,	NULL,	1,	'2025-05-25 21:41:35',	0);
