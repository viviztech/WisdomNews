CREATE DATABASE IF NOT EXISTS `wisdomnews`
  CHARACTER SET utf8mb4
  COLLATE utf8mb4_unicode_ci;

USE `wisdomnews`;

CREATE TABLE IF NOT EXISTS `admins` (
  `id` BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
  `name` VARCHAR(120) NOT NULL,
  `username` VARCHAR(80) NOT NULL,
  `email` VARCHAR(190) NOT NULL,
  `password` VARCHAR(255) NOT NULL,
  `created_at` DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `updated_at` DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  UNIQUE KEY `admins_username_unique` (`username`),
  UNIQUE KEY `admins_email_unique` (`email`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS `categories` (
  `id` BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
  `wordpress_id` BIGINT UNSIGNED NULL,
  `parent_id` BIGINT UNSIGNED NULL,
  `name` VARCHAR(160) NOT NULL,
  `slug` VARCHAR(190) NOT NULL,
  `description` TEXT NULL,
  `status` ENUM('active','inactive') NOT NULL DEFAULT 'active',
  `sort_order` INT NOT NULL DEFAULT 0,
  `created_at` DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `updated_at` DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  UNIQUE KEY `categories_slug_unique` (`slug`),
  UNIQUE KEY `categories_wordpress_id_unique` (`wordpress_id`),
  KEY `categories_parent_id_index` (`parent_id`),
  KEY `categories_status_sort_index` (`status`, `sort_order`),
  CONSTRAINT `categories_parent_id_foreign` FOREIGN KEY (`parent_id`) REFERENCES `categories` (`id`) ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS `posts` (
  `id` BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
  `wordpress_id` BIGINT UNSIGNED NULL,
  `category_id` BIGINT UNSIGNED NULL,
  `title` VARCHAR(255) NOT NULL,
  `slug` VARCHAR(190) NOT NULL,
  `short_description` TEXT NULL,
  `content` LONGTEXT NOT NULL,
  `featured_image` VARCHAR(500) NULL,
  `image_alt` VARCHAR(255) NULL,
  `status` ENUM('draft','published') NOT NULL DEFAULT 'draft',
  `views` BIGINT UNSIGNED NOT NULL DEFAULT 0,
  `published_at` DATETIME NULL,
  `created_at` DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `updated_at` DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  UNIQUE KEY `posts_slug_unique` (`slug`),
  UNIQUE KEY `posts_wordpress_id_unique` (`wordpress_id`),
  KEY `posts_category_id_index` (`category_id`),
  KEY `posts_status_published_index` (`status`, `published_at`),
  CONSTRAINT `posts_category_id_foreign` FOREIGN KEY (`category_id`) REFERENCES `categories` (`id`) ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS `settings` (
  `id` BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
  `setting_key` VARCHAR(120) NOT NULL,
  `setting_value` TEXT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `settings_key_unique` (`setting_key`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

INSERT INTO `settings` (`setting_key`, `setting_value`) VALUES
  ('site_name', 'Wisdom News'),
  ('site_logo', ''),
  ('favicon', ''),
  ('contact_email', ''),
  ('facebook_url', ''),
  ('youtube_url', ''),
  ('instagram_url', ''),
  ('footer_text', 'உண்மையை அறிந்து, அறிவுடன் முன்னேறுவோம்.')
ON DUPLICATE KEY UPDATE `setting_key` = VALUES(`setting_key`);

