ALTER TABLE subscribers ADD INDEX idx_status_last_active (status, last_active);
ALTER TABLE campaigns ADD INDEX idx_status_send_at (status, send_at);
ALTER TABLE analytics_campaign ADD INDEX idx_created_at (created_at);