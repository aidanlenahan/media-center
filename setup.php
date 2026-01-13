<?php
/**
 * Database Setup & Initialization Script
 * Run this in browser to automatically create all tables and data
 * URL: http://localhost/media-center/public/setup.php
 */

require_once '../includes/config.php';

$setupComplete = false;
$errors = [];
$messages = [];

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action']) && $_POST['action'] === 'initialize') {
    try {
        $pdo->beginTransaction();
        
        // Drop existing tables if they exist (optional - can be skipped)
        // $pdo->exec("DROP TABLE IF EXISTS passes_archive");
        // $pdo->exec("DROP TABLE IF EXISTS passes_current");
        // $pdo->exec("DROP TABLE IF EXISTS librarians");
        // $pdo->exec("DROP TABLE IF EXISTS settings");
        
        // Create settings table if it doesn't exist
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
        $messages[] = "✓ Settings table created/verified";
        
        // Create librarians table if it doesn't exist
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
        $messages[] = "✓ Librarians table created/verified";
        
        // Create passes_current table
        $pdo->exec("
            CREATE TABLE IF NOT EXISTS passes_current (
                id INT PRIMARY KEY AUTO_INCREMENT,
                first_name VARCHAR(50) NOT NULL,
                last_name VARCHAR(50) NOT NULL,
                email VARCHAR(100) NOT NULL,
                teacher_name VARCHAR(100) NOT NULL,
                \`mod\` INT NOT NULL,
                activities TEXT NOT NULL,
                agreement_checked BOOLEAN DEFAULT 0,
                status ENUM('pending', 'approved', 'rejected') DEFAULT 'pending',
                pass_code VARCHAR(20) UNIQUE,
                sent_at TIMESTAMP NULL,
                created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
                updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
            )
        ");
        $messages[] = "✓ Passes Current table created/verified";
        
        // Create passes_archive table
        $pdo->exec("
            CREATE TABLE IF NOT EXISTS passes_archive (
                id INT PRIMARY KEY AUTO_INCREMENT,
                first_name VARCHAR(50) NOT NULL,
                last_name VARCHAR(50) NOT NULL,
                email VARCHAR(100) NOT NULL,
                teacher_name VARCHAR(100) NOT NULL,
                \`mod\` INT NOT NULL,
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
        $messages[] = "✓ Passes Archive table created/verified";
        
        // Check if settings already has data
        $settingsCheck = $pdo->query("SELECT COUNT(*) FROM settings")->fetchColumn();
        if ($settingsCheck == 0) {
            $pdo->exec("
                INSERT INTO settings (form_auto_open, form_open_time, form_close_time, auto_approval) 
                VALUES (1, '07:30:00', '14:30:00', 0)
            ");
            $messages[] = "✓ Default settings inserted";
        } else {
            $messages[] = "ℹ Settings data already exists (skipped insertion)";
        }
        
        // Check if admin user already exists
        $adminCheck = $pdo->query("SELECT COUNT(*) FROM librarians WHERE username = 'admin'")->fetchColumn();
        if ($adminCheck == 0) {
            // Generate proper bcrypt hash for "admin123"
            $adminHash = password_hash('admin123', PASSWORD_BCRYPT);
            $stmt = $pdo->prepare("
                INSERT INTO librarians (username, email, password_hash) 
                VALUES (?, ?, ?)
            ");
            $stmt->execute(['admin', 'librarian@school.local', $adminHash]);
            $messages[] = "✓ Default admin account created (username: admin, password: admin123)";
        } else {
            $messages[] = "ℹ Admin account already exists (skipped creation)";
        }
        
        $pdo->commit();
        $setupComplete = true;
        
    } catch (Exception $e) {
        $pdo->rollBack();
        $errors[] = "Error: " . $e->getMessage();
    }
}

?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Database Setup - Media Center Pass System</title>
    <style>
        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
        }
        
        body {
            font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            min-height: 100vh;
            padding: 20px;
        }
        
        .container {
            max-width: 700px;
            margin: 0 auto;
            background: white;
            border-radius: 8px;
            box-shadow: 0 10px 30px rgba(0, 0, 0, 0.2);
            padding: 40px;
        }
        
        .header {
            text-align: center;
            margin-bottom: 30px;
            border-bottom: 3px solid #667eea;
            padding-bottom: 20px;
        }
        
        .header h1 {
            color: #333;
            font-size: 1.8em;
            margin-bottom: 10px;
        }
        
        .header p {
            color: #666;
            font-size: 0.95em;
        }
        
        .info-box {
            background: #e7f3ff;
            border-left: 4px solid #2196F3;
            padding: 15px;
            border-radius: 4px;
            margin-bottom: 20px;
            color: #0c3a66;
            font-size: 0.9em;
            line-height: 1.6;
        }
        
        .button-group {
            display: flex;
            gap: 10px;
            justify-content: center;
            margin-bottom: 30px;
        }
        
        .btn {
            padding: 12px 30px;
            border: none;
            border-radius: 4px;
            font-size: 1em;
            cursor: pointer;
            font-weight: 600;
            transition: all 0.3s;
        }
        
        .btn-primary {
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            color: white;
        }
        
        .btn-primary:hover {
            transform: translateY(-2px);
            box-shadow: 0 5px 15px rgba(102, 126, 234, 0.4);
        }
        
        .btn-secondary {
            background: #95a5a6;
            color: white;
        }
        
        .btn-secondary:hover {
            background: #7f8c8d;
        }
        
        .messages {
            margin-bottom: 20px;
        }
        
        .message {
            padding: 12px 15px;
            border-radius: 4px;
            margin-bottom: 10px;
            font-size: 0.95em;
        }
        
        .message.success {
            background: #d4edda;
            color: #155724;
            border-left: 4px solid #28a745;
        }
        
        .message.info {
            background: #d1ecf1;
            color: #0c5460;
            border-left: 4px solid #17a2b8;
        }
        
        .message.error {
            background: #f8d7da;
            color: #721c24;
            border-left: 4px solid #dc3545;
        }
        
        .status {
            text-align: center;
            padding: 20px;
            border-radius: 8px;
            margin-bottom: 20px;
            font-size: 1.1em;
            font-weight: bold;
        }
        
        .status.success {
            background: #d4edda;
            color: #155724;
            border: 2px solid #28a745;
        }
        
        .status.error {
            background: #f8d7da;
            color: #721c24;
            border: 2px solid #dc3545;
        }
        
        .next-steps {
            background: #f8f9fa;
            border-left: 4px solid #667eea;
            padding: 15px;
            border-radius: 4px;
            margin-top: 20px;
        }
        
        .next-steps h3 {
            color: #333;
            margin-bottom: 10px;
            font-size: 1em;
        }
        
        .next-steps ol {
            margin-left: 20px;
            color: #555;
            font-size: 0.9em;
            line-height: 1.8;
        }
        
        .next-steps li {
            margin-bottom: 8px;
        }
        
        .link-list {
            display: flex;
            flex-direction: column;
            gap: 8px;
            margin-top: 15px;
        }
        
        .link-list a {
            display: inline-block;
            padding: 8px 15px;
            background: #667eea;
            color: white;
            text-decoration: none;
            border-radius: 4px;
            font-size: 0.9em;
            transition: background 0.3s;
        }
        
        .link-list a:hover {
            background: #764ba2;
        }
    </style>
</head>
<body>
    <div class="container">
        <div class="header">
            <h1>⚙️ Database Setup</h1>
            <p>Media Center Pass System - Initialize Database</p>
        </div>
        
        <?php if (!$setupComplete): ?>
            <div class="info-box">
                <strong>ℹ️ About this setup:</strong><br>
                This script will create all necessary database tables and insert default data. It's safe to run multiple times - existing data won't be overwritten.
            </div>
            
            <div class="button-group">
                <form method="POST" style="width: 100%;">
                    <input type="hidden" name="action" value="initialize">
                    <button type="submit" class="btn btn-primary" style="width: 100%;">
                        🚀 Initialize Database Now
                    </button>
                </form>
            </div>
            
            <div class="button-group">
                <a href="check_database.php" class="btn btn-secondary" style="text-decoration: none; display: inline-block;">
                    🔍 Check Current Status
                </a>
            </div>
        <?php else: ?>
            <?php if (empty($errors)): ?>
                <div class="status success">
                    ✅ Database Setup Complete!
                </div>
                
                <?php if (!empty($messages)): ?>
                    <div class="messages">
                        <?php foreach ($messages as $msg): ?>
                            <div class="message success">
                                <?php echo htmlspecialchars($msg); ?>
                            </div>
                        <?php endforeach; ?>
                    </div>
                <?php endif; ?>
                
                <div class="next-steps">
                    <h3>✓ Next Steps:</h3>
                    <ol>
                        <li><strong>Verify Setup:</strong> Click the button below to verify all tables and data</li>
                        <li><strong>Admin Login:</strong> Use username <code>admin</code> and password <code>admin123</code></li>
                        <li><strong>Change Password:</strong> Log in and update your admin password immediately</li>
                        <li><strong>Configure Settings:</strong> Adjust form times and approval settings in the dashboard</li>
                    </ol>
                    
                    <div class="link-list">
                        <a href="check_database.php">🔍 Verify Database Setup</a>
                        <a href="login.php">🔐 Go to Admin Login</a>
                        <a href="index.php">📋 Go to Student Form</a>
                    </div>
                </div>
            <?php else: ?>
                <div class="status error">
                    ❌ Setup Failed
                </div>
                
                <div class="messages">
                    <?php foreach ($errors as $error): ?>
                        <div class="message error">
                            <?php echo htmlspecialchars($error); ?>
                        </div>
                    <?php endforeach; ?>
                    
                    <?php foreach ($messages as $msg): ?>
                        <div class="message success">
                            <?php echo htmlspecialchars($msg); ?>
                        </div>
                    <?php endforeach; ?>
                </div>
                
                <div class="button-group">
                    <form method="POST">
                        <input type="hidden" name="action" value="initialize">
                        <button type="submit" class="btn btn-primary">🔄 Try Again</button>
                    </form>
                </div>
            <?php endif; ?>
        <?php endif; ?>
    </div>
</body>
</html>
