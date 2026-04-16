# PHASE 2 Deployment Guide

## What's Been Created

### 1. Database Migration (`PHASE2_MIGRATION.sql`)
**Location:** `PHASE2_MIGRATION.sql`

**Creates:**
- ✅ `instructor` role (for role-based access control)
- ✅ `journal_file_path` - Path to converted journal document
- ✅ `is_journal_converted` - Boolean flag
- ✅ `journal_conversion_status` - Enum (pending/processing/completed/failed)
- ✅ `journal_page_count` - Page count of journal version
- ✅ `journal_converted_at` - Timestamp
- ✅ `journal_imrad_sections` - JSON storage of IMRaD structure

---

## 2. IMRaD Analyzer (`ai_includes/imrad_analyzer.php`)
**Location:** `ai_includes/imrad_analyzer.php`

**Functionality:**
- ✅ Analyzes document structure
- ✅ Identifies IMRaD sections (Introduction, Methods, Results, Discussion, Conclusions)
- ✅ Calculates confidence scores
- ✅ Handles documents without clear headers
- ✅ Returns structured section data

**Class:** `IMRaDAnalyzer`
```php
$analyzer = new IMRaDAnalyzer($document_text);
$structure = $analyzer->analyze();
// Returns: {success, sections[], section_count, confidence}
```

---

## 3. Journal Converter (`ai_includes/journal_converter.php`)
**Location:** `ai_includes/journal_converter.php`

**Functionality:**
- ✅ Extracts and condenses sections to ~3500 words (14 pages)
- ✅ Uses Ollama AI for intelligent summarization (if available)
- ✅ Falls back to keyword-based summarization
- ✅ Reconstructs as proper IMRaD journal article
- ✅ Generates PDF output
- ✅ Updates database with results

**Class:** `JournalConverter`
```php
$converter = new JournalConverter($thesis_id, $text, $metadata, $conn);
$result = $converter->convert();
// Returns: {success, journal_file_path, conversion_status, message}
```

---

## 4. API Endpoint (`api_includes/journal_conversion_api.php`)
**Location:** `api_includes/journal_conversion_api.php`

**Purpose:** Processes journal conversion requests from admin interface

**Usage:**
```
POST /api_includes/journal_conversion_api.php
Parameters:
  - thesis_id (int)
  - file_path (string) - relative path to original file
```

---

## DEPLOYMENT STEPS

### Step 1: Execute Database Migration
1. Open phpMyAdmin on Hostinger
2. Select database: `u965322812_thesis_db`
3. Go to **SQL** tab
4. Copy & paste content from `PHASE2_MIGRATION.sql`
5. Click **Execute**

**Verification Query:**
```sql
SELECT * FROM information_schema.COLUMNS 
WHERE TABLE_NAME = 'thesis' 
AND COLUMN_NAME LIKE 'journal_%'
```
Should return 6 rows.

---

### Step 2: Deploy PHP Files
Files already created:
- ✅ `ai_includes/imrad_analyzer.php`
- ✅ `ai_includes/journal_converter.php`
- ✅ `api_includes/journal_conversion_api.php`

No manual action needed - files are in place.

---

### Step 3: TODO - Next Steps

These files still need to be created:
- [ ] Updated `view_thesis.php` with 5 tabs (add "Raw Document" tab)
- [ ] Role-based tab visibility logic
- [ ] Integration into admin_add_thesis.php
- [ ] Test journal conversion with NeuroGuard (thesis ID 67)

---

## TEST DATA

**Use for testing:**
- **Thesis ID:** 67 (NeuroGuard)
- **Title:** NeuroGuard: An AI-Powered Neurological Disorder Early Detection...
- **Original File:** `uploads/thesis_files/thesis_69dae2eea4e4d_1775952622.pdf`
- **Pages:** 58 (will be condensed to 10-20)
- **Status:** Ready for conversion

---

## Configuration

### Ollama Integration (Optional)
If Ollama service is running at `http://localhost:11434`, the system will:
- Use AI-powered summarization for better quality
- Fall back to keyword-based if Ollama unavailable

To enable/disable:
- Modify `JournalConverter::summarizeWithOllama()` method
- Or check if `ai_includes/ollama_service.php` exists

### Target Journal Specifications
- **Word Count:** ~3500 words
- **Pages:** ~14 pages (250 words/page)
- **Format:** IMRaD structure
- **Abstract:** Kept unchanged from original

---

## ERROR HANDLING

The system logs all activities to Apache error log:
```
[JournalConverter] Starting conversion for thesis X
[JournalConverter] Document structure: ...
[JournalConverter] Conversion completed successfully
```

If conversion fails:
- Status = 'failed'
- Original thesis not affected
- Database records failure for retry

---

## NEXT PHASE

Once Phase 2 deployment is confirmed:
1. Update view_thesis.php with new tab structure
2. Add role-based access control
3. Test full workflow with NeuroGuard
4. Create admin UI for triggering conversions

---

**Status:** ✅ Code complete, awaiting database migration execution
