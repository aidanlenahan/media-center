<?php
/**
 * Database Verification Checker
 * Run this in browser to verify database schema is correct
 * URL: http://localhost/media-center/public/check_database.php
 */

require_once '../includes/config.php';

$checks = [];
$allPassed = true;

// Check 1: Database connection
try {
    $test = $pdo->query("SELECT 1");
    $checks['Database Connection'] = [
        'status' => 'PASS',
        'message' => 'Successfully connected to database: ' . DB_NAME
    ];
} catch (Exception $e) {
    $checks['Database Connection'] = [
        'status' => 'FAIL',
        'message' => 'Connection failed: ' . $e->getMessage()
    ];
    $allPassed = false;
}

// Check 2: Settings table exists
try {
    $result = $pdo->query("SELECT COUNT(*) FROM settings");
    $checks['Settings Table'] = [
        'status' => 'PASS',
        'message' => 'Settings table exists with ' . $result->fetchColumn() . ' record(s)'
    ];
} catch (Exception $e) {
    $checks['Settings Table'] = [
        'status' => 'FAIL',
        'message' => 'Table missing or error: ' . $e->getMessage()
    ];
    $allPassed = false;
}

// Check 3: Librarians table exists
try {
    $result = $pdo->query("SELECT COUNT(*) FROM librarians");
    $count = $result->fetchColumn();
    $checks['Librarians Table'] = [
        'status' => 'PASS',
        'message' => 'Librarians table exists with ' . $count . ' account(s)'
    ];
} catch (Exception $e) {
    $checks['Librarians Table'] = [
        'status' => 'FAIL',
        'message' => 'Table missing or error: ' . $e->getMessage()
    ];
    $allPassed = false;
}

// Check 4: Passes current table exists and has MOD column with backticks
try {
    $result = $pdo->query("DESCRIBE passes_current");
    $columns = $result->fetchAll();
    $hasModColumn = false;
    
    foreach ($columns as $col) {
        if ($col['Field'] === 'mod') {
            $hasModColumn = true;
            break;
        }
    }
    
    if ($hasModColumn) {
        $checks['Passes Current Table'] = [
            'status' => 'PASS',
            'message' => 'passes_current table exists with `mod` column properly defined'
        ];
    } else {
        throw new Exception('mod column not found');
    }
} catch (Exception $e) {
    $checks['Passes Current Table'] = [
        'status' => 'FAIL',
        'message' => 'Table missing or mod column error: ' . $e->getMessage()
    ];
    $allPassed = false;
}

// Check 5: Passes archive table exists
try {
    $result = $pdo->query("DESCRIBE passes_archive");
    $columns = $result->fetchAll();
    $hasModColumn = false;
    
    foreach ($columns as $col) {
        if ($col['Field'] === 'mod') {
            $hasModColumn = true;
            break;
        }
    }
    
    if ($hasModColumn) {
        $checks['Passes Archive Table'] = [
            'status' => 'PASS',
            'message' => 'passes_archive table exists with `mod` column properly defined'
        ];
    } else {
        throw new Exception('mod column not found');
    }
} catch (Exception $e) {
    $checks['Passes Archive Table'] = [
        'status' => 'FAIL',
        'message' => 'Table missing or mod column error: ' . $e->getMessage()
    ];
    $allPassed = false;
}

// Check 6: Test INSERT query with backticks
try {
    $testStmt = $pdo->prepare("
        INSERT INTO passes_current 
        (first_name, last_name, email, teacher_name, `mod`, activities, agreement_checked, status, pass_code)
        VALUES (?, ?, ?, ?, ?, ?, 1, ?, ?)
    ");
    // Don't actually execute, just check if it can be prepared
    $checks['INSERT Query (backticks)'] = [
        'status' => 'PASS',
        'message' => 'INSERT statement with backticked `mod` column prepared successfully'
    ];
} catch (Exception $e) {
    $checks['INSERT Query (backticks)'] = [
        'status' => 'FAIL',
        'message' => 'Query preparation failed: ' . $e->getMessage()
    ];
    $allPassed = false;
}

// Check 7: Test SELECT query with backticks
try {
    $testStmt = $pdo->prepare("
        SELECT id, email, first_name, last_name, pass_code, `mod`, activities 
        FROM passes_current 
        WHERE status = 'pending'
    ");
    $checks['SELECT Query (backticks)'] = [
        'status' => 'PASS',
        'message' => 'SELECT statement with backticked `mod` column prepared successfully'
    ];
} catch (Exception $e) {
    $checks['SELECT Query (backticks)'] = [
        'status' => 'FAIL',
        'message' => 'Query preparation failed: ' . $e->getMessage()
    ];
    $allPassed = false;
}

// Check 8: Test ORDER BY with backticks
try {
    $testStmt = $pdo->prepare("
        SELECT first_name, last_name, `mod`, status 
        FROM passes_current 
        WHERE teacher_name = ? 
        ORDER BY `mod` ASC
    ");
    $checks['ORDER BY Query (backticks)'] = [
        'status' => 'PASS',
        'message' => 'ORDER BY statement with backticked `mod` column prepared successfully'
    ];
} catch (Exception $e) {
    $checks['ORDER BY Query (backticks)'] = [
        'status' => 'FAIL',
        'message' => 'Query preparation failed: ' . $e->getMessage()
    ];
    $allPassed = false;
}

// Check 9: Admin user exists
try {
    $stmt = $pdo->prepare("SELECT username, email FROM librarians WHERE username = ?");
    $stmt->execute(['admin']);
    $admin = $stmt->fetch();
    
    if ($admin) {
        $checks['Default Admin Account'] = [
            'status' => 'PASS',
            'message' => 'Admin user exists: ' . htmlspecialchars($admin['username']) . ' (' . htmlspecialchars($admin['email']) . ')'
        ];
    } else {
        throw new Exception('Admin user not found');
    }
} catch (Exception $e) {
    $checks['Default Admin Account'] = [
        'status' => 'FAIL',
        'message' => 'Check failed: ' . $e->getMessage()
    ];
    $allPassed = false;
}

// Check 10: Form can be opened
try {
    $stmt = $pdo->query("SELECT form_auto_open, form_open_time, form_close_time FROM settings LIMIT 1");
    $settings = $stmt->fetch();
    
    if ($settings) {
        $autoOpen = $settings['form_auto_open'] ? 'Enabled' : 'Disabled';
        $checks['Form Settings'] = [
            'status' => 'PASS',
            'message' => "Auto-open: $autoOpen | Times: {$settings['form_open_time']} - {$settings['form_close_time']}"
        ];
    } else {
        throw new Exception('No settings found');
    }
} catch (Exception $e) {
    $checks['Form Settings'] = [
        'status' => 'FAIL',
        'message' => 'Settings check failed: ' . $e->getMessage()
    ];
    $allPassed = false;
}

?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Database Verification Checker</title>
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
            max-width: 800px;
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
        
        .overall-status {
            margin-bottom: 30px;
            padding: 20px;
            border-radius: 8px;
            text-align: center;
            font-size: 1.1em;
            font-weight: bold;
        }
        
        .overall-status.pass {
            background: #d4edda;
            color: #155724;
            border: 2px solid #28a745;
        }
        
        .overall-status.fail {
            background: #f8d7da;
            color: #721c24;
            border: 2px solid #dc3545;
        }
        
        .checks-list {
            display: flex;
            flex-direction: column;
            gap: 15px;
        }
        
        .check-item {
            border-left: 5px solid #ddd;
            padding: 15px;
            border-radius: 4px;
            background: #f8f9fa;
        }
        
        .check-item.pass {
            border-left-color: #28a745;
            background: #f0f9f1;
        }
        
        .check-item.fail {
            border-left-color: #dc3545;
            background: #fef5f5;
        }
        
        .check-item-header {
            display: flex;
            align-items: center;
            gap: 10px;
            margin-bottom: 8px;
            font-weight: 600;
            font-size: 1.05em;
        }
        
        .status-badge {
            display: inline-block;
            padding: 4px 12px;
            border-radius: 4px;
            font-size: 0.85em;
            font-weight: bold;
            min-width: 60px;
            text-align: center;
        }
        
        .status-badge.pass {
            background: #28a745;
            color: white;
        }
        
        .status-badge.fail {
            background: #dc3545;
            color: white;
        }
        
        .check-item-message {
            color: #555;
            font-size: 0.9em;
            margin-left: 35px;
            line-height: 1.5;
        }
        
        .icon {
            font-size: 1.2em;
        }
        
        .pass-icon {
            color: #28a745;
        }
        
        .fail-icon {
            color: #dc3545;
        }
        
        .footer {
            margin-top: 30px;
            padding-top: 20px;
            border-top: 1px solid #ddd;
            text-align: center;
            color: #666;
            font-size: 0.9em;
        }
        
        .database-info {
            background: #e7f3ff;
            border-left: 4px solid #2196F3;
            padding: 15px;
            border-radius: 4px;
            margin-bottom: 20px;
            font-size: 0.9em;
            color: #0c3a66;
        }
        
        .refresh-btn {
            display: inline-block;
            margin-top: 20px;
            padding: 10px 20px;
            background: #667eea;
            color: white;
            border: none;
            border-radius: 4px;
            cursor: pointer;
            font-size: 0.95em;
            font-weight: 600;
        }
        
        .refresh-btn:hover {
            background: #764ba2;
            transform: translateY(-2px);
            box-shadow: 0 5px 15px rgba(102, 126, 234, 0.4);
        }
    </style>
</head>
<body>
    <div class="container">
        <div class="header">
            <h1>🔍 Database Verification Checker</h1>
            <p>Media Center Pass System - Database Schema Validation</p>
        </div>
        
        <div class="database-info">
            <strong>Database:</strong> <?php echo htmlspecialchars(DB_NAME); ?> | 
            <strong>Host:</strong> <?php echo htmlspecialchars(DB_HOST); ?>
        </div>
        
        <div class="overall-status <?php echo $allPassed ? 'pass' : 'fail'; ?>">
            <?php if ($allPassed): ?>
                ✅ ALL CHECKS PASSED - Database is properly configured!
            <?php else: ?>
                ❌ SOME CHECKS FAILED - Please review the errors below
            <?php endif; ?>
        </div>
        
        <div class="checks-list">
            <?php foreach ($checks as $checkName => $checkResult): ?>
                <div class="check-item <?php echo strtolower($checkResult['status']); ?>">
                    <div class="check-item-header">
                        <span class="icon <?php echo strtolower($checkResult['status']); ?>-icon">
                            <?php echo $checkResult['status'] === 'PASS' ? '✓' : '✗'; ?>
                        </span>
                        <span><?php echo htmlspecialchars($checkName); ?></span>
                        <span class="status-badge <?php echo strtolower($checkResult['status']); ?>">
                            <?php echo $checkResult['status']; ?>
                        </span>
                    </div>
                    <div class="check-item-message">
                        <?php echo htmlspecialchars($checkResult['message']); ?>
                    </div>
                </div>
            <?php endforeach; ?>
        </div>
        
        <div class="footer">
            <p>Generated on: <?php echo date('Y-m-d H:i:s'); ?></p>
            <button class="refresh-btn" onclick="location.reload();">🔄 Refresh Checks</button>
        </div>
    </div>
</body>
</html>
