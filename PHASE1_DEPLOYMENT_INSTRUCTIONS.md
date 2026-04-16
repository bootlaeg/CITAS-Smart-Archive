# PHASE 1 Deployment Instructions

## ✅ What's been prepared:
- CHATBOT_FIX.sql (fixes database integrity)
- PHASE1_MIGRATION_2026_04_11.sql (adds new columns)

## 📋 How to Deploy to Hostinger:

### Option 1: Using phpMyAdmin (Recommended)
1. Go to Hostinger Control Panel → Databases → phpMyAdmin
2. Select database: `u965322812_thesis_db`
3. Open "SQL" tab
4. Copy & paste the SQL from CHATBOT_FIX.sql below
5. Click "Execute" button
6. Copy & paste the SQL from PHASE1_MIGRATION_2026_04_11.sql below  
7. Click "Execute" button

### Option 2: Using Hostinger's Command Line
1. SSH into your server
2. Login to MySQL: `mysql -u u965322812_CITAS_Smart -p`
3. Select database: `use u965322812_thesis_db;`
4. Paste commands from CHATBOT_FIX.sql
5. Paste commands from PHASE1_MIGRATION_2026_04_11.sql

---

## 📄 SQL to Execute (in order):

### STEP 1: CHATBOT_FIX.sql
```sql
DELETE FROM chatbot_messages WHERE session_id = 0 OR session_id IS NULL;
DELETE FROM chatbot_sessions WHERE id = 0 OR id IS NULL;
DELETE FROM chatbot_access_requests WHERE id = 0 OR id IS NULL;
ALTER TABLE chatbot_sessions AUTO_INCREMENT = 100;
ALTER TABLE chatbot_messages AUTO_INCREMENT = 100;
ALTER TABLE chatbot_access_requests AUTO_INCREMENT = 100;
DELETE FROM chatbot_messages WHERE session_id NOT IN (SELECT id FROM chatbot_sessions) AND session_id IS NOT NULL;
```

### STEP 2: PHASE1_MIGRATION_2026_04_11.sql
```sql
ALTER TABLE thesis ADD COLUMN document_type ENUM('journal','book','thesis','report') DEFAULT 'thesis' AFTER file_type;
ALTER TABLE thesis ADD COLUMN page_count INT DEFAULT NULL COMMENT 'Number of pages extracted from uploaded file' AFTER document_type;
```

---

## ✅ Verification Query (Run this after deployment to confirm):
```sql
SELECT COLUMN_NAME, COLUMN_TYPE 
FROM INFORMATION_SCHEMA.COLUMNS 
WHERE TABLE_NAME = 'thesis' 
AND (COLUMN_NAME = 'document_type' OR COLUMN_NAME = 'page_count');
```

Should return 2 rows with the new columns.

---

## 🎯 After Deployment:
Once you've executed both SQL blocks, PHASE 1 is complete!
Next: PHASE 2 - Implement validation rules for document types
