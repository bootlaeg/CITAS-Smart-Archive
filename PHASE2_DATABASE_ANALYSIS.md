# Database Analysis for Phase 2 Implementation

## Current Database Structure Summary

### 1. Users Table ⚠️
**Current Roles:** `admin`, `student`
**Missing:** `instructor` role (needs to be added!)

**Issue:** Phase 2 requires Instructor role for "Raw Document" tab visibility
**Solution:** Add 'instructor' to user_role ENUM in users table

```sql
ALTER TABLE users MODIFY user_role ENUM('student','instructor','admin') DEFAULT 'student';
```

### 2. Thesis Table ✅
**Current Columns:**
- `id` - Primary key
- `title`, `author`, `course`, `year` - Document metadata
- `abstract` - Abstract text
- `file_path` - Original file path (e.g., uploads/thesis_files/thesis_xxx.pdf)
- `file_type` - ENUM('pdf','doc','docx')
- `document_type` - ENUM('journal','book','thesis','report') ✅ Already exists!
- `page_count` - INT ✅ Already exists!
- `file_size`, `views`, `status`, `created_at`, `updated_at`

**Status:** Phase 1 columns already added! ✅

### 3. Gap for Phase 2: Journal Conversion Storage ⚠️
**Need to track:**
- Original file: file_path (already exists)
- Journal version file: NEW column needed
- Conversion status: NEW column needed
- Conversion timestamp: NEW column needed

**Proposed new columns:**
```sql
ALTER TABLE thesis ADD COLUMN journal_file_path VARCHAR(255) DEFAULT NULL COMMENT 'Path to journal-converted version';
ALTER TABLE thesis ADD COLUMN is_journal_converted BOOLEAN DEFAULT FALSE COMMENT 'Whether journal version has been created';
ALTER TABLE thesis ADD COLUMN journal_conversion_status ENUM('pending','processing','completed','failed') DEFAULT 'pending';
ALTER TABLE thesis ADD COLUMN journal_page_count INT DEFAULT NULL COMMENT 'Page count of journal version';
ALTER TABLE thesis ADD COLUMN journal_converted_at TIMESTAMP NULL DEFAULT NULL;
```

### 4. Existing Data Sample
**Important Files in Database:**
- `thesis_67` (NeuroGuard): 
  - Original: `uploads/thesis_files/thesis_69dae2eea4e4d_1775952622.pdf`
  - Pages: 58 ✅ (page_count already populated!)
  - Status: approved ✅
  - This is your test case!

### 5. Current View_Thesis Tabs (view_thesis.php)
Current implementation has 4 tabs:
1. Overview
2. Thesis Info
3. Citation & Reference
4. Related Thesis

**Phase 2 Additions:**
- Overview: Will show journal version (converted)
- NEW: Raw Document tab (Instructor + Admin only)
- Abstract: Remains in Overview unchanged

### 6. File Organization
```
uploads/thesis_files/
  ├── thesis_69dae2eea4e4d_1775952622.pdf (original - 58 pages)
  └── thesis_69dae2eea4e4d_journal_CONVERTED.pdf (NEW - journal version - 10-20 pages)
```

### 7. Role-Based Access (NEW)
Current system: student, admin
Phase 2 needs: student, instructor, admin

**Permissions:**
- **Student**: Sees 4 tabs (Overview with journal, Thesis Info, Citation, Related)
- **Instructor**: Sees 5 tabs (all + new Raw Document)
- **Admin**: Sees 5 tabs (all + new Raw Document)

## Database Changes Required for Phase 2

### MUST DO (Required):
1. ✅ Add 'instructor' role to users.user_role enum
2. ✅ Add journal_file_path, is_journal_converted columns to thesis
3. ✅ Add journal_conversion_status, journal_page_count columns
4. ✅ Add journal_converted_at timestamp

### NICE-TO-HAVE (Optional):
- Add archive_original_conversion_log (for tracking conversion process details)
- Add conversion_engine_version (for versioning different conversion algorithms)

## Test Data Available
- **Thesis ID 67** (NeuroGuard_BSIT.pdf):
  - File: thesis_69dae2eea4e4d_1775952622.pdf
  - Current pages: 58
  - Format: PDF
  - Status: Ready for conversion testing
  - User ID 1 (admin) has approved access

## Critical Notes
1. ✅ Phase 1 columns (document_type, page_count) ARE ALREADY IN DATABASE!
2. ⚠️ User role 'instructor' does NOT exist yet
3. ⚠️ No column yet to track journal converted file
4. ⚠️ Current view_thesis.php shows 4 tabs, needs 5 for Phase 2
5. ✅ File storage system (file_path) already working

---

**Ready to proceed with Phase 2 implementation?**
