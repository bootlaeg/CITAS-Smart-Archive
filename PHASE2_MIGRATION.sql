-- ============================================================================
-- PHASE 2 Database Migration: Journal Conversion System
-- Date: April 16, 2026
-- ============================================================================

-- Step 1: Add 'instructor' role to users table
-- This enables role-based access control for the new "Raw Document" tab
ALTER TABLE users MODIFY user_role ENUM('student','instructor','admin') DEFAULT 'student';

-- Step 2: Add journal conversion columns to thesis table
-- These columns track the journal-converted version of documents

ALTER TABLE thesis ADD COLUMN journal_file_path VARCHAR(255) 
DEFAULT NULL COMMENT 'Path to converted journal-format document';

ALTER TABLE thesis ADD COLUMN is_journal_converted BOOLEAN 
DEFAULT FALSE COMMENT 'Whether journal version has been successfully created';

ALTER TABLE thesis ADD COLUMN journal_conversion_status ENUM('pending','processing','completed','failed') 
DEFAULT 'pending' COMMENT 'Status of journal conversion process';

ALTER TABLE thesis ADD COLUMN journal_page_count INT 
DEFAULT NULL COMMENT 'Page count of journal-converted version (10-20 pages)';

ALTER TABLE thesis ADD COLUMN journal_converted_at TIMESTAMP 
NULL DEFAULT NULL COMMENT 'Timestamp when journal conversion was completed';

ALTER TABLE thesis ADD COLUMN journal_imrad_sections LONGTEXT 
DEFAULT NULL COMMENT 'JSON object storing IMRaD sections: {introduction, methods, results, discussion, conclusions}';

-- ============================================================================
-- Verification Query - Run this after migration to confirm all columns exist
-- ============================================================================
/*
SELECT COLUMN_NAME, COLUMN_TYPE, COLUMN_DEFAULT 
FROM INFORMATION_SCHEMA.COLUMNS 
WHERE TABLE_NAME = 'thesis' 
AND TABLE_SCHEMA = DATABASE()
AND COLUMN_NAME IN (
  'journal_file_path',
  'is_journal_converted', 
  'journal_conversion_status',
  'journal_page_count',
  'journal_converted_at',
  'journal_imrad_sections'
)
ORDER BY ORDINAL_POSITION;

-- Should return 6 rows with all journal-related columns
*/

-- ============================================================================
-- Migration Complete
-- ============================================================================
