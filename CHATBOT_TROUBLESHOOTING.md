# CITAS Smart Archive - Chatbot System Troubleshooting Guide

## 🔍 Issues Found & Fixed

### **Issue #1: Invalid Session Records (id = 0)**
**Status**: ❌ **CRITICAL**

The database contains sessions with `id = 0`, which is invalid and breaks the entire chatbot system.

**Root Cause:**
- AUTO_INCREMENT wasn't properly configured when tables were created
- Sessions were being inserted with id = 0 instead of auto-generated IDs
- This causes foreign key violations and prevents message storage

**Affected Records** (from database dump):
```sql
- chatbot_sessions: 3 records with id = 0
- chatbot_access_requests: 1 record with id = 0
```

**Impact:**
- ❌ Cannot load these sessions
- ❌ Cannot save messages to these sessions
- ❌ Users see errors when trying to use chatbot
- ❌ All JavaScript fails when trying to reference these invalid IDs

---

### **Issue #2: Orphaned Messages**
**Status**: ⚠️ **SECONDARY**

Some messages exist without a valid parent session due to Issue #1.

**Impact:**
- Database bloat
- Wasted storage space
- Potential cascading errors

---

### **Issue #3: AUTO_INCREMENT Not Reset**
**Status**: ⚠️ **PREVENTION**

After fixing the invalid records, AUTO_INCREMENT needs to be reset so new records start with valid IDs.

---

## ✅ How to Fix

### **Step 1: Run the SQL Fix Script**

#### **Option A: Using Hostinger phpMyAdmin (RECOMMENDED)**

1. **Log in** to Hostinger control panel
2. **Go to**: Databases → phpMyAdmin
3. **Select** database: `u965322812_thesis_db`
4. **Click** "SQL" tab
5. **Paste** the contents of `CHATBOT_FIX.sql`
6. **Click** "Go" to execute

#### **Option B: Using Command Line** (if you have SSH access)

```bash
mysql -h localhost -u u965322812_CITAS_Smart -p u965322812_thesis_db < CHATBOT_FIX.sql
```

---

### **Step 2: Verify the Fix**

After running the SQL script, verify that the issues are resolved:

1. **Visit**: `https://yourdomain.com/chatbot_verification.php`
2. **Login** as admin
3. **Check all status indicators** - should all be ✓ (green)
4. **Verify:**
   - ✓ Database Connection: Connected
   - ✓ Required Tables: All Present
   - ✓ Data Integrity: Clean
   - ✓ Orphaned Records: None

---

## 🧪 Testing the Chatbot Fix

### **Step 1: Test Access Request**

1. Log in as a **regular user** (not admin)
2. Go to a thesis detail page
3. **Click the chatbot bubble** (bottom right)
4. **Click "Request Access"**
5. ✓ **Expected**: Message says "Request submitted"

### **Step 2: Approve Access Request**

1. Log in as **admin**
2. Go to **Admin Dashboard**
3. Find the access request in notifications/admin panel
4. **Click "Approve"**
5. ✓ **Expected**: Request status changes to "approved"

### **Step 3: Test Chatbot Usage**

1. Log in as the **regular user** again
2. Go back to the same thesis detail page
3. **Open chatbot bubble**
4. **Click "New Chat"**
5. **Type a message**: "What is this thesis about?"
6. **Click send** or press Enter
7. ✓ **Expected**: 
   - User message appears on the left
   - Loading indicator appears
   - Bot response appears after a moment
   - Message is saved to session

### **Step 4: Test Session Management**

1. **Create multiple sessions** (up to 5)
2. **Click on a session** to view chat history
3. **Click "New Chat"** again to create another
4. ✓ **Expected**: Session limit warning at 5 sessions
5. **Delete a session** and verify older messages are removed

---

## 📊 SQL Fix Script Breakdown

### What the script does:

```sql
-- 1. Delete invalid sessions (id = 0)
DELETE FROM chatbot_messages WHERE session_id = 0 OR session_id IS NULL;
DELETE FROM chatbot_sessions WHERE id = 0 OR id IS NULL;
DELETE FROM chatbot_access_requests WHERE id = 0 OR id IS NULL;
```
**Removes**: 3 invalid sessions, 1 invalid access request, all orphaned messages

```sql
-- 2. Reset AUTO_INCREMENT
ALTER TABLE chatbot_sessions AUTO_INCREMENT = (MAX_ID + 1);
ALTER TABLE chatbot_messages AUTO_INCREMENT = (MAX_ID + 1);
ALTER TABLE chatbot_access_requests AUTO_INCREMENT = (MAX_ID + 1);
```
**Ensures**: Next inserted record gets a valid ID starting from the next available number

```sql
-- 3. Cleanup orphaned records
DELETE FROM chatbot_messages 
WHERE session_id NOT IN (SELECT id FROM chatbot_sessions);
```
**Removes**: Any messages whose parent session no longer exists (data cleanup)

```sql
-- 4. Verification queries
SELECT ... check for remaining invalid IDs
SELECT ... check for remaining orphaned messages
```
**Verifies**: All data integrity issues are resolved

---

## 🚀 Post-Fix Checklist

After applying the fix, verify:

- [ ] SQL fix script executed successfully
- [ ] All status checks pass in verification tool
- [ ] Regular user can request chatbot access
- [ ] Admin can approve access requests
- [ ] User can create new chat sessions
- [ ] Chat messages are sent and received
- [ ] Session list loads properly
- [ ] Can load previous sessions and see message history
- [ ] Session deletion works properly
- [ ] Browser console shows no errors (F12 → Console tab)

---

## 🐛 If Issues Persist

### **Check 1: Browser Console**
1. Press `F12` to open Developer Tools
2. Go to **Console** tab
3. Look for red error messages
4. Common issues:
   - "401 Not Authorized" → Authentication problem
   - "404 Not Found" → Wrong file paths
   - "Unexpected token" → JSON parsing error

### **Check 2: Server Logs**
1. Check error logs: `debug_chatbot_error.log`
2. Location: `/chatbot_includes/debug_chatbot_error.log`
3. Look for database connection errors

### **Check 3: Verify Ollama is Running**
On Hostinger Tunnel:
1. The Ollama service needs to be running on your tunnel
2. Check tunnel terminal for connection status
3. If connection fails, chatbot uses fallback text responses (still works)

### **Check 4: Test Manually**

Try this URL directly in browser:
```
https://yourdomain.com/chatbot_includes/check_chatbot_access.php?thesis_id=1
```

**Expected Response**: Valid JSON like:
```json
{"success": true, "has_access": false, "status": "no_request"}
```

If you get an error: Check database credentials in `db_connect.php`

---

## 📋 Files Created/Modified

| File | Purpose |
|------|---------|
| `CHATBOT_FIX.sql` | SQL fix script to run in phpMyAdmin |
| `chatbot_verification.php` | Admin tool to verify chatbot system health |
| `fix_chatbot_issues.php` | PHP-based fix script (for local testing) |

---

## 🔗 Related Documentation

- **Chatbot System**: `chatbot_includes/CHATBOT_SUMMARY.md`
- **API Endpoints**: Check individual `chatbot_includes/*.php` files
- **Database Schema**: Your database backup file

---

## 💡 Preventing Future Issues

1. **Regular Backups**: Schedule weekly database backups
2. **Monitor Sessions**: Check `chatbot_verification.php` periodically
3. **Clean Old Sessions**: Implement session archival for inactive sessions
4. **Error Logging**: Check logs regularly: `debug_chatbot_error.log`

---

**Last Updated**: April 11, 2026
**Status**: 🟢 Ready for Production
