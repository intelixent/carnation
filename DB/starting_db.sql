-- Adminer 4.8.1 MySQL 8.4.3 dump

SET NAMES utf8;
SET time_zone = '+00:00';
SET foreign_key_checks = 0;
SET sql_mode = 'NO_AUTO_VALUE_ON_ZERO';

SET NAMES utf8mb4;

DROP TABLE IF EXISTS `failed_jobs`;
CREATE TABLE `failed_jobs` (
  `id` bigint unsigned NOT NULL AUTO_INCREMENT,
  `uuid` varchar(255) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NOT NULL,
  `connection` text CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NOT NULL,
  `queue` text CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NOT NULL,
  `payload` longtext CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NOT NULL,
  `exception` longtext CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NOT NULL,
  `failed_at` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  UNIQUE KEY `failed_jobs_uuid_unique` (`uuid`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;


DROP TABLE IF EXISTS `migrations`;
CREATE TABLE `migrations` (
  `id` int unsigned NOT NULL AUTO_INCREMENT,
  `migration` varchar(255) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NOT NULL,
  `batch` int NOT NULL,
  PRIMARY KEY (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;


DROP TABLE IF EXISTS `model_has_permissions`;
CREATE TABLE `model_has_permissions` (
  `permission_id` bigint unsigned NOT NULL,
  `model_type` varchar(255) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NOT NULL,
  `model_id` bigint unsigned NOT NULL,
  PRIMARY KEY (`permission_id`,`model_id`,`model_type`),
  KEY `model_has_permissions_model_id_model_type_index` (`model_id`,`model_type`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;


DROP TABLE IF EXISTS `model_has_roles`;
CREATE TABLE `model_has_roles` (
  `role_id` bigint unsigned NOT NULL,
  `model_type` varchar(255) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NOT NULL,
  `model_id` bigint unsigned NOT NULL,
  PRIMARY KEY (`role_id`,`model_id`,`model_type`),
  KEY `model_has_roles_model_id_model_type_index` (`model_id`,`model_type`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

INSERT INTO `model_has_roles` (`role_id`, `model_type`, `model_id`) VALUES
(1,	'App\\Models\\User',	1);

DROP TABLE IF EXISTS `password_reset_tokens`;
CREATE TABLE `password_reset_tokens` (
  `email` varchar(255) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NOT NULL,
  `token` varchar(255) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NOT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`email`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;


DROP TABLE IF EXISTS `password_resets`;
CREATE TABLE `password_resets` (
  `email` varchar(255) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NOT NULL,
  `token` varchar(255) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NOT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  KEY `password_resets_email_index` (`email`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;


DROP TABLE IF EXISTS `permissions`;
CREATE TABLE `permissions` (
  `id` bigint unsigned NOT NULL AUTO_INCREMENT,
  `name` varchar(255) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NOT NULL,
  `category` varchar(255) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NOT NULL,
  `guard_name` varchar(255) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NOT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `permissions_name_guard_name_unique` (`name`,`guard_name`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

INSERT INTO `permissions` (`id`, `name`, `category`, `guard_name`, `created_at`, `updated_at`) VALUES
(1,	'create-user',	'Users',	'web',	'2024-11-04 03:58:38',	'2024-11-04 03:58:38'),
(2,	'edit-user',	'Users',	'web',	'2024-11-04 03:58:38',	'2024-11-04 03:58:38'),
(3,	'view-user',	'Users',	'web',	'2024-11-04 03:58:38',	'2024-11-04 03:58:38'),
(4,	'delete-user',	'Users',	'web',	'2024-11-04 03:58:38',	'2024-11-04 03:58:38'),
(5,	'list-user',	'Users',	'web',	'2024-11-04 03:58:38',	'2024-11-04 03:58:38'),
(6,	'create-role',	'Roles',	'web',	'2024-11-04 03:58:38',	'2024-11-04 03:58:38'),
(7,	'edit-role',	'Roles',	'web',	'2024-11-04 03:58:38',	'2024-11-04 03:58:38'),
(8,	'view-role',	'Roles',	'web',	'2024-11-04 03:58:38',	'2024-11-04 03:58:38'),
(9,	'delete-role',	'Roles',	'web',	'2024-11-04 03:58:38',	'2024-11-04 03:58:38'),
(10,	'list-role',	'Roles',	'web',	'2024-11-04 03:58:38',	'2024-11-04 03:58:38'),
(11,	'create-vendor',	'Vendor',	'web',	'2024-11-04 03:58:38',	'2024-11-04 03:58:38'),
(12,	'edit-vendor',	'Vendor',	'web',	'2024-11-04 03:58:38',	'2024-11-04 03:58:38'),
(13,	'view-vendor',	'Vendor',	'web',	'2024-11-04 03:58:38',	'2024-11-04 03:58:38'),
(14,	'delete-vendor',	'Vendor',	'web',	'2024-11-04 03:58:38',	'2024-11-04 03:58:38'),
(15,	'list-vendor',	'Vendor',	'web',	'2024-11-04 03:58:38',	'2024-11-04 03:58:38'),
(16,	'status-vendor',	'Vendor',	'web',	'2024-11-04 03:58:38',	'2024-11-04 03:58:38');

DROP TABLE IF EXISTS `personal_access_tokens`;
CREATE TABLE `personal_access_tokens` (
  `id` bigint unsigned NOT NULL AUTO_INCREMENT,
  `tokenable_type` varchar(255) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NOT NULL,
  `tokenable_id` bigint unsigned NOT NULL,
  `name` varchar(255) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NOT NULL,
  `token` varchar(64) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NOT NULL,
  `abilities` text CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci,
  `last_used_at` timestamp NULL DEFAULT NULL,
  `expires_at` timestamp NULL DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `personal_access_tokens_token_unique` (`token`),
  KEY `personal_access_tokens_tokenable_type_tokenable_id_index` (`tokenable_type`,`tokenable_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

INSERT INTO `personal_access_tokens` (`id`, `tokenable_type`, `tokenable_id`, `name`, `token`, `abilities`, `last_used_at`, `expires_at`, `created_at`, `updated_at`) VALUES
(3,	'App\\Models\\User',	1,	'auth_token',	'5f4f23ffe0e7fda06ffe7a655c5b1e6770203f8557d7e08712fad1bd2054cb6e',	'[\"*\"]',	'2025-01-29 05:25:58',	NULL,	'2025-01-29 05:12:29',	'2025-01-29 05:25:58');

DROP TABLE IF EXISTS `prefix_setting`;
CREATE TABLE `prefix_setting` (
  `id` int NOT NULL AUTO_INCREMENT,
  `name` mediumtext CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NOT NULL,
  `format` mediumtext CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NOT NULL,
  `number` int NOT NULL,
  `created_at` datetime NOT NULL,
  `status` tinyint NOT NULL DEFAULT '0',
  PRIMARY KEY (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;


DROP TABLE IF EXISTS `role_has_permissions`;
CREATE TABLE `role_has_permissions` (
  `permission_id` bigint unsigned NOT NULL,
  `role_id` bigint unsigned NOT NULL,
  PRIMARY KEY (`permission_id`,`role_id`),
  KEY `role_has_permissions_role_id_foreign` (`role_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;


DROP TABLE IF EXISTS `roles`;
CREATE TABLE `roles` (
  `id` bigint unsigned NOT NULL AUTO_INCREMENT,
  `name` varchar(255) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NOT NULL,
  `guard_name` varchar(255) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NOT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `roles_name_guard_name_unique` (`name`,`guard_name`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

INSERT INTO `roles` (`id`, `name`, `guard_name`, `created_at`, `updated_at`) VALUES
(1,	'superadmin',	'web',	'2024-10-23 05:38:49',	'2024-10-23 05:38:49');

DROP TABLE IF EXISTS `state_master`;
CREATE TABLE `state_master` (
  `id` int NOT NULL AUTO_INCREMENT,
  `code` mediumtext CHARACTER SET utf8mb4 COLLATE utf8mb4_0900_ai_ci NOT NULL,
  `name` mediumtext CHARACTER SET utf8mb4 COLLATE utf8mb4_0900_ai_ci NOT NULL,
  `status` int NOT NULL DEFAULT '0',
  PRIMARY KEY (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci;

INSERT INTO `state_master` (`id`, `code`, `name`, `status`) VALUES
(1,	'01',	'Jammu & Kashmir',	0),
(2,	'02',	'Himachal Pradesh',	0),
(3,	'03',	'Punjab',	0),
(4,	'04',	'Chandigarh',	0),
(5,	'05',	'Uttarakhand',	0),
(6,	'06',	'Haryana',	0),
(7,	'07',	'Delhi',	0),
(8,	'08',	'Rajasthan',	0),
(9,	'09',	'Uttar Pradesh',	0),
(10,	'10',	'Bihar',	0),
(11,	'11',	'Sikkim',	0),
(12,	'12',	'Arunachal Pradesh',	0),
(13,	'13',	'Nagaland',	0),
(14,	'14',	'Manipur',	0),
(15,	'15',	'Mizoram',	0),
(16,	'16',	'Tripura',	0),
(17,	'17',	'Meghalaya',	0),
(18,	'18',	'Assam',	0),
(19,	'19',	'West Bengal',	0),
(20,	'20',	'Jharkhand',	0),
(21,	'21',	'Odisha',	0),
(22,	'22',	'Chhattisgarh',	0),
(23,	'23',	'Madhya Pradesh',	0),
(24,	'24',	'Gujarat',	0),
(25,	'25',	'Daman & Diu',	0),
(26,	'26',	'Dadra & Nagar Haveli',	0),
(27,	'27',	'Maharashtra',	0),
(28,	'28',	'Andhra Pradesh (Old)',	0),
(29,	'29',	'Karnataka',	0),
(30,	'30',	'Goa',	0),
(31,	'31',	'Lakshadweep',	0),
(32,	'32',	'Kerala',	0),
(33,	'33',	'Tamil Nadu',	0),
(34,	'34',	'Puducherry',	0),
(35,	'35',	'Andaman & Nicobar Islands',	0),
(36,	'36',	'Telangana',	0),
(37,	'37',	'Andhra Pradesh (New)',	0),
(38,	'38',	'Ladakh',	0);

DROP TABLE IF EXISTS `users`;
CREATE TABLE `users` (
  `id` bigint unsigned NOT NULL AUTO_INCREMENT,
  `first_name` varchar(255) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NOT NULL,
  `last_name` varchar(255) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NOT NULL,
  `username` varchar(255) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `mobile` mediumtext COLLATE utf8mb4_unicode_ci,
  `email` varchar(255) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NOT NULL,
  `address` varchar(255) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `designation` varchar(255) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `password` varchar(255) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NOT NULL,
  `email_verified_at` timestamp NULL DEFAULT NULL,
  `remember_token` varchar(100) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `created_at` datetime DEFAULT NULL,
  `updated_at` datetime DEFAULT NULL,
  `deleted_at` datetime DEFAULT NULL,
  `status` tinyint DEFAULT '0',
  PRIMARY KEY (`id`),
  UNIQUE KEY `users_email_unique` (`email`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

INSERT INTO `users` (`id`, `first_name`, `last_name`, `username`, `mobile`, `email`, `address`, `designation`, `password`, `email_verified_at`, `remember_token`, `created_at`, `updated_at`, `deleted_at`, `status`) VALUES
(1,	'Super ',	'Admin',	'super',	NULL,	'superadmin@intelixent.com',	NULL,	NULL,	'$2y$10$JIheAzy1xn.OW3stv1cWM.CVTRyDVNjF7R0dvfHx7A/ftE/o4K7Cm',	NULL,	NULL,	'2024-10-23 11:32:40',	'2024-10-23 11:32:40',	NULL,	0);

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


-- 2025-04-30 11:21:59
