<?php
/**
 * Database Verification Tool
 * Checks if all tables, columns, and data exist correctly
 */

require_once 'includes/config.php';

$checks = [];

// Check 1: Settings table exists
try {
    $pdo->query("SELECT COUNT(*) FROM settings");
    $checks[] = ['name' => 'Settings table exists', 'status' => 'success'];
} catch (Exception $e) {
    $checks[] = ['name' => 'Settings table exists', 'status' => 'error', 'message' => $e->getMessage()];
}

// Check 2: Librarians table exists with role column
try {
    $result = $pdo->query("SELECT id, username, role FROM librarians LIMIT 1");
    $checks[] = ['name' => 'Librarians table exists with role column', 'status' => 'success'];
} catch (Exception $e) {
    $checks[] = ['name' => 'Librarians table exists with role column', 'status' => 'error', 'message' => $e->getMessage()];
}

// Check 3: Passes_current table exists
try {
    $pdo->query("SELECT COUNT(*) FROM passes_current");
    $checks[] = ['name' => 'Passes_current table exists', 'status' => 'success'];
} catch (Exception $e) {
    $checks[] = ['name' => 'Passes_current table exists', 'status' => 'error', 'message' => $e->getMessage()];
}

// Check 4: Passes_archive table exists
try {
    $pdo->query("SELECT COUNT(*) FROM passes_archive");
    $checks[] = ['name' => 'Passes_archive table exists', 'status' => 'success'];
} catch (Exception $e) {
    $checks[] = ['name' => 'Passes_archive table exists', 'status' => 'error', 'message' => $e->getMessage()];
}

// Check 5: Dev_settings table exists
try {
    $pdo->query("SELECT COUNT(*) FROM dev_settings");
    $checks[] = ['name' => 'Dev_settings table exists', 'status' => 'success'];
} catch (Exception $e) {
    $checks[] = ['name' => 'Dev_settings table exists', 'status' => 'error', 'message' => $e->getMessage()];
}

// Check 6: Mod column with backticks works
try {
    $pdo->query("SELECT `mod` FROM passes_current LIMIT 1");
    $checks[] = ['name' => 'Mod column query (with backticks)', 'status' => 'success'];
} catch (Exception $e) {
    $checks[] = ['name' => 'Mod column query (with backticks)', 'status' => 'error', 'message' => $e->getMessage()];
}

// Check 7: Admin account exists
try {
    $result = $pdo->query("SELECT COUNT(*) FROM librarians WHERE username = 'admin'")->fetchColumn();
    if ($result > 0) {
        $checks[] = ['name' => 'Admin account exists', 'status' => 'success'];
    } else {
        $checks[] = ['name' => 'Admin account exists', 'status' => 'warning', 'message' => 'No admin account found'];
    }
} catch (Exception $e) {
    $checks[] = ['name' => 'Admin account exists', 'status' => 'error', 'message' => $e->getMessage()];
}

// Check 8: Root user exists
try {
    $result = $pdo->query("SELECT COUNT(*) FROM librarians WHERE username = 'root' AND role = 'root'")->fetchColumn();
    if ($result > 0) {
        $checks[] = ['name' => 'Root user exists with root role', 'status' => 'success'];
    } else {
        $checks[] = ['name' => 'Root user exists with root role', 'status' => 'warning', 'message' => 'No root user found'];
    }
} catch (Exception $e) {
    $checks[] = ['name' => 'Root user exists with root role', 'status' => 'error', 'message' => $e->getMessage()];
}

// Check 9: Settings has data
try {
    $result = $pdo->query("SELECT COUNT(*) FROM settings")->fetchColumn();
    if ($result > 0) {
        $checks[] = ['name' => 'Settings table has data', 'status' => 'success'];
    } else {
        $checks[] = ['name' => 'Settings table has data', 'status' => 'warning', 'message' => 'Settings table is empty'];
    }
} catch (Exception $e) {
    $checks[] = ['name' => 'Settings table has data', 'status' => 'error', 'message' => $e->getMessage()];
}

// Check 10: Dev_settings has data
try {
    $result = $pdo->query("SELECT COUNT(*) FROM dev_settings")->fetchColumn();
    if ($result > 0) {
        $checks[] = ['name' => 'Dev_settings table has data', 'status' => 'success'];
    } else {
        $checks[] = ['name' => 'Dev_settings table has data', 'status' => 'warning', 'message' => 'Dev_settings table is empty'];
    }
} catch (Exception $e) {
    $checks[] = ['name' => 'Dev_settings table has data', 'status' => 'error', 'message' => $e->getMessage()];
}

// Count results
$successCount = count(array_filter($checks, fn($c) => $c['status'] === 'success'));
$warningCount = count(array_filter($checks, fn($c) => $c['status'] === 'warning'));
$errorCount = count(array_filter($checks, fn($c) => $c['status'] === 'error'));
$totalChecks = count($checks);
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Database Status Check</title>
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
            padding: 40px 20px;
        }
        
        .container {
            max-width: 900px;
            margin: 0 auto;
            background: white;
            padding: 40px;
            border-radius: 12px;
            box-shadow: 0 10px 40px rgba(0,0,0,0.2);
        }
        
        h1 {
            color: #333;
            margin-bottom: 10px;
        }
        
        .summary {
            display: flex;
            gap: 20px;
            margin: 30px 0;
            padding: 20px;
            background: #f8f9fa;
            border-radius: 8px;
        }
        
        .summary-item {
            flex: 1;
            text-align: center;
        }
        
        .summary-number {
            font-size: 2em;
            font-weight: bold;
        }
        
        .summary-label {
            color: #666;
            margin-top: 5px;
        }
        
        .success-number { color: #28a745; }
        .warning-number { color: #ffc107; }
        .error-number { color: #dc3545; }
        
        .check-item {
            padding: 15px;
            margin: 10px 0;
            border-radius: 8px;
            border-left: 4px solid;
            background: #f8f9fa;
        }
        
        .check-item.success {
            border-left-color: #28a745;
            background: #d4edda;
        }
        
        .check-item.warning {
            border-left-color: #ffc107;
            background: #fff3cd;
        }
        
        .check-item.error {
            border-left-color: #dc3545;
            background: #f8d7da;
        }
        
        .check-name {
            font-weight: bold;
            margin-bottom: 5px;
        }
        
        .check-message {
            color: #666;
            font-size: 0.9em;
        }
        
        .badge {
            display: inline-block;
            padding: 4px 12px;
            border-radius: 12px;
            font-size: 0.8em;
            font-weight: bold;
            margin-left: 10px;
        }
        
        .badge.success {
            background: #28a745;
            color: white;
        }
        
        .badge.warning {
            background: #ffc107;
            color: #333;
        }
        
        .badge.error {
            background: #dc3545;
            color: white;
        }
        
        .actions {
            margin-top: 30px;
            padding-top: 20px;
            border-top: 2px solid #e9ecef;
        }
        
        .btn {
            display: inline-block;
            padding: 12px 24px;
            background: #667eea;
            color: white;
            text-decoration: none;
            border-radius: 6px;
            margin-right: 10px;
            transition: background 0.3s;
        }
        
        .btn:hover {
            background: #5568d3;
        }
        
        .btn-secondary {
            background: #6c757d;
        }
        
        .btn-secondary:hover {
            background: #5a6268;
        }
    </style>
</head>
<body>
    <div class="container">
        <h1>🔍 Database Status Check</h1>
        <p style="color: #666; margin-bottom: 20px;">Comprehensive verification of all database tables and data</p>
        
        <div class="summary">
            <div class="summary-item">
                <div class="summary-number success-number"><?php echo $successCount; ?></div>
                <div class="summary-label">Passed</div>
            </div>
            <div class="summary-item">
                <div class="summary-number warning-number"><?php echo $warningCount; ?></div>
                <div class="summary-label">Warnings</div>
            </div>
            <div class="summary-item">
                <div class="summary-number error-number"><?php echo $errorCount; ?></div>
                <div class="summary-label">Errors</div>
            </div>
            <div class="summary-item">
                <div class="summary-number"><?php echo $totalChecks; ?></div>
                <div class="summary-label">Total Checks</div>
            </div>
        </div>
        
        <h2 style="margin: 30px 0 15px 0;">Check Results</h2>
        
        <?php foreach ($checks as $check): ?>
            <div class="check-item <?php echo $check['status']; ?>">
                <div class="check-name">
                    <?php echo htmlspecialchars($check['name']); ?>
                    <span class="badge <?php echo $check['status']; ?>">
                        <?php echo strtoupper($check['status']); ?>
                    </span>
                </div>
                <?php if (isset($check['message'])): ?>
                    <div class="check-message"><?php echo htmlspecialchars($check['message']); ?></div>
                <?php endif; ?>
            </div>
        <?php endforeach; ?>
        
        <div class="actions">
            <a href="setup.php" class="btn">Run Setup Again</a>
            <a href="dashboard.php" class="btn btn-secondary">Go to Dashboard</a>
            <a href="javascript:location.reload()" class="btn btn-secondary">Refresh Check</a>
        </div>
    </div>
</body>
</html>
