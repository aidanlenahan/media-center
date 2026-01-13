<?php
/**
 * Auto Database Setup Script
 * This file will automatically create the database and all necessary tables
 * Open this file in a web browser to run the setup
 */

// Configuration
define('DB_HOST', 'localhost');
define('DB_USER', 'root');
define('DB_PASS', '');
define('DB_NAME', 'media_center');

// Set output formatting
header('Content-Type: text/html; charset=utf-8');
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Database Setup - Media Center</title>
    <style>
        body {
            font-family: Arial, sans-serif;
            max-width: 800px;
            margin: 50px auto;
            padding: 20px;
            background-color: #f5f5f5;
        }
        .container {
            background: white;
            padding: 30px;
            border-radius: 8px;
            box-shadow: 0 2px 4px rgba(0,0,0,0.1);
        }
        h1 {
            color: #333;
            border-bottom: 3px solid #4CAF50;
            padding-bottom: 10px;
        }
        .success {
            color: #4CAF50;
            background: #e8f5e9;
            padding: 10px;
            border-left: 4px solid #4CAF50;
            margin: 10px 0;
        }
        .error {
            color: #f44336;
            background: #ffebee;
            padding: 10px;
            border-left: 4px solid #f44336;
            margin: 10px 0;
        }
        .info {
            color: #2196F3;
            background: #e3f2fd;
            padding: 10px;
            border-left: 4px solid #2196F3;
            margin: 10px 0;
        }
        .step {
            margin: 15px 0;
            padding: 10px;
            background: #fafafa;
            border-radius: 4px;
        }
        .btn {
            display: inline-block;
            padding: 10px 20px;
            background: #4CAF50;
            color: white;
            text-decoration: none;
            border-radius: 4px;
            margin-top: 20px;
        }
        .btn:hover {
            background: #45a049;
        }
    </style>
</head>
<body>
    <div class="container">
        <h1>Media Center Database Setup</h1>
        
        <?php
        try {
            echo "<div class='info'>Starting database setup...</div>";
            
            // Step 1: Connect to MySQL without database
            echo "<div class='step'><strong>Step 1:</strong> Connecting to MySQL server...</div>";
            $pdo = new PDO(
                'mysql:host=' . DB_HOST . ';charset=utf8mb4',
                DB_USER,
                DB_PASS,
                [
                    PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
                    PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
                ]
            );
            echo "<div class='success'>✓ Connected to MySQL server successfully</div>";
            
            // Step 2: Create database
            echo "<div class='step'><strong>Step 2:</strong> Creating database...</div>";
            $pdo->exec("CREATE DATABASE IF NOT EXISTS " . DB_NAME . " CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci");
            echo "<div class='success'>✓ Database '" . DB_NAME . "' created or already exists</div>";
            
            // Step 3: Select database
            echo "<div class='step'><strong>Step 3:</strong> Selecting database...</div>";
            $pdo->exec("USE " . DB_NAME);
            echo "<div class='success'>✓ Using database '" . DB_NAME . "'</div>";
            
            // Step 4: Create settings table
            echo "<div class='step'><strong>Step 4:</strong> Creating settings table...</div>";
            $pdo->exec("
                CREATE TABLE IF NOT EXISTS settings (
                    id INT PRIMARY KEY AUTO_INCREMENT,
                    form_auto_open BOOLEAN DEFAULT 0,
                    form_open_time TIME,
                    form_close_time TIME,
                    auto_approval BOOLEAN DEFAULT 0,
                    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
                    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
                )
            ");
            echo "<div class='success'>✓ Settings table created</div>";
            
            // Step 5: Create librarians table
            echo "<div class='step'><strong>Step 5:</strong> Creating librarians table...</div>";
            $pdo->exec("
                CREATE TABLE IF NOT EXISTS librarians (
                    id INT PRIMARY KEY AUTO_INCREMENT,
                    username VARCHAR(100) UNIQUE NOT NULL,
                    password_hash VARCHAR(255) NOT NULL,
                    email VARCHAR(100) NOT NULL,
                    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
                    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
                )
            ");
            echo "<div class='success'>✓ Librarians table created</div>";
            
            // Step 6: Create passes_current table
            echo "<div class='step'><strong>Step 6:</strong> Creating passes_current table...</div>";
            $pdo->exec("
                CREATE TABLE IF NOT EXISTS passes_current (
                    id INT PRIMARY KEY AUTO_INCREMENT,
                    first_name VARCHAR(50) NOT NULL,
                    last_name VARCHAR(50) NOT NULL,
                    email VARCHAR(100) NOT NULL,
                    teacher_name VARCHAR(100) NOT NULL,
                    `mod` INT NOT NULL,
                    activities TEXT NOT NULL,
                    agreement_checked BOOLEAN DEFAULT 0,
                    status ENUM('pending', 'approved', 'rejected') DEFAULT 'pending',
                    pass_code VARCHAR(20) UNIQUE,
                    sent_at TIMESTAMP NULL,
                    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
                    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
                )
            ");
            echo "<div class='success'>✓ Passes_current table created</div>";
            
            // Step 7: Create passes_archive table
            echo "<div class='step'><strong>Step 7:</strong> Creating passes_archive table...</div>";
            $pdo->exec("
                CREATE TABLE IF NOT EXISTS passes_archive (
                    id INT PRIMARY KEY AUTO_INCREMENT,
                    first_name VARCHAR(50) NOT NULL,
                    last_name VARCHAR(50) NOT NULL,
                    email VARCHAR(100) NOT NULL,
                    teacher_name VARCHAR(100) NOT NULL,
                    `mod` INT NOT NULL,
                    activities TEXT NOT NULL,
                    agreement_checked BOOLEAN DEFAULT 0,
                    status ENUM('pending', 'approved', 'rejected') DEFAULT 'pending',
                    pass_code VARCHAR(20) UNIQUE,
                    sent_at TIMESTAMP NULL,
                    pass_date DATE NOT NULL,
                    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
                    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
                    INDEX (pass_date),
                    INDEX (first_name),
                    INDEX (last_name)
                )
            ");
            echo "<div class='success'>✓ Passes_archive table created</div>";
            
            // Step 8: Insert default settings if not exists
            echo "<div class='step'><strong>Step 8:</strong> Inserting default settings...</div>";
            $checkSettings = $pdo->query("SELECT COUNT(*) as count FROM settings");
            $settingsCount = $checkSettings->fetch()['count'];
            
            if ($settingsCount == 0) {
                $pdo->exec("
                    INSERT INTO settings (form_auto_open, form_open_time, form_close_time, auto_approval) 
                    VALUES (1, '07:30:00', '14:30:00', 0)
                ");
                echo "<div class='success'>✓ Default settings inserted</div>";
            } else {
                echo "<div class='info'>ℹ Settings already exist, skipping insert</div>";
            }
            
            // Step 9: Insert default admin if not exists
            echo "<div class='step'><strong>Step 9:</strong> Creating default admin account...</div>";
            $checkAdmin = $pdo->query("SELECT COUNT(*) as count FROM librarians WHERE username = 'admin'");
            $adminCount = $checkAdmin->fetch()['count'];
            
            if ($adminCount == 0) {
                // Create password hash for 'admin123'
                $passwordHash = password_hash('admin123', PASSWORD_DEFAULT);
                $stmt = $pdo->prepare("
                    INSERT INTO librarians (username, email, password_hash) 
                    VALUES ('admin', 'librarian@school.local', :password_hash)
                ");
                $stmt->execute(['password_hash' => $passwordHash]);
                echo "<div class='success'>✓ Default admin account created</div>";
                echo "<div class='info'>
                    <strong>Default Login Credentials:</strong><br>
                    Username: admin<br>
                    Password: admin123<br>
                    <em>Please change this password after first login!</em>
                </div>";
            } else {
                echo "<div class='info'>ℹ Admin account already exists, skipping insert</div>";
            }
            
            // Final success message
            echo "<div class='success' style='margin-top: 20px; font-size: 18px;'>
                <strong>✓ Database setup completed successfully!</strong>
            </div>";
            
            echo "<div class='info' style='margin-top: 20px;'>
                <strong>Next Steps:</strong><br>
                1. Navigate to the admin login page to start using the system<br>
                2. Change the default admin password<br>
                3. Configure your form settings in the dashboard
            </div>";
            
            echo "<a href='form.php' class='btn'>Go to Home Page</a>";
            echo "<a href='login.php' class='btn' style='background: #2196F3; margin-left: 10px;'>Go to Admin Login</a>";
            
        } catch (PDOException $e) {
            echo "<div class='error'><strong>Database Error:</strong> " . htmlspecialchars($e->getMessage()) . "</div>";
            echo "<div class='info'>
                <strong>Troubleshooting:</strong><br>
                • Make sure MySQL/MariaDB server is running (WAMP should be started)<br>
                • Check that the database credentials are correct (DB_USER and DB_PASS)<br>
                • Verify that the user has CREATE DATABASE permissions<br>
                • Check if another process is using the database
            </div>";
        } catch (Exception $e) {
            echo "<div class='error'><strong>Error:</strong> " . htmlspecialchars($e->getMessage()) . "</div>";
        }
        ?>
        
    </div>
</body>
</html>
