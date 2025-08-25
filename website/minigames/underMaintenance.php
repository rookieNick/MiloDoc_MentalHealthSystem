<?php
session_start();

// Check login
if (!isset($_SESSION["user"]) || $_SESSION["user"] == "" || $_SESSION["usertype"] != "p") {
    header("location: ../login.php");
    exit;
}

// Get game name if passed by URL (optional)
$gameName = isset($_GET['game']) ? htmlspecialchars($_GET['game']) : "This game";
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>Under Maintenance</title>
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <link rel="stylesheet" href="../css/animations.css">
    <link rel="stylesheet" href="../css/main.css">
    <style>
        body {
            display: flex;
            justify-content: center;
            align-items: center;
            min-height: 100vh;
            background-color: #f9f9f9;
            margin: 0;
            font-family: Arial, sans-serif;
        }
        .maintenance-container {
            animation: transitionIn-Y-bottom 0.5s;
            text-align: center;
            background: #fff;
            padding: 40px;
            border-radius: 15px;
            box-shadow: 0 4px 10px rgba(0,0,0,0.1);
        }
        .maintenance-title {
            font-size: 2rem;
            color: #333;
            margin-bottom: 20px;
        }
        .maintenance-message {
            font-size: 1.2rem;
            color: #555;
            margin-bottom: 30px;
        }
        .back-home-btn {
            display: inline-block;
            padding: 12px 24px;
            background-color: #4CAF50;
            color: #fff;
            font-size: 1rem;
            border-radius: 8px;
            text-decoration: none;
            transition: background-color 0.3s;
        }
        .back-home-btn:hover {
            background-color: #45a049;
        }
    </style>
</head>
<body>

<div class="maintenance-container">
    <div class="maintenance-title">🚧 Under Maintenance 🚧</div>
    <div class="maintenance-message">
        <?php echo $gameName; ?> is currently unavailable.<br>
        We are working hard to bring it back soon!
    </div>
    <a href="gameLists.php" class="back-home-btn">Back to Home</a>
</div>

</body>
</html>
