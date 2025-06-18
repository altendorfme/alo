ALTER TABLE segment_goals
ADD INDEX IF NOT EXISTS idx_segment_value_optimized (segment_id, value(191));

ANALYZE TABLE segments, segment_goals;