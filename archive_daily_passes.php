<?php
/**
 * Archive script - Move yesterday's passes to archive and reset current table
 * Run this daily at midnight or beginning of day
 * 
 * Usage: php archive_daily_passes.php
 * Or set up a cron job: 0 0 * * * /usr/bin/php /path/to/archive_daily_passes.php
 */

require_once 'includes/config.php';

$yesterday = date('Y-m-d', strtotime('-1 day'));

try {
    // Begin transaction
    $pdo->beginTransaction();
    
    // Get passes from yesterday to archive
    $getStmt = $pdo->prepare("
        SELECT * FROM passes_current 
        WHERE DATE(created_at) = ?
    ");
    $getStmt->execute([$yesterday]);
    $passes = $getStmt->fetchAll();
    
    $count = count($passes);
    
    if ($count > 0) {
        // Insert into archive
        $archiveStmt = $pdo->prepare("
            INSERT INTO passes_archive 
            (first_name, last_name, email, teacher_name, mod, activities, agreement_checked, status, pass_code, sent_at, pass_date, created_at, updated_at)
            SELECT first_name, last_name, email, teacher_name, mod, activities, agreement_checked, status, pass_code, sent_at, ?, created_at, updated_at
            FROM passes_current 
            WHERE DATE(created_at) = ?
        ");
        $archiveStmt->execute([$yesterday, $yesterday]);
        
        // Delete from current
        $deleteStmt = $pdo->prepare("
            DELETE FROM passes_current 
            WHERE DATE(created_at) = ?
        ");
        $deleteStmt->execute([$yesterday]);
    }
    
    $pdo->commit();
    
    echo "=== Daily Archive Complete ===\n";
    echo "Passes archived from $yesterday: $count\n";
    echo "Timestamp: " . date('Y-m-d H:i:s') . "\n";
    
} catch (Exception $e) {
    $pdo->rollBack();
    echo "Error: " . $e->getMessage() . "\n";
    exit(1);
}
?>
