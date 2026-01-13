<?php
// Database configuration
define('DB_HOST', 'localhost');
define('DB_USER', 'admin');
define('DB_PASS', 'D$2w@kQx*s-Ripped120');
define('DB_NAME', 'media_center');

// Application settings
define('SITE_NAME', 'Media Center Pass System');
define('ADMIN_EMAIL', 'librarian@school.local');

// Session configuration
ini_set('session.use_strict_mode', 1);
ini_set('session.use_only_cookies', 1);

// Error reporting
error_reporting(E_ALL);
ini_set('display_errors', 1);

// Start session if not already started
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

// Database connection
try {
    $pdo = new PDO(
        'mysql:host=' . DB_HOST . ';dbname=' . DB_NAME . ';charset=utf8mb4',
        DB_USER,
        DB_PASS,
        [
            PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
            PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
            PDO::ATTR_EMULATE_PREPARES => false,
        ]
    );
} catch (PDOException $e) {
    die('Database connection failed: ' . $e->getMessage());
}
?>
