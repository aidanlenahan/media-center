<?php
require_once '../includes/config.php';
require_once '../includes/functions.php';

$error = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $username = sanitizeInput($_POST['username'] ?? '');
    $password = $_POST['password'] ?? '';
    
    if ($username && $password) {
        try {
            $stmt = $pdo->prepare("SELECT id, username, password_hash FROM librarians WHERE username = ?");
            $stmt->execute([$username]);
            $user = $stmt->fetch();
            
            if ($user && password_verify($password, $user['password_hash'])) {
                $_SESSION['librarian_id'] = $user['id'];
                $_SESSION['librarian_username'] = $user['username'];
                header('Location: dashboard.php');
                exit;
            } else {
                $error = 'Invalid username or password.';
            }
        } catch (Exception $e) {
            $error = 'Login failed. Please try again.';
            error_log($e->getMessage());
        }
    } else {
        $error = 'Please enter both username and password.';
    }
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Librarian Login - Media Center Pass System</title>
    <link rel="stylesheet" href="css/style.css">
</head>
<body>
    <div class="login-container">
        <div class="container">
            <div class="header">
                <h1>Librarian Login</h1>
                <p>Media Center Pass System</p>
            </div>
            
            <?php if ($error): ?>
                <div class="alert alert-danger">
                    <strong>Error:</strong> <?php echo $error; ?>
                </div>
            <?php endif; ?>
            
            <form method="POST" action="">
                <div class="form-group">
                    <label for="username">Username <span class="required">*</span></label>
                    <input type="text" id="username" name="username" required autofocus>
                </div>
                
                <div class="form-group">
                    <label for="password">Password <span class="required">*</span></label>
                    <input type="password" id="password" name="password" required>
                </div>
                
                <button type="submit" class="btn btn-primary" style="width: 100%;">Login</button>
            </form>
            
            <p style="text-align: center; margin-top: 20px; color: #666;">
                <a href="../public/index.php" style="color: #667eea; text-decoration: none;">Back to Pass Request Form</a>
            </p>
        </div>
    </div>
</body>
</html>
