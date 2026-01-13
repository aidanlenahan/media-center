<?php
require_once 'includes/config.php';

try {
    // Add new columns to settings table (ignore errors if columns already exist)
    try {
        $pdo->exec("ALTER TABLE settings ADD COLUMN form_status_override BOOLEAN DEFAULT 0");
        echo "Added form_status_override column<br>";
    } catch (Exception $e) {
        echo "form_status_override column already exists or error: " . $e->getMessage() . "<br>";
    }
    
    try {
        $pdo->exec("ALTER TABLE settings ADD COLUMN form_status_manual ENUM('open', 'closed') DEFAULT 'open'");
        echo "Added form_status_manual column<br>";
    } catch (Exception $e) {
        echo "form_status_manual column already exists or error: " . $e->getMessage() . "<br>";
    }
    
    try {
        $pdo->exec("ALTER TABLE settings ADD COLUMN recent_entries_limit INT DEFAULT 10");
        echo "Added recent_entries_limit column<br>";
    } catch (Exception $e) {
        echo "recent_entries_limit column already exists or error: " . $e->getMessage() . "<br>";
    }
    
    try {
        $pdo->exec("ALTER TABLE settings ADD COLUMN disable_weekends BOOLEAN DEFAULT 0");
        echo "Added disable_weekends column<br>";
    } catch (Exception $e) {
        echo "disable_weekends column already exists or error: " . $e->getMessage() . "<br>";
    }
    
    echo "<br>Database update completed!<br>";
    echo "<a href='dashboard.php'>Go to Dashboard</a>";
} catch (Exception $e) {
    echo "Error: " . $e->getMessage();
}
?>
