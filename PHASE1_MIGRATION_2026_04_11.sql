-- Phase 1: Title Hearing Requirements Implementation
-- Date: April 11, 2026
-- Purpose: Add journal type validation and page count tracking

-- 1. Add document_type column to thesis table
ALTER TABLE thesis ADD COLUMN document_type ENUM('journal','book','thesis','report') DEFAULT 'thesis' AFTER file_type;

-- 2. Add page_count column to thesis table
ALTER TABLE thesis ADD COLUMN page_count INT DEFAULT NULL COMMENT 'Number of pages extracted from uploaded file' AFTER document_type;

-- Verification query:
-- SELECT COLUMN_NAME, COLUMN_TYPE FROM INFORMATION_SCHEMA.COLUMNS 
-- WHERE TABLE_NAME = 'thesis' AND (COLUMN_NAME = 'document_type' OR COLUMN_NAME = 'page_count');
