<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>403 Forbidden - Access Denied</title>
    <link rel="icon" type="image/svg+xml" href="img/buc.svg">
    <link rel="stylesheet" href="css/style.css">
    <style>
        .error-container {
            text-align: center;
            padding: 60px 20px;
        }
        .error-code {
            font-size: 8em;
            font-weight: bold;
            color: #690000;
            margin: 0;
            line-height: 1;
        }
        .error-message {
            font-size: 1.5em;
            color: #333;
            margin: 20px 0;
        }
        .error-description {
            color: #666;
            margin: 20px 0 40px 0;
            font-size: 1.1em;
        }
        .back-button {
            display: inline-block;
            padding: 15px 30px;
            background: #690000;
            color: white;
            text-decoration: none;
            border-radius: 6px;
            font-weight: 600;
            transition: all 0.3s;
        }
        .back-button:hover {
            background: #4a0000;
            transform: translateY(-2px);
            box-shadow: 0 5px 15px rgba(105, 0, 0, 0.4);
        }
    </style>
</head>
<body>
    <div class="container">
        <div class="error-container">
            <h1 class="error-code">403</h1>
            <h2 class="error-message">Access Forbidden</h2>
            <p class="error-description">
                You don't have permission to access this resource.<br>
                This area is restricted for security purposes.
            </p>
            <a href="form.php" class="back-button">← Go to Pass Request Form</a>
        </div>
    </div>
</body>
</html>
