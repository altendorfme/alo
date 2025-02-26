/*
 Navicat Premium Data Transfer

 Source Server         : Localhost
 Source Server Type    : MariaDB
 Source Server Version : 110202 (11.2.2-MariaDB-log)
 Source Host           : localhost:3306
 Source Schema         : pushbase

 Target Server Type    : MariaDB
 Target Server Version : 110202 (11.2.2-MariaDB-log)
 File Encoding         : 65001

 Date: 12/02/2025 17:01:57
*/

SET NAMES utf8mb4;
SET FOREIGN_KEY_CHECKS = 0;

-- ----------------------------
-- Table structure for analytics_campaign
-- ----------------------------
DROP TABLE IF EXISTS `analytics_campaign`;
CREATE TABLE `analytics_campaign`  (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `campaign_id` int(11) NOT NULL,
  `subscriber_id` int(11) NULL DEFAULT NULL,
  `interaction_type` enum('clicked','delivered','failed','sent') CHARACTER SET latin1 COLLATE latin1_swedish_ci NOT NULL,
  `created_at` timestamp NULL DEFAULT current_timestamp(),
  PRIMARY KEY (`id`) USING BTREE,
  INDEX `idx_campaign_interaction`(`campaign_id`, `interaction_type`) USING BTREE,
  INDEX `idx_user_interaction`(`interaction_type`) USING BTREE,
  INDEX `analytics_campaign_ibfk_3`(`subscriber_id`) USING BTREE,
  CONSTRAINT `analytics_campaign_ibfk_1` FOREIGN KEY (`campaign_id`) REFERENCES `campaigns` (`id`) ON DELETE CASCADE ON UPDATE CASCADE,
  CONSTRAINT `analytics_campaign_ibfk_3` FOREIGN KEY (`subscriber_id`) REFERENCES `subscribers` (`id`) ON DELETE SET NULL ON UPDATE CASCADE
) ENGINE = InnoDB AUTO_INCREMENT = 1 CHARACTER SET = latin1 COLLATE = latin1_swedish_ci ROW_FORMAT = Dynamic;

-- ----------------------------
-- Table structure for analytics_campaigns
-- ----------------------------
DROP TABLE IF EXISTS `analytics_campaigns`;
CREATE TABLE `analytics_campaigns`  (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `campaign_id` int(11) NOT NULL,
  `error_count` int(10) UNSIGNED NOT NULL,
  `successfully_count` int(10) UNSIGNED NOT NULL,
  PRIMARY KEY (`id`) USING BTREE,
  INDEX `idx_campaign_sent`(`campaign_id`) USING BTREE,
  CONSTRAINT `analytics_campaigns_ibfk_1` FOREIGN KEY (`campaign_id`) REFERENCES `campaigns` (`id`) ON DELETE CASCADE ON UPDATE CASCADE
) ENGINE = InnoDB AUTO_INCREMENT = 1 CHARACTER SET = latin1 COLLATE = latin1_swedish_ci ROW_FORMAT = Dynamic;

-- ----------------------------
-- Table structure for analytics_segments
-- ----------------------------
DROP TABLE IF EXISTS `analytics_segments`;
CREATE TABLE `analytics_segments`  (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `segment_id` int(11) NOT NULL,
  `segment_value` varchar(255) CHARACTER SET latin1 COLLATE latin1_swedish_ci NOT NULL,
  `count` bigint(20) UNSIGNED NOT NULL DEFAULT 1,
  `last_occurred_at` timestamp NULL DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  PRIMARY KEY (`id`) USING BTREE,
  UNIQUE INDEX `uk_segment_value`(`segment_id`, `segment_value`) USING BTREE,
  INDEX `idx_occurrence_count`(`count`) USING BTREE
) ENGINE = InnoDB AUTO_INCREMENT = 1 CHARACTER SET = latin1 COLLATE = latin1_swedish_ci ROW_FORMAT = Dynamic;

-- ----------------------------
-- Table structure for analytics_subscribers
-- ----------------------------
DROP TABLE IF EXISTS `analytics_subscribers`;
CREATE TABLE `analytics_subscribers`  (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `subscriber_id` int(11) NOT NULL,
  `status` enum('active','inactive','unsubscribed') CHARACTER SET latin1 COLLATE latin1_swedish_ci NOT NULL,
  `created_at` timestamp NULL DEFAULT current_timestamp(),
  PRIMARY KEY (`id`) USING BTREE,
  INDEX `idx_created_at`(`subscriber_id`, `created_at`) USING BTREE,
  CONSTRAINT `analytics_subscribers_ibfk_1` FOREIGN KEY (`subscriber_id`) REFERENCES `subscribers` (`id`) ON DELETE CASCADE ON UPDATE CASCADE
) ENGINE = InnoDB AUTO_INCREMENT = 1 CHARACTER SET = latin1 COLLATE = latin1_swedish_ci ROW_FORMAT = Dynamic;

-- ----------------------------
-- Table structure for campaigns
-- ----------------------------
DROP TABLE IF EXISTS `campaigns`;
CREATE TABLE `campaigns`  (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `uuid` varchar(36) CHARACTER SET latin1 COLLATE latin1_swedish_ci NOT NULL,
  `name` varchar(255) CHARACTER SET latin1 COLLATE latin1_swedish_ci NOT NULL,
  `push_title` text CHARACTER SET latin1 COLLATE latin1_swedish_ci NOT NULL,
  `push_body` text CHARACTER SET latin1 COLLATE latin1_swedish_ci NOT NULL,
  `push_icon` varchar(510) CHARACTER SET latin1 COLLATE latin1_swedish_ci NOT NULL,
  `push_image` varchar(510) CHARACTER SET latin1 COLLATE latin1_swedish_ci NOT NULL,
  `push_badge` varchar(510) CHARACTER SET latin1 COLLATE latin1_swedish_ci NULL DEFAULT NULL,
  `push_requireInteraction` tinyint(1) NULL DEFAULT NULL,
  `push_url` varchar(510) CHARACTER SET latin1 COLLATE latin1_swedish_ci NULL DEFAULT NULL,
  `push_renotify` tinyint(1) NULL DEFAULT 0,
  `push_silent` tinyint(1) NULL DEFAULT 0,
  `status` enum('draft','scheduled','sent','sending','cancelled','queuing') CHARACTER SET latin1 COLLATE latin1_swedish_ci NULL DEFAULT 'draft',
  `send_at` datetime NULL DEFAULT NULL,
  `total_recipients` int(11) NULL DEFAULT NULL,
  `started_at` datetime NULL DEFAULT NULL,
  `ended_at` datetime NULL DEFAULT NULL,
  `created_by` int(11) NULL DEFAULT NULL,
  `created_at` datetime NULL DEFAULT current_timestamp(),
  `updated_by` int(11) NULL DEFAULT NULL,
  `updated_at` datetime NULL DEFAULT current_timestamp() ON UPDATE CURRENT_TIMESTAMP,
  `segments` longtext CHARACTER SET utf8mb4 COLLATE utf8mb4_bin NULL DEFAULT NULL CHECK (json_valid(`segments`)),
  PRIMARY KEY (`id`) USING BTREE,
  INDEX `idx_status`(`status`) USING BTREE,
  INDEX `idx_created_by`(`created_by`) USING BTREE,
  INDEX `idx_send_at`(`send_at`) USING BTREE,
  CONSTRAINT `campaigns_ibfk_1` FOREIGN KEY (`created_by`) REFERENCES `users` (`id`) ON DELETE SET NULL ON UPDATE CASCADE
) ENGINE = InnoDB AUTO_INCREMENT = 1 CHARACTER SET = latin1 COLLATE = latin1_swedish_ci ROW_FORMAT = Dynamic;

-- ----------------------------
-- Table structure for segment_goals
-- ----------------------------
DROP TABLE IF EXISTS `segment_goals`;
CREATE TABLE `segment_goals`  (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `segment_id` int(11) NOT NULL,
  `subscriber_id` int(11) NOT NULL,
  `value` text CHARACTER SET latin1 COLLATE latin1_swedish_ci NOT NULL,
  `created_at` datetime NULL DEFAULT current_timestamp(),
  PRIMARY KEY (`id`) USING BTREE,
  INDEX `subscriber_id`(`subscriber_id`) USING BTREE,
  INDEX `idx_segment_subscriber`(`segment_id`, `subscriber_id`) USING BTREE,
  CONSTRAINT `segment_goals_ibfk_1` FOREIGN KEY (`segment_id`) REFERENCES `segments` (`id`) ON DELETE CASCADE ON UPDATE CASCADE,
  CONSTRAINT `segment_goals_ibfk_2` FOREIGN KEY (`subscriber_id`) REFERENCES `subscribers` (`id`) ON DELETE CASCADE ON UPDATE CASCADE
) ENGINE = InnoDB AUTO_INCREMENT = 1 CHARACTER SET = latin1 COLLATE = latin1_swedish_ci ROW_FORMAT = Dynamic;

-- ----------------------------
-- Table structure for segments
-- ----------------------------
DROP TABLE IF EXISTS `segments`;
CREATE TABLE `segments`  (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `name` varchar(255) CHARACTER SET latin1 COLLATE latin1_swedish_ci NOT NULL,
  `description` text CHARACTER SET latin1 COLLATE latin1_swedish_ci NULL DEFAULT NULL,
  `created_at` datetime NULL DEFAULT current_timestamp(),
  `updated_at` datetime NULL DEFAULT current_timestamp() ON UPDATE CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`) USING BTREE,
  UNIQUE INDEX `uk_name`(`name`) USING BTREE
) ENGINE = InnoDB AUTO_INCREMENT = 1 CHARACTER SET = latin1 COLLATE = latin1_swedish_ci ROW_FORMAT = Dynamic;

-- ----------------------------
-- Table structure for subscribers
-- ----------------------------
DROP TABLE IF EXISTS `subscribers`;
CREATE TABLE `subscribers`  (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `uuid` varchar(36) CHARACTER SET latin1 COLLATE latin1_swedish_ci NOT NULL,
  `endpoint` varchar(510) CHARACTER SET latin1 COLLATE latin1_swedish_ci NOT NULL,
  `p256dh` text CHARACTER SET latin1 COLLATE latin1_swedish_ci NOT NULL,
  `authKey` varchar(255) CHARACTER SET latin1 COLLATE latin1_swedish_ci NOT NULL,
  `expirationTime` datetime NULL DEFAULT NULL,
  `subscribed_at` datetime NULL DEFAULT current_timestamp(),
  `unsubscribed_at` datetime NULL DEFAULT NULL,
  `error_count` int(11) NULL DEFAULT 0,
  `last_active` datetime NULL DEFAULT current_timestamp(),
  `status` enum('active','inactive','unsubscribed') CHARACTER SET latin1 COLLATE latin1_swedish_ci NULL DEFAULT 'active',
  PRIMARY KEY (`id`) USING BTREE,
  UNIQUE INDEX `uk_uuid`(`uuid`) USING BTREE,
  INDEX `idx_status`(`status`) USING BTREE,
  INDEX `idx_last_active`(`last_active`) USING BTREE
) ENGINE = InnoDB AUTO_INCREMENT = 1 CHARACTER SET = latin1 COLLATE = latin1_swedish_ci ROW_FORMAT = Dynamic;

-- ----------------------------
-- Table structure for user_sessions
-- ----------------------------
DROP TABLE IF EXISTS `user_sessions`;
CREATE TABLE `user_sessions`  (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `user_id` int(11) NOT NULL,
  `session_code` varchar(255) CHARACTER SET latin1 COLLATE latin1_swedish_ci NOT NULL,
  `created_at` datetime NULL DEFAULT current_timestamp(),
  `expires_at` datetime NULL DEFAULT NULL,
  `ip_address` varchar(45) CHARACTER SET latin1 COLLATE latin1_swedish_ci NOT NULL,
  `status` enum('active','expired','revoked') CHARACTER SET latin1 COLLATE latin1_swedish_ci NULL DEFAULT 'active',
  PRIMARY KEY (`id`) USING BTREE,
  INDEX `idx_user_status`(`user_id`, `status`) USING BTREE,
  INDEX `idx_ip_address`(`ip_address`) USING BTREE,
  CONSTRAINT `user_sessions_ibfk_1` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`) ON DELETE CASCADE ON UPDATE CASCADE
) ENGINE = InnoDB AUTO_INCREMENT = 1 CHARACTER SET = latin1 COLLATE = latin1_swedish_ci ROW_FORMAT = Dynamic;

-- ----------------------------
-- Records of user_sessions
-- ----------------------------

-- ----------------------------
-- Table structure for users
-- ----------------------------
DROP TABLE IF EXISTS `users`;
CREATE TABLE `users`  (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `email` varchar(255) CHARACTER SET latin1 COLLATE latin1_swedish_ci NOT NULL,
  `password` varchar(255) CHARACTER SET latin1 COLLATE latin1_swedish_ci NOT NULL,
  `created_at` datetime NOT NULL DEFAULT current_timestamp(),
  `last_login` datetime NULL DEFAULT NULL,
  `status` enum('active','inactive') CHARACTER SET latin1 COLLATE latin1_swedish_ci NOT NULL DEFAULT 'active',
  `role` enum('admin','editor') CHARACTER SET latin1 COLLATE latin1_swedish_ci NOT NULL DEFAULT 'editor',
  `password_reset_token` varchar(255) CHARACTER SET latin1 COLLATE latin1_swedish_ci NULL DEFAULT NULL,
  `password_reset_expires` datetime NULL DEFAULT NULL,
  `api_key` varchar(255) CHARACTER SET latin1 COLLATE latin1_swedish_ci NULL DEFAULT NULL,
  PRIMARY KEY (`id`) USING BTREE,
  UNIQUE INDEX `uk_email`(`email`) USING BTREE,
  INDEX `idx_status`(`status`) USING BTREE
) ENGINE = InnoDB AUTO_INCREMENT = 1 CHARACTER SET = latin1 COLLATE = latin1_swedish_ci ROW_FORMAT = Dynamic;

-- ----------------------------
-- Records of users
-- ----------------------------

SET FOREIGN_KEY_CHECKS = 1;
