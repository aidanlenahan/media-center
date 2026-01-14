# Root User & Developer Panel Guide

## Overview
The root user has access to an exclusive Developer Panel with advanced settings and tools for testing and debugging the Media Center Pass System.

## Login Credentials
- **Username:** `root`
- **Password:** `root123` (⚠️ **CHANGE THIS IMMEDIATELY AFTER FIRST LOGIN**)

## Accessing the Developer Panel
1. Login at: `http://localhost/media-center/login.php`
2. Use the root credentials
2. Click the "⚙️ Developer" button in the dashboard navigation
3. Or navigate directly to: `http://localhost/media-center/dev_panel.php`

## Developer Settings

### Debug Mode
- **Purpose:** Display detailed error messages and debugging information
- **Use Case:** Troubleshooting PHP errors, database issues, or application logic problems
- **When to Enable:** During development only, never in production

### Show SQL Queries
- **Purpose:** Log all database queries for debugging
- **Use Case:** Optimize database performance, debug query issues
- **When to Enable:** When investigating database-related problems

### Log All Actions
- **Purpose:** Record every user action and system event
- **Use Case:** Track user behavior, audit trail, debugging workflow issues
- **When to Enable:** When you need a detailed log of all system activity

### Bypass Time Restrictions ⭐
- **Purpose:** Allow form submission anytime (ignores open/close times)
- **Use Case:** Testing the form outside of normal hours
- **When to Enable:** During development or after-hours testing
- **Impact:** Students can submit passes 24/7 regardless of settings

### Test Mode ⭐
- **Purpose:** Prevent emails from actually being sent
- **Use Case:** Testing email functionality without spamming real addresses
- **When to Enable:** During development, staging, or testing
- **Impact:** Emails are logged but not sent

### Allow Duplicate Passes
- **Purpose:** Allow the same student to submit multiple passes per mod
- **Use Case:** Testing pass submission workflow
- **When to Enable:** When you need to test multiple submissions
- **Impact:** Bypasses duplicate pass prevention logic

### Email Override Address ⭐
- **Purpose:** Redirect ALL emails to a single address
- **Use Case:** Testing email content and delivery without using real student emails
- **When to Enable:** Always during testing
- **Example:** Set to your personal email to receive all test passes
- **Impact:** All emails go to the override address instead of actual recipients

## Test Pass Sender Tool

### Purpose
Send test passes to any email address for any mod without going through the student form.

### How to Use
1. Open the Developer Panel
2. Scroll to "Send Test Pass" section
3. Enter recipient email address
4. Select mod (1-8)
5. Click "📤 Send Test Pass"
6. Pass is created and email sent immediately (unless Test Mode is enabled)

### Use Cases
- Test email delivery to different providers (Gmail, Outlook, etc.)
- Verify pass email formatting
- Test pass code generation
- Demonstrate the system to stakeholders

## Quick Actions

### Check Database Status
- Verifies all tables and columns exist
- Tests SQL query syntax
- Validates admin accounts
- Shows green/red status for each check

### Test Email Configuration
- Send test emails to any address
- Verify PHPMailer and SMTP settings
- Check email delivery

### Re-run Setup
- Reinitialize database tables
- Useful if you modify the schema
- Safe to run multiple times (uses IF NOT EXISTS)

## Danger Zone ⚠️

### Clear All Current Passes
- **Action:** Deletes ALL records from `passes_current` table
- **Warning:** IRREVERSIBLE - use with extreme caution
- **Use Case:** Reset system during testing
- **Confirmation:** Requires JavaScript confirm dialog

### Reset Settings to Default
- **Action:** Resets all settings to original values
- **Warning:** Overwrites all librarian-configured settings
- **Use Case:** Fix misconfigured settings, start fresh
- **Default Values:**
  - Form Auto Open: Enabled
  - Open Time: 7:30 AM
  - Close Time: 2:30 PM
  - Auto Approval: Disabled
  - Weekend Disable: Disabled

## Common Testing Workflows

### Testing Email Functionality
1. Enable **Email Override Address** → Set to your email
2. Enable **Test Mode** if you want to skip actual sending
3. Use **Test Pass Sender** to send a test pass
4. Check your email for delivery

### Testing After-Hours Form Submission
1. Enable **Bypass Time Restrictions**
2. Open the student form (`form.php`)
3. Submit a pass outside normal hours (e.g., at night)
4. Form should accept submission regardless of time

### Testing Multiple Submissions
1. Enable **Allow Duplicate Passes**
2. Submit the same student info multiple times
3. System should allow duplicates for the same mod

### Debugging Email Issues
1. Enable **Debug Mode** for detailed error messages
2. Use **Test Email Configuration** tool
3. Check PHP error logs in `C:\wamp64\logs\`
4. Verify Gmail SMTP credentials in `includes/email_config.php`

## System Information Panel
The Developer Panel displays:
- PHP Version
- MySQL Version
- Server Software (Apache)
- Document Root path
- PHPMailer installation status
- Database connection details

## Security Best Practices

1. **Change Default Password**
   - Change `root123` immediately after first login
   - Use a strong password with special characters

2. **Disable Root User in Production**
   - Remove or disable root account on live servers
   - Only use for development and testing

3. **Never Enable Debug Mode in Production**
   - Exposes sensitive error messages
   - Security risk

4. **Clear Developer Settings Before Going Live**
   - Disable all debug/test settings
   - Remove email override
   - Disable bypass time restrictions

## Troubleshooting

### Cannot Access Developer Panel
- **Issue:** 403 Forbidden error
- **Solution:** Check `.htaccess` allows `public/*.php` files
- **Verify:** Can access other files in `public/` directory?

### Developer Panel Doesn't Show in Dashboard
- **Issue:** "Developer" button missing in navigation
- **Solution:** Check database, ensure librarian role is 'root'
- **SQL:** `SELECT role FROM librarians WHERE username = 'root';`

### Test Pass Not Sending
- **Check:** Is Test Mode enabled? (emails won't actually send)
- **Check:** Email Override address correct?
- **Check:** PHPMailer installed? View System Information panel
- **Check:** Gmail credentials correct in `email_config.php`?

## File Locations
- Developer Panel: `dev_panel.php`
- Functions (dev settings integration): `includes/functions.php`
- Database Schema: `database/init.sql`
- Root User Creation Script: `database/create_root_user.sql`
- Dashboard (root detection): `dashboard.php`

## Additional Developer Settings Ideas

Future enhancements to consider:
- **Maintenance Mode:** Display "system under maintenance" message
- **Mock Data Generator:** Auto-generate test passes for all mods
- **Email Queue Viewer:** See pending/sent emails
- **Activity Log Viewer:** Display logged actions in UI
- **Database Backup/Restore:** One-click backup tools
- **API Test Console:** Test internal functions
- **Performance Metrics:** Track page load times, query counts
- **Error Log Viewer:** Display recent PHP errors in panel
- **Session Viewer:** See active sessions and user data
- **Cron Job Simulator:** Manually trigger scheduled tasks
- **Theme Tester:** Preview different color schemes
- **Language Switcher:** Test bilingual content easily

---

## Support
For issues or questions about the Developer Panel:
1. Check this guide first
2. Review `PROJECT_DOCUMENTATION.md`
3. Check PHP error logs: `C:\wamp64\logs\php_error.log`
4. Check Apache error logs: `C:\wamp64\logs\apache_error.log`
