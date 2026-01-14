<?php
require_once 'includes/config.php';
require_once 'includes/functions.php';

requireAdmin();

// Check if user is root
$stmt = $pdo->prepare("SELECT role FROM librarians WHERE id = ?");
$stmt->execute([$_SESSION['librarian_id']]);
$user = $stmt->fetch();

if ($user['role'] !== 'root') {
    header('Location: dashboard.php');
    exit;
}

$message = '';
$messageType = '';

// Handle form submissions
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $action = $_POST['action'] ?? '';
    
    if ($action === 'update_dev_settings') {
        try {
            $debugMode = isset($_POST['debug_mode']) ? 1 : 0;
            $showSqlQueries = isset($_POST['show_sql_queries']) ? 1 : 0;
            $logAllActions = isset($_POST['log_all_actions']) ? 1 : 0;
            $bypassTimeRestrictions = isset($_POST['bypass_time_restrictions']) ? 1 : 0;
            $testMode = isset($_POST['test_mode']) ? 1 : 0;
            $allowDuplicatePasses = isset($_POST['allow_duplicate_passes']) ? 1 : 0;
            $requireSchoolEmail = isset($_POST['require_school_email']) ? 1 : 0;
            $emailOverride = $_POST['email_override_address'] ?? null;
            
            $stmt = $pdo->prepare("
                UPDATE dev_settings SET 
                debug_mode = ?, 
                show_sql_queries = ?, 
                log_all_actions = ?,
                bypass_time_restrictions = ?,
                test_mode = ?,
                allow_duplicate_passes = ?,
                require_school_email = ?,
                email_override_address = ?
                WHERE id = 1
            ");
            $stmt->execute([$debugMode, $showSqlQueries, $logAllActions, $bypassTimeRestrictions, $testMode, $allowDuplicatePasses, $requireSchoolEmail, $emailOverride]);
            
            $message = 'Developer settings updated successfully.';
            $messageType = 'success';
        } catch (Exception $e) {
            $message = 'Error updating settings: ' . $e->getMessage();
            $messageType = 'danger';
        }
    } elseif ($action === 'send_test_pass') {
        try {
            $testEmail = sanitizeInput($_POST['test_email']);
            $testMod = (int)$_POST['test_mod'];
            
            if (!filter_var($testEmail, FILTER_VALIDATE_EMAIL)) {
                throw new Exception('Invalid email address');
            }
            
            if ($testMod < 1 || $testMod > 8) {
                throw new Exception('Invalid mod selection');
            }
            
            $passCode = generatePassCode();
            $testActivities = ['Testing'];
            
            // Insert test pass
            $stmt = $pdo->prepare("
                INSERT INTO passes_current 
                (first_name, last_name, email, teacher_name, `mod`, activities, agreement_checked, status, pass_code)
                VALUES ('Test', 'User', ?, 'Developer Test', ?, ?, 1, 'approved', ?)
            ");
            $stmt->execute([$testEmail, $testMod, json_encode($testActivities), $passCode]);
            
            // Send email
            $result = sendPassEmail($testEmail, 'Test', 'User', $passCode, $testMod, $testActivities);
            
            if ($result) {
                $message = "Test pass sent successfully to $testEmail (Mod $testMod). Pass Code: $passCode";
                $messageType = 'success';
            } else {
                $message = "Pass created but email failed to send. Pass Code: $passCode";
                $messageType = 'warning';
            }
        } catch (Exception $e) {
            $message = 'Error sending test pass: ' . $e->getMessage();
            $messageType = 'danger';
        }
    } elseif ($action === 'clear_all_passes') {
        try {
            $pdo->exec("TRUNCATE TABLE passes_current");
            $message = 'All current passes cleared successfully.';
            $messageType = 'success';
        } catch (Exception $e) {
            $message = 'Error clearing passes: ' . $e->getMessage();
            $messageType = 'danger';
        }
    } elseif ($action === 'reset_settings') {
        try {
            $pdo->exec("UPDATE settings SET form_auto_open = 1, form_open_time = '07:30:00', form_close_time = '14:30:00', auto_approval = 0, disable_weekends = 0, form_status_override = 0 WHERE id = 1");
            $message = 'Settings reset to default values.';
            $messageType = 'success';
        } catch (Exception $e) {
            $message = 'Error resetting settings: ' . $e->getMessage();
            $messageType = 'danger';
        }
    }
}

// Get current dev settings
$devSettings = $pdo->query("SELECT * FROM dev_settings LIMIT 1")->fetch();
$systemInfo = [
    'php_version' => phpversion(),
    'mysql_version' => $pdo->query("SELECT VERSION()")->fetchColumn(),
    'server_software' => $_SERVER['SERVER_SOFTWARE'] ?? 'Unknown',
    'document_root' => $_SERVER['DOCUMENT_ROOT'] ?? 'Unknown',
    'phpmailer_installed' => class_exists('PHPMailer\PHPMailer\PHPMailer'),
];
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Developer Panel - Media Center Pass System</title>
    <link rel="stylesheet" href="css/style.css">
    <style>
        .dev-header {
            background: linear-gradient(135deg, #e74c3c 0%, #c0392b 100%);
            color: white;
            padding: 15px;
            border-radius: 4px;
            margin-bottom: 20px;
        }
        .dev-section {
            background: #f8f9fa;
            border-left: 4px solid #e74c3c;
            padding: 20px;
            border-radius: 4px;
            margin-bottom: 20px;
        }
        .system-info {
            display: grid;
            grid-template-columns: 200px 1fr;
            gap: 10px;
            font-size: 0.9em;
        }
        .system-info dt {
            font-weight: bold;
            color: #333;
        }
        .system-info dd {
            color: #666;
            margin: 0;
        }
        .danger-zone {
            border-left-color: #e74c3c;
            background: #fff5f5;
        }
        .btn-danger {
            background: #e74c3c;
        }
        .btn-danger:hover {
            background: #c0392b;
        }
    </style>
</head>
<body>
    <div class="container">
        <div class="dev-header">
            <h1>⚙️ Developer Panel</h1>
            <p>Root User Access Only - Advanced Settings & Tools</p>
        </div>
        
        <div style="margin-bottom: 20px;">
            <a href="dashboard.php" class="btn btn-secondary">← Back to Dashboard</a>
            <a href="logout.php" class="btn btn-secondary" style="margin-left: 10px;">Logout</a>
        </div>
        
        <?php if ($message): ?>
            <div class="alert alert-<?php echo $messageType; ?>">
                <?php echo htmlspecialchars($message); ?>
            </div>
        <?php endif; ?>
        
        <!-- System Information -->
        <div class="dev-section">
            <h2>System Information</h2>
            <dl class="system-info">
                <dt>PHP Version:</dt>
                <dd><?php echo htmlspecialchars($systemInfo['php_version']); ?></dd>
                
                <dt>MySQL Version:</dt>
                <dd><?php echo htmlspecialchars($systemInfo['mysql_version']); ?></dd>
                
                <dt>Server Software:</dt>
                <dd><?php echo htmlspecialchars($systemInfo['server_software']); ?></dd>
                
                <dt>Document Root:</dt>
                <dd><?php echo htmlspecialchars($systemInfo['document_root']); ?></dd>
                
                <dt>PHPMailer:</dt>
                <dd><?php echo $systemInfo['phpmailer_installed'] ? '<span style="color: green;">✓ Installed</span>' : '<span style="color: red;">✗ Not Found</span>'; ?></dd>
                
                <dt>Database:</dt>
                <dd><?php echo htmlspecialchars(DB_NAME); ?> @ <?php echo htmlspecialchars(DB_HOST); ?></dd>
            </dl>
        </div>
        
        <!-- Developer Settings -->
        <div class="dev-section">
            <h2>Developer Settings</h2>
            <form method="POST" action="">
                <input type="hidden" name="action" value="update_dev_settings">
                
                <div class="form-group">
                    <label style="display: flex; align-items: center; margin-bottom: 10px;">
                        <input type="checkbox" name="debug_mode" <?php echo $devSettings['debug_mode'] ? 'checked' : ''; ?> style="width: auto; margin-right: 10px;">
                        <span><strong>Debug Mode</strong> - Display detailed error messages and debugging info</span>
                    </label>
                </div>
                
                <div class="form-group">
                    <label style="display: flex; align-items: center; margin-bottom: 10px;">
                        <input type="checkbox" name="show_sql_queries" <?php echo $devSettings['show_sql_queries'] ? 'checked' : ''; ?> style="width: auto; margin-right: 10px;">
                        <span><strong>Show SQL Queries</strong> - Log all database queries for debugging</span>
                    </label>
                </div>
                
                <div class="form-group">
                    <label style="display: flex; align-items: center; margin-bottom: 10px;">
                        <input type="checkbox" name="log_all_actions" <?php echo $devSettings['log_all_actions'] ? 'checked' : ''; ?> style="width: auto; margin-right: 10px;">
                        <span><strong>Log All Actions</strong> - Record every user action and system event</span>
                    </label>
                </div>
                
                <div class="form-group">
                    <label style="display: flex; align-items: center; margin-bottom: 10px;">
                        <input type="checkbox" name="bypass_time_restrictions" <?php echo $devSettings['bypass_time_restrictions'] ? 'checked' : ''; ?> style="width: auto; margin-right: 10px;">
                        <span><strong>Bypass Time Restrictions</strong> - Allow form submission anytime (ignores open/close times)</span>
                    </label>
                </div>
                
                <div class="form-group">
                    <label style="display: flex; align-items: center; margin-bottom: 10px;">
                        <input type="checkbox" name="test_mode" <?php echo $devSettings['test_mode'] ? 'checked' : ''; ?> style="width: auto; margin-right: 10px;">
                        <span><strong>Test Mode</strong> - Prevent emails from being sent (for testing)</span>
                    </label>
                </div>
                
                <div class="form-group">
                    <label style="display: flex; align-items: center; margin-bottom: 10px;">
                        <input type="checkbox" name="allow_duplicate_passes" <?php echo $devSettings['allow_duplicate_passes'] ? 'checked' : ''; ?> style="width: auto; margin-right: 10px;">
                        <span><strong>Allow Duplicate Passes</strong> - Allow same student to submit multiple passes per mod</span>
                    </label>
                </div>
                
                <div class="form-group">
                    <label style="display: flex; align-items: center; margin-bottom: 10px;">
                        <input type="checkbox" name="require_school_email" <?php echo $devSettings['require_school_email'] ? 'checked' : ''; ?> style="width: auto; margin-right: 10px;">
                        <span><strong>Require School Email</strong> - Only accept @students.rbrhs.org email addresses</span>
                    </label>
                </div>
                
                <div class="form-group">
                    <label for="email_override">Email Override Address</label>
                    <small style="color: #666; display: block; margin-bottom: 5px;">Send ALL emails to this address instead of actual recipients (for testing)</small>
                    <input type="email" id="email_override" name="email_override_address" 
                           value="<?php echo htmlspecialchars($devSettings['email_override_address'] ?? ''); ?>"
                           placeholder="developer@example.com">
                </div>
                
                <button type="submit" class="btn btn-primary">Save Developer Settings</button>
            </form>
        </div>
        
        <!-- Test Pass Sending -->
        <div class="dev-section">
            <h2>Send Test Pass</h2>
            <p style="color: #666; margin-bottom: 15px;">Create and send a test pass to any email address for Mod 1 or Mod 2.</p>
            
            <form method="POST" action="">
                <input type="hidden" name="action" value="send_test_pass">
                
                <div class="form-group">
                    <label for="test_email">Recipient Email <span class="required">*</span></label>
                    <input type="email" id="test_email" name="test_email" required placeholder="student@example.com">
                </div>
                
                <div class="form-group">
                    <label for="test_mod">Mod Selection <span class="required">*</span></label>
                    <select id="test_mod" name="test_mod" required>
                        <option value="1">Mod 1</option>
                        <option value="2">Mod 2</option>
                        <option value="3">Mod 3</option>
                        <option value="4">Mod 4</option>
                        <option value="5">Mod 5</option>
                        <option value="6">Mod 6</option>
                        <option value="7">Mod 7</option>
                        <option value="8">Mod 8</option>
                    </select>
                </div>
                
                <button type="submit" class="btn btn-success">📤 Send Test Pass</button>
            </form>
        </div>
        
        <!-- Quick Actions -->
        <div class="dev-section">
            <h2>Quick Actions</h2>
            <div style="display: flex; gap: 10px; flex-wrap: wrap;">
                <a href="check_database.php" class="btn btn-primary" target="_blank">🔍 Check Database Status</a>
                <a href="test_email.php" class="btn btn-primary" target="_blank">📧 Test Email Configuration</a>
                <a href="setup.php" class="btn btn-primary" target="_blank">⚙️ Re-run Setup</a>
            </div>
        </div>
        
        <!-- Danger Zone -->
        <div class="dev-section danger-zone">
            <h2 style="color: #e74c3c;">⚠️ Danger Zone</h2>
            <p style="color: #666; margin-bottom: 15px;">These actions are irreversible. Use with caution.</p>
            
            <form method="POST" action="" onsubmit="return confirm('Are you sure you want to CLEAR ALL CURRENT PASSES? This cannot be undone!');" style="display: inline-block; margin-right: 10px;">
                <input type="hidden" name="action" value="clear_all_passes">
                <button type="submit" class="btn btn-danger">🗑️ Clear All Current Passes</button>
            </form>
            
            <form method="POST" action="" onsubmit="return confirm('Reset all settings to default values?');" style="display: inline-block;">
                <input type="hidden" name="action" value="reset_settings">
                <button type="submit" class="btn btn-danger">🔄 Reset Settings to Default</button>
            </form>
        </div>
    </div>
</body>
</html>
