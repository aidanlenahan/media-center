<?php
require_once '../includes/config.php';
require_once '../includes/functions.php';

requireAdmin();

// Check if user is root
$stmt = $pdo->prepare("SELECT role FROM librarians WHERE id = ?");
$stmt->execute([$_SESSION['librarian_id']]);
$user = $stmt->fetch();

if ($user['role'] !== 'root') {
    header('Location: ../dashboard.php');
    exit;
}

$testResult = null;
$errorMsg = null;

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $testEmail = $_POST['test_email'] ?? '';
    
    if ($testEmail && filter_var($testEmail, FILTER_VALIDATE_EMAIL)) {
        try {
            $result = sendPassEmail(
                $testEmail,
                'Test',
                'Student',
                'TEST' . strtoupper(bin2hex(random_bytes(3))),
                3,
                ['Studying', 'Reading']
            );
            
            if ($result) {
                $testResult = 'success';
            } else {
                $testResult = 'fail';
                $errorMsg = 'Email function returned false. Check error logs.';
            }
        } catch (Exception $e) {
            $testResult = 'fail';
            $errorMsg = $e->getMessage();
        }
    } else {
        $testResult = 'fail';
        $errorMsg = 'Invalid email address provided.';
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Test Email Configuration</title>
    <link rel="stylesheet" href="css/style.css">
</head>
<body>
    <div class="container" style="max-width: 600px;">
        <div class="header">
            <h1>📧 Test Email Configuration</h1>
            <p>Send a test pass email to verify Gmail SMTP setup</p>
        </div>
        
        <?php if ($testResult === 'success'): ?>
            <div class="alert alert-success">
                <strong>✅ Success!</strong> Test email sent to <?php echo htmlspecialchars($_POST['test_email']); ?><br>
                Check your inbox (and spam folder) for the pass email.
            </div>
        <?php elseif ($testResult === 'fail'): ?>
            <div class="alert alert-danger">
                <strong>❌ Failed!</strong> Email could not be sent.<br>
                <?php if ($errorMsg): ?>
                    <strong>Error:</strong> <?php echo htmlspecialchars($errorMsg); ?>
                <?php endif; ?>
            </div>
            
            <div class="alert alert-info" style="margin-top: 15px;">
                <strong>Troubleshooting Steps:</strong>
                <ol style="margin: 10px 0 0 20px; line-height: 1.8;">
                    <li>Install PHPMailer: <code>composer require phpmailer/phpmailer</code></li>
                    <li>Verify Gmail App Password in <code>includes/email_config.php</code></li>
                    <li>Check that 2-Step Verification is enabled on your Google Account</li>
                    <li>Make sure port 587 is not blocked by firewall</li>
                    <li>Check PHP error logs for details</li>
                </ol>
            </div>
        <?php endif; ?>
        
        <form method="POST" action="">
            <div class="form-group">
                <label for="test_email">Enter email address to test: <span class="required">*</span></label>
                <input type="email" id="test_email" name="test_email" required 
                       placeholder="student@example.com" 
                       value="<?php echo htmlspecialchars($_POST['test_email'] ?? ''); ?>">
            </div>
            
            <button type="submit" class="btn btn-primary" style="width: 100%;">
                📤 Send Test Email
            </button>
        </form>
        
        <div style="margin-top: 30px; padding: 15px; background: #f8f9fa; border-radius: 4px; font-size: 0.9em;">
            <strong>Current Configuration:</strong>
            <ul style="margin: 10px 0 0 20px; line-height: 1.6;">
                <li><strong>SMTP Host:</strong> <?php echo SMTP_HOST; ?></li>
                <li><strong>Port:</strong> <?php echo SMTP_PORT; ?></li>
                <li><strong>Security:</strong> <?php echo strtoupper(SMTP_SECURE); ?></li>
                <li><strong>From Email:</strong> <?php echo SMTP_FROM_EMAIL; ?></li>
                <li><strong>PHPMailer:</strong> 
                    <?php 
                    if (class_exists('PHPMailer\PHPMailer\PHPMailer')) {
                        echo '<span style="color: green;">✓ Installed</span>';
                    } else {
                        echo '<span style="color: red;">✗ Not Found</span> - Run: <code>composer require phpmailer/phpmailer</code>';
                    }
                    ?>
                </li>
            </ul>
        </div>
        
        <div style="margin-top: 20px; text-align: center;">
            <a href="../dev_panel.php" class="btn btn-secondary" style="display: inline-block; padding: 10px 20px; text-decoration: none;">
                ← Back to Developer Panel
            </a>
            <a href="check_database.php" class="btn btn-secondary" style="display: inline-block; padding: 10px 20px; text-decoration: none; margin-left: 10px;">
                Database Check
            </a>
            <a href="../dashboard.php" class="btn btn-secondary" style="display: inline-block; padding: 10px 20px; text-decoration: none; margin-left: 10px;">
                Dashboard
            </a>
        </div>
    </div>
</body>
</html>
