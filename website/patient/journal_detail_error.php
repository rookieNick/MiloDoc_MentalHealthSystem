<?php
session_start();
$previous_url = isset($_SERVER['HTTP_REFERER']) ? $_SERVER['HTTP_REFERER'] : 'index.php'; // Default to index if no referrer
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Error</title>
    <link rel="stylesheet" href="../css/main.css"> <!-- Use your existing CSS -->
    <style>
        .error-container {
            text-align: center;
            padding: 50px;
        }
        .error-title {
            font-size: 24px;
            color: red;
        }
        .error-message {
            font-size: 18px;
            margin: 20px 0;
        }
        .back-btn {
            display: inline-block;
            padding: 10px 20px;
            font-size: 16px;
            color: white;
            background-color: #007bff;
            text-decoration: none;
            border-radius: 5px;
        }
        .back-btn:hover {
            background-color: #0056b3;
        }
    </style>
</head>
<body>
    <div class="error-container">
        <h1 class="error-title">No Journal Found.</h1>
        <p class="error-message">We couldn't process your request. Please try again later.</p>
        <a href="<?php echo htmlspecialchars($previous_url); ?>" class="back-btn">Go Back</a>
    </div>
</body>
</html>
