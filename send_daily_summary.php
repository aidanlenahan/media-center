<?php
/**
 * Cron job or scheduled task to send daily teacher summaries
 * Run this daily at end of school day
 * 
 * Usage: php send_daily_summary.php
 * Or set up a cron job: 0 15 * * 1-5 /usr/bin/php /path/to/send_daily_summary.php
 */

require_once 'includes/config.php';
require_once 'includes/functions.php';

$today = date('Y-m-d');

try {
    // Get all unique teachers with passes today
    $stmt = $pdo->query("
        SELECT DISTINCT teacher_name 
        FROM passes_current 
        WHERE DATE(created_at) = '$today'
        ORDER BY teacher_name
    ");
    
    $teachers = $stmt->fetchAll();
    $sentCount = 0;
    $failCount = 0;
    
    foreach ($teachers as $teacher) {
        $teacherName = $teacher['teacher_name'];
        
        // Get this teacher's passes for today
        $passStmt = $pdo->prepare("
            SELECT first_name, last_name, `mod`, status 
            FROM passes_current 
            WHERE teacher_name = ? AND DATE(created_at) = ?
            ORDER BY `mod` ASC, last_name ASC
        ");
        $passStmt->execute([$teacherName, $today]);
        $passes = $passStmt->fetchAll();
        
        if (empty($passes)) {
            continue;
        }
        
        // Build email
        $subject = "Media Center Pass Summary - $today";
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
                <p><strong>Teacher:</strong> " . htmlspecialchars($teacherName) . "</p>
                
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
            
            $message .= "
                    <tr>
                        <td>" . htmlspecialchars($pass['first_name'] . ' ' . $pass['last_name']) . "</td>
                        <td>" . $pass['mod'] . "</td>
                        <td><span class='$statusClass'>$status</span></td>
                    </tr>";
            
            if ($pass['status'] === 'approved') $approvedCount++;
            elseif ($pass['status'] === 'pending') $pendingCount++;
            else $rejectedCount++;
        }
        
        $message .= "
                </table>
                
                <p style='margin-top: 20px; color: #666;'>
                    <strong>Summary:</strong><br>
                    Approved: $approvedCount<br>
                    Pending: $pendingCount<br>
                    Rejected: $rejectedCount
                </p>
                
                <p style='margin-top: 30px; font-size: 0.9em; color: #666;'>
                    This is an automated message from the Media Center Pass System.
                </p>
            </body>
        </html>";
        
        $headers = "MIME-Version: 1.0\r\n";
        $headers .= "Content-type: text/html; charset=UTF-8\r\n";
        $headers .= "From: " . ADMIN_EMAIL . "\r\n";
        
        // Attempt to send email
        if (mail($teacherName . "@school.local", $subject, $message, $headers)) {
            $sentCount++;
            echo "[OK] Email sent to $teacherName\n";
        } else {
            $failCount++;
            echo "[FAIL] Failed to send to $teacherName\n";
        }
    }
    
    echo "\n=== Daily Summary Complete ===\n";
    echo "Emails sent: $sentCount\n";
    echo "Emails failed: $failCount\n";
    echo "Timestamp: " . date('Y-m-d H:i:s') . "\n";
    
} catch (Exception $e) {
    echo "Error: " . $e->getMessage() . "\n";
    exit(1);
}
?>
