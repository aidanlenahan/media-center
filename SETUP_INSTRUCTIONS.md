# Quick Setup Instructions for Root User Feature

## What Was Added

The system now includes a **Root User** with access to a comprehensive **Developer Panel** featuring advanced testing and debugging tools.

## Setup Steps

### 1. Update Database Schema
Run the setup to add the new `role` column to librarians table and create `dev_settings` table:

```
http://localhost/media-center/public/setup.php
```

This will:
- Add `role` ENUM column to `librarians` table (librarian, root)
- Create `dev_settings` table with 7 developer options
- Create root user account (username: `root`, password: `root123`)
- Create default admin account (username: `admin`, password: `admin123`)

### 2. Login as Root User
1. Go to: `http://localhost/media-center/admin_login.php`
2. Username: `root`
3. Password: `root123`
4. ⚠️ **CHANGE THIS PASSWORD IMMEDIATELY**

### 3. Access Developer Panel
After logging in, you'll see a red "⚙️ Developer" button in the dashboard navigation. Click it to access the Developer Panel.

Or navigate directly to:
```
http://localhost/media-center/dev_panel.php
```

## Developer Panel Features

### 1. System Information
- PHP version, MySQL version, server info
- PHPMailer installation status
- Database connection details

### 2. Developer Settings (Toggle On/Off)
- **Debug Mode**: Show detailed error messages
- **Show SQL Queries**: Log all database queries
- **Log All Actions**: Record every user action
- **Bypass Time Restrictions**: Form open 24/7 (great for testing)
- **Test Mode**: Prevent emails from sending (logs only)
- **Allow Duplicate Passes**: Remove duplicate prevention
- **Email Override Address**: Send all emails to one address

### 3. Test Pass Sender
- Send test passes to any email for any mod (1-8)
- Useful for testing email delivery
- Creates actual pass in database
- Respects test mode and email override settings

### 4. Quick Actions
- Check Database Status
- Test Email Configuration
- Re-run Setup Script

### 5. Danger Zone
- Clear All Current Passes (IRREVERSIBLE)
- Reset Settings to Default

## Common Testing Scenarios

### Test Email Without Spamming Students
1. Enable **Email Override Address** → Enter your email
2. Enable **Test Mode** (optional - prevents actual sending)
3. Use **Test Pass Sender** to send test emails
4. All emails redirect to your address

### Test Form Outside Business Hours
1. Enable **Bypass Time Restrictions**
2. Open student form any time (even at night)
3. Form accepts submissions regardless of time settings

### Test Pass Workflow
1. Enable **Email Override Address** → Your email
2. Open student form: `http://localhost/media-center/form.php`
3. Submit a pass request
4. Go to Dashboard → Manage Passes
5. Approve the pass
6. Check your email for the pass notification

## Files Added/Modified

### New Files
- `dev_panel.php` - Developer Panel UI
- `database/create_root_user.sql` - SQL script to create root user
- `ROOT_USER_GUIDE.md` - Comprehensive developer guide
- `SETUP_INSTRUCTIONS.md` - This file

### Modified Files
- `database/init.sql` - Added role column, dev_settings table, root user INSERT
- `includes/functions.php` - Integrated dev settings into email and form functions
- `dashboard.php` - Added role detection and Developer button for root users

## Verification Checklist

After setup, verify:
- [ ] Can login with root user (root / root123)
- [ ] Developer button appears in dashboard navigation
- [ ] Can access Developer Panel
- [ ] System Information displays correctly
- [ ] Can toggle developer settings and save
- [ ] Test Pass Sender creates and sends test passes
- [ ] Email Override redirects emails correctly
- [ ] Bypass Time Restrictions allows 24/7 form submission
- [ ] Test Mode prevents email sending
- [ ] Check Database shows all green statuses

## Troubleshooting

### Cannot Login as Root
**Issue**: Invalid username/password
**Solution**: Re-run setup.php to recreate root user

### Developer Button Not Showing
**Issue**: Role not set correctly
**Solution**: Check database:
```sql
SELECT id, username, role FROM librarians WHERE username = 'root';
```
Should show role='root'. If not, update:
```sql
UPDATE librarians SET role = 'root' WHERE username = 'root';
```

### 403 Error on dev_panel.php
**Issue**: .htaccess blocking access
**Solution**: .htaccess should allow `dev_panel.php` in root. Verify:
```apache
<ElseIf "%{REQUEST_URI} =~ m#^/media-center/(form|login|dashboard|logout|setup|update_database|403|dev_panel)\.php$#">
    Require all granted
</ElseIf>
```

### Developer Settings Not Working
**Issue**: Functions not reading dev_settings table
**Solution**: 
1. Verify dev_settings table exists: `SHOW TABLES LIKE 'dev_settings';`
2. Check for data: `SELECT * FROM dev_settings;`
3. Re-run setup.php if table missing

### Test Emails Not Sending
**Issue**: PHPMailer or Gmail credentials
**Solution**:
1. Check System Information panel - PHPMailer installed?
2. Verify Gmail credentials in `includes/email_config.php`
3. Use Test Email Configuration tool
4. Check PHP error logs: `C:\wamp64\logs\php_error.log`

## Security Notes

⚠️ **Important Security Considerations**:

1. **Change Default Password**: `root123` is insecure
2. **Production**: Remove or disable root user on live servers
3. **Debug Mode**: Never enable in production (security risk)
4. **Email Override**: Clear before going live
5. **Developer Settings**: Disable all before production deployment

## Next Steps

1. Run setup.php to create database changes
2. Login as root and explore Developer Panel
3. Change root password
4. Test each developer setting
5. Try test pass sender
6. Read ROOT_USER_GUIDE.md for complete documentation

## Support

For detailed information:
- **ROOT_USER_GUIDE.md** - Complete developer guide
- **PROJECT_DOCUMENTATION.md** - Full system documentation
- **TODO.md** - Task list and feature suggestions
