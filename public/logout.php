<?php
require_once '../includes/config.php';

// Destroy session
session_destroy();

// Redirect to login
header('Location: admin_login.php');
exit;
?>
