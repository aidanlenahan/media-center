# Gmail SMTP Configuration Guide

## Option 1: Using PHPMailer (Recommended)

PHPMailer is the most reliable way to send emails via Gmail SMTP.

### Step 1: Install PHPMailer

Using Composer (recommended):
```bash
cd c:\wamp64\www\media-center
composer require phpmailer/phpmailer
```

OR download manually:
1. Download from: https://github.com/PHPMailer/PHPMailer
2. Extract to `media-center/vendor/phpmailer/`

### Step 2: Get Gmail App Password

**Important:** You can't use your regular Gmail password. You need an App Password.

1. Go to your Google Account: https://myaccount.google.com/
2. Click **Security** (left sidebar)
3. Enable **2-Step Verification** if not already enabled
4. Scroll to **2-Step Verification** section
5. Click **App passwords**
6. Select **Mail** and **Windows Computer** (or Other)
7. Click **Generate**
8. Copy the 16-character password (example: `abcd efgh ijkl mnop`)

### Step 3: Create Email Configuration File

Create a new file: `includes/email_config.php`

```php
<?php
// Gmail SMTP Configuration
define('SMTP_HOST', 'smtp.gmail.com');
define('SMTP_PORT', 587);  // or 465 for SSL
define('SMTP_SECURE', 'tls');  // or 'ssl' if using port 465
define('SMTP_USERNAME', 'your-email@gmail.com');  // Your Gmail address
define('SMTP_PASSWORD', 'your-app-password-here');  // 16-char app password
define('SMTP_FROM_EMAIL', 'your-email@gmail.com');
define('SMTP_FROM_NAME', 'Media Center Pass System');
?>
```

### Step 4: Update functions.php to use PHPMailer

Replace the email functions with:

```php
<?php
use PHPMailer\PHPMailer\PHPMailer;
use PHPMailer\PHPMailer\Exception;

require_once __DIR__ . '/../vendor/autoload.php';
require_once __DIR__ . '/email_config.php';

function sendPassEmail($email, $firstName, $lastName, $passCode, $mod, $activities) {
    $mail = new PHPMailer(true);
    
    try {
        // Server settings
        $mail->isSMTP();
        $mail->Host       = SMTP_HOST;
        $mail->SMTPAuth   = true;
        $mail->Username   = SMTP_USERNAME;
        $mail->Password   = SMTP_PASSWORD;
        $mail->SMTPSecure = SMTP_SECURE;
        $mail->Port       = SMTP_PORT;
        
        // Recipients
        $mail->setFrom(SMTP_FROM_EMAIL, SMTP_FROM_NAME);
        $mail->addAddress($email, "$firstName $lastName");
        
        // Content
        $mail->isHTML(true);
        $mail->Subject = 'Your Media Center Study Hall Pass';
        $mail->Body    = "
        <html>
            <body style='font-family: Arial, sans-serif;'>
                <h2 style='color: #667eea;'>Media Center Study Hall Pass</h2>
                <p>Hello <strong>$firstName $lastName</strong>,</p>
                <p>Your pass for today has been approved. Here are your details:</p>
                <div style='background: #f8f9fa; padding: 15px; border-left: 4px solid #667eea;'>
                    <p><strong>Pass Code:</strong> <span style='font-size: 1.3em; color: #667eea;'>$passCode</span></p>
                    <p><strong>Mod:</strong> $mod</p>
                    <p><strong>Activity:</strong> " . implode(', ', $activities) . "</p>
                </div>
                <p>Please present this pass code to enter the Media Center.</p>
                <p style='color: #666; font-size: 0.9em;'><em>Remember: The Media Center is a quiet space for studying and reading.</em></p>
            </body>
        </html>
        ";
        
        $mail->send();
        return true;
    } catch (Exception $e) {
        error_log("Email send failed: {$mail->ErrorInfo}");
        return false;
    }
}

function sendTeacherDailySummary($pdo, $teacherEmail) {
    $mail = new PHPMailer(true);
    $today = date('Y-m-d');
    
    try {
        // Get passes for this teacher
        $stmt = $pdo->prepare("
            SELECT first_name, last_name, `mod`, status 
            FROM passes_current 
            WHERE teacher_name = ? AND DATE(created_at) = ?
            ORDER BY `mod` ASC
        ");
        $stmt->execute([$teacherEmail, $today]);
        $passes = $stmt->fetchAll();
        
        if (empty($passes)) {
            return true;
        }
        
        // Build email content
        $message = "
        <html>
            <head>
                <style>
                    body { font-family: Arial, sans-serif; }
                    table { border-collapse: collapse; width: 100%; margin-top: 20px; }
                    th, td { border: 1px solid #ddd; padding: 10px; text-align: left; }
                    th { background: #667eea; color: white; }
                    tr:nth-child(even) { background: #f8f9fa; }
                    .approved { color: green; font-weight: bold; }
                    .pending { color: orange; font-weight: bold; }
                    .rejected { color: red; font-weight: bold; }
                </style>
            </head>
            <body>
                <h2>Media Center Study Hall Passes - Daily Summary</h2>
                <p><strong>Date:</strong> $today</p>
                <table>
                    <tr>
                        <th>Student Name</th>
                        <th>Mod</th>
                        <th>Status</th>
                    </tr>";
        
        $approvedCount = 0;
        $pendingCount = 0;
        $rejectedCount = 0;
        
        foreach ($passes as $pass) {
            $status = ucfirst($pass['status']);
            $statusClass = strtolower($pass['status']);
            
            $message .= "<tr>
                <td>{$pass['first_name']} {$pass['last_name']}</td>
                <td>{$pass['mod']}</td>
                <td><span class='$statusClass'>$status</span></td>
            </tr>";
            
            if ($pass['status'] === 'approved') $approvedCount++;
            elseif ($pass['status'] === 'pending') $pendingCount++;
            else $rejectedCount++;
        }
        
        $message .= "
                </table>
                <p style='margin-top: 20px;'>
                    <strong>Summary:</strong><br>
                    Approved: $approvedCount | Pending: $pendingCount | Rejected: $rejectedCount
                </p>
            </body>
        </html>";
        
        // Server settings
        $mail->isSMTP();
        $mail->Host       = SMTP_HOST;
        $mail->SMTPAuth   = true;
        $mail->Username   = SMTP_USERNAME;
        $mail->Password   = SMTP_PASSWORD;
        $mail->SMTPSecure = SMTP_SECURE;
        $mail->Port       = SMTP_PORT;
        
        // Recipients
        $mail->setFrom(SMTP_FROM_EMAIL, SMTP_FROM_NAME);
        $mail->addAddress($teacherEmail);
        
        // Content
        $mail->isHTML(true);
        $mail->Subject = "Daily Media Center Pass Summary - $today";
        $mail->Body    = $message;
        
        $mail->send();
        return true;
    } catch (Exception $e) {
        error_log("Email send failed: {$mail->ErrorInfo}");
        return false;
    }
}
?>
```

---

## Option 2: Using Native PHP mail() with Gmail SMTP (Windows)

If you can't install PHPMailer, configure your `php.ini`:

### For WAMP/XAMPP on Windows:

1. Open `php.ini` (usually in `c:\wamp64\bin\php\php8.x.x\`)

2. Find and update these lines:
```ini
[mail function]
SMTP = smtp.gmail.com
smtp_port = 587
sendmail_from = your-email@gmail.com
sendmail_path = "\"C:\wamp64\sendmail\sendmail.exe\" -t"
```

3. Configure sendmail (comes with WAMP):
   - Open `C:\wamp64\sendmail\sendmail.ini`
   - Update:
```ini
smtp_server=smtp.gmail.com
smtp_port=587
auth_username=your-email@gmail.com
auth_password=your-16-char-app-password
force_sender=your-email@gmail.com
```

4. Restart WAMP services

---

## Option 3: Using School Email Server

If your school has an email server, use those credentials instead:

```php
define('SMTP_HOST', 'mail.yourschool.edu');
define('SMTP_PORT', 587);
define('SMTP_USERNAME', 'mediacenter@yourschool.edu');
define('SMTP_PASSWORD', 'school-password');
```

---

## Testing Email Configuration

Create a test file: `public/test_email.php`

```php
<?php
require_once '../includes/config.php';
require_once '../includes/functions.php';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $testEmail = $_POST['test_email'] ?? '';
    
    if ($testEmail) {
        $result = sendPassEmail(
            $testEmail,
            'Test',
            'Student',
            'TEST123ABC',
            3,
            ['Studying', 'Reading']
        );
        
        if ($result) {
            echo "<div style='color: green; padding: 20px;'>✅ Email sent successfully to $testEmail!</div>";
        } else {
            echo "<div style='color: red; padding: 20px;'>❌ Email failed to send. Check error logs.</div>";
        }
    }
}
?>
<!DOCTYPE html>
<html>
<head>
    <title>Test Email Configuration</title>
</head>
<body style="font-family: Arial; padding: 40px;">
    <h2>Test Email Sending</h2>
    <form method="POST">
        <label>Enter your email to receive a test pass:</label><br>
        <input type="email" name="test_email" required style="padding: 8px; width: 300px;"><br><br>
        <button type="submit" style="padding: 10px 20px;">Send Test Email</button>
    </form>
</body>
</html>
```

---

## Troubleshooting

### "SMTP connect() failed"
- Check your firewall allows port 587/465
- Verify Gmail App Password is correct (no spaces)
- Enable "Less secure app access" if needed (not recommended)

### "Authentication failed"
- Use App Password, not your regular Gmail password
- 2-Step Verification must be enabled

### Emails going to spam
- Set proper FROM address matching your Gmail
- Add SPF/DKIM records (if using custom domain)
- Ask recipients to whitelist your email

### Check PHP error logs:
```bash
# WAMP location:
tail -f c:\wamp64\logs\php_error.log
```

---

## Quick Start (Easiest Method)

1. Install PHPMailer: `composer require phpmailer/phpmailer`
2. Get Gmail App Password from Google Account settings
3. Create `includes/email_config.php` with your credentials
4. Update `includes/functions.php` with PHPMailer code above
5. Test with `public/test_email.php`

Done! Your system will now send emails via Gmail.
