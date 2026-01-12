<?php

// Generate unique pass code
function generatePassCode() {
    return strtoupper(bin2hex(random_bytes(6)));
}

// Check if form is currently open
function isFormOpen($pdo) {
    $stmt = $pdo->query("SELECT form_auto_open, form_open_time, form_close_time FROM settings LIMIT 1");
    $settings = $stmt->fetch();
    
    if (!$settings['form_auto_open']) {
        return false;
    }
    
    $currentTime = date('H:i:s');
    $openTime = $settings['form_open_time'];
    $closeTime = $settings['form_close_time'];
    
    return $currentTime >= $openTime && $currentTime <= $closeTime;
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

// Verify librarian session
function requireAdmin() {
    if (!isset($_SESSION['librarian_id'])) {
        header('Location: admin_login.php');
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
