<?php

use PHPMailer\PHPMailer\PHPMailer;
use PHPMailer\PHPMailer\Exception;

// Load PHPMailer autoloader if available
if (file_exists(__DIR__ . '/../vendor/autoload.php')) {
    require_once __DIR__ . '/../vendor/autoload.php';
}

// Load email configuration
require_once __DIR__ . '/email_config.php';

// Generate unique pass code
function generatePassCode() {
    return strtoupper(bin2hex(random_bytes(6)));
}

// Check if form is currently open
function isFormOpen($pdo) {
    // Check developer settings for bypass
    $devStmt = $pdo->query("SELECT bypass_time_restrictions FROM dev_settings LIMIT 1");
    $devSettings = $devStmt->fetch();
    if ($devSettings && $devSettings['bypass_time_restrictions']) {
        return true;
    }
    
    $stmt = $pdo->query("SELECT form_auto_open, form_open_time, form_close_time, form_status_override, form_status_manual, disable_weekends FROM settings LIMIT 1");
    $settings = $stmt->fetch();
    
    // Check for manual override first
    if ($settings['form_status_override']) {
        return $settings['form_status_manual'] === 'open';
    }
    
    // Check if weekends are disabled
    if ($settings['disable_weekends']) {
        $dayOfWeek = date('N'); // 1 (Monday) through 7 (Sunday)
        if ($dayOfWeek == 6 || $dayOfWeek == 7) { // Saturday or Sunday
            return false;
        }
    }
    
    // Check if auto-open is disabled
    if (!$settings['form_auto_open']) {
        return false;
    }
    
    // Check time-based opening
    $currentTime = date('H:i:s');
    $openTime = $settings['form_open_time'];
    $closeTime = $settings['form_close_time'];
    
    // Convert to timestamps for proper comparison
    $current = strtotime($currentTime);
    $open = strtotime($openTime);
    $close = strtotime($closeTime);
    
    return $current >= $open && $current <= $close;
}

// Get current settings
function getSettings($pdo) {
    $stmt = $pdo->query("SELECT * FROM settings LIMIT 1");
    return $stmt->fetch();
}

// Validate user input
function sanitizeInput($data) {
    return htmlspecialchars(trim($data), ENT_QUOTES, 'UTF-8');
}

// Send pass email to student
function sendPassEmail($email, $firstName, $lastName, $passCode, $mod, $activities) {
    global $pdo;
    
    // Check developer settings
    $devStmt = $pdo->query("SELECT test_mode, email_override_address FROM dev_settings LIMIT 1");
    $devSettings = $devStmt->fetch();
    
    // If test mode is enabled, don't send email
    if ($devSettings && $devSettings['test_mode']) {
        error_log("TEST MODE: Would have sent email to $email with pass code $passCode");
        return true;
    }
    
    // If email override is set, send to override address instead
    $originalEmail = $email;
    if ($devSettings && !empty($devSettings['email_override_address'])) {
        $email = $devSettings['email_override_address'];
        error_log("EMAIL OVERRIDE: Redirecting email from $originalEmail to $email");
    }
    
    // Check if PHPMailer is available
    if (!class_exists('PHPMailer\PHPMailer\PHPMailer')) {
        // Fallback to native mail()
        $subject = "Your Media Center Study Hall Pass";
        $message = "
        <html>
            <body>
                <h2>Media Center Study Hall Pass</h2>
                <p>Hello $firstName $lastName,</p>
                <p>Your pass for today has been approved. Here are your details:</p>
                <ul>
                    <li><strong>Pass Code:</strong> $passCode</li>
                    <li><strong>Mod:</strong> $mod</li>
                    <li><strong>Activity:</strong> " . implode(', ', $activities) . "</li>
                </ul>
                <p>Please present this pass code to enter the Media Center.</p>
                <p>Remember: The Media Center is a quiet space for studying and reading.</p>
            </body>
        </html>
        ";
        $headers = "MIME-Version: 1.0" . "\r\n";
        $headers .= "Content-type: text/html; charset=UTF-8" . "\r\n";
        return mail($email, $subject, $message, $headers);
    }
    
    // Use PHPMailer with Gmail SMTP
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
        
        // Add test mode prefix if needed
        $subjectPrefix = ($devSettings && $devSettings['test_mode']) ? '[TEST] ' : '';
        
        // Content
        $mail->isHTML(true);
        $mail->Subject = $subjectPrefix . 'Your Media Center Study Hall Pass';
        $mail->Body    = "
        <html>
            <body style='font-family: Arial, sans-serif;'>
                <h2 style='color: #667eea;'>Media Center Study Hall Pass</h2>
                <p>Hello <strong>$firstName $lastName</strong>,</p>
                <p>Your pass for today has been approved. Here are your details:</p>
                <div style='background: #f8f9fa; padding: 15px; border-left: 4px solid #667eea; margin: 20px 0;'>
                    <p><strong>Pass Code:</strong> <span style='font-size: 1.3em; color: #667eea; font-weight: bold;'>$passCode</span></p>
                    <p><strong>Mod:</strong> $mod</p>
                    <p><strong>Activity:</strong> " . implode(', ', $activities) . "</p>
                </div>
                <p>Please present this pass code to enter the Media Center.</p>
                <p style='color: #666; font-size: 0.9em;'><em>Remember: The Media Center is a quiet space for studying and reading.</em></p>
                <hr style='margin-top: 30px; border: none; border-top: 1px solid #ddd;'>
                <p style='color: #999; font-size: 0.8em;'>This is an automated message from the Media Center Pass System.</p>
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

// Send daily summary to teachers
function sendTeacherDailySummary($pdo, $teacherEmail) {
    $today = date('Y-m-d');
    
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
    
    // Check if PHPMailer is available
    if (!class_exists('PHPMailer\PHPMailer\PHPMailer')) {
        // Fallback to native mail()
        $subject = "Daily Media Center Pass Summary - $today";
        $message = "<html><body>";
        $message .= "<h2>Media Center Pass Summary for $today</h2>";
        $message .= "<table border='1' cellpadding='10'>";
        $message .= "<tr><th>Student Name</th><th>Mod</th><th>Status</th></tr>";
        
        foreach ($passes as $pass) {
            $status = ucfirst($pass['status']);
            $message .= "<tr><td>{$pass['first_name']} {$pass['last_name']}</td><td>{$pass['mod']}</td><td>$status</td></tr>";
        }
        
        $message .= "</table></body></html>";
        $headers = "MIME-Version: 1.0" . "\r\n";
        $headers .= "Content-type: text/html; charset=UTF-8" . "\r\n";
        return mail($teacherEmail, $subject, $message, $headers);
    }
    
    // Use PHPMailer with Gmail SMTP
    $mail = new PHPMailer(true);
    
    try {
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
                <hr style='margin-top: 30px; border: none; border-top: 1px solid #ddd;'>
                <p style='color: #999; font-size: 0.8em;'>This is an automated message from the Media Center Pass System.</p>
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

// Verify librarian session
function requireAdmin() {
    if (!isset($_SESSION['librarian_id'])) {
        header('Location: login.php');
        exit;
    }
}

// Log admin actions
function logAdminAction($pdo, $adminId, $action, $details) {
    $stmt = $pdo->prepare("
        INSERT INTO admin_logs (admin_id, action, details, ip_address)
        VALUES (?, ?, ?, ?)
    ");
    $stmt->execute([$adminId, $action, $details, $_SERVER['REMOTE_ADDR'] ?? 'unknown']);
}

?>
