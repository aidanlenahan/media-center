# Media Center Pass System

A bilingual (English/Spanish) web application for managing media center study hall passes. Students submit a form to request passes, and the librarian manages approvals and settings.

## Features

- **Bilingual Form**: Complete English/Spanish support for the student pass request form
- **Student Form**: Collects first name, last name, teacher, mod selection, activities, and behavior agreement
- **Automatic Email Notifications**: Sends pass codes to approved students
- **Librarian Dashboard**: Full admin panel with:
  - Form settings (auto open/close times, automatic approval)
  - Pending pass approval/rejection
  - Pass code generation
  - Historical records viewing
  - System configuration
- **Database Persistence**: All submissions stored for historical review
- **Security**: Password-protected admin login, CSRF protection, input validation

## Project Structure

```
media-center/
├── public/
│   ├── index.php                 # Student pass request form
│   ├── admin_login.php           # Librarian login page
│   ├── dashboard.php             # Admin dashboard
│   ├── logout.php                # Session logout
│   ├── css/
│   │   └── style.css             # Global stylesheet
│   └── js/
│       └── (future custom scripts)
├── includes/
│   ├── config.php                # Database config & connection
│   └── functions.php             # Helper functions
├── database/
│   └── init.sql                  # Database schema & initialization
├── src/
│   └── (future separation of logic)
└── overview.txt                  # Project requirements
```

## Installation & Setup

### 1. Database Setup

You need a MySQL database. Run the initialization script:

```sql
-- Option A: Using command line
mysql -u root -p < database/init.sql

-- Option B: Using phpMyAdmin
1. Create a new database: media_center
2. Import database/init.sql into that database
```

This creates:
- `settings` table: Librarian controls (form times, approval mode)
- `librarians` table: Admin user accounts
- `passes_current` table: Today's pass submissions
- `passes_archive` table: Historical pass records

### 2. Configure Database Connection

Edit `includes/config.php` and set your database credentials:

```php
define('DB_HOST', 'localhost');      // Database host
define('DB_USER', 'root');            // Database user
define('DB_PASS', '');                // Database password
define('DB_NAME', 'media_center');    // Database name
```

### 3. Web Server Setup

Place the `media-center` folder in your web root (e.g., `C:\wamp64\www\`)

Access the application:
- **Student Form**: `http://localhost/media-center/public/index.php`
- **Admin Login**: `http://localhost/media-center/public/admin_login.php`

### 4. Default Admin Credentials

After running the initialization script, log in with:
- **Username**: `admin`
- **Password**: `admin123`

**Important**: Change this password immediately in the database after first login!

```bash
# Generate a new password hash in PHP:
php -r "echo password_hash('your_new_password', PASSWORD_BCRYPT);"

# Then update in MySQL:
UPDATE librarians SET password_hash='<new_hash>' WHERE username='admin';
```

## How It Works

### Student Flow

1. Student opens the form (`index.php`)
2. Checks if form is open (based on settings or manual control)
3. Fills out bilingual form with:
   - First and Last Name
   - Email address (auto-populated from school system ideally)
   - Study Hall Teacher Name
   - Selected Mod (1-8)
   - Activities (checkboxes: Studying, Project, Reading, Meeting, Other)
   - Agreement checkbox
4. Submits form
5. If **Auto-Approval** enabled: Pass is immediately approved and email sent with pass code
6. If **Manual Approval**: Pass becomes pending; librarian reviews and approves

### Librarian Flow

1. Log in to dashboard (`admin_login.php`)
2. View overview with pass statistics
3. **Manage Passes**: Approve pending passes or reject them
4. **Settings**: Configure:
   - Enable/disable automatic form opening
   - Set form open/close times
   - Toggle automatic pass approval
5. **History**: View historical pass records from any date
6. **Logout**: End session

### Database Tables

#### `settings`
- `form_auto_open`: Boolean (1=auto, 0=manual)
- `form_open_time`: Time (HH:MM:SS)
- `form_close_time`: Time (HH:MM:SS)
- `auto_approval`: Boolean (1=auto send, 0=manual review)

#### `passes_current`
- Student info: first_name, last_name, email
- Pass details: teacher_name, mod, activities (JSON)
- Pass info: pass_code (unique), status (pending/approved/rejected)
- Timestamps: created_at, sent_at, updated_at

#### `passes_archive`
- Same as passes_current plus `pass_date` field for historical sorting

#### `librarians`
- id, username, password_hash, email

## Email Configuration

The system uses PHP's `mail()` function. For production, configure your server's mail settings:

### Windows (WAMP/XAMPP)

Edit `php.ini`:
```ini
[mail function]
SMTP = smtp.gmail.com
smtp_port = 587
sendmail_from = your-email@school.local
```

Or use a dedicated mail server in your school's network.

### Testing Email

For development, modify `sendPassEmail()` function in `includes/functions.php` to log to file instead of sending:

```php
// Log to file instead of mailing
file_put_contents('mail.log', "To: $email\nSubject: $subject\n\n$message\n---\n", FILE_APPEND);
return true;
```

## Security Notes

- **CSRF Protection**: All forms include CSRF tokens
- **Input Sanitization**: All user inputs are sanitized
- **SQL Injection Prevention**: Using prepared statements (PDO)
- **Password Hashing**: Passwords use bcrypt (PASSWORD_BCRYPT)
- **Session Security**: Secure session configuration enabled

### Recommended Production Steps

1. Change default admin password
2. Use HTTPS for all connections
3. Configure proper mail server
4. Set appropriate file permissions on database files
5. Keep database backups
6. Consider adding rate limiting to form submissions
7. Add logging for all admin actions

## Features Implemented

### Core Features
✅ Bilingual form (English/Spanish)
✅ Student pass request submission
✅ Auto-generated pass codes
✅ Email notifications
✅ Admin login system
✅ Dashboard with settings management
✅ Pass approval/rejection
✅ Historical record storage
✅ Automatic form open/close
✅ Manual/automatic approval modes
✅ CSRF protection
✅ Input validation
✅ Responsive design

### Future Enhancements
- Email templates customization
- Pass validity/expiration dates
- QR code generation for passes
- Integration with school email system
- Advanced reporting and analytics
- Batch import of teacher names
- Two-factor authentication for admin
- API for integration with other systems
- Student verification system
- Mod conflict detection

## Troubleshooting

### "Database connection failed"
- Check database credentials in `includes/config.php`
- Ensure MySQL is running
- Verify database name exists

### "Form not appearing"
- Check if form is closed (check settings)
- Verify PHP is properly configured

### "Emails not sending"
- Check mail configuration in `php.ini`
- Verify SMTP settings
- Check server error logs

### "Login not working"
- Ensure admin password hash was created correctly
- Clear browser cookies/session
- Check if librarians table exists and has data

## Database Queries (Examples)

### Get today's passes by status
```sql
SELECT status, COUNT(*) as count 
FROM passes_current 
GROUP BY status;
```

### Get specific student's history
```sql
SELECT * FROM passes_archive 
WHERE first_name = 'John' AND last_name = 'Doe' 
ORDER BY pass_date DESC;
```

### Get all passes from a specific teacher
```sql
SELECT * FROM passes_current 
WHERE teacher_name = 'Mr. Smith' 
ORDER BY created_at DESC;
```

## Support & Maintenance

For issues or enhancements:
1. Check error logs in PHP error_log
2. Review database integrity
3. Verify all files are properly uploaded
4. Check file permissions (especially on database directory)

## License

This project is for school use. Modify as needed for your school's requirements.
