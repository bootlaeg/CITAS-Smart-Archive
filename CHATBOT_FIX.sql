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

-- Step 2: Ensure AUTO_INCREMENT is properly set
-- Get max ID and set next AUTO_INCREMENT value (MariaDB compatible)
SELECT @max_sessions := COALESCE(MAX(id), 0) FROM chatbot_sessions;
SELECT @max_messages := COALESCE(MAX(id), 0) FROM chatbot_messages;
SELECT @max_access := COALESCE(MAX(id), 0) FROM chatbot_access_requests;

-- Calculate next values (use IF to ensure valid number)
SET @next_sessions = IF(@max_sessions > 0, @max_sessions + 1, 1);
SET @next_messages = IF(@max_messages > 0, @max_messages + 1, 1);
SET @next_access = IF(@max_access > 0, @max_access + 1, 1);

-- Reset AUTO_INCREMENT counters
ALTER TABLE chatbot_sessions AUTO_INCREMENT = @next_sessions;
ALTER TABLE chatbot_messages AUTO_INCREMENT = @next_messages;
ALTER TABLE chatbot_access_requests AUTO_INCREMENT = @next_access;

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
