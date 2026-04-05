NOTIFICATION SYSTEM DEPLOYMENT GUIDE
=====================================

## Overview
The notification system has been enhanced to support 4 key events:
1. New Approved Thesis Upload
2. Thesis Deletion
3. Thesis Access Request (to admin)
4. Thesis Access Approval (to requester)

## Installation Steps

### Step 1: Initialize Database
1. Open your browser and navigate to: http://localhost/ctrws-fix/init_notifications.php
2. This script will:
   - Check if notifications table exists
   - Create it if missing
   - Add any missing columns (thesis_id, type, created_at)
   - Verify indexes are in place
3. You should see: "=== NOTIFICATION SYSTEM READY ===" message

### Step 2: Verify Files Are Updated
The following files have been automatically updated:
- ✓ admin_includes/admin_add_thesis.php - Sends notifications when thesis approved
- ✓ admin_includes/admin_delete_thesis.php - Sends notifications when thesis deleted
- ✓ client_includes/request_access_code.php - Sends notifications to admin on request
- ✓ admin_includes/admin_approve_access.php - Sends notifications to requester on approval
- ✓ client_includes/create_notification.php - Helper functions (already created)

## Testing the Notification System

### Test 1: Upload New Thesis (Admin) → Notify Users
1. Login as admin
2. Go to Admin Panel → Add Thesis
3. Fill in thesis details
4. Set Status to "Approved"
5. Upload file and submit
6. Expected: All students/instructors receive notification
   - Title: "New Thesis Available: [Thesis Title]"
   - Message: "A new thesis '[Title]' by [Author] is now available for viewing."
   - Time: Current time in relative format (e.g., "just now", "5 minutes ago")

### Test 2: Delete Thesis (Admin) → Notify Users
1. Login as admin
2. Go to Admin Panel → View Thesis
3. Find any thesis and click Delete
4. Confirm deletion
5. Expected: All active users receive notification
   - Title: "Thesis Deleted"
   - Message: "The thesis '[Title]' has been removed from the system."

### Test 3: Request Access (Student) → Notify Admin
1. Login as student/instructor
2. Go to Browse Thesis
3. Find a restricted thesis and click "Request Access"
4. Submit request
5. Expected: Admin receives notification
   - Title: "Thesis Access Request"
   - Message: "[Student Name] has requested access to '[Thesis Title]'"

### Test 4: Approve Access Request (Admin) → Notify Requester
1. Login as admin
2. Go to Admin Panel → View Access Requests
3. Find a pending request and click Approve
4. Expected: Student receives notification
   - Title: "Thesis Access Approved"
   - Message: "Your request to access '[Thesis Title]' has been approved."

## Viewing Notifications

### Notification Center (Header)
- Click the bell icon 📢 in the top-right corner
- Shows:
  - Badge with unread count (orange)
  - Latest 10 notifications
  - Unread notifications highlighted with orange border
  - Time relative to current time (e.g., "2 hours ago")

### Mark as Read
- Click on a notification to mark as read
- Automatically removes the orange highlight
- Notification remains visible but marked as read

### Mark All as Read
- In notification dropdown, click "Mark All as Read"
- All notifications marked as read at once

## Database Schema

### notifications Table
```
id (INT) - Primary key
user_id (INT) - User receiving notification (foreign key → users.id)
type (VARCHAR 50) - Notification type: 'thesis_upload', 'thesis_deleted', 'access_request', 'access_approved'
title (VARCHAR 255) - Notification title
message (TEXT) - Notification message
thesis_id (INT, nullable) - Related thesis (foreign key → thesis.id)
is_read (BOOLEAN) - Whether user has viewed the notification
created_at (TIMESTAMP) - When notification was created
```

## Notification Types and Triggers

### thesis_upload
- Triggered when: Admin uploads thesis with status="approved"
- Recipients: All students and instructors
- Title: "New Thesis Available: [Thesis Title]"
- Message: "A new thesis '[Title]' by [Author] is now available for viewing."

### thesis_deleted
- Triggered when: Admin deletes any thesis
- Recipients: All active non-admin users
- Title: "Thesis Deleted"
- Message: "The thesis '[Title]' has been removed from the system."

### access_request
- Triggered when: Student/instructor requests access to restricted thesis
- Recipients: Admin only
- Title: "Thesis Access Request"
- Message: "[User Name] has requested access to '[Thesis Title]'"

### access_approved
- Triggered when: Admin approves access request
- Recipients: User who made the request
- Title: "Thesis Access Approved"
- Message: "Your request to access '[Thesis Title]' has been approved."

## Troubleshooting

### Notifications Not Appearing
1. Clear browser cache (Ctrl+Shift+Delete in Chrome)
2. Check database notifications table exists:
   - Run: http://localhost/ctrws-fix/init_notifications.php
3. Check PHP error logs for errors in:
   - client_includes/create_notification.php
   - admin_includes/admin_add_thesis.php
   - admin_includes/admin_delete_thesis.php
   - client_includes/request_access_code.php
   - admin_includes/admin_approve_access.php

### Notification Timestamp Wrong
- Check server time sync: `date` in terminal
- MySQL should use same timezone as PHP (check php.ini)
- created_at field uses NOW() which respects MySQL timezone

### Missing Notification for Specific Event
1. Check error logs: tail -f /var/log/php-errors.log
2. Verify admin user exists: SELECT * FROM users WHERE user_role='admin'
3. Verify users are active: SELECT * FROM users WHERE account_status='active'
4. Check notification was inserted: SELECT * FROM notifications WHERE created_at > DATE_SUB(NOW(), INTERVAL 5 MINUTE)

## Deployment to Hostinger

1. Upload all modified files to server:
   - /ctrws-fix/admin_includes/admin_add_thesis.php
   - /ctrws-fix/admin_includes/admin_delete_thesis.php
   - /ctrws-fix/admin_includes/admin_approve_access.php
   - /ctrws-fix/client_includes/request_access_code.php
   - /ctrws-fix/client_includes/create_notification.php
   - /ctrws-fix/init_notifications.php (for initialization)

2. Via Hostinger File Manager:
   - Login to Hostinger cpanel
   - File Manager → public_html
   - Upload files maintaining directory structure

3. Initialize notifications:
   - Open browser: https://yourdomain.com/ctrws-fix/init_notifications.php
   - Verify success message appears

4. Test on live server:
   - Follow testing steps above
   - Check notifications appear in real-time

## Real-Time Updates

The notification system uses polling via AJAX:
- Notifications checked every 30 seconds (configurable in browse.php)
- Updates happen automatically without page refresh
- Bell icon updates in real-time when new notifications arrive

## Performance Considerations

- Notifications table has indexes on: user_id, is_read, created_at
- Query retrieves only unread + 10 recent notifications (optimized)
- Bulk notifications use prepared statements (fast and secure)
- No WebSocket overhead - simple polling is sufficient for most deployments

## Customization

To modify notification behavior, edit:

### Change Notification Poll Interval
File: browse.php, find `setInterval(loadNotifications, 30000)`
Change 30000 (milliseconds) to desired value

### Add New Notification Types
1. Modify create_notification.php to add type
2. Add creation call in relevant event handler
3. Add styling for new type in browse.php UI

### Change Notification Display
File: browse.php, search for `notification-item`
Modify HTML and CSS in `loadNotifications()` function

## Success Criteria

✓ New approved thesis generates notification for all users
✓ Deleted thesis generates notification for all users
✓ Access request generates notification for admin
✓ Access approval generates notification for requester
✓ Notifications display in bell icon with badge count
✓ Clicking notification marks it as read
✓ Timestamps show relative time (e.g., "5 minutes ago")
✓ Notifications persist in database
✓ No duplicate notifications created
✓ System works on Hostinger hosting

## Next Steps

1. Run init_notifications.php to set up database
2. Test each of the 4 test scenarios above
3. Monitor error logs for issues
4. Deploy to Hostinger using File Manager
5. Test on live site

Questions? Check browser console (F12) for JavaScript errors or PHP error logs.
