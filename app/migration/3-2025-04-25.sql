CREATE TABLE `analytics_campaign_new` (
    `id` int(11) NOT NULL AUTO_INCREMENT,
    `campaign_id` int(11) NOT NULL,
    `interaction_type` enum('clicked','delivered','failed','sent') CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NOT NULL,
    `hour` datetime NOT NULL,
    `count` int(11) NOT NULL DEFAULT 0,
    `created_at` timestamp NULL DEFAULT current_timestamp(),
    `updated_at` timestamp NULL DEFAULT current_timestamp() ON UPDATE current_timestamp(),
    PRIMARY KEY (`id`) USING BTREE,
    UNIQUE KEY `idx_campaign_hour_type` (`campaign_id`, `hour`, `interaction_type`),
    INDEX `idx_campaign_interaction`(`campaign_id`, `interaction_type`) USING BTREE,
    INDEX `idx_interaction_type`(`interaction_type`) USING BTREE,
    INDEX `idx_hour` (`hour`),
    CONSTRAINT `analytics_campaign_new_ibfk_1` FOREIGN KEY (`campaign_id`) REFERENCES `campaigns` (`id`) ON DELETE CASCADE ON UPDATE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

INSERT INTO analytics_campaign_new (campaign_id, interaction_type, hour, count)
SELECT 
    campaign_id,
    interaction_type,
    DATE_FORMAT(created_at, '%Y-%m-%d %H:00:00') as hour,
    COUNT(*) as count
FROM 
    analytics_campaign
GROUP BY 
    campaign_id, interaction_type, DATE_FORMAT(created_at, '%Y-%m-%d %H:00:00');

RENAME TABLE analytics_campaign TO analytics_campaign_old;
RENAME TABLE analytics_campaign_new TO analytics_campaign;