-- ============================================================================
-- CITAS Smart Archive - Chatbot System Database Fix
-- ============================================================================
-- This script fixes database integrity issues preventing the chatbot from working
-- Run this in phpMyAdmin or your database client
-- ============================================================================

-- Step 1: Clean up invalid sessions (id = 0)
DELETE FROM chatbot_messages WHERE session_id = 0 OR session_id IS NULL;
DELETE FROM chatbot_sessions WHERE id = 0 OR id IS NULL;
DELETE FROM chatbot_access_requests WHERE id = 0 OR id IS NULL;

-- Step 2: Reset AUTO_INCREMENT to safe values
-- After deleting invalid records (id = 0), AUTO_INCREMENT is automatically recalculated
-- We'll manually set it to a safe value higher than any existing valid ID
-- This ensures new records will get valid IDs (no more id = 0)
ALTER TABLE chatbot_sessions AUTO_INCREMENT = 100;
ALTER TABLE chatbot_messages AUTO_INCREMENT = 100;
ALTER TABLE chatbot_access_requests AUTO_INCREMENT = 100;

-- Step 3: Remove any orphaned messages (messages whose session doesn't exist)
DELETE FROM chatbot_messages 
WHERE session_id NOT IN (SELECT id FROM chatbot_sessions) 
AND session_id IS NOT NULL;

-- Step 4: Verify all tables have proper structure
-- Make sure no invalid IDs remain
SELECT 'chatbot_sessions' as table_name, COUNT(*) as total_records, 
       SUM(IF(id <= 0, 1, 0)) as invalid_ids 
FROM chatbot_sessions

UNION ALL
y
SELECT 'chatbot_messages' as table_name, COUNT(*) as total_records,
       SUM(IF(id <= 0, 1, 0)) as invalid_ids
FROM chatbot_messages

UNION ALL

SELECT 'chatbot_access_requests' as table_name, COUNT(*) as total_records,
       SUM(IF(id <= 0, 1, 0)) as invalid_ids
FROM chatbot_access_requests;

-- Step 5: Verify foreign key relationships
SELECT 'Orphaned messages' as issue, COUNT(*) as count
FROM chatbot_messages cm
WHERE NOT EXISTS (SELECT 1 FROM chatbot_sessions cs WHERE cs.id = cm.session_id);

-- ============================================================================
-- Verification Complete - All chatbot tables should now be healthy
-- ============================================================================
