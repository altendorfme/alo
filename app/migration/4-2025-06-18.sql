ALTER TABLE segment_goals 
ADD INDEX IF NOT EXISTS idx_segment_value_optimized (segment_id, value);

ANALYZE TABLE segments, segment_goals;